<?php
require_once '../config.php';
if (!is_logged_in()) {
    redirect('login.php');
}
$customer = get_customer_info();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = clean_input($_POST['first_name']);
    $last_name  = clean_input($_POST['last_name']);
    $phone      = clean_input($_POST['phone']);
    $address    = clean_input($_POST['address']);
    $new_pass   = $_POST['new_password'];
    $confirm    = $_POST['confirm_password'];

    if (empty($first_name) || empty($last_name) || empty($phone)) {
        $error = "First name, last name and phone are required.";
    } elseif (!validate_phone($phone)) {
        $error = "Please enter a valid Sri Lankan phone number.";
    } else {
        $customer_id = (int)$_SESSION['customer_id'];

        // Update password if provided
        if (!empty($new_pass)) {
            if (strlen($new_pass) < 6) {
                $error = "Password must be at least 6 characters.";
            } elseif ($new_pass !== $confirm) {
                $error = "Passwords do not match.";
            } else {
                $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
                $conn->query("UPDATE customer SET Password = '$hashed' WHERE CustomerID = $customer_id");
            }
        }

        if (!$error) {
            $sql = "UPDATE customer SET 
                        FirstName = '$first_name',
                        LastName  = '$last_name',
                        PhoneNumber = '$phone',
                        Address   = '$address'
                    WHERE CustomerID = $customer_id";
            if ($conn->query($sql)) {
                $_SESSION['customer_name'] = $first_name . ' ' . $last_name;
                set_success_message("Profile updated successfully!");
                redirect('profile.php');
            } else {
                $error = "Update failed. Please try again.";
            }
        }
    }
}
?>
<?php $page_title = "My Profile"; include '../includes/customer-header.php'; ?>

<div class="container my-4">
    <div class="row justify-content-center">
        <div class="col-md-7">

            <div class="d-flex align-items-center mb-4">
                <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center me-3"
                    style="width:60px;height:60px;font-size:1.5rem;">
                    <?php echo strtoupper(substr($customer['FirstName'], 0, 1)); ?>
                </div>
                <div>
                    <h4 class="fw-bold mb-0"><?php echo htmlspecialchars($customer['FirstName'] . ' ' . $customer['LastName']); ?></h4>
                    <p class="text-muted mb-0 small"><?php echo htmlspecialchars($customer['Email']); ?></p>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="bi bi-exclamation-circle me-2"></i><?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php $msg = get_success_message(); if ($msg): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="bi bi-check-circle me-2"></i><?php echo $msg; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-4">Edit Profile</h5>
                    <form method="POST" action="">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">First Name <span class="text-danger">*</span></label>
                                <input type="text" name="first_name" class="form-control"
                                    value="<?php echo htmlspecialchars($customer['FirstName']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Last Name <span class="text-danger">*</span></label>
                                <input type="text" name="last_name" class="form-control"
                                    value="<?php echo htmlspecialchars($customer['LastName']); ?>" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Email</label>
                                <input type="email" class="form-control bg-light"
                                    value="<?php echo htmlspecialchars($customer['Email']); ?>" disabled>
                                <div class="form-text">Email cannot be changed.</div>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Phone Number <span class="text-danger">*</span></label>
                                <input type="tel" name="phone" class="form-control"
                                    value="<?php echo htmlspecialchars($customer['PhoneNumber']); ?>" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Delivery Address</label>
                                <textarea name="address" class="form-control" rows="2"><?php echo htmlspecialchars($customer['Address']); ?></textarea>
                            </div>

                            <div class="col-12"><hr><h6 class="fw-bold">Change Password <small class="text-muted fw-normal">(leave blank to keep current)</small></h6></div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">New Password</label>
                                <div class="input-group">
                                    <input type="password" name="new_password" id="password"
                                        class="form-control" placeholder="Min. 6 characters">
                                    <button class="btn btn-outline-secondary" type="button"
                                        onclick="togglePassword('password','eye-pwd')">
                                        <i class="bi bi-eye" id="eye-pwd"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Confirm Password</label>
                                <div class="input-group">
                                    <input type="password" name="confirm_password" id="confirm_password"
                                        class="form-control" placeholder="Repeat password">
                                    <button class="btn btn-outline-secondary" type="button"
                                        onclick="togglePassword('confirm_password','eye-confirm')">
                                        <i class="bi bi-eye" id="eye-confirm"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-success btn-lg fw-semibold">
                                <i class="bi bi-save me-2"></i>Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
