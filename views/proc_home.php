<?php
/**
 * MiskStone ERP — Procurement Home Dashboard
 */
require_once dirname(__DIR__, 1) . '/includes/math_models.php';

$totalPOs = $pdo->query("SELECT COUNT(*) FROM purchase_orders")->fetchColumn();
$pendingPOs = $pdo->query("SELECT COUNT(*) FROM purchase_orders WHERE order_status = 'Pending'")->fetchColumn();
$totalSpend = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM purchase_orders")->fetchColumn();
$supplierCount = $pdo->query("SELECT COUNT(*) FROM suppliers")->fetchColumn();
$pendingRequests = $pdo->query("SELECT COUNT(*) FROM material_requests WHERE status = 'Requested'")->fetchColumn();

$alerts = calculateReorderPoints($pdo);
$criticalCount = count(array_filter($alerts, fn($a) => $a['status'] === 'Critical'));

$recentPOs = $pdo->query("
    SELECT po.po_id, s.supplier_name, po.total_amount, po.currency, po.order_status, po.order_date
    FROM purchase_orders po
    JOIN suppliers s ON po.supplier_id = s.supplier_id
    ORDER BY po.order_date DESC LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="dashboard-container">
    <div class="welcome-banner" style="background: linear-gradient(135deg, #1e293b, #334155); color: white; padding: 2rem; border-radius: 16px; margin-bottom: 2rem;">
        <h1 style="margin-bottom: 0.5rem;"><i class="fa-solid fa-truck-fast" style="margin-right: 0.75rem;"></i>Procurement Command Center</h1>
        <p style="opacity: 0.9;">Supplier Management · Purchase Orders · Material Requests · Stock Alerting</p>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card" style="border-left: 4px solid var(--accent-primary);">
            <div class="stat-info">
                <h3 style="color: var(--text-secondary); font-size: 0.9rem;">Total POs</h3>
                <p class="stat-value"><?= $totalPOs ?></p>
            </div>
        </div>
        <div class="stat-card" style="border-left: 4px solid #f59e0b;">
            <div class="stat-info">
                <h3 style="color: var(--text-secondary); font-size: 0.9rem;">Pending POs</h3>
                <p class="stat-value"><?= $pendingPOs ?></p>
            </div>
        </div>
        <div class="stat-card" style="border-left: 4px solid #ef4444;">
            <div class="stat-info">
                <h3 style="color: var(--text-secondary); font-size: 0.9rem;">Incoming Requests</h3>
                <p class="stat-value"><?= $pendingRequests ?></p>
                <span class="stat-trend <?= $pendingRequests > 0 ? 'negative' : 'positive' ?>">From Production</span>
            </div>
        </div>
        <div class="stat-card" style="border-left: 4px solid #22c55e;">
            <div class="stat-info">
                <h3 style="color: var(--text-secondary); font-size: 0.9rem;">Total Spend</h3>
                <p class="stat-value"><?= number_format($totalSpend) ?> JOD</p>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 1rem; margin: 2rem 0;">
        <?php
        $actions = [
            ['proc_suppliers', 'fa-building', 'Suppliers', '#0ea5e9', $supplierCount . ' registered'],
            ['proc_create', 'fa-file-circle-plus', 'Create PO', '#22c55e', 'New order'],
            ['proc_track', 'fa-list-check', 'Track Orders', '#f59e0b', $pendingPOs . ' pending'],
            ['proc_receive', 'fa-box-open', 'Receive', '#7c3aed', 'Mark received'],
            ['proc_requests', 'fa-envelope-open-text', 'Requests', '#ef4444', $pendingRequests . ' waiting'],
            ['proc_rop', 'fa-chart-line', 'ROP Alerts', '#334155', $criticalCount . ' critical'],
        ];
        foreach ($actions as [$view, $icon, $label, $color, $desc]):
        ?>
            <a href="<?= BASE_URL ?>/app.php?view=<?= $view ?>" style="display: block; text-decoration: none; background: white; border: 1px solid var(--border-color); border-radius: 12px; padding: 1.25rem; text-align: center; transition: all 0.2s; border-top: 3px solid <?= $color ?>;" onmouseover="this.style.transform='translateY(-3px)'" onmouseout="this.style.transform=''">
                <i class="fa-solid <?= $icon ?>" style="font-size: 1.5rem; color: <?= $color ?>; margin-bottom: 0.5rem; display: block;"></i>
                <span style="font-weight: 700; color: var(--text-primary); font-size: 0.9rem;"><?= $label ?></span>
                <span style="display: block; font-size: 0.75rem; color: var(--text-secondary); margin-top: 0.25rem;"><?= $desc ?></span>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Recent POs -->
    <div class="card">
        <div class="card-header"><h3>Recent Purchase Orders</h3></div>
        <table class="data-table">
            <thead><tr><th>PO ID</th><th>Supplier</th><th>Amount</th><th>Date</th><th>Status</th></tr></thead>
            <tbody>
                <?php foreach ($recentPOs as $po): ?>
                    <tr>
                        <td style="font-weight: 600; font-family: monospace;"><?= htmlspecialchars($po['po_id']) ?></td>
                        <td><?= htmlspecialchars($po['supplier_name']) ?></td>
                        <td style="font-weight: 600;"><?= $po['currency'] ?> <?= number_format($po['total_amount'], 2) ?></td>
                        <td style="color: var(--text-secondary);"><?= htmlspecialchars($po['order_date']) ?></td>
                        <td><span class="badge <?= $po['order_status'] === 'Completed' ? 'completed' : 'pending' ?>"><?= $po['order_status'] ?></span></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($recentPOs)): ?>
                    <tr><td colspan="5" style="text-align: center; color: var(--text-secondary);">No purchase orders yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
