<?php
/**
 * security_headers.php — Place in /my_ss_system/ root
 *
 * SECURITY FIXES APPLIED:
 *   [Vuln 2] CSP Header Not Set (CWE-693)
 *   [Vuln 2] Missing Anti-Clickjacking Header (CWE-1021)
 *   [Vuln 2] X-Content-Type-Options Header Missing
 *   [Vuln 5] X-Powered-By Information Leak
 *
 * CSP UPDATE: 'unsafe-inline' removed from script-src and style-src.
 * All inline <script> and <style> blocks have been moved to external
 * files in assets/js/ and assets/css/ so this directive is no longer needed.
 * This eliminates the ZAP alerts: "CSP: script-src unsafe-inline"
 * and "CSP: style-src unsafe-inline".
 *
 * Root pages:  require_once 'security_headers.php';
 * Sub pages:   require_once '../security_headers.php';
 */

// [Vuln 2 Fix] Clickjacking — prevent page from being embedded in any iframe
header("X-Frame-Options: DENY");

// [Vuln 2 Fix] Content Security Policy — no unsafe-inline in script-src or style-src
header("Content-Security-Policy: " .
    "default-src 'self'; " .
    "script-src 'self' https://cdn.jsdelivr.net https://accounts.google.com; " .
    "style-src 'self' https://cdn.jsdelivr.net; " .
    "font-src 'self' https://cdn.jsdelivr.net; " .
    "img-src 'self' data: https://lh3.googleusercontent.com; " .
    "connect-src 'self' https://accounts.google.com; " .
    "frame-ancestors 'none'; " .
    "form-action 'self';"
);

// [Vuln 2 Fix] Prevent MIME type sniffing
header("X-Content-Type-Options: nosniff");

// [Vuln 5 Fix] Remove PHP version disclosure header
header_remove("X-Powered-By");

// Referrer policy — limit info leakage to external sites
header("Referrer-Policy: strict-origin-when-cross-origin");
?>
