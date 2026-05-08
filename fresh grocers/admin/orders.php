<?php
$page_title = "Manage Orders";
include '../includes/admin-header.php';

// Handle Order Deletion (If triggered)
$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_order'])) {
    $delete_id = (int)$_POST['order_id'];
    
    // Deleting the order will cascade to OrderItems/Payments based on your SQL schema
    $delStmt = $conn->prepare("DELETE FROM `Order` WHERE OrderID = ?");
    if ($delStmt) {
        $delStmt->bind_param("i", $delete_id);
        if ($delStmt->execute()) {
            $success_msg = "Order #{$delete_id} has been successfully deleted.";
        } else {
            $error_msg = "Failed to delete order. It may be tied to other records.";
        }
        $delStmt->close();
    }
}

// Fetch Orders
$customer_filter = isset($_GET['customer_id']) ? (int)$_GET['customer_id'] : 0;
$query = "
    SELECT o.*, c.FirstName, c.LastName, da.FirstName as AgFirst, da.LastName as AgLast
    FROM `Order` o
    LEFT JOIN Customer c ON o.CustomerID = c.CustomerID
    LEFT JOIN DeliveryAgent da ON o.DeliveryAgentID = da.DeliveryAgentID
";

if ($customer_filter > 0) {
    $query .= " WHERE o.CustomerID = " . $customer_filter;
}
$query .= " ORDER BY o.OrderDate DESC";

$orders = $conn->query($query);
?>

<!-- Styles moved to assets/css/style.css -->

<div class="dashboard-bg">
    <div class="container-fluid py-4 px-4 d-flex flex-column flex-grow-1">
        
        <?php if ($success_msg): ?>
            <div class="alert alert-success alert-dismissible fade show shadow-sm border-0" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i> <?php echo $success_msg; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <?php if ($error_msg): ?>
            <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $error_msg; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-2 border-bottom">
            <div class="mb-3 mb-md-0">
                <h3 class="fw-bold mb-1 text-dark"><i class="bi bi-receipt-cutoff me-2 text-success"></i>Manage Orders</h3>
                <p class="text-muted small mb-0">
                    <?php if($customer_filter > 0): ?>
                        Viewing orders for Customer #<?php echo $customer_filter; ?>. <a href="orders.php" class="text-primary text-decoration-none">View All</a>
                    <?php else: ?>
                        View and track all customer orders across the store.
                    <?php endif; ?>
                </p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-white border shadow-sm fw-medium text-secondary bg-white" onclick="window.location.reload();">
                    <i class="bi bi-arrow-clockwise me-1"></i> Refresh
                </button>
            </div>
        </div>

        <div class="table-container d-flex flex-column">
            <div class="table-responsive flex-grow-1">
                <table class="table table-hover mb-0 align-middle">
                    <thead>
                        <tr>
                            <th class="ps-4">Order ID</th>
                            <th>Customer</th>
                            <th>Date & Time</th>
                            <th>Amount</th>
                            <th>Driver</th>
                            <th>Status</th>
                            <th>Payment</th>
                            <th class="pe-4 text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($orders && $orders->num_rows > 0): ?>
                            <?php while($o = $orders->fetch_assoc()): ?>
                            <?php $oid = $o['OrderID']; ?>
                            <tr>
                                <td class="ps-4"><span class="fw-bold text-dark fs-6">#<?php echo $oid; ?></span></td>
                                
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="bg-success bg-opacity-10 text-success rounded-circle d-flex align-items-center justify-content-center me-2" style="width:35px; height:35px;">
                                            <i class="bi bi-person-fill fs-6"></i>
                                        </div>
                                        <span class="fw-semibold text-dark"><?php echo htmlspecialchars(($o['FirstName']??'Guest').' '.($o['LastName']??'')); ?></span>
                                    </div>
                                </td>
                                
                                <td>
                                    <div class="text-dark fw-medium small"><i class="bi bi-calendar-event text-muted me-1"></i><?php echo date('d M Y', strtotime($o['OrderDate'])); ?></div>
                                    <div class="text-muted small mt-1"><i class="bi bi-clock me-1"></i><?php echo date('h:i A', strtotime($o['OrderDate'])); ?></div>
                                </td>
                                
                                <td><span class="fw-bold text-success fs-6"><?php echo format_price($o['TotalAmount']); ?></span></td>
                                
                                <td>
                                    <?php if ($o['AgFirst']): ?>
                                        <span class="agent-badge text-truncate d-inline-block" style="max-width: 130px;" title="<?php echo htmlspecialchars($o['AgFirst'].' '.$o['AgLast']); ?>">
                                            <i class="bi bi-truck text-info me-1"></i> <?php echo htmlspecialchars($o['AgFirst'].' '.$o['AgLast']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-warning border px-2 py-1 rounded-pill">
                                            <i class="bi bi-exclamation-circle-fill me-1"></i> Unassigned
                                        </span>
                                    <?php endif; ?>
                                </td>
                                
                                <td>
                                    <?php 
                                        $status = trim($o['OrderStatus']);
                                        $status_class = 'bg-secondary text-white';
                                        $icon = 'bi-circle-fill';
                                        switch(strtolower($status)) {
                                            case 'pending': $status_class = 'bg-warning text-dark'; $icon = 'bi-hourglass-split'; break;
                                            case 'confirmed': 
                                            case 'processing': $status_class = 'bg-primary text-white'; $icon = 'bi-box-seam'; break;
                                            case 'dispatched': 
                                            case 'out for delivery': $status_class = 'bg-info text-dark'; $icon = 'bi-truck'; break;
                                            case 'delivered': 
                                            case 'completed': $status_class = 'bg-success text-white'; $icon = 'bi-check-circle-fill'; break;
                                            case 'canceled': 
                                            case 'cancelled': $status_class = 'bg-danger text-white'; $icon = 'bi-x-circle-fill'; break;
                                        }
                                    ?>
                                    <span class="status-badge <?php echo $status_class; ?> shadow-sm">
                                        <i class="bi <?php echo $icon; ?>"></i> <?php echo htmlspecialchars($status); ?>
                                    </span>
                                </td>
                                
                                <td>
                                    <?php $pb = ($o['PaymentStatus']=='Paid') ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-secondary-subtle text-secondary border'; ?>
                                    <span class="badge <?php echo $pb; ?> px-2 py-1 rounded-2"><?php echo htmlspecialchars($o['PaymentStatus']); ?></span>
                                </td>
                                
                                <td class="pe-4 text-end">
                                    <div class="btn-group btn-group-sm shadow-sm" role="group">
                                        <a href="order-details.php?id=<?php echo $oid; ?>" class="btn btn-light text-primary border" title="View Details">
                                            <i class="bi bi-eye-fill"></i> View
                                        </a>
                                        <button type="button" class="btn btn-light text-danger border" data-bs-toggle="modal" data-bs-target="#deleteOrder<?php echo $oid; ?>" title="Delete Order">
                                            <i class="bi bi-trash-fill"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <div class="modal fade" id="deleteOrder<?php echo $oid; ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content border-0 shadow">
                                        <div class="modal-header bg-danger text-white border-0">
                                            <h5 class="modal-title fw-bold"><i class="bi bi-exclamation-octagon-fill me-2"></i>Delete Order</h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body p-4 text-center">
                                            <div class="mb-3"><i class="bi bi-trash3 text-danger opacity-75" style="font-size: 3.5rem;"></i></div>
                                            <h5 class="fw-bold text-dark">Delete Order #<?php echo $oid; ?>?</h5>
                                            <p class="text-muted mb-1">This will permanently delete this order and remove its items from the database.</p>
                                        </div>
                                        <div class="modal-footer border-0 bg-light justify-content-center">
                                            <button type="button" class="btn btn-light border px-4" data-bs-dismiss="modal">Cancel</button>
                                            <form method="POST" action="orders.php<?php echo $customer_filter > 0 ? '?customer_id='.$customer_filter : ''; ?>">
                                                <input type="hidden" name="order_id" value="<?php echo $oid; ?>">
                                                <button type="submit" name="delete_order" class="btn btn-danger px-4">Yes, Delete</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 70px; height: 70px;">
                                        <i class="bi bi-inbox fs-1 text-secondary opacity-50"></i>
                                    </div>
                                    <h5 class="fw-bold text-dark">No orders found</h5>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/admin-footer.php'; ?>
