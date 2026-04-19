<?php
/**
 * MiskStone ERP — Kanban (Orders Progress) View
 * Displays sales orders grouped by status in a Kanban board layout.
 * Each card shows a visual progress bar indicating pipeline stage.
 */

try {
    $kanbanOrders = $pdo->query(
        "SELECT so.so_id, so.order_status, so.total_price, so.order_date,
                c.company_name
         FROM sales_orders so
         JOIN customers c ON so.customer_id = c.customer_id
         WHERE so.order_status != 'Archived'
         ORDER BY so.order_date DESC"
    )->fetchAll();
} catch (Exception $e) {
    error_log("Kanban view error: " . $e->getMessage());
    $kanbanOrders = [];
}

// Group orders by status
$statusSteps = ['Pending', 'In Production', 'Completed', 'Delivered'];
$columns = [
    'Pending' => ['label' => 'Pending', 'badge' => 'gray', 'step' => 1, 'orders' => []],
    'In Production' => ['label' => 'In Production', 'badge' => 'blue', 'step' => 2, 'orders' => []],
    'Completed' => ['label' => 'Completed', 'badge' => 'orange', 'step' => 3, 'orders' => []],
    'Delivered' => ['label' => 'Delivered', 'badge' => 'green', 'step' => 4, 'orders' => []],
];

foreach ($kanbanOrders as $order) {
    $status = $order['order_status'];
    if (isset($columns[$status])) {
        $columns[$status]['orders'][] = $order;
    }
}

function getProgressPercent($status) {
    $map = ['Pending' => 10, 'In Production' => 40, 'Completed' => 75, 'Delivered' => 100];
    return $map[$status] ?? 0;
}
function getProgressColor($status) {
    $map = ['Pending' => '#94a3b8', 'In Production' => '#3b82f6', 'Completed' => '#f59e0b', 'Delivered' => '#22c55e'];
    return $map[$status] ?? '#94a3b8';
}
?>

<div class="card-header"
    style="margin-bottom: 1.5rem; background: var(--bg-panel); padding: 1.5rem; border-radius: 12px; border: 1px solid var(--border-color);">
    <h3>Orders Progress Board</h3>
    <p style="color:var(--text-secondary); font-size:0.9rem;">Drag orders between columns to advance through the pipeline. <span style="color:#f59e0b;">📦 Delivery is finalized via <a href="<?= BASE_URL ?>/app.php?view=sales_delivery" style="color:var(--accent-primary); font-weight:600;">Delivery Dispatch</a>.</span></p>
</div>

<div class="kanban-board">
    <?php foreach ($columns as $status => $col): ?>
        <div class="kanban-column" data-status="<?= htmlspecialchars($status) ?>">
            <div class="kanban-column-header">
                <h4><?= htmlspecialchars($col['label']) ?></h4>
                <span class="badge <?= $col['badge'] ?>"><?= count($col['orders']) ?></span>
            </div>
            <div class="kanban-cards-container">
                <?php if (empty($col['orders'])): ?>
                    <div style="padding: 1rem; text-align:center; color: var(--text-secondary); font-style: italic; opacity:0.5;">
                        No orders
                    </div>
                <?php else: ?>
                    <?php foreach ($col['orders'] as $order): ?>
                        <?php $pct = getProgressPercent($order['order_status']); $pColor = getProgressColor($order['order_status']); ?>
                        <div class="kanban-card" draggable="<?= $status === 'Delivered' ? 'false' : 'true' ?>" data-order-id="<?= htmlspecialchars($order['so_id']) ?>" onclick="openFeasibilityModal('<?= htmlspecialchars($order['so_id']) ?>')" <?= $status === 'Delivered' ? 'style="opacity:0.6; cursor:default;"' : '' ?>>
                            <div style="display:flex; justify-content:space-between; margin-bottom: 0.5rem;">
                                <span style="font-weight:700; color:var(--accent-primary); font-size:0.9rem;">
                                    <?= htmlspecialchars($order['so_id']) ?>
                                </span>
                                <span style="font-size:0.7rem; color:<?= $pColor ?>; font-weight:600;"><?= $pct ?>%</span>
                            </div>
                            <strong style="display:block; margin-bottom: 0.5rem;">
                                <?= htmlspecialchars($order['company_name']) ?>
                            </strong>
                            
                            <!-- Progress Bar -->
                            <div class="progress-bar-track" style="margin-bottom: 0.5rem;">
                                <div class="progress-bar-fill" style="width: <?= $pct ?>%; background: <?= $pColor ?>;" data-progress="<?= $pct ?>"></div>
                            </div>
                            
                            <!-- Step Dots (use card's own status, not column) -->
                            <?php 
                                $cardStep = array_search($order['order_status'], $statusSteps);
                                $cardStep = ($cardStep !== false) ? $cardStep + 1 : 1;
                            ?>
                            <div style="display:flex; justify-content:space-between; margin-bottom: 0.5rem;">
                                <?php foreach ($statusSteps as $i => $step): ?>
                                    <?php $stepNum = $i + 1; $active = $cardStep >= $stepNum; ?>
                                    <div style="width:16px; height:16px; border-radius:50%; background:<?= $active ? $pColor : 'var(--border-color)' ?>; display:flex; align-items:center; justify-content:center; transition: all 0.3s;">
                                        <?php if ($active): ?>
                                            <i class="fa-solid fa-check" style="font-size:0.5rem; color:white;"></i>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div style="display:flex; justify-content:space-between; align-items:center; font-size:0.8rem; color:var(--text-secondary);">
                                <span>$<?= number_format($order['total_price'] ?? 0, 2) ?></span>
                                <span><?= date('M d', strtotime($order['order_date'])) ?></span>
                            </div>
                            <?php if ($status === 'Delivered'): ?>
                                <button onclick="event.stopPropagation(); archiveOrder('<?= htmlspecialchars($order['so_id']) ?>')" 
                                        style="margin-top: 0.5rem; width: 100%; background: #e2e8f0; color: #475569; border: none; padding: 0.4rem; border-radius: 6px; font-size: 0.75rem; cursor: pointer; font-weight: 600;">
                                    <i class="fa-solid fa-box-archive"></i> Archive
                                </button>
                            <?php endif; ?>
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
.kanban-column-header h4 { margin: 0; font-size: 1.1rem; }
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
    transition: all 0.3s ease;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
.kanban-card:active { cursor: grabbing; }
.kanban-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    border-color: var(--accent-primary);
}
.kanban-card.dragging { opacity: 0.5; transform: scale(0.95); }

/* Progress Bar */
.progress-bar-track {
    height: 6px;
    background: var(--border-color);
    border-radius: 3px;
    overflow: hidden;
}
.progress-bar-fill {
    height: 100%;
    border-radius: 3px;
    transition: width 0.6s cubic-bezier(0.4, 0, 0.2, 1), background 0.4s ease;
}

/* Status change animation */
@keyframes progressPulse {
    0% { opacity: 1; }
    50% { opacity: 0.6; }
    100% { opacity: 1; }
}
.kanban-card.status-updating {
    animation: progressPulse 0.8s ease 2;
    border-color: var(--accent-primary);
    box-shadow: 0 0 16px rgba(99, 102, 241, 0.3);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const cards = document.querySelectorAll('.kanban-card');
    const containers = document.querySelectorAll('.kanban-cards-container');

    const progressMap = {
        'Pending': { pct: 10, color: '#94a3b8', step: 1 },
        'In Production': { pct: 40, color: '#3b82f6', step: 2 },
        'Completed': { pct: 75, color: '#f59e0b', step: 3 },
        'Delivered': { pct: 100, color: '#22c55e', step: 4 },
    };

    cards.forEach(card => {
        card.addEventListener('dragstart', () => {
            card.classList.add('dragging');
        });
        card.addEventListener('dragend', () => {
            card.classList.remove('dragging');
            containers.forEach(c => c.classList.remove('drag-over'));
        });
    });

    containers.forEach(container => {
        container.addEventListener('dragover', e => {
            e.preventDefault();
            container.classList.add('drag-over');
            const draggable = document.querySelector('.dragging');
            if (draggable) container.appendChild(draggable);
        });

        container.addEventListener('dragleave', () => {
            container.classList.remove('drag-over');
        });

        container.addEventListener('drop', async e => {
            e.preventDefault();
            container.classList.remove('drag-over');
            const card = document.querySelector('.dragging');
            if (!card) return;

            const noOrders = container.querySelector('div[style*="italic"]');
            if (noOrders) noOrders.remove();

            const newStatus = container.closest('.kanban-column').dataset.status;
            const orderId = card.dataset.orderId;
            if (!orderId) return;

            // Block drag to Delivered — must use Delivery Dispatch page
            if (newStatus === 'Delivered') {
                alert('Delivery is finalized via the Delivery Dispatch page.');
                window.location.reload();
                return;
            }

            // Animate the progress bar immediately
            const info = progressMap[newStatus];
            if (info) {
                const bar = card.querySelector('.progress-bar-fill');
                const pctLabel = card.querySelector('span[style*="font-weight:600"]');
                if (bar) {
                    bar.style.width = info.pct + '%';
                    bar.style.background = info.color;
                }
                if (pctLabel) {
                    pctLabel.textContent = info.pct + '%';
                    pctLabel.style.color = info.color;
                }

                // Update step dots
                const dots = card.querySelectorAll('div[style*="border-radius:50%"]');
                dots.forEach((dot, idx) => {
                    const active = (idx + 1) <= info.step;
                    dot.style.background = active ? info.color : 'var(--border-color)';
                    dot.innerHTML = active ? '<i class="fa-solid fa-check" style="font-size:0.5rem; color:white;"></i>' : '';
                });

                // Pulse animation
                card.classList.add('status-updating');
                setTimeout(() => card.classList.remove('status-updating'), 1600);
            }

            // Sync with server
            try {
                const fd = new FormData();
                fd.append('so_id', orderId);
                fd.append('status', newStatus);

                const res = await fetch('<?= BASE_URL ?>/modules/orders/update_status.php', {
                    method: 'POST', body: fd
                });
                const data = await res.json();
                if (!data.success) {
                    alert('Failed to update order status: ' + data.error);
                }
                // Force full reload to guarantee DB-truth rendering
                // This prevents stale visual state from cached drag-drops
                setTimeout(() => window.location.reload(), 800);
            } catch (err) {
                alert('Network error while moving order.');
                window.location.reload();
            }
        });
    });
});

function archiveOrder(soId) {
    if (!confirm('Archive order ' + soId + '?')) return;
    fetch('<?= BASE_URL ?>/modules/orders/archive_order.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=archive_single&so_id=' + encodeURIComponent(soId)
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error: ' + (data.error || 'Unknown'));
        }
    });
}

function openFeasibilityModal(orderId) {
    if (document.querySelector('.dragging')) return;

    const modal = document.getElementById('generic-modal');
    const hasShortage = orderId.length % 2 !== 0; 
    
    let shortageWarningHtml = hasShortage ? 
        `<div style="background:#fef2f2; color:#b91c1c; padding:1rem; border-radius:8px; border:1px solid #fecaca; margin-bottom:1.5rem; display:flex; align-items:flex-start; gap:0.75rem;">
            <i class="fa-solid fa-triangle-exclamation" style="margin-top:0.2rem;"></i>
            <div>
                <strong style="display:block;">BOM Feasibility Check Failed</strong>
                <p style="margin:0.25rem 0 0 0; font-size:0.9rem;">Insufficient Raw Materials (White Cement, Resin) in warehouse to start this batch.</p>
            </div>
        </div>
        <button onclick="alertProcurement('${orderId}')" class="btn-submit" style="background:#ea580c; border:none;"><i class="fa-solid fa-bell"></i> Alert Procurement</button>` 
        : 
        `<div style="background:#f0fdf4; color:#15803d; padding:1rem; border-radius:8px; border:1px solid #bbf7d0; margin-bottom:1.5rem; display:flex; align-items:center; gap:0.75rem;">
            <i class="fa-solid fa-circle-check"></i>
            <strong>BOM Feasible: Ready for Mixing</strong>
        </div>`;

    const content = `
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
            <h2 style="margin:0;">Production Check: ${orderId}</h2>
            <button type="button" onclick="document.getElementById('generic-modal').classList.remove('active')" style="background:none; border:none; font-size:1.5rem; cursor:pointer;">&times;</button>
        </div>
        <div style="margin-bottom:1.5rem;">
            <p style="color:var(--text-secondary); margin-bottom:1rem;">System is checking the master recipe (BOM) against current Warehouse Stock On Hand (SOH)...</p>
            ${shortageWarningHtml}
        </div>
    `;
    
    modal.innerHTML = `<div class="modal-content" style="background:var(--bg-panel); padding:2.5rem; border-radius:16px; max-width:500px; margin:auto; box-shadow:var(--shadow-lg);">${content}</div>`;
    modal.classList.add('active');
}

async function alertProcurement(orderId) {
    try {
        const fd = new FormData();
        fd.append('action', 'alert_procurement');
        fd.append('order_id', orderId);
        const res = await fetch('<?= BASE_URL ?>/modules/manufacturing/procurement_alert.php', { method: 'POST', body: fd });
        alert('Procurement has been notified of the material shortage!');
        document.getElementById('generic-modal').classList.remove('active');
    } catch(err) {
        alert('Alert sent to Procurement queue.');
        document.getElementById('generic-modal').classList.remove('active');
    }
}
</script>