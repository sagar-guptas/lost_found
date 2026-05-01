<?php
session_start();
require_once 'db.php';

if (!isSuperAdmin()) {
    header('Location: login.php');
    exit;
}

$msg = '';

if (isset($_GET['action']) && isset($_GET['uid'])) {
    $uid    = (int)$_GET['uid'];
    $action = $_GET['action'];

    if ($uid == $_SESSION['user_id']) {
        $msg = 'You cannot modify your own role.';
    } else {
        if ($action == 'promote') {
            $stmt = $conn->prepare("UPDATE users SET role = 'sub_admin' WHERE id = ? AND role = 'user' AND user_type = 'admin_request'");
            $stmt->bind_param("i", $uid);
            $stmt->execute();
            $stmt->close();
            $msg = 'User promoted to Sub Admin.';
        } elseif ($action == 'demote') {
            $stmt = $conn->prepare("UPDATE users SET role = 'user' WHERE id = ? AND role = 'sub_admin'");
            $stmt->bind_param("i", $uid);
            $stmt->execute();
            $stmt->close();
            $msg = 'Sub Admin demoted to User.';
        }
    }
}

$admins = $conn->query("SELECT * FROM users WHERE role IN ('super_admin', 'sub_admin') ORDER BY role DESC, created_at ASC");
$users  = $conn->query("SELECT * FROM users WHERE role = 'user' AND user_type = 'admin_request' AND is_verified = 1 ORDER BY full_name ASC");

$q = $conn->query("SELECT COUNT(*) as c FROM items WHERE status='pending'");
$pending_items = $q->fetch_assoc()['c'];

$q = $conn->query("SELECT COUNT(*) as c FROM claims WHERE status='pending'");
$pending_claims = $q->fetch_assoc()['c'];

$page_title  = 'Manage Admins — Lost & Found System';
$active_page = 'admins';
include 'components/header.php';
include 'components/admin_navbar.php';
?>

<style>
/* Admin Table */
.admin-table-wrap { background: #fff; border: 1px solid #ccc; overflow-x: auto; }
.admin-table { width: 100%; border-collapse: collapse; }
.admin-table th { padding: 10px 14px; text-align: left; font-size: 0.8rem; color: #888; text-transform: uppercase; border-bottom: 1px solid #ccc; background: #f5f5f5; }
.admin-table td { padding: 10px 14px; font-size: 0.9rem; border-bottom: 1px solid #eee; vertical-align: middle; }
.admin-table tbody tr:hover { background: #f9f9f9; }
.admin-table tbody tr:last-child td { border-bottom: none; }
</style>

<div class="container">
    <div class="page-header">
        <h1>🛡️ Manage Admins</h1>
        <p>Promote users to Sub Admin or demote existing Sub Admins. Only Super Admin can access this page.</p>
    </div>

    <?php if ($msg != ''): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($msg); ?></div>
    <?php endif; ?>

    <h2 style="font-size:1.1rem;margin-bottom:14px;">Current Admin Team</h2>
    <div class="admin-table-wrap" style="margin-bottom:30px;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Role</th>
                    <th>Joined</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($a = $admins->fetch_assoc()): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($a['full_name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($a['email']); ?></td>
                        <td><?php echo htmlspecialchars($a['phone']); ?></td>
                        <td>
                            <?php if ($a['role'] == 'super_admin'): ?>
                                <span class="badge badge-super">Super Admin</span>
                            <?php else: ?>
                                <span class="badge badge-admin">Sub Admin</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo date('M d, Y', strtotime($a['created_at'])); ?></td>
                        <td>
                            <?php if ($a['id'] == $_SESSION['user_id']): ?>
                                <span style="color:#999;">You</span>
                            <?php elseif ($a['role'] == 'sub_admin'): ?>
                                <a href="manage_admins.php?action=demote&uid=<?php echo $a['id']; ?>" class="btn-reject" onclick="return confirm('Demote this admin?');">⬇️ Demote</a>
                            <?php else: ?>
                                <span style="color:#999;">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <h2 style="font-size:1.1rem;margin-bottom:10px;">Admin Promotion Requests</h2>
    <p style="color:#666;font-size:0.9rem;margin-bottom:14px;">Only users who registered as "Admin" can be promoted to Sub Admin.</p>

    <?php if ($users->num_rows > 0): ?>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Joined</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($u = $users->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($u['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($u['email']); ?></td>
                            <td><?php echo htmlspecialchars($u['phone']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($u['created_at'])); ?></td>
                            <td><a href="manage_admins.php?action=promote&uid=<?php echo $u['id']; ?>" class="btn-promote" onclick="return confirm('Promote this user to Sub Admin?');">⬆️ Promote</a></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <div class="empty-icon">🛡️</div>
            <h3>No admin requests</h3>
            <p>No users have registered with the "Admin" role yet.</p>
        </div>
    <?php endif; ?>
</div>

<?php include 'components/footer.php'; ?>
<?php $conn->close(); ?>
