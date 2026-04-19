<?php
/**
 * MiskStone ERP — Mix Recipes
 * Shows recipe inputs (raw materials) AND outputs (finished goods).
 * Each recipe = one mix dough → many finished goods.
 */

// Fetch all recipes with their inputs and outputs
$recipes = $pdo->query("SELECT * FROM recipes ORDER BY recipe_id")->fetchAll(PDO::FETCH_ASSOC);

$recipeData = [];
foreach ($recipes as $recipe) {
    $rid = $recipe['recipe_id'];

    // Inputs (raw materials)
    $inputsStmt = $pdo->prepare("
        SELECT ri.raw_material_id, ri.quantity_required, im.item_name, im.base_uom, im.standard_cost
        FROM recipe_ingredients ri
        JOIN item_master im ON ri.raw_material_id = im.item_id
        WHERE ri.recipe_id = ?
    ");
    $inputsStmt->execute([$rid]);
    $inputs = $inputsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Get current stock for each input
    foreach ($inputs as &$inp) {
        $stockStmt = $pdo->prepare("SELECT COALESCE(SUM(quantity_change), 0) FROM inventory_ledger WHERE item_id = ?");
        $stockStmt->execute([$inp['raw_material_id']]);
        $inp['current_stock'] = (float)$stockStmt->fetchColumn();
    }
    unset($inp);

    // Outputs (finished goods from mix_outputs)
    $outputsStmt = $pdo->prepare("
        SELECT mo.output_id, mo.item_id, mo.output_quantity, im.item_name
        FROM mix_outputs mo
        JOIN item_master im ON mo.item_id = im.item_id
        WHERE mo.recipe_id = ?
        ORDER BY mo.output_quantity DESC
    ");
    $outputsStmt->execute([$rid]);
    $outputs = $outputsStmt->fetchAll(PDO::FETCH_ASSOC);

    $recipeData[$rid] = [
        'info' => $recipe,
        'inputs' => $inputs,
        'outputs' => $outputs,
    ];
}
?>

<div class="dashboard-container">
    <div class="welcome-banner" style="background: linear-gradient(135deg, #1e293b, #06b6d4); color: white; padding: 2rem; border-radius: 16px; margin-bottom: 2rem;">
        <h1 style="margin-bottom: 0.5rem;"><i class="fa-solid fa-flask" style="margin-right: 0.75rem;"></i>Mix Recipes</h1>
        <p style="opacity: 0.9;">Each mix recipe consumes raw materials and produces multiple finished goods — like a dough pressed into different molds.</p>
    </div>

    <?php foreach ($recipeData as $rid => $data): ?>
        <?php $recipe = $data['info']; ?>
        <div class="card" style="margin-bottom: 2rem; overflow: hidden;">
            <!-- Recipe Header -->
            <div style="background: linear-gradient(135deg, #f8fafc, #f0f9ff); padding: 1.5rem; border-bottom: 2px solid #bae6fd;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h2 style="margin: 0; font-size: 1.25rem; color: #0f172a;">
                            <i class="fa-solid fa-flask" style="color: #06b6d4; margin-right: 0.5rem;"></i>
                            <?= htmlspecialchars($recipe['recipe_name']) ?>
                        </h2>
                        <p style="margin: 0.25rem 0 0; color: #64748b; font-size: 0.85rem;">
                            Recipe #<?= $rid ?> · Base yield: <?= $recipe['base_yield_qty'] ?> mix run
                        </p>
                    </div>
                    <span class="badge completed" style="font-size: 0.8rem;">Active</span>
                </div>
            </div>

            <div style="padding: 1.5rem;">
                <!-- Two-Column Layout: Inputs | Outputs -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                    
                    <!-- INPUTS (Raw Materials Consumed) -->
                    <div>
                        <h3 style="margin: 0 0 1rem; font-size: 0.85rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #ef4444;">
                            <i class="fa-solid fa-arrow-down" style="margin-right: 0.25rem;"></i> Raw Material Inputs (Consumed)
                        </h3>
                        <div style="border: 1px solid var(--border-color); border-radius: 10px; overflow: hidden;">
                            <table class="data-table" style="margin: 0;">
                                <thead>
                                    <tr>
                                        <th style="padding: 0.6rem 0.75rem; font-size: 0.75rem;">Material</th>
                                        <th style="padding: 0.6rem 0.75rem; font-size: 0.75rem;">Per Mix</th>
                                        <th style="padding: 0.6rem 0.75rem; font-size: 0.75rem;">Stock</th>
                                        <th style="padding: 0.6rem 0.75rem; font-size: 0.75rem;">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($data['inputs'] as $inp): ?>
                                        <?php
                                        $sufficient = $inp['current_stock'] >= $inp['quantity_required'];
                                        $low = $inp['current_stock'] >= $inp['quantity_required'] * 0.5 && !$sufficient;
                                        ?>
                                        <tr style="background: <?= !$sufficient ? '#fef2f2' : '' ?>;">
                                            <td style="padding: 0.5rem 0.75rem; font-size: 0.85rem; font-weight: 600;"><?= htmlspecialchars($inp['item_name']) ?></td>
                                            <td style="padding: 0.5rem 0.75rem; font-size: 0.85rem;"><?= $inp['quantity_required'] ?> <?= $inp['base_uom'] ?></td>
                                            <td style="padding: 0.5rem 0.75rem; font-size: 0.85rem; color: #64748b;"><?= number_format($inp['current_stock'], 0) ?></td>
                                            <td style="padding: 0.5rem 0.75rem;">
                                                <?php if ($sufficient): ?>
                                                    <span style="color: #22c55e; font-size: 0.8rem; font-weight: 600;">✅</span>
                                                <?php elseif ($low): ?>
                                                    <span style="color: #f59e0b; font-size: 0.8rem; font-weight: 600;">⚠️</span>
                                                <?php else: ?>
                                                    <span style="color: #ef4444; font-size: 0.8rem; font-weight: 600;">❌</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- OUTPUTS (Finished Goods Produced) -->
                    <div>
                        <h3 style="margin: 0 0 1rem; font-size: 0.85rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #22c55e;">
                            <i class="fa-solid fa-arrow-up" style="margin-right: 0.25rem;"></i> Finished Good Outputs (Produced)
                        </h3>
                        <div style="border: 1px solid var(--border-color); border-radius: 10px; overflow: hidden;">
                            <table class="data-table" style="margin: 0;">
                                <thead>
                                    <tr>
                                        <th style="padding: 0.6rem 0.75rem; font-size: 0.75rem;">Finished Good</th>
                                        <th style="padding: 0.6rem 0.75rem; font-size: 0.75rem;">Qty Per Mix</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($data['outputs'])): ?>
                                        <tr><td colspan="2" style="text-align: center; padding: 1rem; color: var(--text-secondary);">No outputs defined</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($data['outputs'] as $out): ?>
                                            <tr>
                                                <td style="padding: 0.5rem 0.75rem; font-size: 0.85rem; font-weight: 600;"><?= htmlspecialchars($out['item_name']) ?></td>
                                                <td style="padding: 0.5rem 0.75rem; font-size: 0.85rem; font-weight: 700; color: var(--accent-primary);">× <?= (int)$out['output_quantity'] ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <p style="text-align: right; font-size: 0.75rem; color: #94a3b8; margin-top: 0.5rem;">
                            Total: <?= array_sum(array_column($data['outputs'], 'output_quantity')) ?> units per mix run
                        </p>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
