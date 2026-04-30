<nav class="navbar">
    <div class="navbar-inner">
        <div class="navbar-brand">⚙️ Admin Panel</div>
        <ul class="navbar-links">
            <li><a href="index.php">View Site</a></li>
            <li><a href="admin_dashboard.php" <?php if ($active_page == 'dashboard') echo 'class="active"'; ?>>Dashboard</a></li>
            <li>
                <a href="approve_items.php" <?php if ($active_page == 'items') echo 'class="active"'; ?>>
                    Items
                    <?php if (!empty($pending_items) && $pending_items > 0): ?>
                        <span style="background:#c0392b;color:#fff;font-size:0.7rem;padding:2px 6px;border-radius:8px;"><?php echo $pending_items; ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li>
                <a href="approve_claims.php" <?php if ($active_page == 'claims') echo 'class="active"'; ?>>
                    Claims
                    <?php if (!empty($pending_claims) && $pending_claims > 0): ?>
                        <span style="background:#c0392b;color:#fff;font-size:0.7rem;padding:2px 6px;border-radius:8px;"><?php echo $pending_claims; ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <?php if (isSuperAdmin()): ?>
                <li><a href="manage_admins.php" <?php if ($active_page == 'admins') echo 'class="active"'; ?>>Admins</a></li>
            <?php endif; ?>
            <li><a href="analytics.php" <?php if ($active_page == 'analytics') echo 'class="active"'; ?>>Analytics</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </div>
</nav>
