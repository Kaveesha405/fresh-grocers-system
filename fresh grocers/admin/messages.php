<?php
$page_title = "Contact Messages";
include '../includes/admin-header.php';

$success_msg = "";
$error_msg = "";

// Helpers (in case your project doesn't have these everywhere)
if (!function_exists('set_success_message')) {
    function set_success_message($msg) { $_SESSION['success_message'] = $msg; }
}
if (!function_exists('get_success_message')) {
    function get_success_message() {
        if (!empty($_SESSION['success_message'])) {
            $m = $_SESSION['success_message'];
            unset($_SESSION['success_message']);
            return $m;
        }
        return null;
    }
}

// Check if Message table exists
$tableExistsRes = $conn->query("SHOW TABLES LIKE 'Message'");
$tableExists = ($tableExistsRes && $tableExistsRes->num_rows > 0);

// Delete message
if ($tableExists && isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM Message WHERE MessageID = ?");
    if ($stmt) {
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            set_success_message("Message deleted successfully.");
        } else {
            set_success_message("Failed to delete message.");
        }
        $stmt->close();
    } else {
        set_success_message("Database error: " . $conn->error);
    }
    header("Location: messages.php");
    exit();
}

// Load messages (join Customer for name/email)
$messages = null;
if ($tableExists) {
    $sql = "
        SELECT
            m.MessageID,
            m.MessageDate,
            m.Subject,
            m.Content,
            m.CustomerID,
            c.FirstName,
            c.LastName,
            c.Email AS CustomerEmail,
            c.PhoneNumber AS CustomerPhone
        FROM Message m
        LEFT JOIN Customer c ON c.CustomerID = m.CustomerID
        ORDER BY m.MessageDate DESC, m.MessageID DESC
    ";
    $messages = $conn->query($sql);
}
?>

<!-- Styles moved to assets/css/style.css -->

<div class="dashboard-bg">
    <div class="container-fluid py-4 px-4 d-flex flex-column flex-grow-1">

        <?php $msg = get_success_message(); ?>
        <?php if ($msg): ?>
            <div class="alert alert-success alert-dismissible fade show shadow-sm border-0" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i><?php echo htmlspecialchars($msg); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-2 border-bottom">
            <div class="mb-3 mb-md-0">
                <h3 class="fw-bold mb-1 text-dark"><i class="bi bi-envelope-paper me-2 text-success"></i>Customer Messages</h3>
                <p class="text-muted small mb-0">View, reply, and remove customer inquiries.</p>
            </div>
        </div>

        <?php if (!$tableExists): ?>
            <div class="alert alert-warning shadow-sm border-0">
                <i class="bi bi-info-circle me-2"></i>
                The <strong>Message</strong> table does not exist in your database.
            </div>
        <?php else: ?>

            <div class="table-container d-flex flex-column">
                <div class="table-responsive flex-grow-1">
                    <table class="table table-hover mb-0 align-middle">
                        <thead>
                            <tr>
                                <th class="ps-4">Date</th>
                                <th>Customer</th>
                                <th>Contact</th>
                                <th>Subject</th>
                                <th>Message</th>
                                <th class="pe-4 text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if ($messages && $messages->num_rows > 0): ?>
                            <?php while ($m = $messages->fetch_assoc()): ?>
                                <?php
                                    $customerName = trim(($m['FirstName'] ?? '') . ' ' . ($m['LastName'] ?? ''));
                                    if ($customerName === "") $customerName = "Guest / Unknown";
                                    $email = $m['CustomerEmail'] ?? '';
                                    $phone = $m['CustomerPhone'] ?? '';
                                    $subject = $m['Subject'] ?? '(No subject)';
                                    $content = $m['Content'] ?? '';
                                    $dateVal = !empty($m['MessageDate']) ? date('d M Y, h:ia', strtotime($m['MessageDate'])) : '—';
                                ?>
                                <tr>
                                    <td class="ps-4 text-muted small" style="white-space:nowrap;">
                                        <?php echo htmlspecialchars($dateVal); ?>
                                    </td>

                                    <td class="fw-semibold">
                                        <?php echo htmlspecialchars($customerName); ?>
                                        <?php if (!empty($m['CustomerID'])): ?>
                                            <span class="badge bg-light text-dark border ms-2">#<?php echo (int)$m['CustomerID']; ?></span>
                                        <?php endif; ?>
                                    </td>

                                    <td class="small text-muted">
                                        <div>
                                            <i class="bi bi-envelope me-1"></i>
                                            <?php if (!empty($email)): ?>
                                                <a class="text-decoration-none" href="mailto:<?php echo htmlspecialchars($email); ?>">
                                                    <?php echo htmlspecialchars($email); ?>
                                                </a>
                                            <?php else: ?>
                                                <span class="opacity-75">No email</span>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <i class="bi bi-telephone me-1"></i>
                                            <?php echo !empty($phone) ? htmlspecialchars($phone) : '<span class="opacity-75">No phone</span>'; ?>
                                        </div>
                                    </td>

                                    <td class="fw-bold text-dark">
                                        <?php echo htmlspecialchars($subject); ?>
                                    </td>

                                    <td class="text-muted">
                                        <span class="d-inline-block text-truncate msg-preview">
                                            <?php echo htmlspecialchars($content); ?>
                                        </span>
                                    </td>

                                    <td class="pe-4 text-end">
                                        <div class="btn-group btn-group-sm shadow-sm">
                                            <button
                                                type="button"
                                                class="btn btn-light border text-primary"
                                                data-bs-toggle="modal"
                                                data-bs-target="#viewMessageModal"
                                                data-name="<?php echo htmlspecialchars($customerName); ?>"
                                                data-email="<?php echo htmlspecialchars($email); ?>"
                                                data-phone="<?php echo htmlspecialchars($phone); ?>"
                                                data-subject="<?php echo htmlspecialchars($subject); ?>"
                                                data-content="<?php echo htmlspecialchars($content); ?>"
                                                data-date="<?php echo htmlspecialchars($dateVal); ?>"
                                                title="View">
                                                <i class="bi bi-eye-fill"></i>
                                            </button>

                                            <?php if (!empty($email)): ?>
                                                <a
                                                    class="btn btn-light border text-success"
                                                    href="mailto:<?php echo htmlspecialchars($email); ?>?subject=<?php echo urlencode('RE: ' . $subject); ?>"
                                                    title="Reply">
                                                    <i class="bi bi-reply-fill"></i>
                                                </a>
                                            <?php else: ?>
                                                <button class="btn btn-light border text-success" disabled title="No email available">
                                                    <i class="bi bi-reply-fill"></i>
                                                </button>
                                            <?php endif; ?>

                                            <a
                                                class="btn btn-light border text-danger"
                                                href="?delete=<?php echo (int)$m['MessageID']; ?>"
                                                onclick="return confirm('Delete this message?');"
                                                title="Delete">
                                                <i class="bi bi-trash-fill"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width:70px;height:70px;">
                                        <i class="bi bi-inbox fs-1 text-secondary opacity-50"></i>
                                    </div>
                                    <h5 class="fw-bold text-dark">No messages received</h5>
                                    <p class="mb-0">When customers send messages, they will appear here.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <?php endif; ?>

    </div>
</div>

<!-- View Message Modal -->
<div class="modal fade" id="viewMessageModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-success text-white border-0">
        <h5 class="modal-title fw-bold"><i class="bi bi-envelope-open me-2"></i>Message Details</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">
        <div class="row g-3 mb-3">
          <div class="col-md-6">
            <div class="small text-muted text-uppercase fw-bold">Customer</div>
            <div id="vm_name" class="fw-semibold">—</div>
          </div>
          <div class="col-md-6">
            <div class="small text-muted text-uppercase fw-bold">Date</div>
            <div id="vm_date" class="fw-semibold">—</div>
          </div>
          <div class="col-md-6">
            <div class="small text-muted text-uppercase fw-bold">Email</div>
            <div><a id="vm_email" href="#" class="text-decoration-none">—</a></div>
          </div>
          <div class="col-md-6">
            <div class="small text-muted text-uppercase fw-bold">Phone</div>
            <div id="vm_phone" class="fw-semibold">—</div>
          </div>
        </div>

        <div class="mb-2 small text-muted text-uppercase fw-bold">Subject</div>
        <div id="vm_subject" class="fw-bold mb-3">—</div>

        <div class="mb-2 small text-muted text-uppercase fw-bold">Message</div>
        <div id="vm_content" class="border rounded p-3 bg-light" style="white-space:pre-wrap;">—</div>
      </div>
      <div class="modal-footer border-0 bg-light">
        <button type="button" class="btn btn-light border px-4" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- View message modal script moved to assets/js/script.js -->

<?php include '../includes/admin-footer.php'; ?>
