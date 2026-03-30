<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'school_system';

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Set charset to UTF-8
$conn->set_charset("utf8");
?>