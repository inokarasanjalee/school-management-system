<?php
require '../db.php';
session_start();

$token = $_POST['credential'] ?? null;
if (!$token) {
    header("Location: ../index.php?error=No token received from Google.");
    exit();
}

// FIX: Use cURL instead of file_get_contents for reliability
$google_url = "https://oauth2.googleapis.com/tokeninfo?id_token=" . urlencode($token);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $google_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$response = curl_exec($ch);
$curl_error = curl_error($ch);
curl_close($ch);

if ($curl_error || !$response) {
    header("Location: ../index.php?error=Google verification failed. Please try again.");
    exit();
}

$google_user = json_decode($response, true);

// FIX: Check for error in Google response
if (isset($google_user['error_description']) || !isset($google_user['email'])) {
    header("Location: ../index.php?error=Invalid Google token. Please try again.");
    exit();
}

// FIX: Verify the token is for YOUR app
$expected_client_id = "add client id";
if (!isset($google_user['aud']) || $google_user['aud'] !== $expected_client_id) {
    header("Location: ../index.php?error=Token client ID mismatch.");
    exit();
}

// FIX: Check token expiry
if (!isset($google_user['exp']) || $google_user['exp'] < time()) {
    header("Location: ../index.php?error=Google token has expired. Please try again.");
    exit();
}

$email     = $google_user['email'];
$name      = $google_user['name'] ?? '';
$google_id = $google_user['sub'];

// Check if user exists by email OR google_id
$stmt = $conn->prepare("SELECT * FROM users WHERE email = ? OR google_id = ?");
$stmt->bind_param("ss", $email, $google_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    // New Google user - need census verification
    $_SESSION['google_temp_email'] = $email;
    $_SESSION['google_temp_name']  = $name;
    $_SESSION['google_temp_id']    = $google_id;
    header("Location: ../verify_census.php");
    exit();
} else {
    // Existing user - log in
    $user = $result->fetch_assoc();

    // FIX: Link google_id if user registered normally first, then used Google
    if (empty($user['google_id'])) {
        $upd = $conn->prepare("UPDATE users SET google_id = ? WHERE email = ?");
        $upd->bind_param("ss", $google_id, $email);
        $upd->execute();
    }

    $_SESSION['census_number'] = $user['census_number'];
    header("Location: ../home.php");
    exit();
}
?>
