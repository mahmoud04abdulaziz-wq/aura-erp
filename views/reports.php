<?php
/**
 * AURA ERP — Reports View
 * Displays available report cards with DB-driven counts.
 * Aggregated queries across multiple tables.
 */

try {
    $salesCount = $pdo->query("SELECT COUNT(*) FROM sales_orders")->fetchColumn();
    $productionCount = $pdo->query("SELECT COUNT(*) FROM production_orders")->fetchColumn();
    $itemCount = $pdo->query("SELECT COUNT(*) FROM item_master")->fetchColumn();
    $employeeCount = $pdo->query("SELECT COUNT(*) FROM employees")->fetchColumn();
} catch (Exception $e) {
    $salesCount = $productionCount = $itemCount = $employeeCount = 0;
}

$reportCards = [
    [
        'icon' => 'fa-solid fa-file-invoice-dollar',
        'color' => 'var(--accent-primary)',
        'title' => 'Monthly Sales Summary',
        'desc' => "Breakdown of revenue by client and product. ($salesCount orders recorded)",
    ],
    [
        'icon' => 'fa-solid fa-shapes',
        'color' => 'var(--success)',
        'title' => 'Production Efficiency',
        'desc' => "Machine usage, downtime, and output volume. ($productionCount batches tracked)",
    ],
    [
        'icon' => 'fa-solid fa-warehouse',
        'color' => 'var(--warning)',
        'title' => 'Inventory Valuation',
        'desc' => "Current stock levels and estimated ledger value. ($itemCount items in master)",
    ],
    [
        'icon' => 'fa-solid fa-users',
        'color' => 'var(--text-primary)',
        'title' => 'Employee Payroll',
        'desc' => "Monthly salary disbursements and overtime. ($employeeCount employees)",
    ],
];
?>

<div class="card">
    <div class="card-header">
        <h3>Available Reports</h3>
    </div>
    <div
        style="display:grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem; padding: 1rem 0;">
        <?php foreach ($reportCards as $report): ?>
            <div
                style="border: 1px solid var(--border-color); border-radius: 8px; padding: 1.5rem; display:flex; flex-direction:column; gap: 1rem;">
                <div style="display:flex; align-items:center; gap: 1rem;">
                    <div
                        style="width:48px; height:48px; background:var(--bg-body); border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:1.5rem; color:<?= $report['color'] ?>;">
                        <i class="<?= $report['icon'] ?>"></i>
                    </div>
                    <div>
                        <h4 style="margin:0; font-size:1.1rem;">
                            <?= htmlspecialchars($report['title']) ?>
                        </h4>
                        <p style="margin:0; font-size:0.85rem; color:var(--text-secondary);">
                            <?= htmlspecialchars($report['desc']) ?>
                        </p>
                    </div>
                </div>
                <div style="display:flex; gap:0.5rem; margin-top:auto;">
                    <button
                        style="flex:1; padding:0.5rem; background:var(--accent-primary); color:white; border:none; border-radius:4px; cursor:pointer;">PDF</button>
                    <button
                        style="flex:1; padding:0.5rem; background:var(--bg-body); color:var(--text-primary); border:1px solid var(--border-color); border-radius:4px; cursor:pointer;">CSV</button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>