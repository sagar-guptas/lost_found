<?php
require_once __DIR__ . '/config.php';

$conn = new mysqli('localhost', 'root', '', 'lost_found_db', 3307);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset('utf8mb4');

function generateOTP() {
    return rand(100000, 999999);
}

function sendOTP($email, $otp) {
    return true;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    if (!isset($_SESSION['role'])) return false;
    if ($_SESSION['role'] == 'sub_admin') return true;
    if ($_SESSION['role'] == 'super_admin') return true;
    return false;
}

function isSuperAdmin() {
    if (!isset($_SESSION['role'])) return false;
    if ($_SESSION['role'] == 'super_admin') return true;
    return false;
}
?>
