<?php
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = (int)$_POST['product_id'];
    $quantity   = max(1, (int)($_POST['quantity'] ?? 1));
    $redirect   = isset($_POST['redirect']) ? clean_input($_POST['redirect']) : '../customer/shop.php';

    $product = get_product($product_id);

    if (!$product) {
        set_error_message("Product not found.");
    } elseif (!check_stock($product_id, $quantity)) {
        set_error_message("Sorry, not enough stock for " . $product['ProductName']);
    } else {
        add_to_cart($product_id, $quantity);
        set_success_message("'" . $product['ProductName'] . "' added to cart!");
    }

    // Redirect back
    $back = strpos($redirect, '/') === 0 ? $redirect : '../customer/' . $redirect;
    header("Location: $back");
    exit();
}

header("Location: ../customer/shop.php");
exit();
?>
