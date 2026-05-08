<?php
require_once '../config.php';

if (isset($_SESSION['agent_id'])) {
    header("Location: ../delivery/index.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = clean_input($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        // SQL table: DeliveryAgent
        $result = $conn->query("SELECT * FROM DeliveryAgent WHERE Email = '$email' LIMIT 1");

        if ($result && $result->num_rows > 0) {
            $agent = $result->fetch_assoc();
            if (password_verify($password, $agent['Password'])) {
                // SQL PK: DeliveryAgentID
                $_SESSION['agent_id']    = $agent['DeliveryAgentID'];
                $_SESSION['agent_name']  = $agent['FirstName'] . ' ' . $agent['LastName'];
                $_SESSION['agent_email'] = $agent['Email'];
                
                set_success_message("Welcome back, " . $agent['FirstName'] . "!");
                header("Location: ../delivery/index.php");
                exit();
            } else {
                $error = "Invalid email or password.";
            }
        } else {
            $error = "No delivery agent account found with that email.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Login - Fresh Grocers</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $BASE_URL; ?>assets/css/style.css">
</head>
<body class="bg-light">

<div class="min-vh-100 d-flex align-items-center justify-content-center py-4">
    <div class="col-md-4 col-sm-10">

        <div class="text-center mb-4">
            <div class="text-white rounded-circle d-inline-flex align-items-center
                justify-content-center mb-3"
                style="width:64px;height:64px;background-color:#fd7e14;">
                <i class="bi bi-truck fs-2"></i>
            </div>
            <h4 class="fw-bold">Delivery Agent Login</h4>
            <p class="text-muted small">Fresh Grocers Delivery Portal</p>
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
                                placeholder="agent@freshgrocers.lk"
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
                    <div class="d-grid mb-3">
                        <button type="submit" class="btn btn-lg fw-semibold text-white"
                            style="background-color:#fd7e14;">
                            <i class="bi bi-box-arrow-in-right me-2"></i>Login
                        </button>
                    </div>
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <hr class="flex-grow-1">
                        <small class="text-muted">OR</small>
                        <hr class="flex-grow-1">
                    </div>
                    <div class="d-grid">
                        <a href="register.php" class="btn btn-outline-secondary fw-semibold">
                            <i class="bi bi-person-plus me-2"></i>Create New Account
                        </a>
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
