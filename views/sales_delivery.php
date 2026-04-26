<?php
/**
 * MiskStone ERP — Sales Delivery Dispatch
 * Shows ALL accepted orders (any status except Delivered/Archived).
 * Stock check happens live — if FG stock >= order qty, Dispatch button is enabled.
 * Dispatching: deducts FG inventory, records revenue, marks Delivered.
 */

// Fetch ALL non-delivered, non-archived orders
$allOrders = $pdo->query("
    SELECT so.so_id, so.customer_id, so.total_price, so.order_date, so.order_status, so.payment_method,
           c.company_name, c.contact_person
    FROM sales_orders so
    JOIN customers c ON so.customer_id = c.customer_id
    WHERE so.order_status NOT IN ('Delivered', 'Archived')
    ORDER BY so.order_date ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Get line items for each order + live stock check
$orderDetails = [];
foreach ($allOrders as $order) {
    $linesStmt = $pdo->prepare("
        SELECT sol.item_id, sol.quantity, sol.unit_price, im.item_name
        FROM sales_order_lines sol
        JOIN item_master im ON sol.item_id = im.item_id
        WHERE sol.so_id = ?
    ");
    $linesStmt->execute([$order['so_id']]);
    $lines = $linesStmt->fetchAll(PDO::FETCH_ASSOC);

    $allReady = true;
    foreach ($lines as &$line) {
        $stockStmt = $pdo->prepare("SELECT COALESCE(SUM(quantity_change), 0) FROM inventory_ledger WHERE item_id = ?");
        $stockStmt->execute([$line['item_id']]);
        $line['current_stock'] = (float)$stockStmt->fetchColumn();
        $line['is_sufficient'] = $line['current_stock'] >= $line['quantity'];
        if (!$line['is_sufficient']) $allReady = false;
    }
    unset($line);

    $orderDetails[$order['so_id']] = [
        'lines' => $lines,
        'all_ready' => $allReady,
        'has_lines' => !empty($lines)
    ];
}

// Recently delivered (last 10)
$deliveredOrders = $pdo->query("
    SELECT so.so_id, c.company_name, so.total_price, so.order_date
    FROM sales_orders so
    JOIN customers c ON so.customer_id = c.customer_id
    WHERE so.order_status = 'Delivered'
    ORDER BY so.order_date DESC
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="dashboard-container">
    <div class="welcome-banner" style="background: linear-gradient(135deg, #0f172a, #22c55e); color: white; padding: 2rem; border-radius: 16px; margin-bottom: 2rem;">
        <h1 style="margin-bottom: 0.5rem;"><i class="fa-solid fa-truck-ramp-box" style="margin-right: 0.75rem;"></i>Delivery Dispatch</h1>
        <p style="opacity: 0.9;">All accepted orders appear here. If finished goods stock satisfies the order, click <strong>Dispatch</strong> to deliver and record revenue.</p>
    </div>

    <!-- Stats -->
    <div class="stats-grid" style="margin-bottom: 2rem;">
        <?php
        $readyCount = count(array_filter($orderDetails, fn($d) => $d['all_ready'] && $d['has_lines']));
        $waitingCount = count($allOrders) - $readyCount;
        ?>
        <div class="stat-card" style="border-left: 4px solid #22c55e;">
            <div class="stat-info">
                <h3 style="color: var(--text-secondary); font-size: 0.9rem;">Ready to Ship</h3>
                <p class="stat-value"><?= $readyCount ?></p>
                <span class="stat-trend positive">Stock Satisfied</span>
            </div>
        </div>
        <div class="stat-card" style="border-left: 4px solid #f59e0b;">
            <div class="stat-info">
                <h3 style="color: var(--text-secondary); font-size: 0.9rem;">Awaiting Stock</h3>
                <p class="stat-value"><?= $waitingCount ?></p>
                <span class="stat-trend negative">Insufficient FG</span>
            </div>
        </div>
        <div class="stat-card" style="border-left: 4px solid var(--accent-primary);">
            <div class="stat-info">
                <h3 style="color: var(--text-secondary); font-size: 0.9rem;">Total Orders</h3>
                <p class="stat-value"><?= count($allOrders) ?></p>
            </div>
        </div>
        <div class="stat-card" style="border-left: 4px solid #06b6d4;">
            <div class="stat-info">
                <h3 style="color: var(--text-secondary); font-size: 0.9rem;">Delivered This Month</h3>
                <p class="stat-value"><?= count($deliveredOrders) ?></p>
            </div>
        </div>
    </div>

    <?php if (empty($allOrders)): ?>
        <div class="card" style="text-align: center; padding: 4rem;">
            <i class="fa-solid fa-truck" style="font-size: 3rem; color: var(--text-secondary); opacity: 0.3; margin-bottom: 1rem;"></i>
            <h3 style="color: var(--text-secondary); margin-bottom: 0.5rem;">No active orders</h3>
            <p style="color: var(--text-secondary); opacity: 0.7;">Accept web leads in the Order Inbox to see them here.</p>
        </div>
    <?php else: ?>
        <!-- Dispatch Cards -->
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(450px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
            <?php foreach ($allOrders as $order): ?>
                <?php
                $detail = $orderDetails[$order['so_id']];
                $allReady = $detail['all_ready'] && $detail['has_lines'];
                $borderColor = $allReady ? '#22c55e' : '#f59e0b';
                $statusBadge = match($order['order_status']) {
                    'Pending' => ['bg' => '#dbeafe', 'color' => '#1e40af', 'label' => 'Pending'],
                    'In Production' => ['bg' => '#fef3c7', 'color' => '#92400e', 'label' => 'In Production'],
                    'Pending Delivery' => ['bg' => '#d1fae5', 'color' => '#065f46', 'label' => 'Pending Delivery'],
                    default => ['bg' => '#e2e8f0', 'color' => '#475569', 'label' => $order['order_status'] ?: 'New']
                };
                ?>
                <div class="card" id="dispatch-card-<?= $order['so_id'] ?>" style="padding: 0; overflow: hidden; border-left: 4px solid <?= $borderColor ?>; transition: all 0.3s;">

                    <!-- Header -->
                    <div style="padding: 1.25rem 1.5rem; background: <?= $allReady ? '#f0fdf4' : '#fffbeb' ?>; border-bottom: 1px solid var(--border-color);">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <h3 style="margin: 0; font-size: 1.05rem; color: #0f172a;">
                                    <?= htmlspecialchars($order['so_id']) ?>
                                </h3>
                                <p style="margin: 0.25rem 0 0; font-size: 0.85rem; color: #475569;">
                                    <i class="fa-solid fa-building"></i> <?= htmlspecialchars($order['company_name']) ?>
                                    <?= $order['contact_person'] ? ' · ' . htmlspecialchars($order['contact_person']) : '' ?>
                                </p>
                            </div>
                            <div style="display: flex; gap: 0.5rem; align-items: center;">
                                <span style="background: <?= $statusBadge['bg'] ?>; color: <?= $statusBadge['color'] ?>; padding: 0.25rem 0.6rem; border-radius: 6px; font-size: 0.7rem; font-weight: 700;">
                                    <?= $statusBadge['label'] ?>
                                </span>
                                <?php if ($allReady): ?>
                                    <span style="background: #d1fae5; color: #065f46; padding: 0.25rem 0.6rem; border-radius: 6px; font-size: 0.7rem; font-weight: 700;">
                                        ✅ Stock OK
                                    </span>
                                <?php else: ?>
                                    <span style="background: #fef3c7; color: #92400e; padding: 0.25rem 0.6rem; border-radius: 6px; font-size: 0.7rem; font-weight: 700;">
                                        ⏳ Low Stock
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Order Details -->
                    <div style="padding: 1.25rem 1.5rem;">
                        <div style="display: flex; gap: 1.5rem; margin-bottom: 1rem; font-size: 0.85rem; color: var(--text-secondary);">
                            <span><i class="fa-solid fa-calendar"></i> <?= htmlspecialchars($order['order_date']) ?></span>
                            <span style="font-weight: 700; color: var(--accent-primary); font-size: 1rem;">$<?= number_format($order['total_price'], 2) ?></span>
                        </div>

                        <?php if (!empty($detail['lines'])): ?>
                            <p style="font-size: 0.7rem; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 0.5rem;">
                                <i class="fa-solid fa-boxes-stacked"></i> Finished Goods Check
                            </p>
                            <div style="border: 1px solid var(--border-color); border-radius: 8px; overflow: hidden;">
                                <?php foreach ($detail['lines'] as $idx => $line): ?>
                                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.6rem 1rem; <?= $idx < count($detail['lines']) - 1 ? 'border-bottom: 1px solid var(--border-color);' : '' ?> background: <?= $line['is_sufficient'] ? ($idx % 2 === 0 ? '#f0fdf4' : 'white') : '#fef2f2' ?>;">
                                        <div>
                                            <span style="font-weight: 600; font-size: 0.85rem;"><?= htmlspecialchars($line['item_name']) ?></span>
                                            <span style="color: var(--text-secondary); font-size: 0.8rem;"> — Need: <?= (int)$line['quantity'] ?></span>
                                        </div>
                                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                                            <span style="font-size: 0.8rem; color: #64748b;">In Stock: <strong><?= (int)$line['current_stock'] ?></strong></span>
                                            <?php if ($line['is_sufficient']): ?>
                                                <i class="fa-solid fa-circle-check" style="color: #22c55e;"></i>
                                            <?php else: ?>
                                                <i class="fa-solid fa-circle-xmark" style="color: #ef4444;"></i>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p style="color: var(--text-secondary); font-size: 0.85rem; padding: 0.5rem 0;">
                                <i class="fa-solid fa-info-circle"></i> No line items linked to this order. Order may need to be re-accepted.
                            </p>
                        <?php endif; ?>
                    </div>

                    <!-- Dispatch Button -->
                    <div style="padding: 1rem 1.5rem; background: #f8fafc; border-top: 1px solid var(--border-color);">
                        <?php if ($allReady): ?>
                            <button onclick="dispatchOrder('<?= $order['so_id'] ?>')"
                                    id="btn-<?= $order['so_id'] ?>"
                                    style="width: 100%; background: linear-gradient(135deg, #16a34a, #22c55e); color: white; border: none; padding: 0.8rem; border-radius: 10px; font-weight: 700; cursor: pointer; font-size: 0.95rem; transition: all 0.2s; box-shadow: 0 2px 8px rgba(34,197,94,0.3);">
                                <i class="fa-solid fa-truck-fast"></i> Dispatch & Record Delivery
                            </button>
                        <?php else: ?>
                            <button disabled style="width: 100%; background: #e2e8f0; color: #94a3b8; border: none; padding: 0.8rem; border-radius: 10px; font-weight: 600; font-size: 0.9rem; cursor: not-allowed;">
                                <i class="fa-solid fa-hourglass-half"></i> <?= $detail['has_lines'] ? 'Insufficient Finished Goods — Awaiting Production' : 'No Items Linked' ?>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Recently Delivered -->
    <?php if (!empty($deliveredOrders)): ?>
        <div class="card" style="margin-top: 2rem;">
            <div class="card-header">
                <h3><i class="fa-solid fa-check-double" style="color: var(--success); margin-right: 0.5rem;"></i>Recently Delivered</h3>
            </div>
            <table class="data-table">
                <thead>
                    <tr><th>Order ID</th><th>Client</th><th>Value</th><th>Date</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($deliveredOrders as $d): ?>
                        <tr>
                            <td style="font-weight: 600;"><?= htmlspecialchars($d['so_id']) ?></td>
                            <td><?= htmlspecialchars($d['company_name']) ?></td>
                            <td style="font-weight: 700; color: var(--success);">$<?= number_format($d['total_price'], 2) ?></td>
                            <td style="color: var(--text-secondary);"><?= htmlspecialchars($d['order_date']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script>
function dispatchOrder(soId) {
    if (!confirm('Dispatch this order?\n\nThis will:\n• Deduct Finished Goods from inventory\n• Record revenue in Finance\n• Mark order as Delivered')) return;

    const btn = document.getElementById('btn-' + soId);
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Processing...';

    fetch('<?= BASE_URL ?>/modules/orders/process_delivery.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'so_id=' + encodeURIComponent(soId)
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const card = document.getElementById('dispatch-card-' + soId);
            card.style.transform = 'scale(0.95)';
            card.style.opacity = '0';
            setTimeout(() => {
                card.style.display = 'none';
                const toast = document.createElement('div');
                toast.innerHTML = '<i class="fa-solid fa-check-circle"></i> ' + data.message;
                toast.style.cssText = 'position:fixed; bottom:2rem; right:2rem; background:#22c55e; color:white; padding:1rem 1.5rem; border-radius:12px; font-weight:600; z-index:9999; box-shadow:0 8px 30px rgba(0,0,0,0.15);';
                document.body.appendChild(toast);
                setTimeout(() => toast.remove(), 4000);
            }, 400);
        } else {
            alert('Error: ' + (data.error || 'Failed to dispatch'));
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-truck-fast"></i> Dispatch & Record Delivery';
        }
    })
    .catch(err => {
        alert('Network error. Please try again.');
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-truck-fast"></i> Dispatch & Record Delivery';
    });
}
</script>
