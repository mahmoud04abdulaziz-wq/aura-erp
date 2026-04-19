<?php
/**
 * MiskStone ERP — Production Material Requests
 * Production users can request raw materials from Procurement.
 */

// Fetch raw materials for the form
$rawMaterials = $pdo->query("SELECT item_id, item_name, standard_cost, base_uom FROM item_master WHERE category = 'Raw Material' ORDER BY item_name")->fetchAll(PDO::FETCH_ASSOC);

// Fetch this user's existing requests
$myRequests = $pdo->query("
    SELECT mr.*, im.item_name, im.base_uom,
           u.email as handler_email
    FROM material_requests mr
    JOIN item_master im ON mr.item_id = im.item_id
    LEFT JOIN users u ON mr.handled_by = u.user_id
    ORDER BY mr.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="dashboard-container">
    <div class="welcome-banner" style="background: linear-gradient(135deg, #1e293b, #f59e0b); color: white; padding: 2rem; border-radius: 16px; margin-bottom: 2rem;">
        <h1 style="margin-bottom: 0.5rem;"><i class="fa-solid fa-boxes-packing" style="margin-right: 0.75rem;"></i>Request Materials</h1>
        <p style="opacity: 0.9;">Request raw materials from Procurement. They will review and create a Purchase Order.</p>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 1.5rem;">

        <!-- Request Form -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fa-solid fa-file-circle-plus" style="color: var(--accent-primary); margin-right: 0.5rem;"></i>New Request</h3>
            </div>
            <form id="requestForm" style="display: flex; flex-direction: column; gap: 1rem;">
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.25rem; color: var(--text-secondary);">Raw Material</label>
                    <select name="item_id" id="item_select" required style="width: 100%; padding: 0.6rem; border: 1px solid var(--border-color); border-radius: 8px; font-size: 0.9rem;">
                        <option value="">Select material...</option>
                        <?php foreach ($rawMaterials as $rm): ?>
                            <option value="<?= $rm['item_id'] ?>" data-cost="<?= $rm['standard_cost'] ?>" data-uom="<?= $rm['base_uom'] ?>">
                                <?= htmlspecialchars($rm['item_name']) ?> (<?= $rm['base_uom'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.25rem; color: var(--text-secondary);">Quantity Needed</label>
                    <input type="number" name="quantity" id="qty_input" step="0.001" min="1" required style="width: 100%; padding: 0.6rem; border: 1px solid var(--border-color); border-radius: 8px; font-size: 0.9rem;">
                </div>

                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.25rem; color: var(--text-secondary);">Unit Price ($)</label>
                    <input type="number" name="unit_price" id="price_input" step="0.01" min="0" style="width: 100%; padding: 0.6rem; border: 1px solid var(--border-color); border-radius: 8px; font-size: 0.9rem;">
                </div>

                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.25rem; color: var(--text-secondary);">Urgency</label>
                    <select name="urgency" style="width: 100%; padding: 0.6rem; border: 1px solid var(--border-color); border-radius: 8px; font-size: 0.9rem;">
                        <option value="Normal">Normal</option>
                        <option value="High">High</option>
                        <option value="Critical">Critical</option>
                    </select>
                </div>

                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.25rem; color: var(--text-secondary);">Reason / Batch Reference</label>
                    <textarea name="reason" rows="2" placeholder="e.g., For batch MX-20260418-001" style="width: 100%; padding: 0.6rem; border: 1px solid var(--border-color); border-radius: 8px; font-size: 0.9rem; resize: vertical;"></textarea>
                </div>

                <button type="submit" id="submitBtn" style="background: var(--accent-primary); color: white; border: none; padding: 0.75rem; border-radius: 10px; font-weight: 700; cursor: pointer; font-size: 0.95rem;">
                    <i class="fa-solid fa-paper-plane"></i> Submit Request
                </button>
            </form>
        </div>

        <!-- Existing Requests -->
        <div class="card">
            <div class="card-header">
                <h3>My Material Requests</h3>
                <span class="badge in-progress"><?= count($myRequests) ?> total</span>
            </div>
            <div style="overflow-x: auto;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Material</th>
                            <th>Qty</th>
                            <th>Unit Price</th>
                            <th>Urgency</th>
                            <th>Status</th>
                            <th>Reason</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($myRequests)): ?>
                            <tr><td colspan="7" style="text-align: center; padding: 2rem; color: var(--text-secondary);">No requests submitted yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($myRequests as $req): ?>
                                <tr>
                                    <td style="font-family: monospace; font-weight: 600;">#<?= $req['request_id'] ?></td>
                                    <td style="font-weight: 600;"><?= htmlspecialchars($req['item_name']) ?></td>
                                    <td><?= number_format($req['quantity_requested'], 0) ?> <?= $req['base_uom'] ?></td>
                                    <td>$<?= number_format($req['unit_price'], 2) ?></td>
                                    <td>
                                        <?php 
                                        $urgencyColors = ['Normal' => 'completed', 'High' => 'in-progress', 'Critical' => 'pending'];
                                        ?>
                                        <span class="badge <?= $urgencyColors[$req['urgency']] ?? 'gray' ?>"><?= $req['urgency'] ?></span>
                                    </td>
                                    <td>
                                        <?php
                                        $statusColors = ['Requested' => 'in-progress', 'Accepted' => 'completed', 'Declined' => 'pending'];
                                        ?>
                                        <span class="badge <?= $statusColors[$req['status']] ?? 'gray' ?>"><?= $req['status'] ?></span>
                                        <?php if ($req['status'] === 'Accepted' && $req['po_id']): ?>
                                            <span style="font-size: 0.75rem; color: var(--text-secondary); display: block;">PO: <?= htmlspecialchars($req['po_id']) ?></span>
                                        <?php endif; ?>
                                        <?php if ($req['status'] === 'Declined' && $req['decline_reason']): ?>
                                            <span style="font-size: 0.75rem; color: #ef4444; display: block;"><?= htmlspecialchars($req['decline_reason']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="font-size: 0.85rem; color: var(--text-secondary); max-width: 200px; overflow: hidden; text-overflow: ellipsis;"><?= htmlspecialchars($req['reason'] ?? '—') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('item_select').addEventListener('change', function() {
    const opt = this.options[this.selectedIndex];
    const cost = opt.dataset.cost || 0;
    document.getElementById('price_input').value = cost;
});

document.getElementById('requestForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = document.getElementById('submitBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Submitting...';

    const formData = new FormData(this);

    fetch('<?= BASE_URL ?>/modules/production/request_materials.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error: ' + (data.error || 'Unknown'));
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-paper-plane"></i> Submit Request';
        }
    })
    .catch(() => {
        alert('Network error');
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-paper-plane"></i> Submit Request';
    });
});
</script>
