<?php
/**
 * MiskStone ERP — Order Archive
 * View archived (completed/delivered) orders.
 */
$archivedOrders = $pdo->query("
    SELECT so.so_id, so.total_price, so.order_date, so.order_status,
           c.company_name, c.contact_person
    FROM sales_orders so
    JOIN customers c ON so.customer_id = c.customer_id
    WHERE so.order_status = 'Archived'
    ORDER BY so.order_date DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="dashboard-container">
    <div class="welcome-banner" style="background: linear-gradient(135deg, #1e293b, #64748b); color: white; padding: 2rem; border-radius: 16px; margin-bottom: 2rem;">
        <h1 style="margin-bottom: 0.5rem;"><i class="fa-solid fa-box-archive" style="margin-right: 0.75rem;"></i>Order Archive</h1>
        <p style="opacity: 0.9;">Completed orders archived after 24 hours, or manually archived for review.</p>
    </div>

    <!-- Auto-archive button -->
    <div style="margin-bottom: 1.5rem; display: flex; justify-content: flex-end;">
        <button onclick="autoArchive()" id="autoArchiveBtn" style="background: var(--accent-primary); color: white; border: none; padding: 0.6rem 1.2rem; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 0.85rem;">
            <i class="fa-solid fa-clock-rotate-left"></i> Auto-Archive Old Orders (24h+)
        </button>
    </div>

    <div class="card">
        <div class="card-header">
            <h3>Archived Orders</h3>
            <span class="badge gray"><?= count($archivedOrders) ?> archived</span>
        </div>
        <div style="overflow-x: auto;">
            <table class="data-table">
                <thead>
                    <tr><th>Order ID</th><th>Client</th><th>Contact</th><th>Value</th><th>Date</th><th>Status</th></tr>
                </thead>
                <tbody>
                    <?php if (empty($archivedOrders)): ?>
                        <tr><td colspan="6" style="text-align: center; padding: 3rem; color: var(--text-secondary);">
                            <i class="fa-solid fa-box-archive" style="font-size: 2rem; opacity: 0.3; display: block; margin-bottom: 0.5rem;"></i>
                            No archived orders yet.
                        </td></tr>
                    <?php else: ?>
                        <?php foreach ($archivedOrders as $order): ?>
                            <tr>
                                <td style="font-weight: 600; font-family: monospace;"><?= htmlspecialchars($order['so_id']) ?></td>
                                <td style="font-weight: 600;"><?= htmlspecialchars($order['company_name']) ?></td>
                                <td style="color: var(--text-secondary);"><?= htmlspecialchars($order['contact_person'] ?? '—') ?></td>
                                <td style="font-weight: 700; color: var(--success);"><?= number_format($order['total_price'], 2) ?> JOD</td>
                                <td style="color: var(--text-secondary);"><?= htmlspecialchars($order['order_date']) ?></td>
                                <td><span class="badge gray">Archived</span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function autoArchive() {
    const btn = document.getElementById('autoArchiveBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Archiving...';

    fetch('<?= BASE_URL ?>/modules/orders/archive_order.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=auto_archive'
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert('Error: ' + (data.error || 'Unknown'));
        }
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-clock-rotate-left"></i> Auto-Archive Old Orders (24h+)';
    });
}
</script>
