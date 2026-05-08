<?php
require_once '../config.php';
if (!is_logged_in()) {
    redirect('login.php');
}
$customer_id = (int)$_SESSION['customer_id'];

// Filter by status
$status_filter = isset($_GET['status']) ? clean_input($_GET['status']) : '';
$where = "WHERE o.CustomerID = $customer_id";
if ($status_filter) {
    $where .= " AND o.OrderStatus = '$status_filter'";
}

$orders = $conn->query("
    SELECT o.*, 
           CONCAT(da.FirstName, ' ', da.LastName) as AgentName,
           da.PhoneNumber as AgentPhone
    FROM `order` o
    LEFT JOIN deliveryagent da ON o.DeliveryAgentID = da.DeliveryAgentID
    $where
    ORDER BY o.OrderDate DESC
");
?>
<?php $page_title = "My Orders"; include '../includes/customer-header.php'; ?>

<div class="container my-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-1"><i class="bi bi-bag me-2 text-success"></i>My Orders</h3>
            <p class="text-muted mb-0">Track and manage your orders</p>
        </div>
        <a href="shop.php" class="btn btn-success">
            <i class="bi bi-plus-circle me-1"></i>New Order
        </a>
    </div>

    <!-- Status Filter -->
    <div class="d-flex gap-2 mb-4 flex-wrap">
        <a href="orders.php" class="btn btn-sm <?php echo !$status_filter ? 'btn-success' : 'btn-outline-secondary'; ?>">All</a>
        <a href="orders.php?status=Pending" class="btn btn-sm <?php echo $status_filter=='Pending' ? 'btn-warning' : 'btn-outline-warning'; ?>">Pending</a>
        <a href="orders.php?status=Confirmed" class="btn btn-sm <?php echo $status_filter=='Confirmed' ? 'btn-info' : 'btn-outline-info'; ?>">Confirmed</a>
        <a href="orders.php?status=Delivered" class="btn btn-sm <?php echo $status_filter=='Delivered' ? 'btn-success' : 'btn-outline-success'; ?>">Delivered</a>
        <a href="orders.php?status=Canceled" class="btn btn-sm <?php echo $status_filter=='Canceled' ? 'btn-danger' : 'btn-outline-danger'; ?>">Canceled</a>
    </div>

    <?php if ($orders && $orders->num_rows > 0): ?>
        <?php while($order = $orders->fetch_assoc()): ?>
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-3">
                        <p class="text-muted small mb-1">Order ID</p>
                        <h6 class="fw-bold mb-0">#<?php echo $order['OrderID']; ?></h6>
                        <small class="text-muted"><?php echo date('d M Y, h:i A', strtotime($order['OrderDate'])); ?></small>
                    </div>
                    <div class="col-md-2">
                        <p class="text-muted small mb-1">Total</p>
                        <h6 class="fw-bold text-success mb-0"><?php echo format_price($order['TotalAmount']); ?></h6>
                    </div>
                    <div class="col-md-2">
                        <p class="text-muted small mb-1">Payment</p>
                        <span class="badge bg-<?php echo $order['PaymentStatus']=='Paid' ? 'success' : 'warning'; ?>">
                            <?php echo $order['PaymentStatus']; ?>
                        </span>
                    </div>
                    <div class="col-md-2">
                        <p class="text-muted small mb-1">Status</p>
                        <?php
                        $status_colors = [
                            'Pending'   => 'warning',
                            'Confirmed' => 'info',
                            'Delivered' => 'success',
                            'Canceled'  => 'danger'
                        ];
                        $color = $status_colors[$order['OrderStatus']] ?? 'secondary';
                        ?>
                        <span class="badge bg-<?php echo $color; ?>">
                            <?php echo $order['OrderStatus']; ?>
                        </span>
                    </div>
                    <div class="col-md-3 text-md-end mt-3 mt-md-0">
                        <a href="track-order.php?id=<?php echo $order['OrderID']; ?>"
                            class="btn btn-sm btn-outline-success me-1">
                            <i class="bi bi-geo-alt me-1"></i>Track
                        </a>
                        <?php if ($order['OrderStatus'] == 'Delivered' && $order['DeliveryAgentID']): ?>
                        <a href="track-order.php?id=<?php echo $order['OrderID']; ?>&rate=1"
                            class="btn btn-sm btn-outline-warning">
                            <i class="bi bi-star me-1"></i>Rate
                        </a>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($order['AgentName']): ?>
                <hr class="my-2">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-truck text-success"></i>
                    <small class="text-muted">Delivery Agent:
                        <strong><?php echo htmlspecialchars($order['AgentName']); ?></strong>
                        — <?php echo htmlspecialchars($order['AgentPhone']); ?>
                    </small>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="text-center py-5">
            <i class="bi bi-bag-x display-1 text-muted mb-3"></i>
            <h5 class="text-muted">No orders found</h5>
            <p class="text-muted">You haven't placed any orders yet.</p>
            <a href="shop.php" class="btn btn-success mt-2">Start Shopping</a>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
