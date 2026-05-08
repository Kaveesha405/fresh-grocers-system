<?php
require_once '../config.php';

// Redirect to dashboard if already logged in
if (isset($_SESSION['admin_id'])) { 
    header("Location: index.php"); 
    exit(); 
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // FIX: Use ?? '' to safely handle missing POST keys
    $email    = clean_input($_POST['email']    ?? '');
    $password = trim($_POST['password']        ?? '');

    // Validate fields are not empty
    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password.";
    } else {
        // Query using Email column
        $stmt = $conn->prepare("SELECT AdminID, Password FROM Admin WHERE Email = ?");
        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $res = $stmt->get_result();
            
            if ($res->num_rows > 0) {
                $admin = $res->fetch_assoc();
                
                // Check password (supports hashed OR plain text for testing)
                if (password_verify($password, $admin['Password']) || $password === $admin['Password']) {
                    $_SESSION['admin_id'] = $admin['AdminID'];
                    header("Location: index.php");
                    exit();
                } else { 
                    $error = "Incorrect password. Please try again."; 
                }
            } else {
                $error = "No admin account found with this email."; 
            }
        } else {
            $error = "Database error: " . $conn->error;
        }
    }
}

// FIX: Added the missing '$' before page_title
$page_title = "Admin Login";
include '../includes/admin-header.php';
?>

<!-- Styles moved to assets/css/style.css -->

<div class="container login-wrapper">
    <div class="col-md-6 col-lg-4">
        <div class="card login-card">
            <div class="login-header">
                <div class="logo-circle"><i class="bi bi-shield-lock-fill"></i></div>
                <h4 class="fw-bold mb-1">Admin Portal</h4>
                <p class="mb-0 text-white-50 small">Secure Access Only</p>
            </div>

            <div class="card-body p-4 p-md-5">
                <?php if($error): ?>
                    <div class="alert alert-danger d-flex align-items-center p-3 mb-4 rounded-3 shadow-sm" role="alert">
                        <i class="bi bi-exclamation-triangle-fill fs-5 me-3 text-danger"></i>
                        <div class="fw-semibold small"><?php echo htmlspecialchars($error); ?></div>
                    </div>
                <?php endif; ?>

                <form method="POST" novalidate>
                    <!-- EMAIL FIELD -->
                    <div class="mb-4">
                        <label class="form-label fw-bold text-muted small text-uppercase mb-2">Email Address</label>
                        <div class="input-group input-group-lg border rounded-3">
                            <span class="input-group-text"><i class="bi bi-envelope-fill"></i></span>
                            <input 
                                type="email" 
                                name="email" 
                                class="form-control" 
                                placeholder="Enter admin email"
                                value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                required 
                                autofocus>
                        </div>
                    </div>

                    <!-- PASSWORD FIELD -->
                    <div class="mb-5">
                        <label class="form-label fw-bold text-muted small text-uppercase mb-2">Password</label>
                        <div class="input-group input-group-lg border rounded-3">
                            <span class="input-group-text"><i class="bi bi-key-fill"></i></span>
                            <input 
                                type="password" 
                                name="password" 
                                id="adminPwd" 
                                class="form-control border-end-0" 
                                placeholder="Enter password" 
                                required>
                            <button class="btn pwd-toggle-btn text-muted" type="button" onclick="toggleLocalPassword()">
                                <i class="bi bi-eye-fill" id="pwdIcon"></i>
                            </button>
                        </div>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-success btn-login fw-bold">
                            Login to Dashboard <i class="bi bi-box-arrow-in-right ms-2"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="text-center mt-4">
            <a href="../index.php" class="text-muted text-decoration-none fw-semibold small">
                <i class="bi bi-arrow-left me-1"></i> Return to Main Website
            </a>
        </div>
    </div>
</div>

<!-- Admin login JS moved to assets/js/script.js -->

<?php include '../includes/admin-footer.php'; ?>
