<?php
require_once '../config.php';

// Handle cart actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Update quantity
    if (isset($_POST['update_cart'])) {
        foreach ($_POST['quantities'] as $product_id => $qty) {
            $qty = (int)$qty;
            $product_id = (int)$product_id;
            if ($qty <= 0) {
                remove_from_cart($product_id);
            } else {
                // Check stock before updating
                if (check_stock($product_id, $qty)) {
                    update_cart($product_id, $qty);
                } else {
                    $product = get_product($product_id);
                    set_error_message("Not enough stock for " . $product['ProductName']);
                }
            }
        }
        set_success_message("Cart updated!");
        redirect('cart.php');
    }

    // Remove single item
    if (isset($_POST['remove_item'])) {
        $product_id = (int)$_POST['product_id'];
        remove_from_cart($product_id);
        set_success_message("Item removed from cart.");
        redirect('cart.php');
    }

    // Clear cart
    if (isset($_POST['clear_cart'])) {
        clear_cart();
        set_success_message("Cart cleared.");
        redirect('cart.php');
    }
}

// Build cart items with product details
$cart_items = [];
$cart_total = 0;

if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $product_id => $item) {
        $product = get_product($product_id);
        if ($product) {
            $subtotal = $product['Price'] * $item['quantity'];
            $cart_total += $subtotal;
            $cart_items[] = [
                'product'  => $product,
                'quantity' => $item['quantity'],
                'subtotal' => $subtotal
            ];
        }
    }
}
?>
<?php $page_title = "My Cart"; include '../includes/customer-header.php'; ?>

<div class="container my-4">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h3 class="fw-bold mb-1">
                <i class="bi bi-cart3 me-2 text-success"></i>My Cart
            </h3>
            <p class="text-muted mb-0">
                <?php echo count($cart_items); ?> item(s) in your cart
            </p>
        </div>
        <a href="shop.php" class="btn btn-outline-success">
            <i class="bi bi-arrow-left me-1"></i>Continue Shopping
        </a>
    </div>

    <?php $msg = get_success_message(); if ($msg): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle me-2"></i><?php echo $msg; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php $err = get_error_message(); if ($err): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-circle me-2"></i><?php echo $err; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($cart_items)): ?>

    <div class="row g-4">

        <!-- Cart Items -->
        <div class="col-lg-8">
            <form method="POST" action="" id="cart-form">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <span class="fw-semibold">
                            <i class="bi bi-bag me-2 text-success"></i>Cart Items
                        </span>
                        <button type="submit" name="clear_cart"
                            class="btn btn-sm btn-outline-danger"
                            onclick="return confirm('Clear entire cart?')">
                            <i class="bi bi-trash me-1"></i>Clear All
                        </button>
                    </div>

                    <div class="card-body p-0">
                        <?php foreach ($cart_items as $index => $item):
                            $product = $item['product'];
                        ?>
                        <div class="cart-item p-3 <?php echo $index < count($cart_items)-1 ? 'border-bottom' : ''; ?>">
                            <div class="row align-items-center g-3">

                                <!-- Product Image + Name -->
                                <div class="col-md-5 col-8">
                                    <div class="d-flex align-items-center gap-3">
                                        <img src="<?php echo $product['ImageURL'] ?: '../assets/img/placeholder.jpg'; ?>"
                                            style="width:70px;height:70px;object-fit:cover;border-radius:10px;"
                                            alt="<?php echo htmlspecialchars($product['ProductName']); ?>">
                                        <div>
                                            <h6 class="fw-semibold mb-1">
                                                <a href="product.php?id=<?php echo $product['ProductID']; ?>"
                                                    class="text-dark text-decoration-none">
                                                    <?php echo htmlspecialchars($product['ProductName']); ?>
                                                </a>
                                            </h6>
                                            <span class="badge bg-light text-dark small">
                                                <?php echo htmlspecialchars($product['Category']); ?>
                                            </span>
                                            <p class="text-success fw-semibold mb-0 small mt-1">
                                                <?php echo format_price($product['Price']); ?> each
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Quantity -->
                                <div class="col-md-3 col-4">
                                    <div class="input-group input-group-sm" style="width:130px;">
                                        <button class="btn btn-outline-secondary" type="button"
                                            onclick="decreaseQty('qty-<?php echo $product['ProductID']; ?>')">
                                            <i class="bi bi-dash"></i>
                                        </button>
                                        <input type="number"
                                            id="qty-<?php echo $product['ProductID']; ?>"
                                            name="quantities[<?php echo $product['ProductID']; ?>]"
                                            class="form-control text-center"
                                            value="<?php echo $item['quantity']; ?>"
                                            min="1"
                                            max="<?php echo $product['StockQuantity']; ?>"
                                            readonly>
                                        <button class="btn btn-outline-secondary" type="button"
                                            onclick="increaseQty('qty-<?php echo $product['ProductID']; ?>')">
                                            <i class="bi bi-plus"></i>
                                        </button>
                                    </div>
                                    <small class="text-muted d-block mt-1">
                                        Stock: <?php echo $product['StockQuantity']; ?>
                                    </small>
                                </div>

                                <!-- Subtotal -->
                                <div class="col-md-2 d-none d-md-block">
                                    <p class="fw-bold text-success mb-0">
                                        <?php echo format_price($item['subtotal']); ?>
                                    </p>
                                    <small class="text-muted">subtotal</small>
                                </div>

                                <!-- Remove -->
                                <div class="col-md-2 col-12 text-end">
                                    <button type="submit" name="remove_item"
                                        onclick="this.form.product_id.value=<?php echo $product['ProductID']; ?>; return confirmRemove();"
                                        class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>

                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="card-footer bg-white text-end">
                        <input type="hidden" name="product_id" value="">
                        <button type="submit" name="update_cart" class="btn btn-outline-success">
                            <i class="bi bi-arrow-repeat me-1"></i>Update Cart
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Order Summary -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm" style="position: sticky; top: 100px; z-index: 1;">

                <div class="card-header bg-white fw-semibold">
                    <i class="bi bi-receipt me-2 text-success"></i>Order Summary
                </div>
                <div class="card-body">

                    <!-- Item Breakdown -->
                    <?php foreach ($cart_items as $item): ?>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted small">
                            <?php echo htmlspecialchars(substr($item['product']['ProductName'], 0, 20)); ?>
                            <span class="badge bg-light text-dark">×<?php echo $item['quantity']; ?></span>
                        </span>
                        <span class="small fw-semibold">
                            <?php echo format_price($item['subtotal']); ?>
                        </span>
                    </div>
                    <?php endforeach; ?>

                    <hr>

                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Subtotal</span>
                        <span class="fw-semibold"><?php echo format_price($cart_total); ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Delivery</span>
                        <span class="text-success fw-semibold">FREE</span>
                    </div>

                    <hr>

                    <div class="d-flex justify-content-between mb-4">
                        <span class="fw-bold fs-5">Total</span>
                        <span class="fw-bold fs-5 text-success">
                            <?php echo format_price($cart_total); ?>
                        </span>
                    </div>

                    <div class="d-grid gap-2">
                        <a href="checkout.php" class="btn btn-success btn-lg fw-semibold">
                            <i class="bi bi-lock me-2"></i>Proceed to Checkout
                        </a>
                        <a href="shop.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i>Continue Shopping
                        </a>
                    </div>

                </div>

                <!-- Trust Badges -->
                <div class="card-footer bg-light">
                    <div class="row text-center g-2">
                        <div class="col-4">
                            <i class="bi bi-shield-check text-success fs-5"></i>
                            <p class="small text-muted mb-0" style="font-size:0.7rem;">Secure</p>
                        </div>
                        <div class="col-4">
                            <i class="bi bi-truck text-success fs-5"></i>
                            <p class="small text-muted mb-0" style="font-size:0.7rem;">Free Delivery</p>
                        </div>
                        <div class="col-4">
                            <i class="bi bi-arrow-repeat text-success fs-5"></i>
                            <p class="small text-muted mb-0" style="font-size:0.7rem;">Easy Returns</p>
                        </div>
                    </div>
                </div>

            </div>
        </div>

    </div>

    <?php else: ?>

    <!-- Empty Cart -->
    <div class="text-center py-5">
        <div class="mb-4">
            <i class="bi bi-cart-x" style="font-size: 5rem; color: #dee2e6;"></i>
        </div>
        <h4 class="text-muted mb-2">Your cart is empty</h4>
        <p class="text-muted mb-4">Looks like you haven't added anything yet.</p>
        <a href="shop.php" class="btn btn-success btn-lg px-5">
            <i class="bi bi-shop me-2"></i>Start Shopping
        </a>
    </div>

    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
