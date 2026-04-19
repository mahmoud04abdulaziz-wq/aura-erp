<?php
/**
 * MiskStone ERP — Mix Batches
 * Production batch management with mix-to-many FG output model.
 */

// Fetch all production orders
$batches = $pdo->query("
    SELECT po.*, im.item_name, r.recipe_name
    FROM production_orders po 
    JOIN item_master im ON po.item_id = im.item_id
    LEFT JOIN recipes r ON r.finished_item_id = po.item_id
    ORDER BY po.production_id DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Fetch recipes for "Start Batch" form
$recipes = $pdo->query("SELECT recipe_id, recipe_name FROM recipes ORDER BY recipe_id")->fetchAll(PDO::FETCH_ASSOC);

// Get recipe outputs for display
$recipeOutputs = [];
foreach ($recipes as $recipe) {
    $outputsStmt = $pdo->prepare("
        SELECT mo.item_id, mo.output_quantity, im.item_name 
        FROM mix_outputs mo
        JOIN item_master im ON mo.item_id = im.item_id
        WHERE mo.recipe_id = ?
    ");
    $outputsStmt->execute([$recipe['recipe_id']]);
    $recipeOutputs[$recipe['recipe_id']] = $outputsStmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<div class="dashboard-container">
    <div class="welcome-banner" style="background: linear-gradient(135deg, #1e293b, #7c3aed); color: white; padding: 2rem; border-radius: 16px; margin-bottom: 2rem;">
        <h1 style="margin-bottom: 0.5rem;"><i class="fa-solid fa-industry" style="margin-right: 0.75rem;"></i>Mix Batches</h1>
        <p style="opacity: 0.9;">Manage production mix runs. Each mix batch produces multiple finished goods.</p>
    </div>

    <!-- Stats -->
    <div class="stats-grid" style="margin-bottom: 2rem;">
        <?php 
        $active = count(array_filter($batches, fn($b) => $b['status'] === 'In Progress'));
        $completed = count(array_filter($batches, fn($b) => $b['status'] === 'Completed'));
        ?>
        <div class="stat-card" style="border-left: 4px solid #f59e0b;">
            <div class="stat-info">
                <h3 style="color: var(--text-secondary); font-size: 0.9rem;">Active Batches</h3>
                <p class="stat-value"><?= $active ?></p>
            </div>
        </div>
        <div class="stat-card" style="border-left: 4px solid #22c55e;">
            <div class="stat-info">
                <h3 style="color: var(--text-secondary); font-size: 0.9rem;">Completed</h3>
                <p class="stat-value"><?= $completed ?></p>
            </div>
        </div>
        <div class="stat-card" style="border-left: 4px solid var(--accent-primary);">
            <div class="stat-info">
                <h3 style="color: var(--text-secondary); font-size: 0.9rem;">Total Batches</h3>
                <p class="stat-value"><?= count($batches) ?></p>
            </div>
        </div>
    </div>

    <!-- Batches Table -->
    <div class="card">
        <div class="card-header">
            <h3>All Production Batches</h3>
        </div>
        <div style="overflow-x: auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Batch ID</th>
                        <th>Recipe</th>
                        <th>Target Qty</th>
                        <th>Actual Yield</th>
                        <th>Status</th>
                        <th>QA</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($batches)): ?>
                        <tr><td colspan="7" style="text-align:center; color: var(--text-secondary); padding: 2rem;">No production batches yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($batches as $batch): ?>
                            <tr>
                                <td style="font-weight: 600; font-family: monospace;"><?= htmlspecialchars($batch['production_id']) ?></td>
                                <td><?= htmlspecialchars($batch['recipe_name'] ?? $batch['item_name']) ?></td>
                                <td><?= (int)$batch['target_quantity'] ?></td>
                                <td><?= $batch['actual_yield'] ? (int)$batch['actual_yield'] : '—' ?></td>
                                <td>
                                    <?php
                                    $statusColors = [
                                        'Pending' => 'pending',
                                        'In Progress' => 'in-progress',
                                        'Completed' => 'completed',
                                    ];
                                    $badgeClass = $statusColors[$batch['status']] ?? 'gray';
                                    ?>
                                    <span class="badge <?= $badgeClass ?>"><?= $batch['status'] ?></span>
                                </td>
                                <td>
                                    <?php if ($batch['qa_status']): ?>
                                        <span class="badge <?= $batch['qa_status'] === 'Passed' ? 'completed' : ($batch['qa_status'] === 'Failed' ? 'pending' : 'in-progress') ?>">
                                            <?= $batch['qa_status'] ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="color: var(--text-secondary); font-size: 0.85rem;">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($batch['status'] !== 'Completed'): ?>
                                        <select onchange="updateBatchStatus('<?= $batch['production_id'] ?>', this.value)" 
                                                style="padding: 0.4rem 0.6rem; border: 1px solid var(--border-color); border-radius: 6px; font-size: 0.8rem; cursor: pointer;">
                                            <option value="">Change status...</option>
                                            <?php if ($batch['status'] === 'Pending'): ?>
                                                <option value="In Progress">Start Mixing</option>
                                            <?php endif; ?>
                                            <?php if ($batch['status'] === 'In Progress'): ?>
                                                <option value="Completed">Mark Completed</option>
                                            <?php endif; ?>
                                        </select>
                                    <?php else: ?>
                                        <span style="color: var(--success); font-size: 0.85rem;">✅ Done</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function updateBatchStatus(prodId, newStatus) {
    if (!newStatus) return;
    
    let body = 'production_id=' + encodeURIComponent(prodId) + '&status=' + encodeURIComponent(newStatus);
    
    // If completing, set actual_yield = target_quantity
    if (newStatus === 'Completed') {
        body += '&actual_yield=1'; // 1 mix run
    }

    fetch('<?= BASE_URL ?>/modules/production/update_batch.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: body
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(() => alert('Network error'));
}
</script>
