<?php
$page_title = "Manage CSRs";
include '../includes/admin-header.php';

$success_msg = "";
$error_msg = "";

// Handle CSR Creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_csr'])) {
    $first = clean_input_data($_POST['fname']);
    $last  = clean_input_data($_POST['lname']);
    $user  = clean_input_data($_POST['username']);
    $email = clean_input_data($_POST['email']);
    $hash  = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $check = $conn->query("SELECT CSRID FROM CSR WHERE Username = '$user'");
    if ($check->num_rows > 0) {
        $error_msg = "A CSR with that username already exists!";
    } else {
        $stmt = $conn->prepare("INSERT INTO CSR (FirstName, LastName, Username, Email, Password) VALUES (?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("sssss", $first, $last, $user, $email, $hash);
            $stmt->execute() ? $success_msg = "CSR account created successfully." : $error_msg = "Error creating account.";
            $stmt->close();
        }
    }
}

// Handle CSR Update (Edit)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_csr'])) {
    $id    = (int)$_POST['edit_id'];
    $first = clean_input_data($_POST['edit_fname']);
    $last  = clean_input_data($_POST['edit_lname']);
    $user  = clean_input_data($_POST['edit_username']);
    $email = clean_input_data($_POST['edit_email']);

    // Check if the username is taken by ANOTHER CSR
    $check = $conn->query("SELECT CSRID FROM CSR WHERE Username = '$user' AND CSRID != $id");
    if ($check->num_rows > 0) {
        $error_msg = "That username is already taken by another CSR!";
    } else {
        // If a new password was provided, update it too
        if (!empty($_POST['edit_password'])) {
            $hash = password_hash($_POST['edit_password'], PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE CSR SET FirstName=?, LastName=?, Username=?, Email=?, Password=? WHERE CSRID=?");
            $stmt->bind_param("sssssi", $first, $last, $user, $email, $hash, $id);
        } else {
            // Keep existing password
            $stmt = $conn->prepare("UPDATE CSR SET FirstName=?, LastName=?, Username=?, Email=? WHERE CSRID=?");
            $stmt->bind_param("ssssi", $first, $last, $user, $email, $id);
        }

        if ($stmt) {
            $stmt->execute() ? $success_msg = "CSR account updated successfully." : $error_msg = "Error updating account.";
            $stmt->close();
        }
    }
}

// Handle CSR Deletion
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM CSR WHERE CSRID = ?");
    if ($stmt) {
        $stmt->bind_param("i", $id);
        $stmt->execute() ? $success_msg = "CSR account deleted." : $error_msg = "Failed to delete CSR.";
        $stmt->close();
    }
}

$csrs = $conn->query("SELECT * FROM CSR ORDER BY CSRID DESC");
?>

<!-- Styles moved to assets/css/style.css -->

<div class="dashboard-bg">
    <div class="container-fluid py-4 px-4 d-flex flex-column flex-grow-1">

        <!-- Alerts -->
        <?php if ($success_msg): ?>
            <div class="alert alert-success alert-dismissible fade show shadow-sm border-0">
                <i class="bi bi-check-circle-fill me-2"></i> <?php echo $success_msg; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if ($error_msg): ?>
            <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $error_msg; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Page Header -->
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-2 border-bottom">
            <div class="mb-3 mb-md-0">
                <h3 class="fw-bold mb-1 text-dark"><i class="bi bi-headset me-2 text-info"></i>Customer Service Reps</h3>
                <p class="text-muted small mb-0">Manage customer support staff access and accounts.</p>
            </div>
            <button class="btn btn-info text-white shadow-sm fw-medium" data-bs-toggle="modal" data-bs-target="#addCsrModal">
                <i class="bi bi-person-plus-fill me-1"></i> Add New CSR
            </button>
        </div>

        <!-- Table -->
        <div class="table-container d-flex flex-column">
            <div class="table-responsive flex-grow-1">
                <table class="table table-hover mb-0 align-middle">
                    <thead>
                        <tr>
                            <th class="ps-4">ID</th>
                            <th>Staff Name</th>
                            <th>Username</th>
                            <th>Email Address</th>
                            <th class="pe-4 text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($csrs && $csrs->num_rows > 0): ?>
                            <?php while($c = $csrs->fetch_assoc()): ?>
                            <tr>
                                <td class="ps-4 text-muted fw-bold">#<?php echo $c['CSRID']; ?></td>

                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="bg-info bg-opacity-10 text-info rounded-circle d-flex align-items-center justify-content-center me-3" style="width:38px; height:38px;">
                                            <i class="bi bi-person-badge-fill fs-5"></i>
                                        </div>
                                        <span class="fw-bold text-dark"><?php echo htmlspecialchars($c['FirstName'].' '.$c['LastName']); ?></span>
                                    </div>
                                </td>

                                <td>
                                    <span class="badge bg-light text-dark border px-2 py-1 rounded-2">
                                        @<?php echo htmlspecialchars($c['Username']); ?>
                                    </span>
                                </td>

                                <td class="text-muted">
                                    <i class="bi bi-envelope me-1"></i> <?php echo htmlspecialchars($c['Email']); ?>
                                </td>

                                <td class="pe-4 text-end">
                                    <div class="btn-group btn-group-sm shadow-sm">
                                        <!-- Edit Button triggers edit modal with data -->
                                        <button type="button"
                                            class="btn btn-light text-primary border hover-primary"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editCsrModal"
                                            data-id="<?php echo $c['CSRID']; ?>"
                                            data-fname="<?php echo htmlspecialchars($c['FirstName']); ?>"
                                            data-lname="<?php echo htmlspecialchars($c['LastName']); ?>"
                                            data-username="<?php echo htmlspecialchars($c['Username']); ?>"
                                            data-email="<?php echo htmlspecialchars($c['Email']); ?>"
                                            title="Edit CSR">
                                            <i class="bi bi-pencil-fill"></i> Edit
                                        </button>
                                        <!-- Delete Button -->
                                        <a href="?delete=<?php echo $c['CSRID']; ?>"
                                            class="btn btn-light text-danger border hover-danger"
                                            onclick="return confirm('Permanently delete this CSR account?');"
                                            title="Delete CSR">
                                            <i class="bi bi-trash-fill"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 70px; height: 70px;">
                                        <i class="bi bi-people fs-1 text-secondary opacity-50"></i>
                                    </div>
                                    <h5 class="fw-bold text-dark">No CSR accounts found</h5>
                                    <p class="mb-0">Click the button above to add your first customer service representative.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>


<!-- ===================== ADD CSR MODAL ===================== -->
<div class="modal fade" id="addCsrModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="csr.php" class="modal-content border-0 shadow">
            <div class="modal-header bg-info text-white border-0">
                <h5 class="modal-title fw-bold"><i class="bi bi-person-plus-fill me-2"></i>Add New CSR</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold small text-muted text-uppercase">First Name</label>
                        <input type="text" name="fname" class="form-control shadow-sm" required placeholder="e.g. Saman">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small text-muted text-uppercase">Last Name</label>
                        <input type="text" name="lname" class="form-control shadow-sm" required placeholder="e.g. Perera">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold small text-muted text-uppercase">Username</label>
                        <div class="input-group shadow-sm">
                            <span class="input-group-text bg-white border-end-0"><i class="bi bi-at"></i></span>
                            <input type="text" name="username" class="form-control border-start-0 ps-0" required placeholder="csr_saman">
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold small text-muted text-uppercase">Email Address</label>
                        <div class="input-group shadow-sm">
                            <span class="input-group-text bg-white border-end-0"><i class="bi bi-envelope"></i></span>
                            <input type="email" name="email" class="form-control border-start-0 ps-0" required placeholder="saman@freshgrocers.lk">
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold small text-muted text-uppercase">Password</label>
                        <div class="input-group shadow-sm">
                            <span class="input-group-text bg-white border-end-0"><i class="bi bi-lock-fill"></i></span>
                            <input type="password" name="password" class="form-control border-start-0 ps-0" required placeholder="Create a secure password">
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 bg-light">
                <button type="button" class="btn btn-light border px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" name="save_csr" class="btn btn-info text-white px-4 fw-bold">
                    <i class="bi bi-person-check-fill me-1"></i> Create Account
                </button>
            </div>
        </form>
    </div>
</div>


<!-- ===================== EDIT CSR MODAL ===================== -->
<div class="modal fade" id="editCsrModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="csr.php" class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white border-0">
                <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i>Edit CSR Account</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <!-- Hidden ID -->
                <input type="hidden" name="edit_id" id="edit_id">

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold small text-muted text-uppercase">First Name</label>
                        <input type="text" name="edit_fname" id="edit_fname" class="form-control shadow-sm" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small text-muted text-uppercase">Last Name</label>
                        <input type="text" name="edit_lname" id="edit_lname" class="form-control shadow-sm" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold small text-muted text-uppercase">Username</label>
                        <div class="input-group shadow-sm">
                            <span class="input-group-text bg-white border-end-0"><i class="bi bi-at"></i></span>
                            <input type="text" name="edit_username" id="edit_username" class="form-control border-start-0 ps-0" required>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold small text-muted text-uppercase">Email Address</label>
                        <div class="input-group shadow-sm">
                            <span class="input-group-text bg-white border-end-0"><i class="bi bi-envelope"></i></span>
                            <input type="email" name="edit_email" id="edit_email" class="form-control border-start-0 ps-0" required>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold small text-muted text-uppercase">
                            New Password <span class="text-muted fw-normal normal-case">(leave blank to keep current)</span>
                        </label>
                        <div class="input-group shadow-sm">
                            <span class="input-group-text bg-white border-end-0"><i class="bi bi-lock-fill"></i></span>
                            <input type="password" name="edit_password" id="edit_password" class="form-control border-start-0 ps-0" placeholder="Leave blank to keep existing">
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 bg-light">
                <button type="button" class="btn btn-light border px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" name="edit_csr" class="btn btn-primary px-4 fw-bold">
                    <i class="bi bi-save-fill me-1"></i> Save Changes
                </button>
            </div>
        </form>
    </div>
</div>


<!-- Edit modal script moved to assets/js/script.js -->

<?php include '../includes/admin-footer.php'; ?>
