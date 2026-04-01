<?php
/**
 * session_config.php — Place in /my_ss_system/ root
 *
 * SECURITY FIXES APPLIED:
 *   [Vuln 6] Cookie No HttpOnly Flag (CWE-1004)
 *   [Vuln 6] Cookie Without SameSite Attribute (CWE-1275)
 *
 * Include this INSTEAD of calling session_start() directly.
 * Root pages:  require_once 'session_config.php';
 * Sub pages:   require_once '../session_config.php';
 */
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'httponly' => true,      // [Vuln 6 Fix] Block JS access to PHPSESSID
        'samesite' => 'Strict',  // [Vuln 6 Fix] Block cross-site cookie sending
        'secure'   => false,     // Set TRUE in production (HTTPS)
    ]);
    session_start();
}
?>
