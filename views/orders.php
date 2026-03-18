<?php
/**
 * AURA ERP — Orders View
 * Displays sales orders from the database with filter chips and status badges.
 * Tables: sales_orders, customers
 */

// Fetch all sales orders with customer info
try {
    $orders = $pdo->query(
        "SELECT so.so_id, so.order_date, so.total_price, so.order_status, so.payment_method,
                c.company_name
         FROM sales_orders so
         JOIN customers c ON so.customer_id = c.customer_id
         ORDER BY so.order_date DESC"
    )->fetchAll();

    // Count by status for filter badges
    $statusCounts = ['All' => count($orders)];
    foreach ($orders as $o) {
        $s = $o['order_status'];
        $statusCounts[$s] = ($statusCounts[$s] ?? 0) + 1;
    }
} catch (Exception $e) {
    error_log("Orders view error: " . $e->getMessage());
    $orders = [];
    $statusCounts = ['All' => 0];
}

$badgeMap = [
    'Pending' => 'pending',
    'In Production' => 'blue',
    'Pending Delivery' => 'orange',
    'Delivered' => 'completed',
];
?>

<div class="card">
    <div class="card-header" style="flex-direction:column; align-items:flex-start; gap:1rem;">
        <div style="width:100%; display:flex; justify-content:space-between; align-items:center;">
            <h3>Sales Orders</h3>
            <div style="display:flex; gap:0.5rem;">
                <button class="icon-btn"><i class="fa-solid fa-filter"></i></button>
                <button class="icon-btn"><i class="fa-solid fa-download"></i></button>
            </div>
        </div>

        <!-- Filter Chips -->
        <div class="filter-chips">
            <?php foreach ($statusCounts as $status => $count): ?>
                <button class="filter-chip <?= $status === 'All' ? 'active' : '' ?>"
                    onclick="filterOrders(this, '<?= $status ?>')">
                    <?= htmlspecialchars($status) ?> (
                    <?= $count ?>)
                </button>
            <?php endforeach; ?>
        </div>
    </div>

    <div style="overflow-x:auto;">
        <table class="data-table" id="orders-table">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Client</th>
                    <th>Order Date</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($orders)): ?>
                    <tr>
                        <td colspan="6" style="text-align:center; color:var(--text-secondary); padding:3rem;">
                            <i class="fa-solid fa-inbox"
                                style="font-size:2rem; opacity:0.3; display:block; margin-bottom:0.75rem;"></i>
                            No sales orders found
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($orders as $order): ?>
                        <tr data-status="<?= htmlspecialchars($order['order_status']) ?>">
                            <td><span style="font-weight:700; color:var(--accent-primary);">
                                    <?= htmlspecialchars($order['so_id']) ?>
                                </span></td>
                            <td><span style="font-weight:500;">
                                    <?= htmlspecialchars($order['company_name']) ?>
                                </span></td>
                            <td>
                                <?= date('M d, Y', strtotime($order['order_date'])) ?>
                            </td>
                            <td><span style="font-family:monospace; font-weight:600;">$
                                    <?= number_format($order['total_price'] ?? 0, 2) ?>
                                </span></td>
                            <td><span class="badge <?= $badgeMap[$order['order_status']] ?? 'pending' ?>">
                                    <?= htmlspecialchars($order['order_status']) ?>
                                </span></td>
                            <td>
                                <div class="action-menu-container">
                                    <button class="action-btn" onclick="this.nextElementSibling.classList.toggle('active')">
                                        <i class="fa-solid fa-ellipsis-vertical"></i>
                                    </button>
                                    <div class="dropdown-menu">
                                        <button class="dropdown-item"
                                            onclick="showGenericModal('Order Details', 'Details for <?= htmlspecialchars($order['so_id']) ?>')">
                                            <i class="fa-regular fa-eye"></i> View Details
                                        </button>
                                        <button class="dropdown-item"
                                            onclick="showGenericModal('Edit Order', 'Edit form for <?= htmlspecialchars($order['so_id']) ?>')">
                                            <i class="fa-regular fa-pen-to-square"></i> Edit
                                        </button>
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
    function filterOrders(btn, status) {
        document.querySelectorAll('.filter-chip').forEach(c => c.classList.remove('active'));
        btn.classList.add('active');
        document.querySelectorAll('#orders-table tbody tr').forEach(row => {
            if (status === 'All' || row.dataset.status === status) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }
    // Close dropdowns on outside click
    document.addEventListener('click', e => {
        if (!e.target.closest('.action-menu-container')) {
            document.querySelectorAll('.dropdown-menu.active').forEach(m => m.classList.remove('active'));
        }
    });
</script>