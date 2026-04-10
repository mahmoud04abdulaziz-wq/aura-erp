<?php
require_once dirname(__DIR__, 2) . '/includes/math_models.php';

// Fetch Planned Production Orders
$upcomingProdStmt = $pdo->query("
    SELECT po.production_id, po.item_id, im.item_name, po.target_quantity, po.production_date, po.machine_id
    FROM production_orders po
    JOIN item_master im ON po.item_id = im.item_id
    WHERE po.status = 'Planned'
    ORDER BY po.production_date ASC
");
$plannedOrders = $upcomingProdStmt->fetchAll();

// We will calculate BOM for the VERY NEXT planned order to show the mathematical integration
$nextOrder = $plannedOrders[0] ?? null;
$bomRequirements = [];
if ($nextOrder) {
    // Find recipe for this item
    $recipeStmt = $pdo->prepare("SELECT recipe_id FROM recipes WHERE finished_item_id = ?");
    $recipeStmt->execute([$nextOrder['item_id']]);
    $recipeId = $recipeStmt->fetchColumn();
    
    if ($recipeId) {
        $bomRequirements = calculateBOM($pdo, $recipeId, $nextOrder['target_quantity']);
    }
}

// Basic Stats
$activeMixes = $pdo->query("SELECT COUNT(*) FROM production_orders WHERE status IN ('Mixing', 'Curing')")->fetchColumn();
$completedToday = $pdo->query("SELECT COUNT(*) FROM production_orders WHERE status = 'Completed' AND DATE(created_at) = CURDATE()")->fetchColumn();
?>

<div class="dashboard-container">
    <div class="welcome-banner" style="background: linear-gradient(135deg, #1e293b, #475569); color: white;">
        <h1>Production Floor Control</h1>
        <p>Batch Management & Bill of Materials (BOM) Calculator</p>
    </div>

    <!-- Stats Grid -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon info"><i class="fa-solid fa-blender"></i></div>
            <div class="stat-info">
                <h3>Active Mixes</h3>
                <p class="stat-value"><?= $activeMixes ?></p>
                <span class="stat-trend neutral">Mixing & Curing phase</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon warning"><i class="fa-solid fa-list-check"></i></div>
            <div class="stat-info">
                <h3>Planned Batches</h3>
                <p class="stat-value"><?= count($plannedOrders) ?></p>
                <span class="stat-trend neutral">Awaiting materials/schedule</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon success"><i class="fa-solid fa-check-double"></i></div>
            <div class="stat-info">
                <h3>Completed Today</h3>
                <p class="stat-value"><?= $completedToday ?></p>
                <span class="stat-trend positive">Ready for QA</span>
            </div>
        </div>
    </div>

    <div class="content-split" style="margin-top: 2rem;">
        
        <!-- BOM Calculator -->
        <div class="card" style="flex: 2;">
            <div class="card-header">
                <h3><i class="fa-solid fa-calculator" style="color:var(--accent-primary);margin-right:8px;"></i> Bill of Materials (BOM) Requirement</h3>
            </div>
            
            <?php if ($nextOrder): ?>
                <div style="background: #f8fafc; padding: 1.5rem; border-radius: 12px; margin-bottom: 1.5rem; border: 1px solid #e2e8f0;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <span style="font-size:0.85rem; color:#64748b; text-transform:uppercase; font-weight:600;">Next Scheduled Batch</span>
                            <h4 style="margin: 0.25rem 0 0 0; font-size:1.15rem; color:#0f172a;"><?= htmlspecialchars($nextOrder['production_id']) ?>: <?= htmlspecialchars($nextOrder['item_name']) ?></h4>
                        </div>
                        <div style="text-align:right;">
                            <span style="font-size:0.85rem; color:#64748b; text-transform:uppercase; font-weight:600;">Target Yield</span>
                            <div style="font-size:1.25rem; font-weight:700; color:var(--accent-primary);"><?= number_format($nextOrder['target_quantity']) ?> PCS</div>
                        </div>
                    </div>
                </div>

                <?php if (empty($bomRequirements)): ?>
                    <p style="text-align:center; padding: 2rem; color: #94a3b8;"><i class="fa-solid fa-triangle-exclamation"></i> No recipe found for this item. Cannot calculate BOM.</p>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Raw Material</th>
                                <th>Required Quantity (Math)</th>
                                <th>Current Inventory Stock</th>
                                <th>Availability Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $canStart = true;
                            foreach ($bomRequirements as $req): 
                                $isShort = $req['calculated_requirement'] > $req['current_stock'];
                                if ($isShort) $canStart = false;
                            ?>
                                <tr>
                                    <td style="font-weight: 500;"><i class="fa-solid fa-vial" style="color:#94a3b8; font-size:0.8rem; margin-right:6px;"></i> <?= htmlspecialchars($req['item_name']) ?></td>
                                    <td style="font-weight: 600; color: var(--accent-primary);">
                                        <?= number_format($req['calculated_requirement'], 2) ?> <?= $req['base_uom'] ?>
                                    </td>
                                    <td><?= number_format($req['current_stock'], 2) ?> <?= $req['base_uom'] ?></td>
                                    <td>
                                        <?php if ($isShort): ?>
                                            <span class="badge pending" style="background:#fee2e2; color:#991b1b;"><i class="fa-solid fa-xmark"></i> Shortage</span>
                                        <?php else: ?>
                                            <span class="badge completed" style="background:#dcfce7; color:#166534;"><i class="fa-solid fa-check"></i> Available</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <div style="margin-top: 1.5rem; text-align:right;">
                        <?php if ($canStart): ?>
                            <button class="btn-primary" style="background: #10b981; border: none; padding: 0.75rem 1.5rem; border-radius: 8px; color: white; font-weight: 600; cursor: pointer;">
                                <i class="fa-solid fa-play"></i> Start Batch Production
                            </button>
                        <?php else: ?>
                            <button class="btn-primary" disabled style="background: #cbd5e1; border: none; padding: 0.75rem 1.5rem; border-radius: 8px; color: white; font-weight: 600; cursor: not-allowed;">
                                <i class="fa-solid fa-lock"></i> Material Shortage (Wait for Procurement)
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <p style="text-align:center; padding: 3rem; color: #94a3b8;">No planned production orders to calculate.</p>
            <?php endif; ?>
        </div>

        <!-- Planned Schedule List -->
        <div class="card" style="flex: 1;">
            <div class="card-header">
                <h3>Schedule Pipeline</h3>
            </div>
            <div style="display:flex; flex-direction: column; gap: 1rem;">
                <?php if (empty($plannedOrders)): ?>
                    <p style="color:#94a3b8; text-align:center;">Empty pipeline</p>
                <?php else: ?>
                    <?php foreach ($plannedOrders as $idx => $order): ?>
                        <div style="padding: 1rem; border: 1px solid <?= $idx === 0 ? 'var(--accent-primary)' : '#e2e8f0' ?>; border-radius: 8px; background: <?= $idx === 0 ? 'rgba(99,102,241,0.02)' : 'white' ?>;">
                            <div style="display:flex; justify-content:space-between; margin-bottom:0.5rem;">
                                <span style="font-weight:600; font-size:0.9rem;"><?= htmlspecialchars($order['production_id']) ?></span>
                                <span class="badge in-progress" style="font-size:0.7rem;"><?= htmlspecialchars($order['machine_id'] ?? 'TBD') ?></span>
                            </div>
                            <div style="font-size:0.85rem; color:#334155;">
                                <?= htmlspecialchars($order['item_name']) ?> <br>
                                <span style="color:#64748b;">Qty: <?= number_format($order['target_quantity']) ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>
