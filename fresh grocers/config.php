<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Base URL for asset linking (used across includes)
$BASE_URL = "/fresh grocers/";

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'fresh_grocers');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");


// ============================================
// HELPER FUNCTIONS
// ============================================

function clean_input($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = $conn->real_escape_string($data);
    return $data;
}

// ---- AUTH CHECKS ----

function is_logged_in() {
    return isset($_SESSION['customer_id']);
}
function is_admin() {
    return isset($_SESSION['admin_id']);
}
function is_delivery() {
    return isset($_SESSION['agent_id']);
}
function is_csr() {
    return isset($_SESSION['csr_id']);
}

// ---- USER INFO FETCHERS ----

function get_customer_info() {
    global $conn;
    if (!is_logged_in()) return null;
    $id     = (int)$_SESSION['customer_id'];
    // SQL table: Customer (capital C)
    $result = $conn->query("SELECT * FROM Customer WHERE CustomerID = $id");
    return ($result && $result->num_rows > 0) ? $result->fetch_assoc() : null;
}

function get_admin_info() {
    global $conn;
    if (!is_admin()) return null;
    $id     = (int)$_SESSION['admin_id'];
    // SQL table: Admin (capital A)
    $result = $conn->query("SELECT * FROM Admin WHERE AdminID = $id"); // <--- Fixed here
    return ($result && $result->num_rows > 0) ? $result->fetch_assoc() : null;
}


function get_delivery_info() {
    global $conn;
    if (!is_delivery()) return null;
    $id     = (int)$_SESSION['agent_id'];
    // SQL table: DeliveryAgent, PK: DeliveryAgentID
    $result = $conn->query("SELECT * FROM DeliveryAgent WHERE DeliveryAgentID = $id");
    return ($result && $result->num_rows > 0) ? $result->fetch_assoc() : null;
}

function get_csr_info() {
    global $conn;
    if (!is_csr()) return null;
    $id     = (int)$_SESSION['csr_id'];
    // SQL table: CSR, PK: CSRID
    $result = $conn->query("SELECT * FROM CSR WHERE CSRID = $id");
    return ($result && $result->num_rows > 0) ? $result->fetch_assoc() : null;
}

// ---- CART ----

function get_cart_count() {
    if (empty($_SESSION['cart'])) return 0;
    return count($_SESSION['cart']);
}

function get_cart_total_quantity() {
    if (empty($_SESSION['cart'])) return 0;
    $total = 0;
    foreach ($_SESSION['cart'] as $item) {
        $total += $item['quantity'];
    }
    return $total;
}

function get_cart_total() {
    global $conn;
    if (empty($_SESSION['cart'])) return 0;
    $total = 0;
    foreach ($_SESSION['cart'] as $product_id => $item) {
        $product_id = (int)$product_id;
        // SQL table: Product (capital P)
        $result = $conn->query("SELECT Price FROM Product WHERE ProductID = $product_id");
        if ($result && $result->num_rows > 0) {
            $p = $result->fetch_assoc();
            $total += $p['Price'] * $item['quantity'];
        }
    }
    return $total;
}

function add_to_cart($product_id, $quantity = 1) {
    if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
    if (isset($_SESSION['cart'][$product_id])) {
        $_SESSION['cart'][$product_id]['quantity'] += $quantity;
    } else {
        $_SESSION['cart'][$product_id] = ['product_id' => $product_id, 'quantity' => $quantity];
    }
    return true;
}

function update_cart($product_id, $quantity) {
    if (isset($_SESSION['cart'][$product_id])) {
        if ($quantity <= 0) {
            unset($_SESSION['cart'][$product_id]);
        } else {
            $_SESSION['cart'][$product_id]['quantity'] = $quantity;
        }
        return true;
    }
    return false;
}

function remove_from_cart($product_id) {
    if (isset($_SESSION['cart'][$product_id])) {
        unset($_SESSION['cart'][$product_id]);
        return true;
    }
    return false;
}

function clear_cart() {
    $_SESSION['cart'] = [];
    return true;
}

// ---- PRICING ----

function format_price($price) {
    return 'Rs. ' . number_format($price, 2);
}

// ---- REDIRECT ----

function redirect($url) {
    if (strpos($url, 'http') === 0 || strpos($url, '/') === 0) {
        header("Location: " . $url);
    } else {
        $dir = rtrim(dirname($_SERVER['PHP_SELF']), '/');
        header("Location: " . $dir . '/' . $url);
    }
    exit();
}

// ---- FLASH MESSAGES ----

function set_success_message($message) {
    $_SESSION['success_message'] = $message;
}
function set_error_message($message) {
    $_SESSION['error_message'] = $message;
}
function get_success_message() {
    if (isset($_SESSION['success_message'])) {
        $msg = $_SESSION['success_message'];
        unset($_SESSION['success_message']);
        return $msg;
    }
    return null;
}
function get_error_message() {
    if (isset($_SESSION['error_message'])) {
        $msg = $_SESSION['error_message'];
        unset($_SESSION['error_message']);
        return $msg;
    }
    return null;
}

// ---- STOCK & PRODUCTS ----

function check_stock($product_id, $quantity = 1) {
    global $conn;
    $product_id = (int)$product_id;
    // SQL table: Product (capital P)
    $result = $conn->query("SELECT StockQuantity FROM Product WHERE ProductID = $product_id");
    if ($result && $result->num_rows > 0) {
        $p = $result->fetch_assoc();
        return $p['StockQuantity'] >= $quantity;
    }
    return false;
}

function get_product($product_id) {
    global $conn;
    $product_id = (int)$product_id;
    // SQL table: Product (capital P)
    $result = $conn->query("SELECT * FROM Product WHERE ProductID = $product_id");
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    return null;
}

// ---- UTILITIES ----

function generate_order_id() {
    return 'ORD' . date('Ymd') . rand(1000, 9999);
}

function time_ago($timestamp) {
    $diff    = time() - strtotime($timestamp);
    $minutes = round($diff / 60);
    $hours   = round($diff / 3600);
    $days    = round($diff / 86400);
    $weeks   = round($diff / 604800);
    $months  = round($diff / 2629440);
    $years   = round($diff / 31553280);

    if ($diff <= 60)     return "Just now";
    if ($minutes <= 60)  return "$minutes min ago";
    if ($hours <= 24)    return "$hours hours ago";
    if ($days <= 7)      return "$days days ago";
    if ($weeks <= 4)     return "$weeks weeks ago";
    if ($months <= 12)   return "$months months ago";
    return "$years years ago";
}

function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validate_phone($phone) {
    $phone = preg_replace('/[\s\-]/', '', $phone);
    return preg_match('/^0[0-9]{9}$/', $phone);
}

function log_activity($user_type, $user_id, $action, $description = '') {
    global $conn;
    $user_type   = clean_input($user_type);
    $user_id     = (int)$user_id;
    $action      = clean_input($action);
    $description = clean_input($description);
    $ip          = $_SERVER['REMOTE_ADDR'];
    return $conn->query("INSERT INTO ActivityLog (UserType, UserID, Action, Description, IPAddress, LogDate)
                         VALUES ('$user_type', $user_id, '$action', '$description', '$ip', NOW())");
}

// ini_set('display_errors', 1);
// error_reporting(E_ALL);
?>
