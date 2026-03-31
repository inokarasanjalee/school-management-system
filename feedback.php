<?php
// [Vuln 6 Fix] Secure session cookie
require_once '../session_config.php';

if (!isset($_SESSION['census_number'])) {
    header("Location: ../index.php");
    exit();
}

// [Vuln 1 Fix] Validate CSRF token
if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
    header("Location: ../home.php?error=Invalid request. Please try again.");
    exit();
}

include '../db.php';

$census = $_SESSION['census_number'];

// [Vuln 5 Fix] Safe parameter reads + length limits
$subject = trim($_POST['subject'] ?? '');
$message = trim($_POST['message'] ?? '');

if (empty($subject) || strlen($subject) > 200) {
    header("Location: ../home.php?tab=feedback&error=Subject is required and must be under 200 characters.");
    exit();
}
if (empty($message) || strlen($message) > 5000) {
    header("Location: ../home.php?tab=feedback&error=Message is required and must be under 5000 characters.");
    exit();
}

$stmt = $conn->prepare("SELECT email, school_name FROM users WHERE census_number = ?");
$stmt->bind_param("s", $census);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

include '../email.php';

$email_message = "
<h2>Feedback from School Management System</h2>
<p><strong>From:</strong> " . htmlspecialchars($user['school_name']) . " (" . htmlspecialchars($user['email']) . ")</p>
<p><strong>Subject:</strong> " . htmlspecialchars($subject) . "</p>
<p><strong>Message:</strong><br>" . nl2br(htmlspecialchars($message)) . "</p>
";

$admin_email = "inokarasanjalee@gmail.com";
if (sendMail($admin_email, "Feedback: " . $subject, $email_message)) {
    header("Location: ../home.php?tab=feedback&msg=Feedback sent successfully! We'll get back to you soon.");
} else {
    error_log("Feedback email failed for census: $census");
    header("Location: ../home.php?tab=feedback&error=Error sending feedback. Please try again later.");
}
?>
