<?php
require_once '../config.php';

if (isset($_SESSION['csr_id'])) {
    header("Location: ../csr/index.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = clean_input($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        $result = $conn->query("SELECT * FROM CSR WHERE Email = '$email' LIMIT 1");

        if ($result && $result->num_rows > 0) {
            $csr = $result->fetch_assoc();
            if (password_verify($password, $csr['Password'])) {
                $_SESSION['csr_id']    = $csr['CSRID'];
                $_SESSION['csr_name']  = $csr['FirstName'] . ' ' . $csr['LastName'];
                $_SESSION['csr_email'] = $csr['Email'];
                set_success_message("Welcome back, " . $csr['FirstName'] . "!");
                header("Location: ../csr/index.php");
                exit();
            } else {
                $error = "Invalid email or password.";
            }
        } else {
            $error = "No CSR account found with that email.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CSR Login - Fresh Grocers</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $BASE_URL; ?>assets/css/style.css">
</head>
<body class="bg-light">

<div class="min-vh-100 d-flex align-items-center justify-content-center py-4">
    <div class="col-md-4 col-sm-10">

        <div class="text-center mb-4">
            <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center
                justify-content-center mb-3" style="width:64px;height:64px;">
                <i class="bi bi-headset fs-2"></i>
            </div>
            <h4 class="fw-bold">CSR Agent Login</h4>
            <p class="text-muted small">Fresh Grocers Customer Service Portal</p>
        </div>

        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4">

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
                            <input type="email" name="email" class="form-control"
                                placeholder="csr@freshgrocers.lk"
                                value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                                required>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light">
                                <i class="bi bi-lock text-muted"></i>
                            </span>
                            <input type="password" name="password" id="password"
                                class="form-control" placeholder="••••••••" required>
                            <button class="btn btn-outline-secondary" type="button"
                                onclick="togglePassword('password','eye-icon')">
                                <i class="bi bi-eye" id="eye-icon"></i>
                            </button>
                        </div>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg fw-semibold">
                            <i class="bi bi-box-arrow-in-right me-2"></i>Login
                        </button>
                    </div>
                </form>

            </div>
        </div>

        <p class="text-center mt-3 small">
            <a href="../customer/index.php" class="text-muted text-decoration-none">
                <i class="bi bi-arrow-left me-1"></i>Back to Store
            </a>
        </p>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo $BASE_URL; ?>assets/js/script.js"></script>
</body>
</html>
