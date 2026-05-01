<link rel="stylesheet" href="components/navbar.css">
<nav class="navbar">
    <div class="navbar-inner">
        <div class="navbar-brand">🔍 Lost & Found</div>
        <ul class="navbar-links">
            <li><a href="index.php" <?php if ($active_page == 'home') echo 'class="active"'; ?>>Home</a></li>
            <li><a href="search.php" <?php if ($active_page == 'search') echo 'class="active"'; ?>>Search</a></li>
            <?php if (isLoggedIn()): ?>
                <li><a href="add_item.php" <?php if ($active_page == 'add') echo 'class="active"'; ?>>Add Item</a></li>
                <li><a href="my_items.php" <?php if ($active_page == 'my_items') echo 'class="active"'; ?>>My Items</a></li>
                <?php if (isAdmin()): ?>
                    <li><a href="admin_dashboard.php" <?php if ($active_page == 'dashboard') echo 'class="active"'; ?>>Dashboard</a></li>
                <?php endif; ?>
                <li><a href="logout.php">Logout</a></li>
            <?php else: ?>
                <li><a href="login.php" <?php if ($active_page == 'login') echo 'class="active"'; ?>>Login</a></li>
                <li><a href="register.php" <?php if ($active_page == 'register') echo 'class="active"'; ?>>Register</a></li>
            <?php endif; ?>
        </ul>
    </div>
</nav>
