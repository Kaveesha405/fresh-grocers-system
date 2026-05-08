<?php
require_once '../config.php';
if (!isset($_SESSION['agent_id'])) {
    header("Location: ../delivery/login.php");
    exit();
}

$agent_id = (int)$_SESSION['agent_id'];
// SQL: DeliveryAgent, PK: DeliveryAgentID
$agent    = $conn->query("SELECT * FROM DeliveryAgent WHERE DeliveryAgentID = $agent_id")->fetch_assoc();
$error    = '';

// SQL: Rating — RatingScore, FeedbackComment, DeliveryAgentID FK
$ratings = $conn->query("
    SELECT r.*, CONCAT(c.FirstName,' ',c.LastName) as CustomerName
    FROM Rating r
    JOIN Customer c ON r.CustomerID = c.CustomerID
    WHERE r.DeliveryAgentID = $agent_id
    ORDER BY r.RatingID DESC LIMIT 10
");
$avg_rating = $conn->query("
    SELECT ROUND(AVG(RatingScore),1) as avg, COUNT(*) as total
    FROM Rating WHERE DeliveryAgentID = $agent_id
")->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $first_name = clean_input($_POST['first_name']);
    $last_name  = clean_input($_POST['last_name']);
    $phone      = clean_input($_POST['phone']);

    if (empty($first_name) || empty($last_name) || empty($phone)) {
        $error = "Please fill in all required fields.";
    } else {
        // ONLY basic info, NO location here
        $conn->query("UPDATE DeliveryAgent SET
            FirstName   = '$first_name',
            LastName    = '$last_name',
            PhoneNumber = '$phone'
            WHERE DeliveryAgentID = $agent_id");

        $_SESSION['agent_name'] = $first_name . ' ' . $last_name;
        set_success_message("Profile updated successfully!");
        header("Location: ../delivery/profile.php");
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current = $_POST['current_password'];
    $new_pwd = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];

    if (!password_verify($current, $agent['Password'])) {
        $error = "Current password is incorrect.";
    } elseif ($new_pwd !== $confirm) {
        $error = "New passwords do not match.";
    } elseif (strlen($new_pwd) < 6) {
        $error = "Password must be at least 6 characters.";
    } else {
        $hashed = password_hash($new_pwd, PASSWORD_DEFAULT);
        $conn->query("UPDATE DeliveryAgent SET Password = '$hashed' WHERE DeliveryAgentID = $agent_id");
        set_success_message("Password changed successfully!");
        header("Location: ../delivery/profile.php");
        exit();
    }
}
?>
<?php $page_title = "My Profile"; include '../includes/delivery-header.php'; ?>

<div class="container-fluid py-4 px-4">

    <div class="mb-4">
        <h4 class="fw-bold mb-1"><i class="bi bi-person me-2 text-warning"></i>My Profile</h4>
        <p class="text-muted mb-0 small">Manage your account details and password</p>
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
        <div class="col-lg-8">

            <!-- Profile Form (NO location fields) -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white fw-semibold py-3">
                    <i class="bi bi-person-circle me-2 text-warning"></i>Personal Information
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">First Name <span class="text-danger">*</span></label>
                                <input type="text" name="first_name" class="form-control"
                                    value="<?php echo htmlspecialchars($agent['FirstName']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Last Name <span class="text-danger">*</span></label>
                                <input type="text" name="last_name" class="form-control"
                                    value="<?php echo htmlspecialchars($agent['LastName']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Email</label>
                                <input type="email" class="form-control bg-light"
                                    value="<?php echo htmlspecialchars($agent['Email']); ?>" disabled>
                                <small class="text-muted">Email cannot be changed</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Phone Number <span class="text-danger">*</span></label>
                                <input type="text" name="phone" class="form-control"
                                    value="<?php echo htmlspecialchars($agent['PhoneNumber']); ?>" required>
                            </div>

                            <div class="col-12 mt-4">
                                <button type="submit" name="update_profile"
                                    class="btn text-white fw-semibold" style="background-color:#fd7e14;">
                                    <i class="bi bi-check-circle me-2"></i>Save Changes
                                </button>
                                <a href="update-location.php" class="btn btn-outline-warning fw-semibold ms-2">
                                    <i class="bi bi-geo-alt me-1"></i>Update Location
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Change Password -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-semibold py-3">
                    <i class="bi bi-shield-lock me-2 text-warning"></i>Change Password
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label fw-semibold small">Current Password</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-lock text-muted"></i></span>
                                    <input type="password" name="current_password" id="current_password"
                                        class="form-control" placeholder="••••••••" required>
                                    <button class="btn btn-outline-secondary" type="button"
                                        onclick="togglePassword('current_password','eye1')">
                                        <i class="bi bi-eye" id="eye1"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">New Password</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-lock text-muted"></i></span>
                                    <input type="password" name="new_password" id="new_password"
                                        class="form-control" placeholder="••••••••" required>
                                    <button class="btn btn-outline-secondary" type="button"
                                        onclick="togglePassword('new_password','eye2')">
                                        <i class="bi bi-eye" id="eye2"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Confirm New Password</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-lock text-muted"></i></span>
                                    <input type="password" name="confirm_password" id="confirm_password"
                                        class="form-control" placeholder="••••••••" required>
                                    <button class="btn btn-outline-secondary" type="button"
                                        onclick="togglePassword('confirm_password','eye3')">
                                        <i class="bi bi-eye" id="eye3"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-12">
                                <button type="submit" name="change_password" class="btn btn-dark fw-semibold">
                                    <i class="bi bi-shield-check me-2"></i>Change Password
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

        </div>

        <div class="col-lg-4">
            <!-- Agent Card (still shows location info, but read-only) -->
            <div class="card border-0 shadow-sm mb-4 text-center">
                <div class="card-body py-4">
                    <div class="text-white rounded-circle d-inline-flex align-items-center
                        justify-content-center mb-3"
                        style="width:72px;height:72px;font-size:1.8rem;font-weight:700;background-color:#fd7e14;">
                        <?php echo strtoupper(substr($agent['FirstName'], 0, 1)); ?>
                    </div>
                    <h5 class="fw-bold mb-1">
                        <?php echo htmlspecialchars($agent['FirstName'].' '.$agent['LastName']); ?>
                    </h5>
                    <p class="text-muted small mb-2"><?php echo htmlspecialchars($agent['Email']); ?></p>

                    <p class="text-muted small mb-1">
                        <i class="bi bi-geo-alt me-1"></i>
                        <?php echo htmlspecialchars($agent['Location'] ?? 'Location not set'); ?>
                    </p>
                    <?php if (!empty($agent['LocationLat']) && !empty($agent['LocationLng'])): ?>
                    <p class="text-muted small mb-3 fst-italic">
                        📍 GPS: <?php echo $agent['LocationLat']; ?>, <?php echo $agent['LocationLng']; ?>
                    </p>
                    <?php endif; ?>

                    <div class="d-flex justify-content-center gap-1 mb-1">
                        <?php for($i=1;$i<=5;$i++): ?>
                            <i class="bi bi-star<?php echo $i <= round($avg_rating['avg'] ?? 0) ? '-fill text-warning':' text-muted'; ?>"></i>
                        <?php endfor; ?>
                    </div>
                    <p class="text-muted small mb-0">
                        <?php echo $avg_rating['avg'] ?? 'No'; ?> avg
                        (<?php echo $avg_rating['total']; ?> review<?php echo $avg_rating['total'] != 1 ? 's':''; ?>)
                    </p>
                </div>
            </div>

            <!-- Recent Ratings -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-semibold py-3">
                    <i class="bi bi-star me-2 text-warning"></i>Recent Ratings
                </div>
                <div class="card-body p-0">
                    <?php if ($ratings && $ratings->num_rows > 0): ?>
                    <div class="list-group list-group-flush">
                        <?php while($r = $ratings->fetch_assoc()): ?>
                        <div class="list-group-item px-3 py-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <p class="mb-1 small fw-semibold"><?php echo htmlspecialchars($r['CustomerName']); ?></p>
                                    <?php if (!empty($r['FeedbackComment'])): ?>
                                        <p class="mb-0 small fst-italic text-muted">"<?php echo htmlspecialchars($r['FeedbackComment']); ?>"</p>
                                    <?php endif; ?>
                                </div>
                                <div class="d-flex gap-1">
                                    <?php for($i=1;$i<=5;$i++): ?>
                                        <i class="bi bi-star<?php echo $i <= $r['RatingScore'] ? '-fill text-warning':''; ?>"
                                            style="font-size:0.75rem;"></i>
                                    <?php endfor; ?>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-star fs-2 d-block mb-2"></i>No ratings yet
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/delivery-footer.php'; ?>
