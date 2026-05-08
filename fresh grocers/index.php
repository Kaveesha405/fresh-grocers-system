<?php
require_once 'config.php';

// Redirect based on role
if (is_admin()) {
    header("Location: admin/index.php");
    exit();
} elseif (is_delivery()) {
    header("Location: delivery/index.php");
    exit();
} elseif (is_csr()) {
    header("Location: csr/index.php");
    exit();
}

// For customers (logged in or guest), redirect to customer area
header("Location: customer/index.php");
exit();
?>
