<?php
/**
 * AURA ERP — Accounting View
 * Displays financial overview and recent transactions from the database.
 * Tables: finance_ledger, journal_entries, journal_lines, chart_of_accounts, quotations
 */

try {
    // Financial summary
    $totalIncome = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM finance_ledger WHERE transaction_type = 'Income'")->fetchColumn();
    $totalExpenses = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM finance_ledger WHERE transaction_type = 'Expense'")->fetchColumn();
    $totalBalance = $totalIncome - $totalExpenses;

    // Monthly - current month
    $monthlyIncome = $pdo->query(
        "SELECT COALESCE(SUM(amount), 0) FROM finance_ledger
         WHERE transaction_type = 'Income' AND MONTH(transaction_date) = MONTH(NOW()) AND YEAR(transaction_date) = YEAR(NOW())"
    )->fetchColumn();
    $monthlyExpenses = $pdo->query(
        "SELECT COALESCE(SUM(amount), 0) FROM finance_ledger
         WHERE transaction_type = 'Expense' AND MONTH(transaction_date) = MONTH(NOW()) AND YEAR(transaction_date) = YEAR(NOW())"
    )->fetchColumn();

    // Pending invoices count
    $pendingInvoices = $pdo->query("SELECT COUNT(*) FROM quotations WHERE status IN ('Draft','Sent')")->fetchColumn();

    // Recent transactions
    $transactions = $pdo->query(
        "SELECT transaction_id, transaction_date, transaction_type, category, amount, reference_id
         FROM finance_ledger
         ORDER BY transaction_date DESC
         LIMIT 10"
    )->fetchAll();

} catch (Exception $e) {
    error_log("Accounting view error: " . $e->getMessage());
    $totalBalance = $totalIncome = $totalExpenses = $monthlyIncome = $monthlyExpenses = $pendingInvoices = 0;
    $transactions = [];
}
?>

<!-- Financial Stats -->
<div class="stats-grid" style="margin-bottom: 1.5rem;">
    <div class="stat-card">
        <div class="stat-icon primary"><i class="fa-solid fa-wallet"></i></div>
        <div class="stat-info">
            <h3>Total Balance</h3>
            <p class="stat-value" style="font-size:1.5rem;">$
                <?= number_format($totalBalance, 2) ?>
            </p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon success"><i class="fa-solid fa-arrow-trend-up"></i></div>
        <div class="stat-info">
            <h3>Monthly Income</h3>
            <p class="stat-value" style="font-size:1.5rem;">$
                <?= number_format($monthlyIncome, 2) ?>
            </p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon warning"><i class="fa-solid fa-arrow-trend-down"></i></div>
        <div class="stat-info">
            <h3>Monthly Expenses</h3>
            <p class="stat-value" style="font-size:1.5rem;">$
                <?= number_format($monthlyExpenses, 2) ?>
            </p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon info"><i class="fa-solid fa-file-invoice-dollar"></i></div>
        <div class="stat-info">
            <h3>Pending Invoices</h3>
            <p class="stat-value" style="font-size:1.5rem;">
                <?= $pendingInvoices ?>
            </p>
        </div>
    </div>
</div>

<!-- Recent Transactions + Quick Actions -->
<div class="content-split">
    <div class="card" style="flex: 2;">
        <div class="card-header" style="justify-content:space-between;">
            <h3>Recent Transactions</h3>
            <button class="icon-btn"
                style="width:auto; padding:0 1rem; color:var(--text-primary); border-color:var(--border-color);">
                <i class="fa-solid fa-plus"></i> New Entry
            </button>
        </div>
        <div style="overflow-x:auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Ref ID</th>
                        <th>Date</th>
                        <th>Category</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($transactions)): ?>
                        <tr>
                            <td colspan="4" style="text-align:center; color:var(--text-secondary); padding:3rem;">
                                <i class="fa-solid fa-receipt"
                                    style="font-size:2rem; opacity:0.3; display:block; margin-bottom:0.75rem;"></i>
                                No transactions recorded
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($transactions as $trx): ?>
                            <tr>
                                <td><span style="font-family:monospace; color:var(--text-secondary);">
                                        <?= htmlspecialchars($trx['transaction_id']) ?>
                                    </span></td>
                                <td>
                                    <?= date('M d, Y', strtotime($trx['transaction_date'])) ?>
                                </td>
                                <td><span style="font-weight:500;">
                                        <?= htmlspecialchars($trx['category'] ?? '—') ?>
                                    </span></td>
                                <td>
                                    <span
                                        style="font-weight:700; color:<?= $trx['transaction_type'] === 'Income' ? 'var(--success)' : 'var(--text-primary)' ?>;">
                                        <?= $trx['transaction_type'] === 'Income' ? '+' : '-' ?>$
                                        <?= number_format($trx['amount'], 2) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="card" style="flex: 1;">
        <div class="card-header">
            <h3>Quick Actions</h3>
        </div>
        <div style="display:flex; flex-direction:column; gap:1rem; padding: 1rem 0;">
            <button
                style="padding:1rem; background:var(--bg-body); border:1px solid var(--border-color); border-radius:8px; cursor:pointer; display:flex; align-items:center; gap:1rem; text-align:left;">
                <i class="fa-solid fa-file-invoice" style="font-size:1.5rem; color:var(--accent-primary);"></i>
                <div>
                    <h4 style="margin:0;">Create Invoice</h4>
                    <span style="font-size:0.8rem; color:var(--text-secondary);">Bill a client</span>
                </div>
            </button>
            <button
                style="padding:1rem; background:var(--bg-body); border:1px solid var(--border-color); border-radius:8px; cursor:pointer; display:flex; align-items:center; gap:1rem; text-align:left;">
                <i class="fa-solid fa-receipt" style="font-size:1.5rem; color:var(--warning);"></i>
                <div>
                    <h4 style="margin:0;">Record Expense</h4>
                    <span style="font-size:0.8rem; color:var(--text-secondary);">Log a payment</span>
                </div>
            </button>
            <button
                style="padding:1rem; background:var(--bg-body); border:1px solid var(--border-color); border-radius:8px; cursor:pointer; display:flex; align-items:center; gap:1rem; text-align:left;">
                <i class="fa-solid fa-chart-pie" style="font-size:1.5rem; color:var(--success);"></i>
                <div>
                    <h4 style="margin:0;">Financial Reports</h4>
                    <span style="font-size:0.8rem; color:var(--text-secondary);">View P&L and Balance</span>
                </div>
            </button>
        </div>
    </div>
</div>