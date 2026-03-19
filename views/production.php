<?php
/**
 * AURA ERP — Production View
 * Displays production orders and machine status from the database.
 * Tables: production_orders, item_master, users, employees
 */

try {
    $batches = $pdo->query(
        "SELECT po.production_id, po.machine_id, po.production_date, po.target_quantity,
                po.actual_yield, po.status, po.qa_status,
                im.item_name,
                CONCAT(e.first_name, ' ', e.last_name) as operator_name
         FROM production_orders po
         JOIN item_master im ON po.item_id = im.item_id
         JOIN users u ON po.operator_user_id = u.user_id
         JOIN employees e ON u.employee_id = e.employee_id
         ORDER BY po.created_at DESC"
    )->fetchAll();

    // Get unique machines and their statuses
    $machines = $pdo->query(
        "SELECT DISTINCT machine_id,
                CASE
                    WHEN status IN ('Mixing','Curing') THEN 'Running'
                    WHEN status = 'Failed' THEN 'Maintenance'
                    ELSE 'Idle'
                END as machine_status
         FROM production_orders
         WHERE machine_id IS NOT NULL
         ORDER BY machine_id"
    )->fetchAll();

    // Get finished goods for the Add Batch modal
    $finishedGoods = $pdo->query("SELECT item_id, item_name FROM item_master WHERE category = 'Finished Good' ORDER BY item_name")->fetchAll(PDO::FETCH_ASSOC);

    // Get production operators for the Add Batch modal
    $operators = $pdo->query("
        SELECT u.user_id, CONCAT(e.first_name, ' ', e.last_name) as full_name 
        FROM users u 
        JOIN employees e ON u.employee_id = e.employee_id 
        JOIN roles r ON u.role_id = r.role_id 
        WHERE r.role_name IN ('Production Manager', 'Executive Board')
        ORDER BY first_name
    ")->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    error_log("Production view error: " . $e->getMessage());
    $batches = [];
    $machines = [];
    $finishedGoods = [];
    $operators = [];
}
?>

<div class="card">
    <div class="card-header" style="flex-direction:column; align-items:flex-start; gap:1rem;">
        <div style="width:100%; display:flex; justify-content:space-between; align-items:center;">
            <h3>Active Production Batches</h3>
            <button class="icon-btn" onclick="openStartBatchModal()"
                style="width:auto; padding:0 1rem; color:var(--accent-primary); border-color:var(--accent-primary);">
                <i class="fa-solid fa-play"></i> Start Batch
            </button>
        </div>
    </div>

    <div style="padding-bottom: 4rem;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Batch ID</th>
                    <th>Material</th>
                    <th>Machine</th>
                    <th>Operator</th>
                    <th>Progress</th>
                    <th>Status</th>
                    <th>QA</th>
                    <th style="width:60px;"></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($batches)): ?>
                    <tr>
                        <td colspan="8" style="text-align:center; color:var(--text-secondary); padding:3rem;">
                            <i class="fa-solid fa-industry"
                                style="font-size:2rem; opacity:0.3; display:block; margin-bottom:0.75rem;"></i>
                            No production orders found
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($batches as $batch): ?>
                        <?php
                        $progress = $batch['target_quantity'] > 0
                            ? min(100, round(($batch['actual_yield'] / $batch['target_quantity']) * 100))
                            : 0;

                        $statusClass = match ($batch['status']) {
                            'Completed' => 'completed',
                            'Failed' => 'orange',
                            'Planned' => 'pending',
                            default => 'in-progress'
                        };

                        $qaClass = match ($batch['qa_status']) {
                            'Passed' => 'completed',
                            'Failed' => 'orange',
                            'Rework' => 'blue',
                            default => 'gray'
                        };
                        ?>
                        <tr>
                            <td><span style="font-weight:700; color:var(--text-secondary);">
                                    <?= htmlspecialchars($batch['production_id']) ?>
                                </span></td>
                            <td><span style="font-weight:500;">
                                    <?= htmlspecialchars($batch['item_name']) ?>
                                </span></td>
                            <td>
                                <?= htmlspecialchars($batch['machine_id'] ?? '—') ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($batch['operator_name']) ?>
                            </td>
                            <td style="width:150px;">
                                <div
                                    style="display:flex; justify-content:space-between; font-size:0.75rem; color:var(--text-secondary); margin-bottom:0.25rem;">
                                    <span>
                                        <?= $progress ?>%
                                    </span>
                                </div>
                                <div class="progress-bar-container">
                                    <div class="progress-bar-fill"
                                        style="width:<?= $progress ?>%; background:<?= $progress >= 100 ? 'var(--success)' : 'var(--accent-primary)' ?>;">
                                    </div>
                                </div>
                            </td>
                            <td><span class="badge <?= $statusClass ?>">
                                    <?= htmlspecialchars($batch['status']) ?>
                                </span></td>
                            <td><span class="badge <?= $qaClass ?>">
                                    <?= htmlspecialchars($batch['qa_status']) ?>
                                </span></td>
                            <td>
                                <div class="action-menu-container">
                                    <button class="action-btn" onclick="toggleActionMenu(event, this)"><i class="fa-solid fa-ellipsis-vertical"></i></button>
                                    <div class="dropdown-menu">
                                        <button class="dropdown-item" onclick="updateBatchField('<?= $batch['production_id'] ?>', 'status', ['Planned','Mixing','Curing','Completed','Failed'])"><i class="fa-solid fa-arrows-rotate"></i> Update Status</button>
                                        <button class="dropdown-item" onclick="updateBatchField('<?= $batch['production_id'] ?>', 'qa_status', ['Pending','Passed','Failed','Rework'])"><i class="fa-solid fa-vial-circle-check"></i> QA Status</button>
                                        <button class="dropdown-item" onclick="logBatchYield('<?= $batch['production_id'] ?>')"><i class="fa-solid fa-weight-scale"></i> Log Yield</button>
                                        <button class="dropdown-item delete" onclick="deleteBatch('<?= $batch['production_id'] ?>')"><i class="fa-regular fa-trash-can"></i> Delete</button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Machine Floor Status -->
<?php if (!empty($machines)): ?>
    <div style="margin-top: 1.5rem;" class="card production-status">
        <div class="card-header">
            <h3>Machine Floor Status</h3>
        </div>
        <div class="machine-list"
            style="display:grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap:1rem;">
            <?php foreach ($machines as $machine): ?>
                <?php
                $statusClass = match ($machine['machine_status']) {
                    'Running' => 'running',
                    'Maintenance' => 'maintenance',
                    default => 'idle'
                };
                ?>
                <div class="machine-item" style="border: 1px solid var(--border-color); padding: 1rem; border-radius: 8px;">
                    <span class="machine-name" style="font-weight:600;">
                        <?= htmlspecialchars($machine['machine_id']) ?>
                    </span>
                    <div style="display:flex; align-items:center; gap:0.5rem; margin-top:0.5rem;">
                        <div class="status-indicator <?= $statusClass ?>"></div>
                        <span class="status-text">
                            <?= htmlspecialchars($machine['machine_status']) ?>
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<script>
    const finishedGoodsData = <?= json_encode($finishedGoods) ?>;
    const operatorsData = <?= json_encode($operators) ?>;

    document.addEventListener('click', e => {
        if (!e.target.closest('.action-menu-container')) {
            document.querySelectorAll('.dropdown-menu.active').forEach(m => m.classList.remove('active'));
        }
    });

    function toggleActionMenu(e, btn) {
        e.stopPropagation();
        const menu = btn.nextElementSibling;
        const isActive = menu.classList.contains('active');
        
        // Close all currently open action menus
        document.querySelectorAll('.dropdown-menu.active').forEach(m => m.classList.remove('active'));
        
        // If the clicked one wasn't active, open it
        if (!isActive) {
            menu.classList.add('active');
        }
    }

    function openStartBatchModal() {
        if (typeof showGenericModal === 'function') {
            const goodsOptionsHtml = finishedGoodsData.map((g, i) => `<div class="custom-option ${i===0?'selected':''}" data-value="${g.item_id}">${g.item_name}</div>`).join('');
            const opsOptionsHtml = operatorsData.map((o, i) => `<div class="custom-option ${i===0?'selected':''}" data-value="${o.user_id}">${o.full_name}</div>`).join('');
            
            const firstGoodId = finishedGoodsData.length > 0 ? finishedGoodsData[0].item_id : '';
            const firstGoodName = finishedGoodsData.length > 0 ? finishedGoodsData[0].item_name : 'Select Finished Good';
            const firstOpId = operatorsData.length > 0 ? operatorsData[0].user_id : '';
            const firstOpName = operatorsData.length > 0 ? operatorsData[0].full_name : 'Select Operator';

            const content = `
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
                    <h2 style="margin:0;">Start Production Batch</h2>
                    <button type="button" onclick="document.getElementById('generic-modal').classList.remove('active')" style="background:none; border:none; font-size:1.5rem; cursor:pointer; color:var(--text-primary);">&times;</button>
                </div>
                <form onsubmit="submitNewBatch(event)">
                    <div style="margin-bottom:1.5rem;">
                        <label style="display:block; margin-bottom:0.5rem; color:var(--text-secondary); font-size:0.9rem;">Finished Good Product *</label>
                        <div class="custom-select-wrapper">
                            <input type="hidden" name="item_id" value="${firstGoodId}" required>
                            <div class="custom-select">
                                <div class="custom-select-trigger">
                                    <span class="selected-text">${firstGoodName}</span>
                                    <i class="fa-solid fa-chevron-down"></i>
                                </div>
                            </div>
                            <div class="custom-options">
                                ${goodsOptionsHtml}
                            </div>
                        </div>
                    </div>
                    
                    <div style="display:flex; gap:1.5rem; margin-bottom:1.5rem;">
                        <div style="flex:1;">
                            <label style="display:block; margin-bottom:0.5rem; color:var(--text-secondary); font-size:0.9rem;">Target Quantity *</label>
                            <input type="number" step="0.01" name="target_quantity" required style="width:100%; padding:0.75rem; border:1px solid var(--border-color); border-radius:8px;">
                        </div>
                        <div style="flex:1;">
                            <label style="display:block; margin-bottom:0.5rem; color:var(--text-secondary); font-size:0.9rem;">Machine ID</label>
                            <input type="text" name="machine_id" placeholder="Optional" style="width:100%; padding:0.75rem; border:1px solid var(--border-color); border-radius:8px;">
                        </div>
                    </div>

                    <div style="display:flex; gap:1.5rem; margin-bottom:2rem;">
                        <div style="flex:1;">
                            <label style="display:block; margin-bottom:0.5rem; color:var(--text-secondary); font-size:0.9rem;">Operator *</label>
                            <div class="custom-select-wrapper">
                                <input type="hidden" name="operator_user_id" value="${firstOpId}" required>
                                <div class="custom-select">
                                    <div class="custom-select-trigger">
                                        <span class="selected-text">${firstOpName}</span>
                                        <i class="fa-solid fa-chevron-down"></i>
                                    </div>
                                </div>
                                <div class="custom-options">
                                    ${opsOptionsHtml}
                                </div>
                            </div>
                        </div>
                        <div style="flex:1;">
                            <label style="display:block; margin-bottom:0.5rem; color:var(--text-secondary); font-size:0.9rem;">Production Date *</label>
                            <input type="date" name="production_date" required value="${new Date().toISOString().split('T')[0]}" style="width:100%; padding:0.75rem; border:1px solid var(--border-color); border-radius:8px;">
                        </div>
                    </div>

                    <button type="submit" style="padding:1rem; background:var(--accent-primary); color:white; border:none; border-radius:8px; width:100%; cursor:pointer; font-weight:600; font-size:1rem;">
                        Assign & Start Batch
                    </button>
                </form>
            `;
            
            const modal = document.getElementById('generic-modal');
            modal.innerHTML = `<div class="modal-content" style="background:var(--bg-panel); padding:2.5rem; border-radius:16px; max-width:600px; margin:auto; box-shadow:var(--shadow-lg);">${content}</div>`;
            modal.classList.add('active');
            setTimeout(() => { if(window.initCustomSelects) window.initCustomSelects(modal); }, 50);
        }
    }

    async function submitNewBatch(e) {
        e.preventDefault();
        try {
            const formData = new FormData(e.target);
            const res = await fetch('<?= BASE_URL ?>/modules/production/create_batch.php', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) {
                window.location.reload();
            } else {
                alert('Error starting batch: ' + data.error);
            }
        } catch (err) { alert('Network error'); }
    }

    function updateBatchField(id, fieldName, allowedValues) {
        if (typeof showGenericModal === 'function') {
            const optionsHtml = allowedValues.map((v, i) => `<div class="custom-option ${i===0?'selected':''}" data-value="${v}">${v}</div>`).join('');
            const title = fieldName === 'status' ? 'Update Production Status' : 'Update QA Status';
            
            const content = `
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
                    <h2 style="margin:0;">${title}</h2>
                    <button type="button" onclick="document.getElementById('generic-modal').classList.remove('active')" style="background:none; border:none; font-size:1.5rem; cursor:pointer; color:var(--text-primary);">&times;</button>
                </div>
                <form onsubmit="submitUpdateBatchField(event, '${id}', '${fieldName}')">
                    <div style="margin-bottom:2rem;">
                        <label style="display:block; margin-bottom:0.5rem; color:var(--text-secondary); font-size:0.9rem;">Select New ${fieldName === 'status' ? 'Status' : 'QA Status'} *</label>
                        <div class="custom-select-wrapper">
                            <input type="hidden" name="new_val" value="${allowedValues[0]}" required>
                            <div class="custom-select">
                                <div class="custom-select-trigger">
                                    <span class="selected-text">${allowedValues[0]}</span>
                                    <i class="fa-solid fa-chevron-down"></i>
                                </div>
                            </div>
                            <div class="custom-options">
                                ${optionsHtml}
                            </div>
                        </div>
                    </div>
                    <button type="submit" style="padding:1rem; background:var(--accent-primary); color:white; border:none; border-radius:8px; width:100%; cursor:pointer; font-weight:600; font-size:1rem;">
                        Save Changes
                    </button>
                </form>
            `;
            
            const modal = document.getElementById('generic-modal');
            modal.innerHTML = `<div class="modal-content" style="background:var(--bg-panel); padding:2.5rem; border-radius:16px; max-width:450px; margin:auto; box-shadow:var(--shadow-lg);">${content}</div>`;
            modal.classList.add('active');
            setTimeout(() => { if(window.initCustomSelects) window.initCustomSelects(modal); }, 50);
        }
    }

    async function submitUpdateBatchField(e, id, fieldName) {
        e.preventDefault();
        const val = e.target.new_val.value;
        if (!val) return;
        try {
            const fd = new FormData();
            fd.append('production_id', id);
            fd.append(fieldName, val);
            const res = await fetch('<?= BASE_URL ?>/modules/production/update_batch.php', { method: 'POST', body: fd });
            const data = await res.json();
            if (data.success) window.location.reload();
            else alert('Error: ' + data.error);
        } catch (err) { alert('Network error'); }
    }

    function logBatchYield(id) {
        if (typeof showGenericModal === 'function') {
            const content = `
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
                    <h2 style="margin:0;">Log Actual Yield</h2>
                    <button type="button" onclick="document.getElementById('generic-modal').classList.remove('active')" style="background:none; border:none; font-size:1.5rem; cursor:pointer; color:var(--text-primary);">&times;</button>
                </div>
                <form onsubmit="submitLogYield(event, '${id}')">
                    <div style="margin-bottom:2rem;">
                        <label style="display:block; margin-bottom:0.5rem; color:var(--text-secondary); font-size:0.9rem;">Actual Yield Quantity *</label>
                        <input type="number" step="0.01" min="0" name="yield_qty" placeholder="e.g. 50.5" required style="width:100%; padding:0.75rem; border:1px solid var(--border-color); border-radius:8px; background:var(--bg-body); color:var(--text-primary);">
                    </div>
                    <button type="submit" style="padding:1rem; background:var(--success); color:white; border:none; border-radius:8px; width:100%; cursor:pointer; font-weight:600; font-size:1rem;">
                        Log Quantity
                    </button>
                </form>
            `;
            const modal = document.getElementById('generic-modal');
            modal.innerHTML = `<div class="modal-content" style="background:var(--bg-panel); padding:2.5rem; border-radius:16px; max-width:450px; margin:auto; box-shadow:var(--shadow-lg);">${content}</div>`;
            modal.classList.add('active');
        }
    }

    async function submitLogYield(e, id) {
        e.preventDefault();
        const val = e.target.yield_qty.value;
        if (!val) return;
        try {
            const fd = new FormData();
            fd.append('production_id', id);
            fd.append('actual_yield', val);
            const res = await fetch('<?= BASE_URL ?>/modules/production/update_batch.php', { method: 'POST', body: fd });
            const data = await res.json();
            if (data.success) window.location.reload();
            else alert('Error: ' + data.error);
        } catch (err) { alert('Network error'); }
    }

    async function deleteBatch(id) {
        if (!confirm('Are you sure you want to completely delete Production Batch ' + id + '?')) return;
        try {
            const fd = new FormData();
            fd.append('production_id', id);
            const res = await fetch('<?= BASE_URL ?>/modules/production/delete_batch.php', { method: 'POST', body: fd });
            const data = await res.json();
            if (data.success) window.location.reload();
            else alert('Error: ' + data.error);
        } catch (err) { alert('Network error'); }
    }
</script>