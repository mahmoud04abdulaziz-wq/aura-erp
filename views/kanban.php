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

<style>
.kanban-board {
    display: flex;
    overflow-x: auto;
    gap: 1rem;
    padding-bottom: 2rem;
    min-height: 70vh;
}
.kanban-column {
    background: var(--bg-panel);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    min-width: 220px;
    flex: 1;
    display: flex;
    flex-direction: column;
}
.kanban-column-header {
    background: rgba(255,255,255,0.03);
    padding: 1.25rem;
    border-bottom: 1px solid var(--border-color);
    border-radius: 12px 12px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.kanban-column-header h4 {
    margin: 0;
    font-size: 1.1rem;
}
.kanban-cards-container {
    padding: 1rem;
    flex: 1;
    overflow-y: auto;
    min-height: 150px;
}
.kanban-cards-container.drag-over {
    background: rgba(99, 102, 241, 0.05);
    border-radius: 8px;
    border: 2px dashed var(--accent-primary);
}
.kanban-card {
    background: var(--bg-body);
    border: 1px solid var(--border-color);
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1rem;
    cursor: grab;
    transition: all 0.2s ease;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
.kanban-card:active {
    cursor: grabbing;
}
.kanban-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    border-color: var(--accent-primary);
}
.kanban-card.dragging {
    opacity: 0.5;
    transform: scale(0.95);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const cards = document.querySelectorAll('.kanban-card');
    const columns = document.querySelectorAll('.kanban-column');
    const containers = document.querySelectorAll('.kanban-cards-container');

    cards.forEach(card => {
        card.addEventListener('dragstart', () => {
            card.classList.add('dragging');
            // Store the order ID in the dataset so we can send it in fetch
            const orderId = card.querySelector('span[style*="font-weight:700"]').innerText.trim();
            card.dataset.id = orderId;
        });

        card.addEventListener('dragend', () => {
            card.classList.remove('dragging');
            containers.forEach(c => c.classList.remove('drag-over'));
        });
    });

    containers.forEach(container => {
        container.addEventListener('dragover', e => {
            e.preventDefault(); // Necessary to allow dropping
            container.classList.add('drag-over');
            
            // Optional: determine insert position based on cursor Y (append to end for now)
            const draggable = document.querySelector('.dragging');
            if (draggable) {
                container.appendChild(draggable);
            }
        });

        container.addEventListener('dragleave', () => {
            container.classList.remove('drag-over');
        });

        container.addEventListener('drop', async e => {
            e.preventDefault();
            container.classList.remove('drag-over');
            const card = document.querySelector('.dragging');
            
            if (!card) return;
            
            // Remove "No orders" text if it exists
            const noOrders = container.querySelector('div[style*="italic"]');
            if (noOrders) {
                noOrders.remove();
            }

            const newStatus = container.closest('.kanban-column').dataset.status;
            const orderId = card.dataset.id;
            
            if (!orderId) return;

            // Sync with Server globally
            try {
                const fd = new FormData();
                fd.append('so_id', orderId);
                fd.append('status', newStatus);
                
                const res = await fetch('<?= BASE_URL ?>/modules/orders/update_status.php', {
                    method: 'POST',
                    body: fd
                });
                
                const data = await res.json();
                if (!data.success) {
                    alert('Failed to update order status: ' + data.error);
                    window.location.reload(); // Revert board state on failure
                }
            } catch (err) {
                alert('Network error while moving order.');
                window.location.reload();
            }
        });
    });
});
</script>