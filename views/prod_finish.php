<?php
/**
 * MiskStone ERP — Finish Production
 * Shows batches ready for completion (status = Curing or Completed).
 * Completing a batch credits ALL finished goods to inventory.
 * Also shows recently completed batches for reference.
 */

// Batches ready to finish (Curing stage)
$readyBatches = $pdo->query("
    SELECT po.*, im.item_name, r.recipe_name, r.recipe_id as linked_recipe
    FROM production_orders po
    JOIN item_master im ON po.item_id = im.item_id
    LEFT JOIN recipes r ON po.recipe_id = r.recipe_id
    WHERE po.status = 'Curing'
    ORDER BY po.production_date ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Recently completed batches
$completedBatches = $pdo->query("
    SELECT po.*, im.item_name, r.recipe_name, bb.total_material_cost
    FROM production_orders po
    JOIN item_master im ON po.item_id = im.item_id
    LEFT JOIN recipes r ON po.recipe_id = r.recipe_id
    LEFT JOIN bom_bills bb ON bb.production_id = po.production_id
    WHERE po.status = 'Completed'
    ORDER BY po.created_at DESC
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

// Get mix_outputs for ready batches to show what FG they'll produce
function getMixOutputs($pdo, $recipeId) {
    if (!$recipeId) return [];
    $stmt = $pdo->prepare("
        SELECT mo.item_id, mo.output_quantity, im.item_name 
        FROM mix_outputs mo 
        JOIN item_master im ON mo.item_id = im.item_id
        WHERE mo.recipe_id = ?
    ");
    $stmt->execute([$recipeId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<div class="dashboard-container">
    <div class="welcome-banner" style="background: linear-gradient(135deg, #1e293b, #22c55e); color: white; padding: 2rem; border-radius: 16px; margin-bottom: 2rem;">
        <h1 style="margin-bottom: 0.5rem;"><i class="fa-solid fa-flag-checkered" style="margin-right: 0.75rem;"></i>Finish Production</h1>
        <p style="opacity: 0.9;">Complete production batches. Finishing a batch adds all finished goods to inventory and updates the sales order status.</p>
    </div>

    <!-- Ready to Complete -->
    <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1rem;">
        <div style="width: 12px; height: 12px; border-radius: 50%; background: #22c55e;"></div>
        <h3 style="margin: 0;">Ready to Complete</h3>
        <span class="badge" style="background: #22c55e20; color: #22c55e; font-weight: 700;"><?= count($readyBatches) ?></span>
    </div>

    <?php if (empty($readyBatches)): ?>
        <div class="card" style="text-align: center; padding: 3rem; margin-bottom: 2rem;">
            <i class="fa-solid fa-flag-checkered" style="font-size: 2.5rem; color: var(--text-secondary); opacity: 0.3; display: block; margin-bottom: 0.5rem;"></i>
            <h3 style="color: var(--text-secondary);">No batches ready for completion</h3>
            <p style="color: var(--text-secondary); font-size: 0.9rem;">Batches must finish curing before they can be completed.</p>
        </div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(450px, 1fr)); gap: 1.5rem; margin-bottom: 2.5rem;">
        <?php foreach ($readyBatches as $batch): ?>
            <?php
            $recipeId = $batch['linked_recipe'] ?: $batch['recipe_id'];
            $outputs = getMixOutputs($pdo, $recipeId);
            $scaleFactor = (float)$batch['target_quantity'] / max(1, 1); // target_qty = mix runs
            ?>
            <div class="card" style="border-left: 5px solid #22c55e; padding: 0; overflow: hidden;">
                <div style="padding: 1.25rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                        <span style="font-weight: 800; font-family: monospace; font-size: 1.05rem;"><?= htmlspecialchars($batch['production_id']) ?></span>
                        <span style="background: #dcfce7; color: #166534; padding: 0.2rem 0.6rem; border-radius: 6px; font-weight: 700; font-size: 0.8rem;">
                            <i class="fa-solid fa-check"></i> Ready to Complete
                        </span>
                    </div>

                    <div style="font-weight: 600; font-size: 1rem; margin-bottom: 0.5rem;">
                        <i class="fa-solid fa-flask" style="color: var(--accent-primary);"></i>
                        <?= htmlspecialchars($batch['recipe_name'] ?? $batch['item_name']) ?>
                        <span style="font-size: 0.85rem; font-weight: 400; color: var(--text-secondary);"> · <?= (int)$batch['target_quantity'] ?> mix run(s)</span>
                    </div>

                    <!-- What will be produced -->
                    <?php if (!empty($outputs)): ?>
                        <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 0.75rem; margin-bottom: 0.75rem;">
                            <div style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: #166534; margin-bottom: 0.4rem;">
                                <i class="fa-solid fa-box"></i> Finished Goods to be Produced
                            </div>
                            <?php foreach ($outputs as $out): ?>
                                <?php $qty = round($out['output_quantity'] * $batch['target_quantity'], 0); ?>
                                <div style="display: flex; justify-content: space-between; font-size: 0.85rem; padding: 0.2rem 0; border-bottom: 1px solid #dcfce7;">
                                    <span style="font-weight: 600; color: #15803d;"><?= htmlspecialchars($out['item_name']) ?></span>
                                    <span style="font-weight: 700; color: #166534;">+<?= $qty ?> units</span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Progress Bar -->
                    <div style="display: flex; gap: 3px;">
                        <div style="flex: 1; height: 5px; border-radius: 3px; background: #94a3b8;"></div>
                        <div style="flex: 1; height: 5px; border-radius: 3px; background: #f59e0b;"></div>
                        <div style="flex: 1; height: 5px; border-radius: 3px; background: #3b82f6;"></div>
                        <div style="flex: 1; height: 5px; border-radius: 3px; background: #22c55e;"></div>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-top: 0.2rem; font-size: 0.6rem; color: var(--text-secondary);">
                        <span style="color: #94a3b8;">Planned</span>
                        <span style="color: #f59e0b;">Mixed</span>
                        <span style="color: #3b82f6;">Cured</span>
                        <span style="color: #22c55e; font-weight: 700;">Complete →</span>
                    </div>
                </div>

                <!-- Complete Button -->
                <div style="padding: 0.75rem 1.25rem; background: #f0fdf4; border-top: 1px solid #bbf7d0;">
                    <button onclick="completeBatch('<?= htmlspecialchars($batch['production_id']) ?>')" 
                            style="width: 100%; background: #22c55e; color: white; border: none; padding: 0.8rem; border-radius: 10px; font-weight: 700; cursor: pointer; font-size: 1rem;">
                        <i class="fa-solid fa-flag-checkered"></i> Complete Production — Add FG to Inventory
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Recently Completed -->
    <?php if (!empty($completedBatches)): ?>
        <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1rem;">
            <div style="width: 12px; height: 12px; border-radius: 50%; background: #94a3b8;"></div>
            <h3 style="margin: 0;">Recently Completed</h3>
        </div>
        <div class="card" style="opacity: 0.8;">
            <table class="data-table" style="font-size: 0.85rem;">
                <thead>
                    <tr><th>Batch ID</th><th>Recipe</th><th>Mix Runs</th><th>BOM Cost</th><th>Date</th><th>Status</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($completedBatches as $b): ?>
                        <tr>
                            <td style="font-family: monospace; font-weight: 600;"><?= htmlspecialchars($b['production_id']) ?></td>
                            <td style="font-weight: 600;"><?= htmlspecialchars($b['recipe_name'] ?? $b['item_name']) ?></td>
                            <td><?= (int)$b['target_quantity'] ?></td>
                            <td style="font-weight: 700; color: var(--accent-primary);">
                                <?= $b['total_material_cost'] ? number_format($b['total_material_cost'], 2) . ' JOD' : '—' ?>
                            </td>
                            <td style="color: var(--text-secondary);"><?= $b['production_date'] ? date('M d', strtotime($b['production_date'])) : '—' ?></td>
                            <td><span class="badge completed">Completed</span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script>
function completeBatch(prodId) {
    if (!confirm('Complete production for ' + prodId + '?\n\nThis will:\n• Add finished goods to inventory\n• Record BOM expense in Finance\n• Update linked sales orders')) return;

    const fd = new FormData();
    fd.append('production_id', prodId);
    fd.append('status', 'Completed');
    fd.append('actual_yield', '1');

    fetch('<?= BASE_URL ?>/modules/production/update_batch.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                alert('Production complete! Finished goods added to inventory.');
                location.reload();
            } else alert('Error: ' + (data.error || 'Unknown'));
        })
        .catch(() => alert('Network error'));
}
</script>
