<?php
require_once '../config.php';

if (!is_logged_in()) {
    set_error_message("Please login to proceed to checkout.");
    redirect('login.php');
}

$customer    = get_customer_info();
$customer_id = (int)$customer['CustomerID'];

if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    redirect('cart.php');
}

// Build cart items
$cart_items = [];
$cart_total = 0;
foreach ($_SESSION['cart'] as $pid => $item) {
    $p = get_product($pid);
    if ($p) {
        $sub         = $p['Price'] * $item['quantity'];
        $cart_total += $sub;
        $cart_items[] = [
            'id'       => (int)$pid,
            'qty'      => $item['quantity'],
            'price'    => $p['Price'],
            'name'     => $p['ProductName'],
            'subtotal' => $sub
        ];
    }
}

// ─── cURL helper ────────────────────────────────────
function geocurl($url) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERAGENT      => 'FreshGrocers-App/1.0 (contact@freshgrocers.lk)',
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER     => ['Accept-Language: en']
    ]);
    $resp = curl_exec($ch);
    $err  = curl_error($ch);
    curl_close($ch);
    return [$resp, $err];
}

// ─── AJAX: GEOCODE (address → lat/lng) ──────────────
if (isset($_GET['geocode_address'])) {
    $address = trim($_GET['geocode_address']);
    if (stripos($address, 'sri lanka') === false) {
        $address .= ', Sri Lanka';
    }
    $url  = "https://nominatim.openstreetmap.org/search?q=" . urlencode($address) . "&format=json&limit=3&countrycodes=lk&addressdetails=1";
    [$resp, $err] = geocurl($url);
    header('Content-Type: application/json');
    echo ($resp && !$err) ? $resp : json_encode(['error' => 'cURL: ' . $err]);
    exit();
}

// ─── AJAX: REVERSE GEOCODE (lat/lng → address) ──────
if (isset($_GET['reverse_geocode'])) {
    $lat  = (float)$_GET['lat'];
    $lng  = (float)$_GET['lng'];
    $url  = "https://nominatim.openstreetmap.org/reverse?lat=$lat&lon=$lng&format=json";
    [$resp, $err] = geocurl($url);
    header('Content-Type: application/json');
    echo ($resp && !$err) ? $resp : json_encode(['error' => 'Reverse geocode failed']);
    exit();
}

// ─── AJAX: SORTED AGENTS (ALL AGENTS, CLOSEST FIRST) ──
if (isset($_GET['ajax_agents'])) {
    $lat = (float)$_GET['lat'];
    $lng = (float)$_GET['lng'];

    $agents = $conn->query("
        SELECT d.DeliveryAgentID,
               CONCAT(d.FirstName,' ',d.LastName) AS FullName,
               d.PhoneNumber,
               d.Location,
               COUNT(o.OrderID) AS ActiveOrders,
               (
                 6371 * acos(
                   cos(radians($lat)) * cos(radians(d.LocationLat))
                   * cos(radians(d.LocationLng) - radians($lng))
                   + sin(radians($lat)) * sin(radians(d.LocationLat))
                 )
               ) AS distance_km
        FROM DeliveryAgent d
        LEFT JOIN `Order` o
            ON o.DeliveryAgentID = d.DeliveryAgentID
            AND o.OrderStatus NOT IN ('Delivered','Canceled')
        WHERE d.IsActive = 1
          AND d.LocationLat IS NOT NULL
          AND d.LocationLng IS NOT NULL
        GROUP BY d.DeliveryAgentID, d.FirstName, d.LastName, d.PhoneNumber, d.Location
        ORDER BY distance_km ASC, ActiveOrders ASC
    ");

    if ($agents && $agents->num_rows > 0) {
        $first = true;
        while ($ag = $agents->fetch_assoc()) {
            $dist     = isset($ag['distance_km']) ? round((float)$ag['distance_km'], 1) : null;
            $dist_txt = $dist !== null ? $dist . ' km away' : 'Distance unknown';
            $busy     = (int)$ag['ActiveOrders'];
            $busy_col = $busy === 0 ? 'success' : ($busy <= 2 ? 'warning' : 'danger');
            $busy_lbl = $busy === 0 ? 'Available' : $busy . ' active order(s)';
            $initial  = strtoupper(substr($ag['FullName'], 0, 1));
            $fname    = htmlspecialchars($ag['FullName']);
            $loc      = htmlspecialchars($ag['Location'] ?? 'Location not set');
            $phone    = htmlspecialchars($ag['PhoneNumber']);
            $checked  = $first ? 'checked' : '';
            $sel      = $first ? 'selected' : '';

            echo "
            <div class='col-md-6'>
                <label class='agent-card d-block border rounded-3 p-3 $sel' style='cursor:pointer;transition:all .2s;'>
                    <input type='radio' name='agent_id' value='{$ag['DeliveryAgentID']}' class='d-none agent-radio' $checked>
                    <div class='d-flex align-items-center gap-3'>
                        <div class='rounded-circle bg-success bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0' style='width:44px;height:44px;'>
                            <span class='fw-bold text-success'>$initial</span>
                        </div>
                        <div class='flex-grow-1'>
                            <div class='d-flex justify-content-between'>
                                <p class='mb-0 fw-semibold small'>$fname</p>
                                <span class='badge bg-{$busy_col}' style='font-size:0.65rem;'>$dist_txt</span>
                            </div>
                            <small class='text-muted d-block'><i class='bi bi-geo-alt me-1'></i>$loc</small>
                            <small class='text-muted'><i class='bi bi-telephone me-1'></i>$phone &nbsp;
                                <span class='badge bg-{$busy_col} bg-opacity-25 text-dark'>$busy_lbl</span>
                            </small>
                        </div>
                    </div>
                </label>
            </div>";
            $first = false;
        }
    } else {
        echo "<div class='col-12'><div class='alert alert-warning mb-0'><i class='bi bi-exclamation-triangle me-2'></i>No delivery agents available right now.</div></div>";
    }
    exit();
}

$error = '';

// ─── HANDLE ORDER PLACEMENT ─────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $delivery_address = clean_input($_POST['delivery_address'] ?? '');
    $payment_method   = clean_input($_POST['payment_method'] ?? 'Cash on Delivery');
    $agent_id         = isset($_POST['agent_id']) ? (int)$_POST['agent_id'] : 0;
    $delivery_lat     = isset($_POST['delivery_lat']) && $_POST['delivery_lat'] !== '' ? (float)$_POST['delivery_lat'] : null;
    $delivery_lng     = isset($_POST['delivery_lng']) && $_POST['delivery_lng'] !== '' ? (float)$_POST['delivery_lng'] : null;

    if (empty($delivery_address)) {
        $error = "Please enter a delivery address.";
    } elseif ($agent_id === 0) {
        $error = "Please load and select a delivery agent.";
    } else {
        $pay_status = ($payment_method === 'Card') ? 'Paid' : 'Pending';
        $cust_name  = clean_input($customer['FirstName'] . ' ' . $customer['LastName']);
        $cust_phone = clean_input($customer['PhoneNumber']);
        $lat_val    = $delivery_lat !== null ? $delivery_lat : 'NULL';
        $lng_val    = $delivery_lng !== null ? $delivery_lng : 'NULL';

        $sql = "INSERT INTO `Order`
                    (CustomerID, CustomerName, CustomerPhone, DeliveryAddress,
                     DeliveryLat, DeliveryLng, DeliveryAgentID, TotalAmount,
                     OrderStatus, PaymentStatus, PlacedByCsr)
                VALUES
                    ($customer_id, '$cust_name', '$cust_phone', '$delivery_address',
                     $lat_val, $lng_val, $agent_id, $cart_total,
                     'Confirmed', '$pay_status', 0)";

        if ($conn->query($sql)) {
            $order_id = $conn->insert_id;

            foreach ($cart_items as $item) {
                $conn->query("INSERT INTO OrderItem (OrderID, ProductID, Quantity, UnitPrice)
                              VALUES ($order_id, {$item['id']}, {$item['qty']}, {$item['price']})");
                $conn->query("UPDATE Product SET StockQuantity = StockQuantity - {$item['qty']}
                              WHERE ProductID = {$item['id']}");
            }

            $pay_enum_map = ['Cash on Delivery' => 'CashOnDelivery', 'Card' => 'Card', 'Bank Transfer' => 'MobileWallet'];
            $pay_enum     = $pay_enum_map[$payment_method] ?? 'CashOnDelivery';
            $conn->query("INSERT INTO Payment (Amount, PaymentMethod, PaymentStatus, OrderID)
                          VALUES ($cart_total, '$pay_enum', '$pay_status', $order_id)");

            $agent_row   = $conn->query("SELECT FirstName, LastName, PhoneNumber FROM DeliveryAgent WHERE DeliveryAgentID = $agent_id")->fetch_assoc();
            $agent_name  = $agent_row['FirstName'] . ' ' . $agent_row['LastName'];
            $agent_phone = $agent_row['PhoneNumber'];

            $sms = "FreshGrocers: Order #$order_id Confirmed! Your delivery agent: $agent_name ($agent_phone). Est. delivery: 30-60 mins. Thank you!";

            $_SESSION['success_message'] = "<div class='no-auto-dismiss'>
                <i class='bi bi-check-circle me-2'></i>
                <b>Order #$order_id Placed Successfully!</b><br><br>
                <b>(Localhost Test — Simulated SMS to $cust_phone):</b><br>
                <span class='d-block mt-2 p-2 bg-dark text-white rounded small'>$sms</span>
            </div>";

            clear_cart();
            redirect("track-order.php?id=$order_id");
        } else {
            $error = "Order placement failed: " . $conn->error;
        }
    }
}

$page_title = "Checkout";
include '../includes/customer-header.php';
?>

<!-- Styles moved to assets/css/style.css -->

<div class="container my-5">

    <div class="mb-4">
        <h3 class="fw-bold mb-1"><i class="bi bi-credit-card me-2 text-success"></i>Checkout</h3>
        <p class="text-muted mb-0 small">Complete your order below</p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle me-2"></i><?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <form method="POST" action="" id="checkout-form">
        <div class="row g-4">

            <!-- LEFT COLUMN -->
            <div class="col-lg-7">

                <!-- Step 1: Delivery Details -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <span class="fw-semibold">
                            <span class="badge bg-success rounded-pill me-2">1</span>
                            Delivery Details
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">

                            <div class="col-md-6">
                                <label class="form-label small text-muted">Full Name</label>
                                <input type="text" class="form-control bg-light"
                                    value="<?php echo htmlspecialchars($customer['FirstName'] . ' ' . $customer['LastName']); ?>"
                                    readonly>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small text-muted">
                                    Phone
                                    <span class="text-success small">(SMS sent here)</span>
                                </label>
                                <input type="text" class="form-control bg-light"
                                    value="<?php echo htmlspecialchars($customer['PhoneNumber']); ?>"
                                    readonly>
                            </div>

                            <!-- GPS Auto-detect -->
                            <div class="col-12 mt-4">
                                <button type="button" id="btn-detect-gps"
                                        class="btn btn-success w-100 fw-semibold">
                                    <i class="bi bi-crosshair me-2"></i>Use My Current Location (GPS)
                                </button>
                                <div id="gps-status" class="mt-1"></div>
                            </div>

                            <div class="location-divider">or enter address manually</div>

                            <!-- Manual Address -->
                            <div class="col-12">
                                <label class="form-label fw-semibold small">
                                    Delivery Address <span class="text-danger">*</span>
                                </label>
                                <textarea name="delivery_address"
                                          id="delivery-address"
                                          class="form-control" rows="2"
                                          placeholder="e.g. 123 Galle Road, Colombo 03"
                                          required><?php echo htmlspecialchars($customer['Address'] ?? ''); ?></textarea>
                            </div>

                            <div class="col-12">
                                <button type="button" id="btn-find-agents"
                                        class="btn btn-outline-success w-100 fw-semibold">
                                    <i class="bi bi-geo-alt me-2"></i>Find Delivery Agents (using address above)
                                </button>
                            </div>

                            <!-- Hidden GPS coords -->
                            <input type="hidden" name="delivery_lat" id="delivery-lat" value="">
                            <input type="hidden" name="delivery_lng" id="delivery-lng" value="">

                        </div>
                    </div>
                </div>

                <!-- Step 2: Choose Agent -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <span class="fw-semibold">
                            <span class="badge bg-success rounded-pill me-2">2</span>
                            Choose Delivery Agent <span class="text-danger">*</span>
                        </span>
                        <span class="badge bg-success bg-opacity-10 text-success fw-normal small">
                            Closest at the top
                        </span>
                    </div>
                    <div class="card-body">
                        <div id="agent-placeholder" class="text-center py-4">
                            <i class="bi bi-truck fs-1 text-muted opacity-25 mb-2 d-block"></i>
                            <p class="text-muted small mb-0">
                                Use GPS or enter your address above to<br>
                                list all available drivers.
                            </p>
                        </div>
                        <div class="row g-2" id="agent-cards"></div>
                    </div>
                </div>

                <!-- Step 3: Payment -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3">
                        <span class="fw-semibold">
                            <span class="badge bg-success rounded-pill me-2">3</span>
                            Payment Method
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">

                            <div class="col-12">
                                <label class="payment-option d-block border rounded-3 p-3 selected" style="cursor:pointer;">
                                    <input type="radio" name="payment_method" value="Cash on Delivery" class="d-none" checked>
                                    <div class="d-flex align-items-center gap-3">
                                        <span class="fs-3">💵</span>
                                        <div>
                                            <p class="fw-semibold mb-0">Cash on Delivery</p>
                                            <small class="text-muted">Pay when your order arrives</small>
                                        </div>
                                    </div>
                                </label>
                            </div>

                            <div class="col-12">
                                <label class="payment-option d-block border rounded-3 p-3" style="cursor:pointer;">
                                    <input type="radio" name="payment_method" value="Card" class="d-none">
                                    <div class="d-flex align-items-center gap-3">
                                        <span class="fs-3">💳</span>
                                        <div>
                                            <p class="fw-semibold mb-0">Credit / Debit Card</p>
                                            <small class="text-muted">Secure online payment — marked as Paid immediately</small>
                                        </div>
                                    </div>
                                </label>
                            </div>

                            <div class="col-12">
                                <label class="payment-option d-block border rounded-3 p-3" style="cursor:pointer;">
                                    <input type="radio" name="payment_method" value="Bank Transfer" class="d-none">
                                    <div class="d-flex align-items-center gap-3">
                                        <span class="fs-3">🏦</span>
                                        <div>
                                            <p class="fw-semibold mb-0">Bank Transfer</p>
                                            <small class="text-muted">Direct bank deposit</small>
                                        </div>
                                    </div>
                                </label>
                            </div>

                        </div>
                    </div>
                </div>

            </div>

            <!-- RIGHT SIDEBAR: ORDER SUMMARY -->
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm sticky-top" style="top:80px;">
                    <div class="card-header bg-white fw-semibold py-3">
                        <i class="bi bi-receipt me-2 text-success"></i>Order Summary
                    </div>
                    <div class="card-body">

                        <?php foreach ($cart_items as $item): ?>
                        <div class="d-flex justify-content-between mb-2 small">
                            <span class="text-muted text-truncate me-2" style="max-width:200px;">
                                <?php echo htmlspecialchars($item['name']); ?>
                                <span class="badge bg-light text-dark ms-1">×<?php echo $item['qty']; ?></span>
                            </span>
                            <span class="fw-semibold"><?php echo format_price($item['subtotal']); ?></span>
                        </div>
                        <?php endforeach; ?>

                        <hr>

                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Subtotal</span>
                            <span class="fw-semibold"><?php echo format_price($cart_total); ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-3">
                            <span class="text-muted">Delivery</span>
                            <span class="text-success fw-semibold">FREE</span>
                        </div>

                        <div class="d-flex justify-content-between p-3 bg-light rounded-3 mb-4">
                            <span class="fw-bold fs-5">Total</span>
                            <span class="fw-bold fs-5 text-success"><?php echo format_price($cart_total); ?></span>
                        </div>

                        <div class="d-grid">
                            <button type="submit" name="place_order" class="btn btn-success btn-lg fw-bold">
                                <i class="bi bi-check2-circle me-2"></i>Confirm & Place Order
                            </button>
                        </div>

                        <div class="mt-3 text-center">
                            <small class="text-muted">
                                <i class="bi bi-shield-check text-success me-1"></i>
                                Your order info is secure
                            </small>
                        </div>

                    </div>

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
                                <i class="bi bi-chat-dots text-success fs-5"></i>
                                <p class="small text-muted mb-0" style="font-size:0.7rem;">SMS Updates</p>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </form>
</div>

<!-- Script moved to assets/js/script.js -->

<?php include '../includes/footer.php'; ?>
