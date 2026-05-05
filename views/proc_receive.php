<?php
/**
 * MiskStone ERP — Receive Orders (Multi-Line PO Support)
 * Shows pending POs with their line items. 
 * On receipt: credits inventory for ALL lines + records expense in Finance.
 */

// Fetch pending POs with supplier info
$pendingPOs = $pdo->query("
    SELECT po.*, s.supplier_name
    FROM purchase_orders po
    JOIN suppliers s ON po.supplier_id = s.supplier_id
    WHERE po.order_status = 'Pending'
    ORDER BY po.order_date DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Fetch po_lines for each PO
$poLines = [];
if (!empty($pendingPOs)) {
    $poIds = array_column($pendingPOs, 'po_id');
    $placeholders = implode(',', array_fill(0, count($poIds), '?'));
    $linesStmt = $pdo->prepare("
        SELECT pl.*, im.item_name, im.base_uom 
        FROM po_lines pl
        JOIN item_master im ON pl.item_id = im.item_id
        WHERE pl.po_id IN ({$placeholders})
        ORDER BY pl.line_id
    ");
    $linesStmt->execute($poIds);
    foreach ($linesStmt->fetchAll(PDO::FETCH_ASSOC) as $line) {
        $poLines[$line['po_id']][] = $line;
    }
}
?>
<div class="dashboard-container">
    <div class="welcome-banner" style="background: linear-gradient(135deg, #1e293b, #7c3aed); color: white; padding: 2rem; border-radius: 16px; margin-bottom: 2rem;">
        <h1><i class="fa-solid fa-box-open" style="margin-right: 0.75rem;"></i>Receive Orders</h1>
        <p style="opacity: 0.9;">Mark purchase orders as received. This adds materials to inventory and records the expense in Finance.</p>
    </div>

    <?php if (empty($pendingPOs)): ?>
        <div class="card" style="text-align: center; padding: 4rem;">
            <i class="fa-solid fa-truck-ramp-box" style="font-size: 3rem; color: var(--text-secondary); opacity: 0.3; margin-bottom: 1rem; display: block;"></i>
            <h3 style="color: var(--text-secondary);">No pending purchase orders</h3>
            <p style="color: var(--text-secondary); font-size: 0.9rem;">All orders have been received or cancelled.</p>
        </div>
    <?php else: ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(420px, 1fr)); gap: 1.5rem;">
            <?php foreach ($pendingPOs as $po): ?>
                <?php $lines = $poLines[$po['po_id']] ?? []; ?>
                <div class="card" id="po-card-<?= $po['po_id'] ?>" style="padding: 0; overflow: hidden; border-left: 4px solid #f59e0b;">
                    <!-- PO Header -->
                    <div style="padding: 1.25rem;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                            <span style="font-weight: 800; font-family: monospace;"><?= htmlspecialchars($po['po_id']) ?></span>
                            <span class="badge in-progress">Pending</span>
                        </div>
                        <div style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 0.75rem;">
                            <i class="fa-solid fa-building"></i> <?= htmlspecialchars($po['supplier_name']) ?>
                            <span style="opacity: 0.5; margin: 0 0.5rem;">·</span>
                            <i class="fa-solid fa-calendar"></i> <?= htmlspecialchars($po['order_date']) ?>
                        </div>

                        <!-- Line Items -->
                        <?php if (!empty($lines)): ?>
                            <div style="background: #f8fafc; border-radius: 8px; padding: 0.75rem; margin-bottom: 0.75rem;">
                                <div style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: var(--text-secondary); margin-bottom: 0.5rem;">Materials (<?= count($lines) ?> items)</div>
                                <?php foreach ($lines as $line): ?>
                                    <div style="display: flex; justify-content: space-between; font-size: 0.85rem; padding: 0.25rem 0; border-bottom: 1px solid #e2e8f0;">
                                        <span style="font-weight: 600;"><?= htmlspecialchars($line['item_name']) ?></span>
                                        <span style="color: var(--text-secondary);"><?= number_format($line['quantity'], 1) ?> <?= $line['base_uom'] ?> × <?= number_format($line['unit_price'], 2) ?> JOD</span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <!-- Legacy single-item PO -->
                            <?php if (!empty($po['item_id'])): ?>
                                <?php 
                                    $itemStmt = $pdo->prepare("SELECT item_name FROM item_master WHERE item_id = ?");
                                    $itemStmt->execute([$po['item_id']]);
                                    $itemName = $itemStmt->fetchColumn() ?: $po['item_id'];
                                ?>
                                <div style="background: #f8fafc; border-radius: 8px; padding: 0.75rem; margin-bottom: 0.75rem;">
                                    <span style="font-weight: 600; font-size: 0.85rem;"><?= htmlspecialchars($itemName) ?></span>
                                    <span style="color: var(--text-secondary); font-size: 0.85rem;"> — <?= number_format($po['requested_quantity'], 1) ?> units</span>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>

                        <div style="font-weight: 700; font-size: 1.1rem; color: var(--accent-primary);">
                            <?= $po['currency'] ?: 'JOD' ?> <?= number_format($po['total_amount'], 2) ?>
                        </div>
                    </div>

                    <!-- Receive Button -->
                    <div style="padding: 1rem; background: #f8fafc; border-top: 1px solid var(--border-color);">
                        <button onclick="receivePO('<?= htmlspecialchars($po['po_id']) ?>')" 
                                style="width: 100%; background: #22c55e; color: white; border: none; padding: 0.7rem; border-radius: 8px; font-weight: 700; cursor: pointer; font-size: 0.9rem;">
                            <i class="fa-solid fa-truck-ramp-box"></i> Mark as Received
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
function receivePO(poId) {
    if (!confirm('Mark PO ' + poId + ' as received? This will:\n• Add all materials to inventory\n• Record expense in Finance')) return;

    fetch('<?= BASE_URL ?>/modules/procurement/receive_po.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'po_id=' + encodeURIComponent(poId)
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert(data.message || 'PO received!');
            location.reload();
        } else {
            alert('Error: ' + (data.error || 'Unknown'));
        }
    });
}
</script>
