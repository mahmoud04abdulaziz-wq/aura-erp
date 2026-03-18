<?php
/**
 * AURA ERP — Procurement View
 * Displays purchase orders from the database.
 * Tables: purchase_orders, suppliers
 */

try {
    $purchaseOrders = $pdo->query(
        "SELECT po.po_id, po.order_date, po.total_amount, po.currency, po.order_status,
                po.payment_status, po.delivery_location,
                s.supplier_name
         FROM purchase_orders po
         JOIN suppliers s ON po.supplier_id = s.supplier_id
         ORDER BY po.order_date DESC"
    )->fetchAll();
} catch (Exception $e) {
    error_log("Procurement view error: " . $e->getMessage());
    $purchaseOrders = [];
}
?>

<div class="card">
    <div class="card-header">
        <h3>Purchase Orders</h3>
        <div style="display:flex; gap:0.5rem;">
            <button class="icon-btn"><i class="fa-solid fa-filter"></i></button>
            <button class="icon-btn"><i class="fa-solid fa-download"></i></button>
        </div>
    </div>

    <div style="overflow-x:auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Supplier</th>
                    <th>Order Date</th>
                    <th>Amount</th>
                    <th>Payment</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($purchaseOrders)): ?>
                    <tr>
                        <td colspan="6" style="text-align:center; color:var(--text-secondary); padding:3rem;">
                            <i class="fa-solid fa-truck"
                                style="font-size:2rem; opacity:0.3; display:block; margin-bottom:0.75rem;"></i>
                            No purchase orders found
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($purchaseOrders as $po): ?>
                        <tr>
                            <td><span style="font-family:monospace; color:var(--text-secondary);">
                                    <?= htmlspecialchars($po['po_id']) ?>
                                </span></td>
                            <td>
                                <?= htmlspecialchars($po['supplier_name']) ?>
                            </td>
                            <td>
                                <?= date('M d, Y', strtotime($po['order_date'])) ?>
                            </td>
                            <td><span style="font-weight:600;">
                                    <?= number_format($po['total_amount'] ?? 0, 2) ?>
                                    <?= htmlspecialchars($po['currency'] ?? 'JOD') ?>
                                </span></td>
                            <td><span class="badge <?= $po['payment_status'] === 'Paid' ? 'completed' : 'pending' ?>">
                                    <?= htmlspecialchars($po['payment_status']) ?>
                                </span></td>
                            <td><span
                                    class="badge <?= $po['order_status'] === 'Received' ? 'in-progress' : ($po['order_status'] === 'Cancelled' ? 'orange' : 'pending') ?>">
                                    <?= htmlspecialchars($po['order_status']) ?>
                                </span></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>