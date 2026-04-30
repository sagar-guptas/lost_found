<?php
session_start();
require_once 'db.php';

$search_q    = $_GET['q']        ?? '';
$filter_cat  = $_GET['category'] ?? '';
$filter_type = $_GET['type']     ?? '';
$filter_from = $_GET['from']     ?? '';
$filter_to   = $_GET['to']       ?? '';

$where  = "i.status = 'approved'";
$params = [];
$types  = '';

if ($search_q != '') {
    $where   .= " AND (i.title LIKE ? OR i.description LIKE ?)";
    $like     = '%' . $search_q . '%';
    $params[] = $like;
    $params[] = $like;
    $types   .= 'ss';
}

if ($filter_cat != '') {
    $where   .= " AND i.category = ?";
    $params[] = $filter_cat;
    $types   .= 's';
}

if ($filter_type != '') {
    $where   .= " AND i.type = ?";
    $params[] = $filter_type;
    $types   .= 's';
}

if ($filter_from != '') {
    $where   .= " AND DATE(i.date) >= ?";
    $params[] = $filter_from;
    $types   .= 's';
}

if ($filter_to != '') {
    $where   .= " AND DATE(i.date) <= ?";
    $params[] = $filter_to;
    $types   .= 's';
}

$sql  = "SELECT i.*, u.full_name AS posted_by FROM items i LEFT JOIN users u ON i.user_id = u.id WHERE $where ORDER BY i.date DESC";
$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$cat_counts = [];
$cat_res    = $conn->query("SELECT category, COUNT(*) as c FROM items WHERE status='approved' GROUP BY category");
while ($r = $cat_res->fetch_assoc()) {
    $cat_counts[$r['category']] = $r['c'];
}
$total_approved = array_sum($cat_counts);

$page_title  = 'Lost & Found System';
$active_page = 'home';
include 'components/header.php';
include 'components/navbar.php';
?>

<div class="hero">
    <h1>Lost something? We'll help you find it.</h1>
    <p>Report, search, and reclaim lost items.</p>
    <form class="hero-search" action="index.php" method="GET">
        <input type="text" name="q" placeholder="Search for items..." value="<?php echo htmlspecialchars($search_q); ?>">
        <button type="submit">Search</button>
    </form>
</div>

<div class="container">

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">Item submitted! Waiting for admin approval.</div>
    <?php endif; ?>

    <?php if (isset($_GET['claimed'])): ?>
        <div class="alert alert-success">Your claim has been submitted for admin review!</div>
    <?php endif; ?>

    <?php if (isset($_GET['resolved'])): ?>
        <div class="alert alert-success">Item marked as resolved!</div>
    <?php endif; ?>

    <div class="category-chips">
        <?php if ($filter_cat == '' && $filter_type == ''): ?>
            <a href="index.php" class="chip active">All (<?php echo $total_approved; ?>)</a>
        <?php else: ?>
            <a href="index.php" class="chip">All (<?php echo $total_approved; ?>)</a>
        <?php endif; ?>

        <?php if ($filter_type == 'Lost'): ?>
            <a href="index.php?type=Lost" class="chip active">🔴 Lost</a>
        <?php else: ?>
            <a href="index.php?type=Lost" class="chip">🔴 Lost</a>
        <?php endif; ?>

        <?php if ($filter_type == 'Found'): ?>
            <a href="index.php?type=Found" class="chip active">🟢 Found</a>
        <?php else: ?>
            <a href="index.php?type=Found" class="chip">🟢 Found</a>
        <?php endif; ?>

        <?php
        $categories = ['Electronics','Documents','Clothing','Accessories','Keys','Bags','Wallets','ID Cards','Books','Other'];
        foreach ($categories as $cat) {
            if (isset($cat_counts[$cat])) {
                if ($filter_cat === $cat) {
                    echo '<a href="index.php?category=' . urlencode($cat) . '" class="chip active">' . $cat . ' (' . $cat_counts[$cat] . ')</a>';
                } else {
                    echo '<a href="index.php?category=' . urlencode($cat) . '" class="chip">' . $cat . ' (' . $cat_counts[$cat] . ')</a>';
                }
            }
        }
        ?>
    </div>

    <?php if ($filter_from != '' || $filter_to != ''): ?>
        <div class="filter-bar">
            <form action="index.php" method="GET">
                <?php if ($filter_cat != ''): ?>
                    <input type="hidden" name="category" value="<?php echo htmlspecialchars($filter_cat); ?>">
                <?php endif; ?>
                <?php if ($filter_type != ''): ?>
                    <input type="hidden" name="type" value="<?php echo htmlspecialchars($filter_type); ?>">
                <?php endif; ?>
                <div class="filter-group">
                    <label>From</label>
                    <input type="date" name="from" class="form-control" value="<?php echo htmlspecialchars($filter_from); ?>">
                </div>
                <div class="filter-group">
                    <label>To</label>
                    <input type="date" name="to" class="form-control" value="<?php echo htmlspecialchars($filter_to); ?>">
                </div>
                <div class="filter-group">
                    <button type="submit" class="btn btn-primary btn-sm">Apply</button>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <?php if ($search_q != ''): ?>
        <div class="search-results-info">
            Showing results for "<strong><?php echo htmlspecialchars($search_q); ?></strong>"
            — <a href="index.php">Clear</a>
        </div>
    <?php endif; ?>

    <div class="section-header">
        <h2>
            <?php
            if ($search_q != '') {
                echo 'Search Results';
            } elseif ($filter_cat != '') {
                echo htmlspecialchars($filter_cat);
            } elseif ($filter_type == 'Lost') {
                echo 'Lost Items';
            } elseif ($filter_type == 'Found') {
                echo 'Found Items';
            } else {
                echo 'Recently Reported';
            }
            ?>
        </h2>
        <span style="color:#888;"><?php echo $result->num_rows; ?> item(s)</span>
    </div>

    <?php if ($result->num_rows > 0): ?>
        <div class="cards-grid">
            <?php $show_actions = true; ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <?php include 'components/item_card.php'; ?>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <div class="empty-icon">📦</div>
            <h3>No items found</h3>
            <p>Try different filters or check back later.</p>
            <?php if (isLoggedIn()): ?>
                <br><a href="add_item.php" class="btn btn-primary">Report an Item</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

</div>

<?php include 'components/footer.php'; ?>
<?php $stmt->close(); $conn->close(); ?>
