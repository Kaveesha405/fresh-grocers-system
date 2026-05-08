<?php
require_once '../config.php';
if (!isset($_SESSION['csr_id'])) {
    header("Location: ../csr/login.php");
    exit();
}

$total_customers = $conn->query("SELECT COUNT(*) as c FROM Customer")->fetch_assoc()['c'];
$total_orders    = $conn->query("SELECT COUNT(*) as c FROM `Order`")->fetch_assoc()['c'];
$pending_orders  = $conn->query("SELECT COUNT(*) as c FROM `Order` WHERE OrderStatus='Pending'")->fetch_assoc()['c'];
$today_orders    = $conn->query("SELECT COUNT(*) as c FROM `Order` WHERE DATE(OrderDate)=CURDATE()")->fetch_assoc()['c'];

$recent = $conn->query("
    SELECT o.*, CONCAT(c.FirstName,' ',c.LastName) as CustomerName
    FROM `Order` o
    JOIN Customer c ON o.CustomerID = c.CustomerID
    ORDER BY o.OrderDate DESC LIMIT 8
");
?>
<?php $page_title = "Dashboard"; include '../includes/csr-header.php'; ?>

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
                <?php echo htmlspecialchars(explode(' ', $_SESSION['csr_name'])[0]); ?>! 👋
            </h4>
            <p class="text-muted mb-0 small">
                <i class="bi bi-calendar me-1"></i><?php echo date('l, d F Y'); ?>
            </p>
        </div>
        <a href="place-order.php" class="btn btn-primary">
            <i class="bi bi-cart-plus me-2"></i>Place New Order
        </a>
    </div>

    <!-- Stats -->
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="bg-primary bg-opacity-10 rounded-3 d-flex align-items-center
                        justify-content-center flex-shrink-0" style="width:52px;height:52px;">
                        <i class="bi bi-people text-primary fs-4"></i>
                    </div>
                    <div>
                        <h4 class="fw-bold mb-0"><?php echo $total_customers; ?></h4>
                        <small class="text-muted">Total Customers</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="bg-success bg-opacity-10 rounded-3 d-flex align-items-center
                        justify-content-center flex-shrink-0" style="width:52px;height:52px;">
                        <i class="bi bi-bag-check text-success fs-4"></i>
                    </div>
                    <div>
                        <h4 class="fw-bold mb-0"><?php echo $total_orders; ?></h4>
                        <small class="text-muted">Total Orders</small>
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
                        <h4 class="fw-bold mb-0"><?php echo $pending_orders; ?></h4>
                        <small class="text-muted">Pending Orders</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="bg-info bg-opacity-10 rounded-3 d-flex align-items-center
                        justify-content-center flex-shrink-0" style="width:52px;height:52px;">
                        <i class="bi bi-calendar-check text-info fs-4"></i>
                    </div>
                    <div>
                        <h4 class="fw-bold mb-0"><?php echo $today_orders; ?></h4>
                        <small class="text-muted">Today's Orders</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Orders -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
            <span class="fw-semibold">
                <i class="bi bi-clock-history me-2 text-primary"></i>Recent Orders
            </span>
            <a href="orders.php" class="btn btn-sm btn-outline-primary">View All</a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Order ID</th>
                            <th>Customer</th>
                            <th>Date</th>
                            <th>Total</th>
                            <th>Payment</th>
                            <th>Status</th>
                            <th class="pe-3">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($recent && $recent->num_rows > 0):
                            while($row = $recent->fetch_assoc()):
                            $colors = ['Pending'=>'warning','Confirmed'=>'info',
                                       'Delivered'=>'success','Canceled'=>'danger'];
                            $c = $colors[$row['OrderStatus']] ?? 'secondary';
                        ?>
                        <tr>
                            <td class="ps-3 fw-semibold">#<?php echo $row['OrderID']; ?></td>
                            <td><?php echo htmlspecialchars($row['CustomerName']); ?></td>
                            <td class="text-muted small"><?php echo date('d M Y', strtotime($row['OrderDate'])); ?></td>
                            <td class="fw-semibold text-success"><?php echo format_price($row['TotalAmount']); ?></td>
                            <td>
                                <span class="badge bg-<?php echo $row['PaymentStatus']=='Paid' ? 'success':'warning'; ?>">
                                    <?php echo $row['PaymentStatus']; ?>
                                </span>
                            </td>
                            <td><span class="badge bg-<?php echo $c; ?>"><?php echo $row['OrderStatus']; ?></span></td>
                            <td class="pe-3">
                                <a href="orders.php?id=<?php echo $row['OrderID']; ?>"
                                    class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; else: ?>
                        <tr><td colspan="7" class="text-center py-4 text-muted">No orders yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/csr-footer.php'; ?>
