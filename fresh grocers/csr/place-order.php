<?php
error_reporting(0);
ini_set('display_errors', 0);
require_once '../config.php';
if (!isset($_SESSION['csr_id'])) {
    header("Location: ../csr/login.php");
    exit();
}

// ==========================================
// AJAX ENDPOINT 2: RETURN AGENT CARDS HTML (for fetch fragment)
// ==========================================
if (isset($_GET['ajax_agents']) && isset($_GET['lat']) && isset($_GET['lng'])) {
    // Return only the inner agent card HTML so client can inject it safely
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: text/html; charset=utf-8');

    $customer_lat = (float)$_GET['lat'];
    $customer_lng = (float)$_GET['lng'];

    $agents_q = $conn->query(
        "SELECT d.DeliveryAgentID,
                CONCAT(d.FirstName,' ',d.LastName) AS FullName,
                d.PhoneNumber,
                d.Location,
                d.LocationLat,
                d.LocationLng,
                COUNT(o.OrderID) AS ActiveOrders,
                (
                  6371 * acos(
                    cos(radians($customer_lat))
                    * cos(radians(d.LocationLat))
                    * cos(radians(d.LocationLng) - radians($customer_lng))
                    + sin(radians($customer_lat))
                    * sin(radians(d.LocationLat))
                  )
                ) AS distance_km
         FROM DeliveryAgent d
         LEFT JOIN `Order` o
           ON o.DeliveryAgentID = d.DeliveryAgentID
          AND o.OrderStatus NOT IN ('Delivered','Canceled')
         WHERE d.IsActive = 1
           AND d.LocationLat IS NOT NULL
           AND d.LocationLng IS NOT NULL
         GROUP BY d.DeliveryAgentID, d.FirstName, d.LastName, d.PhoneNumber, d.Location, d.LocationLat, d.LocationLng
         ORDER BY distance_km ASC, ActiveOrders ASC"
    );

    if ($agents_q && $agents_q->num_rows > 0) {
        $first_agent = true;
        while ($ag = $agents_q->fetch_assoc()):
            $busy_color = $ag['ActiveOrders'] == 0 ? 'success' : ($ag['ActiveOrders'] <= 2 ? 'warning' : 'danger');
            $busy_label = $ag['ActiveOrders'] == 0 ? 'Available' : $ag['ActiveOrders'] . ' active order(s)';
            $distance = isset($ag['distance_km']) ? round((float)$ag['distance_km'], 1) : null;
            ?>
            <div class="col-md-6">
                <label class="agent-card d-block border rounded-3 p-3 <?php echo $first_agent ? 'selected' : ''; ?> shadow-sm">
                    <input type="radio" name="agent_id"
                           value="<?php echo $ag['DeliveryAgentID']; ?>"
                           class="d-none agent-radio"
                           <?php echo $first_agent ? 'checked' : ''; ?> />

                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0" style="width:44px;height:44px;">
                            <span class="fw-bold text-primary fs-5"><?php echo strtoupper(substr($ag['FullName'], 0, 1)); ?></span>
                        </div>

                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-start">
                                <p class="mb-0 fw-bold text-dark fs-6"><?php echo htmlspecialchars($ag['FullName']); ?></p>
                                <span class="badge bg-<?php echo $busy_color; ?> ms-1 shadow-sm">
                                    <?php echo $distance !== null ? ($distance . "km • ") : ""; ?>
                                    <?php echo $busy_label; ?>
                                </span>
                            </div>
                            <small class="text-muted d-block fw-semibold mt-1"><i class="bi bi-geo-alt me-1"></i><?php echo htmlspecialchars($ag['Location'] ?? 'Location not set'); ?></small>
                            <small class="text-muted fw-semibold"><i class="bi bi-telephone me-1"></i><?php echo htmlspecialchars($ag['PhoneNumber']); ?></small>
                        </div>
                    </div>
                </label>
            </div>
            <?php
            $first_agent = false;
        endwhile;
    } else {
        echo '<div class="alert alert-warning mb-0 w-100 shadow-sm fw-semibold"><i class="bi bi-exclamation-triangle-fill me-2"></i>No delivery agents with GPS location available.</div>';
    }
    exit();
}

// ==========================================
// AJAX ENDPOINT 1: GEOCODE ADDRESS VIA PHP
// ==========================================
if (isset($_GET['geocode_address'])) {
    // Clear any previous output buffers that might contain HTML
    while (ob_get_level()) {
        ob_end_clean();
    }

    header('Content-Type: application/json; charset=utf-8');

    $address = trim($_GET['geocode_address']);
    if (empty($address)) {
        echo json_encode(['error' => 'Address cannot be empty']);
        exit();
    }

    $url = "https://nominatim.openstreetmap.org/search?" . http_build_query([
        'q' => $address,
        'format' => 'json',
        'limit' => 1
    ]);

    // Try file_get_contents first (correct stream context key is 'header')
    $context = stream_context_create([
        'http' => [
            'header' => "User-Agent: FreshGrocers-App/1.0 (contact@freshgrocers.lk)\r\n",
            'timeout' => 10
        ]
    ]);

    $response = @file_get_contents($url, false, $context);

    // If file_get_contents failed, attempt a cURL fallback (more reliable on some hosts)
    if ($response === FALSE) {
        if (function_exists('curl_version')) {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_USERAGENT => "FreshGrocers-App/1.0 (contact@freshgrocers.lk)",
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_SSL_VERIFYPEER => false,
            ]);
            $response = curl_exec($ch);
            $curlErr = curl_errno($ch) ? curl_error($ch) : null;
            curl_close($ch);

            if ($response === false || $response === '') {
                $msg = $curlErr ? $curlErr : 'No response from map service.';
                echo json_encode(['error' => 'Failed to reach map service. ' . $msg]);
                exit();
            }
        } else {
            echo json_encode(['error' => 'Failed to reach map service. Please try again.']);
            exit();
        }
    }

    // Validate JSON response
    $json = json_decode($response, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        echo json_encode($json);
    } else {
        echo json_encode(['error' => 'Invalid response from map service.']);
    }
    exit(); // CRITICAL: Stop script so HTML doesn't render
}

// ==========================================

$error = '';

// Products (Fetching ImageURL alongside other data)
$products = $conn->query("SELECT * FROM Product WHERE StockQuantity > 0 ORDER BY Category, ProductName");

// Default coords (Colombo) used on initial page load
$customer_lat = isset($_POST['delivery_lat']) ? (float)$_POST['delivery_lat'] : 6.9271;
$customer_lng = isset($_POST['delivery_lng']) ? (float)$_POST['delivery_lng'] : 79.8612;

// Initial Load Agents (Strict Sort)
$agents = $conn->query("
    SELECT d.DeliveryAgentID,
           CONCAT(d.FirstName,' ',d.LastName) AS FullName,
           d.PhoneNumber,
           d.Location,
           d.LocationLat,
           d.LocationLng,
           COUNT(o.OrderID) AS ActiveOrders,
           (
             6371 * acos(
               cos(radians($customer_lat))
               * cos(radians(d.LocationLat))
               * cos(radians(d.LocationLng) - radians($customer_lng))
               + sin(radians($customer_lat))
               * sin(radians(d.LocationLat))
             )
           ) AS distance_km
    FROM DeliveryAgent d
    LEFT JOIN `Order` o
      ON o.DeliveryAgentID = d.DeliveryAgentID
     AND o.OrderStatus NOT IN ('Delivered','Canceled')
    WHERE d.IsActive = 1
      AND d.LocationLat IS NOT NULL
      AND d.LocationLng IS NOT NULL
    GROUP BY d.DeliveryAgentID, d.FirstName, d.LastName, d.PhoneNumber, d.Location, d.LocationLat, d.LocationLng
    ORDER BY distance_km ASC, ActiveOrders ASC
");

// Place order
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {

    $customer_name  = clean_input($_POST['customer_name'] ?? '');
    $customer_phone = clean_input($_POST['customer_phone'] ?? '');
    $delivery_addr  = clean_input($_POST['delivery_address'] ?? '');

    $delivery_lat   = isset($_POST['delivery_lat']) ? (float)$_POST['delivery_lat'] : 0;
    $delivery_lng   = isset($_POST['delivery_lng']) ? (float)$_POST['delivery_lng'] : 0;

    $agent_id       = !empty($_POST['agent_id']) ? (int)$_POST['agent_id'] : 0;
    $payment_method = clean_input($_POST['payment_method'] ?? '');

    $product_ids    = $_POST['product_ids'] ?? [];
    $quantities     = $_POST['quantities']  ?? [];

    if (empty($customer_phone)) {
        $error = "Customer phone number is required.";
    } elseif (!preg_match('/^[0-9]{10}$/', $customer_phone)) {
        $error = "Please enter a valid 10-digit phone number.";
    } elseif (empty($delivery_addr)) {
        $error = "Please enter a delivery address.";
    } elseif (!$agent_id) {
        $error = "Please assign a delivery agent.";
    } elseif (empty($product_ids)) {
        $error = "Please add at least one product.";
    } else {
        $total = 0;
        $items = [];

        foreach ($product_ids as $i => $pid) {
            $pid = (int)$pid;
            $qty = (int)($quantities[$i] ?? 1);

            if (!$pid || $qty <= 0) continue;

            // Using the existing config helper function (Ensure it queries properly)
            $p = $conn->query("SELECT * FROM Product WHERE ProductID = $pid")->fetch_assoc();
            
            if ($p && $p['StockQuantity'] >= $qty) {
                $total += $p['Price'] * $qty;
                $items[] = [
                    'id'    => $pid,
                    'qty'   => $qty,
                    'price' => $p['Price'],
                    'name'  => $p['ProductName'],
                ];
            } else {
                $error = "Insufficient stock for: " . ($p['ProductName'] ?? "Product #$pid");
                break;
            }
        }

        if (!$error && !empty($items)) {
            $payment_enum_map = [
                'Cash on Delivery' => 'CashOnDelivery',
                'Card'             => 'Card',
                'Bank Transfer'    => 'MobileWallet',
            ];

            $payment_enum = $payment_enum_map[$payment_method] ?? 'CashOnDelivery';
            $pay_status   = ($payment_method === 'Card') ? 'Paid' : 'Pending';

            // Guest order placed by CSR (CustomerID = NULL)
            $sql = "INSERT INTO `Order`
                        (CustomerID, CustomerName, CustomerPhone, DeliveryAddress, DeliveryLat, DeliveryLng,
                         DeliveryAgentID, TotalAmount, OrderStatus, PaymentStatus, PlacedByCsr)
                    VALUES
                        (NULL, '$customer_name', '$customer_phone', '$delivery_addr',
                         " . ($delivery_lat ?: "NULL") . ", " . ($delivery_lng ?: "NULL") . ",
                         $agent_id, $total, 'Confirmed', '$pay_status', 1)";

            if ($conn->query($sql)) {
                $order_id = $conn->insert_id;

                foreach ($items as $item) {
                    $conn->query("INSERT INTO OrderItem (OrderID, ProductID, Quantity, UnitPrice)
                                  VALUES ($order_id, {$item['id']}, {$item['qty']}, {$item['price']})");

                    $conn->query("UPDATE Product
                                  SET StockQuantity = StockQuantity - {$item['qty']}
                                  WHERE ProductID = {$item['id']}");
                }

                $conn->query("INSERT INTO Payment (Amount, PaymentMethod, PaymentStatus, OrderID)
                              VALUES ($total, '$payment_enum', '$pay_status', $order_id)");

                set_success_message("Order #$order_id placed! SMS confirmation sent to $customer_phone.");
                header("Location: ../csr/orders.php");
                exit();
            } else {
                $error = "Failed to place order: " . $conn->error;
            }
        }
    }
}
?>

<?php $page_title = "Place Order"; include '../includes/csr-header.php'; ?>

<div class="container-fluid py-4 px-4" style="background-color: #f4f6f9; min-height: 85vh;">

    <div class="mb-4 pb-2 border-bottom">
        <h4 class="fw-bold mb-1 text-dark">
            <i class="bi bi-cart-plus me-2 text-primary"></i>Place New Order
        </h4>
        <p class="text-muted mb-0 fw-semibold">Manually place a grocery order on behalf of a customer via phone call.</p>
    </div>

    <?php $msg = get_success_message(); if ($msg): ?>
        <div class="alert alert-success alert-dismissible shadow-sm fade show">
            <i class="bi bi-check-circle-fill me-2"></i><?php echo $msg; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible shadow-sm fade show">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <form method="POST" action="" id="place-order-form">
        <div class="row g-4">

            <!-- LEFT -->
            <div class="col-lg-8">

                <!-- Customer Details Card -->
                <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px; overflow: hidden;">
                    <div class="card-header bg-white fw-bold py-3 fs-5 border-bottom">
                        <i class="bi bi-person-lines-fill me-2 text-primary"></i>Customer Details
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-4">

                            <div class="col-md-6">
                                <label class="form-label fw-bold text-muted small text-uppercase mb-1">
                                    Customer Name <span class="text-muted fw-normal text-lowercase">(optional)</span>
                                </label>
                                <div class="input-group shadow-sm">
                                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-person text-muted"></i></span>
                                    <input type="text" name="customer_name" class="form-control border-start-0"
                                           placeholder="e.g. Nimal Silva"
                                           value="<?php echo htmlspecialchars($_POST['customer_name'] ?? ''); ?>">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold text-muted small text-uppercase mb-1">
                                    Phone Number <span class="text-danger">*</span>
                                </label>
                                <div class="input-group shadow-sm">
                                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-telephone text-muted"></i></span>
                                    <input type="tel" name="customer_phone" class="form-control border-start-0"
                                           placeholder="e.g. 0771234567" maxlength="10"
                                           value="<?php echo htmlspecialchars($_POST['customer_phone'] ?? ''); ?>" required>
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-bold text-muted small text-uppercase mb-1">
                                    Delivery Address <span class="text-danger">*</span>
                                </label>
                                <div class="input-group shadow-sm">
                                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-geo-alt text-muted"></i></span>
                                    <textarea name="delivery_address" id="delivery-address"
                                              class="form-control border-start-0" rows="2"
                                              placeholder="e.g. 123 Galle Road, Colombo 03" required><?php echo htmlspecialchars($_POST['delivery_address'] ?? ''); ?></textarea>
                                    
                                    <button type="button" id="btn-find-agents" class="btn btn-outline-primary px-3 d-flex align-items-center flex-column justify-content-center border-start-0">
                                        <i class="bi bi-geo-alt-fill fs-5 mb-1"></i>
                                        <small class="fw-bold">Find Nearest</small>
                                    </button>
                                </div>
                                <small class="text-muted mt-2 d-inline-block"><i class="bi bi-info-circle me-1"></i>Type address and click "Find Nearest" to update delivery agents automatically.</small>
                            </div>

                            <!-- Hidden coords -->
                            <input type="hidden" name="delivery_lat" id="delivery-lat" value="<?php echo htmlspecialchars($_POST['delivery_lat'] ?? $customer_lat); ?>">
                            <input type="hidden" name="delivery_lng" id="delivery-lng" value="<?php echo htmlspecialchars($_POST['delivery_lng'] ?? $customer_lng); ?>">

                            <div class="col-md-6">
                                <label class="form-label fw-bold text-muted small text-uppercase mb-1">Payment Method</label>
                                <select name="payment_method" id="payment-method" class="form-select shadow-sm fw-semibold">
                                    <option value="Cash on Delivery">💵 Cash on Delivery</option>
                                    <option value="Card">💳 Card (Paid immediately)</option>
                                    <option value="Bank Transfer">🏦 Bank Transfer</option>
                                </select>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- Agents -->
                <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px; overflow: hidden;">
                    <div class="card-header bg-white fw-bold py-3 fs-5 border-bottom d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-truck me-2 text-primary"></i>Assign Delivery Agent <span class="text-danger">*</span></span>
                        <span class="badge bg-primary bg-opacity-10 text-primary fw-semibold small px-3 py-2 rounded-pill shadow-sm">Sorted by closest</span>
                    </div>

                    <div class="card-body p-4">
                        <div class="row g-3 mb-3" id="agent-cards">
                            <?php if ($agents && $agents->num_rows > 0): ?>
                                <?php
                                $first_agent = true;
                                while ($ag = $agents->fetch_assoc()):
                                    $busy_color = $ag['ActiveOrders'] == 0 ? 'success' : ($ag['ActiveOrders'] <= 2 ? 'warning' : 'danger');
                                    $busy_label = $ag['ActiveOrders'] == 0 ? 'Available' : $ag['ActiveOrders'] . ' active order(s)';
                                    $distance = isset($ag['distance_km']) ? round((float)$ag['distance_km'], 1) : null;
                                ?>
                                <div class="col-md-6">
                                    <label class="agent-card d-block border rounded-3 p-3 <?php echo $first_agent ? 'selected' : ''; ?> shadow-sm">
                                        <input type="radio" name="agent_id"
                                               value="<?php echo $ag['DeliveryAgentID']; ?>"
                                               class="d-none agent-radio"
                                               <?php echo $first_agent ? 'checked' : ''; ?>>

                                        <div class="d-flex align-items-center gap-3">
                                            <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0" style="width:44px;height:44px;">
                                                <span class="fw-bold text-primary fs-5">
                                                    <?php echo strtoupper(substr($ag['FullName'], 0, 1)); ?>
                                                </span>
                                            </div>

                                            <div class="flex-grow-1">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <p class="mb-0 fw-bold text-dark fs-6"><?php echo htmlspecialchars($ag['FullName']); ?></p>
                                                    <span class="badge bg-<?php echo $busy_color; ?> ms-1 shadow-sm">
                                                        <?php echo $distance !== null ? ($distance . "km • ") : ""; ?>
                                                        <?php echo $busy_label; ?>
                                                    </span>
                                                </div>
                                                <small class="text-muted d-block fw-semibold mt-1"><i class="bi bi-geo-alt me-1"></i><?php echo htmlspecialchars($ag['Location'] ?? 'Location not set'); ?></small>
                                                <small class="text-muted fw-semibold"><i class="bi bi-telephone me-1"></i><?php echo htmlspecialchars($ag['PhoneNumber']); ?></small>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                                <?php $first_agent = false; endwhile; ?>
                            <?php else: ?>
                                <div class="alert alert-warning mb-0 w-100 shadow-sm fw-semibold">
                                    <i class="bi bi-exclamation-triangle-fill me-2"></i>No delivery agents with GPS location available.
                                </div>
                            <?php endif; ?>
                        </div>

                        <div id="agent-selected-info" class="rounded-3 p-3 border border-primary border-opacity-25 mt-3 <?php echo ($agents && $agents->num_rows > 0) ? '' : 'd-none'; ?>" style="background-color:#f0f5ff;">
                            <small class="text-primary fw-bold text-uppercase d-block mb-1">
                                <i class="bi bi-check-circle-fill me-1"></i>Assigned to:
                            </small>
                            <span id="agent-selected-name" class="fw-bold fs-5 text-dark"></span>
                        </div>
                    </div>
                </div>

                <!-- Products Block -->
                <div class="card border-0 shadow-sm" style="border-radius: 12px; overflow: hidden;">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3 border-bottom">
                        <span class="fw-bold fs-5 text-dark"><i class="bi bi-basket me-2 text-primary"></i>Add Products</span>
                        <button type="button" class="btn btn-sm btn-light border text-primary fw-bold px-3 shadow-sm rounded-pill" id="add-product-row">
                            <i class="bi bi-plus-lg me-1"></i>Add Row
                        </button>
                    </div>

                    <div class="card-body p-4">
                        <div class="row g-2 mb-3 d-none d-md-flex pb-2 border-bottom">
                            <div class="col-md-6"><small class="text-muted fw-bold text-uppercase">Product Selection</small></div>
                            <div class="col-md-2 text-center"><small class="text-muted fw-bold text-uppercase">Qty</small></div>
                            <div class="col-md-2 text-center"><small class="text-muted fw-bold text-uppercase">Subtotal</small></div>
                            <div class="col-md-2"></div>
                        </div>

                        <div id="product-rows">
                            <!-- Rows are added here -->
                        </div>

                        <!-- 
                            FIXED: Added data-image to the <option> tag. 
                            This allows Javascript to grab the DB image URL string!
                        -->
                        <template id="product-options">
                            <option value="">-- Select product --</option>
                            <?php
                            if ($products && $products->num_rows > 0):
                                $products->data_seek(0);
                                while ($p = $products->fetch_assoc()):
                                    $imgStr = trim($p['ImageURL']);
                                    if (empty($imgStr)) {
                                        $finalImg = 'https://via.placeholder.com/150?text=No+Image';
                                    } elseif (preg_match('/^https?:\/\//i', $imgStr)) {
                                        $finalImg = $imgStr;
                                    } else {
                                        $finalImg = '../assets/img/' . $imgStr;
                                    }
                            ?>
                                <option value="<?php echo $p['ProductID']; ?>"
                                        data-price="<?php echo $p['Price']; ?>"
                                        data-stock="<?php echo $p['StockQuantity']; ?>"
                                        data-image="<?php echo htmlspecialchars($finalImg); ?>"
                                        data-name="<?php echo htmlspecialchars($p['ProductName']); ?>">
                                    <?php echo htmlspecialchars($p['ProductName']); ?> (<?php echo $p['Category']; ?>) — Rs. <?php echo number_format($p['Price'], 2); ?> [<?php echo $p['StockQuantity']; ?> left]
                                </option>
                            <?php endwhile; endif; ?>
                        </template>

                    </div>
                </div>

            </div>

            <!-- RIGHT SUMMARY -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm sticky-top" style="top:90px; border-radius: 12px; overflow: hidden;">
                    <div class="card-header bg-white fw-bold py-3 fs-5 border-bottom">
                        <i class="bi bi-receipt-cutoff me-2 text-primary"></i>Order Summary
                    </div>

                    <div class="card-body p-4">
                        <div id="order-summary" class="mb-3">
                            <p class="text-muted small text-center py-4 my-2 fw-semibold">
                                <i class="bi bi-basket-fill d-block fs-1 mb-2 opacity-25"></i>
                                Add products to see summary
                            </p>
                        </div>

                        <hr class="text-muted">

                        <div class="d-flex justify-content-between text-muted small mb-3">
                            <span class="fw-bold">Total Items</span><span id="item-count" class="badge bg-secondary rounded-pill px-3 fs-6">0</span>
                        </div>

                        <div class="d-flex justify-content-between text-muted small mb-3 align-items-center">
                            <span class="fw-bold">Delivery Agent</span>
                            <span id="summary-agent" class="fw-bold text-dark text-end" style="max-width:60%;">—</span>
                        </div>

                        <div class="d-flex justify-content-between text-muted small mb-3">
                            <span class="fw-bold flex-shrink-0 me-2">Deliver To</span>
                            <span id="summary-address" class="fw-bold text-dark text-end" style="max-width:60%;">—</span>
                        </div>

                        <div class="d-flex justify-content-between text-muted small mb-3 align-items-center">
                            <span class="fw-bold">Payment Status</span>
                            <span id="pay-status-preview" class="badge bg-warning text-dark px-3 py-2 rounded-pill shadow-sm">Pending</span>
                        </div>

                        <hr class="text-muted my-4">

                        <div class="d-flex justify-content-between align-items-center fw-bold fs-5 mb-4">
                            <span class="text-dark">Total Amount</span>
                            <span class="text-success fs-3" id="order-total">Rs. 0.00</span>
                        </div>

                        <div class="d-grid">
                            <button type="submit" name="place_order" class="btn btn-primary btn-lg fw-bold shadow-sm" style="border-radius: 8px;">
                                <i class="bi bi-check2-circle me-2"></i>Confirm & Place Order
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </form>
</div>

<!-- Page scripts moved to assets/js/script.js -->
<?php include '../includes/csr-footer.php'; ?>
