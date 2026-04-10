<?php
require_once dirname(__DIR__, 2) . '/includes/math_models.php';

$alerts = calculateReorderPoints($pdo);
$criticalCount = 0;
$warningCount = 0;
$safeCount = 0;

foreach ($alerts as $a) {
    if ($a['status'] === 'Critical') $criticalCount++;
    elseif ($a['status'] === 'Warning') $warningCount++;
    else $safeCount++;
}

// Fetch recent POs
$recentPOs = $pdo->query("
    SELECT po.po_id, s.supplier_name, po.total_amount, po.currency, po.order_status
    FROM purchase_orders po
    JOIN suppliers s ON po.supplier_id = s.supplier_id
    ORDER BY po.order_date DESC LIMIT 5
")->fetchAll();
?>

<div class="dashboard-container">
    <div class="welcome-banner" style="background: linear-gradient(135deg, #1e293b, #334155); color: white;">
        <h1>Procurement Command Center</h1>
        <p>Mathematical Stock Alerting & Supplier Management</p>
    </div>

    <!-- Math KPI Grid -->
    <div class="stats-grid">
        <div class="stat-card" style="border-left: 4px solid #ef4444;">
            <div class="stat-icon warning"><i class="fa-solid fa-triangle-exclamation"></i></div>
            <div class="stat-info">
                <h3>Critical Restock</h3>
                <p class="stat-value"><?= $criticalCount ?></p>
                <span class="stat-trend negative">Below Safety Stock</span>
            </div>
        </div>
        <div class="stat-card" style="border-left: 4px solid #f59e0b;">
            <div class="stat-icon info"><i class="fa-solid fa-bell"></i></div>
            <div class="stat-info">
                <h3>Reorder Warnings</h3>
                <p class="stat-value"><?= $warningCount ?></p>
                <span class="stat-trend neutral">Hit Reorder Point (ROP)</span>
            </div>
        </div>
        <div class="stat-card" style="border-left: 4px solid #10b981;">
            <div class="stat-icon success"><i class="fa-solid fa-shield-halved"></i></div>
            <div class="stat-info">
                <h3>Safe Levels</h3>
                <p class="stat-value"><?= $safeCount ?></p>
                <span class="stat-trend positive">Service Level Maintained</span>
            </div>
        </div>
    </div>

    <!-- Advanced ROP Math Data Table -->
    <div class="card" style="margin-top: 2rem;">
        <div class="card-header">
            <h3><i class="fa-solid fa-calculator" style="color:var(--accent-primary);margin-right:8px;"></i> Reorder Point (ROP) Analytics</h3>
            <span style="font-size: 0.85rem; color: #64748b;">Formula: (Avg Daily Usage × Lead Time) + Safety Stock</span>
        </div>
        <div style="overflow-x: auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Raw Material</th>
                        <th>Current Stock</th>
                        <th>Avg Daily Usage</th>
                        <th>Safety Stock</th>
                        <th>Calculated ROP</th>
                        <th>Service Level</th>
                        <th>Status</th>
                        <th>Action Required</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($alerts)): ?>
                        <tr><td colspan="8" style="text-align:center;">No stock data available.</td></tr>
                    <?php else: ?>
                        <?php foreach ($alerts as $item): 
                            $rowStyle = '';
                            if ($item['status'] === 'Critical') $rowStyle = 'background: rgba(239, 68, 68, 0.05);';
                            elseif ($item['status'] === 'Warning') $rowStyle = 'background: rgba(245, 158, 11, 0.05);';
                        ?>
                        <tr style="<?= $rowStyle ?>">
                            <td style="font-weight: 600;"><?= htmlspecialchars($item['item_name']) ?></td>
                            <td><?= number_format($item['current_stock'], 2) ?> <?= $item['uom'] ?></td>
                            <td style="color:#64748b;"><?= $item['avg_daily_usage'] ?> / day</td>
                            <td style="color:#64748b;">
                                <i class="fa-solid fa-shield" style="font-size:0.8rem; opacity:0.5; margin-right:4px;"></i>
                                <?= $item['safety_stock'] ?>
                            </td>
                            <td style="font-weight: 600; color: #0f172a;"><?= $item['calculated_rop'] ?></td>
                            <td><span class="badge completed"><?= $item['service_level'] ?> Target</span></td>
                            <td>
                                <?php if ($item['status'] === 'Critical'): ?>
                                    <span class="badge pending" style="background:#fee2e2; color:#991b1b;">Critical</span>
                                <?php elseif ($item['status'] === 'Warning'): ?>
                                    <span class="badge in-progress" style="background:#fef3c7; color:#92400e;">Warning</span>
                                <?php else: ?>
                                    <span class="badge completed" style="background:#dcfce7; color:#166534;">Safe</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($item['status'] !== 'Safe'): ?>
                                    <button class="btn-text" style="color:var(--accent-primary);"><i class="fa-solid fa-file-invoice"></i> <?= $item['recommended_action'] ?></button>
                                <?php else: ?>
                                    <span style="color:#94a3b8; font-size:0.85rem;">None</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent POs -->
    <div class="card" style="margin-top: 2rem;">
        <div class="card-header">
            <h3>Recent Purchase Orders</h3>
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>PO ID</th>
                    <th>Supplier</th>
                    <th>Amount</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentPOs as $po): ?>
                    <tr>
                        <td><?= htmlspecialchars($po['po_id']) ?></td>
                        <td><?= htmlspecialchars($po['supplier_name']) ?></td>
                        <td><?= htmlspecialchars($po['currency']) ?> <?= number_format($po['total_amount'], 2) ?></td>
                        <td><span class="badge <?= $po['order_status'] === 'Received' ? 'completed' : 'pending' ?>"><?= $po['order_status'] ?></span></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
