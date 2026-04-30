<?php
session_start();
require_once 'db.php';

if (!isLoggedIn()) { header('Location: login.php'); exit; }

$page_title = 'Report Item — Lost & Found System';
$active_page = 'add';
include 'components/header.php';
include 'components/navbar.php';
?>

<div class="container">
    <div class="page-header">
        <h1>Report an Item</h1>
        <p>Describe the item and where it was lost or found. Your submission will be reviewed by an admin.</p>
    </div>

    <div class="form-card form-wide">
        <div class="alert alert-info">Submissions require admin approval before appearing publicly.</div>

        <form action="submit_item.php" method="POST">
            <div class="form-group">
                <label for="title">Item Title <span class="required">*</span></label>
                <input type="text" id="title" name="title" class="form-control"
                       placeholder="e.g. Blue Leather Wallet" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="category">Category <span class="required">*</span></label>
                    <select id="category" name="category" class="form-control" required>
                        <option value="" disabled selected>Select category</option>
                        <?php
                        $categories = ['Electronics','Documents','Clothing','Accessories','Keys','Bags','Wallets','ID Cards','Books','Other'];
                        foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat; ?>"><?php echo $cat; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="type">Type <span class="required">*</span></label>
                    <select id="type" name="type" class="form-control" required>
                        <option value="" disabled selected>Lost or Found?</option>
                        <option value="Lost">🔴 I lost this item</option>
                        <option value="Found">🟢 I found this item</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="location">Location <span class="required">*</span></label>
                <input type="text" id="location" name="location" class="form-control"
                       placeholder="Where was it lost/found? e.g. Campus Library" required>
            </div>

            <div class="form-group">
                <label for="description">Description <span class="required">*</span></label>
                <textarea id="description" name="description" class="form-control"
                          placeholder="Provide details — color, brand, identifying features..."
                          required></textarea>
            </div>

            <button type="submit" class="btn btn-primary" style="width:100%;">Submit for Review</button>
        </form>
    </div>
</div>

<?php include 'components/footer.php'; ?>
