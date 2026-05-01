<style>
.cards-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 14px; }
.card { background: #fff; border: 1px solid #ccc; }
.card-strip { height: 4px; }
.card-strip-lost { background: #c0392b; }
.card-strip-found { background: #27ae60; }
.card-body { padding: 14px; }
.card-top { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px; }
.card-title { font-size: 1rem; font-weight: bold; }
.card-description { color: #555; font-size: 0.9rem; margin-bottom: 10px; }
.card-tags { margin-bottom: 8px; }
.card-meta { display: flex; justify-content: space-between; font-size: 0.82rem; color: #888; border-top: 1px solid #eee; padding-top: 8px; margin-top: 8px; }
.card-author { color: #c0392b; font-weight: bold; }
.card-actions { padding: 10px 14px; border-top: 1px solid #eee; background: #f9f9f9; }
</style>
                    <div class="card">
                        <div class="card-strip <?php echo ($row['type'] === 'Lost') ? 'card-strip-lost' : 'card-strip-found'; ?>"></div>
                        <div class="card-body">
                            <div class="card-top">
                                <span class="card-title"><?php echo htmlspecialchars($row['title']); ?></span>
                                <span class="badge <?php echo ($row['type'] === 'Lost') ? 'badge-lost' : 'badge-found'; ?>">
                                    <?php echo $row['type']; ?>
                                </span>
                            </div>

                            <div class="card-tags">
                                <span class="badge badge-cat"><?php echo htmlspecialchars($row['category']); ?></span>
                                <?php if (!empty($row['location'])): ?>
                                    <span class="badge badge-cat">📍 <?php echo htmlspecialchars($row['location']); ?></span>
                                <?php endif; ?>
                            </div>

                            <p class="card-description"><?php echo htmlspecialchars($row['description']); ?></p>

                            <div class="card-meta">
                                <span class="card-date">📅 <?php echo date('M d, Y', strtotime($row['date'])); ?></span>
                                <?php if (!empty($row['posted_by'])): ?>
                                    <span class="card-author">by <?php echo htmlspecialchars($row['posted_by']); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if (!empty($show_actions) && isLoggedIn() && $row['status'] === 'approved' && $row['user_id'] != $_SESSION['user_id']): ?>
                            <div class="card-actions">
                                <?php if ($row['type'] === 'Lost'): ?>
                                    <a href="claim_item.php?id=<?php echo $row['id']; ?>" class="btn btn-claim btn-sm" style="width:100%;">
                                        🙋 I Found This Item
                                    </a>
                                <?php else: ?>
                                    <a href="claim_item.php?id=<?php echo $row['id']; ?>" class="btn btn-resolve btn-sm" style="width:100%;">
                                        🙋 This is Mine
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
