<?php
/**
 * AURA ERP — Bill of Materials (BOM) Explosion & Feasibility
 * Simulates scaling a recipe against current inventory and calculates Standard Cost.
 */

$recipes = $pdo->query("
    SELECT r.recipe_id, r.recipe_name, r.base_yield_qty, i.item_name as fg_name 
    FROM recipes r
    JOIN item_master i ON r.finished_item_id = i.item_id
")->fetchAll(PDO::FETCH_ASSOC);

$selectedRecipeId = $_GET['recipe_id'] ?? ($recipes[0]['recipe_id'] ?? null);
$simulateQty = max(1, (float)($_GET['simulate_qty'] ?? 100)); // Default simulate 100 units

$explosionData = [];
$totalUnitMaterialCost = 0.0;
$totalBatchMaterialCost = 0.0;
$isFeasible = true;
$selectedRecipeName = "";
$baseYield = 1.0;
$reportSuccess = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_to_finance') {
    $rId = (int)$_POST['recipe_id'];
    $simQty = (float)$_POST['simulate_qty'];
    $batchCost = (float)$_POST['batch_cost'];
    $rName = trim($_POST['recipe_name']);
    
    // Insert a draft journal entry for Finance to review
    $desc = "BOM Cost Estimate Report | Recipe: $rName | Qty: $simQty | Est. Total Cost: $" . number_format($batchCost, 2);
    $pdo->prepare("INSERT INTO journal_entries (entry_date, source_module, description, reference_id, recorded_by) VALUES (CURRENT_DATE(), 'Production', ?, ?, ?)")
        ->execute([$desc, 'EST-'.$rId, $_SESSION['user_id'] ?? 1]);
        
    require_once __DIR__ . '/../../includes/notifications.php';
    addNotification($pdo, "BOM Estimate Received", "Production submitted a BOM cost estimate for $rName.", 'finance');
    $reportSuccess = 'Cost estimation report successfully routed to the Finance module!';
}

if ($selectedRecipeId) {
    // Find recipe details
    foreach ($recipes as $r) {
        if ($r['recipe_id'] == $selectedRecipeId) {
            $selectedRecipeName = $r['recipe_name'];
            $baseYield = (float)$r['base_yield_qty'];
            break;
        }
    }

    // Safety fallback
    if ($baseYield <= 0) $baseYield = 1.0;
    
    // Scale factor: how many times do we need to run the recipe to hit target yield?
    $scaleFactor = $simulateQty / $baseYield;

    // Fetch child items (ingredients)
    $stmt = $pdo->prepare("
        SELECT ri.quantity_required, i.item_id, i.item_name, i.base_uom, i.standard_cost,
               COALESCE((SELECT SUM(quantity_change) FROM inventory_ledger il WHERE il.item_id = i.item_id), 0) as current_stock
        FROM recipe_ingredients ri
        JOIN item_master i ON ri.raw_material_id = i.item_id
        WHERE ri.recipe_id = ?
    ");
    $stmt->execute([$selectedRecipeId]);
    $ingredients = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($ingredients as $ing) {
        $requiredForOneUnit = (float)$ing['quantity_required'] / $baseYield;
        $requiredForBatch = (float)$ing['quantity_required'] * $scaleFactor;
        
        $stdCost = (float)$ing['standard_cost'];
        $itemUnitCost = $requiredForOneUnit * $stdCost;
        $itemBatchCost = $requiredForBatch * $stdCost;
        
        $totalUnitMaterialCost += $itemUnitCost;
        $totalBatchMaterialCost += $itemBatchCost;
        
        $soh = (float)$ing['current_stock'];
        $shortage = 0;
        if ($soh < $requiredForBatch) {
            $shortage = $requiredForBatch - $soh;
            $isFeasible = false;
        }

        $explosionData[] = [
            'item_id' => $ing['item_id'],
            'item_name' => $ing['item_name'],
            'uom' => $ing['base_uom'],
            'req_unit' => $requiredForOneUnit,
            'req_batch' => $requiredForBatch,
            'soh' => $soh,
            'shortage' => $shortage,
            'unit_cost_val' => $itemUnitCost,
            'batch_cost_val' => $itemBatchCost
        ];
    }
}
?>

<div class="card" style="margin-bottom: 2rem;">
    <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom: 2rem;">
        <div>
            <h3 style="margin-bottom: 0.5rem; color: var(--text-primary); font-size: 1.3rem;">BOM Explosion & Feasibility Simulator</h3>
            <p style="color:var(--text-secondary); line-height: 1.5; max-width: 800px;">
                Performs a dynamic calculation (Cost Roll-up) of direct materials against the master recipe. 
                Simulate massive production runs to verify warehouse capacity and detect material bottlenecks before starting a batch.
            </p>
        </div>
        
        <form method="GET" action="<?= BASE_URL ?>/app.php" style="background: var(--bg-body); padding: 1rem 1.5rem; border-radius: 8px; border: 1px solid var(--border-color); display:flex; gap:1rem; align-items:flex-end;">
            <input type="hidden" name="view" value="analytics">
            <input type="hidden" name="tab" value="bom">
            
            <div>
                <label style="display:block; font-size:0.85rem; font-weight:600; margin-bottom:0.5rem; color:var(--text-secondary);">Target Finished Good</label>
                <select name="recipe_id" style="padding:0.6rem; border-radius:6px; border:1px solid var(--border-color); font-family:inherit;">
                    <?php foreach ($recipes as $r): ?>
                        <option value="<?= $r['recipe_id'] ?>" <?= $r['recipe_id'] == $selectedRecipeId ? 'selected' : '' ?>>
                            <?= htmlspecialchars($r['fg_name']) ?> (<?= htmlspecialchars($r['recipe_name']) ?>)
                        option>
                    <?php endforeach; ?>
                select>
            </div>
            
            <div>
                <label style="display:block; font-size:0.85rem; font-weight:600; margin-bottom:0.5rem; color:var(--text-secondary);">Simulate Quantity</label>
                <input type="number" name="simulate_qty" value="<?= htmlspecialchars($simulateQty) ?>" min="1" style="padding:0.6rem; border-radius:6px; border:1px solid var(--border-color); width:120px;">
            </div>
            
            <button type="submit" class="icon-btn" style="background:var(--accent-primary); color:white; width:auto; padding:0 1.25rem; font-weight:600; height:42px;">
                <i class="fa-solid fa-calculator" style="margin-right:0.5rem;"></i> Calculate
            </button>
        </form>
    </div>

    <?php if ($selectedRecipeId && !empty($explosionData)): ?>
        <!-- Financial Summary Bar -->
        <div style="display:flex; gap:1.5rem; margin-bottom: 2rem;">
            <div style="flex:1; background: <?= $isFeasible ? '#f0fdf4; border: 1px solid #bbf7d0;' : '#fef2f2; border: 1px solid #fecaca;' ?> padding: 1.5rem; border-radius: 12px; display:flex; align-items:center; gap:1rem;">
                <div style="width:48px; height:48px; border-radius:50%; background: <?= $isFeasible ? '#16a34a' : '#dc2626' ?>; color:white; display:flex; align-items:center; justify-content:center; font-size:1.5rem;">
                    <i class="fa-solid <?= $isFeasible ? 'fa-circle-check' : 'fa-triangle-exclamation' ?>"></i>
                </div>
                <div>
                    <h4 style="color:var(--text-primary); margin:0 0 0.25rem 0;">Feasibility Check</h4>
                    <?php if ($isFeasible): ?>
                        <p style="margin:0; color:#15803d; font-weight:500;">Sufficient inventory to complete this batch.</p>
                    <?php else: ?>
                        <p style="margin:0; color:#b91c1c; font-weight:500;">Critical material shortage detected.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div style="flex:1; background: #eff6ff; border: 1px solid #bfdbfe; padding: 1.5rem; border-radius: 12px; display:flex; justify-content:space-between; align-items:center;">
                <div>
                    <span style="font-size:0.85rem; font-weight:600; color:#1e40af; text-transform:uppercase;">Cost Roll-up / Unit</span>
                    <div style="font-size:1.8rem; font-weight:700; color:#1e3a8a; margin-top:0.25rem;">$<?= number_format($totalUnitMaterialCost, 2) ?></div>
                </div>
                <div style="text-align:right;">
                    <span style="font-size:0.85rem; font-weight:600; color:#1e40af; text-transform:uppercase;">Total Material Estimate</span>
                    <div style="font-size:1.8rem; font-weight:700; color:#1e3a8a; margin-top:0.25rem;">$<?= number_format($totalBatchMaterialCost, 2) ?></div>
                </div>
            </div>
        </div>

        <?php if ($reportSuccess): ?>
            <div style="background:#dcfce7; border:1px solid #bbf7d0; color:#166534; padding:1rem; border-radius:8px; margin-bottom:1.5rem;">
                <i class="fa-solid fa-check-circle" style="margin-right:0.5rem;"></i><?= htmlspecialchars($reportSuccess) ?>
            </div>
        <?php endif; ?>

        <div style="display:flex; justify-content:flex-end; margin-bottom:1.5rem;">
            <form method="POST">
                <input type="hidden" name="action" value="send_to_finance">
                <input type="hidden" name="recipe_id" value="<?= $selectedRecipeId ?>">
                <input type="hidden" name="simulate_qty" value="<?= $simulateQty ?>">
                <input type="hidden" name="batch_cost" value="<?= $totalBatchMaterialCost ?>">
                <input type="hidden" name="recipe_name" value="<?= htmlspecialchars($selectedRecipeName) ?>">
                <button type="submit" style="padding:0.6rem 1.25rem; background:var(--accent-primary); color:white; border:none; border-radius:8px; cursor:pointer; font-weight:600; display:flex; align-items:center; gap:0.5rem;">
                    <i class="fa-solid fa-paper-plane"></i> Send Estimate to Finance
                </button>
            </form>
        </div>

        <!-- BOM Hierarchical Table -->
        <h4 style="margin-bottom:1rem; border-bottom:1px solid var(--border-color); padding-bottom:0.5rem;"><i class="fa-solid fa-list-check" style="color:var(--text-secondary); margin-right:0.5rem;"></i> Recipe Explosion Breakdown</h4>
        <div style="overflow-x: auto;">
            <table class="data-table" style="width: 100%; white-space: nowrap;">
                <thead>
                    <tr>
                        <th style="background:#f8fafc; padding-left:1rem;">Child Item (Raw Material)</th>
                        <th>Req / Unit</th>
                        <th style="background:#eff6ff; font-weight:700; color:#1e40af;">Required For Batch</th>
                        <th title="Stock on Hand">Current SOH</th>
                        <th style="background:#fef2f2; color:#991b1b;">Shortage / Deficit</th>
                        <th style="text-align:right; padding-right:1rem;">Standard Cost Impact</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($explosionData as $row): ?>
                    <tr>
                        <td style="background:#f8fafc; padding-left:1rem;">
                            <strong style="color:var(--text-primary);"><?= htmlspecialchars($row['item_name']) ?></strong><br>
                            <span style="font-size:0.8rem; color:var(--text-secondary);"><i class="fa-solid fa-link"></i> <?= htmlspecialchars($row['item_id']) ?></span>
                        </td>
                        <td><?= number_format($row['req_unit'], 3) ?> <span style="font-size:0.8rem; color:var(--text-secondary);"><?= $row['uom'] ?></span></td>
                        <td style="background:#eff6ff; font-weight:600; color:#1d4ed8; font-size:1.05rem;">
                            <?= number_format($row['req_batch'], 3) ?> <span style="font-size:0.8rem; opacity:0.8;"><?= $row['uom'] ?></span>
                        </td>
                        <td style="font-weight:600;">
                            <?= number_format($row['soh'], 3) ?>
                        </td>
                        <td style="background:#fef2f2; color:#b91c1c; font-weight:700;">
                            <?php if ($row['shortage'] > 0): ?>
                                <i class="fa-solid fa-arrow-down" style="font-size:0.8rem; margin-right:4px;"></i> <?= number_format($row['shortage'], 3) ?>
                            <?php else: ?>
                                <span style="color:#15803d; font-size:0.9rem;"><i class="fa-solid fa-check"></i> Satisfied</span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align:right; padding-right:1rem; font-weight:600;">
                            $<?= number_format($row['batch_cost_val'], 2) ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php elseif ($selectedRecipeId): ?>
        <div style="padding: 3rem; text-align: center; color: var(--text-secondary);">
            <i class="fa-solid fa-flask" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.3;"></i>
            <p>No ingredients defined for this recipe.</p>
        </div>
    <?php endif; ?>
</div>
