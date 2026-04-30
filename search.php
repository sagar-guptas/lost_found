<?php
session_start();
require_once 'db.php';

$keyword = '';
$filter_cat = $_GET['category'] ?? '';
$results = null;
$searched = false;

if (isset($_GET['q']) && trim($_GET['q']) !== '') {
    $searched = true;
    $keyword  = trim($_GET['q']);
    $like     = '%' . $keyword . '%';

    $where = ["(i.title LIKE ? OR i.description LIKE ?)", "i.status = 'approved'"];
    $params = [$like, $like];
    $types = 'ss';

    if (!empty($filter_cat)) {
        $where[] = "i.category = ?";
        $params[] = $filter_cat;
        $types .= 's';
    }

    $where_sql = implode(' AND ', $where);
    $sql = "SELECT i.*, u.full_name AS posted_by FROM items i LEFT JOIN users u ON i.user_id = u.id WHERE $where_sql ORDER BY i.date DESC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $results = $stmt->get_result();
}

$page_title = 'Search — Lost & Found System';
$active_page = 'search';
include 'components/header.php';
include 'components/navbar.php';
?>

<div class="container">
    <div class="page-header">
        <h1>Search Items</h1>
        <p>Find lost or found items by keyword and category.</p>
    </div>

    <div class="search-section">
        <form action="search.php" method="GET" class="search-form">
            <input type="text" name="q" class="form-control"
                   placeholder="Search for wallet, phone, keys..."
                   value="<?php echo htmlspecialchars($keyword); ?>" required>
            <select name="category" class="form-control" style="max-width:160px;border:none;border-left:1px solid #ddd;">
                <option value="">All</option>
                <?php
                $categories = ['Electronics','Documents','Clothing','Accessories','Keys','Bags','Wallets','ID Cards','Books','Other'];
                foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat; ?>" <?php echo ($filter_cat === $cat) ? 'selected' : ''; ?>><?php echo $cat; ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-primary">Search</button>
        </form>
    </div>

    <?php if ($searched): ?>
        <?php if ($results && $results->num_rows > 0): ?>
            <div class="search-results-info">
                Found <strong><?php echo $results->num_rows; ?></strong> result<?php echo ($results->num_rows !== 1) ? 's' : ''; ?>
                for "<strong><?php echo htmlspecialchars($keyword); ?></strong>"
            </div>
            <div class="cards-grid">
                <?php $show_actions = true; ?>
                <?php while ($row = $results->fetch_assoc()): ?>
                    <?php include 'components/item_card.php'; ?>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">🔍</div>
                <h3>No results found</h3>
                <p>Nothing matched "<?php echo htmlspecialchars($keyword); ?>". Try a different search.</p>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php include 'components/footer.php'; ?>
<?php if (isset($stmt)) $stmt->close(); $conn->close(); ?>
