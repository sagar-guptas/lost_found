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
        $claim = $conn->prepare("SELECT item_id FROM claims WHERE id = ? AND status = 'pending'");
        $claim->bind_param("i", $id);
        $claim->execute();
        $cr = $claim->get_result()->fetch_assoc();
        $claim->close();

        if ($cr) {
            $upd = $conn->prepare("UPDATE claims SET status = 'approved', reviewed_by = ? WHERE id = ?");
            $upd->bind_param("ii", $_SESSION['user_id'], $id);
            $upd->execute();
            $upd->close();

            $upd2 = $conn->prepare("UPDATE items SET status = 'claimed' WHERE id = ?");
            $upd2->bind_param("i", $cr['item_id']);
            $upd2->execute();
            $upd2->close();

            $rej = $conn->prepare("UPDATE claims SET status = 'rejected', reviewed_by = ? WHERE item_id = ? AND id != ? AND status = 'pending'");
            $rej->bind_param("iii", $_SESSION['user_id'], $cr['item_id'], $id);
            $rej->execute();
            $rej->close();

            $msg = 'Claim approved. Item marked as claimed.';
        }
    } elseif ($action == 'reject') {
        $upd = $conn->prepare("UPDATE claims SET status = 'rejected', reviewed_by = ? WHERE id = ? AND status = 'pending'");
        $upd->bind_param("ii", $_SESSION['user_id'], $id);
        $upd->execute();
        $upd->close();
        $msg = 'Claim rejected.';
    }
}

$result = $conn->query("SELECT c.*, i.title AS item_title, i.type AS item_type, i.category AS item_cat,
         u.full_name AS claimer_name, u.email AS claimer_email, u.phone AS claimer_phone
         FROM claims c
         JOIN items i ON c.item_id = i.id
         JOIN users u ON c.claimer_id = u.id
         WHERE c.status = 'pending'
         ORDER BY c.created_at DESC");

$q = $conn->query("SELECT COUNT(*) as c FROM items WHERE status='pending'");
$pending_items = $q->fetch_assoc()['c'];

$q = $conn->query("SELECT COUNT(*) as c FROM claims WHERE status='pending'");
$pending_claims = $q->fetch_assoc()['c'];

$page_title  = 'Approve Claims — Lost & Found System';
$active_page = 'claims';
include 'components/header.php';
include 'components/admin_navbar.php';
?>

<div class="container">
    <div class="page-header">
        <h1>Pending Claim Approvals</h1>
        <p>Review claims from users who say they found a lost item.</p>
    </div>

    <?php if ($msg != ''): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($msg); ?></div>
    <?php endif; ?>

    <?php if ($result && $result->num_rows > 0): ?>
        <?php while ($c = $result->fetch_assoc()): ?>
            <div class="claim-card">
                <div class="claim-header">
                    <div>
                        <strong style="font-size:1rem;"><?php echo htmlspecialchars($c['item_title']); ?></strong>
                        <?php if ($c['item_type'] == 'Lost'): ?>
                            <span class="badge badge-lost" style="margin-left:8px;">Lost</span>
                        <?php else: ?>
                            <span class="badge badge-found" style="margin-left:8px;">Found</span>
                        <?php endif; ?>
                        <span class="badge badge-cat" style="margin-left:4px;"><?php echo htmlspecialchars($c['item_cat']); ?></span>
                    </div>
                    <span class="badge badge-pending">Pending Review</span>
                </div>

                <div class="claimer-info">
                    👤 <strong><?php echo htmlspecialchars($c['claimer_name']); ?></strong><br>
                    📧 <?php echo htmlspecialchars($c['claimer_email']); ?> &nbsp;|&nbsp;
                    📞 <?php echo htmlspecialchars($c['claimer_phone']); ?>
                </div>

                <div class="claim-message"><?php echo htmlspecialchars($c['message']); ?></div>

                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;">
                    <span style="font-size:0.82rem;color:#888;">📅 <?php echo date('M d, Y — h:i A', strtotime($c['created_at'])); ?></span>
                    <div class="action-group">
                        <a href="approve_claims.php?action=approve&id=<?php echo $c['id']; ?>" class="btn-approve" onclick="return confirm('Approve this claim?');">✅ Approve Claim</a>
                        <a href="approve_claims.php?action=reject&id=<?php echo $c['id']; ?>" class="btn-reject" onclick="return confirm('Reject this claim?');">❌ Reject</a>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="empty-state">
            <div class="empty-icon">🙋</div>
            <h3>No pending claims</h3>
            <p>All claims have been reviewed.</p>
        </div>
    <?php endif; ?>
</div>

<?php include 'components/footer.php'; ?>
<?php $conn->close(); ?>
