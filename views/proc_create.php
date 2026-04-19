<?php
/** MiskStone ERP — Create Purchase Order */
$suppliers = $pdo->query("SELECT supplier_id, supplier_name FROM suppliers ORDER BY supplier_name")->fetchAll(PDO::FETCH_ASSOC);
$rawMaterials = $pdo->query("SELECT item_id, item_name, standard_cost, base_uom FROM item_master WHERE category = 'Raw Material' ORDER BY item_name")->fetchAll(PDO::FETCH_ASSOC);

// Pre-fill from query params (from ROP alerts or material requests)
$prefillItem = $_GET['item'] ?? '';
$prefillQty = $_GET['qty'] ?? '';
?>
<div class="dashboard-container">
    <div class="welcome-banner" style="background: linear-gradient(135deg, #1e293b, #22c55e); color: white; padding: 2rem; border-radius: 16px; margin-bottom: 2rem;">
        <h1><i class="fa-solid fa-file-circle-plus" style="margin-right: 0.75rem;"></i>Create Purchase Order</h1>
        <p style="opacity: 0.9;">Select a supplier and raw material to create a new PO.</p>
    </div>
    <div class="card" style="max-width: 700px;">
        <form id="createPoForm" style="display: flex; flex-direction: column; gap: 1.25rem;">
            <div>
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.25rem; color: var(--text-secondary);">Supplier</label>
                <select name="supplier_id" required style="width: 100%; padding: 0.7rem; border: 1px solid var(--border-color); border-radius: 8px; font-size: 0.95rem;">
                    <option value="">Select supplier...</option>
                    <?php foreach ($suppliers as $s): ?>
                        <option value="<?= $s['supplier_id'] ?>"><?= htmlspecialchars($s['supplier_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.25rem; color: var(--text-secondary);">Raw Material</label>
                <select name="item_id" id="po_item" required style="width: 100%; padding: 0.7rem; border: 1px solid var(--border-color); border-radius: 8px; font-size: 0.95rem;">
                    <option value="">Select material...</option>
                    <?php foreach ($rawMaterials as $rm): ?>
                        <option value="<?= $rm['item_id'] ?>" data-cost="<?= $rm['standard_cost'] ?>" <?= $prefillItem === $rm['item_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($rm['item_name']) ?> (<?= $rm['base_uom'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.25rem; color: var(--text-secondary);">Quantity</label>
                    <input type="number" name="requested_quantity" id="po_qty" step="0.001" min="1" required value="<?= htmlspecialchars($prefillQty) ?>" style="width: 100%; padding: 0.7rem; border: 1px solid var(--border-color); border-radius: 8px; font-size: 0.95rem;">
                </div>
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.25rem; color: var(--text-secondary);">Estimated Total</label>
                    <input type="text" id="po_total" readonly style="width: 100%; padding: 0.7rem; border: 1px solid var(--border-color); border-radius: 8px; font-size: 0.95rem; background: #f8fafc; font-weight: 700; color: var(--accent-primary);" value="$0.00">
                </div>
            </div>
            <div>
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.25rem; color: var(--text-secondary);">Delivery Location</label>
                <input type="text" name="delivery_location" value="Main Warehouse" style="width: 100%; padding: 0.7rem; border: 1px solid var(--border-color); border-radius: 8px; font-size: 0.95rem;">
            </div>
            <button type="submit" id="submitPoBtn" style="background: var(--accent-primary); color: white; border: none; padding: 0.85rem; border-radius: 10px; font-weight: 700; cursor: pointer; font-size: 1rem;">
                <i class="fa-solid fa-check"></i> Create Purchase Order
            </button>
        </form>
    </div>
</div>
<script>
function calcTotal() {
    const opt = document.getElementById('po_item').options[document.getElementById('po_item').selectedIndex];
    const cost = parseFloat(opt?.dataset?.cost || 0);
    const qty = parseFloat(document.getElementById('po_qty').value || 0);
    document.getElementById('po_total').value = '$' + (cost * qty).toFixed(2);
}
document.getElementById('po_item').addEventListener('change', calcTotal);
document.getElementById('po_qty').addEventListener('input', calcTotal);

document.getElementById('createPoForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = document.getElementById('submitPoBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Creating...';
    fetch('<?= BASE_URL ?>/modules/procurement/create_po.php', { method: 'POST', body: new FormData(this) })
        .then(r => r.json()).then(data => {
            if (data.success) {
                alert('PO created: ' + (data.po_id || 'Success'));
                window.location.href = '<?= BASE_URL ?>/app.php?view=proc_track';
            } else { alert('Error: ' + (data.error || 'Unknown')); btn.disabled = false; btn.innerHTML = '<i class="fa-solid fa-check"></i> Create Purchase Order'; }
        }).catch(() => { alert('Network error'); btn.disabled = false; btn.innerHTML = '<i class="fa-solid fa-check"></i> Create Purchase Order'; });
});
if ('<?= $prefillItem ?>') calcTotal();
</script>
