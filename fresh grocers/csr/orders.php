<?php
require_once '../config.php';
if (!isset($_SESSION['csr_id'])) {
    header("Location: ../csr/login.php");
    exit();
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id   = (int)$_POST['order_id'];
    $new_status = clean_input($_POST['new_status']);
    // Fixed: `Order` (capital O)
    $conn->query("UPDATE `Order` SET OrderStatus = '$new_status' WHERE OrderID = $order_id");
    set_success_message("Order #$order_id updated to $new_status.");
    header("Location: ../csr/orders.php");
    exit();
}

$status_filter   = isset($_GET['status'])      ? clean_input($_GET['status']) : '';
$customer_filter = isset($_GET['customer_id']) ? (int)$_GET['customer_id']    : 0;
$search          = isset($_GET['search'])      ? clean_input($_GET['search']) : '';

$where = "WHERE 1=1";
if ($status_filter)   $where .= " AND o.OrderStatus = '$status_filter'";
if ($customer_filter) $where .= " AND o.CustomerID = $customer_filter";
if ($search)          $where .= " AND (c.FirstName LIKE '%$search%'
                                   OR c.LastName LIKE '%$search%'
                                   OR o.OrderID LIKE '%$search%')";

// Fixed: `Order` and Customer (capital)
$orders = $conn->query("
    SELECT o.*, CONCAT(c.FirstName,' ',c.LastName) as CustomerName, c.PhoneNumber
    FROM `Order` o
    JOIN Customer c ON o.CustomerID = c.CustomerID
    $where
    ORDER BY o.OrderDate DESC
");
?>
<?php $page_title = "Orders"; include '../includes/csr-header.php'; ?>

<div class="container-fluid py-4 px-4">

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1"><i class="bi bi-bag me-2 text-primary"></i>All Orders</h4>
            <p class="text-muted mb-0 small"><?php echo $orders ? $orders->num_rows : 0; ?> order(s)</p>
        </div>
        <a href="place-order.php" class="btn btn-primary">
            <i class="bi bi-cart-plus me-2"></i>Place New Order
        </a>
    </div>

    <?php $msg = get_success_message(); if ($msg): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle me-2"></i><?php echo $msg; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Filters -->
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body p-3">
            <form method="GET" action="" class="row g-2 align-items-end">
                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control"
                            placeholder="Search customer or order ID..."
                            value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="Pending"   <?php echo $status_filter=='Pending'   ? 'selected':''; ?>>Pending</option>
                        <option value="Confirmed" <?php echo $status_filter=='Confirmed' ? 'selected':''; ?>>Confirmed</option>
                        <option value="Delivered" <?php echo $status_filter=='Delivered' ? 'selected':''; ?>>Delivered</option>
                        <option value="Canceled"  <?php echo $status_filter=='Canceled'  ? 'selected':''; ?>>Canceled</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
                <div class="col-md-2">
                    <a href="orders.php" class="btn btn-outline-secondary w-100">Clear</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Quick Status Tabs -->
    <div class="d-flex gap-2 mb-3 flex-wrap">
        <a href="orders.php"
            class="btn btn-sm <?php echo !$status_filter ? 'btn-primary':'btn-outline-secondary'; ?>">All</a>
        <a href="orders.php?status=Pending"
            class="btn btn-sm <?php echo $status_filter=='Pending'   ? 'btn-warning'         :'btn-outline-warning'; ?>">Pending</a>
        <a href="orders.php?status=Confirmed"
            class="btn btn-sm <?php echo $status_filter=='Confirmed' ? 'btn-info text-white'  :'btn-outline-info'; ?>">Confirmed</a>
        <a href="orders.php?status=Delivered"
            class="btn btn-sm <?php echo $status_filter=='Delivered' ? 'btn-success'          :'btn-outline-success'; ?>">Delivered</a>
        <a href="orders.php?status=Canceled"
            class="btn btn-sm <?php echo $status_filter=='Canceled'  ? 'btn-danger'           :'btn-outline-danger'; ?>">Canceled</a>
    </div>

    <!-- Orders Table -->
    <div class="card border-0 shadow-sm">
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
                            <th class="pe-3">Update Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($orders && $orders->num_rows > 0):
                            while($row = $orders->fetch_assoc()):
                            $colors = ['Pending'=>'warning','Confirmed'=>'info',
                                       'Delivered'=>'success','Canceled'=>'danger'];
                            $c = $colors[$row['OrderStatus']] ?? 'secondary';
                        ?>
                        <tr>
                            <td class="ps-3 fw-semibold">#<?php echo $row['OrderID']; ?></td>
                            <td>
                                <p class="mb-0 small fw-semibold"><?php echo htmlspecialchars($row['CustomerName']); ?></p>
                                <p class="mb-0 text-muted" style="font-size:0.75rem;"><?php echo htmlspecialchars($row['PhoneNumber']); ?></p>
                            </td>
                            <td class="text-muted small"><?php echo date('d M Y', strtotime($row['OrderDate'])); ?></td>
                            <td class="fw-semibold text-success"><?php echo format_price($row['TotalAmount']); ?></td>
                            <td>
                                <span class="badge bg-<?php echo $row['PaymentStatus']=='Paid' ? 'success':'warning'; ?>">
                                    <?php echo $row['PaymentStatus']; ?>
                                </span>
                            </td>
                            <td><span class="badge bg-<?php echo $c; ?>"><?php echo $row['OrderStatus']; ?></span></td>
                            <td class="pe-3">
                                <form method="POST" action="" class="d-flex gap-1">
                                    <input type="hidden" name="order_id" value="<?php echo $row['OrderID']; ?>">
                                    <select name="new_status" class="form-select form-select-sm" style="width:130px;">
                                        <option value="Pending"   <?php echo $row['OrderStatus']=='Pending'   ? 'selected':''; ?>>Pending</option>
                                        <option value="Confirmed" <?php echo $row['OrderStatus']=='Confirmed' ? 'selected':''; ?>>Confirmed</option>
                                        <option value="Delivered" <?php echo $row['OrderStatus']=='Delivered' ? 'selected':''; ?>>Delivered</option>
                                        <option value="Canceled"  <?php echo $row['OrderStatus']=='Canceled'  ? 'selected':''; ?>>Canceled</option>
                                    </select>
                                    <button type="submit" name="update_status" class="btn btn-sm btn-primary">
                                        <i class="bi bi-check-lg"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; else: ?>
                        <tr><td colspan="7" class="text-center py-4 text-muted">No orders found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/csr-footer.php'; ?>
