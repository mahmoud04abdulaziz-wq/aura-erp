<?php
/**
 * MiskStone ERP — Curing Station
 * Shows all batches in the "Curing" phase.
 * Advance from Curing → ready for Finish Production.
 */

$curingBatches = $pdo->query("
    SELECT po.*, im.item_name, r.recipe_name, r.curing_time_hours
    FROM production_orders po
    JOIN item_master im ON po.item_id = im.item_id
    LEFT JOIN recipes r ON po.recipe_id = r.recipe_id
    WHERE po.status = 'Curing'
    ORDER BY po.production_date ASC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="dashboard-container">
    <div class="welcome-banner" style="background: linear-gradient(135deg, #1e293b, #3b82f6); color: white; padding: 2rem; border-radius: 16px; margin-bottom: 2rem;">
        <h1 style="margin-bottom: 0.5rem;"><i class="fa-solid fa-hourglass-half" style="margin-right: 0.75rem;"></i>Curing Station</h1>
        <p style="opacity: 0.9;">Batches currently curing. When curing is complete, move them to Finish Production to credit finished goods.</p>
    </div>

    <div class="card" style="padding: 1rem; margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center;">
        <span style="font-weight: 600;"><i class="fa-solid fa-hourglass-half" style="color: #3b82f6; margin-right: 0.5rem;"></i><?= count($curingBatches) ?> batch(es) currently curing</span>
        <a href="<?= BASE_URL ?>/app.php?view=mix_batches" style="font-size: 0.85rem; color: var(--accent-primary); text-decoration: none; font-weight: 600;">
            <i class="fa-solid fa-arrow-left"></i> Back to All Batches
        </a>
    </div>

    <?php if (empty($curingBatches)): ?>
        <div class="card" style="text-align: center; padding: 4rem;">
            <i class="fa-solid fa-hourglass-half" style="font-size: 3rem; color: #3b82f6; opacity: 0.3; display: block; margin-bottom: 1rem;"></i>
            <h3 style="color: var(--text-secondary);">No batches currently curing</h3>
            <p style="color: var(--text-secondary); font-size: 0.9rem;">Finish mixing a batch to move it to the curing stage.</p>
        </div>
    <?php else: ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(420px, 1fr)); gap: 1.5rem;">
            <?php foreach ($curingBatches as $batch): ?>
                <?php
                $curingHours = $batch['curing_time_hours'] ?? 24;
                // Estimate time since batch started (rough — based on production_date)
                $startTime = strtotime($batch['production_date'] ?? $batch['created_at']);
                $elapsedHours = round((time() - $startTime) / 3600, 1);
                $curingProgress = min(100, round(($elapsedHours / max(1, $curingHours)) * 100));
                $isReady = $elapsedHours >= $curingHours;
                ?>
                <div class="card" style="border-left: 5px solid <?= $isReady ? '#22c55e' : '#3b82f6' ?>; padding: 0; overflow: hidden;">
                    <div style="padding: 1.25rem;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                            <span style="font-weight: 800; font-family: monospace; font-size: 1.05rem;"><?= htmlspecialchars($batch['production_id']) ?></span>
                            <span style="background: <?= $isReady ? '#dcfce7' : '#dbeafe' ?>; color: <?= $isReady ? '#166534' : '#1e40af' ?>; padding: 0.2rem 0.6rem; border-radius: 6px; font-weight: 700; font-size: 0.8rem;">
                                <?= $isReady ? '✅ Ready' : '⏳ Curing' ?>
                            </span>
                        </div>

                        <div style="font-weight: 600; font-size: 1rem; margin-bottom: 0.25rem;">
                            <i class="fa-solid fa-flask" style="color: var(--accent-primary); margin-right: 0.25rem;"></i>
                            <?= htmlspecialchars($batch['recipe_name'] ?? $batch['item_name']) ?>
                        </div>
                        <div style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 0.75rem;">
                            <span><i class="fa-solid fa-layer-group"></i> <?= (int)$batch['target_quantity'] ?> mix run(s)</span>
                            <span style="margin: 0 0.3rem; opacity: 0.5;">·</span>
                            <span><i class="fa-solid fa-clock"></i> Curing time: <?= $curingHours ?>h</span>
                        </div>

                        <!-- Curing Timer -->
                        <div style="background: #f8fafc; border-radius: 8px; padding: 0.75rem; margin-bottom: 0.75rem;">
                            <div style="display: flex; justify-content: space-between; font-size: 0.85rem; margin-bottom: 0.4rem;">
                                <span style="font-weight: 600;">Curing Progress</span>
                                <span style="color: <?= $isReady ? '#22c55e' : '#3b82f6' ?>; font-weight: 700;"><?= $curingProgress ?>%</span>
                            </div>
                            <div style="height: 8px; background: #e2e8f0; border-radius: 4px; overflow: hidden;">
                                <div style="height: 100%; width: <?= $curingProgress ?>%; background: <?= $isReady ? '#22c55e' : '#3b82f6' ?>; border-radius: 4px; transition: width 0.5s;"></div>
                            </div>
                            <div style="display: flex; justify-content: space-between; font-size: 0.75rem; color: var(--text-secondary); margin-top: 0.25rem;">
                                <span><?= $elapsedHours ?>h elapsed</span>
                                <span><?= $curingHours ?>h required</span>
                            </div>
                        </div>

                        <!-- Progress Bar -->
                        <div style="display: flex; gap: 3px;">
                            <div style="flex: 1; height: 5px; border-radius: 3px; background: #94a3b8;"></div>
                            <div style="flex: 1; height: 5px; border-radius: 3px; background: #f59e0b;"></div>
                            <div style="flex: 1; height: 5px; border-radius: 3px; background: #3b82f6;"></div>
                            <div style="flex: 1; height: 5px; border-radius: 3px; background: #e2e8f0;"></div>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-top: 0.2rem; font-size: 0.6rem; color: var(--text-secondary);">
                            <span style="color: #94a3b8;">Planned</span>
                            <span style="color: #f59e0b;">Mixed</span>
                            <span style="color: #3b82f6; font-weight: 700;">Curing ●</span>
                            <span>Complete</span>
                        </div>
                    </div>

                    <!-- Action -->
                    <div style="padding: 0.75rem 1.25rem; background: <?= $isReady ? '#f0fdf4' : '#f8fafc' ?>; border-top: 1px solid var(--border-color);">
                        <button onclick="finishCuring('<?= htmlspecialchars($batch['production_id']) ?>')" 
                                style="width: 100%; background: <?= $isReady ? '#22c55e' : '#94a3b8' ?>; color: white; border: none; padding: 0.7rem; border-radius: 8px; font-weight: 700; cursor: pointer; font-size: 0.9rem;"
                                <?= $isReady ? '' : 'title="Curing still in progress — you can still advance manually"' ?>>
                            <i class="fa-solid fa-forward"></i> Finish Curing → Ready for Completion
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
function finishCuring(prodId) {
    if (!confirm('Finish curing for batch ' + prodId + '?')) return;

    const fd = new FormData();
    fd.append('production_id', prodId);
    fd.append('status', 'Completed');

    fetch('<?= BASE_URL ?>/modules/production/update_batch.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                alert('Batch completed! Finished goods have been credited to inventory.');
                location.reload();
            } else alert('Error: ' + (data.error || 'Unknown'));
        })
        .catch(() => alert('Network error'));
}
</script>
