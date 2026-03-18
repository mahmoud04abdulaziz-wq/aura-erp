<?php
/**
 * AURA ERP — Invoices View
 * Displays quotations/invoices from the database.
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
} catch (Exception $e) {
    error_log("Invoices view error: " . $e->getMessage());
    $invoices = [];
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
        <button class="icon-btn"
            style="width:auto; padding:0 1rem; color:var(--text-primary); border-color:var(--border-color);">
            <i class="fa-solid fa-plus"></i> Create Invoice
        </button>
    </div>

    <div style="overflow-x:auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Invoice #</th>
                    <th>Client Name</th>
                    <th>Issue Date</th>
                    <th>Valid Until</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Actions</th>
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
                            <td><span style="font-family:monospace; font-weight:600;">$
                                    <?= number_format($inv['total_amount'] ?? 0, 2) ?>
                                </span></td>
                            <td><span class="badge <?= $badgeClass ?>">
                                    <?= $displayStatus ?>
                                </span></td>
                            <td>
                                <button class="action-btn" title="View"><i class="fa-regular fa-eye"></i></button>
                                <button class="action-btn" title="Send"><i class="fa-regular fa-paper-plane"></i></button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>