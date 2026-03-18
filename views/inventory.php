<?php
/**
 * AURA ERP — Inventory View
 * Displays item master list and stock stats from the database.
 * Tables: item_master, inventory_ledger, inventory_finished_goods, warehouses
 */

try {
    // Summary stats
    $rawMaterialStock = $pdo->query(
        "SELECT COALESCE(SUM(il.quantity_change), 0) as total
         FROM item_master im
         LEFT JOIN inventory_ledger il ON im.item_id = il.item_id
         WHERE im.category = 'Raw Material'"
    )->fetchColumn();

    $finishedGoodsStock = $pdo->query(
        "SELECT COALESCE(SUM(quantity_in_stock), 0) FROM inventory_finished_goods"
    )->fetchColumn();

    $lowStockCount = $pdo->query(
        "SELECT COUNT(*) FROM (
            SELECT im.item_id, im.min_stock_level, COALESCE(SUM(il.quantity_change), 0) as current_stock
            FROM item_master im
            LEFT JOIN inventory_ledger il ON im.item_id = il.item_id
            GROUP BY im.item_id
            HAVING current_stock < im.min_stock_level
        ) as low_items"
    )->fetchColumn();

    // Full inventory list with current stock
    $items = $pdo->query(
        "SELECT im.item_id, im.item_name, im.category, im.base_uom, im.min_stock_level,
                COALESCE(SUM(il.quantity_change), 0) as current_stock,
                COALESCE(
                    (SELECT w.warehouse_name FROM inventory_ledger il2
                     JOIN warehouses w ON il2.warehouse_id = w.warehouse_id
                     WHERE il2.item_id = im.item_id
                     ORDER BY il2.timestamp DESC LIMIT 1),
                    '—'
                ) as last_warehouse
         FROM item_master im
         LEFT JOIN inventory_ledger il ON im.item_id = il.item_id
         GROUP BY im.item_id
         ORDER BY im.category, im.item_name"
    )->fetchAll();

} catch (Exception $e) {
    error_log("Inventory view error: " . $e->getMessage());
    $rawMaterialStock = $finishedGoodsStock = $lowStockCount = 0;
    $items = [];
}
?>

<!-- Summary Stats -->
<div class="stats-grid" style="margin-bottom: 1.5rem;">
    <div class="stat-card">
        <div class="stat-info">
            <h3>Total Raw Material</h3>
            <p class="stat-value" style="font-size:1.5rem;">
                <?= number_format($rawMaterialStock, 1) ?>
            </p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-info">
            <h3>Finished Goods</h3>
            <p class="stat-value" style="font-size:1.5rem;">
                <?= number_format($finishedGoodsStock) ?> PCS
            </p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-info">
            <h3>Items Low Stock</h3>
            <p class="stat-value"
                style="font-size:1.5rem; color:<?= $lowStockCount > 0 ? 'var(--warning)' : 'var(--success)' ?>;">
                <?= $lowStockCount ?> Items
            </p>
        </div>
    </div>
</div>

<!-- Inventory Table -->
<div class="card">
    <div class="card-header" style="flex-direction:column; align-items:flex-start; gap:1rem;">
        <div style="width:100%; display:flex; justify-content:space-between; align-items:center;">
            <h3>Inventory Master List</h3>
            <button class="icon-btn"
                style="width:auto; padding:0 1rem; color:var(--success); border-color:var(--success);">
                <i class="fa-solid fa-truck-ramp-box"></i> Receive
            </button>
        </div>
    </div>

    <div style="overflow-x:auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>SKU</th>
                    <th>Item Name</th>
                    <th>Category</th>
                    <th>Quantity</th>
                    <th>Location</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items)): ?>
                    <tr>
                        <td colspan="6" style="text-align:center; color:var(--text-secondary); padding:3rem;">
                            <i class="fa-solid fa-warehouse"
                                style="font-size:2rem; opacity:0.3; display:block; margin-bottom:0.75rem;"></i>
                            No inventory items found
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($items as $item): ?>
                        <?php
                        $stockStatus = 'In Stock';
                        $badgeClass = 'completed';
                        if ($item['current_stock'] <= 0) {
                            $stockStatus = 'Out of Stock';
                            $badgeClass = 'orange';
                        } elseif ($item['current_stock'] < $item['min_stock_level']) {
                            $stockStatus = 'Low Stock';
                            $badgeClass = 'pending';
                        }
                        ?>
                        <tr>
                            <td><span style="font-family:monospace; color:var(--text-secondary);">
                                    <?= htmlspecialchars($item['item_id']) ?>
                                </span></td>
                            <td><span style="font-weight:500;">
                                    <?= htmlspecialchars($item['item_name']) ?>
                                </span></td>
                            <td>
                                <?= htmlspecialchars($item['category']) ?>
                            </td>
                            <td><span style="font-weight:600;">
                                    <?= number_format($item['current_stock'], 1) ?>
                                    <?= htmlspecialchars($item['base_uom']) ?>
                                </span></td>
                            <td>
                                <?= htmlspecialchars($item['last_warehouse']) ?>
                            </td>
                            <td><span class="badge <?= $badgeClass ?>">
                                    <?= $stockStatus ?>
                                </span></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>