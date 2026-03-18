<?php
/**
 * AURA ERP — Analytics View
 * Displays performance metrics from aggregated DB queries.
 * Tables: sales_orders, customers, production_orders, defect_logs, item_master
 */

try {
    // Total customers
    $totalCustomers = $pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn();

    // Customers this month vs last month
    // (using lead_status as proxy — no explicit date field, so we count total growth)

    // Average order value
    $avgOrderValue = $pdo->query("SELECT COALESCE(AVG(total_price), 0) FROM sales_orders")->fetchColumn();

    // Total revenue
    $totalRevenue = $pdo->query("SELECT COALESCE(SUM(total_price), 0) FROM sales_orders")->fetchColumn();

    // Production stats
    $totalProduction = $pdo->query("SELECT COUNT(*) FROM production_orders")->fetchColumn();
    $completedProduction = $pdo->query("SELECT COUNT(*) FROM production_orders WHERE status = 'Completed'")->fetchColumn();
    $productionRate = $totalProduction > 0 ? round(($completedProduction / $totalProduction) * 100) : 0;

    // Defect/waste rate
    $totalScrap = $pdo->query("SELECT COALESCE(SUM(scrap_quantity), 0) FROM defect_logs")->fetchColumn();
    $totalYield = $pdo->query("SELECT COALESCE(SUM(actual_yield), 0) FROM production_orders")->fetchColumn();
    $wasteRate = $totalYield > 0 ? round(($totalScrap / $totalYield) * 100, 1) : 0;

    // Orders by status
    $ordersByStatus = $pdo->query(
        "SELECT order_status, COUNT(*) as cnt FROM sales_orders GROUP BY order_status ORDER BY cnt DESC"
    )->fetchAll();

} catch (Exception $e) {
    error_log("Analytics view error: " . $e->getMessage());
    $totalCustomers = $avgOrderValue = $totalRevenue = $productionRate = $wasteRate = 0;
    $ordersByStatus = [];
}
?>

<!-- Chart Placeholders -->
<div class="card" style="margin-bottom: 1.5rem;">
    <div class="card-header">
        <h3>Performance Overview</h3>
    </div>
    <div style="display: flex; gap: 2rem; padding: 1rem 0;">
        <!-- Revenue summary -->
        <div style="flex: 2; border: 1px solid var(--border-color); border-radius: 8px; padding: 1.5rem;">
            <h4 style="margin-bottom:1rem; color:var(--text-secondary);">Revenue Summary</h4>
            <p style="font-size:2.5rem; font-weight:700; color:var(--accent-primary); margin-bottom:0.5rem;">$
                <?= number_format($totalRevenue, 2) ?>
            </p>
            <p style="color:var(--text-secondary);">Total revenue from
                <?= count($ordersByStatus) > 0 ? array_sum(array_column($ordersByStatus, 'cnt')) : 0 ?> orders
            </p>

            <?php if (!empty($ordersByStatus)): ?>
                <div style="margin-top:1.5rem; display:flex; gap:1rem; flex-wrap:wrap;">
                    <?php foreach ($ordersByStatus as $os): ?>
                        <div style="padding:0.5rem 1rem; background:var(--bg-body); border-radius:8px; font-size:0.85rem;">
                            <span style="font-weight:600;">
                                <?= $os['cnt'] ?>
                            </span>
                            <span style="color:var(--text-secondary);">
                                <?= htmlspecialchars($os['order_status']) ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Production completion -->
        <div
            style="flex: 1; border: 1px solid var(--border-color); border-radius: 8px; padding: 1.5rem; display:flex; flex-direction:column; align-items:center; justify-content:center;">
            <h4 style="margin-bottom:1rem; color:var(--text-secondary);">Production Completion</h4>
            <p style="font-size:3rem; font-weight:700; color:var(--success);">
                <?= $productionRate ?>%
            </p>
            <p style="color:var(--text-secondary); text-align:center; font-size:0.85rem;">
                <?= $completedProduction ?> of
                <?= $totalProduction ?> batches completed
            </p>
        </div>
    </div>
</div>

<!-- KPI Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-info">
            <h3>Total Customers</h3>
            <p class="stat-value" style="font-size:1.5rem; color:var(--success);">
                <?= $totalCustomers ?>
            </p>
            <span class="stat-trend neutral">All registered clients</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-info">
            <h3>Avg. Order Value</h3>
            <p class="stat-value" style="font-size:1.5rem; color:var(--accent-primary);">$
                <?= number_format($avgOrderValue, 2) ?>
            </p>
            <span class="stat-trend neutral">Across all orders</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-info">
            <h3>Production Waste</h3>
            <p class="stat-value"
                style="font-size:1.5rem; color:<?= $wasteRate > 5 ? 'var(--warning)' : 'var(--success)' ?>;">
                <?= $wasteRate ?>%
            </p>
            <span class="stat-trend <?= $wasteRate > 5 ? 'negative' : 'positive' ?>">
                <?= $wasteRate > 5 ? 'Above target' : 'Within target' ?>
            </span>
        </div>
    </div>
</div>