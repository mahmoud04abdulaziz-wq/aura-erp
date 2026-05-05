<?php
/**
 * AURA ERP — Accounting View (Interactive)
 * Displays financial overview and recent transactions with CRUD actions.
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
         LIMIT 25"
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
            <p class="stat-value" style="font-size:1.5rem;"><?= number_format($totalBalance, 2) ?> JOD</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon success"><i class="fa-solid fa-arrow-trend-up"></i></div>
        <div class="stat-info">
            <h3>Monthly Income</h3>
            <p class="stat-value" style="font-size:1.5rem;"><?= number_format($monthlyIncome, 2) ?> JOD</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon warning"><i class="fa-solid fa-arrow-trend-down"></i></div>
        <div class="stat-info">
            <h3>Monthly Expenses</h3>
            <p class="stat-value" style="font-size:1.5rem;"><?= number_format($monthlyExpenses, 2) ?> JOD</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon info"><i class="fa-solid fa-file-invoice-dollar"></i></div>
        <div class="stat-info">
            <h3>Pending Invoices</h3>
            <p class="stat-value" style="font-size:1.5rem;"><?= $pendingInvoices ?></p>
        </div>
    </div>
</div>

<!-- Recent Transactions + Quick Actions -->
<div class="content-split">
    <div class="card" style="flex: 2;">
        <div class="card-header" style="justify-content:space-between;">
            <h3>Recent Transactions</h3>
            <button class="icon-btn" onclick="openNewTransactionModal()"
                style="width:auto; padding:0 1rem; color:var(--accent-primary); border-color:var(--accent-primary);">
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
                        <th style="width:60px;"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($transactions)): ?>
                        <tr>
                            <td colspan="5" style="text-align:center; color:var(--text-secondary); padding:3rem;">
                                <i class="fa-solid fa-receipt"
                                    style="font-size:2rem; opacity:0.3; display:block; margin-bottom:0.75rem;"></i>
                                No transactions recorded
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($transactions as $trx): ?>
                            <tr>
                                <td>
                                    <span style="font-family:monospace; color:var(--text-secondary); font-size:0.8rem;">
                                        <?= htmlspecialchars($trx['transaction_id']) ?>
                                    </span>
                                    <?php if (!empty($trx['reference_id'])): ?>
                                        <br><span style="font-size:0.8rem; color:var(--accent-primary); font-weight:500;">
                                            <?= htmlspecialchars($trx['reference_id']) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= date('M d, Y', strtotime($trx['transaction_date'])) ?>
                                </td>
                                <td><span style="font-weight:500;">
                                        <?= htmlspecialchars($trx['category'] ?? '—') ?>
                                    </span></td>
                                <td>
                                    <span style="font-weight:700; color:<?= $trx['transaction_type'] === 'Income' ? 'var(--success)' : '#ef4444' ?>;">
                                        <?= $trx['transaction_type'] === 'Income' ? '+' : '-' ?><?= number_format($trx['amount'], 2) ?> JOD
                                    </span>
                                </td>
                                <td>
                                    <div class="action-menu-container">
                                        <button class="action-btn" onclick="toggleActionMenu(event, this)"><i class="fa-solid fa-ellipsis-vertical"></i></button>
                                        <div class="dropdown-menu">
                                            <button class="dropdown-item delete" onclick="deleteTransaction('<?= htmlspecialchars($trx['transaction_id']) ?>')"><i class="fa-regular fa-trash-can"></i> Delete</button>
                                        </div>
                                    </div>
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
            <button onclick="window.location.href='<?= BASE_URL ?>/?view=invoices'"
                style="padding:1rem; background:var(--bg-body); border:1px solid var(--border-color); border-radius:8px; cursor:pointer; display:flex; align-items:center; gap:1rem; text-align:left;">
                <i class="fa-solid fa-file-invoice" style="font-size:1.5rem; color:var(--accent-primary);"></i>
                <div>
                    <h4 style="margin:0;">Create Invoice</h4>
                    <span style="font-size:0.8rem; color:var(--text-secondary);">Bill a client</span>
                </div>
            </button>
            <button onclick="openNewTransactionModal('Expense')"
                style="padding:1rem; background:var(--bg-body); border:1px solid var(--border-color); border-radius:8px; cursor:pointer; display:flex; align-items:center; gap:1rem; text-align:left;">
                <i class="fa-solid fa-receipt" style="font-size:1.5rem; color:var(--warning);"></i>
                <div>
                    <h4 style="margin:0;">Record Expense</h4>
                    <span style="font-size:0.8rem; color:var(--text-secondary);">Log a payment</span>
                </div>
            </button>
            <button onclick="openNewTransactionModal('Income')"
                style="padding:1rem; background:var(--bg-body); border:1px solid var(--border-color); border-radius:8px; cursor:pointer; display:flex; align-items:center; gap:1rem; text-align:left;">
                <i class="fa-solid fa-hand-holding-dollar" style="font-size:1.5rem; color:var(--success);"></i>
                <div>
                    <h4 style="margin:0;">Record Income</h4>
                    <span style="font-size:0.8rem; color:var(--text-secondary);">Log a receipt</span>
                </div>
            </button>
        </div>
    </div>
</div>

<script>
    document.addEventListener('click', e => {
        if (!e.target.closest('.action-menu-container')) {
            document.querySelectorAll('.dropdown-menu.active').forEach(m => m.classList.remove('active'));
        }
    });

    function toggleActionMenu(e, btn) {
        e.stopPropagation();
        const menu = btn.nextElementSibling;
        const isActive = menu.classList.contains('active');
        document.querySelectorAll('.dropdown-menu.active').forEach(m => m.classList.remove('active'));
        if (!isActive) menu.classList.add('active');
    }

    /* ---- New Transaction Modal ---- */
    function openNewTransactionModal(presetType) {
        if (typeof showGenericModal !== 'function') return;

        const types = ['Income', 'Expense'];
        const defaultType = presetType || 'Income';
        const typeOptions = types.map(t =>
            `<div class="custom-option ${t === defaultType ? 'selected' : ''}" data-value="${t}">${t}</div>`
        ).join('');

        const categories = ['Sales Revenue', 'Service Income', 'Raw Materials', 'Utilities', 'Payroll', 'Equipment', 'Logistics', 'Maintenance', 'Marketing', 'Other'];
        const categoryOptions = categories.map((c, i) =>
            `<div class="custom-option ${i===0?'selected':''}" data-value="${c}">${c}</div>`
        ).join('');

        const content = `
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
                <h2 style="margin:0;">Record Transaction</h2>
                <button type="button" onclick="document.getElementById('generic-modal').classList.remove('active')" style="background:none; border:none; font-size:1.5rem; cursor:pointer; color:var(--text-primary);">&times;</button>
            </div>
            <form onsubmit="submitNewTransaction(event)">
                <div style="display:flex; gap:1.5rem; margin-bottom:1.5rem;">
                    <div style="flex:1;">
                        <label style="display:block; margin-bottom:0.5rem; color:var(--text-secondary); font-size:0.9rem;">Type *</label>
                        <div class="custom-select-wrapper">
                            <input type="hidden" name="transaction_type" value="${defaultType}" required>
                            <div class="custom-select">
                                <div class="custom-select-trigger">
                                    <span class="selected-text">${defaultType}</span>
                                    <i class="fa-solid fa-chevron-down"></i>
                                </div>
                            </div>
                            <div class="custom-options">
                                ${typeOptions}
                            </div>
                        </div>
                    </div>
                    <div style="flex:1;">
                        <label style="display:block; margin-bottom:0.5rem; color:var(--text-secondary); font-size:0.9rem;">Category *</label>
                        <div class="custom-select-wrapper">
                            <input type="hidden" name="category" value="Sales Revenue" required>
                            <div class="custom-select">
                                <div class="custom-select-trigger">
                                    <span class="selected-text">Sales Revenue</span>
                                    <i class="fa-solid fa-chevron-down"></i>
                                </div>
                            </div>
                            <div class="custom-options">
                                ${categoryOptions}
                            </div>
                        </div>
                    </div>
                </div>

                <div style="display:flex; gap:1.5rem; margin-bottom:1.5rem;">
                    <div style="flex:1;">
                        <label style="display:block; margin-bottom:0.5rem; color:var(--text-secondary); font-size:0.9rem;">Amount (JOD) *</label>
                        <input type="number" step="0.01" min="0.01" name="amount" required placeholder="0.00" style="width:100%; padding:0.75rem; border:1px solid var(--border-color); border-radius:8px; background:var(--bg-body); color:var(--text-primary);">
                    </div>
                    <div style="flex:1;">
                        <label style="display:block; margin-bottom:0.5rem; color:var(--text-secondary); font-size:0.9rem;">Date *</label>
                        <input type="date" name="transaction_date" required value="${new Date().toISOString().split('T')[0]}" style="width:100%; padding:0.75rem; border:1px solid var(--border-color); border-radius:8px; background:var(--bg-body); color:var(--text-primary);">
                    </div>
                </div>

                <div style="margin-bottom:2rem;">
                    <label style="display:block; margin-bottom:0.5rem; color:var(--text-secondary); font-size:0.9rem;">Reference ID</label>
                    <input type="text" name="reference_id" placeholder="e.g. SO-260401-0001 (optional)" style="width:100%; padding:0.75rem; border:1px solid var(--border-color); border-radius:8px; background:var(--bg-body); color:var(--text-primary);">
                </div>

                <button type="submit" style="padding:1rem; background:var(--accent-primary); color:white; border:none; border-radius:8px; width:100%; cursor:pointer; font-weight:600; font-size:1rem;">
                    Record Transaction
                </button>
            </form>
        `;

        const modal = document.getElementById('generic-modal');
        modal.innerHTML = `<div class="modal-content" style="background:var(--bg-panel); padding:2.5rem; border-radius:16px; max-width:600px; margin:auto; box-shadow:var(--shadow-lg);">${content}</div>`;
        modal.classList.add('active');
        setTimeout(() => { if(window.initCustomSelects) window.initCustomSelects(modal); }, 50);
    }

    async function submitNewTransaction(e) {
        e.preventDefault();
        try {
            const formData = new FormData(e.target);
            const res = await fetch('<?= BASE_URL ?>/modules/finance/record_transaction.php', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) {
                window.location.reload();
            } else {
                alert('Error: ' + (data.error || 'Unknown error'));
            }
        } catch (err) { alert('Network error'); }
    }

    async function deleteTransaction(txnId) {
        if (!confirm('Delete transaction ' + txnId + '? This cannot be undone.')) return;
        try {
            const fd = new FormData();
            fd.append('transaction_id', txnId);
            const res = await fetch('<?= BASE_URL ?>/modules/finance/delete_transaction.php', { method: 'POST', body: fd });
            const data = await res.json();
            if (data.success) window.location.reload();
            else alert('Error: ' + data.error);
        } catch (err) { alert('Network error'); }
    }
</script>