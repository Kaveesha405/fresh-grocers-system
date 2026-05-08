<?php
require_once '../config.php';

// Filters
$search   = isset($_GET['search'])   ? clean_input($_GET['search'])   : '';
$category = isset($_GET['category']) ? clean_input($_GET['category']) : '';
$special  = isset($_GET['special'])  ? (int)$_GET['special']          : 0;
$sort     = isset($_GET['sort'])     ? clean_input($_GET['sort'])     : 'newest';

// Build query
$where = "WHERE StockQuantity > 0";
if ($search)   $where .= " AND (ProductName LIKE '%$search%' OR Description LIKE '%$search%')";
if ($category) $where .= " AND Category = '$category'";

$order_by = match($sort) {
    'price_low'  => 'Price ASC',
    'price_high' => 'Price DESC',
    'name'       => 'ProductName ASC',
    default      => 'ProductID DESC'
};

$products = $conn->query("SELECT * FROM product $where ORDER BY $order_by");
$categories = $conn->query("SELECT DISTINCT Category FROM product WHERE Category IS NOT NULL ORDER BY Category");

$page_title = "Shop"; 
include '../includes/customer-header.php';
?>

<!-- Top Breadcrumb Bar -->
<div class="bg-light py-3 border-bottom mb-4">
    <div class="container">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 small fw-semibold">
                        <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none text-muted">Home</a></li>
                        <li class="breadcrumb-item active text-success" aria-current="page">Shop</li>
                    </ol>
                </nav>
            </div>
            <div class="text-end mt-2 mt-md-0">
                <span class="badge bg-white text-dark border shadow-sm px-3 py-2 rounded-pill fw-bold">
                    <?php echo $products ? $products->num_rows : 0; ?> Products Found
                </span>
            </div>
        </div>
    </div>
</div>

<div class="container pb-5">
    
    <!-- Filter Toolbar -->
    <div class="card filter-card shadow-sm rounded-4 mb-5">
        <div class="card-body p-4">
            <form method="GET" action="" class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label class="form-label small fw-bold text-uppercase text-muted" style="letter-spacing: 0.5px;">Search</label>
                    <div class="input-group input-group-lg shadow-sm rounded-pill overflow-hidden">
                        <span class="input-group-text bg-white border-0 ps-4">
                            <i class="bi bi-search text-muted"></i>
                        </span>
                        <input type="text" name="search" class="form-control border-0 bg-white" 
                               placeholder="What are you looking for?" value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-uppercase text-muted" style="letter-spacing: 0.5px;">Category</label>
                    <select name="category" class="form-select form-select-lg rounded-pill shadow-sm border-0 cursor-pointer" onchange="this.form.submit()">
                        <option value="">All Categories</option>
                        <?php if ($categories): while($cat = $categories->fetch_assoc()): ?>
                        <option value="<?php echo $cat['Category']; ?>" <?php echo $category == $cat['Category'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['Category']); ?>
                        </option>
                        <?php endwhile; endif; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-uppercase text-muted" style="letter-spacing: 0.5px;">Sort By</label>
                    <select name="sort" class="form-select form-select-lg rounded-pill shadow-sm border-0 cursor-pointer" onchange="this.form.submit()">
                        <option value="newest"     <?php echo $sort=='newest'     ? 'selected' : ''; ?>>Newest Arrivals</option>
                        <option value="price_low"  <?php echo $sort=='price_low'  ? 'selected' : ''; ?>>Price: Low to High</option>
                        <option value="price_high" <?php echo $sort=='price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                        <option value="name"       <?php echo $sort=='name'       ? 'selected' : ''; ?>>Name (A-Z)</option>
                    </select>
                </div>
                <div class="col-md-1 text-end">
                    <a href="shop.php" class="btn btn-white rounded-circle shadow-sm d-flex align-items-center justify-content-center mx-auto" 
                       style="width: 48px; height: 48px;" title="Reset Filters">
                        <i class="bi bi-arrow-counterclockwise fs-5 text-danger"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Products Grid -->
    <?php if ($products && $products->num_rows > 0): ?>
    <div class="row g-4">
        <?php while($product = $products->fetch_assoc()): ?>
        <div class="col-lg-3 col-md-4 col-sm-6">
            <div class="card h-100 bg-white rounded-4 product-card position-relative">
                
                <!-- Badges (Absolute positioned over image) -->
                <div class="position-absolute top-0 start-0 w-100 p-3 d-flex justify-content-between align-items-start" style="z-index: 2;">
                    <span class="badge bg-white text-success shadow-sm rounded-pill cat-badge px-3 py-2 border border-success border-opacity-25">
                        <?php echo htmlspecialchars($product['Category']); ?>
                    </span>
                    <?php if ($product['StockQuantity'] <= 5): ?>
                    <span class="badge bg-danger text-white shadow-sm rounded-pill px-2 py-1 cat-badge">
                        Low Stock
                    </span>
                    <?php endif; ?>
                </div>

                <!-- Product Image -->
                <a href="product.php?id=<?php echo $product['ProductID']; ?>" class="product-image-container border-bottom text-decoration-none">
                    <img src="<?php echo $product['ImageURL'] ?: '../assets/img/placeholder.jpg'; ?>" 
                         class="product-img"
                         alt="<?php echo htmlspecialchars($product['ProductName']); ?>">
                </a>

                <!-- Product Body -->
                <div class="card-body p-4 d-flex flex-column">
                    <!-- Title -->
                    <a href="product.php?id=<?php echo $product['ProductID']; ?>" class="text-decoration-none">
                        <h5 class="product-title" title="<?php echo htmlspecialchars($product['ProductName']); ?>">
                            <?php echo htmlspecialchars($product['ProductName']); ?>
                        </h5>
                    </a>
                    
                    <!-- Short Desc -->
                    <p class="text-muted small mb-4" style="line-height: 1.4;">
                        <?php echo htmlspecialchars(substr($product['Description'] ?? '', 0, 45)); ?>...
                    </p>
                    
                    <!-- Bottom Row: Price & Add Button -->
                    <div class="mt-auto d-flex justify-content-between align-items-center">
                        <div class="product-price">
                            <?php echo format_price($product['Price']); ?>
                        </div>
                        
                        <!-- Add to cart quick button -->
                        <form method="POST" action="../api/add-to-cart.php" class="m-0">
                            <input type="hidden" name="product_id" value="<?php echo $product['ProductID']; ?>">
                            <input type="hidden" name="quantity" value="1">
                            <input type="hidden" name="redirect" value="../customer/shop.php">
                            <button type="submit" 
                                    class="btn btn-success rounded-circle shadow-sm d-flex align-items-center justify-content-center"
                                    style="width: 42px; height: 42px;" 
                                    title="Add to Cart"
                                    <?php echo ($product['StockQuantity'] <= 0) ? 'disabled' : ''; ?>>
                                <i class="bi bi-cart-plus fs-5"></i>
                            </button>
                        </form>
                    </div>
                </div>

            </div>
        </div>
        <?php endwhile; ?>
    </div>
    <?php else: ?>
    <!-- No Results State -->
    <div class="text-center py-5 bg-white rounded-4 shadow-sm border">
        <div class="mb-4">
            <i class="bi bi-search display-1 text-success opacity-25"></i>
        </div>
        <h3 class="fw-bold text-dark">No Products Found</h3>
        <p class="text-muted mb-4 lead">We couldn't find any products matching your current filters.</p>
        <a href="shop.php" class="btn btn-outline-success btn-lg rounded-pill px-5 fw-bold">
            <i class="bi bi-arrow-counterclockwise me-2"></i>Clear Filters
        </a>
    </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
