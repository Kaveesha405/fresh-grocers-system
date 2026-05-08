<?php
require_once '../config.php';
if (!isset($_SESSION['agent_id'])) {
    header("Location: ../delivery/login.php");
    exit();
}

$agent_id = (int)$_SESSION['agent_id'];
$error    = '';

$agent = $conn->query("SELECT * FROM DeliveryAgent WHERE DeliveryAgentID = $agent_id")->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_location'])) {
    $location = clean_input($_POST['current_location']);
    
    // Properly parse Latitude & Longitude variables
    $lat = isset($_POST['latitude']) && $_POST['latitude'] !== '' ? (float)$_POST['latitude'] : 'NULL';
    $lng = isset($_POST['longitude']) && $_POST['longitude'] !== '' ? (float)$_POST['longitude'] : 'NULL';

    if (empty($location)) {
        $error = "Please enter or detect your location.";
    } else {
        // Database query now updates the `LocationLat` and `LocationLng` columns
        $conn->query("UPDATE DeliveryAgent SET 
                        Location = '$location', 
                        LocationLat = $lat, 
                        LocationLng = $lng 
                      WHERE DeliveryAgentID = $agent_id");
        
        set_success_message("Location updated successfully!");
        header("Location: ../delivery/update-location.php");
        exit();
    }
}

$active_orders = $conn->query("
    SELECT o.OrderID, o.OrderStatus,
           CONCAT(c.FirstName,' ',c.LastName) as CustomerName, c.PhoneNumber
    FROM `Order` o
    JOIN Customer c ON o.CustomerID = c.CustomerID
    WHERE o.DeliveryAgentID = $agent_id
      AND o.OrderStatus NOT IN ('Delivered','Canceled')
    ORDER BY o.OrderDate DESC
");
?>
<?php $page_title = "Update Location"; include '../includes/delivery-header.php'; ?>

<!-- Leaflet CSS (OpenStreetMap) — no API key needed -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>

<div class="container-fluid py-4 px-4">

    <div class="mb-4">
        <h4 class="fw-bold mb-1">
            <i class="bi bi-geo-alt me-2 text-warning"></i>Update Location
        </h4>
        <p class="text-muted mb-0 small">Share your live location so orders can be tracked</p>
    </div>

    <?php $msg = get_success_message(); if ($msg): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle me-2"></i><?php echo $msg; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-circle me-2"></i><?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-semibold py-3 d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-pin-map me-2 text-warning"></i>My Location</span>
                    <!-- Live accuracy indicator -->
                    <span id="accuracy-badge" class="badge bg-secondary accuracy-badge d-none">
                        <i class="bi bi-broadcast me-1"></i>
                        <span id="accuracy-text">--</span>
                    </span>
                </div>
                <div class="card-body">

                    <!-- Current saved location banner -->
                    <div class="rounded-3 p-3 mb-3 d-flex align-items-center gap-3"
                        style="background-color:#fff3cd;">
                        <div class="location-pulse rounded-circle d-flex align-items-center
                            justify-content-center flex-shrink-0"
                            style="width:42px;height:42px;background-color:#fd7e14;">
                            <i class="bi bi-geo-alt-fill text-white fs-5"></i>
                        </div>
                        <div class="w-100 overflow-hidden">
                            <p class="mb-0 fw-semibold small">Last Saved Location</p>
                            <p class="mb-0 text-muted text-truncate" style="font-size:0.8rem;">
                                <?php echo htmlspecialchars($agent['Location'] ?? 'Not set yet'); ?>
                            </p>
                        </div>
                    </div>

                    <!-- MAP -->
                    <div id="map" class="mb-3 shadow-sm"></div>
                    <p id="map-status" class="text-muted small text-center mb-3">
                        <i class="bi bi-info-circle me-1"></i>Click "Detect My Location" to load map
                    </p>

                    <form method="POST" action="" id="location-form">
                        <input type="hidden" name="latitude"  id="latitude">
                        <input type="hidden" name="longitude" id="longitude">

                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Exact Location Name / Address</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light">
                                    <i class="bi bi-geo-alt text-muted"></i>
                                </span>
                                <!-- Changed to textarea so full long addresses are visible -->
                                <textarea name="current_location" id="location-text"
                                    class="form-control" rows="2"
                                    placeholder="e.g. 123 Galle Road, Colombo 03"
                                    required><?php echo htmlspecialchars($agent['Location'] ?? ''); ?></textarea>
                            </div>
                            <small class="text-muted">Auto-filled with exact location when you detect, or type manually</small>
                        </div>

                        <!-- Detect Button -->
                        <button type="button" id="detect-btn"
                            class="btn btn-outline-warning fw-semibold w-100 mb-3"
                            onclick="detectLocation()">
                            <i class="bi bi-crosshair me-2"></i>Detect My Exact Location
                        </button>

                        <!-- Save Button -->
                        <div class="d-grid">
                            <button type="submit" name="update_location"
                                class="btn fw-semibold text-white" style="background-color:#fd7e14;">
                                <i class="bi bi-check-circle me-2"></i>Save Location
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Active Deliveries -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-semibold py-3">
                    <i class="bi bi-truck me-2 text-warning"></i>Active Deliveries
                    <?php if ($active_orders): ?>
                        <span class="badge ms-1" style="background-color:#fd7e14;">
                            <?php echo $active_orders->num_rows; ?>
                        </span>
                    <?php endif; ?>
                </div>
                <div class="card-body p-0">
                    <?php if ($active_orders && $active_orders->num_rows > 0): ?>
                    <div class="list-group list-group-flush">
                        <?php while($o = $active_orders->fetch_assoc()):
                            $badge = ['Confirmed'=>'warning','Out for Delivery'=>'info'];
                            $b = $badge[$o['OrderStatus']] ?? 'secondary';
                        ?>
                        <div class="list-group-item px-3 py-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <p class="mb-1 fw-semibold small">
                                        <i class="bi bi-bag me-1 text-warning"></i>
                                        Order #<?php echo $o['OrderID']; ?>
                                    </p>
                                    <p class="mb-1 small text-muted">
                                        <i class="bi bi-person me-1"></i>
                                        <?php echo htmlspecialchars($o['CustomerName']); ?>
                                    </p>
                                    <a href="tel:<?php echo $o['PhoneNumber']; ?>"
                                        class="text-decoration-none small">
                                        <i class="bi bi-telephone me-1 text-success"></i>
                                        <?php echo htmlspecialchars($o['PhoneNumber']); ?>
                                    </a>
                                </div>
                                <div class="text-end d-flex flex-column gap-2">
                                    <span class="badge bg-<?php echo $b; ?>">
                                        <?php echo $o['OrderStatus']; ?>
                                    </span>
                                    <a href="my-orders.php?id=<?php echo $o['OrderID']; ?>"
                                        class="btn btn-sm btn-outline-warning">
                                        <i class="bi bi-eye me-1"></i>View
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-check-circle fs-1 text-success mb-2 d-block"></i>
                        <p class="mb-0 fw-semibold">All clear!</p>
                        <small>No active deliveries right now</small>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<!-- Page-specific JS moved to assets/js/script.js -->

<?php include '../includes/delivery-footer.php'; ?>
