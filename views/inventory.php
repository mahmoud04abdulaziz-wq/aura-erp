<?php
/**
 * AURA ERP — Inventory Dashboard
 * Tracks Raw Materials, Finished Goods, and recent transactions.
 * Tables: item_master, inventory_ledger, inventory_finished_goods
 */

try {
    // Determine which tab to show (Finished Goods by default)
    $tab = $_GET['tab'] ?? 'finished';
    $categoryFilter = $tab === 'raw' ? 'Raw Material' : 'Finished Good';

    // Fetch master items with calculated current stock
    $items = $pdo->query("
        SELECT i.item_id, i.item_name, i.category, i.base_uom, i.standard_cost, i.min_stock_level,
               COALESCE((SELECT SUM(quantity_change) FROM inventory_ledger il WHERE il.item_id = i.item_id), 0) as current_stock
        FROM item_master i
        WHERE i.category = " . $pdo->quote($categoryFilter) . "
        ORDER BY i.item_name
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Fetch recent transactions globally for the sidebar
    $transactions = $pdo->query("
        SELECT tl.transaction_id, tl.transaction_type, tl.quantity_change, tl.timestamp,
               i.item_name,
               CONCAT(e.first_name, ' ', e.last_name) as recorded_by_name
        FROM inventory_ledger tl
        JOIN item_master i ON tl.item_id = i.item_id
        JOIN users u ON tl.recorded_by = u.user_id
        JOIN employees e ON u.employee_id = e.employee_id
        ORDER BY tl.timestamp DESC
        LIMIT 15
    ")->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    error_log("Inventory view error: " . $e->getMessage());
    $items = [];
    $transactions = [];
}
?>

<div class="card header-card" style="margin-bottom: 2rem;">
    <div style="display:flex; justify-content:space-between; align-items:center;">
        <div>
            <h2>Inventory Control</h2>
            <p style="color:var(--text-secondary);">Manage raw materials, track finished products, and log stock movements.</p>
        </div>
        <div style="display:flex; gap:1rem;">
            <button class="icon-btn" onclick="openItemModal()" style="background:var(--accent-primary); color:white; width:auto; padding:0 1.5rem; border-radius:12px; font-weight:500; border:none; box-shadow:0 4px 10px rgba(99,102,241,0.2);"><i class="fa-solid fa-plus" style="margin-right:0.5rem;"></i> New Item</button>
            <button class="icon-btn" onclick="openTransactionModal()" style="background:var(--success); color:white; width:auto; padding:0 1.5rem; border-radius:12px; font-weight:500; border:none; box-shadow:0 4px 10px rgba(16,185,129,0.2);"><i class="fa-solid fa-boxes-packing" style="margin-right:0.5rem;"></i> Log Movement</button>
        </div>
    </div>
</div>

<!-- Custom Layout with Sidebar -->
<div style="display:flex; gap:2rem;">
    
    <!-- Left: Main Inventory Roster -->
    <div style="flex:1;">
        <div class="card" style="margin-bottom: 2rem;">
            <!-- Tabs -->
            <div style="display:flex; border-bottom:1px solid var(--border-color); margin-bottom:1rem;">
                <a href="<?= BASE_URL ?>/app.php?view=inventory&tab=finished" style="padding:1rem 1.5rem; text-decoration:none; font-weight:600; color: <?= $tab === 'finished' ? 'var(--accent-primary)' : 'var(--text-secondary)' ?>; border-bottom: 2px solid <?= $tab === 'finished' ? 'var(--accent-primary)' : 'transparent' ?>;">Finished Goods</a>
                <a href="<?= BASE_URL ?>/app.php?view=inventory&tab=raw" style="padding:1rem 1.5rem; text-decoration:none; font-weight:600; color: <?= $tab === 'raw' ? 'var(--accent-primary)' : 'var(--text-secondary)' ?>; border-bottom: 2px solid <?= $tab === 'raw' ? 'var(--accent-primary)' : 'transparent' ?>;">Raw Materials</a>
            </div>

            <div style="padding-bottom: 4rem;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Item ID</th>
                            <th>Name</th>
                            <th>Current Stock</th>
                            <th>UOM</th>
                            <th>Std Cost</th>
                            <th>Status/Alert</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($items)): ?>
                            <tr><td colspan="6" style="text-align:center; padding:3rem; color:var(--text-secondary);"><i class="fa-solid fa-box-open" style="font-size:2rem; opacity:0.3; display:block; margin-bottom:0.75rem;"></i>No items found in this category.</td></tr>
                        <?php else: ?>
                            <?php foreach($items as $item): ?>
                                <?php
                                    $stock = (float)$item['current_stock'];
                                    $min = (float)$item['min_stock_level'];
                                    $statusColor = 'success';
                                    $statusText = 'Healthy';
                                    if ($stock <= 0) { $statusColor = 'orange'; $statusText = 'Out of Stock'; }
                                    elseif ($stock <= $min) { $statusColor = 'orange'; $statusText = 'Low Stock'; }
                                ?>
                                <tr>
                                    <td><span style="color:var(--text-secondary); font-family:monospace;"><?= htmlspecialchars($item['item_id']) ?></span></td>
                                    <td><strong style="color:var(--text-primary);"><?= htmlspecialchars($item['item_name']) ?></strong></td>
                                    <td style="font-size:1.1rem; font-weight:700; color:var(--<?= $statusColor ?>);"><?= number_format($stock, 2) ?></td>
                                    <td><span class="badge gray"><?= htmlspecialchars($item['base_uom']) ?></span></td>
                                    <td>$<?= number_format($item['standard_cost'], 2) ?></td>
                                    <td><span class="badge <?= $statusColor ?>"><?= $statusText ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Right: Recent Transactions Sidebar -->
    <div style="width: 350px;">
        <div class="card" style="height:100%;">
            <h3 style="margin-bottom:1.5rem; font-size:1.1rem;">Recent Transactions</h3>
            <div style="display:flex; flex-direction:column; gap:1rem;">
                <?php if(empty($transactions)): ?>
                    <p style="color:var(--text-secondary); font-style:italic; font-size:0.9rem;">No recent movements recorded.</p>
                <?php else: ?>
                    <?php foreach($transactions as $tx): ?>
                        <div style="padding:1rem; border:1px solid var(--border-color); border-radius:8px; background:var(--bg-body);">
                            <div style="display:flex; justify-content:space-between; margin-bottom:0.5rem; font-size:0.85rem;">
                                <span style="font-weight:600; color:var(--text-primary);"><?= htmlspecialchars($tx['transaction_type']) ?></span>
                                <span style="color:var(--text-secondary);"><?= date('M j, g:i a', strtotime($tx['timestamp'])) ?></span>
                            </div>
                            <strong style="display:block; margin-bottom:0.5rem; color:var(--accent-primary);"><?= htmlspecialchars($tx['item_name']) ?></strong>
                            <div style="display:flex; justify-content:space-between; font-size:0.85rem;">
                                <span><?= $tx['quantity_change'] > 0 ? '+'.$tx['quantity_change'] : $tx['quantity_change'] ?></span>
                                <span style="color:var(--text-secondary);">by <?= htmlspecialchars($tx['recorded_by_name']) ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    function openItemModal() {
        if (typeof showGenericModal === 'function') {
            const content = `
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
                    <h2 style="margin:0;">Register New Item</h2>
                    <button type="button" onclick="document.getElementById('generic-modal').classList.remove('active')" style="background:none; border:none; font-size:1.5rem; cursor:pointer; color:var(--text-primary);">&times;</button>
                </div>
                <form onsubmit="submitNewItem(event)">
                    <div style="display:flex; gap:1rem; margin-bottom:1rem;">
                        <div style="flex:1;">
                            <label style="display:block; margin-bottom:0.5rem;">Item Object ID *</label>
                            <input type="text" name="item_id" required placeholder="e.g. RM-5001" style="width:100%; padding:0.75rem; border:1px solid var(--border-color); border-radius:8px;">
                        </div>
                        <div style="flex:2;">
                            <label style="display:block; margin-bottom:0.5rem;">Item Name *</label>
                            <input type="text" name="item_name" required style="width:100%; padding:0.75rem; border:1px solid var(--border-color); border-radius:8px;">
                        </div>
                    </div>

                    <div style="display:flex; gap:1.5rem; margin-bottom:1.5rem;">
                        <div style="flex:1;">
                            <label style="display:block; margin-bottom:0.5rem; color:var(--text-secondary); font-size:0.9rem;">Category *</label>
                            <div class="custom-select-wrapper">
                                <input type="hidden" name="category" value="Raw Material" required>
                                <div class="custom-select">
                                    <div class="custom-select-trigger">
                                        <span class="selected-text">Raw Material</span>
                                        <i class="fa-solid fa-chevron-down"></i>
                                    </div>
                                </div>
                                <div class="custom-options">
                                    <div class="custom-option selected" data-value="Raw Material">Raw Material</div>
                                    <div class="custom-option" data-value="Finished Good">Finished Good</div>
                                    <div class="custom-option" data-value="Consumable">Consumable</div>
                                </div>
                            </div>
                        </div>
                        <div style="flex:1;">
                            <label style="display:block; margin-bottom:0.5rem; color:var(--text-secondary); font-size:0.9rem;">Base UOM *</label>
                            <input type="text" name="base_uom" required placeholder="e.g. kg, pcs" style="width:100%; padding:0.75rem; border:1px solid var(--border-color); border-radius:8px;">
                        </div>
                    </div>

                    <div style="display:flex; gap:1.5rem; margin-bottom:2rem;">
                        <div style="flex:1;">
                            <label style="display:block; margin-bottom:0.5rem; color:var(--text-secondary); font-size:0.9rem;">Standard Cost ($)</label>
                            <input type="number" step="0.01" name="standard_cost" value="0.00" style="width:100%; padding:0.75rem; border:1px solid var(--border-color); border-radius:8px;">
                        </div>
                        <div style="flex:1;">
                            <label style="display:block; margin-bottom:0.5rem; color:var(--text-secondary); font-size:0.9rem;">Min Stock Alert</label>
                            <input type="number" step="0.01" name="min_stock_level" value="0.00" style="width:100%; padding:0.75rem; border:1px solid var(--border-color); border-radius:8px;">
                        </div>
                    </div>

                    <button type="submit" style="padding:1rem; background:var(--accent-primary); color:white; border:none; border-radius:8px; width:100%; cursor:pointer; font-weight:600; font-size:1rem;">
                        Create Item Record
                    </button>
                </form>
            `;
            const modal = document.getElementById('generic-modal');
            modal.innerHTML = `<div class="modal-content" style="background:var(--bg-panel); padding:2.5rem; border-radius:16px; max-width:600px; margin:auto; box-shadow:var(--shadow-lg);">${content}</div>`;
            modal.classList.add('active');
            setTimeout(() => { if(window.initCustomSelects) window.initCustomSelects(modal); }, 50);
        }
    }

    async function submitNewItem(e) {
        e.preventDefault();
        try {
            const fd = new FormData(e.target);
            const res = await fetch('<?= BASE_URL ?>/modules/inventory/create_item.php', { method: 'POST', body: fd });
            const data = await res.json();
            if (data.success) window.location.reload();
            else alert('Error: ' + data.error);
        } catch (err) { alert('Network error'); }
    }

    function openTransactionModal() {
        if (typeof showGenericModal === 'function') {
            const content = `
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
                    <h2 style="margin:0;">Log Inventory Movement</h2>
                    <button type="button" onclick="document.getElementById('generic-modal').classList.remove('active')" style="background:none; border:none; font-size:1.5rem; cursor:pointer; color:var(--text-primary);">&times;</button>
                </div>
                <form onsubmit="submitTransaction(event)">
                    <div style="margin-bottom:1.5rem;">
                        <label style="display:block; margin-bottom:0.5rem; color:var(--text-secondary); font-size:0.9rem;">Target Item ID *</label>
                        <input type="text" name="item_id" required placeholder="Exact Code (e.g. RM-001)" style="width:100%; padding:0.75rem; border:1px solid var(--border-color); border-radius:8px;">
                    </div>
                    
                    <div style="display:flex; gap:1.5rem; margin-bottom:2rem; align-items:flex-start;">
                        <div style="flex:1;">
                            <label style="display:block; margin-bottom:0.5rem; color:var(--text-secondary); font-size:0.9rem;">Move Type *</label>
                            <div class="custom-select-wrapper">
                                <input type="hidden" name="transaction_type" value="Receipt" required>
                                <div class="custom-select">
                                    <div class="custom-select-trigger">
                                        <span class="selected-text" style="color:var(--success); font-weight:600;">Receipt (+)</span>
                                        <i class="fa-solid fa-chevron-down"></i>
                                    </div>
                                </div>
                                <div class="custom-options">
                                    <div class="custom-option selected" data-value="Receipt" style="color:var(--success);">Receipt (+)</div>
                                    <div class="custom-option" data-value="Dispatch" style="color:var(--danger);">Dispatch (-)</div>
                                    <div class="custom-option" data-value="Scrap" style="color:var(--danger);">Scrap (-)</div>
                                    <div class="custom-option" data-value="Adjustment">Adjustment (+/-)</div>
                                </div>
                            </div>
                        </div>
                        <div style="flex:1;">
                            <label style="display:block; margin-bottom:0.5rem; color:var(--text-secondary); font-size:0.9rem;">Adjustment Qty *</label>
                            <input type="number" step="0.001" name="quantity_change" placeholder="(Use - to deduct)" required style="width:100%; padding:0.75rem; border:1px solid var(--border-color); border-radius:8px;">
                        </div>
                    </div>

                    <button type="submit" style="padding:1rem; background:var(--success); color:white; border:none; border-radius:8px; width:100%; cursor:pointer; font-weight:600; font-size:1rem;">
                        Submit Ledger Entry
                    </button>
                </form>
            `;
            const modal = document.getElementById('generic-modal');
            modal.innerHTML = `<div class="modal-content" style="background:var(--bg-panel); padding:2.5rem; border-radius:16px; max-width:550px; margin:auto; box-shadow:var(--shadow-lg);">${content}</div>`;
            modal.classList.add('active');
            setTimeout(() => { if(window.initCustomSelects) window.initCustomSelects(modal); }, 50);
        }
    }

    async function submitTransaction(e) {
        e.preventDefault();
        try {
            const fd = new FormData(e.target);
            const res = await fetch('<?= BASE_URL ?>/modules/inventory/record_transaction.php', { method: 'POST', body: fd });
            const data = await res.json();
            if (data.success) {
                window.location.reload();
            } else {
                alert('Transaction Failed: ' + data.error);
            }
        } catch (err) { alert('Network error'); }
    }
</script>