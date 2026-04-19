<?php
/** MiskStone ERP — Track Purchase Orders */
$filter = $_GET['status'] ?? 'All';
$sql = "SELECT po.*, s.supplier_name, im.item_name FROM purchase_orders po JOIN suppliers s ON po.supplier_id = s.supplier_id LEFT JOIN item_master im ON po.item_id = im.item_id";
if ($filter !== 'All') $sql .= " WHERE po.order_status = " . $pdo->quote($filter);
$sql .= " ORDER BY po.order_date DESC";
$orders = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="dashboard-container">
    <div class="welcome-banner" style="background: linear-gradient(135deg, #1e293b, #f59e0b); color: white; padding: 2rem; border-radius: 16px; margin-bottom: 2rem;">
        <h1><i class="fa-solid fa-list-check" style="margin-right: 0.75rem;"></i>Track Purchase Orders</h1>
        <p style="opacity: 0.9;">Monitor all purchase orders and their status.</p>
    </div>
    <!-- Filter -->
    <div class="filter-chips" style="margin-bottom: 1.5rem;">
        <?php foreach (['All', 'Pending', 'Completed', 'Cancelled'] as $s): ?>
            <a href="<?= BASE_URL ?>/app.php?view=proc_track&status=<?= $s ?>" class="filter-chip <?= $filter === $s ? 'active' : '' ?>"><?= $s ?></a>
        <?php endforeach; ?>
    </div>
    <div class="card">
        <div style="overflow-x: auto;">
            <table class="data-table">
                <thead><tr><th>PO ID</th><th>Supplier</th><th>Item</th><th>Qty</th><th>Amount</th><th>Date</th><th>Payment</th><th>Status</th></tr></thead>
                <tbody>
                    <?php foreach ($orders as $po): ?>
                        <tr>
                            <td style="font-weight: 600; font-family: monospace;"><?= htmlspecialchars($po['po_id']) ?></td>
                            <td><?= htmlspecialchars($po['supplier_name']) ?></td>
                            <td><?= htmlspecialchars($po['item_name'] ?? '—') ?></td>
                            <td><?= $po['requested_quantity'] ? number_format($po['requested_quantity']) : '—' ?></td>
                            <td style="font-weight: 600;"><?= $po['currency'] ?> <?= number_format($po['total_amount'], 2) ?></td>
                            <td style="color: var(--text-secondary);"><?= htmlspecialchars($po['order_date']) ?></td>
                            <td><span class="badge <?= $po['payment_status'] === 'Paid' ? 'completed' : 'pending' ?>"><?= $po['payment_status'] ?></span></td>
                            <td><span class="badge <?= $po['order_status'] === 'Completed' ? 'completed' : ($po['order_status'] === 'Cancelled' ? 'gray' : 'pending') ?>"><?= $po['order_status'] ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($orders)): ?>
                        <tr><td colspan="8" style="text-align: center; padding: 2rem; color: var(--text-secondary);">No purchase orders found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
