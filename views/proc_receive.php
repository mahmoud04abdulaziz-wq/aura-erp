<?php
/** MiskStone ERP — Receive Purchase Orders */
$pendingPOs = $pdo->query("
    SELECT po.*, s.supplier_name, im.item_name 
    FROM purchase_orders po 
    JOIN suppliers s ON po.supplier_id = s.supplier_id 
    LEFT JOIN item_master im ON po.item_id = im.item_id
    WHERE po.order_status = 'Pending'
    ORDER BY po.order_date ASC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="dashboard-container">
    <div class="welcome-banner" style="background: linear-gradient(135deg, #1e293b, #7c3aed); color: white; padding: 2rem; border-radius: 16px; margin-bottom: 2rem;">
        <h1><i class="fa-solid fa-box-open" style="margin-right: 0.75rem;"></i>Receive Orders</h1>
        <p style="opacity: 0.9;">Mark purchase orders as received. This adds the items to raw material inventory.</p>
    </div>
    <?php if (empty($pendingPOs)): ?>
        <div class="card" style="text-align: center; padding: 4rem;">
            <i class="fa-solid fa-box-open" style="font-size: 3rem; color: var(--text-secondary); opacity: 0.3; margin-bottom: 1rem;"></i>
            <h3 style="color: var(--text-secondary);">No pending deliveries</h3>
            <p style="color: var(--text-secondary); opacity: 0.7;">All purchase orders have been received.</p>
        </div>
    <?php else: ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(400px, 1fr)); gap: 1.5rem;">
            <?php foreach ($pendingPOs as $po): ?>
                <div class="card" id="po-card-<?= $po['po_id'] ?>" style="border-left: 4px solid #f59e0b; padding: 0; overflow: hidden;">
                    <div style="padding: 1.25rem;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                            <h3 style="margin: 0; font-size: 1rem; font-family: monospace;"><?= htmlspecialchars($po['po_id']) ?></h3>
                            <span class="badge pending"><?= $po['order_status'] ?></span>
                        </div>
                        <p style="color: var(--text-secondary); font-size: 0.85rem; margin-bottom: 0.5rem;"><i class="fa-solid fa-building"></i> <?= htmlspecialchars($po['supplier_name']) ?></p>
                        <p style="font-weight: 600; margin-bottom: 0.5rem;"><?= htmlspecialchars($po['item_name'] ?? 'General Materials') ?> — <?= $po['requested_quantity'] ? number_format($po['requested_quantity']) . ' units' : '' ?></p>
                        <p style="font-weight: 700; color: var(--accent-primary); font-size: 1.1rem;"><?= $po['currency'] ?> <?= number_format($po['total_amount'], 2) ?></p>
                    </div>
                    <div style="padding: 1rem; background: #f8fafc; border-top: 1px solid var(--border-color);">
                        <button onclick="receivePO('<?= $po['po_id'] ?>')" id="rcv-<?= $po['po_id'] ?>" style="width: 100%; background: #7c3aed; color: white; border: none; padding: 0.7rem; border-radius: 8px; font-weight: 700; cursor: pointer; font-size: 0.9rem;">
                            <i class="fa-solid fa-box-open"></i> Mark as Received
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<script>
function receivePO(poId) {
    const btn = document.getElementById('rcv-' + poId);
    btn.disabled = true; btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Processing...';
    fetch('<?= BASE_URL ?>/modules/procurement/receive_po.php', { method: 'POST', headers: {'Content-Type':'application/x-www-form-urlencoded'}, body: 'po_id=' + encodeURIComponent(poId) })
        .then(r => r.json()).then(data => {
            if (data.success) {
                const card = document.getElementById('po-card-' + poId);
                card.style.opacity = '0'; card.style.transform = 'scale(0.95)';
                setTimeout(() => card.remove(), 400);
            } else { alert('Error: ' + (data.error || 'Unknown')); btn.disabled = false; btn.innerHTML = '<i class="fa-solid fa-box-open"></i> Mark as Received'; }
        });
}
</script>
