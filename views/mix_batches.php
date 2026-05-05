<?php
/**
 * MiskStone ERP — Mix Batches (Rebuilt)
 * Production batch management with:
 *   - Inline stage progression: Planned → Mixing → Curing → Completed
 *   - BOM feasibility check on "Start Mixing" (deducts raw materials)
 *   - FG credited to inventory on "Complete"
 */

// Fetch all production orders with recipe info
$batches = $pdo->query("
    SELECT po.*, im.item_name, r.recipe_name, r.recipe_id as linked_recipe_id,
           so.so_id as linked_so_id, c.company_name as so_company
    FROM production_orders po 
    JOIN item_master im ON po.item_id = im.item_id
    LEFT JOIN recipes r ON po.recipe_id = r.recipe_id
    LEFT JOIN sales_orders so ON po.so_id = so.so_id
    LEFT JOIN customers c ON so.customer_id = c.customer_id
    ORDER BY 
        CASE po.status WHEN 'Mixing' THEN 0 WHEN 'Curing' THEN 1 WHEN 'Planned' THEN 2 ELSE 3 END,
        po.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Fetch recipes for the "Start Batch" form
$recipes = $pdo->query("SELECT recipe_id, recipe_name FROM recipes ORDER BY recipe_id")->fetchAll(PDO::FETCH_ASSOC);

// Fetch operators
$operators = $pdo->query("
    SELECT u.user_id, CONCAT(e.first_name, ' ', e.last_name) as full_name 
    FROM users u 
    JOIN employees e ON u.employee_id = e.employee_id 
    JOIN roles r ON u.role_id = r.role_id 
    WHERE r.role_name IN ('Production Manager', 'Executive Board')
    ORDER BY first_name
")->fetchAll(PDO::FETCH_ASSOC);

// Stats
$stats = [
    'planned'   => count(array_filter($batches, fn($b) => $b['status'] === 'Planned')),
    'mixing'    => count(array_filter($batches, fn($b) => $b['status'] === 'Mixing')),
    'curing'    => count(array_filter($batches, fn($b) => $b['status'] === 'Curing')),
    'completed' => count(array_filter($batches, fn($b) => $b['status'] === 'Completed')),
];
?>

<div class="dashboard-container">
    <div class="welcome-banner" style="background: linear-gradient(135deg, #1e293b, #7c3aed); color: white; padding: 2rem; border-radius: 16px; margin-bottom: 2rem;">
        <h1 style="margin-bottom: 0.5rem;"><i class="fa-solid fa-industry" style="margin-right: 0.75rem;"></i>Mix Batches</h1>
        <p style="opacity: 0.9;">Manage production batches through each stage. Starting a mix auto-checks BOM feasibility and deducts raw materials.</p>
    </div>

    <!-- Stage Stats -->
    <div class="stats-grid" style="margin-bottom: 2rem; display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem;">
        <div class="stat-card" style="border-left: 4px solid #94a3b8;">
            <div class="stat-info"><h3 style="color: var(--text-secondary); font-size: 0.85rem;">Planned</h3><p class="stat-value"><?= $stats['planned'] ?></p></div>
        </div>
        <div class="stat-card" style="border-left: 4px solid #f59e0b;">
            <div class="stat-info"><h3 style="color: var(--text-secondary); font-size: 0.85rem;">Mixing</h3><p class="stat-value"><?= $stats['mixing'] ?></p></div>
        </div>
        <div class="stat-card" style="border-left: 4px solid #3b82f6;">
            <div class="stat-info"><h3 style="color: var(--text-secondary); font-size: 0.85rem;">Curing</h3><p class="stat-value"><?= $stats['curing'] ?></p></div>
        </div>
        <div class="stat-card" style="border-left: 4px solid #22c55e;">
            <div class="stat-info"><h3 style="color: var(--text-secondary); font-size: 0.85rem;">Completed</h3><p class="stat-value"><?= $stats['completed'] ?></p></div>
        </div>
    </div>

    <!-- New Batch Button -->
    <div style="margin-bottom: 1.5rem; display: flex; justify-content: flex-end;">
        <button onclick="openNewBatchModal()" style="background: var(--accent-primary); color: white; border: none; padding: 0.7rem 1.5rem; border-radius: 10px; font-weight: 700; cursor: pointer; font-size: 0.95rem;">
            <i class="fa-solid fa-plus"></i> New Production Batch
        </button>
    </div>

    <!-- Batch Cards -->
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(380px, 1fr)); gap: 1.25rem;">
        <?php foreach ($batches as $batch): ?>
            <?php
            $stageColors = [
                'Planned'   => ['bg' => '#f1f5f9', 'border' => '#94a3b8', 'icon' => 'fa-calendar-check', 'label' => 'Planned'],
                'Mixing'    => ['bg' => '#fef3c7', 'border' => '#f59e0b', 'icon' => 'fa-blender',       'label' => 'Mixing'],
                'Curing'    => ['bg' => '#dbeafe', 'border' => '#3b82f6', 'icon' => 'fa-hourglass-half', 'label' => 'Curing'],
                'Completed' => ['bg' => '#dcfce7', 'border' => '#22c55e', 'icon' => 'fa-circle-check',  'label' => 'Completed'],
                'Failed'    => ['bg' => '#fef2f2', 'border' => '#ef4444', 'icon' => 'fa-times-circle',   'label' => 'Failed'],
            ];
            $stage = $stageColors[$batch['status']] ?? $stageColors['Planned'];
            ?>
            <div class="card" style="border-left: 5px solid <?= $stage['border'] ?>; padding: 0; overflow: hidden;">
                <!-- Header -->
                <div style="padding: 1rem 1.25rem; display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <span style="font-weight: 800; font-family: monospace; font-size: 1rem; color: var(--text-primary);"><?= htmlspecialchars($batch['production_id']) ?></span>
                        <span style="margin-left: 0.75rem; font-size: 0.8rem; padding: 0.2rem 0.5rem; border-radius: 6px; background: <?= $stage['bg'] ?>; color: <?= $stage['border'] ?>; font-weight: 700;">
                            <i class="fa-solid <?= $stage['icon'] ?>"></i> <?= $stage['label'] ?>
                        </span>
                    </div>
                    <?php if ($batch['qa_status'] && $batch['qa_status'] !== 'Pending'): ?>
                        <span style="font-size: 0.75rem; padding: 0.15rem 0.4rem; border-radius: 4px; background: <?= $batch['qa_status'] === 'Passed' ? '#dcfce7' : '#fef2f2' ?>; color: <?= $batch['qa_status'] === 'Passed' ? '#22c55e' : '#ef4444' ?>; font-weight: 700;">
                            QA: <?= $batch['qa_status'] ?>
                        </span>
                    <?php endif; ?>
                </div>

                <!-- Details -->
                <div style="padding: 0 1.25rem 0.75rem;">
                    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; font-size: 0.85rem; color: var(--text-secondary);">
                        <span><i class="fa-solid fa-flask" style="color: var(--accent-primary);"></i> <?= htmlspecialchars($batch['recipe_name'] ?? $batch['item_name']) ?></span>
                        <span style="opacity: 0.5;">·</span>
                        <span><i class="fa-solid fa-layer-group"></i> <?= (int)$batch['target_quantity'] ?> mix run(s)</span>
                        <?php if ($batch['production_date']): ?>
                            <span style="opacity: 0.5;">·</span>
                            <span><i class="fa-solid fa-calendar"></i> <?= date('M d', strtotime($batch['production_date'])) ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Linked Sales Order (Kanban) -->
                <?php if (!empty($batch['linked_so_id'])): ?>
                    <div style="padding: 0 1.25rem 0.75rem;">
                        <div style="display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.3rem 0.75rem; border-radius: 6px; background: #eef2ff; border: 1px solid #c7d2fe; font-size: 0.8rem;">
                            <i class="fa-solid fa-link" style="color: #6366f1;"></i>
                            <span style="font-weight: 700; color: #4338ca;"><?= htmlspecialchars($batch['linked_so_id']) ?></span>
                            <?php if (!empty($batch['so_company'])): ?>
                                <span style="color: #6366f1; opacity: 0.7;">· <?= htmlspecialchars($batch['so_company']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Stage Progression Bar -->
                <div style="padding: 0 1.25rem 0.75rem;">
                    <?php
                    $stages = ['Planned', 'Mixing', 'Curing', 'Completed'];
                    $currentIdx = array_search($batch['status'], $stages);
                    if ($currentIdx === false) $currentIdx = -1;
                    ?>
                    <div style="display: flex; gap: 3px;">
                        <?php foreach ($stages as $i => $s): ?>
                            <div style="flex: 1; height: 5px; border-radius: 3px; background: <?= $i <= $currentIdx ? $stage['border'] : '#e2e8f0' ?>;"></div>
                        <?php endforeach; ?>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-top: 0.25rem;">
                        <?php foreach ($stages as $i => $s): ?>
                            <span style="font-size: 0.65rem; color: <?= $i <= $currentIdx ? $stage['border'] : '#cbd5e1' ?>; font-weight: <?= $i === $currentIdx ? '700' : '400' ?>;"><?= $s ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div style="padding: 0.75rem 1.25rem; background: #f8fafc; border-top: 1px solid var(--border-color); display: flex; gap: 0.5rem; align-items: center;">
                    <?php if ($batch['status'] === 'Planned'): ?>
                        <button onclick="bomCheck('<?= htmlspecialchars($batch['production_id']) ?>')" 
                                style="flex: 1; background: #f59e0b; color: white; border: none; padding: 0.6rem; border-radius: 8px; font-weight: 700; cursor: pointer; font-size: 0.85rem;">
                            <i class="fa-solid fa-play"></i> Start Mixing
                        </button>
                    <?php elseif ($batch['status'] === 'Mixing'): ?>
                        <button onclick="advanceStage('<?= htmlspecialchars($batch['production_id']) ?>', 'Curing')" 
                                style="flex: 1; background: #3b82f6; color: white; border: none; padding: 0.6rem; border-radius: 8px; font-weight: 700; cursor: pointer; font-size: 0.85rem;">
                            <i class="fa-solid fa-forward"></i> Start Curing
                        </button>
                    <?php elseif ($batch['status'] === 'Curing'): ?>
                        <button onclick="advanceStage('<?= htmlspecialchars($batch['production_id']) ?>', 'Completed')" 
                                style="flex: 1; background: #22c55e; color: white; border: none; padding: 0.6rem; border-radius: 8px; font-weight: 700; cursor: pointer; font-size: 0.85rem;">
                            <i class="fa-solid fa-circle-check"></i> Mark Complete
                        </button>
                    <?php elseif ($batch['status'] === 'Completed'): ?>
                        <span style="flex: 1; text-align: center; color: #22c55e; font-weight: 700; font-size: 0.85rem;">
                            <i class="fa-solid fa-circle-check"></i> Production Complete
                        </span>
                    <?php elseif ($batch['status'] === 'Failed'): ?>
                        <span style="flex: 1; text-align: center; color: #ef4444; font-weight: 700; font-size: 0.85rem;">
                            <i class="fa-solid fa-times-circle"></i> Failed
                        </span>
                    <?php endif; ?>

                    <?php if (!in_array($batch['status'], ['Completed', 'Failed'])): ?>
                        <button onclick="advanceStage('<?= htmlspecialchars($batch['production_id']) ?>', 'Failed')" 
                                style="background: #fee2e2; color: #991b1b; border: none; padding: 0.6rem 0.8rem; border-radius: 8px; cursor: pointer; font-size: 0.8rem; font-weight: 600;" title="Mark as Failed">
                            <i class="fa-solid fa-ban"></i>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if (empty($batches)): ?>
            <div class="card" style="grid-column: 1/-1; text-align: center; padding: 4rem;">
                <i class="fa-solid fa-industry" style="font-size: 3rem; color: var(--text-secondary); opacity: 0.3; margin-bottom: 1rem; display: block;"></i>
                <h3 style="color: var(--text-secondary); margin-bottom: 0.5rem;">No Production Batches</h3>
                <p style="color: var(--text-secondary);">Click "New Production Batch" to start your first mix.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- BOM Feasibility Modal -->
<div id="bomModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6); z-index:2000; align-items:center; justify-content:center;">
    <div style="background:var(--bg-panel); border-radius:16px; max-width:700px; width:95%; max-height:85vh; overflow-y:auto; padding:2rem; box-shadow: 0 25px 50px rgba(0,0,0,0.3);">
        <div id="bomContent">Loading BOM check...</div>
    </div>
</div>

<script>
const recipesData = <?= json_encode($recipes) ?>;
const operatorsData = <?= json_encode($operators) ?>;

// ========== BOM FEASIBILITY CHECK ==========
function bomCheck(prodId) {
    const modal = document.getElementById('bomModal');
    const content = document.getElementById('bomContent');
    modal.style.display = 'flex';
    content.innerHTML = '<div style="text-align:center; padding:2rem;"><i class="fa-solid fa-spinner fa-spin" style="font-size:2rem; color:var(--accent-primary);"></i><p style="margin-top:1rem;">Running BOM feasibility check...</p></div>';

    fetch('<?= BASE_URL ?>/modules/production/bom_check.php?production_id=' + encodeURIComponent(prodId))
        .then(r => r.json())
        .then(data => {
            if (data.error) {
                content.innerHTML = `<div style="color:#ef4444; padding:1rem;"><h3>Error</h3><p>${data.error}</p><button onclick="closeBomModal()" style="margin-top:1rem; padding:0.5rem 1rem; border-radius:8px; border:1px solid var(--border-color); cursor:pointer;">Close</button></div>`;
                return;
            }
            renderBomResult(prodId, data);
        })
        .catch(() => {
            content.innerHTML = '<div style="color:#ef4444; padding:1rem;">Network error. Please try again.</div>';
        });
}

function renderBomResult(prodId, data) {
    const content = document.getElementById('bomContent');
    const feasible = data.feasible;
    const materials = data.materials;
    const totalCost = data.total_cost;

    let materialRows = materials.map(m => {
        const shortfall = m.current_stock < m.required ? (m.required - m.current_stock) : 0;
        const statusIcon = shortfall > 0 
            ? `<span style="color:#ef4444; font-weight:700;">⚠ Need ${shortfall.toFixed(1)} more</span>`
            : `<span style="color:#22c55e; font-weight:700;">✅ OK</span>`;
        return `<tr>
            <td style="font-weight:600;">${m.item_name}</td>
            <td style="text-align:right;">${m.required.toFixed(3)} ${m.uom}</td>
            <td style="text-align:right;">${m.current_stock.toFixed(1)}</td>
            <td>${statusIcon}</td>
            <td style="text-align:right; font-weight:600;">$${m.cost.toFixed(2)}</td>
        </tr>`;
    }).join('');

    let headerHtml = feasible
        ? `<div style="background:#dcfce7; padding:1rem; border-radius:10px; margin-bottom:1.5rem; display:flex; align-items:center; gap:0.75rem;">
                <i class="fa-solid fa-circle-check" style="font-size:1.5rem; color:#22c55e;"></i>
                <div><strong style="color:#166534;">Feasible — All Materials Available</strong><br><span style="font-size:0.85rem; color:#15803d;">Total BOM Cost: <strong>$${totalCost.toFixed(2)}</strong></span></div>
           </div>`
        : `<div style="background:#fef2f2; padding:1rem; border-radius:10px; margin-bottom:1.5rem; display:flex; align-items:center; gap:0.75rem;">
                <i class="fa-solid fa-triangle-exclamation" style="font-size:1.5rem; color:#ef4444;"></i>
                <div><strong style="color:#991b1b;">Not Feasible — Material Shortage</strong><br><span style="font-size:0.85rem; color:#b91c1c;">Some raw materials are insufficient. Request procurement below.</span></div>
           </div>`;

    let actionHtml = feasible
        ? `<button onclick="confirmStartMixing('${prodId}')" style="width:100%; background:#f59e0b; color:white; border:none; padding:0.85rem; border-radius:10px; font-weight:700; cursor:pointer; font-size:1rem;">
                <i class="fa-solid fa-play"></i> Confirm — Start Mixing (Deduct Materials)
           </button>`
        : `<div style="display:flex; gap:0.5rem;">
                <button onclick="requestBulkProcurement('${prodId}')" style="flex:1; background:#7c3aed; color:white; border:none; padding:0.85rem; border-radius:10px; font-weight:700; cursor:pointer; font-size:0.95rem;">
                    <i class="fa-solid fa-paper-plane"></i> Request Procurement for All Shortages
                </button>
                <button onclick="closeBomModal()" style="background:#e2e8f0; color:#475569; border:none; padding:0.85rem 1.2rem; border-radius:10px; font-weight:700; cursor:pointer;">Cancel</button>
           </div>`;

    content.innerHTML = `
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
            <h2 style="margin:0;"><i class="fa-solid fa-flask" style="color:var(--accent-primary);margin-right:0.5rem;"></i>BOM Feasibility — ${prodId}</h2>
            <button onclick="closeBomModal()" style="background:none; border:none; font-size:1.5rem; cursor:pointer; color:var(--text-secondary);">&times;</button>
        </div>
        <p style="color:var(--text-secondary); font-size:0.85rem; margin-bottom:1rem;">Recipe: <strong>${data.recipe_name}</strong> · Mix Runs: <strong>${data.mix_runs}</strong></p>
        ${headerHtml}
        <table style="width:100%; border-collapse:collapse; font-size:0.85rem; margin-bottom:1.5rem;">
            <thead>
                <tr style="border-bottom:2px solid var(--border-color);">
                    <th style="text-align:left; padding:0.5rem 0;">Material</th>
                    <th style="text-align:right; padding:0.5rem 0;">Required</th>
                    <th style="text-align:right; padding:0.5rem 0;">In Stock</th>
                    <th style="padding:0.5rem 0;">Status</th>
                    <th style="text-align:right; padding:0.5rem 0;">Cost</th>
                </tr>
            </thead>
            <tbody>${materialRows}</tbody>
            <tfoot>
                <tr style="border-top:2px solid var(--border-color); font-weight:700;">
                    <td colspan="4" style="padding:0.5rem 0;">Total Material Cost</td>
                    <td style="text-align:right; padding:0.5rem 0; color:var(--accent-primary);">$${totalCost.toFixed(2)}</td>
                </tr>
            </tfoot>
        </table>
        ${actionHtml}
    `;
}

function closeBomModal() {
    document.getElementById('bomModal').style.display = 'none';
}

// Close on backdrop click
document.getElementById('bomModal').addEventListener('click', function(e) {
    if (e.target === this) closeBomModal();
});

// ========== ADVANCE STAGE ==========
function advanceStage(prodId, newStatus) {
    const labels = { 'Curing': 'Start Curing', 'Completed': 'Mark as Complete', 'Failed': 'Mark as Failed' };
    if (!confirm(`${labels[newStatus] || newStatus} for batch ${prodId}?`)) return;

    const fd = new FormData();
    fd.append('production_id', prodId);
    fd.append('status', newStatus);
    if (newStatus === 'Completed') fd.append('actual_yield', '1');

    fetch('<?= BASE_URL ?>/modules/production/update_batch.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) location.reload();
            else alert('Error: ' + (data.error || 'Unknown'));
        })
        .catch(() => alert('Network error'));
}

// ========== CONFIRM START MIXING ==========
function confirmStartMixing(prodId) {
    const fd = new FormData();
    fd.append('production_id', prodId);
    fd.append('status', 'Mixing');

    fetch('<?= BASE_URL ?>/modules/production/update_batch.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                closeBomModal();
                location.reload();
            } else {
                alert('Error: ' + (data.error || 'Unknown'));
            }
        })
        .catch(() => alert('Network error'));
}

// ========== REQUEST BULK PROCUREMENT ==========
function requestBulkProcurement(prodId) {
    fetch('<?= BASE_URL ?>/modules/production/request_bulk_materials.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'production_id=' + encodeURIComponent(prodId)
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            closeBomModal();
            alert(data.message || 'Material requests sent to Procurement!');
            location.reload();
        } else {
            alert('Error: ' + (data.error || 'Unknown'));
        }
    });
}

// ========== NEW BATCH MODAL ==========
function openNewBatchModal() {
    const recipeOpts = recipesData.map(r => `<option value="${r.recipe_id}">${r.recipe_name}</option>`).join('');
    const opOpts = operatorsData.map(o => `<option value="${o.user_id}">${o.full_name}</option>`).join('');

    const modal = document.getElementById('generic-modal');
    modal.innerHTML = `<div class="modal-content" style="background:var(--bg-panel); padding:2.5rem; border-radius:16px; max-width:550px; margin:auto; box-shadow:var(--shadow-lg);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
            <h2 style="margin:0;">New Production Batch</h2>
            <button onclick="document.getElementById('generic-modal').classList.remove('active')" style="background:none; border:none; font-size:1.5rem; cursor:pointer;">&times;</button>
        </div>
        <form onsubmit="submitNewBatch(event)">
            <div style="margin-bottom:1rem;">
                <label style="display:block; margin-bottom:0.4rem; font-weight:600; font-size:0.85rem; color:var(--text-secondary);">Recipe *</label>
                <select name="recipe_id" required style="width:100%; padding:0.7rem; border:1px solid var(--border-color); border-radius:8px; font-size:0.95rem;">${recipeOpts}</select>
            </div>
            <div style="display:flex; gap:1rem; margin-bottom:1rem;">
                <div style="flex:1;">
                    <label style="display:block; margin-bottom:0.4rem; font-weight:600; font-size:0.85rem; color:var(--text-secondary);">Mix Runs *</label>
                    <input type="number" name="target_quantity" value="1" min="1" required style="width:100%; padding:0.7rem; border:1px solid var(--border-color); border-radius:8px;">
                </div>
                <div style="flex:1;">
                    <label style="display:block; margin-bottom:0.4rem; font-weight:600; font-size:0.85rem; color:var(--text-secondary);">Machine ID</label>
                    <input type="text" name="machine_id" placeholder="Optional" style="width:100%; padding:0.7rem; border:1px solid var(--border-color); border-radius:8px;">
                </div>
            </div>
            <div style="display:flex; gap:1rem; margin-bottom:1.5rem;">
                <div style="flex:1;">
                    <label style="display:block; margin-bottom:0.4rem; font-weight:600; font-size:0.85rem; color:var(--text-secondary);">Operator *</label>
                    <select name="operator_user_id" required style="width:100%; padding:0.7rem; border:1px solid var(--border-color); border-radius:8px; font-size:0.95rem;">${opOpts}</select>
                </div>
                <div style="flex:1;">
                    <label style="display:block; margin-bottom:0.4rem; font-weight:600; font-size:0.85rem; color:var(--text-secondary);">Date *</label>
                    <input type="date" name="production_date" required value="${new Date().toISOString().split('T')[0]}" style="width:100%; padding:0.7rem; border:1px solid var(--border-color); border-radius:8px;">
                </div>
            </div>
            <button type="submit" style="width:100%; padding:0.85rem; background:var(--accent-primary); color:white; border:none; border-radius:10px; font-weight:700; font-size:1rem; cursor:pointer;">
                <i class="fa-solid fa-plus"></i> Create Batch
            </button>
        </form>
    </div>`;
    modal.classList.add('active');
}

async function submitNewBatch(e) {
    e.preventDefault();
    try {
        const fd = new FormData(e.target);
        const res = await fetch('<?= BASE_URL ?>/modules/production/create_batch.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            document.getElementById('generic-modal').classList.remove('active');
            location.reload();
        } else {
            alert('Error: ' + (data.error || 'Unknown'));
        }
    } catch(err) { alert('Network error'); }
}
</script>
