<?php
session_start();
require_once 'db.php';

if (!isAdmin()) {
    header('Location: login.php');
    exit;
}

$msg = '';

if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM items WHERE id = ?");
    $stmt->bind_param("i", $del_id);
    if ($stmt->execute()) {
        $msg = 'Item deleted.';
    }
    $stmt->close();
}

if (isset($_GET['resolved'])) {
    $msg = 'Item marked as resolved.';
}

$q = $conn->query("SELECT COUNT(*) as c FROM items");
$total_items = $q->fetch_assoc()['c'];

$q = $conn->query("SELECT COUNT(*) as c FROM items WHERE status='pending'");
$pending_items = $q->fetch_assoc()['c'];

$q = $conn->query("SELECT COUNT(*) as c FROM items WHERE type='Lost' AND status='approved'");
$lost_count = $q->fetch_assoc()['c'];

$q = $conn->query("SELECT COUNT(*) as c FROM items WHERE type='Found' AND status='approved'");
$found_count = $q->fetch_assoc()['c'];

$q = $conn->query("SELECT COUNT(*) as c FROM items WHERE status='resolved'");
$resolved = $q->fetch_assoc()['c'];

$q = $conn->query("SELECT COUNT(*) as c FROM claims WHERE status='pending'");
$pending_claims = $q->fetch_assoc()['c'];

$q = $conn->query("SELECT COUNT(*) as c FROM users WHERE role='user'");
$user_count = $q->fetch_assoc()['c'];

$items_result = $conn->query("SELECT i.*, u.full_name AS posted_by FROM items i LEFT JOIN users u ON i.user_id = u.id ORDER BY i.date DESC");

$page_title  = 'Admin Dashboard — Lost & Found System';
$active_page = 'dashboard';
include 'components/header.php';
include 'components/admin_navbar.php';
?>

<div class="container">
    <div class="page-header" style="text-align:left;">
        <h1>
            Hello, <?php echo htmlspecialchars($_SESSION['full_name']); ?>
            <?php if (isSuperAdmin()): ?>
                <span class="badge badge-super">Super Admin</span>
            <?php else: ?>
                <span class="badge badge-admin">Sub Admin</span>
            <?php endif; ?>
        </h1>
        <p>Here's what's happening today.</p>
    </div>

    <?php if ($msg != ''): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($msg); ?></div>
    <?php endif; ?>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-number"><?php echo $total_items; ?></div>
            <div class="stat-label">Total Items</div>
        </div>
        <div class="stat-card stat-pending">
            <div class="stat-number"><?php echo $pending_items; ?></div>
            <div class="stat-label">Pending Approval</div>
        </div>
        <div class="stat-card stat-lost">
            <div class="stat-number"><?php echo $lost_count; ?></div>
            <div class="stat-label">Lost Items</div>
        </div>
        <div class="stat-card stat-found">
            <div class="stat-number"><?php echo $found_count; ?></div>
            <div class="stat-label">Found Items</div>
        </div>
        <div class="stat-card stat-claims">
            <div class="stat-number"><?php echo $pending_claims; ?></div>
            <div class="stat-label">Pending Claims</div>
        </div>
        <div class="stat-card" style="border-top-color:#8e44ad;">
            <div class="stat-number"><?php echo $resolved; ?></div>
            <div class="stat-label">Resolved</div>
        </div>
        <div class="stat-card stat-users">
            <div class="stat-number"><?php echo $user_count; ?></div>
            <div class="stat-label">Users</div>
        </div>
    </div>

    <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:24px;">
        <?php if ($pending_items > 0): ?>
            <a href="approve_items.php" class="btn btn-primary btn-sm">📋 Review <?php echo $pending_items; ?> Pending Items</a>
        <?php endif; ?>
        <?php if ($pending_claims > 0): ?>
            <a href="approve_claims.php" class="btn btn-claim btn-sm">🙋 Review <?php echo $pending_claims; ?> Pending Claims</a>
        <?php endif; ?>
        <a href="analytics.php" class="btn btn-outline btn-sm">📊 View Analytics</a>
    </div>

    <h2 style="font-size:1.1rem;margin-bottom:14px;">📦 All Items</h2>
    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Type</th>
                    <th>Category</th>
                    <th>Status</th>
                    <th>Posted By</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($items_result && $items_result->num_rows > 0): ?>
                    <?php while ($item = $items_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $item['id']; ?></td>
                            <td><?php echo htmlspecialchars($item['title']); ?></td>
                            <td>
                                <?php if ($item['type'] == 'Lost'): ?>
                                    <span class="badge badge-lost">Lost</span>
                                <?php else: ?>
                                    <span class="badge badge-found">Found</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($item['category']); ?></td>
                            <td><span class="badge badge-<?php echo $item['status']; ?>"><?php echo ucfirst($item['status']); ?></span></td>
                            <td><?php echo htmlspecialchars($item['posted_by'] ?? 'System'); ?></td>
                            <td><?php echo date('M d', strtotime($item['date'])); ?></td>
                            <td>
                                <div class="action-group">
                                    <?php if ($item['status'] == 'claimed' || $item['status'] == 'approved'): ?>
                                        <a href="resolve_item.php?id=<?php echo $item['id']; ?>&src=admin" class="btn-approve" onclick="return confirm('Mark as resolved?');">✅ Resolve</a>
                                    <?php endif; ?>
                                    <a href="admin_dashboard.php?delete=<?php echo $item['id']; ?>" class="btn-delete" onclick="return confirm('Delete this item?');">🗑️</a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="8" style="text-align:center;color:#999;padding:24px;">No items.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'components/footer.php'; ?>
<?php $conn->close(); ?>
