<?php
/** MiskStone ERP — Track Purchase Orders (Multi-Line) */
$orders = $pdo->query("
    SELECT po.*, s.supplier_name
    FROM purchase_orders po
    JOIN suppliers s ON po.supplier_id = s.supplier_id
    ORDER BY po.order_date DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Get po_lines
$poLines = [];
$lineStmt = $pdo->query("
    SELECT pl.*, im.item_name, im.base_uom 
    FROM po_lines pl
    JOIN item_master im ON pl.item_id = im.item_id
    ORDER BY pl.line_id
");
foreach ($lineStmt->fetchAll(PDO::FETCH_ASSOC) as $line) {
    $poLines[$line['po_id']][] = $line;
}

$stats = [
    'pending'  => count(array_filter($orders, fn($o) => $o['order_status'] === 'Pending')),
    'received' => count(array_filter($orders, fn($o) => $o['order_status'] === 'Received')),
    'total'    => count($orders),
];
?>

<div class="dashboard-container">
    <div class="welcome-banner" style="background: linear-gradient(135deg, #1e293b, #f59e0b); color: white; padding: 2rem; border-radius: 16px; margin-bottom: 2rem;">
        <h1><i class="fa-solid fa-list-check" style="margin-right: 0.75rem;"></i>Track Orders</h1>
        <p style="opacity: 0.9;">Monitor all purchase orders with detailed line items.</p>
    </div>

    <div class="stats-grid" style="margin-bottom: 2rem; display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem;">
        <div class="stat-card" style="border-left: 4px solid #f59e0b;">
            <div class="stat-info"><h3 style="color: var(--text-secondary); font-size: 0.85rem;">Pending</h3><p class="stat-value"><?= $stats['pending'] ?></p></div>
        </div>
        <div class="stat-card" style="border-left: 4px solid #22c55e;">
            <div class="stat-info"><h3 style="color: var(--text-secondary); font-size: 0.85rem;">Received</h3><p class="stat-value"><?= $stats['received'] ?></p></div>
        </div>
        <div class="stat-card" style="border-left: 4px solid var(--accent-primary);">
            <div class="stat-info"><h3 style="color: var(--text-secondary); font-size: 0.85rem;">Total POs</h3><p class="stat-value"><?= $stats['total'] ?></p></div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(400px, 1fr)); gap: 1.25rem;">
        <?php foreach ($orders as $po): ?>
            <?php 
            $lines = $poLines[$po['po_id']] ?? [];
            $statusColor = $po['order_status'] === 'Received' ? '#22c55e' : ($po['order_status'] === 'Cancelled' ? '#ef4444' : '#f59e0b');
            ?>
            <div class="card" style="padding: 0; overflow: hidden; border-left: 4px solid <?= $statusColor ?>; <?= $po['order_status'] === 'Received' ? 'opacity:0.7;' : '' ?>">
                <div style="padding: 1.25rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                        <span style="font-weight: 800; font-family: monospace;"><?= htmlspecialchars($po['po_id']) ?></span>
                        <span style="font-size: 0.75rem; padding: 0.2rem 0.5rem; border-radius: 4px; background: <?= $statusColor ?>20; color: <?= $statusColor ?>; font-weight: 700;">
                            <?= $po['order_status'] ?>
                        </span>
                    </div>

                    <div style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 0.75rem;">
                        <i class="fa-solid fa-building"></i> <?= htmlspecialchars($po['supplier_name']) ?>
                        <span style="opacity:0.5; margin: 0 0.3rem;">·</span>
                        <i class="fa-solid fa-calendar"></i> <?= htmlspecialchars($po['order_date']) ?>
                    </div>

                    <!-- Line Items -->
                    <?php if (!empty($lines)): ?>
                        <div style="background: #f8fafc; border-radius: 8px; padding: 0.5rem 0.75rem; margin-bottom: 0.5rem;">
                            <?php foreach ($lines as $line): ?>
                                <div style="display: flex; justify-content: space-between; font-size: 0.8rem; padding: 0.2rem 0; border-bottom: 1px solid #e2e8f0;">
                                    <span style="font-weight: 600;"><?= htmlspecialchars($line['item_name']) ?></span>
                                    <span><?= number_format($line['quantity'], 1) ?> <?= $line['base_uom'] ?> · <?= number_format($line['line_total'] ?? ($line['quantity'] * $line['unit_price']), 2) ?> JOD</span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php elseif ($po['item_id']): ?>
                        <div style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 0.5rem;">
                            Legacy: <?= htmlspecialchars($po['item_id']) ?> × <?= number_format($po['requested_quantity'], 1) ?>
                        </div>
                    <?php endif; ?>

                    <div style="font-weight: 700; font-size: 1.1rem; color: var(--accent-primary);">
                        <?= $po['currency'] ?: 'JOD' ?> <?= number_format($po['total_amount'], 2) ?>
                        <?php if (!empty($lines)): ?>
                            <span style="font-size: 0.75rem; font-weight: 400; color: var(--text-secondary); margin-left: 0.5rem;">(<?= count($lines) ?> items)</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
