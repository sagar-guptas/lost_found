<?php
session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error   = '';
$success = '';
$show_otp  = false;
$otp_value = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    require_once 'db.php';

    $full_name = trim($_POST['full_name'] ?? '');
    $email     = trim($_POST['email']     ?? '');
    $phone     = trim($_POST['phone']     ?? '');
    $user_type = trim($_POST['user_type'] ?? '');
    $password  = $_POST['password']         ?? '';
    $confirm   = $_POST['confirm_password'] ?? '';

    if ($full_name == '' || $email == '' || $phone == '' || $user_type == '' || $password == '' || $confirm == '') {
        $error = 'All fields are required.';
    } elseif ($user_type != 'student' && $user_type != 'faculty' && $user_type != 'admin_request') {
        $error = 'Invalid user type selected.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif ($password != $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $error = 'An account with this email already exists.';
            $check->close();
        } else {
            $check->close();
            $hashed     = password_hash($password, PASSWORD_DEFAULT);
            $otp        = generateOTP();
            $otp_expiry = date('Y-m-d H:i:s', strtotime('+5 minutes'));

            $stmt = $conn->prepare("INSERT INTO users (full_name, email, phone, user_type, password, role, is_verified, otp, otp_expiry) VALUES (?, ?, ?, ?, ?, 'user', 0, ?, ?)");
            $stmt->bind_param("sssssss", $full_name, $email, $phone, $user_type, $hashed, $otp, $otp_expiry);

            if ($stmt->execute()) {
                sendOTP($email, $otp);
                if (OTP_MODE == 'screen') {
                    $show_otp  = true;
                    $otp_value = $otp;
                }
                $_SESSION['pending_verify_email'] = $email;
                if ($user_type == 'admin_request') {
                    $success = 'Account created! Verify your email, then the Super Admin will review your admin request.';
                } else {
                    $success = 'Account created! Verify your email with the OTP below to get started.';
                }
            } else {
                $error = 'Something went wrong. Please try again.';
            }
            $stmt->close();
        }
        $conn->close();
    }
} else {
    require_once 'db.php';
}

$page_title  = 'Register — Lost & Found System';
$active_page = 'register';
include 'components/header.php';
include 'components/navbar.php';
?>

<div class="container">
    <div class="page-header">
        <h1>Create Your Account</h1>
        <p>Join the community to report and reclaim items.</p>
    </div>

    <div class="form-card form-wide">
        <?php if ($error != ''): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success != ''): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if ($show_otp): ?>
            <div class="otp-display">
                <p>Your verification OTP:</p>
                <div class="otp-code"><?php echo $otp_value; ?></div>
                <p>Expires in 5 minutes</p>
            </div>
            <div style="text-align:center;margin-top:16px;">
                <a href="verify_otp.php" class="btn btn-primary">Verify OTP</a>
            </div>
        <?php else: ?>
            <form action="register.php" method="POST">
                <div class="form-group">
                    <label>I am a <span class="required">*</span></label>
                    <div class="role-selector">
                        <label class="role-option">
                            <input type="radio" name="user_type" value="student" <?php if (isset($user_type) && $user_type == 'student') echo 'checked'; ?> required>
                            <div class="role-card">
                                <span class="role-icon">🎓</span>
                                <span class="role-label">Student</span>
                            </div>
                        </label>
                        <label class="role-option">
                            <input type="radio" name="user_type" value="faculty" <?php if (isset($user_type) && $user_type == 'faculty') echo 'checked'; ?>>
                            <div class="role-card">
                                <span class="role-icon">👨‍🏫</span>
                                <span class="role-label">Faculty</span>
                            </div>
                        </label>
                        <label class="role-option">
                            <input type="radio" name="user_type" value="admin_request" <?php if (isset($user_type) && $user_type == 'admin_request') echo 'checked'; ?>>
                            <div class="role-card">
                                <span class="role-icon">🛡️</span>
                                <span class="role-label">Admin</span>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="full_name">Full Name <span class="required">*</span></label>
                    <input type="text" id="full_name" name="full_name" class="form-control" placeholder="Enter your full name" value="<?php echo htmlspecialchars($full_name ?? ''); ?>" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="email">Email Address <span class="required">*</span></label>
                        <input type="email" id="email" name="email" class="form-control" placeholder="you@example.com" value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone Number <span class="required">*</span></label>
                        <input type="tel" id="phone" name="phone" class="form-control" placeholder="+91 9876543210" value="<?php echo htmlspecialchars($phone ?? ''); ?>" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="password">Password <span class="required">*</span></label>
                        <input type="password" id="password" name="password" class="form-control" placeholder="Min 6 characters" minlength="6" required>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password <span class="required">*</span></label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Re-enter password" minlength="6" required>
                    </div>
                </div>

                <div id="admin-notice" class="alert alert-warning" style="display:none;">
                    Admin accounts require Super Admin approval after registration.
                </div>

                <button type="submit" class="btn btn-primary" style="width:100%;">Create Account</button>
            </form>

            <div class="divider">or</div>

            <div class="auth-footer" style="border:none;margin:0;padding:0;">
                <p>Already have an account? <a href="login.php">Log in</a></p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.querySelectorAll('input[name="user_type"]').forEach(function(radio) {
    radio.addEventListener('change', function() {
        if (this.value === 'admin_request') {
            document.getElementById('admin-notice').style.display = 'block';
        } else {
            document.getElementById('admin-notice').style.display = 'none';
        }
    });
});
</script>

<?php include 'components/footer.php'; ?>
