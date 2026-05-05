<?php
/**
 * MiskStone ERP — Mixing Station
 * Shows all batches currently in the "Mixing" phase.
 * Production officers can:
 *   - Monitor mixing progress
 *   - Advance a batch from Mixing → Curing
 */

$mixingBatches = $pdo->query("
    SELECT po.*, im.item_name, r.recipe_name
    FROM production_orders po
    JOIN item_master im ON po.item_id = im.item_id
    LEFT JOIN recipes r ON po.recipe_id = r.recipe_id
    WHERE po.status = 'Mixing'
    ORDER BY po.production_date DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Get BOM bills for these batches
$bomBills = [];
if (!empty($mixingBatches)) {
    $prodIds = array_column($mixingBatches, 'production_id');
    $placeholders = implode(',', array_fill(0, count($prodIds), '?'));
    $billStmt = $pdo->prepare("
        SELECT bb.*, 
               (SELECT COUNT(*) FROM bom_bill_lines bl WHERE bl.bill_id = bb.bill_id) as line_count
        FROM bom_bills bb 
        WHERE bb.production_id IN ({$placeholders})
    ");
    $billStmt->execute($prodIds);
    foreach ($billStmt->fetchAll(PDO::FETCH_ASSOC) as $bill) {
        $bomBills[$bill['production_id']] = $bill;
    }
}
?>

<div class="dashboard-container">
    <div class="welcome-banner" style="background: linear-gradient(135deg, #1e293b, #f59e0b); color: white; padding: 2rem; border-radius: 16px; margin-bottom: 2rem;">
        <h1 style="margin-bottom: 0.5rem;"><i class="fa-solid fa-blender" style="margin-right: 0.75rem;"></i>Mixing Station</h1>
        <p style="opacity: 0.9;">Batches currently being mixed. Raw materials have been consumed. Advance to Curing when mixing is complete.</p>
    </div>

    <!-- Stats -->
    <div class="card" style="padding: 1rem; margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center;">
        <span style="font-weight: 600;"><i class="fa-solid fa-blender" style="color: #f59e0b; margin-right: 0.5rem;"></i><?= count($mixingBatches) ?> batch(es) currently mixing</span>
        <a href="<?= BASE_URL ?>/app.php?view=mix_batches" style="font-size: 0.85rem; color: var(--accent-primary); text-decoration: none; font-weight: 600;">
            <i class="fa-solid fa-arrow-left"></i> Back to All Batches
        </a>
    </div>

    <?php if (empty($mixingBatches)): ?>
        <div class="card" style="text-align: center; padding: 4rem;">
            <i class="fa-solid fa-blender" style="font-size: 3rem; color: #f59e0b; opacity: 0.3; display: block; margin-bottom: 1rem;"></i>
            <h3 style="color: var(--text-secondary);">No batches currently in mixing</h3>
            <p style="color: var(--text-secondary); font-size: 0.9rem;">Start a new batch from the <a href="<?= BASE_URL ?>/app.php?view=mix_batches">Mix Batches</a> page.</p>
        </div>
    <?php else: ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(420px, 1fr)); gap: 1.5rem;">
            <?php foreach ($mixingBatches as $batch): ?>
                <?php $bill = $bomBills[$batch['production_id']] ?? null; ?>
                <div class="card" style="border-left: 5px solid #f59e0b; padding: 0; overflow: hidden;">
                    <div style="padding: 1.25rem;">
                        <!-- Header -->
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                            <span style="font-weight: 800; font-family: monospace; font-size: 1.05rem;"><?= htmlspecialchars($batch['production_id']) ?></span>
                            <span style="background: #fef3c7; color: #b45309; padding: 0.2rem 0.6rem; border-radius: 6px; font-weight: 700; font-size: 0.8rem;">
                                <i class="fa-solid fa-blender"></i> MIXING
                            </span>
                        </div>

                        <!-- Recipe + Mix Info -->
                        <div style="margin-bottom: 0.75rem;">
                            <div style="font-weight: 600; font-size: 1rem; margin-bottom: 0.25rem;">
                                <i class="fa-solid fa-flask" style="color: var(--accent-primary); margin-right: 0.25rem;"></i>
                                <?= htmlspecialchars($batch['recipe_name'] ?? $batch['item_name']) ?>
                            </div>
                            <div style="font-size: 0.85rem; color: var(--text-secondary); display: flex; gap: 1rem;">
                                <span><i class="fa-solid fa-layer-group"></i> <?= (int)$batch['target_quantity'] ?> mix run(s)</span>
                                <span><i class="fa-solid fa-calendar"></i> <?= $batch['production_date'] ? date('M d', strtotime($batch['production_date'])) : 'N/A' ?></span>
                                <?php if ($batch['machine_id']): ?>
                                    <span><i class="fa-solid fa-gear"></i> <?= htmlspecialchars($batch['machine_id']) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- BOM Cost -->
                        <?php if ($bill): ?>
                            <div style="background: #f8fafc; border-radius: 8px; padding: 0.75rem; display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    <span style="font-size: 0.75rem; text-transform: uppercase; font-weight: 700; color: var(--text-secondary);">BOM Bill #<?= $bill['bill_id'] ?></span>
                                    <div style="font-weight: 700; color: var(--accent-primary); font-size: 1.1rem;"><?= number_format($bill['total_material_cost'], 2) ?> JOD</div>
                                </div>
                                <span style="font-size: 0.8rem; color: var(--text-secondary);"><?= $bill['line_count'] ?> materials consumed</span>
                            </div>
                        <?php endif; ?>

                        <!-- Progress -->
                        <div style="margin-top: 0.75rem;">
                            <div style="display: flex; gap: 3px;">
                                <div style="flex: 1; height: 5px; border-radius: 3px; background: #94a3b8;"></div>
                                <div style="flex: 1; height: 5px; border-radius: 3px; background: #f59e0b;"></div>
                                <div style="flex: 1; height: 5px; border-radius: 3px; background: #e2e8f0;"></div>
                                <div style="flex: 1; height: 5px; border-radius: 3px; background: #e2e8f0;"></div>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-top: 0.2rem; font-size: 0.6rem; color: var(--text-secondary);">
                                <span style="color: #94a3b8;">Planned</span>
                                <span style="color: #f59e0b; font-weight: 700;">Mixing ●</span>
                                <span>Curing</span>
                                <span>Complete</span>
                            </div>
                        </div>
                    </div>

                    <!-- Action -->
                    <div style="padding: 0.75rem 1.25rem; background: #fffbeb; border-top: 1px solid #fde68a;">
                        <button onclick="advanceToCuring('<?= htmlspecialchars($batch['production_id']) ?>')" 
                                style="width: 100%; background: #3b82f6; color: white; border: none; padding: 0.7rem; border-radius: 8px; font-weight: 700; cursor: pointer; font-size: 0.9rem;">
                            <i class="fa-solid fa-forward"></i> Finish Mixing → Start Curing
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
function advanceToCuring(prodId) {
    if (!confirm('Finish mixing for ' + prodId + ' and start curing?')) return;

    const fd = new FormData();
    fd.append('production_id', prodId);
    fd.append('status', 'Curing');

    fetch('<?= BASE_URL ?>/modules/production/update_batch.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) location.reload();
            else alert('Error: ' + (data.error || 'Unknown'));
        })
        .catch(() => alert('Network error'));
}
</script>
