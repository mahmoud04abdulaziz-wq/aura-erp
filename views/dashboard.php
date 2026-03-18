<?php
/**
 * AURA ERP — Dashboard View
 * Displays operational overview with live stats from the database.
 */

try {
    // Pending sales orders
    $pendingOrders = $pdo->query("SELECT COUNT(*) FROM sales_orders WHERE order_status = 'Pending'")->fetchColumn();

    // Active production orders
    $activeProduction = $pdo->query("SELECT COUNT(*) FROM production_orders WHERE status IN ('Planned','Mixing','Curing')")->fetchColumn();

    // Low stock items
    $lowStockSql = "SELECT im.item_name, im.min_stock_level, im.base_uom,
                           COALESCE(SUM(il.quantity_change), 0) as current_stock
                    FROM item_master im
                    LEFT JOIN inventory_ledger il ON im.item_id = il.item_id
                    GROUP BY im.item_id
                    HAVING current_stock < im.min_stock_level
                    ORDER BY (current_stock / im.min_stock_level) ASC
                    LIMIT 5";
    $lowStockItems = $pdo->query($lowStockSql)->fetchAll();

    // Total finished goods
    $finishedGoodsCount = $pdo->query("SELECT COALESCE(SUM(quantity_in_stock), 0) FROM inventory_finished_goods")->fetchColumn();

    // Recent system activity
    $recentLogs = $pdo->query(
        "SELECT sl.action_type, sl.description, sl.status, sl.timestamp,
                CONCAT(e.first_name, ' ', e.last_name) as user_name
         FROM system_logs sl
         JOIN users u ON sl.user_id = u.user_id
         JOIN employees e ON u.employee_id = e.employee_id
         ORDER BY sl.timestamp DESC
         LIMIT 5"
    )->fetchAll();

    // Recent sales orders
    $recentOrders = $pdo->query(
        "SELECT so.so_id, c.company_name, so.order_status, so.total_price
         FROM sales_orders so
         JOIN customers c ON so.customer_id = c.customer_id
         ORDER BY so.order_date DESC
         LIMIT 5"
    )->fetchAll();

} catch (Exception $e) {
    error_log("Dashboard query error: " . $e->getMessage());
    $pendingOrders = $activeProduction = $finishedGoodsCount = 0;
    $lowStockItems = $recentLogs = $recentOrders = [];
}
?>

<div class="view-section active" style="display:block;">
    <div class="dashboard-container">
        <!-- Welcome Banner -->
        <div class="welcome-banner">
            <h1>Welcome back, <span class="highlight">
                    <?= htmlspecialchars(explode(' ', getCurrentUserName())[0]) ?>
                </span></h1>
            <p>Here's your operational overview for today.</p>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon warning"><i class="fa-solid fa-triangle-exclamation"></i></div>
                <div class="stat-info">
                    <h3>Pending Orders</h3>
                    <p class="stat-value">
                        <?= $pendingOrders ?>
                    </p>
                    <span class="stat-trend neutral">Awaiting processing</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon success"><i class="fa-solid fa-industry"></i></div>
                <div class="stat-info">
                    <h3>Active Production</h3>
                    <p class="stat-value">
                        <?= $activeProduction ?>
                    </p>
                    <span class="stat-trend neutral">Batches in progress</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon info"><i class="fa-solid fa-box-open"></i></div>
                <div class="stat-info">
                    <h3>Finished Goods</h3>
                    <p class="stat-value">
                        <?= number_format($finishedGoodsCount) ?> PCS
                    </p>
                    <span class="stat-trend neutral">In warehouses</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon <?= count($lowStockItems) > 0 ? 'primary' : 'success' ?>">
                    <i class="fa-solid fa-<?= count($lowStockItems) > 0 ? 'exclamation' : 'check' ?>"></i>
                </div>
                <div class="stat-info">
                    <h3>Low Stock Alerts</h3>
                    <p class="stat-value">
                        <?= count($lowStockItems) ?>
                    </p>
                    <span class="stat-trend <?= count($lowStockItems) > 0 ? 'negative' : 'positive' ?>">
                        <?= count($lowStockItems) > 0 ? 'Items below threshold' : 'All items stocked' ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Content Split -->
        <div class="content-split">
            <!-- Recent Orders -->
            <div class="card recent-orders">
                <div class="card-header">
                    <h3>Recent Orders</h3>
                    <?php if (hasPermission('crm')): ?>
                        <a href="<?= BASE_URL ?>/index.php?view=orders" class="btn-text">View All</a>
                    <?php endif; ?>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Client</th>
                            <th>Status</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentOrders)): ?>
                            <tr>
                                <td colspan="4" style="text-align:center; color:var(--text-secondary); padding:2rem;">No
                                    orders yet</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recentOrders as $order): ?>
                                <tr>
                                    <td>
                                        <?= htmlspecialchars($order['so_id']) ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($order['company_name']) ?>
                                    </td>
                                    <td>
                                        <?php
                                        $badgeClass = match ($order['order_status']) {
                                            'Pending' => 'pending',
                                            'In Production' => 'in-progress',
                                            'Pending Delivery' => 'in-progress',
                                            'Delivered' => 'completed',
                                            default => 'pending'
                                        };
                                        ?>
                                        <span class="badge <?= $badgeClass ?>">
                                            <?= htmlspecialchars($order['order_status']) ?>
                                        </span>
                                    </td>
                                    <td>$
                                        <?= number_format($order['total_price'] ?? 0, 2) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Recent Activity -->
            <div class="card production-status">
                <div class="card-header">
                    <h3>Recent Activity</h3>
                </div>
                <div class="machine-list">
                    <?php if (empty($recentLogs)): ?>
                        <p style="text-align:center; color:var(--text-secondary); padding:2rem;">No recent activity</p>
                    <?php else: ?>
                        <?php foreach ($recentLogs as $log): ?>
                            <div class="machine-item">
                                <span class="machine-name" style="flex:1;">
                                    <?= htmlspecialchars($log['user_name']) ?> —
                                    <?= htmlspecialchars($log['action_type']) ?>
                                </span>
                                <div class="status-indicator <?= $log['status'] === 'Success' ? 'running' : 'maintenance' ?>">
                                </div>
                                <span class="status-text" style="font-size:0.75rem;">
                                    <?= date('H:i', strtotime($log['timestamp'])) ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>