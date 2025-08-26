<?php
// Start secure session
session_start();

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// เพิ่มการ debug
error_log('Google OAuth accessed: ' . $_SERVER['REQUEST_URI']);
error_log('Current working directory: ' . getcwd());
error_log('Config file exists: ' . (file_exists('../backend/config.php') ? 'YES' : 'NO'));

// ตรวจสอบไฟล์ config.php
if (!file_exists('../backend/config.php')) {
    error_log('Config file not found at: ' . realpath('../backend/config.php'));
    die('Config file not found');
}

require_once '../backend/config.php';

try {
    // Load Google OAuth configuration from JSON file
    $google_config_path = '../backend/env/...';
    
    error_log('Looking for Google config at: ' . realpath($google_config_path));
    
    if (!file_exists($google_config_path)) {
        throw new Exception('Google configuration file not found at: ' . $google_config_path);
    }
    
    $google_config = json_decode(file_get_contents($google_config_path), true);
    if (!$google_config || !isset($google_config['web'])) {
        throw new Exception('Invalid Google configuration format');
    }
    
    $google_client_id = $google_config['web']['client_id'];
    $google_client_secret = $google_config['web']['client_secret'];
    $google_redirect_uri = 'http://localhost/finance_tracker/auth/google_login.php';
    
    error_log('Google Client ID: ' . $google_client_id);
    error_log('Redirect URI: ' . $google_redirect_uri);
    
    // Validate required configuration
    if (empty($google_client_id) || empty($google_client_secret)) {
        throw new Exception('Google OAuth credentials not configured');
    }
    
} catch (Exception $e) {
    error_log('Google OAuth configuration error: ' . $e->getMessage());
    $_SESSION['error'] = 'การกำหนดค่า Google OAuth ผิดพลาด: ' . $e->getMessage();
    header('Location: ../login.php');
    exit();
}

// Generate and store CSRF token for security
if (!isset($_SESSION['oauth_state'])) {
    $_SESSION['oauth_state'] = bin2hex(random_bytes(32));
}

// If no authorization code, redirect to Google OAuth
if (!isset($_GET['code'])) {
    error_log('No authorization code, redirecting to Google OAuth');
    $google_auth_url = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
        'client_id' => $google_client_id,
        'redirect_uri' => $google_redirect_uri,
        'scope' => 'email profile',
        'response_type' => 'code',
        'access_type' => 'offline',
        'prompt' => 'consent',
        'state' => $_SESSION['oauth_state']
    ]);
    header('Location: ' . $google_auth_url);
    exit();
}

error_log('Authorization code received: ' . substr($_GET['code'], 0, 20) . '...');

// Verify CSRF token
if (!isset($_GET['state']) || $_GET['state'] !== $_SESSION['oauth_state']) {
    error_log('CSRF token mismatch. Expected: ' . $_SESSION['oauth_state'] . ', Got: ' . ($_GET['state'] ?? 'none'));
    $_SESSION['error'] = 'Invalid state parameter. Possible CSRF attack.';
    header('Location: ../login.php');
    exit();
}

// Clear the state token
unset($_SESSION['oauth_state']);

// Exchange authorization code for access token
if (isset($_GET['code'])) {
    $token_data = [
        'client_id' => $google_client_id,
        'client_secret' => $google_client_secret,
        'code' => $_GET['code'],
        'grant_type' => 'authorization_code',
        'redirect_uri' => $google_redirect_uri
    ];

    $token_options = [
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/x-www-form-urlencoded',
            'content' => http_build_query($token_data),
            'timeout' => 30
        ]
    ];

    try {
        error_log('Exchanging authorization code for access token');
        $token_context = stream_context_create($token_options);
        $token_response = file_get_contents('https://oauth2.googleapis.com/token', false, $token_context);
        
        if ($token_response === false) {
            error_log('Failed to get token response from Google');
            throw new Exception('Failed to get token from Google');
        }
        
        $token_json = json_decode($token_response, true);
        error_log('Token response: ' . print_r($token_json, true));
        
        if (!isset($token_json['access_token'])) {
            error_log('No access token in response: ' . print_r($token_json, true));
            throw new Exception('No access token received from Google');
        }

        // Get user information using access token
        error_log('Getting user info from Google');
        $user_info_context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => 'Authorization: Bearer ' . $token_json['access_token'],
                'timeout' => 30
            ]
        ]);
        
        $user_info_response = file_get_contents('https://www.googleapis.com/oauth2/v2/userinfo', false, $user_info_context);
        
        if ($user_info_response === false) {
            error_log('Failed to get user info response from Google');
            throw new Exception('Failed to get user info from Google');
        }
        
        $user_info = json_decode($user_info_response, true);
        error_log('User info: ' . print_r($user_info, true));

        if (!isset($user_info['id']) || !isset($user_info['email'])) {
            error_log('Invalid user info structure: ' . print_r($user_info, true));
            throw new Exception('Invalid user information received from Google');
        }

        // Sanitize user data
        $google_id = filter_var($user_info['id'], FILTER_SANITIZE_STRING);
        $email = filter_var($user_info['email'], FILTER_SANITIZE_EMAIL);
        $name = filter_var($user_info['name'] ?? '', FILTER_SANITIZE_STRING);
        $picture = filter_var($user_info['picture'] ?? null, FILTER_SANITIZE_URL);

        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            error_log('Invalid email from Google: ' . $email);
            throw new Exception('Invalid email address from Google');
        }

        try {
            error_log('Connecting to database');
            $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);

            // Check if user exists
            $stmt = $pdo->prepare("SELECT * FROM users WHERE provider = 'google' AND provider_id = ?");
            $stmt->execute([$google_id]);
            $user = $stmt->fetch();

            if ($user) {
                error_log('Existing user found: ' . $user['username']);
                // Existing user - update info and login
                $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, profile_image = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                $stmt->execute([$name, $email, $picture, $user['id']]);
                
                // Regenerate session ID for security
                session_regenerate_id(true);
                
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $name;
                $_SESSION['email'] = $email;
                $_SESSION['profile_image'] = $picture;
                $_SESSION['provider'] = 'google';
                
                error_log('Redirecting existing user to index.php');
                header('Location: ../index.php');
                exit();
            } else {
                // Check if email is already used with different provider
                $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
                $stmt->execute([$email]);
                $existing_user = $stmt->fetch();
                
                if ($existing_user) {
                    error_log('Email already exists with different provider: ' . $email);
                    $_SESSION['error'] = 'อีเมลนี้ถูกใช้แล้วกับบัญชีอื่น กรุณาใช้อีเมลอื่น';
                    header('Location: ../login.php');
                    exit();
                }
                
                error_log('Creating new user');
                // New user - create account
                $username = strtolower(preg_replace('/[^a-zA-Z0-9]/', '_', $name)) . '_' . time();
                $random_password = bin2hex(random_bytes(16));
                $hashed_password = password_hash($random_password, PASSWORD_DEFAULT);
                
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, full_name, profile_image, provider, provider_id, email_verified, created_at) VALUES (?, ?, ?, ?, ?, 'google', ?, 1, CURRENT_TIMESTAMP)");
                $stmt->execute([$username, $email, $hashed_password, $name, $picture, $google_id]);
                
                $user_id = $pdo->lastInsertId();
                error_log('New user created with ID: ' . $user_id);
                
                // Create default categories
                try {
                    $stmt = $pdo->prepare("CALL CreateDefaultCategories(?)");
                    $stmt->execute([$user_id]);
                } catch (PDOException $e) {
                    error_log('Error creating default categories: ' . $e->getMessage());
                    // Continue without failing the login process
                }
                
                // Regenerate session ID for security
                session_regenerate_id(true);
                
                $_SESSION['user_id'] = $user_id;
                $_SESSION['username'] = $username;
                $_SESSION['full_name'] = $name;
                $_SESSION['email'] = $email;
                $_SESSION['profile_image'] = $picture;
                $_SESSION['provider'] = 'google';
                
                error_log('Redirecting new user to index.php');
                header('Location: ../index.php');
                exit();
            }
        } catch (PDOException $e) {
            error_log('Database error in Google OAuth: ' . $e->getMessage());
            $_SESSION['error'] = 'เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล: ' . $e->getMessage();
            header('Location: ../login.php');
            exit();
        }
    } catch (Exception $e) {
        error_log('Google OAuth error: ' . $e->getMessage());
        $_SESSION['error'] = 'การเข้าสู่ระบบด้วย Google ล้มเหลว: ' . $e->getMessage();
        header('Location: ../login.php');
        exit();
    }
}
?>