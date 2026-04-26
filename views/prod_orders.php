<?php
/**
 * MiskStone ERP — Production Orders (Pin Board)
 * View pending sales orders and select which to move into production.
 * Moving an order changes its Kanban status to "In Production".
 */

// Pending orders (eligible to start production)
$pendingOrders = $pdo->query("
    SELECT so.so_id, so.total_price, so.order_date, so.order_status,
           c.company_name, c.contact_person
    FROM sales_orders so
    JOIN customers c ON so.customer_id = c.customer_id
    WHERE so.order_status = 'Pending'
    ORDER BY so.order_date ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Orders already in production
$inProdOrders = $pdo->query("
    SELECT so.so_id, so.total_price, so.order_date, so.order_status,
           c.company_name, c.contact_person
    FROM sales_orders so
    JOIN customers c ON so.customer_id = c.customer_id
    WHERE so.order_status = 'In Production'
    ORDER BY so.order_date ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Get order items for display
function getOrderItems($pdo, $soId) {
    $stmt = $pdo->prepare("
        SELECT soi.*, im.item_name 
        FROM sales_order_lines soi
        JOIN item_master im ON soi.item_id = im.item_id
        WHERE soi.so_id = ?
    ");
    $stmt->execute([$soId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<div class="dashboard-container">
    <div class="welcome-banner" style="background: linear-gradient(135deg, #1e293b, #3b82f6); color: white; padding: 2rem; border-radius: 16px; margin-bottom: 2rem;">
        <h1 style="margin-bottom: 0.5rem;"><i class="fa-solid fa-clipboard-list" style="margin-right: 0.75rem;"></i>Production Orders</h1>
        <p style="opacity: 0.9;">Select pending orders to move into production. This will update the order pipeline status.</p>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">

        <!-- LEFT: Pending Orders -->
        <div>
            <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1rem;">
                <div style="width: 12px; height: 12px; border-radius: 50%; background: #94a3b8;"></div>
                <h3 style="margin: 0;">Pending Orders</h3>
                <span class="badge gray"><?= count($pendingOrders) ?></span>
            </div>

            <?php if (empty($pendingOrders)): ?>
                <div class="card" style="text-align: center; padding: 3rem; opacity: 0.6;">
                    <i class="fa-solid fa-check-double" style="font-size: 2rem; display: block; margin-bottom: 0.5rem;"></i>
                    <p>All orders have been moved to production</p>
                </div>
            <?php endif; ?>

            <?php foreach ($pendingOrders as $order): ?>
                <?php $items = getOrderItems($pdo, $order['so_id']); ?>
                <div class="card" id="order-<?= $order['so_id'] ?>" style="margin-bottom: 1rem; border-left: 4px solid #94a3b8; padding: 0; overflow: hidden;">
                    <div style="padding: 1.25rem;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                            <span style="font-weight: 800; font-family: monospace; color: var(--accent-primary);"><?= htmlspecialchars($order['so_id']) ?></span>
                            <span class="badge gray">Pending</span>
                        </div>
                        <div style="font-weight: 600; margin-bottom: 0.25rem;"><?= htmlspecialchars($order['company_name']) ?></div>
                        <div style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 0.75rem;">
                            <i class="fa-solid fa-calendar"></i> <?= date('M d, Y', strtotime($order['order_date'])) ?>
                            <span style="margin: 0 0.3rem; opacity: 0.5;">·</span>
                            <span style="font-weight: 700; color: var(--accent-primary);">$<?= number_format($order['total_price'], 2) ?></span>
                        </div>

                        <!-- Order Items -->
                        <?php if (!empty($items)): ?>
                            <div style="background: #f8fafc; border-radius: 6px; padding: 0.5rem 0.75rem; margin-bottom: 0.5rem;">
                                <?php foreach ($items as $item): ?>
                                    <div style="font-size: 0.8rem; padding: 0.15rem 0; display: flex; justify-content: space-between;">
                                        <span style="font-weight: 600;"><?= htmlspecialchars($item['item_name']) ?></span>
                                        <span style="color: var(--text-secondary);">×<?= (int)$item['quantity'] ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div style="padding: 0.75rem 1.25rem; background: #f8fafc; border-top: 1px solid var(--border-color);">
                        <button onclick="moveToProduction('<?= htmlspecialchars($order['so_id']) ?>')" 
                                style="width: 100%; background: #3b82f6; color: white; border: none; padding: 0.6rem; border-radius: 8px; font-weight: 700; cursor: pointer; font-size: 0.85rem;">
                            <i class="fa-solid fa-arrow-right"></i> Move to Production
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- RIGHT: In Production -->
        <div>
            <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1rem;">
                <div style="width: 12px; height: 12px; border-radius: 50%; background: #3b82f6;"></div>
                <h3 style="margin: 0;">In Production</h3>
                <span class="badge" style="background: #3b82f620; color: #3b82f6;"><?= count($inProdOrders) ?></span>
            </div>

            <?php if (empty($inProdOrders)): ?>
                <div class="card" style="text-align: center; padding: 3rem; opacity: 0.6;">
                    <i class="fa-solid fa-industry" style="font-size: 2rem; display: block; margin-bottom: 0.5rem;"></i>
                    <p>No orders currently in production</p>
                </div>
            <?php endif; ?>

            <?php foreach ($inProdOrders as $order): ?>
                <?php $items = getOrderItems($pdo, $order['so_id']); ?>
                <div class="card" style="margin-bottom: 1rem; border-left: 4px solid #3b82f6; padding: 1.25rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                        <span style="font-weight: 800; font-family: monospace; color: var(--accent-primary);"><?= htmlspecialchars($order['so_id']) ?></span>
                        <span class="badge" style="background: #3b82f620; color: #3b82f6; font-weight: 700;">In Production</span>
                    </div>
                    <div style="font-weight: 600; margin-bottom: 0.25rem;"><?= htmlspecialchars($order['company_name']) ?></div>
                    <div style="font-size: 0.85rem; color: var(--text-secondary);">
                        <i class="fa-solid fa-calendar"></i> <?= date('M d, Y', strtotime($order['order_date'])) ?>
                        <span style="margin: 0 0.3rem; opacity: 0.5;">·</span>
                        <span style="font-weight: 700; color: var(--accent-primary);">$<?= number_format($order['total_price'], 2) ?></span>
                    </div>
                    <?php if (!empty($items)): ?>
                        <div style="background: #f8fafc; border-radius: 6px; padding: 0.5rem 0.75rem; margin-top: 0.5rem;">
                            <?php foreach ($items as $item): ?>
                                <div style="font-size: 0.8rem; padding: 0.15rem 0; display: flex; justify-content: space-between;">
                                    <span style="font-weight: 600;"><?= htmlspecialchars($item['item_name']) ?></span>
                                    <span style="color: var(--text-secondary);">×<?= (int)$item['quantity'] ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
function moveToProduction(soId) {
    if (!confirm('Move order ' + soId + ' to "In Production"?')) return;

    const fd = new FormData();
    fd.append('so_id', soId);
    fd.append('status', 'In Production');

    fetch('<?= BASE_URL ?>/modules/orders/update_status.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) location.reload();
            else alert('Error: ' + (data.error || 'Unknown'));
        })
        .catch(() => alert('Network error'));
}
</script>
