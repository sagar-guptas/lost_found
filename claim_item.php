<?php
session_start();
require_once 'db.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$item_id = (int)($_GET['id'] ?? 0);
if ($item_id <= 0) {
    header('Location: index.php');
    exit;
}

$stmt = $conn->prepare("SELECT i.*, u.full_name AS posted_by FROM items i LEFT JOIN users u ON i.user_id = u.id WHERE i.id = ? AND i.status = 'approved'");
$stmt->bind_param("i", $item_id);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$item) {
    header('Location: index.php');
    exit;
}

if ($item['user_id'] == $_SESSION['user_id']) {
    header('Location: index.php');
    exit;
}

$is_lost = ($item['type'] == 'Lost');

$check = $conn->prepare("SELECT id FROM claims WHERE item_id = ? AND claimer_id = ? AND status = 'pending'");
$check->bind_param("ii", $item_id, $_SESSION['user_id']);
$check->execute();
$check->store_result();
$already_claimed = $check->num_rows > 0;
$check->close();

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && !$already_claimed) {
    $message = trim($_POST['message'] ?? '');

    if ($message == '') {
        $error = 'Please provide details about your claim.';
    } else {
        $ins = $conn->prepare("INSERT INTO claims (item_id, claimer_id, message, status) VALUES (?, ?, ?, 'pending')");
        $ins->bind_param("iis", $item_id, $_SESSION['user_id'], $message);

        if ($ins->execute()) {
            $ins->close();
            $conn->close();
            header('Location: index.php?claimed=1');
            exit;
        } else {
            $error = 'Failed to submit claim. Try again.';
        }
        $ins->close();
    }
}

$page_title  = ($is_lost ? 'I Found This Item' : 'This is My Item') . ' — Lost & Found System';
$active_page = '';
include 'components/header.php';
include 'components/navbar.php';
?>

<div class="container">
    <div class="page-header">
        <h1><?php echo $is_lost ? 'I Found This Item' : 'This is My Item'; ?></h1>
        <p><?php echo $is_lost ? 'Provide details about where/when you found this item.' : 'Prove this item belongs to you.'; ?></p>
    </div>

    <div class="card" style="max-width:620px;margin:0 auto 24px;">
        <?php if ($is_lost): ?>
            <div class="card-strip card-strip-lost"></div>
        <?php else: ?>
            <div class="card-strip card-strip-found"></div>
        <?php endif; ?>
        <div class="card-body">
            <div class="card-top">
                <span class="card-title"><?php echo htmlspecialchars($item['title']); ?></span>
                <?php if ($is_lost): ?>
                    <span class="badge badge-lost">Lost</span>
                <?php else: ?>
                    <span class="badge badge-found">Found</span>
                <?php endif; ?>
            </div>
            <div class="card-tags">
                <span class="badge badge-cat"><?php echo htmlspecialchars($item['category']); ?></span>
                <span class="badge badge-cat">📍 <?php echo htmlspecialchars($item['location']); ?></span>
            </div>
            <p class="card-description"><?php echo htmlspecialchars($item['description']); ?></p>
            <div class="card-meta">
                <span style="font-size:0.82rem;color:#888;">📅 <?php echo date('M d, Y', strtotime($item['date'])); ?></span>
                <span style="font-size:0.82rem;color:#c0392b;">by <?php echo htmlspecialchars($item['posted_by']); ?></span>
            </div>
        </div>
    </div>

    <div class="form-card">
        <?php if ($already_claimed): ?>
            <div class="alert alert-warning">You already have a pending claim on this item. Please wait for admin review.</div>
            <div style="text-align:center;">
                <a href="index.php" class="btn btn-outline">← Back to Home</a>
            </div>
        <?php else: ?>
            <?php if ($error != ''): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div class="alert alert-info">Your name, email, and phone will be shared with admins for verification.</div>

            <form action="claim_item.php?id=<?php echo $item_id; ?>" method="POST">
                <div class="form-group">
                    <label for="message">
                        <?php echo $is_lost ? 'How did you find it?' : 'Prove this is yours'; ?>
                        <span class="required">*</span>
                    </label>
                    <textarea id="message" name="message" class="form-control"
                              placeholder="<?php echo $is_lost ? 'Describe where and when you found it...' : 'Describe the item to prove ownership...'; ?>"
                              required></textarea>
                </div>
                <?php if ($is_lost): ?>
                    <button type="submit" class="btn btn-claim" style="width:100%;">Submit Claim for Review</button>
                <?php else: ?>
                    <button type="submit" class="btn btn-resolve" style="width:100%;">Submit Claim for Review</button>
                <?php endif; ?>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php include 'components/footer.php'; ?>
<?php $conn->close(); ?>
