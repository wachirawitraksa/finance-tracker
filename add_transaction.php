<?php
require 'backend/config.php';
requireLogin();

$message = '';

if ($_POST) {
    $type = $_POST['type'] ?? '';
    $category = $_POST['category'] ?? '';
    $amount = $_POST['amount'] ?? 0;
    $description = $_POST['description'] ?? '';
    $date = $_POST['transaction_date'] ?? date('Y-m-d');
    
    if ($type && $category && $amount > 0) {
        $stmt = $pdo->prepare("
            INSERT INTO transactions (user_id, type, category, amount, description, transaction_date) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        if ($stmt->execute([$_SESSION['user_id'], $type, $category, $amount, $description, $date])) {
            $message = 'บันทึกรายการสำเร็จ';
        } else {
            $message = 'เกิดข้อผิดพลาด';
        }
    } else {
        $message = 'กรุณากรอกข้อมูลให้ครบถ้วน';
    }
}

$display_name = $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'ผู้ใช้งาน';

// Categories array - you'll need to define this in your config or here
$categories = [
    'income' => ['เงินเดือน', 'ธุรกิจ', 'ลงทุน', 'ของขวัญ', 'อื่นๆ'],
    'expense' => ['อาหาร', 'เดินทาง', 'ช้อปปิ้ง', 'ค่าใช้จ่ายบ้าน', 'สุขภาพ', 'บันเทิง', 'การศึกษา', 'อื่นๆ']
];
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Record</title>
    <link rel="shortcut icon" href="src/logo/logo.png" type="image/x-icon">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="styles/add_styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-wallet me-2"></i>MoneyLog
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">
                            <i class="fas fa-home me-1"></i>หน้าหลัก
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="add_transaction.php">
                            <i class="fas fa-plus me-1"></i>เพิ่มรายการ
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="transactions.php">
                            <i class="fas fa-list me-1"></i>ดูรายการทั้งหมด
                        </a>
                    </li>
                </ul>
                
                <div class="d-flex align-items-center">
                    <span class="user-info me-3">
                        <i class="fas fa-user me-1"></i>สวัสดี, <?= htmlspecialchars($display_name) ?>
                    </span>
                    <a href="logout.php" class="logout-btn">
                        <i class="fas fa-sign-out-alt me-1"></i>ออกจากระบบ
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container">
        <div class="form-container">
            <h2>
                <i class="fas fa-plus-circle me-2"></i>เพิ่มรายการรายรับ-รายจ่าย
            </h2>
            
            <?php if ($message): ?>
                <div class="alert <?= strpos($message, 'สำเร็จ') !== false ? 'alert-success' : 'alert-danger' ?>">
                    <i class="fas <?= strpos($message, 'สำเร็จ') !== false ? 'fa-check-circle' : 'fa-exclamation-circle' ?> me-2"></i>
                    <?= $message ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-tags form-icon"></i>ประเภท:
                    </label>
                    <div class="radio-group">
                        <div class="radio-item income">
                            <input type="radio" name="type" value="income" id="income" onchange="updateCategories()" required>
                            <label for="income" class="radio-label">
                                <i class="fas fa-arrow-up me-2"></i>รายรับ
                            </label>
                        </div>
                        <div class="radio-item expense">
                            <input type="radio" name="type" value="expense" id="expense" onchange="updateCategories()" required>
                            <label for="expense" class="radio-label">
                                <i class="fas fa-arrow-down me-2"></i>รายจ่าย
                            </label>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="category" class="form-label">
                        <i class="fas fa-list form-icon"></i>หมวดหมู่:
                    </label>
                    <select name="category" id="category" class="form-select" required>
                        <option value="">เลือกหมวดหมู่</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="amount" class="form-label">
                        <i class="fas fa-money-bill-wave form-icon"></i>จำนวนเงิน:
                    </label>
                    <input type="number" name="amount" id="amount" class="form-control" step="0.01" min="0.01" placeholder="0.00" required>
                </div>

                <div class="form-group">
                    <label for="description" class="form-label">
                        <i class="fas fa-comment form-icon"></i>รายละเอียด:
                    </label>
                    <textarea name="description" id="description" class="form-control" rows="3" placeholder="รายละเอียดเพิ่มเติม (ไม่บังคับ)"></textarea>
                </div>

                <div class="form-group">
                    <label for="transaction_date" class="form-label">
                        <i class="fas fa-calendar-alt form-icon"></i>วันที่:
                    </label>
                    <input type="date" name="transaction_date" id="transaction_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>

                <button type="submit" class="btn btn-submit">
                    <i class="fas fa-save me-2"></i>บันทึกรายการ
                </button>
            </form>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        const categories = <?= json_encode($categories) ?>;
        
        function updateCategories() {
            const type = document.querySelector('input[name="type"]:checked').value;
            const categorySelect = document.getElementById('category');
            
            categorySelect.innerHTML = '<option value="">เลือกหมวดหมู่</option>';
            
            categories[type].forEach(cat => {
                const option = document.createElement('option');
                option.value = cat;
                option.textContent = cat;
                categorySelect.appendChild(option);
            });
        }
        
        // Auto-focus on first input
        document.addEventListener('DOMContentLoaded', function() {
            const firstRadio = document.querySelector('input[name="type"]');
            if (firstRadio) {
                firstRadio.focus();
            }
        });
    </script>
</body>
</html>