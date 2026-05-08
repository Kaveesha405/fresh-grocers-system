<?php
$page_title = "Order Details";
include '../includes/admin-header.php';

// Check if format_price exists so it doesn't crash if config.php includes it
if (!function_exists('format_price')) {
    function format_price($price) {
        return 'Rs. ' . number_format((float)$price, 2);
    }
}

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($order_id <= 0) {
    echo "<script>window.location.href='orders.php';</script>";
    exit;
}

$success_msg = "";
$error_msg = "";

// Handle Form Updates (Change Status, Agent, Payment)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_order'])) {
    $new_status = $_POST['order_status'];
    $new_agent = !empty($_POST['delivery_agent']) ? (int)$_POST['delivery_agent'] : NULL;
    $new_payment = $_POST['payment_status'];
    
    $updStmt = $conn->prepare("UPDATE `Order` SET OrderStatus = ?, DeliveryAgentID = ?, PaymentStatus = ? WHERE OrderID = ?");
    if ($updStmt) {
        $updStmt->bind_param("sisi", $new_status, $new_agent, $new_payment, $order_id);
        if ($updStmt->execute()) {
            $success_msg = "Order successfully updated!";
        } else {
            $error_msg = "Failed to update order.";
        }
        $updStmt->close();
    }
}

// Fetch Main Order Info
$stmt = $conn->prepare("
    SELECT o.*, c.FirstName, c.LastName, c.Email, c.PhoneNumber, c.Address,
           da.FirstName as AgFirst, da.LastName as AgLast, da.PhoneNumber as AgPhone
    FROM `Order` o
    LEFT JOIN Customer c ON o.CustomerID = c.CustomerID
    LEFT JOIN DeliveryAgent da ON o.DeliveryAgentID = da.DeliveryAgentID
    WHERE o.OrderID = ?
");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    echo "<div class='container mt-5'><h3>Order not found.</h3><a href='orders.php' class='btn btn-primary'>Go Back</a></div>";
    include '../includes/admin-footer.php';
    exit;
}

// Fetch Order Items
$itemsStmt = $conn->prepare("
    SELECT oi.*, p.ProductName, p.ImageURL 
    FROM OrderItem oi 
    JOIN Product p ON oi.ProductID = p.ProductID 
    WHERE oi.OrderID = ?
");
$itemsStmt->bind_param("i", $order_id);
$itemsStmt->execute();
$orderItems = $itemsStmt->get_result();
$itemsStmt->close();

// Fetch Delivery Agents for Dropdown
$agents = $conn->query("SELECT DeliveryAgentID, FirstName, LastName FROM DeliveryAgent ORDER BY FirstName ASC");

// Set Badge Colors
$status = strtolower(trim($order['OrderStatus']));
$sc = 'bg-secondary';
if($status == 'pending') $sc = 'bg-warning text-dark';
if($status == 'confirmed' || $status == 'processing') $sc = 'bg-primary';
if($status == 'dispatched' || $status == 'out for delivery') $sc = 'bg-info text-dark';
if($status == 'delivered') $sc = 'bg-success';
if($status == 'canceled' || $status == 'cancelled') $sc = 'bg-danger';

$pc = ($order['PaymentStatus'] == 'Paid') ? 'bg-success' : 'bg-secondary';

// Determine if the order is closed and should be un-editable
$is_closed = ($status === 'delivered' || $status === 'canceled' || $status === 'cancelled');
?>

<div class="container-fluid py-4 px-4 d-flex flex-column" style="min-height: calc(100vh - 120px); background-color: #f8f9fa;">
    
    <?php if ($success_msg): ?>
        <div class="alert alert-success alert-dismissible shadow-sm border-0"><i class="bi bi-check-circle-fill me-2"></i> <?php echo $success_msg; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>
    <?php if ($error_msg): ?>
        <div class="alert alert-danger alert-dismissible shadow-sm border-0"><i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $error_msg; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>

    <!-- Top Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-1">Order #<?php echo $order['OrderID']; ?></h3>
            <span class="text-muted"><i class="bi bi-calendar"></i> <?php echo date('d M Y, h:i A', strtotime($order['OrderDate'])); ?></span>
        </div>
        <div>
            <a href="orders.php" class="btn btn-outline-secondary bg-white shadow-sm"><i class="bi bi-arrow-left me-1"></i> Back to Orders</a>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <!-- Customer & Delivery Info -->
        <div class="col-md-8">
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <h6 class="fw-bold text-muted mb-3 text-uppercase"><i class="bi bi-person me-2"></i>Customer Details</h6>
                            <h5 class="fw-bold text-dark"><?php echo htmlspecialchars(($order['FirstName']??'Guest').' '.($order['LastName']??'')); ?></h5>
                            <p class="mb-1"><i class="bi bi-telephone text-success me-2"></i><?php echo htmlspecialchars($order['PhoneNumber'] ?? 'N/A'); ?></p>
                            <p class="mb-1"><i class="bi bi-envelope text-primary me-2"></i><?php echo htmlspecialchars($order['Email'] ?? 'N/A'); ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <h6 class="fw-bold text-muted mb-3 text-uppercase"><i class="bi bi-geo-alt me-2"></i>Delivery Address</h6>
                            <p class="mb-0 text-dark" style="line-height: 1.6;"><?php echo nl2br(htmlspecialchars($order['Address'] ?? 'No address provided.')); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Order Items Table -->
            <div class="card border-0 shadow-sm mt-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-cart me-2"></i>Order Items</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead class="table-light text-muted">
                                <tr>
                                    <th class="ps-4 py-3">Product</th>
                                    <th class="py-3">Unit Price</th>
                                    <th class="py-3">Quantity</th>
                                    <th class="text-end pe-4 py-3">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $calculated_total = 0;
                                while($item = $orderItems->fetch_assoc()): 
                                    $subtotal = $item['Quantity'] * $item['UnitPrice'];
                                    $calculated_total += $subtotal;
                                ?>
                                <tr>
                                    <td class="ps-4 py-3">
                                        <span class="fw-semibold text-dark"><?php echo htmlspecialchars($item['ProductName']); ?></span>
                                    </td>
                                    <td class="py-3"><?php echo format_price($item['UnitPrice']); ?></td>
                                    <td class="py-3">x<?php echo $item['Quantity']; ?></td>
                                    <td class="text-end pe-4 fw-bold py-3"><?php echo format_price($subtotal); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                            <tfoot class="bg-light">
                                <tr>
                                    <td colspan="3" class="text-end fw-bold py-3 text-muted border-0">Total Amount:</td>
                                    <td class="text-end pe-4 fw-bold fs-5 text-success py-3 border-0"><?php echo format_price($order['TotalAmount']); ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Update Order Status Panel -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="fw-bold mb-0"><i class="bi bi-gear me-2"></i>Order Management</h6>
                </div>
                <div class="card-body">
                    <!-- Current Status summary -->
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-muted fw-bold">Order Status</span>
                        <span class="badge <?php echo $sc; ?> fs-6 rounded-pill shadow-sm px-3"><?php echo htmlspecialchars($order['OrderStatus']); ?></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <span class="text-muted fw-bold">Payment Status</span>
                        <span class="badge <?php echo $pc; ?> fs-6 rounded-pill shadow-sm px-3"><?php echo htmlspecialchars($order['PaymentStatus']); ?></span>
                    </div>

                    <hr class="text-muted opacity-25">

                    <!-- Info Alert if Order is Closed -->
                    <?php if ($is_closed): ?>
                        <div class="alert alert-secondary border-0 small text-center mb-4">
                            <i class="bi bi-info-circle me-1"></i> This order is marked as <strong><?php echo htmlspecialchars($order['OrderStatus']); ?></strong> and cannot be modified.
                        </div>
                    <?php endif; ?>

                    <!-- Update Form -->
                    <form method="POST" action="order-details.php?id=<?php echo $order_id; ?>">
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted text-uppercase">Update Order Status</label>
                            <select name="order_status" class="form-select shadow-sm bg-light" <?php echo $is_closed ? 'disabled' : ''; ?>>
                                <?php
                                $statuses = ['Pending', 'Confirmed', 'Dispatched', 'Delivered', 'Canceled'];
                                foreach ($statuses as $st) {
                                    $sel = (strtolower(trim($order['OrderStatus'])) === strtolower($st)) ? 'selected' : '';
                                    echo "<option value='$st' $sel>$st</option>";
                                }
                                ?>
                            </select>
                            <?php if($is_closed): ?><input type="hidden" name="order_status" value="<?php echo htmlspecialchars($order['OrderStatus']); ?>"><?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted text-uppercase">Payment Status</label>
                            <select name="payment_status" class="form-select shadow-sm bg-light" <?php echo $is_closed ? 'disabled' : ''; ?>>
                                <option value="Pending" <?php echo ($order['PaymentStatus']=='Pending')?'selected':''; ?>>Pending</option>
                                <option value="Paid" <?php echo ($order['PaymentStatus']=='Paid')?'selected':''; ?>>Paid</option>
                                <option value="Failed" <?php echo ($order['PaymentStatus']=='Failed')?'selected':''; ?>>Failed</option>
                            </select>
                            <?php if($is_closed): ?><input type="hidden" name="payment_status" value="<?php echo htmlspecialchars($order['PaymentStatus']); ?>"><?php endif; ?>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold small text-muted text-uppercase">Assign Delivery Agent</label>
                            <select name="delivery_agent" class="form-select shadow-sm bg-light" <?php echo $is_closed ? 'disabled' : ''; ?>>
                                <option value="">-- Unassigned --</option>
                                <?php
                                if ($agents && $agents->num_rows > 0) {
                                    while ($ag = $agents->fetch_assoc()) {
                                        $agName = htmlspecialchars($ag['FirstName'] . ' ' . $ag['LastName']);
                                        $sel = ($order['DeliveryAgentID'] == $ag['DeliveryAgentID']) ? 'selected' : '';
                                        echo "<option value='{$ag['DeliveryAgentID']}' $sel>{$agName}</option>";
                                    }
                                }
                                ?>
                            </select>
                            <?php if($is_closed && $order['DeliveryAgentID']): ?><input type="hidden" name="delivery_agent" value="<?php echo (int)$order['DeliveryAgentID']; ?>"><?php endif; ?>
                        </div>

                        <?php if ($is_closed): ?>
                            <button type="button" class="btn btn-secondary w-100 fw-bold shadow-sm" disabled>
                                <i class="bi bi-lock-fill me-1"></i> Order Closed
                            </button>
                        <?php else: ?>
                            <button type="submit" name="update_order" class="btn btn-success w-100 fw-bold shadow-sm">
                                <i class="bi bi-save me-1"></i> Save Changes
                            </button>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/admin-footer.php'; ?>
