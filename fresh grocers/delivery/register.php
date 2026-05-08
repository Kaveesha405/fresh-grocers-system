<?php
require_once '../config.php';

if (isset($_SESSION['agent_id'])) {
    header("Location: ../delivery/index.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = clean_input($_POST['first_name']);
    $last_name  = clean_input($_POST['last_name']);
    $email      = clean_input($_POST['email']);
    $phone      = clean_input($_POST['phone']);

    if (empty($first_name) || empty($last_name) || empty($email) || empty($phone)) {
        $error = "Please fill in all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        // SQL table: DeliveryAgent
        $check = $conn->query("SELECT DeliveryAgentID FROM DeliveryAgent WHERE Email = '$email' LIMIT 1");
        if ($check && $check->num_rows > 0) {
            $error = "An account with this email already exists.";
        } else {
            // CASE STUDY ALIGNMENT: Auto-generate a random 8-character password
            $raw_password = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 8);
            $hashed = password_hash($raw_password, PASSWORD_DEFAULT);
            
            $sql = "INSERT INTO DeliveryAgent (FirstName, LastName, PhoneNumber, Email, Password)
                    VALUES ('$first_name', '$last_name', '$phone', '$email', '$hashed')";

            if ($conn->query($sql)) {
                
                // CASE STUDY ALIGNMENT: Send email with unique username and password
                $to = $email;
                $subject = "Welcome to Fresh Grocers Delivery Team";
                $message = "Hello $first_name,\n\nYour account has been successfully created.\n\nUsername: $email\nPassword: $raw_password\n\nPlease log in and update your delivery location.";
                $headers = "From: no-reply@freshgrocers.lk";
                
                // mail($to, $subject, $message, $headers); // Uncomment if SMTP is configured on server

                // Added 'no-auto-dismiss' so the alert stays visible indefinitely
                $_SESSION['success_message'] = "<div class='no-auto-dismiss'>Account created! An email has been sent to <b>$email</b>. <br><br><b>(Localhost test - Your generated password is: <span class='fs-5 badge bg-dark text-white user-select-all'>$raw_password</span>)</b></div>";
                
                header("Location: ../delivery/login.php");
                exit();
            } else {
                $error = "Registration failed: " . $conn->error;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Register - Fresh Grocers</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $BASE_URL; ?>assets/css/style.css">
</head>
<body class="bg-light">

<div class="min-vh-100 d-flex align-items-center justify-content-center py-5">
    <div class="col-md-6 col-sm-11">

        <div class="text-center mb-4">
            <div class="text-white rounded-circle d-inline-flex align-items-center
                justify-content-center mb-3"
                style="width:64px;height:64px;background-color:#fd7e14;">
                <i class="bi bi-truck fs-2"></i>
            </div>
            <h4 class="fw-bold">Create Delivery Account</h4>
            <p class="text-muted small">Join the Fresh Grocers Delivery Team</p>
        </div>

        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4">

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="bi bi-exclamation-circle me-2"></i><?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="alert alert-info small mb-4">
                    <i class="bi bi-info-circle me-2"></i>
                    As per company policy, your secure password will be auto-generated and sent to your email address upon registration.
                </div>

                <form method="POST" action="">

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">
                                First Name <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light">
                                    <i class="bi bi-person text-muted"></i>
                                </span>
                                <input type="text" name="first_name" class="form-control"
                                    placeholder="Kasun"
                                    value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>"
                                    required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">
                                Last Name <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light">
                                    <i class="bi bi-person text-muted"></i>
                                </span>
                                <input type="text" name="last_name" class="form-control"
                                    placeholder="Perera"
                                    value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>"
                                    required>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">
                            Email Address <span class="text-danger">*</span>
                        </label>
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
                        <label class="form-label fw-semibold small">
                            Phone Number <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-light">
                                <i class="bi bi-telephone text-muted"></i>
                            </span>
                            <input type="text" name="phone" class="form-control"
                                placeholder="07X XXX XXXX"
                                value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>"
                                required>
                        </div>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-lg fw-semibold text-white"
                            style="background-color:#fd7e14;">
                            <i class="bi bi-person-plus me-2"></i>Register & Email Password
                        </button>
                    </div>

                </form>
            </div>
        </div>

        <div class="text-center mt-3 small">
            <span class="text-muted">Already have an account?</span>
            <a href="login.php" class="fw-semibold text-decoration-none ms-1"
                style="color:#fd7e14;">Login here</a>
        </div>
        <div class="text-center mt-2 small">
            <a href="../customer/index.php" class="text-muted text-decoration-none">
                <i class="bi bi-arrow-left me-1"></i>Back to Store
            </a>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo $BASE_URL; ?>assets/js/script.js"></script>
</body>
</html>
