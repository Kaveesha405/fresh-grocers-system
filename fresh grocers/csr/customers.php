<?php
require_once '../config.php';
if (!isset($_SESSION['csr_id'])) {
    header("Location: ../csr/login.php");
    exit();
}

$search = isset($_GET['search']) ? clean_input($_GET['search']) : '';
$where  = $search
    ? "WHERE FirstName LIKE '%$search%' OR LastName LIKE '%$search%'
       OR Email LIKE '%$search%' OR PhoneNumber LIKE '%$search%'"
    : '';

// Fixed: Customer (capital C)
$customers = $conn->query("SELECT * FROM Customer $where ORDER BY CustomerID DESC");
?>
<?php $page_title = "Customers"; include '../includes/csr-header.php'; ?>

<div class="container-fluid py-4 px-4">

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1"><i class="bi bi-people me-2 text-primary"></i>Customers</h4>
            <p class="text-muted mb-0 small"><?php echo $customers ? $customers->num_rows : 0; ?> customer(s) found</p>
        </div>
        <a href="place-order.php" class="btn btn-primary">
            <i class="bi bi-cart-plus me-2"></i>Place Order for Customer
        </a>
    </div>

    <!-- Search -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <form method="GET" action="" class="row g-2 align-items-end">
                <div class="col-md-8">
                    <div class="input-group">
                        <span class="input-group-text bg-light">
                            <i class="bi bi-search text-muted"></i>
                        </span>
                        <input type="text" name="search" class="form-control"
                            placeholder="Search by name, email or phone..."
                            value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Search</button>
                </div>
                <div class="col-md-2">
                    <a href="customers.php" class="btn btn-outline-secondary w-100">Clear</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Address</th>
                            <th>Orders</th>
                            <th class="pe-3">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($customers && $customers->num_rows > 0):
                            while($c = $customers->fetch_assoc()):
                            // Fixed: `Order` (capital O)
                            $order_count = $conn->query("SELECT COUNT(*) as cnt FROM `Order`
                                WHERE CustomerID = {$c['CustomerID']}")->fetch_assoc()['cnt'];
                        ?>
                        <tr>
                            <td class="ps-3 text-muted small">#<?php echo $c['CustomerID']; ?></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="bg-primary text-white rounded-circle d-flex align-items-center
                                        justify-content-center flex-shrink-0"
                                        style="width:34px;height:34px;font-size:0.85rem;font-weight:700;">
                                        <?php echo strtoupper(substr($c['FirstName'], 0, 1)); ?>
                                    </div>
                                    <p class="mb-0 fw-semibold small">
                                        <?php echo htmlspecialchars($c['FirstName'].' '.$c['LastName']); ?>
                                    </p>
                                </div>
                            </td>
                            <td class="small"><?php echo htmlspecialchars($c['Email']); ?></td>
                            <td class="small"><?php echo htmlspecialchars($c['PhoneNumber']); ?></td>
                            <td class="small text-muted" style="max-width:150px;">
                                <span class="d-block text-truncate">
                                    <?php echo htmlspecialchars($c['Address'] ?? 'N/A'); ?>
                                </span>
                            </td>
                            <td><span class="badge bg-primary"><?php echo $order_count; ?></span></td>
                            <td class="pe-3">
                                <a href="place-order.php?customer_id=<?php echo $c['CustomerID']; ?>"
                                    class="btn btn-sm btn-success" title="Place Order">
                                    <i class="bi bi-cart-plus"></i>
                                </a>
                                <a href="orders.php?customer_id=<?php echo $c['CustomerID']; ?>"
                                    class="btn btn-sm btn-outline-primary ms-1" title="View Orders">
                                    <i class="bi bi-bag"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; else: ?>
                        <tr><td colspan="7" class="text-center py-4 text-muted">No customers found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/csr-footer.php'; ?>
