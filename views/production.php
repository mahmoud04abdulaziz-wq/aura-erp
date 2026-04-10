<?php
/**
 * MiskStone ERP — Production View
 * Displays production orders, machine status, and Recipe/BOM management.
 * Tables: production_orders, item_master, users, employees, recipes, recipe_ingredients
 */

$prodTab = $_GET['ptab'] ?? 'batches';

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

    $finishedGoods = $pdo->query("SELECT item_id, item_name FROM item_master WHERE category = 'Finished Good' ORDER BY item_name")->fetchAll(PDO::FETCH_ASSOC);
    $operators = $pdo->query("
        SELECT u.user_id, CONCAT(e.first_name, ' ', e.last_name) as full_name 
        FROM users u 
        JOIN employees e ON u.employee_id = e.employee_id 
        JOIN roles r ON u.role_id = r.role_id 
        WHERE r.role_name IN ('Production Manager', 'Executive Board')
        ORDER BY first_name
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Recipe data for the Recipe Editor tab
    $recipes = $pdo->query("
        SELECT r.recipe_id, r.recipe_name, r.base_yield_qty, r.curing_time_hours,
               im.item_name as fg_name, r.finished_item_id
        FROM recipes r
        JOIN item_master im ON r.finished_item_id = im.item_id
        ORDER BY r.recipe_name
    ")->fetchAll(PDO::FETCH_ASSOC);

    $rawMaterials = $pdo->query("SELECT item_id, item_name, base_uom FROM item_master WHERE category = 'Raw Material' ORDER BY item_name")->fetchAll(PDO::FETCH_ASSOC);

    // Ingredients for each recipe
    $allIngredients = [];
    $ingStmt = $pdo->query("
        SELECT ri.recipe_id, ri.raw_material_id, ri.quantity_required, im.item_name, im.base_uom
        FROM recipe_ingredients ri
        JOIN item_master im ON ri.raw_material_id = im.item_id
        ORDER BY ri.recipe_id, im.item_name
    ");
    foreach ($ingStmt->fetchAll(PDO::FETCH_ASSOC) as $ing) {
        $allIngredients[$ing['recipe_id']][] = $ing;
    }

} catch (Exception $e) {
    error_log("Production view error: " . $e->getMessage());
    $batches = $machines = $finishedGoods = $operators = $recipes = $rawMaterials = [];
    $allIngredients = [];
}
?>

<!-- Production Tab Navigation -->
<div class="card" style="margin-bottom: 1.5rem; padding: 0; background: var(--bg-panel);">
    <div style="display:flex; border-bottom:1px solid var(--border-color);">
        <a href="<?= BASE_URL ?>/app.php?view=production&ptab=batches" style="padding:1.25rem 1.5rem; text-decoration:none; font-weight:600; color: <?= $prodTab === 'batches' ? 'var(--accent-primary)' : 'var(--text-secondary)' ?>; border-bottom: 3px solid <?= $prodTab === 'batches' ? 'var(--accent-primary)' : 'transparent' ?>; display:flex; align-items:center; gap:0.5rem;">
            <i class="fa-solid fa-industry"></i> Production Batches
        </a>
        <a href="<?= BASE_URL ?>/app.php?view=production&ptab=recipes" style="padding:1.25rem 1.5rem; text-decoration:none; font-weight:600; color: <?= $prodTab === 'recipes' ? 'var(--accent-primary)' : 'var(--text-secondary)' ?>; border-bottom: 3px solid <?= $prodTab === 'recipes' ? 'var(--accent-primary)' : 'transparent' ?>; display:flex; align-items:center; gap:0.5rem;">
            <i class="fa-solid fa-flask"></i> Recipe / BOM Editor
        </a>
    </div>
</div>

<?php if ($prodTab === 'recipes'): ?>
<!-- ============================== -->
<!-- RECIPE / BOM EDITOR TAB       -->
<!-- ============================== -->
<div class="card" style="margin-bottom: 1.5rem;">
    <div class="card-header" style="justify-content:space-between;">
        <h3><i class="fa-solid fa-flask" style="color:var(--accent-primary); margin-right:0.5rem;"></i>Mixing Recipes (Bill of Materials)</h3>
        <button class="icon-btn" onclick="openNewRecipeModal()" style="width:auto; padding:0 1.5rem; background:var(--accent-primary); color:white; border:none; border-radius:8px; font-weight:600;">
            <i class="fa-solid fa-plus" style="margin-right:0.5rem;"></i> New Recipe
        </button>
    </div>

    <?php foreach ($recipes as $recipe): ?>
        <div style="border:1px solid var(--border-color); border-radius:12px; padding:1.5rem; margin-bottom:1rem; background:var(--bg-body);">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
                <div>
                    <h4 style="margin:0; color:var(--text-primary);"><?= htmlspecialchars($recipe['recipe_name']) ?></h4>
                    <span style="font-size:0.85rem; color:var(--text-secondary);">
                        Produces: <strong><?= htmlspecialchars($recipe['fg_name']) ?></strong> · 
                        Base Yield: <strong><?= $recipe['base_yield_qty'] ?> units</strong> ·
                        Curing: <strong><?= $recipe['curing_time_hours'] ?>h</strong>
                    </span>
                </div>
                <button onclick="openAddIngredientModal(<?= $recipe['recipe_id'] ?>, '<?= addslashes($recipe['recipe_name']) ?>')" 
                    style="padding:0.4rem 0.8rem; background:var(--success); color:white; border:none; border-radius:6px; cursor:pointer; font-size:0.8rem; font-weight:600;">
                    <i class="fa-solid fa-plus"></i> Add Ingredient
                </button>
            </div>
            
            <table class="data-table" style="font-size:0.9rem;">
                <thead>
                    <tr style="background:var(--bg-panel);">
                        <th>Raw Material</th>
                        <th>Quantity Required</th>
                        <th>UOM</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($allIngredients[$recipe['recipe_id']])): ?>
                        <?php foreach ($allIngredients[$recipe['recipe_id']] as $ing): ?>
                            <tr>
                                <td style="font-weight:600;"><?= htmlspecialchars($ing['item_name']) ?></td>
                                <td><?= number_format($ing['quantity_required'], 3) ?></td>
                                <td><span class="badge gray"><?= htmlspecialchars($ing['base_uom']) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="3" style="text-align:center; color:var(--text-secondary); padding:1rem;">No ingredients defined yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php endforeach; ?>

    <?php if (empty($recipes)): ?>
        <div style="text-align:center; padding:3rem; color:var(--text-secondary);">
            <i class="fa-solid fa-flask" style="font-size:2rem; opacity:0.3; margin-bottom:1rem;"></i>
            <p>No recipes found. Click "New Recipe" to create your first mixing recipe.</p>
        </div>
    <?php endif; ?>
</div>

<script>
const rawMaterialsData = <?= json_encode($rawMaterials) ?>;
const finishedGoodsDataR = <?= json_encode($finishedGoods) ?>;

function openNewRecipeModal() {
    const fgOpts = finishedGoodsDataR.map((g, i) => `<option value="${g.item_id}" ${i===0?'selected':''}>${g.item_name}</option>`).join('');
    
    const content = `
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
            <h2 style="margin:0;">Create New Recipe</h2>
            <button type="button" onclick="document.getElementById('generic-modal').classList.remove('active')" style="background:none; border:none; font-size:1.5rem; cursor:pointer;">&times;</button>
        </div>
        <form onsubmit="submitNewRecipe(event)">
            <div style="margin-bottom:1rem;">
                <label style="display:block; margin-bottom:0.5rem; font-weight:600;">Recipe Name *</label>
                <input type="text" name="recipe_name" required placeholder="e.g. Standard Marble Mix" style="width:100%; padding:0.75rem; border:1px solid var(--border-color); border-radius:8px; background:var(--bg-body); color:var(--text-primary);">
            </div>
            <div style="margin-bottom:1rem;">
                <label style="display:block; margin-bottom:0.5rem; font-weight:600;">Target Finished Good *</label>
                <select name="finished_item_id" required style="width:100%; padding:0.75rem; border:1px solid var(--border-color); border-radius:8px; background:var(--bg-body); color:var(--text-primary);">
                    ${fgOpts}
                </select>
            </div>
            <div style="display:flex; gap:1rem; margin-bottom:1.5rem;">
                <div style="flex:1;">
                    <label style="display:block; margin-bottom:0.5rem; font-weight:600;">Base Yield Qty *</label>
                    <input type="number" step="0.01" name="base_yield_qty" required placeholder="100" style="width:100%; padding:0.75rem; border:1px solid var(--border-color); border-radius:8px; background:var(--bg-body); color:var(--text-primary);">
                </div>
                <div style="flex:1;">
                    <label style="display:block; margin-bottom:0.5rem; font-weight:600;">Curing Time (hrs)</label>
                    <input type="number" name="curing_time_hours" value="24" style="width:100%; padding:0.75rem; border:1px solid var(--border-color); border-radius:8px; background:var(--bg-body); color:var(--text-primary);">
                </div>
            </div>
            <button type="submit" style="padding:1rem; background:var(--accent-primary); color:white; border:none; border-radius:8px; width:100%; cursor:pointer; font-weight:600;">Create Recipe</button>
        </form>
    `;
    const modal = document.getElementById('generic-modal');
    modal.innerHTML = `<div class="modal-content" style="background:var(--bg-panel); padding:2.5rem; border-radius:16px; max-width:500px; margin:auto; box-shadow:var(--shadow-lg);">${content}</div>`;
    modal.classList.add('active');
}

function openAddIngredientModal(recipeId, recipeName) {
    const rmOpts = rawMaterialsData.map(r => `<option value="${r.item_id}">${r.item_name} (${r.base_uom})</option>`).join('');
    
    const content = `
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
            <h2 style="margin:0;">Add Ingredient to: ${recipeName}</h2>
            <button type="button" onclick="document.getElementById('generic-modal').classList.remove('active')" style="background:none; border:none; font-size:1.5rem; cursor:pointer;">&times;</button>
        </div>
        <form onsubmit="submitAddIngredient(event, ${recipeId})">
            <div style="margin-bottom:1rem;">
                <label style="display:block; margin-bottom:0.5rem; font-weight:600;">Raw Material *</label>
                <select name="raw_material_id" required style="width:100%; padding:0.75rem; border:1px solid var(--border-color); border-radius:8px; background:var(--bg-body); color:var(--text-primary);">
                    ${rmOpts}
                </select>
            </div>
            <div style="margin-bottom:1.5rem;">
                <label style="display:block; margin-bottom:0.5rem; font-weight:600;">Quantity Required (per base yield batch) *</label>
                <input type="number" step="0.001" name="quantity_required" required placeholder="e.g. 250.000" style="width:100%; padding:0.75rem; border:1px solid var(--border-color); border-radius:8px; background:var(--bg-body); color:var(--text-primary);">
            </div>
            <button type="submit" style="padding:1rem; background:var(--success); color:white; border:none; border-radius:8px; width:100%; cursor:pointer; font-weight:600;">Add to Recipe</button>
        </form>
    `;
    const modal = document.getElementById('generic-modal');
    modal.innerHTML = `<div class="modal-content" style="background:var(--bg-panel); padding:2.5rem; border-radius:16px; max-width:500px; margin:auto; box-shadow:var(--shadow-lg);">${content}</div>`;
    modal.classList.add('active');
}

async function submitNewRecipe(e) {
    e.preventDefault();
    try {
        const fd = new FormData(e.target);
        const res = await fetch('<?= BASE_URL ?>/modules/production/create_recipe.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) window.location.reload();
        else alert('Error: ' + data.error);
    } catch(err) { alert('Network error'); }
}

async function submitAddIngredient(e, recipeId) {
    e.preventDefault();
    try {
        const fd = new FormData(e.target);
        fd.append('recipe_id', recipeId);
        const res = await fetch('<?= BASE_URL ?>/modules/production/add_ingredient.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) window.location.reload();
        else alert('Error: ' + data.error);
    } catch(err) { alert('Network error'); }
}
</script>

<?php else: ?>
<!-- ============================== -->
<!-- PRODUCTION BATCHES TAB         -->
<!-- ============================== -->

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
<?php endif; ?>