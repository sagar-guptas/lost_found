<?php
session_start();
require_once 'db.php';

$email = $_SESSION['pending_verify_email'] ?? '';
if (empty($email)) {
    header('Location: register.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $entered_otp = trim($_POST['otp'] ?? '');

    if (empty($entered_otp)) {
        $error = 'Please enter the OTP.';
    } else {
        $stmt = $conn->prepare("SELECT id, otp, otp_expiry FROM users WHERE email = ? AND is_verified = 0");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            if ($user['otp'] !== $entered_otp) {
                $error = 'Invalid OTP. Please try again.';
            } elseif (strtotime($user['otp_expiry']) < time()) {
                $error = 'OTP has expired. Please request a new one.';
            } else {
                $update = $conn->prepare("UPDATE users SET is_verified = 1, otp = NULL, otp_expiry = NULL WHERE id = ?");
                $update->bind_param("i", $user['id']);
                $update->execute();
                $update->close();

                unset($_SESSION['pending_verify_email']);
                $success = 'Email verified successfully! You can now log in.';
            }
        } else {
            $error = 'Account not found or already verified.';
        }
        $stmt->close();
    }
}

$page_title = 'Verify OTP — Lost & Found System';
$active_page = '';
include 'components/header.php';
include 'components/navbar.php';
?>

<div class="container">
    <div class="page-header">
        <h1>Verify Your Email</h1>
        <p>Enter the 6-digit OTP sent to <strong><?php echo htmlspecialchars($email); ?></strong></p>
    </div>

    <div class="form-card">
        <?php if (isset($_GET['resent']) && isset($_SESSION['resent_otp'])): ?>
            <div class="otp-display">
                <p>Your new OTP is:</p>
                <div class="otp-code"><?php echo $_SESSION['resent_otp']; ?></div>
                <p>Expires in 5 minutes</p>
            </div>
            <?php unset($_SESSION['resent_otp']); ?>
        <?php endif; ?>

        <?php if (isset($_GET['unverified'])): ?>
            <div class="alert alert-warning">Your email is not verified yet. A new OTP has been generated.</div>
            <?php if (isset($_SESSION['resent_otp'])): ?>
                <div class="otp-display">
                    <p>Your OTP is:</p>
                    <div class="otp-code"><?php echo $_SESSION['resent_otp']; ?></div>
                    <p>Expires in 5 minutes</p>
                </div>
                <?php unset($_SESSION['resent_otp']); ?>
            <?php endif; ?>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <div style="text-align:center;margin-top:16px;">
                <a href="login.php" class="btn btn-primary">Go to Login</a>
            </div>
        <?php else: ?>
            <form action="verify_otp.php" method="POST">
                <div style="text-align:center;margin:20px 0;">
                    <input type="text" name="otp" class="form-control" maxlength="6"
                           placeholder="Enter 6-digit OTP"
                           style="text-align:center;font-size:1.5rem;font-weight:bold;letter-spacing:8px;max-width:260px;margin:0 auto;" required>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;">Verify OTP</button>
            </form>

            <div class="auth-footer">
                <p>Didn't receive the OTP?</p>
                <a href="send_otp.php?resend=1" class="btn btn-outline btn-sm" style="margin-top:8px;">Resend OTP</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'components/footer.php'; ?>
