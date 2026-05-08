<?php
require_once '../config.php';

// Assuming is_logged_in() is defined in your config.php. If not, use standard check.
if (!is_logged_in()) {
    header("Location: login.php");
    exit();
}

$customer_id = (int)$_SESSION['customer_id'];
$order = null;
$items = null;
$error = '';

// Handle Rating Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_rating'])) {
    $order_id   = (int)$_POST['order_id'];
    $agent_id   = (int)$_POST['agent_id'];
    $score      = (int)$_POST['rating_score'];
    $comment    = clean_input($_POST['comment']);

    // Check if score is valid
    if ($score < 1 || $score > 5) {
        $error = "Please select a rating between 1 and 5.";
    } else {
        // Insert rating
        $sql = "INSERT INTO rating (RatingScore, FeedbackComment, CustomerID, DeliveryAgentID)
                VALUES ($score, '$comment', $customer_id, $agent_id)";
        
        if ($conn->query($sql)) {
            // Mark this specific order as rated in the session so the box hides after submission
            $_SESSION['rated_order_' . $order_id] = true;
            
            set_success_message("Thank you for your rating!");
            header('Location: track-order.php?id=' . $order_id);
            exit();
        } else {
            $error = "Failed to submit rating: " . $conn->error;
        }
    }
}

// Load order
if (isset($_GET['id'])) {
    $order_id = (int)$_GET['id'];
    $order = $conn->query("
        SELECT o.*,
               CONCAT(da.FirstName,' ',da.LastName) as AgentName,
               da.PhoneNumber as AgentPhone,
               da.Location as AgentLocation,
               da.DeliveryAgentID as AgentID
        FROM `Order` o
        LEFT JOIN DeliveryAgent da ON o.DeliveryAgentID = da.DeliveryAgentID
        WHERE o.OrderID = $order_id AND o.CustomerID = $customer_id
    ")->fetch_assoc();

    if ($order) {
        $items = $conn->query("
            SELECT oi.*, p.ProductName, p.ImageURL
            FROM OrderItem oi
            JOIN Product p ON oi.ProductID = p.ProductID
            WHERE oi.OrderID = $order_id
        ");
    }
}

// Get all orders for dropdown
$all_orders = $conn->query("SELECT OrderID, OrderDate, OrderStatus FROM `Order` WHERE CustomerID = $customer_id ORDER BY OrderDate DESC");
?>
<?php $page_title = "Track Order"; include '../includes/customer-header.php'; ?>

<!-- Styles moved to assets/css/style.css -->

<div class="container my-5" style="min-height: 70vh;">

    <h3 class="fw-bold mb-4 text-dark"><i class="bi bi-geo-alt-fill me-2 text-success"></i>Track My Order</h3>

    <!-- Order Selector -->
    <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
        <div class="card-body p-4">
            <form method="GET" action="" class="row g-3 align-items-end">
                <div class="col-md-8">
                    <label class="form-label fw-bold text-muted small text-uppercase mb-2">Select Order to Track</label>
                    <select name="id" class="form-select form-select-lg shadow-sm border-light bg-light fw-semibold">
                        <option value="">-- Choose an order --</option>
                        <?php if ($all_orders): while($o = $all_orders->fetch_assoc()): ?>
                        <option value="<?php echo $o['OrderID']; ?>"
                            <?php echo (isset($_GET['id']) && $_GET['id'] == $o['OrderID']) ? 'selected' : ''; ?>>
                            Order #<?php echo $o['OrderID']; ?> —
                            <?php echo date('d M Y', strtotime($o['OrderDate'])); ?> —
                            [<?php echo $o['OrderStatus']; ?>]
                        </option>
                        <?php endwhile; endif; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-success btn-lg w-100 fw-bold shadow-sm">
                        <i class="bi bi-search me-2"></i>Track Order
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php $msg = get_success_message(); if ($msg): ?>
        <div class="alert alert-success alert-dismissible shadow-sm fade show mb-4">
            <i class="bi bi-check-circle-fill me-2"></i><?php echo htmlspecialchars($msg); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger shadow-sm mb-4"><i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if ($order): ?>

    <!-- Order Status -->
    <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
        <div class="card-body p-4 p-md-5">
            <div class="row align-items-center mb-5 border-bottom pb-4">
                <div class="col">
                    <h4 class="fw-bold mb-1 text-dark">Order #<?php echo (int)$order['OrderID']; ?></h4>
                    <p class="text-muted fw-semibold mb-0">
                        Placed on <?php echo date('d M Y, h:i A', strtotime($order['OrderDate'])); ?>
                    </p>
                </div>
                <div class="col-auto">
                    <?php
                    $colors = ['Pending'=>'warning text-dark','Confirmed'=>'info text-dark','Delivered'=>'success text-white','Canceled'=>'danger text-white'];
                    $c = $colors[$order['OrderStatus']] ?? 'secondary';
                    ?>
                    <span class="badge bg-<?php echo $c; ?> fs-5 px-4 py-2 rounded-pill shadow-sm">
                        <?php echo htmlspecialchars($order['OrderStatus']); ?>
                    </span>
                </div>
            </div>

            <!-- Progress Bar -->
            <?php
            $steps = ['Pending', 'Confirmed', 'Delivered'];
            $current_step = array_search($order['OrderStatus'], $steps);
            if ($current_step === false) $current_step = -1;
            ?>
            <?php if ($order['OrderStatus'] !== 'Canceled'): ?>
            <div class="d-flex justify-content-between align-items-center position-relative">
                <!-- Background Line -->
                <div class="position-absolute w-100" style="height:4px; background:#e9ecef; top: 25px; z-index: 1;"></div>
                
                <!-- Progress Line -->
                <div class="position-absolute" style="height:4px; background:#28a745; top: 25px; z-index: 2; width: <?php echo ($current_step == 0) ? '0%' : (($current_step == 1) ? '50%' : '100%'); ?>; transition: width 0.4s ease;"></div>

                <?php foreach($steps as $i => $step): ?>
                <div class="text-center position-relative" style="z-index: 3;">
                    <div class="rounded-circle d-flex align-items-center justify-content-center mx-auto mb-2 shadow-sm
                        <?php echo $i <= $current_step ? 'bg-success text-white border border-2 border-white' : 'bg-light text-muted border border-2 border-white'; ?>"
                        style="width:55px;height:55px;font-size:1.5rem; transition: all 0.3s;">
                        <?php
                        $icons = ['bi-clock-history','bi-box-seam','bi-house-check'];
                        echo '<i class="bi ' . $icons[$i] . '"></i>';
                        ?>
                    </div>
                    <span class="<?php echo $i <= $current_step ? 'text-success fw-bold' : 'text-muted fw-semibold'; ?> d-block" style="font-size: 0.9rem;">
                        <?php echo $step; ?>
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="text-center text-danger fw-bold fs-5">
                <i class="bi bi-x-circle-fill fs-1 d-block mb-2"></i>
                This order has been canceled.
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="row g-4">
        <!-- Order Items -->
        <div class="col-md-7">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 12px; overflow: hidden;">
                <div class="card-header bg-white fw-bold py-3 fs-5 border-bottom">
                    <i class="bi bi-basket3-fill me-2 text-success"></i>Order Items
                </div>
                <div class="card-body p-0">
                    <?php if ($items && $items->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead class="table-light text-muted small text-uppercase">
                                <tr>
                                    <th class="ps-4 py-3">Product</th>
                                    <th class="py-3 text-center">Qty</th>
                                    <th class="py-3">Price</th>
                                    <th class="pe-4 py-3 text-end">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                while($item = $items->fetch_assoc()): 
                                    // FIXED: Database Image logic identical to Admin/Delivery
                                    $imgStr = trim($item['ImageURL']);
                                    if (empty($imgStr)) {
                                        $finalImg = 'https://via.placeholder.com/150?text=No+Image';
                                    } elseif (preg_match('/^https?:\/\//i', $imgStr)) {
                                        $finalImg = $imgStr;
                                    } else {
                                        $finalImg = '../assets/img/' . $imgStr;
                                    }
                                ?>
                                <tr>
                                    <td class="ps-4 py-3">
                                        <div class="d-flex align-items-center gap-3">
                                            <img src="<?php echo htmlspecialchars($finalImg); ?>"
                                                 onerror="this.src='https://via.placeholder.com/150?text=No+Image'"
                                                 style="width: 48px; height: 48px; object-fit: cover; border-radius: 8px; border: 1px solid #e9ecef; background: #fff;"
                                                 alt="<?php echo htmlspecialchars($item['ProductName']); ?>">
                                            <span class="fw-bold text-dark fs-6"><?php echo htmlspecialchars($item['ProductName']); ?></span>
                                        </div>
                                    </td>
                                    <td class="py-3 text-center fw-semibold text-secondary">x<?php echo (int)$item['Quantity']; ?></td>
                                    <td class="py-3 fw-semibold text-secondary">Rs. <?php echo number_format((float)$item['UnitPrice'], 2); ?></td>
                                    <td class="pe-4 py-3 fw-bold text-success text-end fs-6">Rs. <?php echo number_format($item['Quantity'] * $item['UnitPrice'], 2); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                            <tfoot class="bg-light border-top">
                                <tr>
                                    <td colspan="3" class="fw-bold text-end py-3 text-uppercase text-muted small">Total Paid/Payable:</td>
                                    <td class="pe-4 fw-bold text-success text-end fs-5 py-3">Rs. <?php echo number_format((float)$order['TotalAmount'], 2); ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Delivery Info + Rating -->
        <div class="col-md-5">
            <!-- Delivery Agent Info -->
            <?php if ($order['AgentName']): ?>
            <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px; overflow: hidden;">
                <div class="card-header bg-white fw-bold py-3 fs-5 border-bottom">
                    <i class="bi bi-truck-front-fill me-2 text-success"></i>Delivery Details
                </div>
                <div class="card-body p-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-success bg-opacity-10 text-success rounded-circle d-flex align-items-center justify-content-center"
                            style="width:60px;height:60px;font-size:1.8rem;">
                            <i class="bi bi-person-badge-fill"></i>
                        </div>
                        <div>
                            <small class="text-uppercase fw-bold text-muted" style="font-size: 0.75rem;">Assigned Agent</small>
                            <h5 class="fw-bold mb-1 text-dark"><?php echo htmlspecialchars($order['AgentName']); ?></h5>
                            <a href="tel:<?php echo htmlspecialchars($order['AgentPhone']); ?>" class="text-decoration-none fw-semibold text-success d-inline-block mt-1">
                                <i class="bi bi-telephone-fill me-1"></i><?php echo htmlspecialchars($order['AgentPhone']); ?>
                            </a>
                            <?php if ($order['AgentLocation']): ?>
                            <p class="text-muted small fw-semibold mt-1 mb-0">
                                <i class="bi bi-geo-alt-fill me-1"></i>From: <?php echo htmlspecialchars($order['AgentLocation']); ?>
                            </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Rate Delivery Agent -->
            <?php
            $can_rate = $order['OrderStatus'] == 'Delivered' && $order['AgentID'];
            // Check if THIS SPECIFIC order was already rated in the current session
            $already_rated = isset($_SESSION['rated_order_' . $order['OrderID']]);
            ?>
            
            <?php if ($can_rate && !$already_rated): ?>
            <div class="card border-0 shadow-sm border-warning" style="border-radius: 12px; overflow: hidden;">
                <div class="card-header bg-warning text-dark fw-bold py-3 fs-5">
                    <i class="bi bi-star-fill me-2"></i>Rate Your Delivery
                </div>
                <div class="card-body p-4">
                    <form method="POST" action="">
                        <input type="hidden" name="order_id" value="<?php echo (int)$order['OrderID']; ?>">
                        <input type="hidden" name="agent_id" value="<?php echo (int)$order['AgentID']; ?>">

                        <label class="form-label fw-bold text-muted small text-uppercase mb-2">How did the agent do?</label>
                        <div class="d-flex gap-2 mb-4" id="star-rating-container">
                            <?php for($i=1;$i<=5;$i++): ?>
                            <div class="form-check m-0 p-0">
                                <input class="form-check-input visually-hidden" type="radio"
                                    name="rating_score" id="star<?php echo $i; ?>" value="<?php echo $i; ?>" required>
                                <label class="form-check-label text-warning star-label" for="star<?php echo $i; ?>"
                                    style="cursor:pointer; font-size: 2rem;" title="<?php echo $i; ?> star">
                                    <i class="bi bi-star" data-value="<?php echo $i; ?>"></i>
                                </label>
                            </div>
                            <?php endfor; ?>
                        </div>

                        <label class="form-label fw-bold text-muted small text-uppercase mb-2">Comment <span class="fw-normal text-lowercase">(optional)</span></label>
                        <textarea name="comment" class="form-control form-control-lg border-light bg-light fs-6 fw-semibold mb-4 shadow-sm" rows="3"
                            placeholder="Tell us about your experience..."></textarea>

                        <button type="submit" name="submit_rating" class="btn btn-warning btn-lg w-100 fw-bold shadow-sm">
                            <i class="bi bi-send-fill me-2"></i>Submit Feedback
                        </button>
                    </form>
                </div>
            </div>
            <?php elseif ($already_rated): ?>
            <div class="alert alert-success shadow-sm rounded-3 py-3 px-4 fw-bold">
                <i class="bi bi-check-circle-fill fs-4 d-block mb-2 text-success"></i>
                You have rated this delivery! Thank you for helping us improve.
            </div>
            <?php endif; ?>
        </div>
    </div>

    <?php elseif (isset($_GET['id'])): ?>
    <div class="alert alert-warning shadow-sm rounded-3 py-4 text-center fw-bold">
        <i class="bi bi-exclamation-triangle-fill fs-1 d-block mb-2 text-warning"></i>
        Order not found or does not belong to your account.
    </div>
    <?php endif; ?>

</div>

<!-- Star rating script moved to assets/js/script.js -->

<?php include '../includes/footer.php'; ?>
