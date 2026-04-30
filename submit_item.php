<?php
session_start();
require_once 'db.php';

if (!isLoggedIn()) { header('Location: login.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: add_item.php'); exit; }

$title       = trim(htmlspecialchars($_POST['title']       ?? '', ENT_QUOTES, 'UTF-8'));
$description = trim(htmlspecialchars($_POST['description'] ?? '', ENT_QUOTES, 'UTF-8'));
$type        = trim($_POST['type']     ?? '');
$category    = trim($_POST['category'] ?? '');
$location    = trim(htmlspecialchars($_POST['location'] ?? '', ENT_QUOTES, 'UTF-8'));
$user_id     = $_SESSION['user_id'];

$valid_categories = ['Electronics','Documents','Clothing','Accessories','Keys','Bags','Wallets','ID Cards','Books','Other'];

if (empty($title) || empty($description) || empty($type) || empty($category) || empty($location)) {
    die('All fields are required. <a href="add_item.php">Go back</a>');
}

if ($type !== 'Lost' && $type !== 'Found') {
    die('Invalid item type. <a href="add_item.php">Go back</a>');
}

if (!in_array($category, $valid_categories)) {
    die('Invalid category. <a href="add_item.php">Go back</a>');
}

$stmt = $conn->prepare("INSERT INTO items (title, description, type, category, location, status, user_id) VALUES (?, ?, ?, ?, ?, 'pending', ?)");
$stmt->bind_param("sssssi", $title, $description, $type, $category, $location, $user_id);

if ($stmt->execute()) {
    $stmt->close();
    $conn->close();
    header('Location: index.php?success=1');
    exit;
} else {
    $err = $stmt->error;
    $stmt->close();
    $conn->close();
    die('Failed to save item: ' . htmlspecialchars($err) . ' <a href="add_item.php">Go back</a>');
}
?>
