<?php
if (!isset($page_title)) {
    $page_title = "Fresh Grocers";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Fresh Grocers</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo isset($BASE_URL) ? $BASE_URL : '/fresh grocers/'; ?>assets/css/style.css">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top" style="min-height:80px;">
    <div class="container">

        <!-- Logo -->
        <a class="navbar-brand py-2" href="index.php">
            <img src="<?php echo isset($BASE_URL) ? $BASE_URL : '/fresh grocers/'; ?>assets/img/logo.png" alt="Fresh Grocers" height="50">
        </a>

        <!-- Mobile: Cart + Toggler -->
        <div class="d-flex align-items-center gap-2 d-lg-none ms-auto">
            <a href="cart.php" style="position:relative;display:inline-block;color:#333;text-decoration:none;">
                <i class="bi bi-cart3 fs-4"></i>
                <?php if (get_cart_count() > 0): ?>
                    <span class="cart-badge"><?php echo get_cart_count(); ?></span>
                <?php endif; ?>
            </a>
            <button class="navbar-toggler border-0" type="button"
                data-bs-toggle="collapse" data-bs-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false">
                <span class="navbar-toggler-icon"></span>
            </button>
        </div>

        <div class="collapse navbar-collapse" id="navbarNav">

            <!-- Center Menu -->
            <ul class="navbar-nav mx-auto">

                <li class="nav-item">
                    <a class="nav-link fw-semibold px-3" href="shop.php">
                        <i class="bi bi-shop me-1"></i>All Products
                    </a>
                </li>

                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle fw-semibold px-3" href="#"
                        role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        Fresh Food
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="shop.php?category=Vegetables">
                            <i class="bi bi-flower1 me-2 text-success"></i>Vegetables</a></li>
                        <li><a class="dropdown-item" href="shop.php?category=Fruits">
                            <i class="bi bi-apple me-2 text-danger"></i>Fruits</a></li>
                        <li><a class="dropdown-item" href="shop.php?category=Dairy">
                            <i class="bi bi-cup-hot me-2 text-warning"></i>Dairy & Eggs</a></li>
                        <li><a class="dropdown-item" href="shop.php?category=Meat">
                            <i class="bi bi-egg-fried me-2 text-danger"></i>Meat & Seafood</a></li>
                    </ul>
                </li>

                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle fw-semibold px-3" href="#"
                        role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        Grocery & Staples
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="shop.php?category=Rice">
                            <i class="bi bi-basket me-2 text-warning"></i>Rice & Grains</a></li>
                        <li><a class="dropdown-item" href="shop.php?category=Pulses">
                            <i class="bi bi-basket2 me-2 text-warning"></i>Pulses & Lentils</a></li>
                        <li><a class="dropdown-item" href="shop.php?category=Oil">
                            <i class="bi bi-droplet me-2 text-warning"></i>Cooking Oil</a></li>
                        <li><a class="dropdown-item" href="shop.php?category=Spices">
                            <i class="bi bi-stars me-2 text-danger"></i>Spices</a></li>
                    </ul>
                </li>

                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle fw-semibold px-3" href="#"
                        role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        Home & Essentials
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="shop.php?category=Cleaning">
                            <i class="bi bi-brush me-2 text-primary"></i>Cleaning Supplies</a></li>
                        <li><a class="dropdown-item" href="shop.php?category=Personal Care">
                            <i class="bi bi-person-heart me-2 text-danger"></i>Personal Care</a></li>
                        <li><a class="dropdown-item" href="shop.php?category=Baby">
                            <i class="bi bi-balloon-heart me-2 text-danger"></i>Baby Products</a></li>
                    </ul>
                </li>

                <li class="nav-item">
                    <a class="nav-link fw-semibold text-danger px-3" href="shop.php?special=1">
                        <i class="bi bi-tag-fill me-1"></i>Specials
                    </a>
                </li>

            </ul>

            <!-- Right Side -->
            <ul class="navbar-nav align-items-center gap-1">

                <!-- Search -->
                <li class="nav-item">
                    <a class="nav-link px-2" href="shop.php" title="Search">
                        <i class="bi bi-search fs-5"></i>
                    </a>
                </li>

                <!-- Cart desktop -->
                <li class="nav-item d-none d-lg-block">
                    <a class="nav-link px-2" href="cart.php" title="Cart"
                        style="position:relative;display:inline-block;">
                        <i class="bi bi-cart3 fs-5"></i>
                        <?php if (get_cart_count() > 0): ?>
                            <span class="cart-badge"><?php echo get_cart_count(); ?></span>
                        <?php endif; ?>
                    </a>
                </li>

                <?php if (is_logged_in()): ?>
                <!-- Logged In User Dropdown -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center gap-2 px-2"
                        href="#" role="button"
                        data-bs-toggle="dropdown"
                        aria-expanded="false">
                        <div class="bg-success text-white rounded-circle d-flex align-items-center
                            justify-content-center flex-shrink-0"
                            style="width:34px;height:34px;font-size:0.9rem;font-weight:700;">
                            <?php echo strtoupper(substr($_SESSION['customer_name'], 0, 1)); ?>
                        </div>
                        <span class="d-none d-lg-inline fw-semibold small">
                            <?php echo htmlspecialchars(explode(' ', $_SESSION['customer_name'])[0]); ?>
                        </span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2"
                        style="min-width:220px;border-radius:12px;">
                        <!-- User Info -->
                        <li class="px-3 pt-3 pb-2">
                            <div class="d-flex align-items-center gap-2">
                                <div class="bg-success text-white rounded-circle d-flex align-items-center
                                    justify-content-center flex-shrink-0"
                                    style="width:42px;height:42px;font-size:1.1rem;font-weight:700;">
                                    <?php echo strtoupper(substr($_SESSION['customer_name'], 0, 1)); ?>
                                </div>
                                <div style="overflow:hidden;">
                                    <p class="fw-bold mb-0 small text-truncate">
                                        <?php echo htmlspecialchars($_SESSION['customer_name']); ?>
                                    </p>
                                    <p class="text-muted mb-0 text-truncate" style="font-size:0.72rem;">
                                        <?php echo htmlspecialchars($_SESSION['customer_email']); ?>
                                    </p>
                                </div>
                            </div>
                        </li>
                        <li><hr class="dropdown-divider my-1"></li>
                        <li>
                            <a class="dropdown-item py-2" href="index.php">
                                <i class="bi bi-speedometer2 me-2 text-success"></i>Dashboard
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item py-2" href="profile.php">
                                <i class="bi bi-person me-2 text-success"></i>My Profile
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item py-2" href="orders.php">
                                <i class="bi bi-bag me-2 text-success"></i>My Orders
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item py-2" href="track-order.php">
                                <i class="bi bi-geo-alt me-2 text-success"></i>Track Order
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item py-2" href="cart.php">
                                <i class="bi bi-cart3 me-2 text-success"></i>My Cart
                                <?php if (get_cart_count() > 0): ?>
                                    <span class="badge bg-danger ms-1">
                                        <?php echo get_cart_count(); ?>
                                    </span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <li><hr class="dropdown-divider my-1"></li>
                        <li>
                            <a class="dropdown-item py-2 text-danger" href="logout.php">
                                <i class="bi bi-box-arrow-right me-2"></i>Logout
                            </a>
                        </li>
                    </ul>
                </li>

                <?php else: ?>
                <!-- Not Logged In -->
                <li class="nav-item ms-1">
                    <a class="btn btn-success btn-sm px-3" href="login.php">
                        <i class="bi bi-box-arrow-in-right me-1"></i>Login
                    </a>
                </li>
                <li class="nav-item ms-1">
                    <a class="btn btn-outline-success btn-sm px-3" href="register.php">
                        Register
                    </a>
                </li>
                <li class="nav-item dropdown ms-1">
                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle"
                        type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person-badge me-1"></i>Staff
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                        <li><a class="dropdown-item" href="../admin/login.php">
                            <i class="bi bi-shield-check me-2 text-dark"></i>Admin</a></li>
                        <li><a class="dropdown-item" href="../delivery/login.php">
                            <i class="bi bi-truck me-2 text-warning"></i>Delivery Agent</a></li>
                        <li><a class="dropdown-item" href="../csr/login.php">
                            <i class="bi bi-headset me-2 text-info"></i>CSR</a></li>
                    </ul>
                </li>
                <?php endif; ?>

            </ul>
        </div>
    </div>
</nav>
