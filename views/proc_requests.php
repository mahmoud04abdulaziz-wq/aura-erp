<?php
/** MiskStone ERP — Incoming Material Requests (from Production)
 *  Grouped by batch reference. Supports individual and bulk accept.
 */
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

// Group pending requests by batch reference (extracted from reason)
$pendingRequests = array_filter($requests, fn($r) => $r['status'] === 'Requested');
$resolvedRequests = array_filter($requests, fn($r) => $r['status'] !== 'Requested');

// Group by batch reference
$groups = [];
foreach ($pendingRequests as $req) {
    // Extract batch ID from reason if present
    if (preg_match('/batch (MX-[\w-]+|BATCH-[\w-]+)/i', $req['reason'] ?? '', $m)) {
        $groupKey = $m[1];
    } else {
        $groupKey = 'Ungrouped';
    }
    $groups[$groupKey][] = $req;
}

$pendingIds = array_column($pendingRequests, 'request_id');
?>

<div class="dashboard-container">
    <div class="welcome-banner" style="background: linear-gradient(135deg, #1e293b, #ef4444); color: white; padding: 2rem; border-radius: 16px; margin-bottom: 2rem;">
        <h1><i class="fa-solid fa-envelope-open-text" style="margin-right: 0.75rem;"></i>Incoming Material Requests</h1>
        <p style="opacity: 0.9;">Review requests from Production. Accept individually or in bulk to auto-create Purchase Orders.</p>
    </div>

    <?php if (!empty($pendingRequests)): ?>
        <!-- Bulk Action Bar -->
        <div class="card" style="padding: 1rem; margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center;">
            <span style="font-weight: 600; color: var(--text-primary);">
                <i class="fa-solid fa-inbox" style="color: var(--accent-primary);"></i>
                <?= count($pendingRequests) ?> pending request(s) in <?= count($groups) ?> group(s)
            </span>
            <button onclick="acceptAllRequests()" style="background: #22c55e; color: white; border: none; padding: 0.6rem 1.2rem; border-radius: 8px; font-weight: 700; cursor: pointer; font-size: 0.85rem;">
                <i class="fa-solid fa-check-double"></i> Accept All & Create PO(s)
            </button>
        </div>
    <?php endif; ?>

    <?php if (empty($pendingRequests) && empty($resolvedRequests)): ?>
        <div class="card" style="text-align: center; padding: 4rem;">
            <i class="fa-solid fa-inbox" style="font-size: 3rem; color: var(--text-secondary); opacity: 0.3; margin-bottom: 1rem; display: block;"></i>
            <h3 style="color: var(--text-secondary);">No incoming requests</h3>
        </div>
    <?php endif; ?>

    <!-- Grouped Pending Requests -->
    <?php foreach ($groups as $groupKey => $groupRequests): ?>
        <div class="card" style="margin-bottom: 1.5rem; padding: 0; overflow: hidden; border-left: 4px solid #f59e0b;">
            <div style="padding: 1rem 1.25rem; display: flex; justify-content: space-between; align-items: center; background: #fffbeb;">
                <div>
                    <span style="font-weight: 700; font-size: 1rem;">
                        <?php if ($groupKey !== 'Ungrouped'): ?>
                            <i class="fa-solid fa-industry" style="color: #f59e0b;"></i> Batch: <?= htmlspecialchars($groupKey) ?>
                        <?php else: ?>
                            <i class="fa-solid fa-layer-group" style="color: #94a3b8;"></i> Individual Requests
                        <?php endif; ?>
                    </span>
                    <span class="badge in-progress" style="margin-left: 0.5rem;"><?= count($groupRequests) ?> items</span>
                </div>
                <?php
                    $groupIds = array_column($groupRequests, 'request_id');
                    $groupIdsStr = implode(',', $groupIds);
                ?>
                <button onclick="acceptBulk('<?= $groupIdsStr ?>')" style="background: #22c55e; color: white; border: none; padding: 0.4rem 0.8rem; border-radius: 6px; font-weight: 700; cursor: pointer; font-size: 0.8rem;">
                    <i class="fa-solid fa-check"></i> Accept Group
                </button>
            </div>

            <div style="padding: 0.5rem 1.25rem 1rem;">
                <?php foreach ($groupRequests as $req): ?>
                    <?php $urgencyColors = ['Normal' => '#22c55e', 'High' => '#f59e0b', 'Critical' => '#ef4444']; ?>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem 0; border-bottom: 1px solid #f1f5f9;">
                        <div style="flex: 1;">
                            <span style="font-weight: 700;"><?= htmlspecialchars($req['item_name']) ?></span>
                            <span class="badge" style="margin-left: 0.5rem; background: <?= $urgencyColors[$req['urgency']] ?? '#94a3b8' ?>20; color: <?= $urgencyColors[$req['urgency']] ?? '#94a3b8' ?>; font-weight: 700; font-size: 0.7rem;"><?= $req['urgency'] ?></span>
                        </div>
                        <div style="display: flex; gap: 1rem; align-items: center; font-size: 0.85rem; color: var(--text-secondary);">
                            <span><strong><?= number_format($req['quantity_requested'], 1) ?></strong> <?= $req['base_uom'] ?></span>
                            <span>$<?= number_format($req['unit_price'], 2) ?>/unit</span>
                            <div style="display: flex; gap: 0.25rem;">
                                <select id="supplier-<?= $req['request_id'] ?>" style="padding: 0.3rem; border: 1px solid var(--border-color); border-radius: 4px; font-size: 0.8rem;">
                                    <?php foreach ($suppliers as $s): ?>
                                        <option value="<?= $s['supplier_id'] ?>"><?= htmlspecialchars($s['supplier_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button onclick="handleRequest(<?= $req['request_id'] ?>, 'accept')" style="background: #22c55e; color: white; border: none; padding: 0.3rem 0.5rem; border-radius: 4px; cursor: pointer; font-size: 0.75rem;"><i class="fa-solid fa-check"></i></button>
                                <button onclick="handleRequest(<?= $req['request_id'] ?>, 'decline')" style="background: #fee2e2; color: #991b1b; border: none; padding: 0.3rem 0.5rem; border-radius: 4px; cursor: pointer; font-size: 0.75rem;"><i class="fa-solid fa-times"></i></button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>

    <!-- Resolved Requests -->
    <?php if (!empty($resolvedRequests)): ?>
        <div class="card" style="margin-top: 1rem;">
            <div class="card-header"><h3>Resolved Requests</h3></div>
            <table class="data-table" style="font-size: 0.85rem;">
                <thead><tr><th>ID</th><th>Material</th><th>Qty</th><th>Status</th><th>PO</th></tr></thead>
                <tbody>
                    <?php foreach ($resolvedRequests as $req): ?>
                        <tr style="opacity: 0.7;">
                            <td style="font-family: monospace;">#<?= $req['request_id'] ?></td>
                            <td style="font-weight: 600;"><?= htmlspecialchars($req['item_name']) ?></td>
                            <td><?= number_format($req['quantity_requested'], 1) ?> <?= $req['base_uom'] ?></td>
                            <td><span class="badge <?= $req['status'] === 'Accepted' ? 'completed' : 'pending' ?>"><?= $req['status'] ?></span></td>
                            <td style="font-family: monospace;"><?= $req['po_id'] ?: '—' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script>
const allPendingIds = [<?= implode(',', $pendingIds) ?>];

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

function acceptBulk(idsStr) {
    if (!confirm('Accept all requests in this group and auto-create PO(s)?')) return;
    fetch('<?= BASE_URL ?>/modules/procurement/handle_request.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: 'action=accept_bulk&request_ids=' + encodeURIComponent(idsStr)
    })
    .then(r => r.json()).then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else alert('Error: ' + (data.error || 'Unknown'));
    });
}

function acceptAllRequests() {
    if (!confirm('Accept ALL pending requests and auto-create PO(s) grouped by supplier?')) return;
    acceptBulk(allPendingIds.join(','));
}
</script>
