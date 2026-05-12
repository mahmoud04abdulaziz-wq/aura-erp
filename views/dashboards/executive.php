<?php
/**
 * AURA ERP — Executive Dashboard (Live Filtered)
 * All chart data loads via AJAX from /modules/api/dashboard_data.php
 */

// Initial server-side data for first paint
$revenueMonth = $pdo->query("SELECT COALESCE(SUM(amount),0) FROM finance_ledger WHERE transaction_type = 'Income' AND MONTH(transaction_date) = MONTH(CURRENT_DATE())")->fetchColumn();
$expensesMonth = $pdo->query("SELECT COALESCE(SUM(amount),0) FROM finance_ledger WHERE transaction_type = 'Expense' AND MONTH(transaction_date) = MONTH(CURRENT_DATE())")->fetchColumn();
$profit = $revenueMonth - $expensesMonth;
$activeOrders = $pdo->query("SELECT COUNT(*) FROM sales_orders WHERE order_status != 'Delivered'")->fetchColumn();
$categories = $pdo->query("SELECT DISTINCT category FROM finance_ledger WHERE category IS NOT NULL ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<div class="dashboard-container">
    <div class="welcome-banner" style="background: linear-gradient(135deg, #0f172a, #334155); color: white;">
        <h1>Executive Dashboard</h1>
        <p>MiskStone Factory Financial Overview & Status</p>
    </div>

    <!-- ═══ FILTER BAR ═══ -->
    <div class="card" style="margin-top: 1.5rem; padding: 1.25rem 1.5rem;">
        <div style="display: flex; flex-wrap: wrap; gap: 1rem; align-items: flex-end;">
            <!-- Period Granularity -->
            <div style="flex: 0 0 auto;">
                <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0.3rem;">Granularity</label>
                <div id="periodBtns" style="display: flex; gap: 2px; background: var(--bg-secondary); border-radius: 8px; padding: 2px;">
                    <button class="period-btn" data-val="daily">Daily</button>
                    <button class="period-btn" data-val="weekly">Weekly</button>
                    <button class="period-btn active" data-val="monthly">Monthly</button>
                    <button class="period-btn" data-val="yearly">Yearly</button>
                </div>
            </div>
            <!-- Date Range -->
            <div style="flex: 1; min-width: 120px;">
                <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0.3rem;">From</label>
                <input type="date" id="filterFrom" style="width: 100%; padding: 0.5rem; border: 1px solid var(--border-primary); border-radius: 6px; font-family: inherit; font-size: 0.85rem; background: var(--bg-primary); color: var(--text-primary);">
            </div>
            <div style="flex: 1; min-width: 120px;">
                <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0.3rem;">To</label>
                <input type="date" id="filterTo" style="width: 100%; padding: 0.5rem; border: 1px solid var(--border-primary); border-radius: 6px; font-family: inherit; font-size: 0.85rem; background: var(--bg-primary); color: var(--text-primary);">
            </div>
            <!-- Amount Range -->
            <div style="flex: 0 0 100px;">
                <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0.3rem;">Min JOD</label>
                <input type="number" id="filterMinAmt" placeholder="0" min="0" step="50" style="width: 100%; padding: 0.5rem; border: 1px solid var(--border-primary); border-radius: 6px; font-family: inherit; font-size: 0.85rem; background: var(--bg-primary); color: var(--text-primary);">
            </div>
            <div style="flex: 0 0 100px;">
                <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0.3rem;">Max JOD</label>
                <input type="number" id="filterMaxAmt" placeholder="∞" min="0" step="50" style="width: 100%; padding: 0.5rem; border: 1px solid var(--border-primary); border-radius: 6px; font-family: inherit; font-size: 0.85rem; background: var(--bg-primary); color: var(--text-primary);">
            </div>
            <!-- Type -->
            <div style="flex: 0 0 120px;">
                <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0.3rem;">Type</label>
                <select id="filterType" style="width: 100%; padding: 0.5rem; border: 1px solid var(--border-primary); border-radius: 6px; font-family: inherit; font-size: 0.85rem; background: var(--bg-primary); color: var(--text-primary);">
                    <option value="all">All</option>
                    <option value="Income">Income</option>
                    <option value="Expense">Expense</option>
                </select>
            </div>
            <!-- Category -->
            <div style="flex: 0 0 150px;">
                <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0.3rem;">Category</label>
                <select id="filterCategory" style="width: 100%; padding: 0.5rem; border: 1px solid var(--border-primary); border-radius: 6px; font-family: inherit; font-size: 0.85rem; background: var(--bg-primary); color: var(--text-primary);">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <!-- Reset -->
            <div style="flex: 0 0 auto;">
                <button id="resetFilters" style="padding: 0.5rem 1rem; background: var(--bg-secondary); border: 1px solid var(--border-primary); border-radius: 6px; cursor: pointer; font-family: inherit; font-size: 0.85rem; color: var(--text-secondary); transition: 0.2s;" onmouseover="this.style.background='var(--accent-primary)';this.style.color='#fff'" onmouseout="this.style.background='var(--bg-secondary)';this.style.color='var(--text-secondary)'">
                    <i class="fa-solid fa-rotate-left" style="margin-right: 4px;"></i> Reset
                </button>
            </div>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon info" style="background: rgba(16, 185, 129, 0.1); color: #10b981;"><i class="fa-solid fa-money-bill-trend-up"></i></div>
            <div class="stat-info">
                <h3>Revenue</h3>
                <p class="stat-value" id="kpiRevenue"><?= number_format($revenueMonth) ?> JOD</p>
                <span class="stat-trend positive">Filtered Period</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon warning" style="background: rgba(239, 68, 68, 0.1); color: #ef4444;"><i class="fa-solid fa-money-bill-transfer"></i></div>
            <div class="stat-info">
                <h3>Expenses</h3>
                <p class="stat-value" id="kpiExpenses"><?= number_format($expensesMonth) ?> JOD</p>
                <span class="stat-trend negative">Filtered Period</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon success" style="background: rgba(59, 130, 246, 0.1); color: #3b82f6;"><i class="fa-solid fa-piggy-bank"></i></div>
            <div class="stat-info">
                <h3>Net Profit</h3>
                <p class="stat-value" id="kpiProfit"><?= number_format($profit) ?> JOD</p>
                <span class="stat-trend neutral">Filtered Period</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon primary"><i class="fa-solid fa-layer-group"></i></div>
            <div class="stat-info">
                <h3>Active Operations</h3>
                <p class="stat-value"><?= $activeOrders ?></p>
                <span class="stat-trend neutral">Orders in pipeline</span>
            </div>
        </div>
    </div>

    <!-- Charts Row 1: Revenue Trend + Sales by Category -->
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; margin-top: 1.5rem;">
        <div class="card">
            <div class="card-header"><h3><i class="fa-solid fa-chart-line" style="margin-right: 0.5rem; color: var(--accent-primary);"></i>Revenue vs Expenses Trend</h3></div>
            <div style="padding: 1rem; position: relative; height: 300px; width: 100%;"><canvas id="execRevenueTrend"></canvas></div>
        </div>
        <div class="card">
            <div class="card-header"><h3><i class="fa-solid fa-tags" style="margin-right: 0.5rem; color: #f59e0b;"></i>Sales by Category</h3></div>
            <div style="padding: 1rem; position: relative; height: 300px; width: 100%;"><canvas id="execSalesCategory"></canvas></div>
        </div>
    </div>

    <!-- Charts Row 2: Production Status + Order Pipeline -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-top: 1.5rem;">
        <div class="card">
            <div class="card-header"><h3><i class="fa-solid fa-industry" style="margin-right: 0.5rem; color: #8b5cf6;"></i>Production Orders by Status</h3></div>
            <div style="padding: 1rem; position: relative; height: 300px; width: 100%; display: flex; justify-content: center;"><canvas id="execProdStatus"></canvas></div>
        </div>
        <div class="card">
            <div class="card-header"><h3><i class="fa-solid fa-layer-group" style="margin-right: 0.5rem; color: #06b6d4;"></i>Sales Order Pipeline</h3></div>
            <div style="padding: 1rem; position: relative; height: 300px; width: 100%;"><canvas id="execOrderPipeline"></canvas></div>
        </div>
    </div>

    <!-- CEO System Modules -->
    <div class="card" style="margin-top: 1.5rem;">
        <div class="card-header"><h3><i class="fa-solid fa-grip" style="margin-right: 0.5rem; color: var(--accent-primary);"></i>System Modules</h3></div>
        <div style="padding: 1.5rem; display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.25rem;">

            <!-- Sales Module -->
            <a href="<?= BASE_URL ?>/app.php?view=dashboard&dept=sales" class="module-box" style="--box-accent: #10b981; text-decoration: none; color: inherit;">
                <div class="module-box-icon" style="background: rgba(16,185,129,0.1); color: #10b981;">
                    <i class="fa-solid fa-chart-line"></i>
                </div>
                <div class="module-box-info">
                    <h4>Sales & Orders</h4>
                    <p>Order inbox, pipeline, delivery dispatch, customer management & revenue tracking</p>
                </div>
                <i class="fa-solid fa-arrow-right module-box-arrow"></i>
            </a>

            <!-- Production Module -->
            <a href="<?= BASE_URL ?>/app.php?view=dashboard&dept=production" class="module-box" style="--box-accent: #8b5cf6; text-decoration: none; color: inherit;">
                <div class="module-box-icon" style="background: rgba(139,92,246,0.1); color: #8b5cf6;">
                    <i class="fa-solid fa-industry"></i>
                </div>
                <div class="module-box-info">
                    <h4>Production</h4>
                    <p>Production orders, mix batches, mixing & curing stations, recipes & quality control</p>
                </div>
                <i class="fa-solid fa-arrow-right module-box-arrow"></i>
            </a>

            <!-- Procurement Module -->
            <a href="<?= BASE_URL ?>/app.php?view=dashboard&dept=procurement" class="module-box" style="--box-accent: #f59e0b; text-decoration: none; color: inherit;">
                <div class="module-box-icon" style="background: rgba(245,158,11,0.1); color: #f59e0b;">
                    <i class="fa-solid fa-truck-field"></i>
                </div>
                <div class="module-box-info">
                    <h4>Procurement</h4>
                    <p>Purchase orders, supplier management, material requests & inventory restocking</p>
                </div>
                <i class="fa-solid fa-arrow-right module-box-arrow"></i>
            </a>

            <!-- HR Module -->
            <a href="<?= BASE_URL ?>/app.php?view=dashboard&dept=hr" class="module-box" style="--box-accent: #ec4899; text-decoration: none; color: inherit;">
                <div class="module-box-icon" style="background: rgba(236,72,153,0.1); color: #ec4899;">
                    <i class="fa-solid fa-users"></i>
                </div>
                <div class="module-box-info">
                    <h4>Human Resources</h4>
                    <p>Employee records, job applications, career postings & workforce management</p>
                </div>
                <i class="fa-solid fa-arrow-right module-box-arrow"></i>
            </a>

            <!-- BI Module -->
            <a href="<?= BASE_URL ?>/app.php?view=bi" class="module-box" style="--box-accent: #06b6d4; text-decoration: none; color: inherit;">
                <div class="module-box-icon" style="background: rgba(6,182,212,0.1); color: #06b6d4;">
                    <i class="fa-solid fa-chart-pie"></i>
                </div>
                <div class="module-box-info">
                    <h4>Business Intelligence</h4>
                    <p>Cross-department analytics, trend analysis, performance reports & KPI dashboards</p>
                </div>
                <i class="fa-solid fa-arrow-right module-box-arrow"></i>
            </a>

            <!-- Storefront -->
            <a href="<?= BASE_URL ?>/" class="module-box" style="--box-accent: #6366f1; text-decoration: none; color: inherit;" target="_blank">
                <div class="module-box-icon" style="background: rgba(99,102,241,0.1); color: #6366f1;">
                    <i class="fa-solid fa-store"></i>
                </div>
                <div class="module-box-info">
                    <h4>Storefront</h4>
                    <p>Public website, product catalog, customer orders & online presence management</p>
                </div>
                <i class="fa-solid fa-arrow-up-right-from-square module-box-arrow"></i>
            </a>

        </div>
    </div>
</div>

<style>
.period-btn {
    padding: 0.4rem 0.85rem; border: none; background: transparent; color: var(--text-secondary);
    font-family: inherit; font-size: 0.8rem; font-weight: 600; cursor: pointer;
    border-radius: 6px; transition: 0.2s;
}
.period-btn.active { background: var(--accent-primary); color: #fff; box-shadow: 0 2px 8px rgba(99,102,241,0.3); }
.period-btn:hover:not(.active) { background: var(--bg-primary); }

@media (max-width: 900px) {
    div[style*="grid-template-columns: 2fr 1fr"] { grid-template-columns: 1fr !important; }
    div[style*="grid-template-columns: 1fr 1fr"] { grid-template-columns: 1fr !important; }
}

/* Module Navigation Boxes */
.module-box {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1.25rem 1.5rem;
    border-radius: 14px;
    border: 1px solid var(--border-primary);
    background: var(--bg-primary);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    cursor: pointer;
    position: relative;
    overflow: hidden;
}
.module-box::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 3px;
    background: var(--box-accent, var(--accent-primary));
    transform: scaleX(0);
    transform-origin: left;
    transition: transform 0.3s ease;
}
.module-box:hover::before { transform: scaleX(1); }
.module-box:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.08);
    border-color: var(--box-accent, var(--accent-primary));
}
.module-box-icon {
    width: 48px; height: 48px;
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.3rem;
    flex-shrink: 0;
}
.module-box-info { flex: 1; }
.module-box-info h4 {
    margin: 0 0 0.25rem 0;
    font-size: 1rem;
    font-weight: 700;
    color: var(--text-primary);
}
.module-box-info p {
    margin: 0;
    font-size: 0.8rem;
    color: var(--text-secondary);
    line-height: 1.4;
}
.module-box-arrow {
    color: var(--text-secondary);
    font-size: 0.9rem;
    transition: transform 0.3s, color 0.3s;
    flex-shrink: 0;
}
.module-box:hover .module-box-arrow {
    transform: translateX(4px);
    color: var(--box-accent, var(--accent-primary));
}
</style>

<script>
(function() {
    const BASE = '<?= BASE_URL ?>';
    const chartColors = {
        purple: '#6366f1', green: '#10b981', red: '#ef4444', amber: '#f59e0b',
        cyan: '#06b6d4', violet: '#8b5cf6', pink: '#ec4899', slate: '#64748b',
        blue: '#3b82f6', emerald: '#059669'
    };
    const bgPalette = ['rgba(99,102,241,0.7)','rgba(245,158,11,0.7)','rgba(16,185,129,0.7)','rgba(236,72,153,0.7)','rgba(6,182,212,0.7)','rgba(139,92,246,0.7)','rgba(239,68,68,0.7)','rgba(100,116,139,0.7)'];
    const statusColors = { 'Planned': chartColors.slate, 'Mixing': chartColors.amber, 'Curing': chartColors.violet, 'Completed': chartColors.green, 'Failed': chartColors.red, 'Archived': '#94a3b8' };
    const pipeColors = { 'In Production': chartColors.purple, 'Pending Delivery': chartColors.amber, 'Delivered': chartColors.green, 'New': chartColors.cyan, 'Confirmed': chartColors.blue };

    // Chart instances
    let trendChart, catChart, prodChart, pipeChart;
    const commonOpts = { responsive: true, maintainAspectRatio: false };

    function initCharts() {
        trendChart = new Chart(document.getElementById('execRevenueTrend'), {
            type: 'line', data: { labels: [], datasets: [
                { label: 'Income', data: [], borderColor: chartColors.green, backgroundColor: 'rgba(16,185,129,0.1)', fill: true, tension: 0.4, borderWidth: 2.5, pointRadius: 4, pointBackgroundColor: chartColors.green },
                { label: 'Expenses', data: [], borderColor: chartColors.red, backgroundColor: 'rgba(239,68,68,0.08)', fill: true, tension: 0.4, borderWidth: 2.5, pointRadius: 4, pointBackgroundColor: chartColors.red }
            ]},
            options: { ...commonOpts, interaction: { intersect: false, mode: 'index' }, plugins: { legend: { position: 'top', labels: { usePointStyle: true, pointStyle: 'circle' } } }, scales: { y: { beginAtZero: true, ticks: { callback: v => v.toLocaleString() + ' JOD' } } } }
        });
        catChart = new Chart(document.getElementById('execSalesCategory'), {
            type: 'doughnut', data: { labels: [], datasets: [{ data: [], backgroundColor: bgPalette, borderWidth: 2, borderColor: '#fff', hoverOffset: 6 }] },
            options: { ...commonOpts, cutout: '60%', plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, padding: 12 } } } }
        });
        prodChart = new Chart(document.getElementById('execProdStatus'), {
            type: 'pie', data: { labels: [], datasets: [{ data: [], backgroundColor: [], borderWidth: 2, borderColor: '#fff' }] },
            options: { ...commonOpts, plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, padding: 12 } } } }
        });
        pipeChart = new Chart(document.getElementById('execOrderPipeline'), {
            type: 'bar', data: { labels: [], datasets: [{ label: 'Orders', data: [], backgroundColor: [], borderRadius: 8, barThickness: 28 }] },
            options: { ...commonOpts, indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true, ticks: { stepSize: 1 } }, y: { grid: { display: false } } } }
        });
    }

    function getFilters() {
        const active = document.querySelector('.period-btn.active');
        const period = active ? active.dataset.val : 'monthly';
        let from = document.getElementById('filterFrom').value;
        let to = document.getElementById('filterTo').value;

        // If no manual date range set, auto-compute from period
        if (!from && !to) {
            const now = new Date();
            const pad = n => String(n).padStart(2, '0');
            to = `${now.getFullYear()}-${pad(now.getMonth()+1)}-${pad(now.getDate())}`;
            
            if (period === 'daily') {
                from = to;
            } else if (period === 'weekly') {
                const d = new Date(now); d.setDate(d.getDate() - 6);
                from = `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}`;
            } else if (period === 'monthly') {
                from = `${now.getFullYear()}-${pad(now.getMonth()+1)}-01`;
            } else if (period === 'yearly') {
                from = `${now.getFullYear()}-01-01`;
            }
        }

        return {
            period,
            from,
            to,
            min_amt: document.getElementById('filterMinAmt').value,
            max_amt: document.getElementById('filterMaxAmt').value,
            type: document.getElementById('filterType').value,
            category: document.getElementById('filterCategory').value,
        };
    }

    async function loadData() {
        const f = getFilters();
        const params = new URLSearchParams(f).toString();
        try {
            const res = await fetch(BASE + '/modules/api/dashboard_data.php?' + params);
            const d = await res.json();
            if (d.error) return;

            // KPIs
            document.getElementById('kpiRevenue').textContent = d.totalIncome.toLocaleString() + ' JOD';
            document.getElementById('kpiExpenses').textContent = d.totalExpenses.toLocaleString() + ' JOD';
            document.getElementById('kpiProfit').textContent = d.profit.toLocaleString() + ' JOD';

            // Revenue trend
            trendChart.data.labels = d.revenueTrend.map(r => r.period_label);
            trendChart.data.datasets[0].data = d.revenueTrend.map(r => parseFloat(r.income));
            trendChart.data.datasets[1].data = d.revenueTrend.map(r => parseFloat(r.expenses));
            trendChart.update();

            // Sales by category
            catChart.data.labels = d.salesByCategory.map(c => c.category || 'Other');
            catChart.data.datasets[0].data = d.salesByCategory.map(c => parseFloat(c.revenue));
            catChart.update();

            // Production status
            prodChart.data.labels = d.prodByStatus.map(p => p.status);
            prodChart.data.datasets[0].data = d.prodByStatus.map(p => parseInt(p.cnt));
            prodChart.data.datasets[0].backgroundColor = d.prodByStatus.map(p => statusColors[p.status] || chartColors.slate);
            prodChart.update();

            // Order pipeline
            pipeChart.data.labels = d.orderPipeline.map(p => p.order_status);
            pipeChart.data.datasets[0].data = d.orderPipeline.map(p => parseInt(p.cnt));
            pipeChart.data.datasets[0].backgroundColor = d.orderPipeline.map(p => pipeColors[p.order_status] || chartColors.slate);
            pipeChart.update();
        } catch (e) {
            console.error('Dashboard fetch error:', e);
        }
    }

    // Init
    initCharts();
    loadData();

    // Period buttons
    document.querySelectorAll('.period-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.period-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            loadData();
        });
    });

    // All other filters — debounced live update
    let debounceTimer;
    ['filterFrom','filterTo','filterMinAmt','filterMaxAmt','filterType','filterCategory'].forEach(id => {
        document.getElementById(id).addEventListener('change', () => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(loadData, 300);
        });
    });

    // Reset
    document.getElementById('resetFilters').addEventListener('click', () => {
        document.getElementById('filterFrom').value = '';
        document.getElementById('filterTo').value = '';
        document.getElementById('filterMinAmt').value = '';
        document.getElementById('filterMaxAmt').value = '';
        document.getElementById('filterType').value = 'all';
        document.getElementById('filterCategory').value = '';
        document.querySelectorAll('.period-btn').forEach(b => b.classList.remove('active'));
        document.querySelector('.period-btn[data-val="monthly"]').classList.add('active');
        loadData();
    });
})();
</script>
