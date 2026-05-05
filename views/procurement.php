<?php
/**
 * AURA ERP — Procurement View (Interactive)
 * Displays suppliers, purchase orders, and provides CRUD actions.
 * Tables: purchase_orders, suppliers
 */

try {
    // Stats
    $totalPOs = $pdo->query("SELECT COUNT(*) FROM purchase_orders")->fetchColumn();
    $pendingPOs = $pdo->query("SELECT COUNT(*) FROM purchase_orders WHERE order_status = 'Pending'")->fetchColumn();
    $totalSpend = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM purchase_orders WHERE payment_status = 'Paid'")->fetchColumn();
    $supplierCount = $pdo->query("SELECT COUNT(*) FROM suppliers")->fetchColumn();

    // Purchase Orders list with item info
    $purchaseOrders = $pdo->query(
        "SELECT po.po_id, po.order_date, po.total_amount, po.currency, po.order_status,
                po.payment_status, po.delivery_location, po.requested_quantity,
                s.supplier_name, im.item_name
         FROM purchase_orders po
         JOIN suppliers s ON po.supplier_id = s.supplier_id
         LEFT JOIN item_master im ON po.item_id = im.item_id
         ORDER BY po.order_date DESC"
    )->fetchAll();

    // Suppliers for the modal dropdown
    $suppliers = $pdo->query("SELECT supplier_id, supplier_name, preferred_currency FROM suppliers ORDER BY supplier_name")->fetchAll(PDO::FETCH_ASSOC);
    
    // Raw Materials for PO creation
    $rawMaterials = $pdo->query("SELECT item_id, item_name, standard_cost FROM item_master WHERE category = 'Raw Material' ORDER BY item_name")->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    error_log("Procurement view error: " . $e->getMessage());
    $purchaseOrders = [];
    $suppliers = [];
    $rawMaterials = [];
    $totalPOs = $pendingPOs = $supplierCount = 0;
    $totalSpend = 0;
}
?>

<!-- Procurement Stats -->
<div class="stats-grid" style="margin-bottom: 1.5rem;">
    <div class="stat-card">
        <div class="stat-icon primary"><i class="fa-solid fa-file-contract"></i></div>
        <div class="stat-info">
            <h3>Total POs</h3>
            <p class="stat-value" style="font-size:1.5rem;"><?= $totalPOs ?></p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon warning"><i class="fa-solid fa-clock"></i></div>
        <div class="stat-info">
            <h3>Pending</h3>
            <p class="stat-value" style="font-size:1.5rem;"><?= $pendingPOs ?></p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon success"><i class="fa-solid fa-coins"></i></div>
        <div class="stat-info">
            <h3>Total Spend</h3>
            <p class="stat-value" style="font-size:1.5rem;"><?= number_format($totalSpend, 2) ?> JOD</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon info"><i class="fa-solid fa-truck-field"></i></div>
        <div class="stat-info">
            <h3>Suppliers</h3>
            <p class="stat-value" style="font-size:1.5rem;"><?= $supplierCount ?></p>
        </div>
    </div>
</div>

<!-- Purchase Orders Table -->
<div class="card">
    <div class="card-header" style="flex-direction:column; align-items:flex-start; gap:1rem;">
        <div style="width:100%; display:flex; justify-content:space-between; align-items:center;">
            <h3>Purchase Orders</h3>
            <div style="display:flex; gap:0.5rem;">
                <button class="icon-btn" onclick="openAddSupplierModal()"
                    style="width:auto; padding:0 1rem; color:var(--text-primary); border-color:var(--border-color);">
                    <i class="fa-solid fa-truck-field"></i> Add Supplier
                </button>
                <button class="icon-btn" onclick="openNewPOModal()"
                    style="width:auto; padding:0 1rem; color:var(--accent-primary); border-color:var(--accent-primary);">
                    <i class="fa-solid fa-plus"></i> New PO
                </button>
            </div>
        </div>
    </div>

    <div style="overflow-x:auto; padding-bottom:4rem;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Supplier</th>
                    <th>Order Date</th>
                    <th>Amount</th>
                    <th>Payment</th>
                    <th>Status</th>
                    <th style="width:60px;"></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($purchaseOrders)): ?>
                    <tr>
                        <td colspan="7" style="text-align:center; color:var(--text-secondary); padding:3rem;">
                            <i class="fa-solid fa-truck"
                                style="font-size:2rem; opacity:0.3; display:block; margin-bottom:0.75rem;"></i>
                            No purchase orders found
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($purchaseOrders as $po): ?>
                        <?php
                        $orderStatusClass = match ($po['order_status']) {
                            'Completed' => 'completed',
                            'Cancelled' => 'orange',
                            default => 'pending'
                        };
                        $paymentClass = $po['payment_status'] === 'Paid' ? 'completed' : 'pending';
                        ?>
                        <tr>
                            <td><span style="font-weight:700; color:var(--accent-primary);">
                                    <?= htmlspecialchars($po['po_id']) ?>
                                </span></td>
                            <td><span style="font-weight:500;">
                                    <?= htmlspecialchars($po['supplier_name']) ?>
                                </span></td>
                            <td>
                                <?= date('M d, Y', strtotime($po['order_date'])) ?>
                                <?php if (!empty($po['item_name'])): ?>
                                    <div style="font-size:0.8rem; color:var(--text-secondary); margin-top:0.25rem;">
                                        <?= htmlspecialchars($po['item_name']) ?> (<?= number_format($po['requested_quantity'], 1) ?> units)
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td><span style="font-weight:600;">
                                    <?= number_format($po['total_amount'] ?? 0, 2) ?>
                                    <?= htmlspecialchars($po['currency'] ?? 'JOD') ?>
                                </span></td>
                            <td><span class="badge <?= $paymentClass ?>">
                                    <?= htmlspecialchars($po['payment_status']) ?>
                                </span></td>
                            <td><span class="badge <?= $orderStatusClass ?>">
                                    <?= htmlspecialchars($po['order_status']) ?>
                                </span></td>
                            <td>
                                <?php if ($po['order_status'] === 'Pending'): ?>
                                    <div class="action-menu-container">
                                        <button class="action-btn" onclick="toggleActionMenu(event, this)"><i class="fa-solid fa-ellipsis-vertical"></i></button>
                                        <div class="dropdown-menu">
                                            <?php if ($po['payment_status'] === 'Paid'): ?>
                                                <button class="dropdown-item" onclick="receivePO('<?= $po['po_id'] ?>')"><i class="fa-solid fa-box-open"></i> Deliver</button>
                                            <?php else: ?>
                                                <button class="dropdown-item" onclick="markPaid('<?= $po['po_id'] ?>')"><i class="fa-solid fa-credit-card"></i> Mark Paid</button>
                                            <?php endif; ?>
                                            <button class="dropdown-item delete" onclick="cancelPO('<?= $po['po_id'] ?>')"><i class="fa-solid fa-ban"></i> Cancel PO</button>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    const suppliersData = <?= json_encode($suppliers) ?>;
    const itemsData = <?= json_encode($rawMaterials) ?>;

    document.addEventListener('click', e => {
        if (!e.target.closest('.action-menu-container')) {
            document.querySelectorAll('.dropdown-menu.active').forEach(m => m.classList.remove('active'));
        }
    });

    function toggleActionMenu(e, btn) {
        e.stopPropagation();
        const menu = btn.nextElementSibling;
        const isActive = menu.classList.contains('active');
        document.querySelectorAll('.dropdown-menu.active').forEach(m => m.classList.remove('active'));
        if (!isActive) menu.classList.add('active');
    }

    /* ---- Add Supplier Modal ---- */
    function openAddSupplierModal() {
        if (typeof showGenericModal !== 'function') return;

        const currencyOptions = ['JOD','USD','EUR','GBP','SAR','AED'].map((c, i) =>
            `<div class="custom-option ${i===0?'selected':''}" data-value="${c}">${c}</div>`
        ).join('');

        const content = `
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
                <h2 style="margin:0;">Add Supplier</h2>
                <button type="button" onclick="document.getElementById('generic-modal').classList.remove('active')" style="background:none; border:none; font-size:1.5rem; cursor:pointer; color:var(--text-primary);">&times;</button>
            </div>
            <form onsubmit="submitNewSupplier(event)">
                <div style="margin-bottom:1.5rem;">
                    <label style="display:block; margin-bottom:0.5rem; color:var(--text-secondary); font-size:0.9rem;">Supplier Name *</label>
                    <input type="text" name="supplier_name" required placeholder="e.g. Jordan Stone Quarry" style="width:100%; padding:0.75rem; border:1px solid var(--border-color); border-radius:8px; background:var(--bg-body); color:var(--text-primary);">
                </div>
                <div style="margin-bottom:2rem;">
                    <label style="display:block; margin-bottom:0.5rem; color:var(--text-secondary); font-size:0.9rem;">Preferred Currency *</label>
                    <div class="custom-select-wrapper">
                        <input type="hidden" name="preferred_currency" value="JOD" required>
                        <div class="custom-select">
                            <div class="custom-select-trigger">
                                <span class="selected-text">JOD</span>
                                <i class="fa-solid fa-chevron-down"></i>
                            </div>
                        </div>
                        <div class="custom-options">
                            ${currencyOptions}
                        </div>
                    </div>
                </div>
                <button type="submit" style="padding:1rem; background:var(--accent-primary); color:white; border:none; border-radius:8px; width:100%; cursor:pointer; font-weight:600; font-size:1rem;">
                    Create Supplier
                </button>
            </form>
        `;

        const modal = document.getElementById('generic-modal');
        modal.innerHTML = `<div class="modal-content" style="background:var(--bg-panel); padding:2.5rem; border-radius:16px; max-width:500px; margin:auto; box-shadow:var(--shadow-lg);">${content}</div>`;
        modal.classList.add('active');
        setTimeout(() => { if(window.initCustomSelects) window.initCustomSelects(modal); }, 50);
    }

    async function submitNewSupplier(e) {
        e.preventDefault();
        try {
            const formData = new FormData(e.target);
            const res = await fetch('<?= BASE_URL ?>/modules/procurement/create_supplier.php', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) {
                window.location.reload();
            } else {
                alert('Error: ' + (data.error || 'Unknown error'));
            }
        } catch (err) { alert('Network error'); }
    }

    /* ---- New PO Modal ---- */
    function openNewPOModal() {
        if (typeof showGenericModal !== 'function') return;

        if (suppliersData.length === 0) {
            alert('Please add a supplier first before creating a Purchase Order.');
            return;
        }
        if (itemsData.length === 0) {
            alert('No raw materials found in inventory. Please add one first.');
            return;
        }

        const supplierOptions = suppliersData.map((s, i) =>
            `<div class="custom-option ${i===0?'selected':''}" data-value="${s.supplier_id}">${s.supplier_name}</div>`
        ).join('');
        
        const itemOptions = itemsData.map((item, i) =>
            `<div class="custom-option ${i===0?'selected':''}" data-value="${item.item_id}">${item.item_name} ($${item.standard_cost})</div>`
        ).join('');

        const currencyOptions = ['JOD','USD','EUR','GBP','SAR','AED'].map((c, i) =>
            `<div class="custom-option ${i===0?'selected':''}" data-value="${c}">${c}</div>`
        ).join('');

        const firstSupplierId = suppliersData[0].supplier_id;
        const firstSupplierName = suppliersData[0].supplier_name;
        const firstItemId = itemsData[0].item_id;
        const firstItemName = itemsData[0].item_name;

        const content = `
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
                <h2 style="margin:0;">New Purchase Order</h2>
                <button type="button" onclick="document.getElementById('generic-modal').classList.remove('active')" style="background:none; border:none; font-size:1.5rem; cursor:pointer; color:var(--text-primary);">&times;</button>
            </div>
            <form onsubmit="submitNewPO(event)">
                <div style="display:flex; gap:1.5rem; margin-bottom:1.5rem;">
                    <div style="flex:1;">
                        <label style="display:block; margin-bottom:0.5rem; color:var(--text-secondary); font-size:0.9rem;">Supplier *</label>
                        <div class="custom-select-wrapper">
                            <input type="hidden" name="supplier_id" value="${firstSupplierId}" required>
                            <div class="custom-select">
                                <div class="custom-select-trigger">
                                    <span class="selected-text">${firstSupplierName}</span>
                                    <i class="fa-solid fa-chevron-down"></i>
                                </div>
                            </div>
                            <div class="custom-options">
                                ${supplierOptions}
                            </div>
                        </div>
                    </div>
                    <div style="flex:1;">
                        <label style="display:block; margin-bottom:0.5rem; color:var(--text-secondary); font-size:0.9rem;">Raw Material *</label>
                        <div class="custom-select-wrapper">
                            <input type="hidden" name="item_id" value="${firstItemId}" required>
                            <div class="custom-select">
                                <div class="custom-select-trigger">
                                    <span class="selected-text">${firstItemName}</span>
                                    <i class="fa-solid fa-chevron-down"></i>
                                </div>
                            </div>
                            <div class="custom-options">
                                ${itemOptions}
                            </div>
                        </div>
                    </div>
                </div>

                <div style="display:flex; gap:1.5rem; margin-bottom:1.5rem;">
                    <div style="flex:1;">
                        <label style="display:block; margin-bottom:0.5rem; color:var(--text-secondary); font-size:0.9rem;">Quantity (units) *</label>
                        <input type="number" step="0.01" min="0.01" name="requested_quantity" required placeholder="0.00" style="width:100%; padding:0.75rem; border:1px solid var(--border-color); border-radius:8px; background:var(--bg-body); color:var(--text-primary);">
                    </div>
                    <div style="flex:1;">
                        <label style="display:block; margin-bottom:0.5rem; color:var(--text-secondary); font-size:0.9rem;">Order Date *</label>
                        <input type="date" name="order_date" required value="${new Date().toISOString().split('T')[0]}" style="width:100%; padding:0.75rem; border:1px solid var(--border-color); border-radius:8px; background:var(--bg-body); color:var(--text-primary);">
                    </div>
                </div>

                <div style="display:flex; gap:1.5rem; margin-bottom:1.5rem;">
                    <div style="flex:1;">
                        <label style="display:block; margin-bottom:0.5rem; color:var(--text-secondary); font-size:0.9rem;">Currency *</label>
                        <div class="custom-select-wrapper">
                            <input type="hidden" name="currency" value="JOD" required>
                            <div class="custom-select">
                                <div class="custom-select-trigger">
                                    <span class="selected-text">JOD</span>
                                    <i class="fa-solid fa-chevron-down"></i>
                                </div>
                            </div>
                            <div class="custom-options">
                                ${currencyOptions}
                            </div>
                        </div>
                    </div>
                    <div style="flex:1;">
                        <label style="display:block; margin-bottom:0.5rem; color:var(--text-secondary); font-size:0.9rem;">Delivery Location</label>
                        <input type="text" name="delivery_location" placeholder="Optional" style="width:100%; padding:0.75rem; border:1px solid var(--border-color); border-radius:8px; background:var(--bg-body); color:var(--text-primary);">
                    </div>
                </div>

                <button type="submit" style="padding:1rem; background:var(--accent-primary); color:white; border:none; border-radius:8px; width:100%; cursor:pointer; font-weight:600; font-size:1rem;">
                    Create Purchase Order
                </button>
            </form>
        `;

        const modal = document.getElementById('generic-modal');
        modal.innerHTML = `<div class="modal-content" style="background:var(--bg-panel); padding:2.5rem; border-radius:16px; max-width:600px; margin:auto; box-shadow:var(--shadow-lg);">${content}</div>`;
        modal.classList.add('active');
        setTimeout(() => { if(window.initCustomSelects) window.initCustomSelects(modal); }, 50);
    }

    async function submitNewPO(e) {
        e.preventDefault();
        try {
            const formData = new FormData(e.target);
            const res = await fetch('<?= BASE_URL ?>/modules/procurement/create_po.php', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) {
                window.location.reload();
            } else {
                alert('Error: ' + (data.error || 'Unknown error'));
            }
        } catch (err) { alert('Network error'); }
    }

    /* ---- PO Actions ---- */
    async function receivePO(poId) {
        if (!confirm('Mark PO ' + poId + ' as Delivered?')) return;
        try {
            const fd = new FormData();
            fd.append('po_id', poId);
            const res = await fetch('<?= BASE_URL ?>/modules/procurement/receive_po.php', { method: 'POST', body: fd });
            const data = await res.json();
            if (data.success) window.location.reload();
            else alert('Error: ' + data.error);
        } catch (err) { alert('Network error'); }
    }

    async function markPaid(poId) {
        if (!confirm('Mark PO ' + poId + ' as Paid?')) return;
        try {
            const fd = new FormData();
            fd.append('po_id', poId);
            const res = await fetch('<?= BASE_URL ?>/modules/procurement/update_payment.php', { method: 'POST', body: fd });
            const data = await res.json();
            if (data.success) window.location.reload();
            else alert('Error: ' + data.error);
        } catch (err) { alert('Network error'); }
    }

    async function cancelPO(poId) {
        if (!confirm('Are you sure you want to cancel PO ' + poId + '? This cannot be undone.')) return;
        try {
            const fd = new FormData();
            fd.append('po_id', poId);
            const res = await fetch('<?= BASE_URL ?>/modules/procurement/delete_po.php', { method: 'POST', body: fd });
            const data = await res.json();
            if (data.success) window.location.reload();
            else alert('Error: ' + data.error);
        } catch (err) { alert('Network error'); }
    }
</script>