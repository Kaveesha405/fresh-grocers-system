<?php
require_once '../config.php';
$page_title = "Contact Us";
include '../includes/customer-header.php';

$success_msg = "";
$error_msg = "";

// Auto-fill details if the user is logged in
$customerID = null;
$name = "";
$email = "";
$phone = "";

if (is_logged_in()) {
    $custInfo = get_customer_info();
    if ($custInfo) {
        $customerID = $custInfo['CustomerID'];
        $name = trim(($custInfo['FirstName'] ?? '') . ' ' . ($custInfo['LastName'] ?? ''));
        $email = $custInfo['Email'] ?? '';
        $phone = $custInfo['PhoneNumber'] ?? '';
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    
    // Check if the Message table exists
    $tableExists = $conn->query("SHOW TABLES LIKE 'Message'")->num_rows > 0;
    
    if (!$tableExists) {
        $error_msg = "The messaging system is currently unavailable (Table missing). Please try again later.";
    } else {
        $subject = clean_input_data($_POST['subject']);
        $content = clean_input_data($_POST['content']);
        
        // If it's a guest, they have to manually type their name/contact. 
        // But your schema links messages strictly by CustomerID. 
        // For strict schema matching, we will just insert NULL for CustomerID if they aren't logged in.
        $insert_cid = $customerID ? $customerID : 'NULL';

        $stmt = $conn->prepare("INSERT INTO Message (Subject, Content, CustomerID) VALUES (?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("ssi", $subject, $content, $customerID);
            
            if ($stmt->execute()) {
                $success_msg = "Your message has been sent successfully! Our team will get back to you soon.";
            } else {
                $error_msg = "Failed to send message. Please try again.";
            }
            $stmt->close();
        } else {
            $error_msg = "Database error: " . $conn->error;
        }
    }
}
?>

<div class="bg-light py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 text-center">
                <h2 class="fw-bold text-success mb-3"><i class="bi bi-chat-dots-fill me-2"></i>Contact Support</h2>
                <p class="text-muted lead">Have a question about your order or our products? Send us a message and we'll help you out.</p>
            </div>
        </div>
    </div>
</div>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            
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

            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-body p-5">
                    <form method="POST" action="contact.php">
                        
                        <?php if(!is_logged_in()): ?>
                            <div class="alert alert-info border-0 shadow-sm small mb-4">
                                <i class="bi bi-info-circle-fill me-2"></i>
                                You are sending this message as a guest. To easily track your support history, please <a href="login.php" class="alert-link">Log In</a> or <a href="login.php" class="alert-link">Register</a>.
                            </div>
                        <?php endif; ?>

                        <div class="row g-4">
                            <!-- We show name and email just for the user's reference, but the database saves it via CustomerID -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-uppercase text-muted">Your Name</label>
                                <input type="text" class="form-control bg-light" value="<?php echo htmlspecialchars($name ? $name : 'Guest User'); ?>" readonly disabled>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-uppercase text-muted">Email Address</label>
                                <input type="text" class="form-control bg-light" value="<?php echo htmlspecialchars($email ? $email : 'Not Provided'); ?>" readonly disabled>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-bold small text-uppercase text-muted">Subject <span class="text-danger">*</span></label>
                                <select name="subject" class="form-select shadow-sm" required>
                                    <option value="" disabled selected>Select a subject...</option>
                                    <option value="Order Inquiry">Order Inquiry</option>
                                    <option value="Delivery Issue">Delivery Issue</option>
                                    <option value="Product Availability">Product Availability</option>
                                    <option value="Payment Problem">Payment Problem</option>
                                    <option value="Feedback / Suggestion">Feedback / Suggestion</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-bold small text-uppercase text-muted">Message <span class="text-danger">*</span></label>
                                <textarea name="content" class="form-control shadow-sm" rows="6" placeholder="How can we help you today?" required></textarea>
                            </div>

                            <div class="col-12 text-end mt-4">
                                <button type="submit" name="send_message" class="btn btn-success btn-lg px-5 shadow-sm fw-bold">
                                    <i class="bi bi-send-fill me-2"></i> Send Message
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
