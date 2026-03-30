<?php
session_start();
if (!isset($_SESSION['census_number'])) {
    header("Location: ../index.php");
    exit();
}

include '../db.php';

$census = $_SESSION['census_number'];
$subject = $_POST['subject'];
$message = $_POST['message'];

// Get user email
$stmt = $conn->prepare("SELECT email, school_name FROM users WHERE census_number = ?");
$stmt->bind_param("s", $census);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

include '../email.php';

$email_message = "
<h2>Feedback from School Management System</h2>
<p><strong>From:</strong> {$user['school_name']} ({$user['email']})</p>
<p><strong>Subject:</strong> " . htmlspecialchars($subject) . "</p>
<p><strong>Message:</strong><br>" . nl2br(htmlspecialchars($message)) . "</p>
";

// Send to admin email (change to your admin email)
$admin_email = "your_email@gmail.com";
if (sendMail($admin_email, "Feedback: " . $subject, $email_message)) {
    header("Location: ../home.php?tab=feedback&msg=Feedback sent successfully! We'll get back to you soon.");
} else {
    die("Error sending feedback. Please try again later.");
}
?>