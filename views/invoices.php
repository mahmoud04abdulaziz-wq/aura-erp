<?php
/**
 * AURA ERP — Invoices View (Interactive)
 * Displays quotations/invoices with CRUD actions.
 * Tables: quotations, customers
 */

try {
    $invoices = $pdo->query(
        "SELECT q.quote_id, q.total_amount, q.status, q.valid_until, q.created_at,
                c.company_name
         FROM quotations q
         JOIN customers c ON q.customer_id = c.customer_id
         ORDER BY q.created_at DESC"
    )->fetchAll();

    // Get customers for the create invoice modal
    $customers = $pdo->query("SELECT customer_id, company_name FROM customers ORDER BY company_name")->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    error_log("Invoices view error: " . $e->getMessage());
    $invoices = [];
    $customers = [];
}

$invoiceBadgeMap = [
    'Draft' => 'gray',
    'Sent' => 'blue',
    'Accepted' => 'completed',
    'Rejected' => 'orange',
];
?>

<div class="card">
    <div class="card-header" style="justify-content:space-between;">
        <h3>Client Invoices / Quotations</h3>
        <button class="icon-btn" onclick="openCreateInvoiceModal()"
            style="width:auto; padding:0 1rem; color:var(--accent-primary); border-color:var(--accent-primary);">
            <i class="fa-solid fa-plus"></i> Create Invoice
        </button>
    </div>

    <div style="overflow-x:auto; padding-bottom:4rem;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Invoice #</th>
                    <th>Client Name</th>
                    <th>Issue Date</th>
                    <th>Valid Until</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th style="width:60px;"></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($invoices)): ?>
                    <tr>
                        <td colspan="7" style="text-align:center; color:var(--text-secondary); padding:3rem;">
                            <i class="fa-solid fa-file-invoice"
                                style="font-size:2rem; opacity:0.3; display:block; margin-bottom:0.75rem;"></i>
                            No invoices found
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($invoices as $inv): ?>
                        <?php
                        $invId = 'INV-' . date('Y', strtotime($inv['created_at'])) . '-' . str_pad($inv['quote_id'], 3, '0', STR_PAD_LEFT);
                        $isOverdue = ($inv['status'] === 'Sent' && $inv['valid_until'] && strtotime($inv['valid_until']) < time());
                        $badgeClass = $isOverdue ? 'orange' : ($invoiceBadgeMap[$inv['status']] ?? 'pending');
                        $displayStatus = $isOverdue ? 'Overdue' : $inv['status'];
                        ?>
                        <tr>
                            <td><span style="font-weight:700; color:var(--accent-primary);">
                                    <?= $invId ?>
                                </span></td>
                            <td><span style="font-weight:600;">
                                    <?= htmlspecialchars($inv['company_name']) ?>
                                </span></td>
                            <td>
                                <?= date('M d, Y', strtotime($inv['created_at'])) ?>
                            </td>
                            <td>
                                <?= $inv['valid_until'] ? date('M d, Y', strtotime($inv['valid_until'])) : '—' ?>
                            </td>
                            <td><span style="font-family:monospace; font-weight:600;">$<?= number_format($inv['total_amount'] ?? 0, 2) ?></span></td>
                            <td><span class="badge <?= $badgeClass ?>"><?= $displayStatus ?></span></td>
                            <td>
                                <div class="action-menu-container">
                                    <button class="action-btn" onclick="toggleActionMenu(event, this)"><i class="fa-solid fa-ellipsis-vertical"></i></button>
                                    <div class="dropdown-menu">
                                        <?php if ($inv['status'] === 'Draft'): ?>
                                            <button class="dropdown-item" onclick="updateInvoiceStatus(<?= $inv['quote_id'] ?>, 'Sent')"><i class="fa-regular fa-paper-plane"></i> Mark as Sent</button>
                                        <?php endif; ?>
                                        <?php if ($inv['status'] === 'Sent' || $isOverdue): ?>
                                            <button class="dropdown-item" onclick="updateInvoiceStatus(<?= $inv['quote_id'] ?>, 'Accepted')"><i class="fa-solid fa-check"></i> Accept</button>
                                            <button class="dropdown-item" onclick="updateInvoiceStatus(<?= $inv['quote_id'] ?>, 'Rejected')"><i class="fa-solid fa-xmark"></i> Reject</button>
                                        <?php endif; ?>
                                        <?php if (in_array($inv['status'], ['Draft', 'Rejected'])): ?>
                                            <button class="dropdown-item delete" onclick="deleteInvoice(<?= $inv['quote_id'] ?>)"><i class="fa-regular fa-trash-can"></i> Delete</button>
                                        <?php endif; ?>
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

<script>
    const customersData = <?= json_encode($customers) ?>;

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

    /* ---- Create Invoice Modal ---- */
    function openCreateInvoiceModal() {
        if (typeof showGenericModal !== 'function') return;

        if (customersData.length === 0) {
            alert('No customers found. Please add a customer first via the Orders module.');
            return;
        }

        const customerOptions = customersData.map((c, i) =>
            `<div class="custom-option ${i===0?'selected':''}" data-value="${c.customer_id}">${c.company_name}</div>`
        ).join('');

        const firstCustId = customersData[0].customer_id;
        const firstCustName = customersData[0].company_name;

        const content = `
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
                <h2 style="margin:0;">Create Invoice</h2>
                <button type="button" onclick="document.getElementById('generic-modal').classList.remove('active')" style="background:none; border:none; font-size:1.5rem; cursor:pointer; color:var(--text-primary);">&times;</button>
            </div>
            <form onsubmit="submitNewInvoice(event)">
                <div style="margin-bottom:1.5rem;">
                    <label style="display:block; margin-bottom:0.5rem; color:var(--text-secondary); font-size:0.9rem;">Customer *</label>
                    <div class="custom-select-wrapper">
                        <input type="hidden" name="customer_id" value="${firstCustId}" required>
                        <div class="custom-select">
                            <div class="custom-select-trigger">
                                <span class="selected-text">${firstCustName}</span>
                                <i class="fa-solid fa-chevron-down"></i>
                            </div>
                        </div>
                        <div class="custom-options">
                            ${customerOptions}
                        </div>
                    </div>
                </div>

                <div style="display:flex; gap:1.5rem; margin-bottom:1.5rem;">
                    <div style="flex:1;">
                        <label style="display:block; margin-bottom:0.5rem; color:var(--text-secondary); font-size:0.9rem;">Total Amount ($) *</label>
                        <input type="number" step="0.01" min="0.01" name="total_amount" required placeholder="0.00" style="width:100%; padding:0.75rem; border:1px solid var(--border-color); border-radius:8px; background:var(--bg-body); color:var(--text-primary);">
                    </div>
                    <div style="flex:1;">
                        <label style="display:block; margin-bottom:0.5rem; color:var(--text-secondary); font-size:0.9rem;">Valid Until</label>
                        <input type="date" name="valid_until" style="width:100%; padding:0.75rem; border:1px solid var(--border-color); border-radius:8px; background:var(--bg-body); color:var(--text-primary);">
                    </div>
                </div>

                <button type="submit" style="padding:1rem; background:var(--accent-primary); color:white; border:none; border-radius:8px; width:100%; cursor:pointer; font-weight:600; font-size:1rem;">
                    Create Invoice
                </button>
            </form>
        `;

        const modal = document.getElementById('generic-modal');
        modal.innerHTML = `<div class="modal-content" style="background:var(--bg-panel); padding:2.5rem; border-radius:16px; max-width:550px; margin:auto; box-shadow:var(--shadow-lg);">${content}</div>`;
        modal.classList.add('active');
        setTimeout(() => { if(window.initCustomSelects) window.initCustomSelects(modal); }, 50);
    }

    async function submitNewInvoice(e) {
        e.preventDefault();
        try {
            const formData = new FormData(e.target);
            const res = await fetch('<?= BASE_URL ?>/modules/finance/create_invoice.php', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) {
                window.location.reload();
            } else {
                alert('Error: ' + (data.error || 'Unknown error'));
            }
        } catch (err) { alert('Network error'); }
    }

    /* ---- Invoice Actions ---- */
    async function updateInvoiceStatus(quoteId, newStatus) {
        const actionLabel = newStatus === 'Sent' ? 'send' : newStatus.toLowerCase();
        if (!confirm(`Are you sure you want to ${actionLabel} this invoice?`)) return;
        try {
            const fd = new FormData();
            fd.append('quote_id', quoteId);
            fd.append('status', newStatus);
            const res = await fetch('<?= BASE_URL ?>/modules/finance/update_invoice_status.php', { method: 'POST', body: fd });
            const data = await res.json();
            if (data.success) window.location.reload();
            else alert('Error: ' + data.error);
        } catch (err) { alert('Network error'); }
    }

    async function deleteInvoice(quoteId) {
        if (!confirm('Delete this invoice? This cannot be undone.')) return;
        try {
            const fd = new FormData();
            fd.append('quote_id', quoteId);
            const res = await fetch('<?= BASE_URL ?>/modules/finance/delete_invoice.php', { method: 'POST', body: fd });
            const data = await res.json();
            if (data.success) window.location.reload();
            else alert('Error: ' + data.error);
        } catch (err) { alert('Network error'); }
    }
</script>