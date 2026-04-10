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
?>

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
                <p class="stat-value">$<?= number_format($revenueMonth) ?></p>
                <span class="stat-trend positive">Total Income</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon warning" style="background: rgba(239, 68, 68, 0.1); color: #ef4444;">
                <i class="fa-solid fa-money-bill-transfer"></i>
            </div>
            <div class="stat-info">
                <h3>Monthly Expenses</h3>
                <p class="stat-value">$<?= number_format($expensesMonth) ?></p>
                <span class="stat-trend negative">Total Outflow</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon success" style="background: rgba(59, 130, 246, 0.1); color: #3b82f6;">
                <i class="fa-solid fa-piggy-bank"></i>
            </div>
            <div class="stat-info">
                <h3>Net Profit</h3>
                <p class="stat-value">$<?= number_format($profit) ?></p>
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

    <!-- Financial Breakdown -->
    <div class="card" style="margin-top: 2rem;">
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
                            <?= $txn['transaction_type'] === 'Income' ? '+' : '-' ?>$<?= number_format($txn['amount'], 2) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
