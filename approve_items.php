<?php
session_start();
require_once 'db.php';

if (!isAdmin()) {
    header('Location: login.php');
    exit;
}

$msg = '';

if (isset($_GET['action']) && isset($_GET['id'])) {
    $id     = (int)$_GET['id'];
    $action = $_GET['action'];

    if ($action == 'approve') {
        $stmt = $conn->prepare("UPDATE items SET status = 'approved', approved_by = ? WHERE id = ? AND status = 'pending'");
        $stmt->bind_param("ii", $_SESSION['user_id'], $id);
        $stmt->execute();
        $stmt->close();
        $msg = 'Item approved successfully.';
    } elseif ($action == 'reject') {
        $stmt = $conn->prepare("UPDATE items SET status = 'rejected', approved_by = ? WHERE id = ? AND status = 'pending'");
        $stmt->bind_param("ii", $_SESSION['user_id'], $id);
        $stmt->execute();
        $stmt->close();
        $msg = 'Item rejected.';
    }
}

$result = $conn->query("SELECT i.*, u.full_name AS posted_by, u.email AS user_email
                         FROM items i
                         LEFT JOIN users u ON i.user_id = u.id
                         WHERE i.status = 'pending'
                         ORDER BY i.date DESC");

$q = $conn->query("SELECT COUNT(*) as c FROM items WHERE status='pending'");
$pending_items = $q->fetch_assoc()['c'];

$q = $conn->query("SELECT COUNT(*) as c FROM claims WHERE status='pending'");
$pending_claims = $q->fetch_assoc()['c'];

$page_title  = 'Approve Items — Lost & Found System';
$active_page = 'items';
include 'components/header.php';
include 'components/admin_navbar.php';
?>

<div class="container">
    <div class="page-header">
        <h1>Pending Item Approvals</h1>
        <p>Review and approve or reject submitted items.</p>
    </div>

    <?php if ($msg != ''): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($msg); ?></div>
    <?php endif; ?>

    <?php if ($result && $result->num_rows > 0): ?>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Type</th>
                        <th>Category</th>
                        <th>Location</th>
                        <th>Posted By</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($row['title']); ?></strong>
                                <br><small style="color:#888;"><?php echo htmlspecialchars(substr($row['description'], 0, 80)) . '...'; ?></small>
                            </td>
                            <td>
                                <?php if ($row['type'] == 'Lost'): ?>
                                    <span class="badge badge-lost">Lost</span>
                                <?php else: ?>
                                    <span class="badge badge-found">Found</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($row['category']); ?></td>
                            <td><?php echo htmlspecialchars($row['location']); ?></td>
                            <td>
                                <?php echo htmlspecialchars($row['posted_by'] ?? 'Unknown'); ?>
                                <br><small style="color:#888;"><?php echo htmlspecialchars($row['user_email'] ?? ''); ?></small>
                            </td>
                            <td><?php echo date('M d', strtotime($row['date'])); ?></td>
                            <td>
                                <div class="action-group">
                                    <a href="approve_items.php?action=approve&id=<?php echo $row['id']; ?>" class="btn-approve" onclick="return confirm('Approve this item?');">✅ Approve</a>
                                    <a href="approve_items.php?action=reject&id=<?php echo $row['id']; ?>" class="btn-reject" onclick="return confirm('Reject this item?');">❌ Reject</a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <div class="empty-icon">✅</div>
            <h3>All caught up!</h3>
            <p>No items pending approval.</p>
        </div>
    <?php endif; ?>
</div>

<?php include 'components/footer.php'; ?>
<?php $conn->close(); ?>
