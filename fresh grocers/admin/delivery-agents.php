<?php
$page_title = "Manage Delivery Agents";
include '../includes/admin-header.php';

$success_msg = "";
$error_msg = "";

if (!function_exists('clean_input_data')) {
    function clean_input_data($data) {
        global $conn;
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $conn->real_escape_string($data);
    }
}

// Handle Delivery Agent Creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_agent'])) {
    $first = clean_input_data($_POST['fname']);
    $last  = clean_input_data($_POST['lname']);
    $phone = clean_input_data($_POST['phone']);
    $email = clean_input_data($_POST['email']);
    $hash  = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Check if phone or email exists
    $check = $conn->query("SELECT DeliveryAgentID FROM DeliveryAgent WHERE PhoneNumber = '$phone' OR Email = '$email'");
    if ($check && $check->num_rows > 0) {
        $error_msg = "Phone Number or Email already exists!";
    } else {
        $stmt = $conn->prepare("INSERT INTO DeliveryAgent (FirstName, LastName, PhoneNumber, Email, Password, IsActive) VALUES (?, ?, ?, ?, ?, 1)");
        if ($stmt) {
            $stmt->bind_param("sssss", $first, $last, $phone, $email, $hash);
            $stmt->execute() ? $success_msg = "Delivery Agent added successfully." : $error_msg = "Error adding agent.";
            $stmt->close();
        } else {
            $error_msg = "Database prepare error: " . $conn->error;
        }
    }
}

// Handle Delivery Agent Update (Edit)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_agent'])) {
    $id     = (int)$_POST['edit_id'];
    $first  = clean_input_data($_POST['edit_fname']);
    $last   = clean_input_data($_POST['edit_lname']);
    $phone  = clean_input_data($_POST['edit_phone']);
    $email  = clean_input_data($_POST['edit_email']);
    $status = isset($_POST['edit_status']) ? 1 : 0;

    // Check conflict
    $check = $conn->query("SELECT DeliveryAgentID FROM DeliveryAgent WHERE (PhoneNumber = '$phone' OR Email = '$email') AND DeliveryAgentID != $id");
    if ($check && $check->num_rows > 0) {
        $error_msg = "That phone number or email is already taken by another agent!";
    } else {
        if (!empty($_POST['edit_password'])) {
            $hash = password_hash($_POST['edit_password'], PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE DeliveryAgent SET FirstName=?, LastName=?, PhoneNumber=?, Email=?, Password=?, IsActive=? WHERE DeliveryAgentID=?");
            if ($stmt) {
                $stmt->bind_param("sssssii", $first, $last, $phone, $email, $hash, $status, $id);
            }
        } else {
            $stmt = $conn->prepare("UPDATE DeliveryAgent SET FirstName=?, LastName=?, PhoneNumber=?, Email=?, IsActive=? WHERE DeliveryAgentID=?");
            if ($stmt) {
                $stmt->bind_param("ssssii", $first, $last, $phone, $email, $status, $id);
            }
        }

        if ($stmt) {
            $stmt->execute() ? $success_msg = "Agent updated successfully." : $error_msg = "Error updating agent.";
            $stmt->close();
        } else {
            $error_msg = "Database prepare error: " . $conn->error;
        }
    }
}

// Handle Deletion
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM DeliveryAgent WHERE DeliveryAgentID = ?");
    if ($stmt) {
        $stmt->bind_param("i", $id);
        $stmt->execute() ? $success_msg = "Agent deleted successfully." : $error_msg = "Failed to delete agent.";
        $stmt->close();
    }
}

// Fetch agents
$agents = $conn->query("SELECT * FROM DeliveryAgent ORDER BY DeliveryAgentID DESC");
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

        <!-- Header -->
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-2 border-bottom">
            <div class="mb-3 mb-md-0">
                <h3 class="fw-bold mb-1 text-dark"><i class="bi bi-truck me-2 text-success"></i>Delivery Agents</h3>
                <p class="text-muted small mb-0">Manage fleet accounts, contact info, and active status.</p>
            </div>
            <button class="btn btn-success shadow-sm fw-medium" data-bs-toggle="modal" data-bs-target="#addAgentModal">
                <i class="bi bi-person-plus-fill me-1"></i> Add Agent
            </button>
        </div>

        <!-- Table -->
        <div class="table-container d-flex flex-column">
            <div class="table-responsive flex-grow-1">
                <table class="table table-hover mb-0 align-middle">
                    <thead>
                        <tr>
                            <th class="ps-4">ID</th>
                            <th>Agent Name</th>
                            <th>Contact</th>
                            <th>Current Location</th>
                            <th>Joined</th>
                            <th class="pe-4 text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($agents && $agents->num_rows > 0): ?>
                            <?php while($a = $agents->fetch_assoc()): ?>
                            <tr>
                                <td class="ps-4 text-muted fw-bold">#<?php echo $a['DeliveryAgentID']; ?></td>
                                
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="bg-success bg-opacity-10 text-success rounded-circle d-flex align-items-center justify-content-center me-3" style="width:38px; height:38px;">
                                            <i class="bi bi-person-bounding-box fs-5"></i>
                                        </div>
                                        <div>
                                            <span class="fw-bold text-dark d-block"><?php echo htmlspecialchars($a['FirstName'].' '.$a['LastName']); ?></span>
                                            <?php if(isset($a['IsActive']) && $a['IsActive']): ?>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-0" style="font-size: 0.7rem;">Active</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary-subtle text-secondary border px-2 py-0" style="font-size: 0.7rem;">Inactive</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                
                                <td>
                                    <div class="small"><i class="bi bi-telephone text-muted me-1"></i> <?php echo htmlspecialchars($a['PhoneNumber']); ?></div>
                                    <div class="small"><i class="bi bi-envelope text-muted me-1"></i> <?php echo htmlspecialchars($a['Email'] ?? 'N/A'); ?></div>
                                </td>
                                
                                <td class="small text-muted">
                                    <?php if (!empty($a['Location'])): ?>
                                        <i class="bi bi-geo-alt-fill text-danger me-1"></i><?php echo htmlspecialchars($a['Location']); ?>
                                        <?php if (!empty($a['LocationLat']) && !empty($a['LocationLng'])): ?>
                                            <br><span style="font-size:0.7rem;">(<?php echo $a['LocationLat']; ?>, <?php echo $a['LocationLng']; ?>)</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="opacity-50"><i class="bi bi-dash-circle me-1"></i>Not tracked</span>
                                    <?php endif; ?>
                                </td>
                                
                                <td class="small text-muted">
                                    <?php echo date('M d, Y', strtotime($a['CreatedAt'])); ?>
                                </td>

                                <td class="pe-4 text-end">
                                    <div class="btn-group btn-group-sm shadow-sm">
                                        <!-- Edit Trigger -->
                                        <button type="button" class="btn btn-light text-primary border hover-primary"
                                            data-bs-toggle="modal" data-bs-target="#editAgentModal"
                                            data-id="<?php echo $a['DeliveryAgentID']; ?>"
                                            data-fname="<?php echo htmlspecialchars($a['FirstName']); ?>"
                                            data-lname="<?php echo htmlspecialchars($a['LastName']); ?>"
                                            data-phone="<?php echo htmlspecialchars($a['PhoneNumber']); ?>"
                                            data-email="<?php echo htmlspecialchars($a['Email'] ?? ''); ?>"
                                            data-status="<?php echo isset($a['IsActive']) ? $a['IsActive'] : 1; ?>"
                                            title="Edit Agent">
                                            <i class="bi bi-pencil-fill"></i> Edit
                                        </button>
                                        <!-- Delete Trigger -->
                                        <a href="?delete=<?php echo $a['DeliveryAgentID']; ?>" class="btn btn-light text-danger border hover-danger" onclick="return confirm('Permanently remove this agent?');" title="Delete Agent">
                                            <i class="bi bi-trash-fill"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 70px; height: 70px;">
                                        <i class="bi bi-truck fs-1 text-secondary opacity-50"></i>
                                    </div>
                                    <h5 class="fw-bold text-dark">No Agents Found</h5>
                                    <p class="mb-0">Click the button above to register a new delivery driver.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<!-- ================= ADD AGENT MODAL ================= -->
<div class="modal fade" id="addAgentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="delivery-agents.php" class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white border-0">
                <h5 class="modal-title fw-bold"><i class="bi bi-person-plus-fill me-2"></i>Add Delivery Agent</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 bg-light">
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label fw-bold small text-muted text-uppercase">First Name</label><input type="text" name="fname" class="form-control shadow-sm" required></div>
                    <div class="col-md-6"><label class="form-label fw-bold small text-muted text-uppercase">Last Name</label><input type="text" name="lname" class="form-control shadow-sm" required></div>
                    
                    <div class="col-md-6"><label class="form-label fw-bold small text-muted text-uppercase">Phone</label><input type="text" name="phone" class="form-control shadow-sm" required maxlength="15"></div>
                    <div class="col-md-6"><label class="form-label fw-bold small text-muted text-uppercase">Email</label><input type="email" name="email" class="form-control shadow-sm" required></div>
                    
                    <div class="col-12"><label class="form-label fw-bold small text-muted text-uppercase">Password</label><input type="password" name="password" class="form-control shadow-sm" required></div>
                </div>
            </div>
            <div class="modal-footer border-0 bg-white">
                <button type="button" class="btn btn-light border px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" name="save_agent" class="btn btn-success px-4 fw-bold shadow-sm">Add Agent</button>
            </div>
        </form>
    </div>
</div>

<!-- ================= EDIT AGENT MODAL ================= -->
<div class="modal fade" id="editAgentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="delivery-agents.php" class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white border-0">
                <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i>Edit Delivery Agent</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 bg-light">
                <input type="hidden" name="edit_id" id="edit_id">
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label fw-bold small text-muted text-uppercase">First Name</label><input type="text" name="edit_fname" id="edit_fname" class="form-control shadow-sm" required></div>
                    <div class="col-md-6"><label class="form-label fw-bold small text-muted text-uppercase">Last Name</label><input type="text" name="edit_lname" id="edit_lname" class="form-control shadow-sm" required></div>
                    
                    <div class="col-md-6"><label class="form-label fw-bold small text-muted text-uppercase">Phone</label><input type="text" name="edit_phone" id="edit_phone" class="form-control shadow-sm" required maxlength="15"></div>
                    <div class="col-md-6"><label class="form-label fw-bold small text-muted text-uppercase">Email</label><input type="email" name="edit_email" id="edit_email" class="form-control shadow-sm" required></div>
                    
                    <div class="col-12">
                        <div class="form-check form-switch mt-2 mb-2">
                            <input class="form-check-input" type="checkbox" id="edit_status" name="edit_status" value="1">
                            <label class="form-check-label fw-bold small text-muted text-uppercase ms-2" for="edit_status">Account Active</label>
                        </div>
                    </div>

                    <div class="col-12"><label class="form-label fw-bold small text-muted text-uppercase">New Password (leave blank to keep current)</label><input type="password" name="edit_password" id="edit_password" class="form-control shadow-sm"></div>
                </div>
            </div>
            <div class="modal-footer border-0 bg-white">
                <button type="button" class="btn btn-light border px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" name="edit_agent" class="btn btn-primary px-4 fw-bold shadow-sm">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit modal script moved to assets/js/script.js -->

<?php include '../includes/admin-footer.php'; ?>
