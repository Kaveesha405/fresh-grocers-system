<?php 
require_once '../config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Check if user is logged in using helper function
$is_logged_in = is_logged_in();

// 2. Fetch customer info if logged in
if ($is_logged_in) {
    $customer = get_customer_info();
}

// 3. FETCH THE PRODUCTS FROM THE DATABASE (This was missing!)
$featured_products = $conn->query("SELECT * FROM Product ORDER BY ProductID DESC LIMIT 8");

$page_title = $is_logged_in ? "Dashboard & Home" : "Home";

// Include header
include '../includes/customer-header.php'; 
?>

<!-- ===== COLORFUL HERO / WELCOME BANNER ===== -->
<div class="shop-hero-banner">
    <div class="container text-center position-relative" style="z-index: 2;">
        <?php if ($is_logged_in && !empty($customer)): ?>
            <h1 class="display-4 fw-bold mb-3 text-white">Welcome back, <?php echo htmlspecialchars($customer['FirstName']); ?>! 👋</h1>
            <p class="lead text-white-50 mb-0" style="max-width: 600px; margin: 0 auto;">
                Ready to order fresh groceries? Shop our freshest arrivals below.
            </p>
        <?php else: ?>
            <h1 class="display-4 fw-bold mb-3 text-white">Fresh Groceries to Your Door</h1>
            <p class="lead text-white-50 mb-4" style="max-width: 600px; margin: 0 auto;">
                Shop now as guest or login for faster checkout.
            </p>
            <a href="login.php" class="btn btn-light text-success btn-lg px-4 me-2 shadow-sm fw-bold">Login</a>
            <a href="register.php" class="btn btn-outline-light btn-lg px-4 shadow-sm fw-bold border-2">Register</a>
        <?php endif; ?>
    </div>
</div>

<?php if ($is_logged_in && !empty($customer)): ?>
<!-- Quick Stats (Logged in only) -->
<div class="container mt-4 mb-5">
    <div class="row g-3">
        <!-- Total Orders -->
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 stat-card">
                <div class="card-body text-center p-0 d-flex flex-column justify-content-center">
                    <div class="stat-value-container">
                        <h3 class="text-success mb-0 fw-bold">
                            <?php 
                            $order_count = $conn->query("SELECT COUNT(*) as cnt FROM `Order` WHERE CustomerID = " . $customer['CustomerID'])->fetch_assoc();
                            echo $order_count['cnt'] ?? 0; 
                            ?>
                        </h3>
                    </div>
                    <small class="text-muted text-uppercase fw-semibold" style="letter-spacing: 0.5px;">Total Orders</small>
                </div>
            </div>
        </div>
        <!-- Cart Items -->
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 stat-card">
                <div class="card-body text-center p-0 d-flex flex-column justify-content-center">
                    <div class="stat-value-container">
                        <h3 class="text-primary mb-0 fw-bold"><?php echo get_cart_count(); ?></h3>
                    </div>
                    <small class="text-muted text-uppercase fw-semibold" style="letter-spacing: 0.5px;">Cart Items</small>
                </div>
            </div>
        </div>
        <!-- My Orders -->
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 stat-card">
                <a href="orders.php" class="text-decoration-none text-dark h-100">
                    <div class="card-body text-center p-0 d-flex flex-column justify-content-center h-100">
                        <div class="stat-value-container">
                            <i class="bi bi-box-seam stat-icon text-dark"></i>
                        </div>
                        <small class="text-muted text-uppercase fw-semibold" style="letter-spacing: 0.5px;">My Orders</small>
                    </div>
                </a>
            </div>
        </div>
        <!-- Track / Rate -->
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 stat-card">
                <a href="track-order.php" class="text-decoration-none text-dark h-100">
                    <div class="card-body text-center p-0 d-flex flex-column justify-content-center h-100">
                        <div class="stat-value-container">
                            <i class="bi bi-truck stat-icon text-warning"></i>
                        </div>
                        <small class="text-muted text-uppercase fw-semibold" style="letter-spacing: 0.5px;">Track / Rate</small>
                    </div>
                </a>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ===== FEATURED PRODUCTS SECTION ===== -->
<div class="container my-5 pt-2">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-1">Featured Products</h3>
            <p class="text-muted small mb-0">Fresh arrivals and popular items</p>
        </div>
        <a href="shop.php" class="btn btn-outline-success rounded-pill px-4">View All</a>
    </div>

    <?php if ($featured_products && $featured_products->num_rows > 0): ?>
    <div class="row g-4">
        <?php while($product = $featured_products->fetch_assoc()): ?>
        <div class="col-lg-3 col-md-4 col-sm-6">
            <div class="card h-100 bg-white rounded-4 product-card position-relative">
                
                <!-- Badges -->
                <div class="position-absolute top-0 start-0 w-100 p-3 d-flex justify-content-between align-items-start" style="z-index: 2;">
                    <span class="badge bg-white text-success shadow-sm rounded-pill cat-badge px-3 py-2 border border-success border-opacity-25">
                        <?php echo htmlspecialchars($product['Category']); ?>
                    </span>
                    <?php if ($product['StockQuantity'] <= 5): ?>
                    <span class="badge bg-danger text-white shadow-sm rounded-pill px-2 py-1 cat-badge">
                        Low Stock
                    </span>
                    <?php endif; ?>
                </div>

                <!-- Product Image -->
                <a href="product.php?id=<?php echo $product['ProductID']; ?>" class="product-image-container border-bottom text-decoration-none">
                    <img src="<?php echo $product['ImageURL'] ?: '../assets/img/placeholder.jpg'; ?>" 
                         class="product-img"
                         alt="<?php echo htmlspecialchars($product['ProductName']); ?>">
                </a>

                <!-- Product Body -->
                <div class="card-body p-4 d-flex flex-column">
                    <!-- Title -->
                    <a href="product.php?id=<?php echo $product['ProductID']; ?>" class="text-decoration-none">
                        <h5 class="product-title" title="<?php echo htmlspecialchars($product['ProductName']); ?>">
                            <?php echo htmlspecialchars($product['ProductName']); ?>
                        </h5>
                    </a>
                    
                    <!-- Short Desc -->
                    <p class="text-muted small mb-4" style="line-height: 1.4;">
                        <?php echo htmlspecialchars(substr($product['Description'] ?? '', 0, 45)); ?>...
                    </p>
                    
                    <!-- Bottom Row: Price & Add Button -->
                    <div class="mt-auto d-flex justify-content-between align-items-center">
                        <div class="product-price">
                            <?php echo format_price($product['Price']); ?>
                        </div>
                        
                        <!-- Add to cart quick button -->
                        <form method="POST" action="../api/add-to-cart.php" class="m-0">
                            <input type="hidden" name="product_id" value="<?php echo $product['ProductID']; ?>">
                            <input type="hidden" name="quantity" value="1">
                            <input type="hidden" name="redirect" value="../customer/index.php">
                            <button type="submit" 
                                    class="btn btn-success rounded-circle shadow-sm d-flex align-items-center justify-content-center"
                                    style="width: 42px; height: 42px;" 
                                    title="Add to Cart"
                                    <?php echo ($product['StockQuantity'] <= 0) ? 'disabled' : ''; ?>>
                                <i class="bi bi-cart-plus fs-5"></i>
                            </button>
                        </form>
                    </div>
                </div>

            </div>
        </div>
        <?php endwhile; ?>
    </div>
    <?php else: ?>
    <div class="alert alert-info text-center">No products available yet. Check back soon!</div>
    <?php endif; ?>
</div>

<?php if ($is_logged_in && !empty($customer)): ?>
<!-- ===== RECENT ORDERS SECTION ===== -->
<div class="container my-5 pt-4 border-top">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0">Recent Orders</h4>
        <a href="orders.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3">View All</a>
    </div>
    
    <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
        <div class="card-body p-0">
            <?php 
            $orders = $conn->query("SELECT * FROM `Order` WHERE CustomerID = " . $customer['CustomerID'] . " ORDER BY OrderDate DESC LIMIT 5");
            if ($orders && $orders->num_rows > 0): 
            ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="px-4 py-3">Order ID</th>
                            <th class="py-3">Date</th>
                            <th class="py-3">Total</th>
                            <th class="py-3">Status</th>
                            <th class="px-4 py-3 text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($order = $orders->fetch_assoc()): ?>
                        <tr>
                            <td class="px-4 py-3"><strong>#<?php echo $order['OrderID']; ?></strong></td>
                            <td class="py-3 text-muted"><?php echo date('d M Y, h:i A', strtotime($order['OrderDate'])); ?></td>
                            <td class="py-3 fw-bold"><?php echo format_price($order['TotalAmount']); ?></td>
                            <td class="py-3">
                                <?php
                                $status_class = match($order['OrderStatus']) {
                                    'Delivered' => 'bg-success',
                                    'Pending' => 'bg-warning text-dark',
                                    'Canceled' => 'bg-danger',
                                    default => 'bg-primary'
                                };
                                ?>
                                <span class="badge rounded-pill <?php echo $status_class; ?>">
                                    <?php echo htmlspecialchars($order['OrderStatus']); ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-end">
                                <a href="track-order.php?id=<?php echo $order['OrderID']; ?>" class="btn btn-sm btn-light border rounded-pill px-3">View Details</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="text-center py-5">
                <div class="display-1 text-muted opacity-25 mb-3"><i class="bi bi-inbox"></i></div>
                <h5 class="text-dark fw-bold mb-2">No orders yet!</h5>
                <p class="text-muted mb-0">Your recent orders will appear here once you make a purchase.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>
