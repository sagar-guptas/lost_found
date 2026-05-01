<style>
.alert { padding: 10px 14px; border: 1px solid #ccc; margin-bottom: 14px; font-size: 0.9rem; }
.alert-success { background: #d4efdf; color: #1e8449; border-color: #a9dfbf; }
.alert-error   { background: #fadbd8; color: #922b21; border-color: #f1948a; }
.alert-info    { background: #d6eaf8; color: #1a5276; border-color: #a9cce3; }
.alert-warning { background: #fef9e7; color: #7d6608; border-color: #f9e79f; }
</style>
        <div class="alert alert-<?php echo htmlspecialchars($alert_type); ?>">
                <?php echo htmlspecialchars($alert_message); ?></div>
