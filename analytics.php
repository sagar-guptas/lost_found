<?php
session_start();
require_once 'db.php';

if (!isAdmin()) { header('Location: login.php'); exit; }

$filter_cat    = $_GET['category'] ?? '';
$filter_from   = $_GET['from'] ?? '';
$filter_to     = $_GET['to'] ?? '';
$filter_status = $_GET['status'] ?? '';

$cat_data = [];
$cat_result = $conn->query("SELECT category, COUNT(*) as cnt FROM items WHERE status != 'rejected' GROUP BY category ORDER BY cnt DESC");
while ($r = $cat_result->fetch_assoc()) { $cat_data[$r['category']] = (int)$r['cnt']; }

$status_data = [];
$status_result = $conn->query("SELECT status, COUNT(*) as cnt FROM items GROUP BY status");
while ($r = $status_result->fetch_assoc()) { $status_data[$r['status']] = (int)$r['cnt']; }

$type_data = [];
$type_result = $conn->query("SELECT type, COUNT(*) as cnt FROM items WHERE status != 'rejected' GROUP BY type");
while ($r = $type_result->fetch_assoc()) { $type_data[$r['type']] = (int)$r['cnt']; }

$timeline_data = [];
$timeline_result = $conn->query("SELECT DATE(date) as d, COUNT(*) as cnt FROM items WHERE date >= DATE_SUB(NOW(), INTERVAL 30 DAY) GROUP BY DATE(date) ORDER BY d ASC");
while ($r = $timeline_result->fetch_assoc()) { $timeline_data[$r['d']] = (int)$r['cnt']; }

$resolution_data = [];
$res_result = $conn->query("SELECT category, SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved, COUNT(*) as total FROM items WHERE status != 'rejected' GROUP BY category");
while ($r = $res_result->fetch_assoc()) {
    $rate = ($r['total'] > 0) ? round(($r['resolved'] / $r['total']) * 100) : 0;
    $resolution_data[$r['category']] = $rate;
}

$where = ["1=1"];
$fparams = [];
$ftypes = '';

if (!empty($filter_cat)) { $where[] = "i.category = ?"; $fparams[] = $filter_cat; $ftypes .= 's'; }
if (!empty($filter_status)) { $where[] = "i.status = ?"; $fparams[] = $filter_status; $ftypes .= 's'; }
if (!empty($filter_from)) { $where[] = "DATE(i.date) >= ?"; $fparams[] = $filter_from; $ftypes .= 's'; }
if (!empty($filter_to)) { $where[] = "DATE(i.date) <= ?"; $fparams[] = $filter_to; $ftypes .= 's'; }

$where_sql = implode(' AND ', $where);
$filtered_sql = "SELECT i.*, u.full_name AS posted_by FROM items i LEFT JOIN users u ON i.user_id = u.id WHERE $where_sql ORDER BY i.date DESC LIMIT 50";

$fstmt = $conn->prepare($filtered_sql);
if (!empty($fparams)) { $fstmt->bind_param($ftypes, ...$fparams); }
$fstmt->execute();
$filtered_result = $fstmt->get_result();

$pending_items  = $conn->query("SELECT COUNT(*) as c FROM items WHERE status='pending'")->fetch_assoc()['c'];
$pending_claims = $conn->query("SELECT COUNT(*) as c FROM claims WHERE status='pending'")->fetch_assoc()['c'];

$page_title = 'Analytics — Lost & Found System';
$active_page = 'analytics';
$extra_head = '<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>';
include 'components/header.php';
include 'components/admin_navbar.php';
?>

<div class="container">
    <div class="page-header">
        <h1>📊 Analytics Dashboard</h1>
        <p>Visual insights into lost and found items, categories, and resolution rates.</p>
    </div>

    <div class="charts-grid">
        <div class="chart-card">
            <h3>📊 Items by Category</h3>
            <canvas id="categoryChart"></canvas>
        </div>
        <div class="chart-card">
            <h3>🍩 Lost vs Found</h3>
            <canvas id="typeChart"></canvas>
        </div>
        <div class="chart-card">
            <h3>📈 Items Over Last 30 Days</h3>
            <canvas id="timelineChart"></canvas>
        </div>
        <div class="chart-card">
            <h3>🍩 Status Distribution</h3>
            <canvas id="statusChart"></canvas>
        </div>
        <div class="chart-card" style="grid-column: 1 / -1;">
            <h3>📊 Resolution Rate by Category (%)</h3>
            <canvas id="resolutionChart" style="max-height:240px;"></canvas>
        </div>
    </div>

    <h2 style="font-size:1.1rem;margin:24px 0 14px;">🔎 Filtered Data View</h2>

    <div class="filter-bar">
        <form action="analytics.php" method="GET">
            <div class="filter-group">
                <label>Category</label>
                <select name="category" class="form-control">
                    <option value="">All</option>
                    <?php
                    $categories = ['Electronics','Documents','Clothing','Accessories','Keys','Bags','Wallets','ID Cards','Books','Other'];
                    foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat; ?>" <?php echo ($filter_cat === $cat) ? 'selected' : ''; ?>><?php echo $cat; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label>Status</label>
                <select name="status" class="form-control">
                    <option value="">All</option>
                    <option value="pending" <?php echo ($filter_status === 'pending') ? 'selected' : ''; ?>>Pending</option>
                    <option value="approved" <?php echo ($filter_status === 'approved') ? 'selected' : ''; ?>>Approved</option>
                    <option value="claimed" <?php echo ($filter_status === 'claimed') ? 'selected' : ''; ?>>Claimed</option>
                    <option value="resolved" <?php echo ($filter_status === 'resolved') ? 'selected' : ''; ?>>Resolved</option>
                    <option value="rejected" <?php echo ($filter_status === 'rejected') ? 'selected' : ''; ?>>Rejected</option>
                </select>
            </div>
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

    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Type</th>
                    <th>Category</th>
                    <th>Status</th>
                    <th>Posted By</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($filtered_result && $filtered_result->num_rows > 0): ?>
                    <?php while ($row = $filtered_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['title']); ?></td>
                            <td><span class="badge <?php echo ($row['type'] === 'Lost') ? 'badge-lost' : 'badge-found'; ?>"><?php echo $row['type']; ?></span></td>
                            <td><?php echo htmlspecialchars($row['category']); ?></td>
                            <td><span class="badge badge-<?php echo $row['status']; ?>"><?php echo ucfirst($row['status']); ?></span></td>
                            <td><?php echo htmlspecialchars($row['posted_by'] ?? 'System'); ?></td>
                            <td><?php echo date('M d, Y', strtotime($row['date'])); ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="6" style="text-align:center;color:#999;padding:24px;">No data matches your filters.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    var colors = ['#3f51b5','#e53935','#43a047','#ff9800','#7b1fa2','#00acc1','#e91e63','#ffc107','#009688','#1a237e'];

    new Chart(document.getElementById('categoryChart'), {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_keys($cat_data)); ?>,
            datasets: [{
                label: 'Items',
                data: <?php echo json_encode(array_values($cat_data)); ?>,
                backgroundColor: colors
            }]
        },
        options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
    });

    new Chart(document.getElementById('typeChart'), {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode(array_keys($type_data)); ?>,
            datasets: [{ data: <?php echo json_encode(array_values($type_data)); ?>, backgroundColor: ['#e53935','#43a047'] }]
        },
        options: { responsive: true }
    });

    new Chart(document.getElementById('timelineChart'), {
        type: 'line',
        data: {
            labels: <?php echo json_encode(array_map(function($d) { return date('M d', strtotime($d)); }, array_keys($timeline_data))); ?>,
            datasets: [{
                label: 'Items',
                data: <?php echo json_encode(array_values($timeline_data)); ?>,
                borderColor: '#3f51b5', backgroundColor: 'rgba(63,81,181,0.1)',
                fill: true, tension: 0.4
            }]
        },
        options: { responsive: true, scales: { y: { beginAtZero: true } } }
    });

    new Chart(document.getElementById('statusChart'), {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode(array_map('ucfirst', array_keys($status_data))); ?>,
            datasets: [{
                data: <?php echo json_encode(array_values($status_data)); ?>,
                backgroundColor: <?php echo json_encode(array_map(function($s) {
                    $map = ['pending'=>'#ff9800','approved'=>'#43a047','rejected'=>'#e53935','claimed'=>'#00acc1','resolved'=>'#7b1fa2'];
                    return $map[$s] ?? '#999';
                }, array_keys($status_data))); ?>
            }]
        },
        options: { responsive: true }
    });

    new Chart(document.getElementById('resolutionChart'), {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_keys($resolution_data)); ?>,
            datasets: [{ label: 'Resolution Rate %', data: <?php echo json_encode(array_values($resolution_data)); ?>, backgroundColor: '#7b1fa2' }]
        },
        options: { indexAxis: 'y', responsive: true, plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true, max: 100 } } }
    });
</script>

<?php include 'components/footer.php'; ?>
<?php $fstmt->close(); $conn->close(); ?>
