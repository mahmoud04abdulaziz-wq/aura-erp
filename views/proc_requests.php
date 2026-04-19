<?php
/** MiskStone ERP — Incoming Material Requests (from Production) */
$requests = $pdo->query("
    SELECT mr.*, im.item_name, im.base_uom, u.email as requester_email
    FROM material_requests mr
    JOIN item_master im ON mr.item_id = im.item_id
    JOIN users u ON mr.requested_by = u.user_id
    ORDER BY 
        CASE mr.status WHEN 'Requested' THEN 0 ELSE 1 END,
        CASE mr.urgency WHEN 'Critical' THEN 0 WHEN 'High' THEN 1 ELSE 2 END,
        mr.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

$suppliers = $pdo->query("SELECT supplier_id, supplier_name FROM suppliers ORDER BY supplier_name")->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="dashboard-container">
    <div class="welcome-banner" style="background: linear-gradient(135deg, #1e293b, #ef4444); color: white; padding: 2rem; border-radius: 16px; margin-bottom: 2rem;">
        <h1><i class="fa-solid fa-envelope-open-text" style="margin-right: 0.75rem;"></i>Incoming Material Requests</h1>
        <p style="opacity: 0.9;">Requests from Production. Accept to create a Purchase Order, or Decline with a reason.</p>
    </div>
    <?php if (empty($requests)): ?>
        <div class="card" style="text-align: center; padding: 4rem;">
            <i class="fa-solid fa-inbox" style="font-size: 3rem; color: var(--text-secondary); opacity: 0.3; margin-bottom: 1rem;"></i>
            <h3 style="color: var(--text-secondary);">No incoming requests</h3>
        </div>
    <?php else: ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(400px, 1fr)); gap: 1.5rem;">
            <?php foreach ($requests as $req): ?>
                <?php
                $urgencyColors = ['Normal' => '#22c55e', 'High' => '#f59e0b', 'Critical' => '#ef4444'];
                $borderColor = $urgencyColors[$req['urgency']] ?? '#94a3b8';
                $isPending = $req['status'] === 'Requested';
                ?>
                <div class="card" id="req-card-<?= $req['request_id'] ?>" style="border-left: 4px solid <?= $borderColor ?>; padding: 0; overflow: hidden; <?= !$isPending ? 'opacity: 0.7;' : '' ?>">
                    <div style="padding: 1.25rem;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                            <span style="font-weight: 700; font-size: 1rem;">#<?= $req['request_id'] ?> — <?= htmlspecialchars($req['item_name']) ?></span>
                            <span class="badge" style="background: <?= $borderColor ?>20; color: <?= $borderColor ?>; font-weight: 700;"><?= $req['urgency'] ?></span>
                        </div>
                        <div style="display: flex; gap: 1rem; font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 0.75rem;">
                            <span><i class="fa-solid fa-cubes"></i> <?= number_format($req['quantity_requested']) ?> <?= $req['base_uom'] ?></span>
                            <span><i class="fa-solid fa-dollar-sign"></i> $<?= number_format($req['unit_price'], 2) ?>/unit</span>
                            <span><i class="fa-solid fa-user"></i> <?= htmlspecialchars($req['requester_email']) ?></span>
                        </div>
                        <?php if ($req['reason']): ?>
                            <p style="font-size: 0.85rem; color: #334155; background: #f8fafc; padding: 0.5rem 0.75rem; border-radius: 6px; margin-bottom: 0;">"<?= htmlspecialchars($req['reason']) ?>"</p>
                        <?php endif; ?>
                        <?php if ($req['status'] !== 'Requested'): ?>
                            <p style="margin-top: 0.5rem; font-weight: 600; color: <?= $req['status'] === 'Accepted' ? '#22c55e' : '#ef4444' ?>;">
                                <?= $req['status'] ?> <?= $req['po_id'] ? "(PO: {$req['po_id']})" : '' ?>
                                <?= $req['decline_reason'] ? " — {$req['decline_reason']}" : '' ?>
                            </p>
                        <?php endif; ?>
                    </div>
                    <?php if ($isPending): ?>
                        <div style="padding: 1rem; background: #f8fafc; border-top: 1px solid var(--border-color); display: flex; gap: 0.5rem;">
                            <select id="supplier-<?= $req['request_id'] ?>" style="flex: 1; padding: 0.5rem; border: 1px solid var(--border-color); border-radius: 6px; font-size: 0.85rem;">
                                <?php foreach ($suppliers as $s): ?>
                                    <option value="<?= $s['supplier_id'] ?>"><?= htmlspecialchars($s['supplier_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button onclick="handleRequest(<?= $req['request_id'] ?>, 'accept')" style="background: #22c55e; color: white; border: none; padding: 0.5rem 1rem; border-radius: 6px; font-weight: 700; cursor: pointer; font-size: 0.85rem;">
                                <i class="fa-solid fa-check"></i> Accept
                            </button>
                            <button onclick="handleRequest(<?= $req['request_id'] ?>, 'decline')" style="background: #fee2e2; color: #991b1b; border: none; padding: 0.5rem 1rem; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 0.85rem;">
                                <i class="fa-solid fa-times"></i> Decline
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<script>
function handleRequest(reqId, action) {
    let body = 'request_id=' + reqId + '&action=' + action;
    if (action === 'accept') {
        const supplier = document.getElementById('supplier-' + reqId).value;
        body += '&supplier_id=' + supplier;
    }
    if (action === 'decline') {
        const reason = prompt('Reason for declining?');
        if (!reason) return;
        body += '&decline_reason=' + encodeURIComponent(reason);
    }
    fetch('<?= BASE_URL ?>/modules/procurement/handle_request.php', { method: 'POST', headers: {'Content-Type':'application/x-www-form-urlencoded'}, body: body })
        .then(r => r.json()).then(data => {
            if (data.success) location.reload();
            else alert('Error: ' + (data.error || 'Unknown'));
        });
}
</script>
