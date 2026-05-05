<?php
$revenueMonth = $pdo->query("SELECT COALESCE(SUM(amount),0) FROM finance_ledger WHERE transaction_type = 'Income' AND MONTH(transaction_date) = MONTH(CURRENT_DATE())")->fetchColumn();
$expensesMonth = $pdo->query("SELECT COALESCE(SUM(amount),0) FROM finance_ledger WHERE transaction_type = 'Expense' AND MONTH(transaction_date) = MONTH(CURRENT_DATE())")->fetchColumn();
$profit = $revenueMonth - $expensesMonth;

$activeOrders = $pdo->query("SELECT COUNT(*) FROM sales_orders WHERE order_status != 'Delivered'")->fetchColumn();

// Fetch Recent Financial Transactions
$transactions = $pdo->query("
    SELECT transaction_id, transaction_date, transaction_type, category, amount 
    FROM finance_ledger 
    ORDER BY transaction_date DESC LIMIT 5
")->fetchAll();

// ── Chart Data ──

// Revenue vs Expenses Trend (last 6 months)
$revenueTrend = $pdo->query("
    SELECT DATE_FORMAT(transaction_date, '%Y-%m') as month,
           SUM(CASE WHEN transaction_type='Income' THEN amount ELSE 0 END) as income,
           SUM(CASE WHEN transaction_type='Expense' THEN amount ELSE 0 END) as expenses
    FROM finance_ledger
    GROUP BY DATE_FORMAT(transaction_date, '%Y-%m')
    ORDER BY month ASC
    LIMIT 6
")->fetchAll(PDO::FETCH_ASSOC);

// Sales by Product Category
$salesByCategory = $pdo->query("
    SELECT im.category, SUM(sol.quantity * sol.unit_price) as revenue
    FROM so_line_items sol
    JOIN item_master im ON sol.item_id = im.item_id
    JOIN sales_orders so ON sol.so_id = so.so_id
    GROUP BY im.category
    ORDER BY revenue DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Fallback if so_line_items doesn't exist
if (empty($salesByCategory)) {
    try {
        $salesByCategory = $pdo->query("
            SELECT im.category, COUNT(*) as revenue
            FROM production_orders po
            JOIN item_master im ON po.item_id = im.item_id
            GROUP BY im.category
        ")->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) { $salesByCategory = []; }
}

// Production Orders by Status
$prodByStatus = $pdo->query("
    SELECT status, COUNT(*) as cnt
    FROM production_orders
    GROUP BY status
")->fetchAll(PDO::FETCH_ASSOC);

// Order Pipeline Summary
$orderPipeline = $pdo->query("
    SELECT order_status, COUNT(*) as cnt
    FROM sales_orders
    GROUP BY order_status
    ORDER BY FIELD(order_status, 'In Production', 'Pending Delivery', 'Delivered')
")->fetchAll(PDO::FETCH_ASSOC);
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<div class="dashboard-container">
    <div class="welcome-banner" style="background: linear-gradient(135deg, #0f172a, #334155); color: white;">
        <h1>Executive Dashboard</h1>
        <p>MiskStone Factory Financial Overview & Status</p>
    </div>

    <!-- Stats Grid -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon info" style="background: rgba(16, 185, 129, 0.1); color: #10b981;">
                <i class="fa-solid fa-money-bill-trend-up"></i>
            </div>
            <div class="stat-info">
                <h3>Monthly Revenue</h3>
                <p class="stat-value"><?= number_format($revenueMonth) ?> JOD</p>
                <span class="stat-trend positive">Total Income</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon warning" style="background: rgba(239, 68, 68, 0.1); color: #ef4444;">
                <i class="fa-solid fa-money-bill-transfer"></i>
            </div>
            <div class="stat-info">
                <h3>Monthly Expenses</h3>
                <p class="stat-value"><?= number_format($expensesMonth) ?> JOD</p>
                <span class="stat-trend negative">Total Outflow</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon success" style="background: rgba(59, 130, 246, 0.1); color: #3b82f6;">
                <i class="fa-solid fa-piggy-bank"></i>
            </div>
            <div class="stat-info">
                <h3>Net Profit</h3>
                <p class="stat-value"><?= number_format($profit) ?> JOD</p>
                <span class="stat-trend neutral">This Month</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon primary">
                <i class="fa-solid fa-layer-group"></i>
            </div>
            <div class="stat-info">
                <h3>Active Operations</h3>
                <p class="stat-value"><?= $activeOrders ?></p>
                <span class="stat-trend neutral">Orders in pipeline</span>
            </div>
        </div>
    </div>

    <!-- Charts Row 1: Revenue Trend + Sales by Category -->
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; margin-top: 1.5rem;">
        <div class="card">
            <div class="card-header"><h3><i class="fa-solid fa-chart-line" style="margin-right: 0.5rem; color: var(--accent-primary);"></i>Revenue vs Expenses Trend</h3></div>
            <div style="padding: 1rem;"><canvas id="execRevenueTrend" height="120"></canvas></div>
        </div>
        <div class="card">
            <div class="card-header"><h3><i class="fa-solid fa-tags" style="margin-right: 0.5rem; color: #f59e0b;"></i>Sales by Category</h3></div>
            <div style="padding: 1rem;"><canvas id="execSalesCategory" height="200"></canvas></div>
        </div>
    </div>

    <!-- Charts Row 2: Production Status + Order Pipeline -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-top: 1.5rem;">
        <div class="card">
            <div class="card-header"><h3><i class="fa-solid fa-industry" style="margin-right: 0.5rem; color: #8b5cf6;"></i>Production Orders by Status</h3></div>
            <div style="padding: 1rem; display: flex; justify-content: center;"><canvas id="execProdStatus" height="200" style="max-width: 280px;"></canvas></div>
        </div>
        <div class="card">
            <div class="card-header"><h3><i class="fa-solid fa-layer-group" style="margin-right: 0.5rem; color: #06b6d4;"></i>Sales Order Pipeline</h3></div>
            <div style="padding: 1rem;"><canvas id="execOrderPipeline" height="200"></canvas></div>
        </div>
    </div>

    <!-- Financial Breakdown -->
    <div class="card" style="margin-top: 1.5rem;">
        <div class="card-header">
            <h3>Recent Cash Flow</h3>
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Transaction ID</th>
                    <th>Category</th>
                    <th>Type</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($transactions as $txn): ?>
                    <tr>
                        <td><?= htmlspecialchars($txn['transaction_date']) ?></td>
                        <td style="font-weight:600;">
                            <a href="<?= BASE_URL ?>/app.php?view=accounting" style="color:var(--accent-primary); text-decoration:none;">
                                <i class="fa-solid fa-link" style="font-size:0.8rem; margin-right:4px; opacity:0.7;"></i><?= htmlspecialchars($txn['transaction_id']) ?>
                            </a>
                        </td>
                        <td><?= htmlspecialchars($txn['category']) ?></td>
                        <td>
                            <span class="badge <?= $txn['transaction_type'] === 'Income' ? 'completed' : 'pending' ?>">
                                <?= $txn['transaction_type'] ?>
                            </span>
                        </td>
                        <td style="font-weight:600; color: <?= $txn['transaction_type'] === 'Income' ? '#10b981' : '#ef4444' ?>">
                            <?= $txn['transaction_type'] === 'Income' ? '+' : '-' ?><?= number_format($txn['amount'], 2) ?> JOD
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// Color palette
const chartColors = {
    purple: '#6366f1', green: '#10b981', red: '#ef4444', amber: '#f59e0b',
    cyan: '#06b6d4', violet: '#8b5cf6', pink: '#ec4899', slate: '#64748b',
    blue: '#3b82f6', emerald: '#059669'
};
const bgPalette = ['rgba(99,102,241,0.7)','rgba(245,158,11,0.7)','rgba(16,185,129,0.7)','rgba(236,72,153,0.7)','rgba(6,182,212,0.7)','rgba(139,92,246,0.7)','rgba(239,68,68,0.7)','rgba(100,116,139,0.7)'];

// 1. Revenue vs Expenses Trend (Line chart)
const revData = <?= json_encode($revenueTrend) ?>;
new Chart(document.getElementById('execRevenueTrend'), {
    type: 'line',
    data: {
        labels: revData.map(r => {
            const [y, m] = r.month.split('-');
            return new Date(y, m-1).toLocaleString('en', {month:'short', year:'2-digit'});
        }),
        datasets: [
            {
                label: 'Income',
                data: revData.map(r => r.income),
                borderColor: chartColors.green,
                backgroundColor: 'rgba(16,185,129,0.1)',
                fill: true,
                tension: 0.4,
                borderWidth: 2.5,
                pointRadius: 4,
                pointBackgroundColor: chartColors.green
            },
            {
                label: 'Expenses',
                data: revData.map(r => r.expenses),
                borderColor: chartColors.red,
                backgroundColor: 'rgba(239,68,68,0.08)',
                fill: true,
                tension: 0.4,
                borderWidth: 2.5,
                pointRadius: 4,
                pointBackgroundColor: chartColors.red
            }
        ]
    },
    options: {
        responsive: true,
        interaction: { intersect: false, mode: 'index' },
        plugins: { legend: { position: 'top', labels: { usePointStyle: true, pointStyle: 'circle' } } },
        scales: { y: { beginAtZero: true, ticks: { callback: v => v.toLocaleString() + ' JOD' } } }
    }
});

// 2. Sales by Category (Doughnut)
const catData = <?= json_encode($salesByCategory) ?>;
new Chart(document.getElementById('execSalesCategory'), {
    type: 'doughnut',
    data: {
        labels: catData.map(c => c.category || 'Other'),
        datasets: [{
            data: catData.map(c => c.revenue),
            backgroundColor: bgPalette,
            borderWidth: 2,
            borderColor: '#fff',
            hoverOffset: 6
        }]
    },
    options: {
        responsive: true,
        cutout: '60%',
        plugins: {
            legend: { position: 'bottom', labels: { boxWidth: 12, padding: 12 } }
        }
    }
});

// 3. Production Orders by Status (Pie)
const prodData = <?= json_encode($prodByStatus) ?>;
const statusColors = { 'Planned': chartColors.slate, 'Mixing': chartColors.amber, 'Curing': chartColors.violet, 'Completed': chartColors.green, 'Failed': chartColors.red };
new Chart(document.getElementById('execProdStatus'), {
    type: 'pie',
    data: {
        labels: prodData.map(p => p.status),
        datasets: [{
            data: prodData.map(p => p.cnt),
            backgroundColor: prodData.map(p => statusColors[p.status] || chartColors.slate),
            borderWidth: 2,
            borderColor: '#fff'
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, padding: 12 } } }
    }
});

// 4. Sales Order Pipeline (Horizontal bar)
const pipeData = <?= json_encode($orderPipeline) ?>;
const pipeColors = { 'In Production': chartColors.purple, 'Pending Delivery': chartColors.amber, 'Delivered': chartColors.green, 'New': chartColors.cyan, 'Confirmed': chartColors.blue };
new Chart(document.getElementById('execOrderPipeline'), {
    type: 'bar',
    data: {
        labels: pipeData.map(p => p.order_status),
        datasets: [{
            label: 'Orders',
            data: pipeData.map(p => p.cnt),
            backgroundColor: pipeData.map(p => pipeColors[p.order_status] || chartColors.slate),
            borderRadius: 8,
            barThickness: 28
        }]
    },
    options: {
        indexAxis: 'y',
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            x: { beginAtZero: true, ticks: { stepSize: 1 } },
            y: { grid: { display: false } }
        }
    }
});
</script>
