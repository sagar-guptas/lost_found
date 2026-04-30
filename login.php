<?php
session_start();

if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] == 'super_admin' || $_SESSION['role'] == 'sub_admin') {
        header('Location: admin_dashboard.php');
    } else {
        header('Location: index.php');
    }
    exit;
}

require_once 'db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email == '' || $password == '') {
        $error = 'Please fill in all fields.';
    } else {
        $stmt = $conn->prepare("SELECT id, full_name, email, phone, password, user_type, role, is_verified FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();

            if (!password_verify($password, $user['password'])) {
                $error = 'Incorrect password. Please try again.';
            } elseif ($user['is_verified'] == 0) {
                $_SESSION['pending_verify_email'] = $user['email'];
                $otp = generateOTP();
                $otp_expiry = date('Y-m-d H:i:s', strtotime('+5 minutes'));
                $upd = $conn->prepare("UPDATE users SET otp = ?, otp_expiry = ? WHERE id = ?");
                $upd->bind_param("ssi", $otp, $otp_expiry, $user['id']);
                $upd->execute();
                $upd->close();
                sendOTP($user['email'], $otp);
                if (OTP_MODE == 'screen') {
                    $_SESSION['resent_otp'] = $otp;
                }
                header('Location: verify_otp.php?unverified=1');
                exit;
            } else {
                $_SESSION['user_id']   = $user['id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['email']     = $user['email'];
                $_SESSION['phone']     = $user['phone'];
                $_SESSION['role']      = $user['role'];
                $_SESSION['user_type'] = $user['user_type'];
                if ($user['role'] == 'super_admin' || $user['role'] == 'sub_admin') {
                    header('Location: admin_dashboard.php');
                } else {
                    header('Location: index.php');
                }
                exit;
            }
        } else {
            $error = 'No account found with that email address.';
        }
        $stmt->close();
    }
}

$page_title  = 'Login — Lost & Found System';
$active_page = 'login';
include 'components/header.php';
include 'components/navbar.php';
?>

<div class="container">
    <div class="page-header">
        <h1>Welcome Back</h1>
        <p>Log in to report items, track claims, and help others.</p>
    </div>

    <div class="form-card">
        <?php if ($error != ''): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <div class="form-group">
                <label for="email">Email Address <span class="required">*</span></label>
                <input type="email" id="email" name="email" class="form-control" placeholder="you@example.com" value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label for="password">Password <span class="required">*</span></label>
                <input type="password" id="password" name="password" class="form-control" placeholder="Enter your password" required>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;">Log In</button>
        </form>

        <div class="divider">or</div>

        <div class="auth-footer" style="border:none;margin:0;padding:0;">
            <p>New here? <a href="register.php">Create an account</a></p>
        </div>
    </div>
</div>

<?php include 'components/footer.php'; ?>
