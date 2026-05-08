<?php
require_once '../config.php';
if (!isset($_SESSION['agent_id'])) {
    header("Location: ../delivery/login.php");
    exit();
}

$agent_id = (int)$_SESSION['agent_id'];

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id   = (int)$_POST['order_id'];
    $new_status = clean_input($_POST['new_status']);
    
    if ($new_status === 'Delivered') {
        // If delivered, set DeliveryDate to NOW and mark payment as Paid in Order table
        $conn->query("UPDATE `Order` SET OrderStatus = '$new_status', DeliveryDate = NOW(), PaymentStatus = 'Paid' WHERE OrderID = $order_id AND DeliveryAgentID = $agent_id");
        
        // Also mark it as Paid in the Payment table
        $conn->query("UPDATE `Payment` SET PaymentStatus = 'Paid' WHERE OrderID = $order_id");
    } else {
        // Just update status
        $conn->query("UPDATE `Order` SET OrderStatus = '$new_status' WHERE OrderID = $order_id AND DeliveryAgentID = $agent_id");
    }
    
    set_success_message("Order #$order_id updated to $new_status.");
    header("Location: ../delivery/my-orders.php");
    exit();
}

$status_filter = isset($_GET['status']) ? clean_input($_GET['status']) : '';
$view_id       = isset($_GET['id'])     ? (int)$_GET['id']             : 0;

$order_detail = null;
$order_items  = null;

if ($view_id) {
    $order_detail = $conn->query("
        SELECT o.*, CONCAT(c.FirstName,' ',c.LastName) as CustomerName,
               c.PhoneNumber, c.Email as CustomerEmail
        FROM `Order` o
        JOIN Customer c ON o.CustomerID = c.CustomerID
        WHERE o.OrderID = $view_id AND o.DeliveryAgentID = $agent_id
    ")->fetch_assoc();

    if ($order_detail) {
        // SQL: OrderItem table - Fetching ImageURL directly from database
        $order_items = $conn->query("
            SELECT oi.*, p.ProductName, p.ImageURL
            FROM OrderItem oi
            JOIN Product p ON oi.ProductID = p.ProductID
            WHERE oi.OrderID = $view_id
        ");
    }
}

$where = "WHERE o.DeliveryAgentID = $agent_id";
if ($status_filter) $where .= " AND o.OrderStatus = '$status_filter'";

$orders = $conn->query("
    SELECT o.*, CONCAT(c.FirstName,' ',c.LastName) as CustomerName, c.PhoneNumber
    FROM `Order` o
    JOIN Customer c ON o.CustomerID = c.CustomerID
    $where
    ORDER BY o.OrderDate DESC
");
?>
<?php $page_title = "My Orders"; include '../includes/delivery-header.php'; ?>

<div class="container-fluid py-4 px-4" style="background-color: #f4f6f9; min-height: 85vh;">

    <?php $msg = get_success_message(); if ($msg): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm">
            <i class="bi bi-check-circle-fill me-2"></i><?php echo htmlspecialchars($msg); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($order_detail): ?>
    <!-- Single Order Detail -->
    <div class="d-flex align-items-center gap-2 mb-4">
        <a href="my-orders.php" class="btn btn-sm btn-light border shadow-sm fw-bold text-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back
        </a>
        <h4 class="fw-bold mb-0 text-dark ms-2">Order #<?php echo (int)$order_detail['OrderID']; ?></h4>
        <?php
        $colors = ['Confirmed'=>'warning','Delivered'=>'success','Canceled'=>'danger'];
        $c = $colors[$order_detail['OrderStatus']] ?? 'secondary';
        ?>
        <span class="badge bg-<?php echo $c; ?> fs-6 shadow-sm ms-2"><?php echo htmlspecialchars($order_detail['OrderStatus']); ?></span>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px; overflow: hidden;">
                <div class="card-header bg-white fw-bold py-3 fs-5 border-bottom">
                    <i class="bi bi-bag-check me-2 text-warning"></i>Order Items
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table mb-0 align-middle table-hover">
                            <thead class="table-light text-muted small text-uppercase">
                                <tr>
                                    <th class="ps-4 py-3">Product</th>
                                    <th class="py-3">Qty</th>
                                    <th class="py-3">Unit Price</th>
                                    <th class="pe-4 py-3 text-end">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                while($item = $order_items->fetch_assoc()): 
                                    // FIXED: Determine if URL is a web link or local filename from the database
                                    $imgStr = trim($item['ImageURL']);
                                    if (empty($imgStr)) {
                                        $finalImg = 'https://via.placeholder.com/150?text=No+Image';
                                    } elseif (preg_match('/^https?:\/\//i', $imgStr)) {
                                        // It's a full web URL
                                        $finalImg = $imgStr;
                                    } else {
                                        // It's a local file name (e.g. 'potatoes.jpg'), route to assets/img
                                        $finalImg = '../assets/img/' . $imgStr;
                                    }
                                ?>
                                <tr>
                                    <td class="ps-4 py-3">
                                        <div class="d-flex align-items-center gap-3">
                                            <img src="<?php echo htmlspecialchars($finalImg); ?>"
                                                 onerror="this.src='https://via.placeholder.com/150?text=No+Image'"
                                                 style="width: 50px; height: 50px; object-fit: cover; border-radius: 8px; border: 1px solid #e9ecef; background: #fff;"
                                                 alt="<?php echo htmlspecialchars($item['ProductName']); ?>">
                                            <span class="fw-bold text-dark fs-6"><?php echo htmlspecialchars($item['ProductName']); ?></span>
                                        </div>
                                    </td>
                                    <td class="py-3 fw-semibold text-secondary">x<?php echo (int)$item['Quantity']; ?></td>
                                    <td class="py-3 fw-semibold text-secondary">Rs. <?php echo number_format((float)$item['UnitPrice'], 2); ?></td>
                                    <td class="pe-4 py-3 fw-bold text-success text-end fs-6">
                                        Rs. <?php echo number_format((float)$item['UnitPrice'] * (int)$item['Quantity'], 2); ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm" style="border-radius: 12px; overflow: hidden;">
                <div class="card-header bg-white fw-bold py-3 fs-5 border-bottom">
                    <i class="bi bi-person-lines-fill me-2 text-warning"></i>Customer Details
                </div>
                <div class="card-body p-4">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="text-muted small text-uppercase fw-bold mb-1">Customer Name</label>
                            <div class="fs-6 fw-semibold text-dark"><?php echo htmlspecialchars($order_detail['CustomerName']); ?></div>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small text-uppercase fw-bold mb-1">Phone Number</label>
                            <div>
                                <a href="tel:<?php echo htmlspecialchars($order_detail['PhoneNumber']); ?>" class="fw-bold text-success text-decoration-none fs-6">
                                    <i class="bi bi-telephone-fill me-2"></i><?php echo htmlspecialchars($order_detail['PhoneNumber']); ?>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px; overflow: hidden;">
                <div class="card-header bg-white fw-bold py-3 fs-5 border-bottom">
                    <i class="bi bi-receipt-cutoff me-2 text-warning"></i>Order Summary
                </div>
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between mb-3">
                        <span class="text-muted fw-semibold">Order Date</span>
                        <span class="fw-bold text-dark"><?php echo date('d M Y, h:i A', strtotime($order_detail['OrderDate'])); ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-3 align-items-center">
                        <span class="text-muted fw-semibold">Payment Status</span>
                        <span class="badge bg-<?php echo $order_detail['PaymentStatus']=='Paid' ? 'success':'warning'; ?> px-3 py-2 rounded-pill shadow-sm">
                            <?php echo htmlspecialchars($order_detail['PaymentStatus']); ?>
                        </span>
                    </div>
                    <hr class="my-4 text-muted">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-bold text-dark fs-5">Total Amount</span>
                        <span class="fw-bold text-success fs-4">Rs. <?php echo number_format((float)$order_detail['TotalAmount'], 2); ?></span>
                    </div>
                </div>
            </div>

            <?php if ($order_detail['OrderStatus'] !== 'Delivered' && $order_detail['OrderStatus'] !== 'Canceled'): ?>
            <div class="card border-0 shadow-sm" style="border-radius: 12px; overflow: hidden;">
                <div class="card-header bg-white fw-bold py-3 fs-5 border-bottom">
                    <i class="bi bi-arrow-repeat me-2 text-warning"></i>Update Delivery Status
                </div>
                <div class="card-body p-4">
                    <form method="POST" action="">
                        <input type="hidden" name="order_id" value="<?php echo (int)$order_detail['OrderID']; ?>">
                        <label class="form-label text-muted small text-uppercase fw-bold mb-2">Change Status</label>
                        <select name="new_status" class="form-select form-select-lg mb-4 fw-semibold border-secondary shadow-sm">
                            <option value="Confirmed"  <?php echo $order_detail['OrderStatus']=='Confirmed'  ? 'selected':''; ?>>🚛 On the Way (Confirmed)</option>
                            <option value="Delivered"  <?php echo $order_detail['OrderStatus']=='Delivered'  ? 'selected':''; ?>>✅ Mark as Delivered</option>
                            <option value="Canceled"   <?php echo $order_detail['OrderStatus']=='Canceled'   ? 'selected':''; ?>>❌ Cancel Order</option>
                        </select>
                        <div class="d-grid">
                            <button type="submit" name="update_status" class="btn btn-lg text-white fw-bold shadow-sm" style="background-color: #fd7e14; border: none; border-radius: 8px;">
                                <i class="bi bi-check2-circle me-2"></i>Save Status
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <?php else: ?>
    <!-- Order List -->
    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
            <h3 class="fw-bold mb-1 text-dark"><i class="bi bi-truck me-2 text-warning"></i>Assigned Deliveries</h3>
            <p class="text-muted fw-semibold mb-0 small">You have <?php echo $orders ? $orders->num_rows : 0; ?> order(s) assigned to you.</p>
        </div>
    </div>

    <div class="d-flex gap-2 mb-4 flex-wrap">
        <a href="my-orders.php"
            class="btn fw-bold px-4 rounded-pill shadow-sm <?php echo !$status_filter ? 'btn-warning text-dark':'btn-white border text-secondary'; ?>">All</a>
        <a href="my-orders.php?status=Confirmed"
            class="btn fw-bold px-4 rounded-pill shadow-sm <?php echo $status_filter=='Confirmed' ? 'btn-warning text-dark':'btn-white border text-secondary'; ?>">Confirmed</a>
        <a href="my-orders.php?status=Delivered"
            class="btn fw-bold px-4 rounded-pill shadow-sm <?php echo $status_filter=='Delivered' ? 'btn-success text-white':'btn-white border text-secondary'; ?>">Delivered</a>
        <a href="my-orders.php?status=Canceled"
            class="btn fw-bold px-4 rounded-pill shadow-sm <?php echo $status_filter=='Canceled'  ? 'btn-danger text-white':'btn-white border text-secondary'; ?>">Canceled</a>
    </div>

    <div class="card border-0 shadow-sm" style="border-radius: 16px; overflow: hidden;">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light text-muted small text-uppercase">
                        <tr>
                            <th class="ps-4 py-3">Order ID</th>
                            <th class="py-3">Customer Info</th>
                            <th class="py-3">Date</th>
                            <th class="py-3">Total Amount</th>
                            <th class="py-3">Status</th>
                            <th class="pe-4 py-3 text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($orders && $orders->num_rows > 0):
                            while($row = $orders->fetch_assoc()):
                            $colors = ['Confirmed'=>'warning','Delivered'=>'success','Canceled'=>'danger'];
                            $c = $colors[$row['OrderStatus']] ?? 'secondary';
                        ?>
                        <tr>
                            <td class="ps-4 py-3 fw-bold text-dark fs-5">#<?php echo (int)$row['OrderID']; ?></td>
                            <td class="py-3">
                                <p class="mb-1 fw-bold text-dark fs-6"><?php echo htmlspecialchars($row['CustomerName']); ?></p>
                                <p class="mb-0 fw-semibold text-success small"><i class="bi bi-telephone me-1"></i><?php echo htmlspecialchars($row['PhoneNumber']); ?></p>
                            </td>
                            <td class="py-3 text-secondary fw-semibold small"><?php echo date('d M Y', strtotime($row['OrderDate'])); ?></td>
                            <td class="py-3 fw-bold text-success fs-6">Rs. <?php echo number_format((float)$row['TotalAmount'], 2); ?></td>
                            <td class="py-3">
                                <span class="badge bg-<?php echo $c; ?> shadow-sm px-3 py-2 rounded-pill fs-6"><?php echo htmlspecialchars($row['OrderStatus']); ?></span>
                            </td>
                            <td class="pe-4 py-3 text-end">
                                <a href="my-orders.php?id=<?php echo (int)$row['OrderID']; ?>"
                                    class="btn btn-sm btn-light border text-warning fw-bold px-3 py-2 rounded-pill shadow-sm">
                                    <i class="bi bi-eye-fill me-1"></i> View Details
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                                    <i class="bi bi-truck fs-1 text-secondary opacity-50"></i>
                                </div>
                                <h5 class="fw-bold text-dark">No orders found</h5>
                                <p class="text-muted mb-0">There are no deliveries matching your filter.</p>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include '../includes/delivery-footer.php'; ?>
