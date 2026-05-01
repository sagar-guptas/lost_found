<style>
.empty-state { text-align: center; padding: 40px 20px; color: #999; }
.empty-state .empty-icon { font-size: 2.5rem; margin-bottom: 12px; }
.empty-state h3 { font-size: 1rem; color: #666; margin-bottom: 6px; }
</style>
        <div class="empty-state">
            <div class="empty-icon"><?php echo $empty_icon ?? '📦'; ?></div>
            <h3><?php echo htmlspecialchars($empty_title ?? 'Nothing here'); ?></h3>
            <p><?php echo $empty_message ?? ''; ?></p>
            <?php if (!empty($empty_action_url) && !empty($empty_action_label)): ?>
                <a href="<?php echo htmlspecialchars($empty_action_url); ?>" class="btn btn-primary" style="margin-top:16px;"><?php echo htmlspecialchars($empty_action_label); ?></a>
            <?php endif; ?>
        </div>
