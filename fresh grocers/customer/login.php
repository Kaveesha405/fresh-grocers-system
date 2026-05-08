<?php
require_once '../config.php';

if (is_logged_in()) {
    redirect('index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = clean_input($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        $sql    = "SELECT * FROM customer WHERE Email = '$email' LIMIT 1";
        $result = $conn->query($sql);

        if ($result && $result->num_rows > 0) {
            $customer = $result->fetch_assoc();
            if (password_verify($password, $customer['Password'])) {
                $_SESSION['customer_id']    = $customer['CustomerID'];
                $_SESSION['customer_name']  = $customer['FirstName'] . ' ' . $customer['LastName'];
                $_SESSION['customer_email'] = $customer['Email'];
                set_success_message("Welcome back, " . $customer['FirstName'] . "!");
                redirect('index.php');
            } else {
                $error = "Invalid email or password.";
            }
        } else {
            $error = "No account found with that email.";
        }
    }
}
?>
<?php $page_title = "Login"; include '../includes/customer-header.php'; ?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-body p-4">

                    <div class="text-center mb-4">
                        <i class="bi bi-leaf fs-1 text-success"></i>
                        <h3 class="fw-bold mt-2">Customer Login</h3>
                        <p class="text-muted small">Welcome back to Fresh Grocers</p>
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

                    <form method="POST" action="">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light">
                                    <i class="bi bi-envelope text-muted"></i>
                                </span>
                                <input type="email" name="email" class="form-control form-control-lg"
                                    placeholder="you@example.com"
                                    value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                                    required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Password</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light">
                                    <i class="bi bi-lock text-muted"></i>
                                </span>
                                <input type="password" name="password" id="password"
                                    class="form-control form-control-lg"
                                    placeholder="••••••••" required>
                                <button class="btn btn-outline-secondary" type="button"
                                    onclick="togglePassword('password', 'eye-login')">
                                    <i class="bi bi-eye" id="eye-login"></i>
                                </button>
                            </div>
                        </div>

                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-success btn-lg fw-semibold">
                                <i class="bi bi-box-arrow-in-right me-2"></i>Login
                            </button>
                        </div>
                    </form>

                    <hr class="my-4">
                    <p class="text-center mb-0">Don't have an account?
                        <a href="register.php" class="text-success fw-semibold">Register here</a>
                    </p>
                    <p class="text-center mt-2 small text-muted">
                        Are you staff?
                        <a href="../admin/login.php" class="text-muted">Admin</a> ·
                        <a href="../delivery/login.php" class="text-muted">Delivery</a> ·
                        <a href="../csr/login.php" class="text-muted">CSR</a>
                    </p>

                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
