<?php
session_start();
require_once 'db.php';

$email = $_SESSION['pending_verify_email'] ?? '';
if (empty($email)) {
    header('Location: register.php');
    exit;
}

$otp = generateOTP();
$otp_expiry = date('Y-m-d H:i:s', strtotime('+5 minutes'));

$stmt = $conn->prepare("UPDATE users SET otp = ?, otp_expiry = ? WHERE email = ? AND is_verified = 0");
$stmt->bind_param("sss", $otp, $otp_expiry, $email);
$stmt->execute();
$stmt->close();

$result = sendOTP($email, $otp);

if (OTP_MODE === 'screen') {
    $_SESSION['resent_otp'] = $otp;
}

$conn->close();
header('Location: verify_otp.php?resent=1');
exit;
?>
