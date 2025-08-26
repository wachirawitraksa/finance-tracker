-- สร้างฐานข้อมูล
CREATE DATABASE IF NOT EXISTS finance_tracker CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE finance_tracker;

-- ตารางผู้ใช้ (อัพเดท)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    profile_image VARCHAR(255) DEFAULT NULL,
    provider ENUM('local', 'google') DEFAULT 'local',
    provider_id VARCHAR(100) DEFAULT NULL,
    email_verified BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_username (username),
    INDEX idx_provider (provider, provider_id)
);

-- ตารางธุรกรรม
CREATE TABLE transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('income', 'expense') NOT NULL,
    category VARCHAR(50) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    description TEXT,
    transaction_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_date (user_id, transaction_date),
    INDEX idx_user_type (user_id, type),
    INDEX idx_user_category (user_id, category)
);

-- ตารางหมวดหมู่ (เพิ่มเติม)
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(50) NOT NULL,
    type ENUM('income', 'expense') NOT NULL,
    color VARCHAR(7) DEFAULT '#007bff',
    icon VARCHAR(50) DEFAULT 'fas fa-circle',
    is_default BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_category (user_id, name, type),
    INDEX idx_user_type (user_id, type)
);

-- ตารางงบประมาณ (เพิ่มเติม)
CREATE TABLE budgets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category VARCHAR(50) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    month TINYINT NOT NULL,
    year INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_budget (user_id, category, month, year),
    INDEX idx_user_period (user_id, year, month)
);

-- ตารางการตั้งค่า (เพิ่มเติม)
CREATE TABLE user_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    currency VARCHAR(10) DEFAULT 'THB',
    language VARCHAR(10) DEFAULT 'th',
    timezone VARCHAR(50) DEFAULT 'Asia/Bangkok',
    date_format VARCHAR(20) DEFAULT 'DD/MM/YYYY',
    notification_email BOOLEAN DEFAULT TRUE,
    notification_budget BOOLEAN DEFAULT TRUE,
    theme VARCHAR(20) DEFAULT 'light',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_settings (user_id)
);

-- ตารางการเชื่อมต่อโซเชียล (อัพเดท - เหลือแค่ Google)
CREATE TABLE social_connections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    provider ENUM('google') NOT NULL,
    provider_id VARCHAR(100) NOT NULL,
    access_token TEXT,
    refresh_token TEXT,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_provider_id (provider, provider_id),
    INDEX idx_user_provider (user_id, provider)
);

-- ตารางเซสชัน (เพิ่มเติม)
CREATE TABLE user_sessions (
    id VARCHAR(128) PRIMARY KEY,
    user_id INT NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_last_activity (last_activity)
);

-- ตารางการรีเซ็ตรหัสผ่าน (เพิ่มเติม)
CREATE TABLE password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(255) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    used BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_user_id (user_id)
);

-- ตัวอย่างข้อมูลผู้ใช้ (รหัสผ่าน: 123456)
INSERT INTO users (username, email, password, full_name, provider) VALUES 
('admin', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ผู้ดูแลระบบ', 'local'),
('user1', 'user1@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ผู้ใช้งาน 1', 'local'),
('john_doe', 'john@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John Doe', 'google');

-- ตัวอย่างข้อมูลหมวดหมู่เริ่มต้น
INSERT INTO categories (user_id, name, type, color, icon, is_default) VALUES
-- หมวดหมู่รายรับ
(1, 'เงินเดือน', 'income', '#28a745', 'fas fa-money-bill-wave', TRUE),
(1, 'โบนัส', 'income', '#17a2b8', 'fas fa-gift', TRUE),
(1, 'รายได้เสริม', 'income', '#6f42c1', 'fas fa-coins', TRUE),
(1, 'เงินปันผล', 'income', '#e83e8c', 'fas fa-chart-line', TRUE),
(1, 'รายได้อื่นๆ', 'income', '#6c757d', 'fas fa-plus-circle', TRUE),

-- หมวดหมู่รายจ่าย
(1, 'อาหาร', 'expense', '#fd7e14', 'fas fa-utensils', TRUE),
(1, 'เดินทาง', 'expense', '#20c997', 'fas fa-car', TRUE),
(1, 'ช้อปปิ้ง', 'expense', '#e83e8c', 'fas fa-shopping-bag', TRUE),
(1, 'บิล', 'expense', '#dc3545', 'fas fa-file-invoice', TRUE),
(1, 'สุขภาพ', 'expense', '#198754', 'fas fa-heartbeat', TRUE),
(1, 'บันเทิง', 'expense', '#6f42c1', 'fas fa-film', TRUE),
(1, 'การศึกษา', 'expense', '#0d6efd', 'fas fa-graduation-cap', TRUE),
(1, 'ที่อยู่อาศัย', 'expense', '#795548', 'fas fa-home', TRUE),
(1, 'เสื้อผ้า', 'expense', '#ff9800', 'fas fa-tshirt', TRUE),
(1, 'อื่นๆ', 'expense', '#6c757d', 'fas fa-ellipsis-h', TRUE);

-- คัดลอกหมวดหมู่ให้ผู้ใช้คนอื่น
INSERT INTO categories (user_id, name, type, color, icon, is_default)
SELECT 2, name, type, color, icon, is_default FROM categories WHERE user_id = 1;

-- ตัวอย่างข้อมูลการตั้งค่า
INSERT INTO user_settings (user_id) VALUES (1), (2);

-- ตัวอย่างข้อมูลงบประมาณ
INSERT INTO budgets (user_id, category, amount, month, year) VALUES
(1, 'อาหาร', 10000.00, 1, 2024),
(1, 'เดินทาง', 3000.00, 1, 2024),
(1, 'ช้อปปิ้ง', 5000.00, 1, 2024),
(1, 'บันเทิง', 2000.00, 1, 2024),
(1, 'สุขภาพ', 3000.00, 1, 2024);

-- ตัวอย่างข้อมูลธุรกรรม
INSERT INTO transactions (user_id, type, category, amount, description, transaction_date) VALUES
(1, 'income', 'เงินเดือน', 30000.00, 'เงินเดือนประจำเดือน', '2024-01-01'),
(1, 'expense', 'อาหาร', 350.00, 'ค่าอาหารกลางวัน', '2024-01-01'),
(1, 'expense', 'เดินทาง', 50.00, 'ค่าโดยสารรถเมล์', '2024-01-01'),
(1, 'income', 'โบนัส', 5000.00, 'โบนัสงาน', '2024-01-15'),
(1, 'expense', 'ช้อปปิ้ง', 1200.00, 'ซื้อเสื้อผ้า', '2024-01-16'),
(1, 'expense', 'บิล', 2500.00, 'ค่าไฟฟ้า', '2024-01-20'),
(1, 'expense', 'สุขภาพ', 800.00, 'ค่าหมอ', '2024-01-25'),
(1, 'expense', 'บันเทิง', 450.00, 'ดูหนัง', '2024-01-28'),
(1, 'expense', 'อาหาร', 280.00, 'ค่าอาหารเย็น', '2024-01-29'),
(1, 'expense', 'เดินทาง', 35.00, 'ค่าแท็กซี่', '2024-01-30'),

-- ข้อมูลสำหรับผู้ใช้คนที่ 2
(2, 'income', 'เงินเดือน', 25000.00, 'เงินเดือนประจำเดือน', '2024-01-01'),
(2, 'expense', 'อาหาร', 300.00, 'ค่าอาหารเช้า', '2024-01-01'),
(2, 'expense', 'เดินทาง', 60.00, 'ค่าน้ำมัน', '2024-01-01'),
(2, 'income', 'รายได้เสริม', 3000.00, 'งานพิเศษ', '2024-01-10'),
(2, 'expense', 'บิล', 1800.00, 'ค่าน้ำประปา', '2024-01-15'),
(2, 'expense', 'ช้อปปิ้ง', 900.00, 'ซื้อของใช้ในบ้าน', '2024-01-20');

-- สร้าง View สำหรับข้อมูลสรุป
CREATE VIEW user_summary AS
SELECT 
    u.id as user_id,
    u.username,
    u.full_name,
    u.email,
    u.provider,
    u.profile_image,
    COALESCE(SUM(CASE WHEN t.type = 'income' THEN t.amount ELSE 0 END), 0) as total_income,
    COALESCE(SUM(CASE WHEN t.type = 'expense' THEN t.amount ELSE 0 END), 0) as total_expense,
    COALESCE(SUM(CASE WHEN t.type = 'income' THEN t.amount ELSE -t.amount END), 0) as balance,
    COUNT(t.id) as transaction_count,
    u.created_at as join_date
FROM users u
LEFT JOIN transactions t ON u.id = t.user_id
WHERE u.is_active = 1
GROUP BY u.id, u.username, u.full_name, u.email, u.provider, u.profile_image, u.created_at;

-- สร้าง View สำหรับข้อมูลรายเดือน
CREATE VIEW monthly_summary AS
SELECT 
    t.user_id,
    YEAR(t.transaction_date) as year,
    MONTH(t.transaction_date) as month,
    t.type,
    t.category,
    SUM(t.amount) as total_amount,
    COUNT(t.id) as transaction_count,
    AVG(t.amount) as avg_amount
FROM transactions t
GROUP BY t.user_id, YEAR(t.transaction_date), MONTH(t.transaction_date), t.type, t.category
ORDER BY t.user_id, year DESC, month DESC;

-- สร้าง View สำหรับข้อมูลงบประมาณ
CREATE VIEW budget_summary AS
SELECT 
    b.user_id,
    b.category,
    b.amount as budget_amount,
    b.month,
    b.year,
    COALESCE(SUM(t.amount), 0) as spent_amount,
    (b.amount - COALESCE(SUM(t.amount), 0)) as remaining_amount,
    ROUND((COALESCE(SUM(t.amount), 0) / b.amount) * 100, 2) as usage_percentage
FROM budgets b
LEFT JOIN transactions t ON b.user_id = t.user_id 
    AND b.category = t.category 
    AND MONTH(t.transaction_date) = b.month 
    AND YEAR(t.transaction_date) = b.year
    AND t.type = 'expense'
GROUP BY b.user_id, b.category, b.amount, b.month, b.year;

-- สร้าง Stored Procedure สำหรับสร้างหมวดหมู่เริ่มต้นสำหรับผู้ใช้ใหม่
DELIMITER //
CREATE PROCEDURE CreateDefaultCategories(IN new_user_id INT)
BEGIN
    INSERT INTO categories (user_id, name, type, color, icon, is_default)
    SELECT new_user_id, name, type, color, icon, is_default 
    FROM categories 
    WHERE user_id = 1 AND is_default = TRUE;
    
    INSERT INTO user_settings (user_id) VALUES (new_user_id);
END //
DELIMITER ;

-- สร้าง Stored Procedure สำหรับการลบผู้ใช้
DELIMITER //
CREATE PROCEDURE DeleteUser(IN target_user_id INT)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
    
    DELETE FROM password_resets WHERE user_id = target_user_id;
    DELETE FROM user_sessions WHERE user_id = target_user_id;
    DELETE FROM social_connections WHERE user_id = target_user_id;
    DELETE FROM user_settings WHERE user_id = target_user_id;
    DELETE FROM budgets WHERE user_id = target_user_id;
    DELETE FROM categories WHERE user_id = target_user_id;
    DELETE FROM transactions WHERE user_id = target_user_id;
    DELETE FROM users WHERE id = target_user_id;
    
    COMMIT;
END //
DELIMITER ;

-- สร้าง Stored Procedure สำหรับสถิติผู้ใช้
DELIMITER //
CREATE PROCEDURE GetUserStats(IN target_user_id INT)
BEGIN
    SELECT 
        u.username,
        u.full_name,
        u.email,
        u.provider,
        COUNT(DISTINCT t.id) as total_transactions,
        COALESCE(SUM(CASE WHEN t.type = 'income' THEN t.amount ELSE 0 END), 0) as total_income,
        COALESCE(SUM(CASE WHEN t.type = 'expense' THEN t.amount ELSE 0 END), 0) as total_expense,
        COUNT(DISTINCT c.id) as total_categories,
        COUNT(DISTINCT b.id) as total_budgets,
        u.created_at as join_date
    FROM users u
    LEFT JOIN transactions t ON u.id = t.user_id
    LEFT JOIN categories c ON u.id = c.user_id
    LEFT JOIN budgets b ON u.id = b.user_id
    WHERE u.id = target_user_id
    GROUP BY u.id, u.username, u.full_name, u.email, u.provider, u.created_at;
END //
DELIMITER ;

-- สร้าง Trigger เพื่อสร้างหมวดหมู่เริ่มต้นอัตโนมัติเมื่อมีผู้ใช้ใหม่
DELIMITER //
CREATE TRIGGER after_user_insert
AFTER INSERT ON users
FOR EACH ROW
BEGIN
    IF NEW.provider != 'local' THEN
        CALL CreateDefaultCategories(NEW.id);
    END IF;
END //
DELIMITER ;

-- สร้าง Trigger สำหรับอัปเดตเวลาล่าสุดใน user_sessions
DELIMITER //
CREATE TRIGGER update_session_activity
BEFORE UPDATE ON user_sessions
FOR EACH ROW
BEGIN
    SET NEW.last_activity = CURRENT_TIMESTAMP;
END //
DELIMITER ;

-- สร้าง Event สำหรับลบ session ที่หมดอายุ (รันทุก 1 ชั่วโมง)
CREATE EVENT IF NOT EXISTS cleanup_expired_sessions
ON SCHEDULE EVERY 1 HOUR
DO
DELETE FROM user_sessions WHERE last_activity < DATE_SUB(NOW(), INTERVAL 30 DAY);

-- สร้าง Event สำหรับลบ password reset tokens ที่หมดอายุ
CREATE EVENT IF NOT EXISTS cleanup_expired_tokens
ON SCHEDULE EVERY 1 HOUR
DO
DELETE FROM password_resets WHERE expires_at < NOW();

-- เปิดใช้งาน Event Scheduler
SET GLOBAL event_scheduler = ON;