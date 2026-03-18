<?php
/**
 * AURA ERP — Kanban (Orders Progress) View
 * Displays sales orders grouped by status in a Kanban board layout.
 * Tables: sales_orders, customers
 */

try {
    $kanbanOrders = $pdo->query(
        "SELECT so.so_id, so.order_status, so.total_price, so.order_date,
                c.company_name
         FROM sales_orders so
         JOIN customers c ON so.customer_id = c.customer_id
         ORDER BY so.order_date DESC"
    )->fetchAll();
} catch (Exception $e) {
    error_log("Kanban view error: " . $e->getMessage());
    $kanbanOrders = [];
}

// Group orders by status
$columns = [
    'Pending' => ['label' => 'Pending', 'badge' => 'gray', 'orders' => []],
    'In Production' => ['label' => 'In Production', 'badge' => 'blue', 'orders' => []],
    'Pending Delivery' => ['label' => 'Quality / Delivery', 'badge' => 'orange', 'orders' => []],
    'Delivered' => ['label' => 'Completed', 'badge' => 'green', 'orders' => []],
];

foreach ($kanbanOrders as $order) {
    $status = $order['order_status'];
    if (isset($columns[$status])) {
        $columns[$status]['orders'][] = $order;
    }
}
?>

<div class="card-header"
    style="margin-bottom: 1.5rem; background: var(--bg-panel); padding: 1.5rem; border-radius: 12px; border: 1px solid var(--border-color);">
    <h3>Orders Progress Board</h3>
    <p style="color:var(--text-secondary); font-size:0.9rem;">Track orders as they move through each stage</p>
</div>

<div class="kanban-board">
    <?php foreach ($columns as $status => $col): ?>
        <div class="kanban-column" data-status="<?= htmlspecialchars($status) ?>">
            <div class="kanban-column-header">
                <h4>
                    <?= htmlspecialchars($col['label']) ?>
                </h4>
                <span class="badge <?= $col['badge'] ?>">
                    <?= count($col['orders']) ?>
                </span>
            </div>
            <div class="kanban-cards-container">
                <?php if (empty($col['orders'])): ?>
                    <div
                        style="padding: 1rem; text-align:center; color: var(--text-secondary); font-style: italic; opacity:0.5;">
                        No orders
                    </div>
                <?php else: ?>
                    <?php foreach ($col['orders'] as $order): ?>
                        <div class="kanban-card" draggable="true">
                            <div style="display:flex; justify-content:space-between; margin-bottom: 0.5rem;">
                                <span style="font-weight:700; color:var(--accent-primary); font-size:0.9rem;">
                                    <?= htmlspecialchars($order['so_id']) ?>
                                </span>
                            </div>
                            <strong style="display:block; margin-bottom: 0.5rem;">
                                <?= htmlspecialchars($order['company_name']) ?>
                            </strong>
                            <div
                                style="display:flex; justify-content:space-between; align-items:center; font-size:0.8rem; color:var(--text-secondary);">
                                <span>$
                                    <?= number_format($order['total_price'] ?? 0, 2) ?>
                                </span>
                                <span>
                                    <?= date('M d', strtotime($order['order_date'])) ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>