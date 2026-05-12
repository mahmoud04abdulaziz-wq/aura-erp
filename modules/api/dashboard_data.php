<?php
/**
 * AURA ERP — Dashboard Data API
 * Returns chart data as JSON for live AJAX filtering.
 * 
 * GET params:
 *   period   = daily | weekly | monthly | yearly  (default: monthly)
 *   from     = YYYY-MM-DD
 *   to       = YYYY-MM-DD
 *   min_amt  = number (minimum transaction amount)
 *   max_amt  = number (maximum transaction amount)
 *   type     = all | Income | Expense
 *   category = string (ledger category filter)
 */
require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/config/db_connect.php';

header('Content-Type: application/json');

// Only allow authenticated ERP users
if (!isset($_SESSION['user_id']) || ($_SESSION['role_name'] ?? '') === 'Customer') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$period   = $_GET['period'] ?? 'monthly';
$from     = $_GET['from'] ?? null;
$to       = $_GET['to'] ?? null;
$minAmt   = isset($_GET['min_amt']) && $_GET['min_amt'] !== '' ? (float)$_GET['min_amt'] : null;
$maxAmt   = isset($_GET['max_amt']) && $_GET['max_amt'] !== '' ? (float)$_GET['max_amt'] : null;
$type     = $_GET['type'] ?? 'all';
$category = $_GET['category'] ?? '';

// Build date format for grouping
$dateFormats = [
    'daily'   => '%Y-%m-%d',
    'weekly'  => '%x-W%v',     // ISO year + week
    'monthly' => '%Y-%m',
    'yearly'  => '%Y',
];
$fmt = $dateFormats[$period] ?? '%Y-%m';

// === 1. Revenue vs Expenses Trend ===
$trendWhere = [];
$trendParams = [];

if ($from) {
    $trendWhere[] = "transaction_date >= ?";
    $trendParams[] = $from;
}
if ($to) {
    $trendWhere[] = "transaction_date <= ?";
    $trendParams[] = $to;
}

$whereClause = count($trendWhere) > 0 ? 'WHERE ' . implode(' AND ', $trendWhere) : '';

$trendSQL = "
    SELECT DATE_FORMAT(transaction_date, '$fmt') as period_label,
           SUM(CASE WHEN transaction_type='Income' THEN amount ELSE 0 END) as income,
           SUM(CASE WHEN transaction_type='Expense' THEN amount ELSE 0 END) as expenses
    FROM finance_ledger
    $whereClause
    GROUP BY DATE_FORMAT(transaction_date, '$fmt')
    ORDER BY MIN(transaction_date) ASC
";
$stmt = $pdo->prepare($trendSQL);
$stmt->execute($trendParams);
$revenueTrend = $stmt->fetchAll(PDO::FETCH_ASSOC);

// === 2. KPI cards (filtered by ALL filter criteria) ===
$kpiWhere = [];
$kpiParams = [];

if ($from) { $kpiWhere[] = "transaction_date >= ?"; $kpiParams[] = $from; }
if ($to)   { $kpiWhere[] = "transaction_date <= ?"; $kpiParams[] = $to; }
if ($minAmt !== null) { $kpiWhere[] = "amount >= ?"; $kpiParams[] = $minAmt; }
if ($maxAmt !== null) { $kpiWhere[] = "amount <= ?"; $kpiParams[] = $maxAmt; }
if ($category !== '') { $kpiWhere[] = "category = ?"; $kpiParams[] = $category; }

$kpiWhereStr = count($kpiWhere) > 0 ? 'AND ' . implode(' AND ', $kpiWhere) : '';

$stmt = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM finance_ledger WHERE transaction_type = 'Income' $kpiWhereStr");
$stmt->execute($kpiParams);
$totalIncome = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM finance_ledger WHERE transaction_type = 'Expense' $kpiWhereStr");
$stmt->execute($kpiParams);
$totalExpenses = $stmt->fetchColumn();

// === 3. Transactions list (with amount + type + category filters) ===
$txWhere = [];
$txParams = [];

if ($from) { $txWhere[] = "transaction_date >= ?"; $txParams[] = $from; }
if ($to)   { $txWhere[] = "transaction_date <= ?"; $txParams[] = $to; }
if ($minAmt !== null) { $txWhere[] = "amount >= ?"; $txParams[] = $minAmt; }
if ($maxAmt !== null) { $txWhere[] = "amount <= ?"; $txParams[] = $maxAmt; }
if ($type !== 'all') { $txWhere[] = "transaction_type = ?"; $txParams[] = $type; }
if ($category !== '') { $txWhere[] = "category = ?"; $txParams[] = $category; }

$txWhereStr = count($txWhere) > 0 ? 'WHERE ' . implode(' AND ', $txWhere) : '';

$stmt = $pdo->prepare("
    SELECT transaction_id, transaction_date, transaction_type, category, amount 
    FROM finance_ledger 
    $txWhereStr
    ORDER BY transaction_date DESC 
    LIMIT 20
");
$stmt->execute($txParams);
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// === 4. Available categories (for filter dropdown) ===
$categories = $pdo->query("SELECT DISTINCT category FROM finance_ledger WHERE category IS NOT NULL ORDER BY category")
    ->fetchAll(PDO::FETCH_COLUMN);

// === 5. Sales by Category ===
$salesByCategory = $pdo->query("
    SELECT fg.category, SUM(sol.quantity * sol.unit_price) as revenue
    FROM sales_order_lines sol
    JOIN inventory_finished_goods fg ON sol.item_id = fg.item_id
    JOIN sales_orders so ON sol.so_id = so.so_id
    GROUP BY fg.category
    ORDER BY revenue DESC
")->fetchAll(PDO::FETCH_ASSOC);

// === 6. Production by Status (filtered by date) ===
$prodWhere = [];
$prodParams = [];
if ($from) { $prodWhere[] = "created_at >= ?"; $prodParams[] = $from; }
if ($to)   { $prodWhere[] = "created_at <= ?"; $prodParams[] = $to; }
$prodWhereStr = count($prodWhere) > 0 ? 'WHERE ' . implode(' AND ', $prodWhere) : '';

$stmt = $pdo->prepare("SELECT status, COUNT(*) as cnt FROM production_orders $prodWhereStr GROUP BY status");
$stmt->execute($prodParams);
$prodByStatus = $stmt->fetchAll(PDO::FETCH_ASSOC);

// === 7. Order Pipeline ===
$orderPipeline = $pdo->query("SELECT order_status, COUNT(*) as cnt FROM sales_orders GROUP BY order_status ORDER BY cnt DESC")
    ->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'revenueTrend'    => $revenueTrend,
    'totalIncome'     => (float) $totalIncome,
    'totalExpenses'   => (float) $totalExpenses,
    'profit'          => (float) ($totalIncome - $totalExpenses),
    'transactions'    => $transactions,
    'categories'      => $categories,
    'salesByCategory' => $salesByCategory,
    'prodByStatus'    => $prodByStatus,
    'orderPipeline'   => $orderPipeline,
]);
