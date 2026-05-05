<?php
/**
 * MiskStone ERP — Production Orders (Smart Optimizer)
 * View pending sales orders and optimize production planning.
 * Features the Smart Production Optimizer with Fastest vs Cheapest strategies.
 */

// Pending orders (eligible to start production)
$pendingOrders = $pdo->query("
    SELECT so.so_id, so.total_price, so.order_date, so.order_status,
           c.company_name, c.contact_person
    FROM sales_orders so
    JOIN customers c ON so.customer_id = c.customer_id
    WHERE so.order_status = 'Pending'
    ORDER BY so.order_date ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Orders already in production
$inProdOrders = $pdo->query("
    SELECT so.so_id, so.total_price, so.order_date, so.order_status,
           c.company_name, c.contact_person
    FROM sales_orders so
    JOIN customers c ON so.customer_id = c.customer_id
    WHERE so.order_status = 'In Production'
    ORDER BY so.order_date ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Get order items for display
function getOrderItems($pdo, $soId) {
    $stmt = $pdo->prepare("
        SELECT soi.*, im.item_name 
        FROM sales_order_lines soi
        JOIN item_master im ON soi.item_id = im.item_id
        WHERE soi.so_id = ?
    ");
    $stmt->execute([$soId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<div class="dashboard-container">
    <div class="welcome-banner" style="background: linear-gradient(135deg, #1e293b, #3b82f6); color: white; padding: 2rem; border-radius: 16px; margin-bottom: 2rem;">
        <h1 style="margin-bottom: 0.5rem;"><i class="fa-solid fa-clipboard-list" style="margin-right: 0.75rem;"></i>Production Orders</h1>
        <p style="opacity: 0.9;">Select pending orders to optimize and move into production using the <strong>Smart Production Optimizer</strong>.</p>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">

        <!-- LEFT: Pending Orders -->
        <div>
            <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1rem;">
                <div style="width: 12px; height: 12px; border-radius: 50%; background: #94a3b8;"></div>
                <h3 style="margin: 0;">Pending Orders</h3>
                <span class="badge gray"><?= count($pendingOrders) ?></span>
            </div>

            <?php if (empty($pendingOrders)): ?>
                <div class="card" style="text-align: center; padding: 3rem; opacity: 0.6;">
                    <i class="fa-solid fa-check-double" style="font-size: 2rem; display: block; margin-bottom: 0.5rem;"></i>
                    <p>All orders have been moved to production</p>
                </div>
            <?php endif; ?>

            <?php foreach ($pendingOrders as $order): ?>
                <?php $items = getOrderItems($pdo, $order['so_id']); ?>
                <div class="card" id="order-<?= $order['so_id'] ?>" style="margin-bottom: 1rem; border-left: 4px solid #94a3b8; padding: 0; overflow: hidden;">
                    <div style="padding: 1.25rem;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                            <span style="font-weight: 800; font-family: monospace; color: var(--accent-primary);"><?= htmlspecialchars($order['so_id']) ?></span>
                            <span class="badge gray">Pending</span>
                        </div>
                        <div style="font-weight: 600; margin-bottom: 0.25rem;"><?= htmlspecialchars($order['company_name']) ?></div>
                        <div style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 0.75rem;">
                            <i class="fa-solid fa-calendar"></i> <?= date('M d, Y', strtotime($order['order_date'])) ?>
                            <span style="margin: 0 0.3rem; opacity: 0.5;">·</span>
                            <span style="font-weight: 700; color: var(--accent-primary);"><?= number_format($order['total_price'], 2) ?> JOD</span>
                        </div>

                        <!-- Order Items -->
                        <?php if (!empty($items)): ?>
                            <div style="background: #f8fafc; border-radius: 6px; padding: 0.5rem 0.75rem; margin-bottom: 0.5rem;">
                                <?php foreach ($items as $item): ?>
                                    <div style="font-size: 0.8rem; padding: 0.15rem 0; display: flex; justify-content: space-between;">
                                        <span style="font-weight: 600;"><?= htmlspecialchars($item['item_name']) ?></span>
                                        <span style="color: var(--text-secondary);">×<?= (int)$item['quantity'] ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div style="padding: 0.75rem 1.25rem; background: #f8fafc; border-top: 1px solid var(--border-color);">
                        <button onclick="openOptimizer('<?= htmlspecialchars($order['so_id']) ?>')" 
                                style="width: 100%; background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white; border: none; padding: 0.7rem; border-radius: 8px; font-weight: 700; cursor: pointer; font-size: 0.85rem; display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                            <i class="fa-solid fa-wand-magic-sparkles"></i> Optimize & Plan Production
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- RIGHT: In Production -->
        <div>
            <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1rem;">
                <div style="width: 12px; height: 12px; border-radius: 50%; background: #3b82f6;"></div>
                <h3 style="margin: 0;">In Production</h3>
                <span class="badge" style="background: #3b82f620; color: #3b82f6;"><?= count($inProdOrders) ?></span>
            </div>

            <?php if (empty($inProdOrders)): ?>
                <div class="card" style="text-align: center; padding: 3rem; opacity: 0.6;">
                    <i class="fa-solid fa-industry" style="font-size: 2rem; display: block; margin-bottom: 0.5rem;"></i>
                    <p>No orders currently in production</p>
                </div>
            <?php endif; ?>

            <?php foreach ($inProdOrders as $order): ?>
                <?php $items = getOrderItems($pdo, $order['so_id']); ?>
                <div class="card" style="margin-bottom: 1rem; border-left: 4px solid #3b82f6; padding: 1.25rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                        <span style="font-weight: 800; font-family: monospace; color: var(--accent-primary);"><?= htmlspecialchars($order['so_id']) ?></span>
                        <span class="badge" style="background: #3b82f620; color: #3b82f6; font-weight: 700;">In Production</span>
                    </div>
                    <div style="font-weight: 600; margin-bottom: 0.25rem;"><?= htmlspecialchars($order['company_name']) ?></div>
                    <div style="font-size: 0.85rem; color: var(--text-secondary);">
                        <i class="fa-solid fa-calendar"></i> <?= date('M d, Y', strtotime($order['order_date'])) ?>
                        <span style="margin: 0 0.3rem; opacity: 0.5;">·</span>
                        <span style="font-weight: 700; color: var(--accent-primary);"><?= number_format($order['total_price'], 2) ?> JOD</span>
                    </div>
                    <?php if (!empty($items)): ?>
                        <div style="background: #f8fafc; border-radius: 6px; padding: 0.5rem 0.75rem; margin-top: 0.5rem;">
                            <?php foreach ($items as $item): ?>
                                <div style="font-size: 0.8rem; padding: 0.15rem 0; display: flex; justify-content: space-between;">
                                    <span style="font-weight: 600;"><?= htmlspecialchars($item['item_name']) ?></span>
                                    <span style="color: var(--text-secondary);">×<?= (int)$item['quantity'] ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════ -->
<!--  SMART PRODUCTION OPTIMIZER MODAL                      -->
<!-- ═══════════════════════════════════════════════════════ -->
<div id="optimizerModal" style="display:none; position:fixed; inset:0; z-index:9999; background:rgba(0,0,0,0.6); backdrop-filter:blur(4px); overflow-y:auto; padding:2rem;">
    <div style="max-width:900px; margin:2rem auto; background:var(--bg-card, #fff); border-radius:16px; box-shadow:0 25px 60px rgba(0,0,0,0.3); overflow:hidden;">
        
        <!-- Modal Header -->
        <div style="background: linear-gradient(135deg, #6366f1, #8b5cf6); color:white; padding:1.5rem 2rem; display:flex; justify-content:space-between; align-items:center;">
            <div>
                <h2 style="margin:0; font-size:1.25rem; display:flex; align-items:center; gap:0.5rem;">
                    <i class="fa-solid fa-wand-magic-sparkles"></i> Smart Production Optimizer
                </h2>
                <p style="margin:0.25rem 0 0; opacity:0.85; font-size:0.85rem;" id="optimizerSubtitle">Analyzing order...</p>
            </div>
            <button onclick="closeOptimizer()" style="background:rgba(255,255,255,0.2); border:none; color:white; width:36px; height:36px; border-radius:50%; cursor:pointer; font-size:1.1rem;">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <!-- Strategy Toggle -->
        <div style="padding:1.25rem 2rem; border-bottom:1px solid var(--border-color, #e2e8f0); display:flex; gap:0.75rem;" id="strategyToggle">
            <button id="btnFastest" onclick="switchStrategy('fastest')" class="opt-strategy-btn opt-strategy-active" style="flex:1;">
                <i class="fa-solid fa-bolt"></i>
                <span class="opt-strategy-label">Fastest</span>
                <span class="opt-strategy-detail" id="fastestSummary">—</span>
            </button>
            <button id="btnCheapest" onclick="switchStrategy('cheapest')" class="opt-strategy-btn" style="flex:1;">
                <i class="fa-solid fa-coins"></i>
                <span class="opt-strategy-label">Cheapest</span>
                <span class="opt-strategy-detail" id="cheapestSummary">—</span>
            </button>
        </div>

        <!-- Loading State -->
        <div id="optimizerLoading" style="padding:4rem 2rem; text-align:center;">
            <div style="width:40px; height:40px; border:4px solid #e2e8f0; border-top-color:#6366f1; border-radius:50%; animation:spin 0.8s linear infinite; margin:0 auto 1rem;"></div>
            <p style="color:var(--text-secondary, #64748b);">Analyzing recipes and materials...</p>
        </div>

        <!-- Results Content -->
        <div id="optimizerContent" style="display:none; padding:1.5rem 2rem;">
            <!-- Populated by JS -->
        </div>

        <!-- Footer Actions -->
        <div id="optimizerFooter" style="display:none; padding:1.25rem 2rem; background:#f8fafc; border-top:1px solid var(--border-color, #e2e8f0); display:none; gap:0.75rem; justify-content:flex-end;">
            <button onclick="closeOptimizer()" style="padding:0.7rem 1.5rem; border-radius:8px; border:1px solid var(--border-color, #e2e8f0); background:white; cursor:pointer; font-weight:600; color:var(--text-secondary, #64748b);">
                Cancel
            </button>
            <button id="btnConfirmPlan" onclick="confirmPlan()" style="padding:0.7rem 1.5rem; border-radius:8px; border:none; background:linear-gradient(135deg, #10b981, #059669); color:white; cursor:pointer; font-weight:700; font-size:0.95rem; display:flex; align-items:center; gap:0.5rem;">
                <i class="fa-solid fa-check-circle"></i> Confirm & Create Batches
            </button>
        </div>
    </div>
</div>

<style>
@keyframes spin { to { transform: rotate(360deg); } }

.opt-strategy-btn {
    display: flex; flex-direction: column; align-items: center; gap: 0.25rem;
    padding: 0.75rem 1rem; border-radius: 10px; border: 2px solid var(--border-color, #e2e8f0);
    background: white; cursor: pointer; transition: all 0.25s ease;
    color: var(--text-secondary, #64748b);
}
.opt-strategy-btn:hover { border-color: #6366f1; }
.opt-strategy-btn i { font-size: 1.3rem; }
.opt-strategy-label { font-weight: 700; font-size: 0.9rem; }
.opt-strategy-detail { font-size: 0.75rem; opacity: 0.7; }

.opt-strategy-active {
    border-color: #6366f1 !important;
    background: linear-gradient(135deg, #eef2ff, #e0e7ff) !important;
    color: #4338ca !important;
}

.opt-recipe-card {
    background: #f8fafc; border-radius: 10px; padding: 1rem; margin-bottom: 0.75rem;
    border: 1px solid var(--border-color, #e2e8f0); transition: all 0.2s ease;
}
.opt-recipe-card:hover { border-color: #6366f1; box-shadow: 0 2px 8px rgba(99, 102, 241, 0.1); }

.opt-stat {
    display: flex; align-items: center; gap: 0.4rem; font-size: 0.8rem;
    color: var(--text-secondary, #64748b); padding: 0.15rem 0;
}
.opt-stat i { width: 16px; text-align: center; }

.opt-badge-feasible {
    display: inline-flex; align-items: center; gap: 0.25rem;
    padding: 0.15rem 0.5rem; border-radius: 6px; font-size: 0.7rem; font-weight: 700;
}
.opt-badge-yes { background: #dcfce7; color: #166534; }
.opt-badge-no  { background: #fee2e2; color: #991b1b; }

.opt-item-header {
    display: flex; justify-content: space-between; align-items: center;
    margin-bottom: 0.5rem; padding-bottom: 0.5rem; border-bottom: 1px solid var(--border-color, #e2e8f0);
}

.opt-savings {
    display: inline-flex; align-items: center; gap: 0.3rem;
    padding: 0.3rem 0.75rem; border-radius: 8px; font-size: 0.8rem; font-weight: 700;
    background: #fef3c7; color: #92400e; margin-top: 0.75rem;
}
</style>

<script>
let optimizerData = null;
let currentStrategy = 'fastest';
let currentSoId = null;

function openOptimizer(soId) {
    currentSoId = soId;
    document.getElementById('optimizerModal').style.display = 'block';
    document.getElementById('optimizerLoading').style.display = 'block';
    document.getElementById('optimizerContent').style.display = 'none';
    document.getElementById('optimizerFooter').style.display = 'none';
    document.getElementById('optimizerSubtitle').textContent = 'Analyzing order ' + soId + '...';
    document.body.style.overflow = 'hidden';

    fetch('<?= BASE_URL ?>/modules/production/optimizer.php?so_id=' + encodeURIComponent(soId))
        .then(r => r.json())
        .then(data => {
            if (data.error) {
                document.getElementById('optimizerLoading').innerHTML = 
                    '<i class="fa-solid fa-triangle-exclamation" style="font-size:2rem; color:#ef4444; display:block; margin-bottom:0.5rem;"></i>' +
                    '<p style="color:#ef4444;">' + data.error + '</p>';
                return;
            }
            optimizerData = data;
            document.getElementById('optimizerSubtitle').textContent = 
                data.company_name + ' — ' + data.items.length + ' item(s) · ' + data.total_price.toFixed(2) + ' JOD';

            // Update strategy summaries
            document.getElementById('fastestSummary').textContent = 
                data.strategies.fastest.total_curing_hours + 'h · ' + data.strategies.fastest.total_material_cost.toFixed(2) + ' JOD';
            document.getElementById('cheapestSummary').textContent = 
                data.strategies.cheapest.total_curing_hours + 'h · ' + data.strategies.cheapest.total_material_cost.toFixed(2) + ' JOD';

            currentStrategy = 'fastest';
            renderStrategy('fastest');

            document.getElementById('optimizerLoading').style.display = 'none';
            document.getElementById('optimizerContent').style.display = 'block';
            document.getElementById('optimizerFooter').style.display = 'flex';
        })
        .catch(err => {
            document.getElementById('optimizerLoading').innerHTML = 
                '<i class="fa-solid fa-triangle-exclamation" style="font-size:2rem; color:#ef4444; display:block; margin-bottom:0.5rem;"></i>' +
                '<p style="color:#ef4444;">Network error: ' + err.message + '</p>';
        });
}

function closeOptimizer() {
    document.getElementById('optimizerModal').style.display = 'none';
    document.body.style.overflow = '';
    optimizerData = null;
}

function switchStrategy(strategy) {
    currentStrategy = strategy;
    document.getElementById('btnFastest').classList.toggle('opt-strategy-active', strategy === 'fastest');
    document.getElementById('btnCheapest').classList.toggle('opt-strategy-active', strategy === 'cheapest');
    renderStrategy(strategy);
}

function renderStrategy(strategy) {
    if (!optimizerData) return;

    const strat = optimizerData.strategies[strategy];
    const otherStrat = optimizerData.strategies[strategy === 'fastest' ? 'cheapest' : 'fastest'];
    let html = '';

    // Summary bar
    const costDiff = strat.total_material_cost - otherStrat.total_material_cost;
    const timeDiff = strat.total_curing_hours - otherStrat.total_curing_hours;
    
    html += '<div style="background:linear-gradient(135deg, #eef2ff, #e0e7ff); border-radius:10px; padding:1rem; margin-bottom:1.25rem; display:flex; justify-content:space-around; text-align:center;">';
    html += '<div><div style="font-size:0.7rem; text-transform:uppercase; font-weight:700; color:#6366f1; margin-bottom:0.25rem;">Total Curing Time</div>';
    html += '<div style="font-size:1.4rem; font-weight:800; color:#1e1b4b;">' + strat.total_curing_hours + 'h</div></div>';
    html += '<div style="width:1px; background:#c7d2fe;"></div>';
    html += '<div><div style="font-size:0.7rem; text-transform:uppercase; font-weight:700; color:#6366f1; margin-bottom:0.25rem;">Total Material Cost</div>';
    html += '<div style="font-size:1.4rem; font-weight:800; color:#1e1b4b;">' + strat.total_material_cost.toFixed(2) + ' JOD</div></div>';
    html += '<div style="width:1px; background:#c7d2fe;"></div>';
    html += '<div><div style="font-size:0.7rem; text-transform:uppercase; font-weight:700; color:#6366f1; margin-bottom:0.25rem;">Batches Needed</div>';
    html += '<div style="font-size:1.4rem; font-weight:800; color:#1e1b4b;">' + strat.recipes.length + '</div></div>';
    html += '</div>';

    // Comparison insight
    if (strategy === 'fastest' && timeDiff < 0) {
        html += '<div class="opt-savings"><i class="fa-solid fa-bolt"></i> ' + Math.abs(timeDiff) + 'h faster than Cheapest strategy</div>';
    } else if (strategy === 'fastest' && costDiff > 0) {
        html += '<div class="opt-savings"><i class="fa-solid fa-triangle-exclamation"></i> Costs ' + costDiff.toFixed(2) + ' JOD more than Cheapest</div>';
    } else if (strategy === 'cheapest' && costDiff < 0) {
        html += '<div class="opt-savings"><i class="fa-solid fa-coins"></i> Saves ' + Math.abs(costDiff).toFixed(2) + ' JOD vs Fastest strategy</div>';
    } else if (strategy === 'cheapest' && timeDiff > 0) {
        html += '<div class="opt-savings"><i class="fa-solid fa-triangle-exclamation"></i> Takes ' + Math.abs(timeDiff) + 'h longer than Fastest</div>';
    }

    // Recipe cards per item
    strat.recipes.forEach(function(rec, idx) {
        html += '<div class="opt-recipe-card">';
        html += '<div class="opt-item-header">';
        html += '<div><span style="font-weight:800; color:var(--accent-primary, #6366f1); font-size:0.95rem;">' + rec.item_name + '</span>';
        html += ' <span style="font-size:0.8rem; color:var(--text-secondary, #64748b);">× ' + optimizerData.items[idx].order_qty + '</span></div>';
        html += '<span class="opt-badge-feasible ' + (rec.feasible ? 'opt-badge-yes' : 'opt-badge-no') + '">';
        html += '<i class="fa-solid ' + (rec.feasible ? 'fa-check-circle' : 'fa-exclamation-circle') + '"></i> ';
        html += rec.feasible ? 'Materials OK' : 'Low Stock';
        html += '</span></div>';

        html += '<div style="display:grid; grid-template-columns:1fr 1fr; gap:0.5rem;">';
        html += '<div class="opt-stat"><i class="fa-solid fa-flask" style="color:#6366f1;"></i> <strong>' + rec.recipe_name + '</strong></div>';
        html += '<div class="opt-stat"><i class="fa-solid fa-layer-group" style="color:#f59e0b;"></i> ' + rec.runs_needed + ' mix run(s)</div>';
        html += '<div class="opt-stat"><i class="fa-solid fa-clock" style="color:#3b82f6;"></i> ' + rec.curing_hours + ' hours curing</div>';
        html += '<div class="opt-stat"><i class="fa-solid fa-coins" style="color:#10b981;"></i> ' + rec.total_cost.toFixed(2) + ' JOD material cost</div>';
        html += '</div>';
        html += '</div>';
    });

    // Items with no recipe
    optimizerData.items.forEach(function(item) {
        if (item.candidates.length === 0) {
            html += '<div class="opt-recipe-card" style="border-color:#fca5a5;">';
            html += '<div class="opt-item-header">';
            html += '<span style="font-weight:700;">' + item.item_name + ' × ' + item.order_qty + '</span>';
            html += '<span class="opt-badge-feasible opt-badge-no"><i class="fa-solid fa-xmark"></i> No Recipe</span>';
            html += '</div>';
            html += '<p style="font-size:0.8rem; color:#dc2626; margin:0;">No recipe can produce this item. Please create a recipe first.</p>';
            html += '</div>';
        }
    });

    document.getElementById('optimizerContent').innerHTML = html;
}

function confirmPlan() {
    if (!optimizerData || !currentSoId) return;
    
    const strat = optimizerData.strategies[currentStrategy];
    const btn = document.getElementById('btnConfirmPlan');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Creating batches...';

    // Create a batch for each recipe in the chosen strategy
    let batchPromises = strat.recipes.map(function(rec) {
        const fd = new FormData();
        fd.append('recipe_id', rec.recipe_id);
        fd.append('so_id', currentSoId);
        fd.append('target_quantity', rec.runs_needed);
        fd.append('production_date', new Date().toISOString().split('T')[0]);
        return fetch('<?= BASE_URL ?>/modules/production/create_batch.php', { method: 'POST', body: fd })
            .then(r => r.json());
    });

    Promise.all(batchPromises)
        .then(results => {
            const allOk = results.every(r => r.success);
            if (!allOk) {
                const errors = results.filter(r => !r.success).map(r => r.error);
                alert('Some batches failed: ' + errors.join(', '));
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-check-circle"></i> Confirm & Create Batches';
                return;
            }

            // Update order status to "In Production"
            const statusFd = new FormData();
            statusFd.append('so_id', currentSoId);
            statusFd.append('status', 'In Production');
            return fetch('<?= BASE_URL ?>/modules/orders/update_status.php', { method: 'POST', body: statusFd })
                .then(r => r.json())
                .then(data => {
                    closeOptimizer();
                    location.reload();
                });
        })
        .catch(err => {
            alert('Network error: ' + err.message);
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-check-circle"></i> Confirm & Create Batches';
        });
}

// Close modal on backdrop click
document.getElementById('optimizerModal').addEventListener('click', function(e) {
    if (e.target === this) closeOptimizer();
});

// Close on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeOptimizer();
});
</script>
