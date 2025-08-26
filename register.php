<?php
require 'backend/config.php';

// ตรวจสอบถ้าล็อกอินแล้วให้ไปหน้าหลัก
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $full_name = trim($_POST['full_name']);
    
    // ตรวจสอบข้อมูลที่กรอก
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password) || empty($full_name)) {
        $error = 'กรุณากรอกข้อมูลให้ครบถ้วน';
    } elseif ($password !== $confirm_password) {
        $error = 'รหัสผ่านไม่ตรงกัน';
    } elseif (strlen($password) < 6) {
        $error = 'รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'รูปแบบอีเมลไม่ถูกต้อง';
    } else {
        // ตรวจสอบว่าชื่อผู้ใช้หรืออีเมลซ้ำหรือไม่
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        $existing_user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing_user) {
            if ($existing_user['username'] === $username) {
                $error = 'ชื่อผู้ใช้นี้ถูกใช้งานแล้ว';
            } else {
                $error = 'อีเมลนี้ถูกใช้งานแล้ว';
            }
        } else {
            // สร้างบัญชีใหม่
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, full_name, created_at) VALUES (?, ?, ?, ?, NOW())");
            
            if ($stmt->execute([$username, $email, $hashed_password, $full_name])) {
                $success = 'สมัครสมาชิกสำเร็จ! กรุณาเข้าสู่ระบบ';
                // หรือจะให้ล็อกอินอัตโนมัติ
                // $_SESSION['user_id'] = $pdo->lastInsertId();
                // $_SESSION['username'] = $username;
                // header('Location: index.php');
                // exit();
            } else {
                $error = 'เกิดข้อผิดพลาดในการสมัครสมาชิก';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link rel="shortcut icon" href="src/logo/logo.png" type="image/x-icon">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="styles/register_styles.css">
</head>
<body>
    <div class="register-container">
        <div class="register-header">
            <h1>
                <i class="fas fa-user-plus me-2"></i>
                สมัครสมาชิก
            </h1>
            <p>เริ่มต้นใช้งานระบบบันทึกการเงิน</p>
        </div>
        
        <div class="register-form">
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i>
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" id="registerForm">
                <div class="form-floating">
                    <input type="text" class="form-control" id="full_name" name="full_name" placeholder="ชื่อ-นามสกุล" required>
                    <label for="full_name">
                        <i class="fas fa-id-card me-2"></i>ชื่อ-นามสกุล
                    </label>
                </div>
                
                <div class="form-floating">
                    <input type="text" class="form-control" id="username" name="username" placeholder="ชื่อผู้ใช้" required>
                    <label for="username">
                        <i class="fas fa-user me-2"></i>ชื่อผู้ใช้
                    </label>
                </div>
                
                <div class="form-floating">
                    <input type="email" class="form-control" id="email" name="email" placeholder="อีเมล" required>
                    <label for="email">
                        <i class="fas fa-envelope me-2"></i>อีเมล
                    </label>
                </div>
                
                <div class="form-floating position-relative">
                    <input type="password" class="form-control" id="password" name="password" placeholder="รหัสผ่าน" required minlength="6">
                    <label for="password">
                        <i class="fas fa-lock me-2"></i>รหัสผ่าน
                    </label>
                    <i class="fas fa-eye password-toggle" id="togglePassword"></i>
                </div>
                
                <div class="password-strength" id="passwordStrength" style="display: none;">
                    <div class="strength-text">ความแข็งแรงของรหัสผ่าน: <span id="strengthText">อ่อน</span></div>
                    <div class="strength-bar">
                        <div class="strength-fill" id="strengthFill"></div>
                    </div>
                </div>
                
                <div class="form-floating position-relative">
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="ยืนยันรหัสผ่าน" required>
                    <label for="confirm_password">
                        <i class="fas fa-lock me-2"></i>ยืนยันรหัสผ่าน
                    </label>
                    <i class="fas fa-eye password-toggle" id="toggleConfirmPassword"></i>
                </div>
                
                <button type="submit" class="btn btn-primary btn-register">
                    <i class="fas fa-user-plus me-2"></i>
                    สมัครสมาชิก
                </button>
            </form>
            
            <div class="divider">
                <span>หรือสมัครด้วย</span>
            </div>
            
            <div class="social-login">
                <a href="auth/google_register.php" class="btn-social btn-google">
                    <i class="fab fa-google me-2"></i>
                    Google
                </a>
            </div>
            
            <div class="auth-footer">
                <p class="mb-0">
                    มีบัญชีแล้ว? 
                    <a href="login.php">เข้าสู่ระบบ</a>
                </p>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = this;
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        });
        
        document.getElementById('toggleConfirmPassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('confirm_password');
            const toggleIcon = this;
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        });
        
        // Password strength checker
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strengthIndicator = document.getElementById('passwordStrength');
            const strengthText = document.getElementById('strengthText');
            const strengthFill = document.getElementById('strengthFill');
            
            if (password.length === 0) {
                strengthIndicator.style.display = 'none';
                return;
            }
            
            strengthIndicator.style.display = 'block';
            
            let strength = 0;
            let strengthClass = '';
            let strengthLabel = '';
            
            // Check password strength
            if (password.length >= 6) strength += 1;
            if (password.match(/[a-z]+/)) strength += 1;
            if (password.match(/[A-Z]+/)) strength += 1;
            if (password.match(/[0-9]+/)) strength += 1;
            if (password.match(/[^a-zA-Z0-9]+/)) strength += 1;
            
            switch (strength) {
                case 1:
                    strengthClass = 'strength-weak';
                    strengthLabel = 'อ่อน';
                    break;
                case 2:
                    strengthClass = 'strength-fair';
                    strengthLabel = 'พอใช้';
                    break;
                case 3:
                    strengthClass = 'strength-good';
                    strengthLabel = 'ดี';
                    break;
                case 4:
                case 5:
                    strengthClass = 'strength-strong';
                    strengthLabel = 'แข็งแรง';
                    break;
                default:
                    strengthClass = 'strength-weak';
                    strengthLabel = 'อ่อน';
            }
            
            strengthFill.className = 'strength-fill ' + strengthClass;
            strengthText.textContent = strengthLabel;
        });
        
        // Form validation
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const fullName = document.getElementById('full_name').value.trim();
            const username = document.getElementById('username').value.trim();
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (fullName === '' || username === '' || email === '' || password === '' || confirmPassword === '') {
                e.preventDefault();
                alert('กรุณากรอกข้อมูลให้ครบถ้วน');
                return;
            }
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('รหัสผ่านไม่ตรงกัน');
                return;
            }
            
            if (password.length < 6) {
                e.preventDefault();
                alert('รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร');
                return;
            }
            
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                e.preventDefault();
                alert('รูปแบบอีเมลไม่ถูกต้อง');
                return;
            }
        });
        
        // Real-time password confirmation check
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            
            if (confirmPassword !== '' && password !== confirmPassword) {
                this.setCustomValidity('รหัสผ่านไม่ตรงกัน');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html>