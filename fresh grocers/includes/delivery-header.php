<?php
if (!isset($page_title)) {
    $page_title = "Delivery Portal";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Fresh Grocers Delivery</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo isset($BASE_URL) ? $BASE_URL : '/fresh grocers/'; ?>assets/css/style.css">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark sticky-top shadow-sm" style="background-color:#fd7e14;">
    <div class="container">

        <a class="navbar-brand d-flex align-items-center gap-2 fw-bold" href="index.php">
            <div class="bg-white rounded d-flex align-items-center justify-content-center"
                style="width:32px;height:32px;">
                <i class="bi bi-truck text-warning fs-5"></i>
            </div>
            <span>Delivery Portal</span>
        </a>

        <button class="navbar-toggler border-0" type="button"
            data-bs-toggle="collapse" data-bs-target="#deliveryNav" aria-expanded="false">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="deliveryNav">
            <ul class="navbar-nav me-auto ms-3 gap-1">
                <li class="nav-item">
                    <a class="nav-link px-3 rounded <?php echo basename($_SERVER['PHP_SELF'])=='index.php' ? 'bg-white bg-opacity-25 fw-semibold' : ''; ?>"
                        href="index.php">
                        <i class="bi bi-speedometer2 me-1"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link px-3 rounded <?php echo basename($_SERVER['PHP_SELF'])=='my-orders.php' ? 'bg-white bg-opacity-25 fw-semibold' : ''; ?>"
                        href="my-orders.php">
                        <i class="bi bi-bag me-1"></i>My Orders
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link px-3 rounded <?php echo basename($_SERVER['PHP_SELF'])=='update-location.php' ? 'bg-white bg-opacity-25 fw-semibold' : ''; ?>"
                        href="update-location.php">
                        <i class="bi bi-geo-alt me-1"></i>Update Location
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link px-3 rounded <?php echo basename($_SERVER['PHP_SELF'])=='profile.php' ? 'bg-white bg-opacity-25 fw-semibold' : ''; ?>"
                        href="profile.php">
                        <i class="bi bi-person me-1"></i>Profile
                    </a>
                </li>
            </ul>

            <ul class="navbar-nav align-items-center gap-2">
                <li class="nav-item">
                    <span class="navbar-text text-white opacity-75 small">
                        <i class="bi bi-person-circle me-1"></i>
                        <?php echo isset($_SESSION['agent_name']) ? htmlspecialchars($_SESSION['agent_name']) : 'Agent'; ?>
                    </span>
                </li>
                <li class="nav-item">
                    <a class="btn btn-light btn-sm fw-semibold" href="logout.php"
                        style="color:#fd7e14;">
                        <i class="bi bi-box-arrow-right me-1"></i>Logout
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>
