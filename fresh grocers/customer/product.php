<?php
require_once '../config.php';

if (!isset($_GET['id'])) {
    redirect('shop.php');
}

$product_id = (int)$_GET['id'];
$product = get_product($product_id);

if (!$product) {
    redirect('shop.php');
}

// Handle Add to Cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $qty = max(1, (int)$_POST['quantity']);
    if (check_stock($product_id, $qty)) {
        add_to_cart($product_id, $qty);
        set_success_message("'" . htmlspecialchars($product['ProductName']) . "' added to cart!");
        redirect('product.php?id=' . $product_id);
    } else {
        $error = "Sorry, not enough stock available.";
    }
}

// Safe category sanitize with fallback
$raw_category = $product['Category'] ?? '';
if (function_exists('clean_input_data')) {
    $category = clean_input_data($raw_category);
} elseif (function_exists('clean_input')) {
    $category = clean_input($raw_category);
} else {
    global $conn;
    $category = $conn->real_escape_string(trim(htmlspecialchars($raw_category)));
}

// Get related products (same category)
$related = $conn->query("
    SELECT * FROM product 
    WHERE Category = '$category' 
    AND ProductID != $product_id 
    LIMIT 4
");
?>
<?php $page_title = $product['ProductName']; include '../includes/customer-header.php'; ?>

<!-- Styles moved to assets/css/style.css -->

<!-- Breadcrumb Bar -->
<div class="bg-light py-3 border-bottom mb-5">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 small fw-semibold">
                <li class="breadcrumb-item">
                    <a href="index.php" class="text-decoration-none text-muted">Home</a>
                </li>
                <li class="breadcrumb-item">
                    <a href="shop.php" class="text-decoration-none text-muted">Shop</a>
                </li>
                <li class="breadcrumb-item">
                    <a href="shop.php?category=<?php echo urlencode($product['Category']); ?>"
                       class="text-decoration-none text-muted">
                        <?php echo htmlspecialchars($product['Category']); ?>
                    </a>
                </li>
                <li class="breadcrumb-item active text-success" aria-current="page">
                    <?php echo htmlspecialchars($product['ProductName']); ?>
                </li>
            </ol>
        </nav>
    </div>
</div>

<div class="container pb-5">

    <!-- Alerts -->
    <?php $msg = get_success_message(); if ($msg): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm border-0 rounded-3 mb-4">
            <i class="bi bi-check-circle-fill me-2"></i><?php echo $msg; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0 rounded-3 mb-4">
            <i class="bi bi-exclamation-circle-fill me-2"></i><?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- ===== MAIN PRODUCT CARD ===== -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-5">
        <div class="row g-0">

            <!-- Left: Product Image -->
            <div class="col-md-5 col-lg-6 bg-light d-flex align-items-center justify-content-center p-4 p-md-5">
                <img src="<?php echo $product['ImageURL'] ?: '../assets/img/placeholder.jpg'; ?>"
                    class="img-fluid rounded-4 shadow-sm"
                    style="max-height: 420px; object-fit: contain; width: 100%;"
                    alt="<?php echo htmlspecialchars($product['ProductName']); ?>">
            </div>

            <!-- Right: Product Details -->
            <div class="col-md-7 col-lg-6">
                <div class="card-body p-4 p-lg-5 d-flex flex-column h-100">

                    <div>
                        <!-- ===== CATEGORY TAG (improved font/style) ===== -->
                        <div class="mb-3">
                            <span class="category-tag">
                                <i class="bi bi-tag-fill"></i>
                                <?php echo htmlspecialchars($product['Category']); ?>
                            </span>
                        </div>

                        <!-- Product Name -->
                        <h1 class="fw-bolder text-dark mb-3" style="font-size: 1.9rem; letter-spacing: -0.4px; line-height: 1.2;">
                            <?php echo htmlspecialchars($product['ProductName']); ?>
                        </h1>

                        <!-- Price + Stock -->
                        <div class="d-flex align-items-center gap-3 mb-4 pb-4 border-bottom">
                            <h2 class="text-success fw-bold mb-0" style="font-size: 2rem;">
                                <?php echo format_price($product['Price']); ?>
                            </h2>
                            <?php if ($product['StockQuantity'] > 0): ?>
                                <span class="badge bg-success rounded-pill px-3 py-2" style="font-size: 0.78rem;">
                                    <i class="bi bi-check2 me-1"></i>In Stock (<?php echo $product['StockQuantity']; ?>)
                                </span>
                            <?php else: ?>
                                <span class="badge bg-danger rounded-pill px-3 py-2" style="font-size: 0.78rem;">
                                    <i class="bi bi-x-lg me-1"></i>Out of Stock
                                </span>
                            <?php endif; ?>
                        </div>

                        <!-- Description -->
                        <p class="fw-bold text-uppercase text-muted mb-1" style="font-size: 0.72rem; letter-spacing: 0.1em;">
                            Product Description
                        </p>
                        <p class="text-muted mb-4" style="line-height: 1.7; font-size: 0.97rem;">
                            <?php echo nl2br(htmlspecialchars($product['Description'])); ?>
                        </p>
                    </div>

                    <!-- ===== ADD TO CART FORM ===== -->
                    <div class="mt-auto bg-light rounded-4 p-4">
                        <?php if ($product['StockQuantity'] > 0): ?>
                        <form method="POST" action="">
                            <div class="row g-3 align-items-end">
                                <!-- Quantity -->
                                <div class="col-12 col-sm-5 col-md-12 col-xl-5">
                                    <label class="form-label fw-bold text-dark mb-2" style="font-size: 0.82rem; text-transform: uppercase; letter-spacing: 0.05em;">
                                        Quantity
                                    </label>
                                    <div class="input-group shadow-sm rounded-pill overflow-hidden bg-white border">
                                        <button class="btn btn-white border-0 px-3 text-success fw-bold fs-5" type="button"
                                            onclick="decreaseQty('qty')">
                                            <i class="bi bi-dash-lg"></i>
                                        </button>
                                        <input type="number" id="qty" name="quantity"
                                            class="form-control border-0 text-center fw-bold bg-white fs-5"
                                            value="1" min="1"
                                            max="<?php echo $product['StockQuantity']; ?>"
                                            readonly style="max-width: 60px;">
                                        <button class="btn btn-white border-0 px-3 text-success fw-bold fs-5" type="button"
                                            onclick="increaseQty('qty')">
                                            <i class="bi bi-plus-lg"></i>
                                        </button>
                                    </div>
                                    <p class="text-muted mt-2 mb-0" style="font-size: 0.78rem;">
                                        Max: <strong class="text-dark"><?php echo $product['StockQuantity']; ?></strong> available
                                    </p>
                                </div>

                                <!-- Buttons -->
                                <div class="col-12 col-sm-7 col-md-12 col-xl-7 d-flex flex-column gap-2">
                                    <button type="submit" name="add_to_cart"
                                        class="btn btn-success btn-lg rounded-pill shadow-sm fw-bold w-100">
                                        <i class="bi bi-cart-plus me-2"></i>Add to Cart
                                    </button>
                                    <a href="cart.php"
                                        class="btn btn-outline-success btn-lg rounded-pill fw-bold w-100 bg-white">
                                        <i class="bi bi-bag-check me-2"></i>View Cart
                                    </a>
                                </div>
                            </div>
                        </form>
                        <?php else: ?>
                            <div class="text-center py-3">
                                <i class="bi bi-emoji-frown display-4 text-muted opacity-50 mb-2 d-block"></i>
                                <h5 class="text-danger fw-bold mb-1">Out of Stock</h5>
                                <p class="text-muted small mb-0">This item is currently unavailable. Check back later.</p>
                            </div>
                        <?php endif; ?>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <!-- ===== TRUST BADGES ===== -->
    <div class="row g-3 mb-5">
        <div class="col-md-4">
            <div class="d-flex align-items-center gap-3 p-3 bg-white rounded-4 shadow-sm border border-success border-opacity-25 h-100">
                <div class="flex-shrink-0 bg-success bg-opacity-10 text-success rounded-3 d-flex align-items-center justify-content-center"
                     style="width:48px; height:48px;">
                    <i class="bi bi-truck fs-4"></i>
                </div>
                <div>
                    <p class="fw-bold mb-0" style="font-size: 0.9rem;">Fast Delivery</p>
                    <p class="text-muted mb-0" style="font-size: 0.8rem;">Same-day delivery across Colombo.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="d-flex align-items-center gap-3 p-3 bg-white rounded-4 shadow-sm border border-success border-opacity-25 h-100">
                <div class="flex-shrink-0 bg-success bg-opacity-10 text-success rounded-3 d-flex align-items-center justify-content-center"
                     style="width:48px; height:48px;">
                    <i class="bi bi-shield-check fs-4"></i>
                </div>
                <div>
                    <p class="fw-bold mb-0" style="font-size: 0.9rem;">100% Fresh</p>
                    <p class="text-muted mb-0" style="font-size: 0.8rem;">Quality guaranteed on all products.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="d-flex align-items-center gap-3 p-3 bg-white rounded-4 shadow-sm border border-success border-opacity-25 h-100">
                <div class="flex-shrink-0 bg-success bg-opacity-10 text-success rounded-3 d-flex align-items-center justify-content-center"
                     style="width:48px; height:48px;">
                    <i class="bi bi-telephone fs-4"></i>
                </div>
                <div>
                    <p class="fw-bold mb-0" style="font-size: 0.9rem;">24/7 Support</p>
                    <p class="text-muted mb-0" style="font-size: 0.8rem;">Always here to help with your order.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== RELATED PRODUCTS ===== -->
    <?php if ($related && $related->num_rows > 0): ?>
    <div class="pt-4 border-top">
        <div class="d-flex justify-content-between align-items-end mb-4">
            <div>
                <h4 class="fw-bold text-dark mb-1">You Might Also Like</h4>
                <p class="text-muted small mb-0">More from <strong><?php echo htmlspecialchars($product['Category']); ?></strong></p>
            </div>
            <a href="shop.php?category=<?php echo urlencode($product['Category']); ?>"
               class="btn btn-outline-success btn-sm rounded-pill px-3 fw-bold">
                View All <i class="bi bi-arrow-right ms-1"></i>
            </a>
        </div>

        <div class="row g-4">
            <?php while($rel = $related->fetch_assoc()): ?>
            <div class="col-md-3 col-6">
                <div class="card product-card h-100 border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="bg-light" style="height: 170px; overflow: hidden;">
                        <img src="<?php echo $rel['ImageURL'] ?: '../assets/img/placeholder.jpg'; ?>"
                            class="w-100 h-100 object-fit-cover"
                            alt="<?php echo htmlspecialchars($rel['ProductName']); ?>">
                    </div>
                    <div class="card-body p-3">
                        <p class="fw-bold text-dark text-truncate mb-1" style="font-size: 0.9rem;">
                            <?php echo htmlspecialchars($rel['ProductName']); ?>
                        </p>
                        <p class="text-success fw-bold mb-0">
                            <?php echo format_price($rel['Price']); ?>
                        </p>
                    </div>
                    <div class="card-footer bg-white border-0 pt-0 pb-3 px-3">
                        <a href="product.php?id=<?php echo $rel['ProductID']; ?>"
                            class="btn btn-outline-success btn-sm rounded-pill w-100 fw-bold">
                            View Details
                        </a>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
    <?php endif; ?>

</div>

<!-- Quantity controls use global assets/js/script.js -->

<?php include '../includes/footer.php'; ?>
