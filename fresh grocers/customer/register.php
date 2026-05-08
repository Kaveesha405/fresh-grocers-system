<?php
require_once '../config.php';

if (is_logged_in()) {
    redirect('index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = clean_input($_POST['first_name']);
    $last_name  = clean_input($_POST['last_name']);
    $email      = clean_input($_POST['email']);
    $phone      = clean_input($_POST['phone']);
    $address    = clean_input($_POST['address']);

    if (empty($first_name) || empty($last_name) || empty($email) || empty($phone)) {
        $error = "Please fill in all required fields.";
    } elseif (!validate_email($email)) {
        $error = "Please enter a valid email address.";
    } elseif (!validate_phone($phone)) {
        $error = "Please enter a valid Sri Lankan phone number (e.g. 0771234567).";
    } else {
        $check = $conn->query("SELECT CustomerID FROM Customer WHERE Email = '$email' LIMIT 1");
        if ($check && $check->num_rows > 0) {
            $error = "An account with this email already exists. <a href='login.php'>Login instead</a>";
        } else {
            // CASE STUDY (a): Auto-generate password and simulate email
            $raw_password = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 8);
            $hashed = password_hash($raw_password, PASSWORD_DEFAULT);

            $sql = "INSERT INTO Customer (FirstName, LastName, Email, PhoneNumber, Address, Password)
                    VALUES ('$first_name', '$last_name', '$email', '$phone', '$address', '$hashed')";

            if ($conn->query($sql)) {
                // Simulate sending email with credentials
                $to      = $email;
                $subject = "Welcome to Fresh Grocers";
                $body    = "Hello $first_name,\n\nYour account has been created.\n\nUsername: $email\nPassword: $raw_password\n\nLogin at: http://localhost/fresh-grocers/customer/login.php";
                $headers = "From: no-reply@freshgrocers.lk";
                // mail($to, $subject, $body, $headers); // Uncomment when SMTP is configured

                $_SESSION['success_message'] = "<div class='no-auto-dismiss'>
                    <i class='bi bi-check-circle me-2'></i>Account created! An email has been sent to <b>$email</b> with your login credentials.<br><br>
                    <b>(Localhost Test — Simulated Email):</b><br>
                    <span class='d-block mt-2 p-2 bg-dark text-white rounded small'>
                        Username: $email<br>
                        Password: <span class='user-select-all fw-bold fs-5'>$raw_password</span>
                    </span>
                    <small class='text-muted'>Click anywhere on the password above to select it.</small>
                </div>";

                redirect('login.php');
            } else {
                $error = "Registration failed. Please try again. " . $conn->error;
            }
        }
    }
}
?>
<?php $page_title = "Register"; include '../includes/customer-header.php'; ?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-body p-4">

                    <div class="text-center mb-4">
                        <i class="bi bi-person-plus fs-1 text-success"></i>
                        <h3 class="fw-bold mt-2">Create Account</h3>
                        <p class="text-muted small">Join Fresh Grocers today</p>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <i class="bi bi-exclamation-circle me-2"></i><?php echo $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <div class="alert alert-info small mb-4">
                        <i class="bi bi-info-circle me-2"></i>
                        Your secure password will be <strong>auto-generated</strong> and sent to your email after registration.
                    </div>

                    <form method="POST" action="">
                        <div class="row g-3">

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">
                                    First Name <span class="text-danger">*</span>
                                </label>
                                <input type="text" name="first_name" class="form-control"
                                    value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>"
                                    placeholder="Kaveesha" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">
                                    Last Name <span class="text-danger">*</span>
                                </label>
                                <input type="text" name="last_name" class="form-control"
                                    value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>"
                                    placeholder="Amiru" required>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold">
                                    Email Address <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">
                                        <i class="bi bi-envelope text-muted"></i>
                                    </span>
                                    <input type="email" name="email" class="form-control"
                                        value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                                        placeholder="you@example.com" required>
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold">
                                    Phone Number <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">
                                        <i class="bi bi-telephone text-muted"></i>
                                    </span>
                                    <input type="tel" name="phone" class="form-control"
                                        value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>"
                                        placeholder="0771234567" required>
                                </div>
                                <div class="form-text">Sri Lankan format: 07XXXXXXXX</div>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold">Delivery Address</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">
                                        <i class="bi bi-geo-alt text-muted"></i>
                                    </span>
                                    <textarea name="address" class="form-control" rows="2"
                                        placeholder="No. 123, Main Street, Colombo"><?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?></textarea>
                                </div>
                            </div>

                        </div>

                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-success btn-lg fw-semibold">
                                <i class="bi bi-person-plus me-2"></i>Register & Email Password
                            </button>
                        </div>
                    </form>

                    <hr class="my-3">
                    <p class="text-center mb-0">
                        Already have an account?
                        <a href="login.php" class="text-success fw-semibold">Login here</a>
                    </p>

                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
