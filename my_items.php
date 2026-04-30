<?php
session_start();
require_once 'db.php';

if (!isLoggedIn()) { header('Location: login.php'); exit; }

$uid = $_SESSION['user_id'];

$items = $conn->prepare("SELECT * FROM items WHERE user_id = ? ORDER BY date DESC");
$items->bind_param("i", $uid);
$items->execute();
$items_result = $items->get_result();

$claims_sql = "SELECT c.*, i.title AS item_title, u.full_name AS claimer_name, u.email AS claimer_email, u.phone AS claimer_phone
               FROM claims c
               JOIN items i ON c.item_id = i.id
               JOIN users u ON c.claimer_id = u.id
               WHERE i.user_id = ?
               ORDER BY c.created_at DESC";
$claims_stmt = $conn->prepare($claims_sql);
$claims_stmt->bind_param("i", $uid);
$claims_stmt->execute();
$claims_result = $claims_stmt->get_result();

$page_title = 'My Items — Lost & Found System';
$active_page = 'my_items';
include 'components/header.php';
include 'components/navbar.php';
?>

<div class="container">
    <div class="page-header">
        <h1>My Items</h1>
        <p>Track the status of items you've reported and any claims on them.</p>
    </div>

    <?php if (isset($_GET['resolved'])): ?>
        <div class="alert alert-success">Item marked as resolved!</div>
    <?php endif; ?>

    <h2 style="font-size:1.1rem;margin-bottom:16px;">📦 My Posted Items</h2>

    <?php if ($items_result->num_rows > 0): ?>
        <div class="cards-grid" style="margin-bottom:32px;">
            <?php while ($row = $items_result->fetch_assoc()): ?>
                <div class="card">
                    <div class="card-strip <?php echo ($row['type'] === 'Lost') ? 'card-strip-lost' : 'card-strip-found'; ?>"></div>
                    <div class="card-body">
                        <div class="card-top">
                            <span class="card-title"><?php echo htmlspecialchars($row['title']); ?></span>
                            <span class="badge badge-<?php echo strtolower($row['status']); ?>"><?php echo ucfirst($row['status']); ?></span>
                        </div>

                        <div class="card-tags">
                            <span class="badge <?php echo ($row['type'] === 'Lost') ? 'badge-lost' : 'badge-found'; ?>"><?php echo $row['type']; ?></span>
                            <span class="badge badge-cat"><?php echo htmlspecialchars($row['category']); ?></span>
                        </div>

                        <p class="card-description"><?php echo htmlspecialchars($row['description']); ?></p>

                        <div class="card-meta">
                            <span style="font-size:0.82rem;color:#888;">📅 <?php echo date('M d, Y', strtotime($row['date'])); ?></span>
                            <?php if ($row['status'] === 'claimed'): ?>
                                <a href="resolve_item.php?id=<?php echo $row['id']; ?>&src=my"
                                   class="btn btn-resolve btn-sm"
                                   onclick="return confirm('Mark this item as resolved?');">✅ Mark Resolved</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="empty-state" style="margin-bottom:32px;">
            <div class="empty-icon">📦</div>
            <h3>No items posted yet</h3>
            <p><a href="add_item.php">Report your first item →</a></p>
        </div>
    <?php endif; ?>

    <h2 style="font-size:1.1rem;margin-bottom:16px;">🙋 Claims on My Items</h2>

    <?php if ($claims_result->num_rows > 0): ?>
        <?php while ($c = $claims_result->fetch_assoc()): ?>
            <div class="claim-card">
                <div class="claim-header">
                    <strong><?php echo htmlspecialchars($c['item_title']); ?></strong>
                    <span class="badge badge-<?php echo $c['status']; ?>"><?php echo ucfirst($c['status']); ?></span>
                </div>
                <div class="claimer-info">
                    <strong><?php echo htmlspecialchars($c['claimer_name']); ?></strong>
                    — <?php echo htmlspecialchars($c['claimer_email']); ?>
                    | 📞 <?php echo htmlspecialchars($c['claimer_phone']); ?>
                </div>
                <div class="claim-message"><?php echo htmlspecialchars($c['message']); ?></div>
                <span style="font-size:0.82rem;color:#888;">📅 <?php echo date('M d, Y — h:i A', strtotime($c['created_at'])); ?></span>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="empty-state">
            <div class="empty-icon">🙋</div>
            <h3>No claims yet</h3>
            <p>When someone claims your lost item, it will appear here.</p>
        </div>
    <?php endif; ?>
</div>

<?php include 'components/footer.php'; ?>
<?php $items->close(); $claims_stmt->close(); $conn->close(); ?>
