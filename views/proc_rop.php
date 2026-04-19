<?php
/** MiskStone ERP — ROP Alerts */
require_once dirname(__DIR__, 1) . '/includes/math_models.php';
$alerts = calculateReorderPoints($pdo);
?>
<div class="dashboard-container">
    <div class="welcome-banner" style="background: linear-gradient(135deg, #1e293b, #334155); color: white; padding: 2rem; border-radius: 16px; margin-bottom: 2rem;">
        <h1><i class="fa-solid fa-chart-line" style="margin-right: 0.75rem;"></i>ROP Alerts</h1>
        <p style="opacity: 0.9;">Reorder Point analytics. Formula: (Avg Daily Usage × Lead Time) + Safety Stock</p>
    </div>
    <div class="card">
        <div style="overflow-x: auto;">
            <table class="data-table">
                <thead><tr><th>Raw Material</th><th>Current Stock</th><th>Avg Usage</th><th>Safety Stock</th><th>ROP</th><th>Status</th><th>Action</th></tr></thead>
                <tbody>
                    <?php foreach ($alerts as $item): 
                        $rowBg = $item['status'] === 'Critical' ? 'rgba(239,68,68,0.05)' : ($item['status'] === 'Warning' ? 'rgba(245,158,11,0.05)' : '');
                    ?>
                        <tr style="background: <?= $rowBg ?>;">
                            <td style="font-weight: 600;"><?= htmlspecialchars($item['item_name']) ?></td>
                            <td><?= number_format($item['current_stock'], 0) ?> <?= $item['uom'] ?></td>
                            <td style="color: #64748b;"><?= $item['avg_daily_usage'] ?>/day</td>
                            <td style="color: #64748b;"><?= $item['safety_stock'] ?></td>
                            <td style="font-weight: 600;"><?= $item['calculated_rop'] ?></td>
                            <td>
                                <?php if ($item['status'] === 'Critical'): ?>
                                    <span class="badge" style="background:#fee2e2; color:#991b1b;">Critical</span>
                                <?php elseif ($item['status'] === 'Warning'): ?>
                                    <span class="badge" style="background:#fef3c7; color:#92400e;">Warning</span>
                                <?php else: ?>
                                    <span class="badge completed">Safe</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($item['status'] !== 'Safe'): ?>
                                    <a href="<?= BASE_URL ?>/app.php?view=proc_create&item=<?= urlencode($item['item_id'] ?? '') ?>&qty=500" style="color: var(--accent-primary); font-weight: 600; font-size: 0.85rem; text-decoration: none;">
                                        <i class="fa-solid fa-file-invoice"></i> Generate PO
                                    </a>
                                <?php else: ?>
                                    <span style="color: #94a3b8; font-size: 0.85rem;">None</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
