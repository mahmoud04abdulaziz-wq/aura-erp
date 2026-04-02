<?php
/**
 * AURA ERP — Data Export Engine
 * Handles CSV and Print-friendly (PDF) exports for various system modules.
 */
require_once dirname(__DIR__, 2) . '/modules/auth/session_guard.php';
requireLogin();

$type = $_GET['type'] ?? '';
$format = $_GET['format'] ?? 'csv';

if (!$type) {
    die("Error: Report type not specified.");
}

// 1. Data Collection
try {
    $data = [];
    $headers = [];
    $filename = "AURA_" . ucfirst($type) . "_Report_" . date('Ymd');

    switch ($type) {
        case 'sales':
            $headers = ['Order ID', 'Customer', 'Date', 'Amount', 'Status', 'Payment Method'];
            $data = $pdo->query("
                SELECT so.so_id, c.company_name, so.order_date, so.total_price, so.order_status, so.payment_method
                FROM sales_orders so
                JOIN customers c ON so.customer_id = c.customer_id
                ORDER BY so.order_date DESC
            ")->fetchAll(PDO::FETCH_ASSOC);
            break;

        case 'production':
            $headers = ['Batch ID', 'Item', 'Target Qty', 'Actual Yield', 'Status', 'QA Status', 'Date'];
            $data = $pdo->query("
                SELECT po.production_id, im.item_name, po.target_quantity, po.actual_yield, po.status, po.qa_status, po.production_date
                FROM production_orders po
                JOIN item_master im ON po.item_id = im.item_id
                ORDER BY po.production_date DESC
            ")->fetchAll(PDO::FETCH_ASSOC);
            break;

        case 'inventory':
            $headers = ['SKU', 'Item Name', 'Category', 'UOM', 'Unit Cost', 'Stock Level'];
            $data = $pdo->query("
                SELECT im.item_id, im.item_name, im.category, im.base_uom, im.standard_cost, 
                       COALESCE(SUM(il.quantity_change), 0) as stock
                FROM item_master im
                LEFT JOIN inventory_ledger il ON im.item_id = il.item_id
                GROUP BY im.item_id
            ")->fetchAll(PDO::FETCH_ASSOC);
            break;

        case 'employees':
            $headers = ['ID', 'First Name', 'Last Name', 'Department', 'Email', 'Hire Date', 'Status'];
            $data = $pdo->query("
                SELECT e.employee_id, e.first_name, e.last_name, d.department_name, e.email, e.hire_date, e.verification_status
                FROM employees e
                JOIN departments d ON e.department_id = d.department_id
                ORDER BY d.department_name, e.last_name
            ")->fetchAll(PDO::FETCH_ASSOC);
            break;

        default:
            die("Error: Invalid report type.");
    }

} catch (Exception $e) {
    die("Export Error: " . $e->getMessage());
}

// 2. Format Handling
if ($format === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename . '.csv');
    
    $output = fopen('php::output', 'w');
    fputcsv($output, $headers);
    foreach ($data as $row) {
        fputcsv($output, array_values($row));
    }
    fclose($output);
    exit;
} 

if ($format === 'pdf') {
    // We use a high-quality print-friendly HTML view that the user can print to PDF
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title><?= $filename ?></title>
        <style>
            body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 40px; color: #1e293b; line-height: 1.5; }
            .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #e2e8f0; padding-bottom: 20px; margin-bottom: 30px; }
            .logo { font-size: 24px; font-weight: 800; color: #6366f1; }
            h1 { margin: 0; font-size: 20px; text-transform: uppercase; letter-spacing: 1px; }
            .meta { font-size: 13px; color: #64748b; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; }
            th { background: #f8fafc; text-align: left; padding: 12px; font-size: 12px; border-bottom: 1px solid #e2e8f0; text-transform: uppercase; color: #475569; }
            td { padding: 12px; font-size: 13px; border-bottom: 1px solid #f1f5f9; }
            tr:nth-child(even) { background: #fafafa; }
            .footer { margin-top: 50px; text-align: center; font-size: 11px; color: #94a3b8; border-top: 1px solid #f1f5f9; padding-top: 20px; }
            @media print {
                .no-print { display: none; }
                body { padding: 0; }
            }
        </style>
    </head>
    <body onload="window.print()">
        <div class="header">
            <div>
                <div class="logo">AURA ERP</div>
                <div class="meta">Exported on: <?= date('M d, Y H:i') ?></div>
            </div>
            <div>
                <h1><?= ucfirst($type) ?> Report</h1>
                <div class="meta" style="text-align: right;">System Generated Document</div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <?php foreach ($headers as $h): ?>
                        <th><?= $h ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data as $row): ?>
                    <tr>
                        <?php foreach ($row as $val): ?>
                            <td><?= htmlspecialchars($val ?? '—') ?></td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="footer">
            &copy; <?= date('Y') ?> AURA Stone Factory Management System. This is a computer generated report and does not require a physical signature.
        </div>

        <div class="no-print" style="position: fixed; bottom: 20px; right: 20px;">
            <button onclick="window.close()" style="padding: 10px 20px; background: #6366f1; color: white; border: none; border-radius: 6px; cursor: pointer; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);">Close Preview</button>
        </div>
    </body>
    </html>
    <?php
    exit;
}
