<?php
// [Vuln 6 Fix] Secure session config
require_once '../session_config.php';

session_unset();
session_destroy();

// [Vuln 6 Fix] Expire the session cookie immediately in the browser
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

header("Location: ../index.php");
exit();
?>
