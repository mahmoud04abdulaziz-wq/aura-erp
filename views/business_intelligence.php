<?php
/**
 * MiskStone ERP — Business Intelligence Dashboard
 * Advanced manufacturing analytics: ROP, BOM, Yield, and Financial KPIs.
 * Charts rendered via Chart.js CDN.
 */

$tab = $_GET['tab'] ?? 'overview';

// --- KPI DATA ---
try {
    // Revenue trend (last 6 months)
    $revenueTrend = $pdo->query("
        SELECT DATE_FORMAT(transaction_date, '%Y-%m') as month, 
               SUM(CASE WHEN transaction_type='Income' THEN amount ELSE 0 END) as income,
               SUM(CASE WHEN transaction_type='Expense' THEN amount ELSE 0 END) as expenses
        FROM finance_ledger
        GROUP BY DATE_FORMAT(transaction_date, '%Y-%m')
        ORDER BY month ASC
        LIMIT 6
    ")->fetchAll();

    // Top customers by revenue
    $topCustomers = $pdo->query("
        SELECT c.company_name, SUM(so.total_price) as total_revenue, COUNT(so.so_id) as order_count
        FROM sales_orders so
        JOIN customers c ON so.customer_id = c.customer_id
        WHERE so.order_status IN ('Delivered', 'Pending Delivery', 'In Production')
        GROUP BY c.customer_id, c.company_name
        ORDER BY total_revenue DESC
        LIMIT 5
    ")->fetchAll();

    // Production yield rates
    $yieldData = $pdo->query("
        SELECT im.item_name,
               SUM(po.target_quantity) as targeted,
               SUM(po.actual_yield) as yielded
        FROM production_orders po
        JOIN item_master im ON po.item_id = im.item_id
        WHERE po.status = 'Completed'
        GROUP BY po.item_id, im.item_name
    ")->fetchAll();

    // Stock levels vs safety stock
    $stockHealth = $pdo->query("
        SELECT im.item_name, im.min_stock_level,
               COALESCE(SUM(il.quantity_change), 0) as current_stock
        FROM item_master im
        LEFT JOIN inventory_ledger il ON im.item_id = il.item_id
        WHERE im.category = 'Raw Material'
        GROUP BY im.item_id, im.item_name, im.min_stock_level
        ORDER BY im.item_name
    ")->fetchAll();

    // Expense breakdown by category
    $expenseBreakdown = $pdo->query("
        SELECT category, SUM(amount) as total
        FROM finance_ledger
        WHERE transaction_type = 'Expense'
        GROUP BY category
        ORDER BY total DESC
    ")->fetchAll();

} catch (Exception $e) {
    error_log("BI Dashboard error: " . $e->getMessage());
    $revenueTrend = $topCustomers = $yieldData = $stockHealth = $expenseBreakdown = [];
}
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<div class="card header-card" style="margin-bottom: 2rem; border-left: 4px solid var(--accent-primary);">
    <div style="display:flex; justify-content:space-between; align-items:center;">
        <div>
            <h2><i class="fa-solid fa-brain" style="margin-right: 0.5rem; color: var(--accent-primary);"></i>Business Intelligence</h2>
            <p style="color:var(--text-secondary);">Advanced analytics, manufacturing KPIs, and financial performance tracking</p>
        </div>
    </div>
</div>

<!-- Tab Navigation -->
<div class="card" style="margin-bottom: 2rem; padding: 0; background: var(--bg-panel);">
    <div style="display:flex; border-bottom:1px solid var(--border-color); flex-wrap: wrap;">
        <a href="<?= BASE_URL ?>/app.php?view=bi&tab=overview" style="padding:1.25rem 1.5rem; text-decoration:none; font-weight:600; color: <?= $tab === 'overview' ? 'var(--accent-primary)' : 'var(--text-secondary)' ?>; border-bottom: 3px solid <?= $tab === 'overview' ? 'var(--accent-primary)' : 'transparent' ?>; display:flex; align-items:center; gap:0.5rem;">
            <i class="fa-solid fa-chart-line"></i> Financial Overview
        </a>
        <a href="<?= BASE_URL ?>/app.php?view=bi&tab=production" style="padding:1.25rem 1.5rem; text-decoration:none; font-weight:600; color: <?= $tab === 'production' ? 'var(--accent-primary)' : 'var(--text-secondary)' ?>; border-bottom: 3px solid <?= $tab === 'production' ? 'var(--accent-primary)' : 'transparent' ?>; display:flex; align-items:center; gap:0.5rem;">
            <i class="fa-solid fa-industry"></i> Production & Yield
        </a>
        <a href="<?= BASE_URL ?>/app.php?view=bi&tab=inventory" style="padding:1.25rem 1.5rem; text-decoration:none; font-weight:600; color: <?= $tab === 'inventory' ? 'var(--accent-primary)' : 'var(--text-secondary)' ?>; border-bottom: 3px solid <?= $tab === 'inventory' ? 'var(--accent-primary)' : 'transparent' ?>; display:flex; align-items:center; gap:0.5rem;">
            <i class="fa-solid fa-boxes-stacked"></i> ROP & Stock Health
        </a>
        <a href="<?= BASE_URL ?>/app.php?view=analytics&tab=rop" style="padding:1.25rem 1.5rem; text-decoration:none; font-weight:600; color: var(--text-secondary); display:flex; align-items:center; gap:0.5rem;">
            <i class="fa-solid fa-calculator"></i> Math Models
        </a>
    </div>
</div>

<?php if ($tab === 'overview'): ?>
<!-- Financial Overview Tab -->
<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
    <div class="card">
        <div class="card-header"><h3>Revenue vs Expenses Trend</h3></div>
        <div style="padding: 1rem;"><canvas id="revenueTrendChart" height="120"></canvas></div>
    </div>
    <div class="card">
        <div class="card-header"><h3>Expense Breakdown</h3></div>
        <div style="padding: 1rem;"><canvas id="expenseChart" height="200"></canvas></div>
    </div>
</div>

<div class="card">
    <div class="card-header"><h3><i class="fa-solid fa-trophy" style="color: #f59e0b; margin-right: 0.5rem;"></i>Top Customers by Revenue</h3></div>
    <table class="data-table">
        <thead><tr><th>#</th><th>Company</th><th>Orders</th><th>Total Revenue</th></tr></thead>
        <tbody>
            <?php foreach ($topCustomers as $i => $c): ?>
                <tr>
                    <td><span style="font-weight:700; color:var(--accent-primary);"><?= $i + 1 ?></span></td>
                    <td style="font-weight:600;"><?= htmlspecialchars($c['company_name']) ?></td>
                    <td><?= $c['order_count'] ?></td>
                    <td style="font-weight:700; color:var(--success);"><?= number_format($c['total_revenue'], 2) ?> JOD</td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($topCustomers)): ?>
                <tr><td colspan="4" style="text-align:center; padding:2rem; color:var(--text-secondary);">No customer data yet.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
const revenueData = <?= json_encode($revenueTrend) ?>;
const expenseData = <?= json_encode($expenseBreakdown) ?>;

new Chart(document.getElementById('revenueTrendChart'), {
    type: 'bar',
    data: {
        labels: revenueData.map(r => r.month),
        datasets: [
            { label: 'Income', data: revenueData.map(r => r.income), backgroundColor: 'rgba(34, 197, 94, 0.7)', borderRadius: 6 },
            { label: 'Expenses', data: revenueData.map(r => r.expenses), backgroundColor: 'rgba(239, 68, 68, 0.7)', borderRadius: 6 }
        ]
    },
    options: { responsive: true, plugins: { legend: { position: 'top' } }, scales: { y: { beginAtZero: true } } }
});

new Chart(document.getElementById('expenseChart'), {
    type: 'doughnut',
    data: {
        labels: expenseData.map(e => e.category),
        datasets: [{
            data: expenseData.map(e => e.total),
            backgroundColor: ['#6366f1','#f59e0b','#ef4444','#22c55e','#06b6d4','#8b5cf6','#ec4899','#64748b']
        }]
    },
    options: { responsive: true, plugins: { legend: { position: 'bottom', labels: { boxWidth: 12 } } } }
});
</script>

<?php elseif ($tab === 'production'): ?>
<!-- Production & Yield Tab -->
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
    <div class="card">
        <div class="card-header"><h3>Production Yield Analysis</h3></div>
        <div style="padding: 1rem;"><canvas id="yieldChart" height="180"></canvas></div>
    </div>
    <div class="card">
        <div class="card-header"><h3>Yield Efficiency Table</h3></div>
        <table class="data-table">
            <thead><tr><th>Product</th><th>Targeted</th><th>Yielded</th><th>Efficiency</th></tr></thead>
            <tbody>
                <?php foreach ($yieldData as $y): ?>
                    <?php $eff = $y['targeted'] > 0 ? round(($y['yielded'] / $y['targeted']) * 100, 1) : 0; ?>
                    <tr>
                        <td style="font-weight:600;"><?= htmlspecialchars($y['item_name']) ?></td>
                        <td><?= number_format($y['targeted']) ?></td>
                        <td><?= number_format($y['yielded']) ?></td>
                        <td>
                            <span class="badge <?= $eff >= 90 ? 'completed' : ($eff >= 75 ? 'in-progress' : 'pending') ?>"><?= $eff ?>%</span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
const yieldData = <?= json_encode($yieldData) ?>;
new Chart(document.getElementById('yieldChart'), {
    type: 'bar',
    data: {
        labels: yieldData.map(y => y.item_name.substring(0, 20)),
        datasets: [
            { label: 'Target', data: yieldData.map(y => y.targeted), backgroundColor: 'rgba(99,102,241,0.6)', borderRadius: 6 },
            { label: 'Actual Yield', data: yieldData.map(y => y.yielded), backgroundColor: 'rgba(34,197,94,0.6)', borderRadius: 6 }
        ]
    },
    options: { responsive: true, indexAxis: 'y', plugins: { legend: { position: 'top' } } }
});
</script>

<?php elseif ($tab === 'inventory'): ?>
<!-- ROP & Stock Health Tab -->
<div class="card">
    <div class="card-header"><h3><i class="fa-solid fa-heartbeat" style="color:#ef4444; margin-right:0.5rem;"></i>Raw Material Stock Health vs Safety Stock</h3></div>
    <div style="padding: 1rem;"><canvas id="stockHealthChart" height="100"></canvas></div>
</div>

<div class="card" style="margin-top: 1.5rem;">
    <div class="card-header"><h3>Detailed Stock Levels</h3></div>
    <table class="data-table">
        <thead><tr><th>Material</th><th>Current Stock</th><th>Safety Stock</th><th>Status</th></tr></thead>
        <tbody>
            <?php foreach ($stockHealth as $s): ?>
                <?php $status = $s['current_stock'] <= $s['min_stock_level'] ? 'CRITICAL' : ($s['current_stock'] <= $s['min_stock_level'] * 1.5 ? 'LOW' : 'OK'); ?>
                <tr>
                    <td style="font-weight:600;"><?= htmlspecialchars($s['item_name']) ?></td>
                    <td><?= number_format($s['current_stock'], 1) ?></td>
                    <td><?= number_format($s['min_stock_level'], 1) ?></td>
                    <td>
                        <span class="badge <?= $status === 'OK' ? 'completed' : ($status === 'LOW' ? 'orange' : 'pending') ?>"><?= $status ?></span>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
const stockData = <?= json_encode($stockHealth) ?>;
new Chart(document.getElementById('stockHealthChart'), {
    type: 'bar',
    data: {
        labels: stockData.map(s => s.item_name.substring(0, 25)),
        datasets: [
            { label: 'Current Stock', data: stockData.map(s => s.current_stock), backgroundColor: 'rgba(34,197,94,0.7)', borderRadius: 6 },
            { label: 'Safety Stock (Min)', data: stockData.map(s => s.min_stock_level), backgroundColor: 'rgba(239,68,68,0.4)', borderRadius: 6, borderDash: [5,5] }
        ]
    },
    options: { responsive: true, plugins: { legend: { position: 'top' } }, scales: { y: { beginAtZero: true } } }
});
</script>
<?php endif; ?>
