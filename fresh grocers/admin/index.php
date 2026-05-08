<?php
$page_title = "Dashboard Overview";
include '../includes/admin-header.php';

// Quick DB Stats
$stats = [
    'orders' => $conn->query("SELECT COUNT(*) FROM `Order`")->fetch_row()[0] ?? 0,
    'revenue' => $conn->query("SELECT SUM(TotalAmount) FROM `Order` WHERE PaymentStatus='Paid'")->fetch_row()[0] ?? 0,
    'customers' => $conn->query("SELECT COUNT(*) FROM Customer")->fetch_row()[0] ?? 0,
    'agents' => $conn->query("SELECT COUNT(*) FROM DeliveryAgent WHERE IsActive=1")->fetch_row()[0] ?? 0
];

// Fetch Recent Orders (Limit 6)
$recent_orders = $conn->query("
    SELECT o.OrderID, o.OrderDate, o.TotalAmount, o.OrderStatus, o.PaymentStatus, 
           CONCAT(IFNULL(c.FirstName,'Guest'), ' ', IFNULL(c.LastName,'')) AS CustomerName 
    FROM `Order` o 
    LEFT JOIN Customer c ON o.CustomerID = c.CustomerID 
    ORDER BY o.OrderDate DESC LIMIT 6
");
?>

<!-- Styles moved to assets/css/style.css -->

<div class="dashboard-bg">
    <div class="container-fluid py-4 px-4">
        
        <!-- Welcome Header -->
        <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
            <div>
                <h3 class="fw-bold mb-1 text-dark"><i class="bi bi-grid-1x2-fill me-2 text-success"></i>Admin Dashboard</h3>
                <p class="text-muted small mb-0 fw-semibold">Overview of Fresh Grocers operations.</p>
            </div>
            <div class="text-end bg-white px-3 py-2 rounded-3 shadow-sm border">
                <i class="bi bi-calendar3 text-success me-2"></i> 
                <span class="fw-bold text-secondary"><?php echo date('l, d F Y'); ?></span>
            </div>
        </div>
        
        <!-- Statistics Row -->
        <div class="row g-4 mb-5">
            <!-- Revenue Stat -->
            <div class="col-xl-3 col-md-6">
                <div class="card stat-card shadow-sm h-100">
                    <div class="card-body p-4 d-flex align-items-center">
                        <div class="icon-box bg-light-success me-3 shadow-sm">
                            <i class="bi bi-cash-stack"></i>
                        </div>
                        <div>
                            <h6 class="text-muted text-uppercase fw-bold mb-1" style="font-size: 0.75rem;">Total Revenue</h6>
                            <h3 class="fw-bold mb-0 text-dark">Rs. <?php echo number_format((float)$stats['revenue'], 2); ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Orders Stat -->
            <div class="col-xl-3 col-md-6">
                <div class="card stat-card shadow-sm h-100">
                    <div class="card-body p-4 d-flex align-items-center">
                        <div class="icon-box bg-light-primary me-3 shadow-sm">
                            <i class="bi bi-bag-check-fill"></i>
                        </div>
                        <div>
                            <h6 class="text-muted text-uppercase fw-bold mb-1" style="font-size: 0.75rem;">Total Orders</h6>
                            <h3 class="fw-bold mb-0 text-dark"><?php echo number_format($stats['orders']); ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Customers Stat -->
            <div class="col-xl-3 col-md-6">
                <div class="card stat-card shadow-sm h-100">
                    <div class="card-body p-4 d-flex align-items-center">
                        <div class="icon-box bg-light-warning me-3 shadow-sm">
                            <i class="bi bi-people-fill"></i>
                        </div>
                        <div>
                            <h6 class="text-muted text-uppercase fw-bold mb-1" style="font-size: 0.75rem;">Customers</h6>
                            <h3 class="fw-bold mb-0 text-dark"><?php echo number_format($stats['customers']); ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Agents Stat -->
            <div class="col-xl-3 col-md-6">
                <div class="card stat-card shadow-sm h-100">
                    <div class="card-body p-4 d-flex align-items-center">
                        <div class="icon-box bg-light-info me-3 shadow-sm">
                            <i class="bi bi-truck-front-fill"></i>
                        </div>
                        <div>
                            <h6 class="text-muted text-uppercase fw-bold mb-1" style="font-size: 0.75rem;">Active Drivers</h6>
                            <h3 class="fw-bold mb-0 text-dark"><?php echo number_format($stats['agents']); ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Orders Section -->
        <div class="d-flex justify-content-between align-items-center mb-3 mt-4">
            <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-clock-history me-2 text-primary"></i>Recent Transactions</h5>
            <a href="orders.php" class="btn btn-primary fw-semibold rounded-pill px-4 shadow-sm">
                View All Orders <i class="bi bi-arrow-right ms-1"></i>
            </a>
        </div>

        <div class="table-container border-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 bg-white">
                    <thead class="bg-light text-muted">
                        <tr>
                            <th class="ps-4 py-3 fw-bold">Order ID</th>
                            <th class="py-3 fw-bold">Customer</th>
                            <th class="py-3 fw-bold">Date & Time</th>
                            <th class="py-3 fw-bold">Total Amount</th>
                            <th class="py-3 fw-bold">Order Status</th>
                            <th class="py-3 fw-bold">Payment</th>
                            <th class="pe-4 py-3 text-end fw-bold">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($recent_orders && $recent_orders->num_rows > 0): while($row = $recent_orders->fetch_assoc()): ?>
                        <tr>
                            <td class="ps-4 fw-bold text-dark fs-6">#<?php echo $row['OrderID']; ?></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="bg-secondary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center me-3" style="width:38px; height:38px;">
                                        <i class="bi bi-person-fill text-secondary fs-5"></i>
                                    </div>
                                    <span class="fw-bold text-dark"><?php echo htmlspecialchars(trim($row['CustomerName'])); ?></span>
                                </div>
                            </td>
                            <td>
                                <div class="text-dark fw-semibold small"><i class="bi bi-calendar-event text-muted me-1"></i><?php echo date('d M Y', strtotime($row['OrderDate'])); ?></div>
                                <div class="text-muted small mt-1"><i class="bi bi-clock me-1"></i><?php echo date('h:i A', strtotime($row['OrderDate'])); ?></div>
                            </td>
                            <td class="fw-bold text-success fs-6">Rs. <?php echo number_format((float)$row['TotalAmount'], 2); ?></td>
                            <td>
                                <?php 
                                    // FIXED: Comprehensive mapping of all possible order statuses to Bootstrap colors
                                    $status = trim($row['OrderStatus']);
                                    $status_class = 'bg-secondary text-white'; // default
                                    $icon = 'bi-circle-fill';

                                    switch(strtolower($status)) {
                                        case 'pending': 
                                            $status_class = 'bg-warning text-dark'; 
                                            $icon = 'bi-hourglass-split';
                                            break;
                                        case 'confirmed': 
                                        case 'processing': 
                                            $status_class = 'bg-primary text-white'; 
                                            $icon = 'bi-box-seam';
                                            break;
                                        case 'dispatched': 
                                        case 'out for delivery':
                                            $status_class = 'bg-info text-dark'; 
                                            $icon = 'bi-truck';
                                            break;
                                        case 'delivered': 
                                        case 'completed':
                                            $status_class = 'bg-success text-white'; 
                                            $icon = 'bi-check-circle-fill';
                                            break;
                                        case 'canceled': 
                                        case 'cancelled':
                                            $status_class = 'bg-danger text-white'; 
                                            $icon = 'bi-x-circle-fill';
                                            break;
                                    }
                                ?>
                                <!-- Using Solid Bootstrap classes instead of opacity so it stands out -->
                                <span class="status-badge <?php echo $status_class; ?> shadow-sm">
                                    <i class="bi <?php echo $icon; ?>"></i> <?php echo htmlspecialchars($status); ?>
                                </span>
                            </td>
                            <td>
                                <?php 
                                    $pb = ($row['PaymentStatus']=='Paid') ? 'bg-success text-white' : 'bg-secondary text-white'; 
                                ?>
                                <span class="badge <?php echo $pb; ?> px-2 py-1 rounded-2 shadow-sm"><?php echo $row['PaymentStatus']; ?></span>
                            </td>
                            <td class="pe-4 text-end">
                                <a href="orders.php" class="btn btn-sm btn-light border text-primary fw-bold rounded-pill px-3 shadow-sm hover-primary">Manage</a>
                            </td>
                        </tr>
                        <?php endwhile; else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                                    <i class="bi bi-inbox fs-1 text-secondary opacity-50"></i>
                                </div>
                                <h5 class="fw-bold text-dark">No orders found</h5>
                                <p class="mb-0">There are no recent orders in the database.</p>
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
