<?php
session_start();
require_once 'db.php';

if (!isLoggedIn()) { header('Location: login.php'); exit; }

$item_id = (int)($_GET['id'] ?? 0);
$src     = $_GET['src'] ?? 'my';

if ($item_id <= 0) { header('Location: index.php'); exit; }

$stmt = $conn->prepare("SELECT * FROM items WHERE id = ?");
$stmt->bind_param("i", $item_id);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$item) { header('Location: index.php'); exit; }

$can_resolve = false;
if ($item['user_id'] == $_SESSION['user_id']) {
    $can_resolve = true;
}
if (isAdmin()) {
    $can_resolve = true;
}

if (!$can_resolve) { header('Location: index.php'); exit; }

if (!in_array($item['status'], ['claimed', 'approved'])) {
    header('Location: index.php');
    exit;
}

$upd = $conn->prepare("UPDATE items SET status = 'resolved', resolved_at = NOW() WHERE id = ?");
$upd->bind_param("i", $item_id);
$upd->execute();
$upd->close();

$conn->close();

if ($src === 'admin') {
    header('Location: admin_dashboard.php?tab=items&resolved=1');
} else {
    header('Location: my_items.php?resolved=1');
}
exit;
?>
