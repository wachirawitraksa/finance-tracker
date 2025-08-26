<?php
require 'backend/config.php';
requireLogin();

// ดึงข้อมูลสรุปการเงิน
$month = $_GET['month'] ?? date('Y-m');
$userId = $_SESSION['user_id'];

$display_name = $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'ผู้ใช้งาน';

// ข้อมูลรายรับ-รายจ่าย
$stmt = $pdo->prepare("
    SELECT 
        type,
        SUM(amount) as total
    FROM transactions 
    WHERE user_id = ? AND DATE_FORMAT(transaction_date, '%Y-%m') = ?
    GROUP BY type
");
$stmt->execute([$userId, $month]);
$summary = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$income = $summary['income'] ?? 0;
$expense = $summary['expense'] ?? 0;
$balance = $income - $expense;

// ข้อมูลค่าใช้จ่ายตามประเภท
$stmt = $pdo->prepare("
    SELECT 
        category,
        SUM(amount) as total
    FROM transactions 
    WHERE user_id = ? AND type = 'expense' AND DATE_FORMAT(transaction_date, '%Y-%m') = ?
    GROUP BY category
");
$stmt->execute([$userId, $month]);
$expenseByCategory = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ข้อมูลธุรกรรมล่าสุด
$stmt = $pdo->prepare("
    SELECT * FROM transactions 
    WHERE user_id = ? 
    ORDER BY transaction_date DESC, created_at DESC 
    LIMIT 10
");
$stmt->execute([$userId]);
$recentTransactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MoneyLog</title>
    <link rel="shortcut icon" href="src/logo/logo.png" type="image/x-icon">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Chart.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    
    <link rel="stylesheet" href="styles/index_styles.css">
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
                        <a class="nav-link active" href="index.php">
                            <i class="fas fa-home me-1"></i>หน้าหลัก
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="add_transaction.php">
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
    <div class="container mt-4">
        <!-- Month Selector -->
        <div class="month-selector">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <label class="form-label">
                        <i class="fas fa-calendar me-2"></i>เลือกเดือน:
                    </label>
                    <select class="form-select d-inline-block" style="width: auto;" onchange="location='?month='+this.value">
                        <?php
                        for ($i = 11; $i >= 0; $i--) {
                            $date = date('Y-m', strtotime("-$i months"));
                            $selected = $date == $month ? 'selected' : '';
                            echo "<option value='$date' $selected>".date('M Y', strtotime($date))."</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-6 text-md-end mt-3 mt-md-0">
                    <span class="text-muted">
                        <i class="fas fa-chart-line me-1"></i>สรุปประจำเดือน <?= date('M Y', strtotime($month)) ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="summary-card income-card">
                    <div class="card-icon">
                        <i class="fas fa-arrow-up"></i>
                    </div>
                    <h5 class="card-title">รายรับ</h5>
                    <p class="card-amount income-amount">+<?= number_format($income, 2) ?> ฿</p>
                </div>
            </div>
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="summary-card expense-card">
                    <div class="card-icon">
                        <i class="fas fa-arrow-down"></i>
                    </div>
                    <h5 class="card-title">รายจ่าย</h5>
                    <p class="card-amount expense-amount">-<?= number_format($expense, 2) ?> ฿</p>
                </div>
            </div>
            <div class="col-lg-4 col-md-12 mb-4">
                <div class="summary-card balance-card">
                    <div class="card-icon">
                        <i class="fas fa-balance-scale"></i>
                    </div>
                    <h5 class="card-title">คงเหลือ</h5>
                    <p class="card-amount balance-amount"><?= number_format($balance, 2) ?> ฿</p>
                </div>
            </div>
        </div>

        <!-- Chart and Transactions Row -->
        <div class="row">
            <?php if (!empty($expenseByCategory)): ?>
            <div class="col-lg-6 mb-4">
                <div class="chart-container">
                    <h3>
                        <i class="fas fa-chart-pie me-2"></i>สัดส่วนค่าใช้จ่าย
                    </h3>
                    <div class="position-relative">
                        <canvas id="expenseChart" style="max-height: 400px;"></canvas>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="<?= !empty($expenseByCategory) ? 'col-lg-6' : 'col-12' ?> mb-4">
                <div class="transactions-container">
                    <h3>
                        <i class="fas fa-history me-2"></i>รายการล่าสุด
                    </h3>
                    <?php if (!empty($recentTransactions)): ?>
                        <?php foreach ($recentTransactions as $transaction): ?>
                        <div class="transaction-item">
                            <div class="transaction-info">
                                <strong><?= htmlspecialchars($transaction['description']) ?></strong>
                                <div class="transaction-date">
                                    <i class="fas fa-calendar-alt me-1"></i>
                                    <?= date('d/m/Y', strtotime($transaction['transaction_date'])) ?> - 
                                    <i class="fas fa-tag me-1"></i>
                                    <?= htmlspecialchars($transaction['category']) ?>
                                </div>
                            </div>
                            <div class="transaction-amount <?= $transaction['type'] ?>">
                                <?= $transaction['type'] == 'income' ? '+' : '-' ?><?= number_format($transaction['amount'], 2) ?> ฿
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <p class="text-muted">ไม่มีรายการ</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Chart.js Script -->
    <script>
        <?php if (!empty($expenseByCategory)): ?>
        const ctx = document.getElementById('expenseChart').getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode(array_column($expenseByCategory, 'category')) ?>,
                datasets: [{
                    data: <?= json_encode(array_column($expenseByCategory, 'total')) ?>,
                    backgroundColor: [
                        '#FF6384',
                        '#36A2EB', 
                        '#FFCE56',
                        '#4BC0C0',
                        '#9966FF',
                        '#FF9F40',
                        '#FF6384',
                        '#C9CBCF'
                    ],
                    borderWidth: 3,
                    borderColor: '#fff',
                    hoverBorderWidth: 5,
                    hoverBorderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            font: {
                                size: 14
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((context.parsed * 100) / total).toFixed(1);
                                return context.label + ': ' + new Intl.NumberFormat('th-TH').format(context.parsed) + ' ฿ (' + percentage + '%)';
                            }
                        }
                    }
                },
                cutout: '50%',
                animation: {
                    animateScale: true,
                    animateRotate: true
                }
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>