<?php
/** MiskStone ERP — Create Purchase Order (Multi-Line)
 *  Select supplier → shows only their materials → add lines → submit.
 */
$suppliers = $pdo->query("SELECT supplier_id, supplier_name FROM suppliers ORDER BY supplier_name")->fetchAll(PDO::FETCH_ASSOC);

// Build supplier → items mapping from supplier_items table
$supplierItemsMap = [];
$siRows = $pdo->query("
    SELECT si.supplier_id, si.item_id, si.unit_price, si.is_preferred, im.item_name, im.base_uom
    FROM supplier_items si
    JOIN item_master im ON si.item_id = im.item_id
    ORDER BY im.item_name
")->fetchAll(PDO::FETCH_ASSOC);
foreach ($siRows as $r) {
    $supplierItemsMap[$r['supplier_id']][] = $r;
}

// Pre-fill from query params
$prefillItem = $_GET['item'] ?? '';
$prefillQty = $_GET['qty'] ?? '';
?>
<div class="dashboard-container">
    <div class="welcome-banner" style="background: linear-gradient(135deg, #1e293b, #22c55e); color: white; padding: 2rem; border-radius: 16px; margin-bottom: 2rem;">
        <h1><i class="fa-solid fa-file-circle-plus" style="margin-right: 0.75rem;"></i>Create Purchase Order</h1>
        <p style="opacity: 0.9;">Select a supplier, add materials they provide, then submit the PO.</p>
    </div>

    <div class="card" style="max-width: 800px;">
        <form id="createPoForm">
            <!-- Supplier Selection -->
            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.4rem; color: var(--text-secondary);">Supplier *</label>
                <select name="supplier_id" id="supplierSelect" required style="width: 100%; padding: 0.7rem; border: 1px solid var(--border-color); border-radius: 8px; font-size: 0.95rem;">
                    <option value="">Select supplier...</option>
                    <?php foreach ($suppliers as $s): ?>
                        <option value="<?= $s['supplier_id'] ?>"><?= htmlspecialchars($s['supplier_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Line Items Container -->
            <div id="linesContainer" style="margin-bottom: 1.5rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                    <label style="font-size: 0.85rem; font-weight: 600; color: var(--text-secondary);">Order Lines</label>
                    <button type="button" id="addLineBtn" onclick="addLine()" disabled style="background: var(--accent-primary); color: white; border: none; padding: 0.4rem 0.8rem; border-radius: 6px; font-size: 0.8rem; cursor: pointer; font-weight: 600;">
                        <i class="fa-solid fa-plus"></i> Add Material
                    </button>
                </div>
                <div id="linesList">
                    <p id="noLinesMsg" style="text-align: center; color: var(--text-secondary); padding: 2rem; background: #f8fafc; border-radius: 10px; border: 2px dashed var(--border-color);">
                        <i class="fa-solid fa-arrow-up" style="display:block; font-size:1.5rem; opacity:0.3; margin-bottom:0.5rem;"></i>
                        Select a supplier first, then add materials
                    </p>
                </div>
            </div>

            <!-- Delivery Location -->
            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.4rem; color: var(--text-secondary);">Delivery Location</label>
                <input type="text" name="delivery_location" value="Main Warehouse" style="width: 100%; padding: 0.7rem; border: 1px solid var(--border-color); border-radius: 8px; font-size: 0.95rem;">
            </div>

            <!-- Total + Submit -->
            <div style="display: flex; justify-content: space-between; align-items: center; padding: 1rem; background: #f1f5f9; border-radius: 10px; margin-bottom: 1rem;">
                <div>
                    <span style="font-size: 0.85rem; color: var(--text-secondary);">Order Total</span>
                    <h2 id="orderTotal" style="margin: 0; color: var(--accent-primary);">$0.00</h2>
                </div>
                <span id="lineCount" style="font-size: 0.85rem; color: var(--text-secondary);">0 items</span>
            </div>

            <button type="submit" id="submitPoBtn" disabled style="width: 100%; background: var(--accent-primary); color: white; border: none; padding: 0.85rem; border-radius: 10px; font-weight: 700; cursor: pointer; font-size: 1rem;">
                <i class="fa-solid fa-check"></i> Create Purchase Order
            </button>
        </form>
    </div>
</div>

<script>
const supplierItemsMap = <?= json_encode($supplierItemsMap) ?>;
let lineCounter = 0;
let currentSupplierItems = [];

document.getElementById('supplierSelect').addEventListener('change', function() {
    const suppId = this.value;
    currentSupplierItems = supplierItemsMap[suppId] || [];
    document.getElementById('addLineBtn').disabled = !suppId;
    
    // Clear existing lines
    document.getElementById('linesList').innerHTML = '';
    lineCounter = 0;
    
    if (suppId && currentSupplierItems.length > 0) {
        document.getElementById('noLinesMsg')?.remove();
        addLine(); // auto-add first line
    } else if (suppId) {
        document.getElementById('linesList').innerHTML = '<p style="text-align:center; color:#ef4444; padding:1rem;">This supplier has no materials mapped. Update in Suppliers page.</p>';
    }
    updateTotal();
});

function addLine() {
    if (currentSupplierItems.length === 0) return;
    
    const idx = lineCounter++;
    const opts = currentSupplierItems.map(i => 
        `<option value="${i.item_id}" data-price="${i.unit_price}" data-uom="${i.base_uom}">${i.item_name} (${i.base_uom})</option>`
    ).join('');

    const firstPrice = currentSupplierItems[0]?.unit_price || 0;

    const lineHtml = `
        <div class="po-line" id="line-${idx}" style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr auto; gap: 0.5rem; align-items: center; padding: 0.75rem; background: #f8fafc; border-radius: 8px; margin-bottom: 0.5rem; border: 1px solid var(--border-color);">
            <select name="lines[${idx}][item_id]" required onchange="updateLinePrice(${idx}, this)" style="padding: 0.5rem; border: 1px solid var(--border-color); border-radius: 6px; font-size: 0.85rem;">
                ${opts}
            </select>
            <input type="number" name="lines[${idx}][quantity]" step="0.001" min="1" value="100" required oninput="updateTotal()" style="padding: 0.5rem; border: 1px solid var(--border-color); border-radius: 6px; font-size: 0.85rem; text-align: right;">
            <input type="number" name="lines[${idx}][unit_price]" step="0.01" min="0" value="${firstPrice}" required oninput="updateTotal()" style="padding: 0.5rem; border: 1px solid var(--border-color); border-radius: 6px; font-size: 0.85rem; text-align: right;">
            <span class="line-total" style="font-weight: 700; font-size: 0.85rem; text-align: right;">$${(100 * firstPrice).toFixed(2)}</span>
            <button type="button" onclick="removeLine(${idx})" style="background: #fee2e2; color: #991b1b; border: none; width: 32px; height: 32px; border-radius: 6px; cursor: pointer; font-size: 0.85rem;">
                <i class="fa-solid fa-times"></i>
            </button>
        </div>
    `;

    document.getElementById('noLinesMsg')?.remove();
    document.getElementById('linesList').insertAdjacentHTML('beforeend', lineHtml);
    updateTotal();
}

function removeLine(idx) {
    document.getElementById('line-' + idx)?.remove();
    updateTotal();
}

function updateLinePrice(idx, select) {
    const opt = select.options[select.selectedIndex];
    const price = opt.dataset.price || 0;
    const line = document.getElementById('line-' + idx);
    line.querySelector('[name*="unit_price"]').value = price;
    updateTotal();
}

function updateTotal() {
    const lines = document.querySelectorAll('.po-line');
    let total = 0;
    let count = 0;
    lines.forEach(line => {
        const qty = parseFloat(line.querySelector('[name*="quantity"]')?.value || 0);
        const price = parseFloat(line.querySelector('[name*="unit_price"]')?.value || 0);
        const lineTotal = qty * price;
        line.querySelector('.line-total').textContent = '$' + lineTotal.toFixed(2);
        total += lineTotal;
        count++;
    });
    document.getElementById('orderTotal').textContent = '$' + total.toFixed(2);
    document.getElementById('lineCount').textContent = count + ' item' + (count !== 1 ? 's' : '');
    document.getElementById('submitPoBtn').disabled = count === 0;
}

document.getElementById('createPoForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = document.getElementById('submitPoBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Creating...';

    fetch('<?= BASE_URL ?>/modules/procurement/create_po.php', { method: 'POST', body: new FormData(this) })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                alert('PO created: ' + (data.po_id || 'Success'));
                window.location.href = '<?= BASE_URL ?>/app.php?view=proc_track';
            } else {
                alert('Error: ' + (data.error || 'Unknown'));
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-check"></i> Create Purchase Order';
            }
        })
        .catch(() => {
            alert('Network error');
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-check"></i> Create Purchase Order';
        });
});
</script>
