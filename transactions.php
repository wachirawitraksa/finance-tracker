<?php
require 'backend/config.php';
requireLogin();

$userId = $_SESSION['user_id'];
$page = $_GET['page'] ?? 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$display_name = $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'ผู้ใช้งาน';

// ตัวกรองข้อมูล
$month = $_GET['month'] ?? '';
$type = $_GET['type'] ?? '';
$category = $_GET['category'] ?? '';

// สร้าง WHERE clause
$whereClause = "WHERE user_id = ?";
$params = [$userId];

if ($month) {
    $whereClause .= " AND DATE_FORMAT(transaction_date, '%Y-%m') = ?";
    $params[] = $month;
}

if ($type) {
    $whereClause .= " AND type = ?";
    $params[] = $type;
}

if ($category) {
    $whereClause .= " AND category = ?";
    $params[] = $category;
}

// นับจำนวนรายการทั้งหมด
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM transactions $whereClause");
$countStmt->execute($params);
$totalRecords = $countStmt->fetchColumn();
$totalPages = ceil($totalRecords / $limit);

// ดึงข้อมูลรายการ
$stmt = $pdo->prepare("
    SELECT * FROM transactions 
    $whereClause 
    ORDER BY transaction_date DESC, created_at DESC 
    LIMIT $limit OFFSET $offset
");
$stmt->execute($params);
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ดึงข้อมูลหมวดหมู่ที่มีในระบบ
$categoriesStmt = $pdo->prepare("SELECT DISTINCT category FROM transactions WHERE user_id = ? ORDER BY category");
$categoriesStmt->execute([$userId]);
$usedCategories = $categoriesStmt->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="shortcut icon" href="src/logo/logo.png" type="image/x-icon">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
        <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="styles/transactions_styles.css">
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
                        <a class="nav-link" href="add_transaction.php">
                            <i class="fas fa-plus me-1"></i>เพิ่มรายการ
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="transactions.php">
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
    <div class="container mt-4">
        <!-- Filter Section -->
        <div class="filter-container">
            <h4>
                <i class="fas fa-filter me-2"></i>กรองข้อมูล
            </h4>
            <form method="GET">
                <div class="row">
                    <div class="col-lg-3 col-md-6 mb-3">
                        <label class="form-label">
                            <i class="fas fa-calendar me-1"></i>เดือน
                        </label>
                        <select name="month" class="form-select">
                            <option value="">ทั้งหมด</option>
                            <?php
                            for ($i = 11; $i >= 0; $i--) {
                                $date = date('Y-m', strtotime("-$i months"));
                                $selected = $date == $month ? 'selected' : '';
                                echo "<option value='$date' $selected>".date('M Y', strtotime($date))."</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-3">
                        <label class="form-label">
                            <i class="fas fa-tag me-1"></i>ประเภท
                        </label>
                        <select name="type" class="form-select">
                            <option value="">ทั้งหมด</option>
                            <option value="income" <?= $type == 'income' ? 'selected' : '' ?>>รายรับ</option>
                            <option value="expense" <?= $type == 'expense' ? 'selected' : '' ?>>รายจ่าย</option>
                        </select>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-3">
                        <label class="form-label">
                            <i class="fas fa-folder me-1"></i>หมวดหมู่
                        </label>
                        <select name="category" class="form-select">
                            <option value="">ทั้งหมด</option>
                            <?php foreach ($usedCategories as $cat): ?>
                                <option value="<?= htmlspecialchars($cat) ?>" <?= $category == $cat ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-3">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-custom">
                                <i class="fas fa-search me-1"></i>กรอง
                            </button>
                            <a href="transactions.php" class="btn btn-outline-custom">
                                <i class="fas fa-times me-1"></i>ล้าง
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <?php if ($transactions): ?>
            <?php
            $totalIncome = 0;
            $totalExpense = 0;
            foreach ($transactions as $t) {
                if ($t['type'] == 'income') $totalIncome += $t['amount'];
                else $totalExpense += $t['amount'];
            }
            ?>
            
            <!-- Summary Section -->
            <div class="summary-container">
                <div class="row">
                    <div class="col-lg-4 col-md-6 mb-4 mb-lg-0">
                        <div class="summary-card income-summary">
                            <div class="summary-icon">
                                <i class="fas fa-arrow-up"></i>
                            </div>
                            <div class="summary-label">รายรับ</div>
                            <div class="summary-value income-value">+<?= number_format($totalIncome, 2) ?> ฿</div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6 mb-4 mb-lg-0">
                        <div class="summary-card expense-summary">
                            <div class="summary-icon">
                                <i class="fas fa-arrow-down"></i>
                            </div>
                            <div class="summary-label">รายจ่าย</div>
                            <div class="summary-value expense-value">-<?= number_format($totalExpense, 2) ?> ฿</div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-12">
                        <div class="summary-card balance-summary">
                            <div class="summary-icon">
                                <i class="fas fa-balance-scale"></i>
                            </div>
                            <div class="summary-label">คงเหลือ</div>
                            <div class="summary-value balance-value"><?= number_format($totalIncome - $totalExpense, 2) ?> ฿</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Transactions Table -->
            <div class="transactions-container">
                <h4>
                    <i class="fas fa-history me-2"></i>รายการทั้งหมด
                    <small class="text-muted">(<?= number_format($totalRecords) ?> รายการ)</small>
                </h4>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th><i class="fas fa-calendar me-1"></i>วันที่</th>
                                <th><i class="fas fa-tag me-1"></i>ประเภท</th>
                                <th><i class="fas fa-folder me-1"></i>หมวดหมู่</th>
                                <th><i class="fas fa-file-text me-1"></i>รายละเอียด</th>
                                <th><i class="fas fa-money-bill me-1"></i>จำนวนเงิน</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $transaction): ?>
                            <tr>
                                <td><?= date('d/m/Y', strtotime($transaction['transaction_date'])) ?></td>
                                <td>
                                    <span class="badge badge-<?= $transaction['type'] == 'income' ? 'income' : 'expense' ?>">
                                        <?= $transaction['type'] == 'income' ? 'รายรับ' : 'รายจ่าย' ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($transaction['category']) ?></td>
                                <td><?= htmlspecialchars($transaction['description']) ?></td>
                                <td class="transaction-amount <?= $transaction['type'] ?>">
                                    <?= $transaction['type'] == 'income' ? '+' : '-' ?><?= number_format($transaction['amount'], 2) ?> ฿
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="pagination-container">
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center mb-0">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=1&month=<?= $month ?>&type=<?= $type ?>&category=<?= $category ?>">
                                    <i class="fas fa-angle-double-left"></i>
                                </a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $page-1 ?>&month=<?= $month ?>&type=<?= $type ?>&category=<?= $category ?>">
                                    <i class="fas fa-angle-left"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page-2); $i <= min($totalPages, $page+2); $i++): ?>
                            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>&month=<?= $month ?>&type=<?= $type ?>&category=<?= $category ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $page+1 ?>&month=<?= $month ?>&type=<?= $type ?>&category=<?= $category ?>">
                                    <i class="fas fa-angle-right"></i>
                                </a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $totalPages ?>&month=<?= $month ?>&type=<?= $type ?>&category=<?= $category ?>">
                                    <i class="fas fa-angle-double-right"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="transactions-container">
                <div class="no-data">
                    <i class="fas fa-inbox"></i>
                    <h4>ไม่มีรายการที่ตรงกับเงื่อนไขที่กำหนด</h4>
                    <p class="text-muted">ลองปรับเปลี่ยนตัวกรองหรือเพิ่มรายการใหม่</p>
                    <a href="add_transaction.php" class="btn btn-custom mt-3">
                        <i class="fas fa-plus me-1"></i>เพิ่มรายการใหม่
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>