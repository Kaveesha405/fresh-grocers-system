<?php
require_once '../config.php';
if (!isset($_SESSION['agent_id'])) {
    header("Location: ../delivery/login.php");
    exit();
}

$agent_id = (int)$_SESSION['agent_id'];

// SQL: DeliveryAgent table, DeliveryAgentID FK in Order
$my_total     = $conn->query("SELECT COUNT(*) as c FROM `Order` WHERE DeliveryAgentID = $agent_id")->fetch_assoc()['c'];
$my_pending   = $conn->query("SELECT COUNT(*) as c FROM `Order` WHERE DeliveryAgentID = $agent_id AND OrderStatus = 'Confirmed'")->fetch_assoc()['c'];
$my_delivered = $conn->query("SELECT COUNT(*) as c FROM `Order` WHERE DeliveryAgentID = $agent_id AND OrderStatus = 'Delivered'")->fetch_assoc()['c'];
// SQL: Rating table uses RatingScore (not RatingValue), DeliveryAgentID FK
$avg_rating   = $conn->query("SELECT ROUND(AVG(RatingScore),1) as avg FROM Rating WHERE DeliveryAgentID = $agent_id")->fetch_assoc()['avg'];

$recent = $conn->query("
    SELECT o.*, CONCAT(c.FirstName,' ',c.LastName) as CustomerName, c.PhoneNumber
    FROM `Order` o
    JOIN Customer c ON o.CustomerID = c.CustomerID
    WHERE o.DeliveryAgentID = $agent_id
    ORDER BY o.OrderDate DESC LIMIT 6
");
?>
<?php $page_title = "Dashboard"; include '../includes/delivery-header.php'; ?>

<div class="container-fluid py-4 px-4">

    <?php $msg = get_success_message(); if ($msg): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle me-2"></i><?php echo $msg; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1">
                Good <?php echo date('H') < 12 ? 'Morning' : (date('H') < 17 ? 'Afternoon' : 'Evening'); ?>,
                <?php echo htmlspecialchars(explode(' ', $_SESSION['agent_name'])[0]); ?>! 👋
            </h4>
            <p class="text-muted mb-0 small">
                <i class="bi bi-calendar me-1"></i><?php echo date('l, d F Y'); ?>
            </p>
        </div>
        <a href="my-orders.php" class="btn text-white fw-semibold" style="background-color:#fd7e14;">
            <i class="bi bi-bag me-2"></i>View My Orders
        </a>
    </div>

    <!-- Stats -->
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0"
                        style="width:52px;height:52px;background-color:#fff3cd;">
                        <i class="bi bi-bag fs-4" style="color:#fd7e14;"></i>
                    </div>
                    <div>
                        <h4 class="fw-bold mb-0"><?php echo $my_total; ?></h4>
                        <small class="text-muted">Total Assigned</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="bg-warning bg-opacity-10 rounded-3 d-flex align-items-center
                        justify-content-center flex-shrink-0" style="width:52px;height:52px;">
                        <i class="bi bi-clock text-warning fs-4"></i>
                    </div>
                    <div>
                        <h4 class="fw-bold mb-0"><?php echo $my_pending; ?></h4>
                        <small class="text-muted">To Deliver</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="bg-success bg-opacity-10 rounded-3 d-flex align-items-center
                        justify-content-center flex-shrink-0" style="width:52px;height:52px;">
                        <i class="bi bi-check-circle text-success fs-4"></i>
                    </div>
                    <div>
                        <h4 class="fw-bold mb-0"><?php echo $my_delivered; ?></h4>
                        <small class="text-muted">Delivered</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="bg-info bg-opacity-10 rounded-3 d-flex align-items-center
                        justify-content-center flex-shrink-0" style="width:52px;height:52px;">
                        <i class="bi bi-star text-info fs-4"></i>
                    </div>
                    <div>
                        <h4 class="fw-bold mb-0"><?php echo $avg_rating ?? 'N/A'; ?></h4>
                        <small class="text-muted">Avg Rating</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Orders -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
            <span class="fw-semibold">
                <i class="bi bi-clock-history me-2 text-warning"></i>Recent Orders
            </span>
            <a href="my-orders.php" class="btn btn-sm btn-outline-warning">View All</a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Order ID</th>
                            <th>Customer</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th class="pe-3">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($recent && $recent->num_rows > 0):
                            while($row = $recent->fetch_assoc()):
                            $colors = ['Confirmed'=>'warning','Delivered'=>'success','Canceled'=>'danger'];
                            $c = $colors[$row['OrderStatus']] ?? 'secondary';
                        ?>
                        <tr>
                            <td class="ps-3 fw-semibold">#<?php echo $row['OrderID']; ?></td>
                            <td>
                                <p class="mb-0 small fw-semibold"><?php echo htmlspecialchars($row['CustomerName']); ?></p>
                                <p class="mb-0 text-muted" style="font-size:0.75rem;"><?php echo htmlspecialchars($row['PhoneNumber']); ?></p>
                            </td>
                            <td class="fw-semibold text-success"><?php echo format_price($row['TotalAmount']); ?></td>
                            <td><span class="badge bg-<?php echo $c; ?>"><?php echo $row['OrderStatus']; ?></span></td>
                            <td class="pe-3">
                                <a href="my-orders.php?id=<?php echo $row['OrderID']; ?>"
                                    class="btn btn-sm btn-outline-warning">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; else: ?>
                        <tr><td colspan="5" class="text-center py-4 text-muted">No orders assigned yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/delivery-footer.php'; ?>
