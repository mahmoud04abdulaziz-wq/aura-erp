<?php
/**
 * AURA ERP — Analytics View
 * Displays performance metrics from aggregated DB queries.
 * Tables: sales_orders, customers, production_orders, defect_logs, item_master
 */

try {
    // Total customers
    $totalCustomers = $pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn();

    // Customers this month vs last month
    // (using lead_status as proxy — no explicit date field, so we count total growth)

    // Average order value
    $avgOrderValue = $pdo->query("SELECT COALESCE(AVG(total_price), 0) FROM sales_orders")->fetchColumn();

    // Total revenue
    $totalRevenue = $pdo->query("SELECT COALESCE(SUM(total_price), 0) FROM sales_orders")->fetchColumn();

    // Production stats
    $totalProduction = $pdo->query("SELECT COUNT(*) FROM production_orders")->fetchColumn();
    $completedProduction = $pdo->query("SELECT COUNT(*) FROM production_orders WHERE status = 'Completed'")->fetchColumn();
    $productionRate = $totalProduction > 0 ? round(($completedProduction / $totalProduction) * 100) : 0;

    // Defect/waste rate
    $totalScrap = $pdo->query("SELECT COALESCE(SUM(scrap_quantity), 0) FROM defect_logs")->fetchColumn();
    $totalYield = $pdo->query("SELECT COALESCE(SUM(actual_yield), 0) FROM production_orders")->fetchColumn();
    $wasteRate = $totalYield > 0 ? round(($totalScrap / $totalYield) * 100, 1) : 0;

    // Revenue By Month (Last 6 Months)
    $revenueByMonth = $pdo->query("
        SELECT DATE_FORMAT(order_date, '%b %Y') as month, SUM(total_price) as total 
        FROM sales_orders 
        WHERE order_date >= DATE_SUB(CURRENT_DATE, INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(order_date, '%Y-%m')
        ORDER BY order_date ASC
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Inventory Distribution by Category
    $inventoryByCat = $pdo->query("
        SELECT category, COUNT(*) as cnt FROM item_master GROUP BY category
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Production Output Trend
    $productionTrend = $pdo->query("
        SELECT DATE_FORMAT(production_date, '%b %d') as day, SUM(target_quantity) as target, SUM(actual_yield) as yield
        FROM production_orders
        WHERE production_date >= DATE_SUB(CURRENT_DATE, INTERVAL 14 DAY)
        GROUP BY production_date
        ORDER BY production_date ASC
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Orders by status
    $ordersByStatus = $pdo->query(
        "SELECT order_status, COUNT(*) as cnt FROM sales_orders GROUP BY order_status ORDER BY cnt DESC"
    )->fetchAll();

} catch (Exception $e) {
    error_log("Analytics view error: " . $e->getMessage());
    $totalCustomers = $avgOrderValue = $totalRevenue = $productionRate = $wasteRate = 0;
    $ordersByStatus = [];
}
?>

<!-- Charts Section -->
<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; margin-bottom: 2rem;">
    <!-- Revenue Trend Chart -->
    <div class="card">
        <div class="card-header">
            <h3>Revenue Trend (Last 6 Months)</h3>
        </div>
        <div id="revenue-chart" style="min-height: 350px;"></div>
    </div>

    <!-- Inventory Distribution -->
    <div class="card">
        <div class="card-header">
            <h3>Inventory by Category</h3>
        </div>
        <div id="inventory-chart" style="min-height: 350px;"></div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 2rem;">
    <!-- Production Efficiency -->
    <div class="card">
        <div class="card-header">
            <h3>Production Output vs. Yield</h3>
        </div>
        <div id="production-chart" style="min-height: 300px;"></div>
    </div>

    <!-- Order Summary Cards & Mix -->
    <div class="card">
        <div class="card-header">
            <h3>Order Status Mix</h3>
        </div>
        <div id="orders-mix-chart" style="min-height: 300px;"></div>
    </div>
</div>

<!-- KPI Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon success"><i class="fa-solid fa-users"></i></div>
        <div class="stat-info">
            <h3>Total Customers</h3>
            <p class="stat-value"><?= $totalCustomers ?></p>
            <span class="stat-trend neutral">All registered clients</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon primary"><i class="fa-solid fa-money-bill-trend-up"></i></div>
        <div class="stat-info">
            <h3>Avg. Order Value</h3>
            <p class="stat-value">$<?= number_format($avgOrderValue, 2) ?></p>
            <span class="stat-trend neutral">Across all orders</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon warning"><i class="fa-solid fa-recycle"></i></div>
        <div class="stat-info">
            <h3>Waste Rate</h3>
            <p class="stat-value"><?= $wasteRate ?>%</p>
            <span class="stat-trend <?= $wasteRate > 5 ? 'negative' : 'positive' ?>">
                <?= $wasteRate > 5 ? 'Above target' : 'Within target' ?>
            </span>
        </div>
    </div>
</div>

<!-- ApexCharts Script -->
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    // Data Preparation
    const revenueData = <?= json_encode($revenueByMonth) ?>;
    const inventoryData = <?= json_encode($inventoryByCat) ?>;
    const productionData = <?= json_encode($productionTrend) ?>;
    const ordersData = <?= json_encode($ordersByStatus) ?>;

    // 1. Revenue Chart
    new ApexCharts(document.querySelector("#revenue-chart"), {
        series: [{
            name: 'Revenue',
            data: revenueData.map(d => d.total)
        }],
        chart: { type: 'area', height: 350, toolbar: { show: false }, zoom: { enabled: false } },
        colors: ['#6366f1'],
        stroke: { curve: 'smooth', width: 3 },
        fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.45, opacityTo: 0.05, stops: [20, 100, 100, 100] } },
        xaxis: { categories: revenueData.map(d => d.month) },
        dataLabels: { enabled: false },
        tooltip: { y: { formatter: val => `$${Number(val).toLocaleString()}` } }
    }).render();

    // 2. Inventory Chart
    new ApexCharts(document.querySelector("#inventory-chart"), {
        series: inventoryData.map(d => d.cnt),
        chart: { type: 'donut', height: 350 },
        labels: inventoryData.map(d => d.category),
        colors: ['#6366f1', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6'],
        legend: { position: 'bottom' },
        plotOptions: { pie: { donut: { size: '70%' } } }
    }).render();

    // 3. Production Chart
    new ApexCharts(document.querySelector("#production-chart"), {
        series: [{
            name: 'Target',
            data: productionData.map(d => Number(d.target))
        }, {
            name: 'Actual Yield',
            data: productionData.map(d => Number(d.yield))
        }],
        chart: { type: 'bar', height: 300, toolbar: { show: false } },
        colors: ['#e2e8f0', '#10b981'],
        plotOptions: { bar: { horizontal: false, columnWidth: '55%', borderRadius: 4 } },
        xaxis: { categories: productionData.map(d => d.day) },
        dataLabels: { enabled: false },
        legend: { position: 'top' }
    }).render();

    // 4. Orders Mix Chart
    new ApexCharts(document.querySelector("#orders-mix-chart"), {
        series: [{
            data: ordersData.map(d => d.cnt)
        }],
        chart: { type: 'bar', height: 300, toolbar: { show: false } },
        plotOptions: { bar: { borderRadius: 4, horizontal: true } },
        colors: ['#6366f1'],
        dataLabels: { enabled: true },
        xaxis: { categories: ordersData.map(d => d.order_status) }
    }).render();
</script>