<?php
/** MiskStone ERP — Suppliers (with material linking from supplier_items) */
$suppliers = $pdo->query("
    SELECT s.*, 
           (SELECT COUNT(*) FROM supplier_items si WHERE si.supplier_id = s.supplier_id) as item_count
    FROM suppliers s ORDER BY s.supplier_name
")->fetchAll(PDO::FETCH_ASSOC);

// Get supplier_items for each supplier
$supplierItems = [];
$siStmt = $pdo->query("
    SELECT si.*, im.item_name, im.base_uom 
    FROM supplier_items si 
    JOIN item_master im ON si.item_id = im.item_id
    ORDER BY si.supplier_id, im.item_name
");
foreach ($siStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $supplierItems[$row['supplier_id']][] = $row;
}

$allRawMaterials = $pdo->query("SELECT item_id, item_name FROM item_master WHERE category = 'Raw Material' ORDER BY item_name")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="dashboard-container">
    <div class="welcome-banner" style="background: linear-gradient(135deg, #1e293b, #3b82f6); color: white; padding: 2rem; border-radius: 16px; margin-bottom: 2rem;">
        <h1><i class="fa-solid fa-building" style="margin-right: 0.75rem;"></i>Suppliers</h1>
        <p style="opacity: 0.9;">Manage suppliers and the raw materials they provide.</p>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(380px, 1fr)); gap: 1.5rem;">
        <?php foreach ($suppliers as $s): ?>
            <?php $items = $supplierItems[$s['supplier_id']] ?? []; ?>
            <div class="card" style="padding: 0; overflow: hidden;">
                <div style="padding: 1.25rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                        <h3 style="margin: 0;"><?= htmlspecialchars($s['supplier_name']) ?></h3>
                        <span class="badge" style="background: var(--accent-primary)20; color: var(--accent-primary); font-weight: 700;">
                            <?= $s['preferred_currency'] ?? 'JOD' ?>
                        </span>
                    </div>
                    <div style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 0.75rem;">
                        <?php if ($s['contact_person']): ?><div><i class="fa-solid fa-user"></i> <?= htmlspecialchars($s['contact_person']) ?></div><?php endif; ?>
                        <?php if ($s['email']): ?><div><i class="fa-solid fa-envelope"></i> <?= htmlspecialchars($s['email']) ?></div><?php endif; ?>
                        <?php if ($s['phone']): ?><div><i class="fa-solid fa-phone"></i> <?= htmlspecialchars($s['phone']) ?></div><?php endif; ?>
                    </div>

                    <!-- Materials supplied -->
                    <div style="margin-top: 0.75rem;">
                        <div style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: var(--text-secondary); margin-bottom: 0.5rem;">
                            Materials Supplied (<?= count($items) ?>)
                        </div>
                        <?php if (!empty($items)): ?>
                            <div style="display: flex; flex-wrap: wrap; gap: 0.35rem;">
                                <?php foreach ($items as $item): ?>
                                    <span style="font-size: 0.75rem; padding: 0.2rem 0.5rem; background: <?= $item['is_preferred'] ? '#dcfce7' : '#f1f5f9' ?>; color: <?= $item['is_preferred'] ? '#166534' : '#475569' ?>; border-radius: 4px; font-weight: 600;">
                                        <?= htmlspecialchars($item['item_name']) ?>
                                        <?php if ($item['unit_price']): ?> · <?= number_format($item['unit_price'], 2) ?> JOD<?php endif; ?>
                                        <?= $item['is_preferred'] ? ' ★' : '' ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <span style="font-size: 0.8rem; color: #ef4444; font-style: italic;">No materials linked</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div style="padding: 0.75rem 1.25rem; background: #f8fafc; border-top: 1px solid var(--border-color);">
                    <button onclick="editSupplierItems(<?= $s['supplier_id'] ?>, '<?= addslashes($s['supplier_name']) ?>')" 
                            style="background: var(--accent-primary); color: white; border: none; padding: 0.4rem 0.8rem; border-radius: 6px; cursor: pointer; font-size: 0.8rem; font-weight: 600;">
                        <i class="fa-solid fa-pen"></i> Edit Materials
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
const allRawMaterials = <?= json_encode($allRawMaterials) ?>;
const currentSupplierItems = <?= json_encode($supplierItems) ?>;

function editSupplierItems(suppId, suppName) {
    const currentItems = currentSupplierItems[suppId] || [];
    const currentIds = currentItems.map(i => i.item_id);

    const checkboxes = allRawMaterials.map(rm => {
        const checked = currentIds.includes(rm.item_id) ? 'checked' : '';
        return `<label style="display:flex; align-items:center; gap:0.5rem; padding:0.35rem 0; font-size:0.85rem; cursor:pointer;">
            <input type="checkbox" name="items[]" value="${rm.item_id}" ${checked} style="cursor:pointer;">
            ${rm.item_name}
        </label>`;
    }).join('');

    const modal = document.getElementById('generic-modal');
    modal.innerHTML = `<div class="modal-content" style="background:var(--bg-panel); padding:2rem; border-radius:16px; max-width:450px; margin:auto; box-shadow:var(--shadow-lg); max-height:80vh; overflow-y:auto;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
            <h2 style="margin:0; font-size:1.1rem;">Materials — ${suppName}</h2>
            <button onclick="document.getElementById('generic-modal').classList.remove('active')" style="background:none; border:none; font-size:1.5rem; cursor:pointer;">&times;</button>
        </div>
        <form onsubmit="saveSupplierItems(event, ${suppId})">
            <div style="margin-bottom:1.5rem;">${checkboxes}</div>
            <button type="submit" style="width:100%; padding:0.75rem; background:var(--accent-primary); color:white; border:none; border-radius:8px; font-weight:700; cursor:pointer;">Save Changes</button>
        </form>
    </div>`;
    modal.classList.add('active');
}

async function saveSupplierItems(e, suppId) {
    e.preventDefault();
    const checked = [...e.target.querySelectorAll('input[name="items[]"]:checked')].map(c => c.value);

    const fd = new FormData();
    fd.append('supplier_id', suppId);
    checked.forEach(id => fd.append('item_ids[]', id));

    const res = await fetch('<?= BASE_URL ?>/modules/procurement/update_supplier_items.php', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.success) location.reload();
    else alert('Error: ' + (data.error || 'Unknown'));
}
</script>
