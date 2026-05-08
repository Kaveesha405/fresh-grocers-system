<?php
$page_title = "Manage Customers";
include '../includes/admin-header.php';

// Helper functions
function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function initials($first, $last) {
    $a = mb_substr(trim((string)$first), 0, 1);
    $b = mb_substr(trim((string)$last), 0, 1);
    return strtoupper($a . $b) ?: "C";
}

$success_msg = '';
$error_msg = '';

// Handle Delete Customer Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_customer'])) {
    $delete_id = (int)$_POST['customer_id'];
    
    // Using prepared statement to prevent SQL injection
    $delStmt = $conn->prepare("DELETE FROM Customer WHERE CustomerID = ?");
    if ($delStmt) {
        $delStmt->bind_param("i", $delete_id);
        if ($delStmt->execute()) {
            $success_msg = "Customer #{$delete_id} has been permanently deleted.";
        } else {
            $error_msg = "Failed to delete customer. They may have dependent records.";
        }
        $delStmt->close();
    }
}

// Search and Pagination logic
$q = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

$whereSql = "";
$types = "";
$params = [];

if ($q !== "") {
    $whereSql = "WHERE FirstName LIKE ? OR LastName LIKE ? OR Email LIKE ? OR PhoneNumber LIKE ? OR Address LIKE ?";
    $like = "%{$q}%";
    $types = "sssss";
    $params = [$like, $like, $like, $like, $like];
}

// Get total count for pagination
$total = 0;
$sqlCount = "SELECT COUNT(*) AS total FROM Customer $whereSql";
$stmtCount = $conn->prepare($sqlCount);
if ($stmtCount) {
    if ($types) $stmtCount->bind_param($types, ...$params);
    $stmtCount->execute();
    $resCount = $stmtCount->get_result();
    if ($row = $resCount->fetch_assoc()) $total = (int)$row['total'];
    $stmtCount->close();
}

$totalPages = max(1, (int)ceil($total / $perPage));

// Fetch customers safely
$sqlList = "SELECT CustomerID, FirstName, LastName, Email, PhoneNumber, Address 
            FROM Customer $whereSql 
            ORDER BY CustomerID DESC LIMIT ? OFFSET ?";
$stmtList = $conn->prepare($sqlList);
if ($stmtList) {
    if ($types) {
        $types2 = $types . "ii";
        $params2 = array_merge($params, [$perPage, $offset]);
        $stmtList->bind_param($types2, ...$params2);
    } else {
        $stmtList->bind_param("ii", $perPage, $offset);
    }
    $stmtList->execute();
    $customers = $stmtList->get_result();
} else {
    $customers = false; 
}
?>

<!-- Main Wrapper: Flexbox & min-height used to push footer down -->
<div class="container-fluid py-4 px-4 d-flex flex-column" style="min-height: calc(100vh - 120px);">

    <!-- Success / Error Alerts -->
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

    <!-- Page Header & Search -->
    <div class="row mb-4 align-items-center">
        <div class="col-md-6 mb-3 mb-md-0">
            <h4 class="fw-bold mb-1"><i class="bi bi-people me-2 text-success"></i>Registered Customers</h4>
            <div class="text-muted small">
                Manage and view customer details. Total: <span class="badge bg-success text-white rounded-pill ms-1"><?php echo $total; ?></span>
            </div>
        </div>
        <div class="col-md-6">
            <form class="d-flex justify-content-md-end gap-2" method="get" action="customers.php">
                <div class="input-group shadow-sm" style="max-width: 350px;">
                    <span class="input-group-text bg-white text-muted border-end-0"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control border-start-0 ps-0 focus-ring-none" name="q" placeholder="Search customers..." value="<?php echo e($q); ?>">
                </div>
                <button class="btn btn-success px-4 shadow-sm" type="submit">Search</button>
                <?php if($q !== ''): ?>
                    <a href="customers.php" class="btn btn-outline-secondary shadow-sm">Clear</a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Table Card -->
    <div class="card border-0 shadow-sm flex-grow-1">
        <div class="card-body p-0 d-flex flex-column">
            <div class="table-responsive flex-grow-1">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4 py-3 text-muted" style="width: 80px;">ID</th>
                            <th class="py-3 text-muted">Customer Details</th>
                            <th class="py-3 text-muted">Contact Info</th>
                            <th class="py-3 text-muted">Shipping Address</th>
                            <th class="pe-4 py-3 text-muted text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($customers && $customers->num_rows > 0): ?>
                            <?php while ($c = $customers->fetch_assoc()): ?>
                                <?php
                                    $id = (int)$c['CustomerID'];
                                    $fname = $c['FirstName'] ?? '';
                                    $lname = $c['LastName'] ?? '';
                                    $fullName = trim($fname . ' ' . $lname) ?: 'Unknown';
                                    $email = $c['Email'] ?? '';
                                    $phone = $c['PhoneNumber'] ?? '';
                                    $address = $c['Address'] ?? '';
                                    $ini = initials($fname, $lname);
                                ?>
                                <tr>
                                    <td class="ps-4">
                                        <span class="fw-bold text-secondary">#<?php echo $id; ?></span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <!-- Customer Avatar -->
                                            <div class="rounded-circle bg-success bg-opacity-10 text-success fw-bold d-flex justify-content-center align-items-center me-3" style="width: 45px; height: 45px; font-size: 1.1rem;">
                                                <?php echo e($ini); ?>
                                            </div>
                                            <div>
                                                <h6 class="mb-0 fw-semibold text-dark"><?php echo e($fullName); ?></h6>
                                                <small class="text-muted"><?php echo e($email ? $email : 'No email provided'); ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="text-dark mb-1">
                                            <i class="bi bi-telephone text-success me-2"></i><?php echo e($phone ? $phone : 'N/A'); ?>
                                        </div>
                                        <?php if ($email): ?>
                                        <div class="text-muted small">
                                            <i class="bi bi-envelope text-secondary me-2"></i><a href="mailto:<?php echo e($email); ?>" class="text-decoration-none text-muted"><?php echo e($email); ?></a>
                                        </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="text-muted small" style="max-width: 250px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?php echo e($address); ?>">
                                            <i class="bi bi-geo-alt text-danger me-1"></i> <?php echo e($address ? $address : 'No address provided'); ?>
                                        </div>
                                    </td>
                                    
                                    <!-- Action Buttons -->
                                    <td class="pe-4 text-end">
                                        <div class="btn-group btn-group-sm shadow-sm" role="group">
                                            <!-- View Profile -->
                                            <button type="button" class="btn btn-light text-primary border" data-bs-toggle="modal" data-bs-target="#viewCustomer<?php echo $id; ?>" title="View Profile">
                                                <i class="bi bi-eye-fill"></i>
                                            </button>
                                            <!-- View Orders (Sends to orders.php filtering by customer_id) -->
                                            <a href="orders.php?customer_id=<?php echo $id; ?>" class="btn btn-light text-success border" title="View Orders">
                                                <i class="bi bi-box-seam"></i>
                                            </a>
                                            <!-- Delete Button -->
                                            <button type="button" class="btn btn-light text-danger border" data-bs-toggle="modal" data-bs-target="#deleteCustomer<?php echo $id; ?>" title="Delete Customer">
                                                <i class="bi bi-trash-fill"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>

                                <!-- View Customer Profile Modal -->
                                <div class="modal fade" id="viewCustomer<?php echo $id; ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content border-0 shadow">
                                            <div class="modal-header bg-light border-0">
                                                <h5 class="modal-title fw-bold"><i class="bi bi-person-badge text-success me-2"></i>Customer Profile</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body p-4">
                                                <div class="text-center mb-4">
                                                    <div class="rounded-circle bg-success bg-opacity-10 text-success fw-bold d-flex justify-content-center align-items-center mx-auto mb-3" style="width: 80px; height: 80px; font-size: 2rem;">
                                                        <?php echo e($ini); ?>
                                                    </div>
                                                    <h4 class="fw-bold mb-0 text-dark"><?php echo e($fullName); ?></h4>
                                                    <span class="badge bg-success mt-2">ID: #<?php echo $id; ?></span>
                                                </div>
                                                <hr class="text-muted opacity-25">
                                                <div class="row g-3">
                                                    <div class="col-12">
                                                        <label class="text-muted small fw-bold text-uppercase">Email Address</label>
                                                        <p class="mb-0 text-dark fw-medium"><i class="bi bi-envelope me-2 text-secondary"></i><?php echo e($email ? $email : 'Not provided'); ?></p>
                                                    </div>
                                                    <div class="col-12">
                                                        <label class="text-muted small fw-bold text-uppercase">Phone Number</label>
                                                        <p class="mb-0 text-dark fw-medium"><i class="bi bi-telephone me-2 text-secondary"></i><?php echo e($phone ? $phone : 'Not provided'); ?></p>
                                                    </div>
                                                    <div class="col-12">
                                                        <label class="text-muted small fw-bold text-uppercase">Delivery Address</label>
                                                        <div class="bg-light p-3 rounded mt-1 border">
                                                            <p class="mb-0 text-dark"><i class="bi bi-geo-alt me-2 text-danger"></i><?php echo nl2br(e($address ? $address : 'Not provided')); ?></p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-footer border-0 bg-light">
                                                <a href="orders.php?customer_id=<?php echo $id; ?>" class="btn btn-outline-success"><i class="bi bi-box-seam me-1"></i> View Orders</a>
                                                <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Close</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Delete Confirmation Modal -->
                                <div class="modal fade" id="deleteCustomer<?php echo $id; ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content border-0 shadow">
                                            <div class="modal-header bg-danger text-white border-0">
                                                <h5 class="modal-title fw-bold"><i class="bi bi-exclamation-triangle-fill me-2"></i>Delete Customer</h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body p-4 text-center">
                                                <div class="mb-3">
                                                    <i class="bi bi-trash text-danger opacity-75" style="font-size: 3.5rem;"></i>
                                                </div>
                                                <h5 class="fw-bold text-dark">Are you sure?</h5>
                                                <p class="text-muted mb-1">You are about to permanently delete the customer account for <strong><?php echo e($fullName); ?></strong>.</p>
                                                <p class="text-muted small">This action cannot be undone. Any existing orders tied to this ID will be kept for history but detached.</p>
                                            </div>
                                            <div class="modal-footer border-0 bg-light justify-content-center">
                                                <button type="button" class="btn btn-light border px-4" data-bs-dismiss="modal">Cancel</button>
                                                <!-- Form to handle deletion -->
                                                <form method="POST" action="customers.php<?php echo $q ? '?q='.urlencode($q).'&page='.$page : '?page='.$page; ?>">
                                                    <input type="hidden" name="customer_id" value="<?php echo $id; ?>">
                                                    <button type="submit" name="delete_customer" class="btn btn-danger px-4">Yes, Delete</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <div class="text-muted mb-3"><i class="bi bi-search fs-1"></i></div>
                                    <h5 class="text-muted">No customers found</h5>
                                    <p class="small text-muted">Try adjusting your search criteria.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Footer -->
            <?php if ($totalPages > 1): ?>
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-center p-3 border-top bg-light mt-auto">
                    <div class="text-muted small mb-3 mb-md-0">
                        Showing <strong><?php echo min($total, $offset + 1); ?></strong> to <strong><?php echo min($total, $offset + $perPage); ?></strong> of <strong><?php echo $total; ?></strong> entries
                    </div>
                    <nav aria-label="Customer pagination">
                        <ul class="pagination pagination-sm mb-0">
                            <?php
                                $baseQuery = $q !== "" ? "q=" . urlencode($q) . "&" : "";
                                $prev = max(1, $page - 1);
                                $next = min($totalPages, $page + 1);
                            ?>
                            <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                                <a class="page-link shadow-none" href="?<?php echo $baseQuery; ?>page=<?php echo $prev; ?>">Previous</a>
                            </li>
                            <?php
                                $start = max(1, $page - 2);
                                $end = min($totalPages, $page + 2);
                                for ($p = $start; $p <= $end; $p++):
                            ?>
                                <li class="page-item <?php echo ($p === $page) ? 'active' : ''; ?>">
                                    <a class="page-link shadow-none" href="?<?php echo $baseQuery; ?>page=<?php echo $p; ?>"><?php echo $p; ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?php echo ($page >= $totalPages) ? 'disabled' : ''; ?>">
                                <a class="page-link shadow-none" href="?<?php echo $baseQuery; ?>page=<?php echo $next; ?>">Next</a>
                            </li>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
if (isset($stmtList) && $stmtList) $stmtList->close();
include '../includes/admin-footer.php';
?>
