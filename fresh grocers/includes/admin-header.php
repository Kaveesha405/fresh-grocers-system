<?php
require_once '../config.php';
$current_page = basename($_SERVER['PHP_SELF']);

if ($current_page !== 'login.php' && !isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . " - Admin Panel" : "Admin Panel"; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <!-- Corrected Path to your CSS -->
    <link rel="stylesheet" href="<?php echo isset($BASE_URL) ? $BASE_URL : '/fresh grocers/'; ?>assets/css/style.css">
    <!-- Admin header styles moved to assets/css/style.css -->
</head>
<body>

<?php if ($current_page !== 'login.php'): ?>
<nav class="navbar navbar-expand-lg sticky-top">
    <div class="container-fluid px-4">
        <a class="navbar-brand fw-bold d-flex align-items-center" href="index.php">
            <i class="bi bi-shield-lock-fill me-2 fs-4"></i> FG Admin
        </a>
        <button class="navbar-toggler border-0 text-white" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav">
            <i class="bi bi-list fs-2"></i>
        </button>
        <div class="collapse navbar-collapse" id="adminNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-4 gap-2">
                <li class="nav-item"><a class="nav-link <?php echo $current_page=='index.php'?'active':''; ?>" href="index.php"><i class="bi bi-speedometer2 me-1"></i>Dashboard</a></li>
                <li class="nav-item"><a class="nav-link <?php echo $current_page=='orders.php'?'active':''; ?>" href="orders.php"><i class="bi bi-receipt me-1"></i>Orders</a></li>
                <li class="nav-item"><a class="nav-link <?php echo $current_page=='products.php'?'active':''; ?>" href="products.php"><i class="bi bi-box me-1"></i>Products</a></li>
                <li class="nav-item"><a class="nav-link <?php echo $current_page=='customers.php'?'active':''; ?>" href="customers.php"><i class="bi bi-people me-1"></i>Customers</a></li>
                <li class="nav-item"><a class="nav-link <?php echo $current_page=='csr.php'?'active':''; ?>" href="csr.php"><i class="bi bi-headset me-1"></i>CSRs</a></li>
                <li class="nav-item"><a class="nav-link <?php echo $current_page=='delivery-agents.php'?'active':''; ?>" href="delivery-agents.php"><i class="bi bi-truck me-1"></i>Agents</a></li>
                <li class="nav-item"><a class="nav-link <?php echo $current_page=='messages.php'?'active':''; ?>" href="messages.php"><i class="bi bi-envelope me-1"></i>Messages</a></li>
                <li class="nav-item"><a class="nav-link <?php echo $current_page=='reports.php'?'active':''; ?>" href="reports.php"><i class="bi bi-graph-up me-1"></i>Reports</a></li>
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link bg-danger bg-opacity-75 text-white rounded px-3" href="logout.php">
                        <i class="bi bi-power me-1"></i>Logout
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>
<?php endif; ?>
