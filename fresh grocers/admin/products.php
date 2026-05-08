<?php
$page_title = "Manage Products";
include '../includes/admin-header.php';

// Safe way to get messages
$msg_ok  = get_success_message();
$msg_err = get_error_message();

// ---------------------------
// ADD / UPDATE (SAVE PRODUCT)
// ---------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_product'])) {

    $id    = (int)($_POST['product_id'] ?? 0);
    $name  = clean_input($_POST['name'] ?? '');
    $cat   = clean_input($_POST['category'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $qty   = (int)($_POST['stock'] ?? 0);
    $desc  = clean_input($_POST['description'] ?? '');
    $img   = clean_input($_POST['image_url'] ?? '');

    // Validation
    if ($name === '' || $cat === '' || $price <= 0 || $qty < 0) {
        set_error_message("Please fill Name, Category, valid Price, and Stock correctly.");
        header("Location: products.php");
        exit();
    }

    if ($id > 0) {
        // UPDATE Existing Product
        $stmt = $conn->prepare("
            UPDATE Product
            SET ProductName=?, Category=?, Price=?, StockQuantity=?, Description=?, ImageURL=?
            WHERE ProductID=?
        ");
        if ($stmt) {
            $stmt->bind_param("ssdissi", $name, $cat, $price, $qty, $desc, $img, $id);
            if ($stmt->execute()) {
                set_success_message("Product updated successfully.");
            } else {
                set_error_message("Update failed: " . $stmt->error);
            }
            $stmt->close();
        } else {
            set_error_message("Database prepare failed: " . $conn->error);
        }
    } else {
        // INSERT New Product
        $stmt = $conn->prepare("
            INSERT INTO Product (ProductName, Category, Price, StockQuantity, Description, ImageURL)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        if ($stmt) {
            $stmt->bind_param("ssdiss", $name, $cat, $price, $qty, $desc, $img);
            if ($stmt->execute()) {
                set_success_message("Product added successfully.");
            } else {
                set_error_message("Insert failed: " . $stmt->error);
            }
            $stmt->close();
        } else {
            set_error_message("Database prepare failed: " . $conn->error);
        }
    }
    header("Location: products.php");
    exit();
}

// --------
// DELETE
// --------
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];

    $stmt = $conn->prepare("DELETE FROM Product WHERE ProductID=?");
    if ($stmt) {
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            set_success_message("Product deleted successfully.");
        } else {
            set_error_message("Delete failed: " . $stmt->error);
        }
        $stmt->close();
    } else {
        set_error_message("Database prepare failed: " . $conn->error);
    }
    header("Location: products.php");
    exit();
}

// ---------------------------
// FILTERS (Search & Category)
// ---------------------------
$search_query = isset($_GET['search']) ? clean_input($_GET['search']) : '';
$cat_filter   = isset($_GET['category']) ? clean_input($_GET['category']) : '';

// Build the dynamic query
$where_clauses = [];
if (!empty($search_query)) {
    $where_clauses[] = "(ProductName LIKE '%$search_query%' OR Description LIKE '%$search_query%')";
}
if (!empty($cat_filter)) {
    $where_clauses[] = "Category = '$cat_filter'";
}

$where_sql = "";
if (count($where_clauses) > 0) {
    $where_sql = "WHERE " . implode(" AND ", $where_clauses);
}

// FIXED: Changed ORDER BY to ASC so product ID 1 appears at the top
$products = $conn->query("SELECT * FROM Product $where_sql ORDER BY ProductID ASC");

// Fetch unique categories for the dropdown filter
$categories = $conn->query("SELECT DISTINCT Category FROM Product WHERE Category IS NOT NULL ORDER BY Category");

// Online placeholder image
$placeholder_img = "https://via.placeholder.com/300x300?text=No+Image";
?>

<style>
    /* Content Wrapper */
    .dashboard-bg { 
        background-color: #f4f6f9; 
        min-height: 85vh; 
        padding-bottom: 3rem;
    }
    
    /* Table Styling */
    .table-container { 
        border-radius: 16px; 
        overflow: hidden; 
        box-shadow: 0 5px 25px rgba(0,0,0,0.06); 
        background: #fff; 
        border: 1px solid #e9ecef;
    }
    .table th { 
        border-bottom: 2px solid #f0f2f5; 
        text-transform: uppercase; 
        font-size: 0.8rem; 
        letter-spacing: 0.5px; 
        padding: 1.1rem 1rem;
        background-color: #f8f9fa;
        color: #6c757d;
    }
    .table td { 
        vertical-align: middle; 
        padding: 1.05rem 1rem; 
        border-bottom: 1px solid #f8f9fa; 
    }
    
    /* Product Image Thumbnail */
    .thumb {
        width: 44px;
        height: 44px;
        object-fit: cover;
        border-radius: 10px;
        border: 1px solid #e9ecef;
        background: #fff;
    }

    /* Stock Badge */
    .stock-pill {
        padding: 0.35rem 0.7rem;
        border-radius: 999px;
        font-weight: 700;
        font-size: 0.82rem;
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
    }

    /* Modal Image Preview */
    .modal-preview {
        width: 100%;
        height: 180px;
        object-fit: cover;
        border-radius: 12px;
        border: 1px solid #e9ecef;
        background: #f8f9fa;
    }
</style>

<div class="dashboard-bg">
    <div class="container-fluid py-4 px-4">

        <!-- Flash Messages -->
        <?php if ($msg_ok): ?>
            <div class="alert alert-success d-flex align-items-center shadow-sm alert-dismissible fade show">
                <i class="bi bi-check-circle-fill me-2 fs-5"></i>
                <div class="fw-semibold"><?php echo htmlspecialchars($msg_ok); ?></div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($msg_err): ?>
            <div class="alert alert-danger d-flex align-items-center shadow-sm alert-dismissible fade show">
                <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                <div class="fw-semibold"><?php echo htmlspecialchars($msg_err); ?></div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Header Section -->
        <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
            <div>
                <h3 class="fw-bold mb-1 text-dark"><i class="bi bi-box-seam me-2 text-success"></i>Products Inventory</h3>
                <p class="text-muted small mb-0 fw-semibold">Add, edit, and manage store products.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="products.php" class="btn btn-light border shadow-sm fw-bold text-secondary">
                    <i class="bi bi-arrow-clockwise me-1"></i> Refresh
                </a>
                <button class="btn btn-success shadow-sm fw-bold" id="btnAddProduct" data-bs-toggle="modal" data-bs-target="#productModal">
                    <i class="bi bi-plus-lg me-1"></i> Add Product
                </button>
            </div>
        </div>

        <!-- Search and Filter Bar -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body p-3">
                <form method="GET" action="products.php" class="row g-3">
                    <div class="col-md-5">
                        <div class="input-group">
                            <span class="input-group-text bg-white text-muted border-end-0"><i class="bi bi-search"></i></span>
                            <input type="text" name="search" class="form-control border-start-0" placeholder="Search by product name..." value="<?php echo htmlspecialchars($search_query); ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <select name="category" class="form-select">
                            <option value="">All Categories</option>
                            <?php if ($categories && $categories->num_rows > 0): ?>
                                <?php while($cat = $categories->fetch_assoc()): ?>
                                    <option value="<?php echo htmlspecialchars($cat['Category']); ?>" <?php echo ($cat_filter == $cat['Category']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['Category']); ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-dark w-100 fw-bold">Search</button>
                        <?php if (!empty($search_query) || !empty($cat_filter)): ?>
                            <a href="products.php" class="btn btn-outline-danger fw-bold" title="Clear Filters"><i class="bi bi-x-lg"></i></a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <!-- Data Table -->
        <div class="table-container">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead>
                        <tr>
                            <th class="ps-4">ID</th>
                            <th>Image</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th class="pe-4 text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($products && $products->num_rows > 0): ?>
                            <?php while($p = $products->fetch_assoc()): 
                                $imgFile = !empty($p['ImageURL']) ? $p['ImageURL'] : $placeholder_img;
                            ?>
                            <tr>
                                <td class="ps-4 fw-bold text-dark"><?php echo (int)$p['ProductID']; ?></td>
                                <td>
                                    <img class="thumb" src="<?php echo htmlspecialchars($imgFile); ?>" alt="Product" onerror="this.src='<?php echo $placeholder_img; ?>'">
                                </td>
                                <td class="fw-bold text-dark">
                                    <?php echo htmlspecialchars($p['ProductName']); ?>
                                    <?php if (!empty($p['Description'])): ?>
                                        <div class="text-muted small mt-1 fw-normal" style="max-width: 250px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                            <?php echo htmlspecialchars($p['Description']); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border px-3 py-2 rounded-pill shadow-sm">
                                        <i class="bi bi-tag me-1"></i><?php echo htmlspecialchars($p['Category']); ?>
                                    </span>
                                </td>
                                <td class="fw-bold text-success fs-6">Rs. <?php echo number_format((float)$p['Price'], 2); ?></td>
                                <td>
                                    <?php if ((int)$p['StockQuantity'] > 0): ?>
                                        <span class="stock-pill bg-success text-white shadow-sm">
                                            <i class="bi bi-check2-circle"></i> <?php echo (int)$p['StockQuantity']; ?> in stock
                                        </span>
                                    <?php else: ?>
                                        <span class="stock-pill bg-danger text-white shadow-sm">
                                            <i class="bi bi-x-circle"></i> Out of stock
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="pe-4 text-end">
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-light border text-primary fw-bold rounded-pill px-3 shadow-sm btnEdit"
                                        data-bs-toggle="modal"
                                        data-bs-target="#productModal"
                                        data-id="<?php echo (int)$p['ProductID']; ?>"
                                        data-name="<?php echo htmlspecialchars($p['ProductName'], ENT_QUOTES); ?>"
                                        data-category="<?php echo htmlspecialchars($p['Category'], ENT_QUOTES); ?>"
                                        data-price="<?php echo htmlspecialchars($p['Price'], ENT_QUOTES); ?>"
                                        data-stock="<?php echo (int)$p['StockQuantity']; ?>"
                                        data-image="<?php echo htmlspecialchars($p['ImageURL'] ?? '', ENT_QUOTES); ?>"
                                        data-desc="<?php echo htmlspecialchars($p['Description'] ?? '', ENT_QUOTES); ?>"
                                    >
                                        <i class="bi bi-pencil-square me-1"></i> Edit
                                    </button>
                                    <a
                                        href="?delete=<?php echo (int)$p['ProductID']; ?>"
                                        class="btn btn-sm btn-outline-danger rounded-pill px-3 ms-2 shadow-sm"
                                        onclick="return confirm('Are you sure you want to delete this product?')"
                                    >
                                        <i class="bi bi-trash me-1"></i> Delete
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <h5 class="fw-bold text-dark">No products found</h5>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal for Add/Edit Product -->
<div class="modal fade" id="productModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form method="POST" action="products.php" class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold text-dark" id="productModalTitle">Add Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" name="product_id" id="product_id" value="0">
                <div class="row g-4">
                    <div class="col-lg-7">
                        <div class="mb-3">
                            <label class="form-label fw-bold text-muted small text-uppercase mb-1">Product Name</label>
                            <input type="text" name="name" id="name" class="form-control form-control-lg fs-6" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold text-muted small text-uppercase mb-1">Category</label>
                                <input type="text" name="category" id="category" class="form-control" required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label fw-bold text-muted small text-uppercase mb-1">Price</label>
                                <input type="number" step="0.01" name="price" id="price" class="form-control" required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label fw-bold text-muted small text-uppercase mb-1">Stock</label>
                                <input type="number" name="stock" id="stock" class="form-control" required>
                            </div>
                        </div>
                        <div class="mb-2">
                            <label class="form-label fw-bold text-muted small text-uppercase mb-1">Description</label>
                            <textarea name="description" id="description" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="col-lg-5 border-start ps-lg-4">
                        <label class="form-label fw-bold text-muted small text-uppercase mb-1">Image URL</label>
                        <input type="url" name="image_url" id="image_url" class="form-control mb-3">
                        <img id="imgPreview" class="modal-preview shadow-sm" src="<?php echo $placeholder_img; ?>" alt="Preview">
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary fw-bold px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" name="save_product" class="btn btn-success fw-bold px-4" id="btnSaveProduct">Save Product</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modalTitle = document.getElementById('productModalTitle');
    const productId  = document.getElementById('product_id');
    const name       = document.getElementById('name');
    const category   = document.getElementById('category');
    const price      = document.getElementById('price');
    const stock      = document.getElementById('stock');
    const imageUrl   = document.getElementById('image_url');
    const desc       = document.getElementById('description');
    const imgPreview = document.getElementById('imgPreview');
    const btnAdd     = document.getElementById('btnAddProduct');
    const btnSave    = document.getElementById('btnSaveProduct');
    const placeholderImg = "<?php echo $placeholder_img; ?>";

    function updatePreview(url) {
        imgPreview.src = (url || '').trim() ? url.trim() : placeholderImg;
    }

    imageUrl.addEventListener('input', function() { updatePreview(this.value); });
    imgPreview.addEventListener('error', function() { this.src = placeholderImg; });

    btnAdd.addEventListener('click', function() {
        modalTitle.textContent = 'Add New Product';
        productId.value = '0';
        name.value = ''; category.value = ''; price.value = ''; stock.value = ''; imageUrl.value = ''; desc.value = '';
        updatePreview('');
    });

    document.querySelectorAll('.btnEdit').forEach(btn => {
        btn.addEventListener('click', function() {
            modalTitle.textContent = 'Edit Product';
            productId.value = this.dataset.id;
            name.value = this.dataset.name;
            category.value = this.dataset.category;
            price.value = this.dataset.price;
            stock.value = this.dataset.stock;
            imageUrl.value = this.dataset.image;
            desc.value = this.dataset.desc;
            updatePreview(this.dataset.image);
        });
    });
});
</script>

<?php include '../includes/admin-footer.php'; ?>
