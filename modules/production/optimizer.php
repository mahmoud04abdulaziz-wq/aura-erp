<?php
/**
 * MiskStone ERP — Smart Production Optimizer
 * GET: ?so_id=XXX
 * 
 * Analyzes a sales order's line items and returns optimized production plans:
 *   - "fastest"  → minimizes total curing time
 *   - "cheapest" → minimizes total material cost
 * 
 * For each order line, finds all candidate recipes (via mix_outputs),
 * calculates runs needed, curing hours, and material cost, then recommends
 * the best recipe per strategy.
 */
require_once __DIR__ . '/../../config/db_connect.php';
require_once __DIR__ . '/../auth/session_guard.php';
requireLogin();

header('Content-Type: application/json');

$soId = trim($_GET['so_id'] ?? '');
if (empty($soId)) {
    echo json_encode(['error' => 'Sales Order ID required.']);
    exit;
}

try {
    // 1. Get order info
    $orderStmt = $pdo->prepare("
        SELECT so.*, c.company_name 
        FROM sales_orders so 
        JOIN customers c ON so.customer_id = c.customer_id 
        WHERE so.so_id = ?
    ");
    $orderStmt->execute([$soId]);
    $order = $orderStmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        echo json_encode(['error' => 'Sales order not found.']);
        exit;
    }

    // 2. Get order line items
    $linesStmt = $pdo->prepare("
        SELECT sol.item_id, sol.quantity, sol.unit_price, im.item_name
        FROM sales_order_lines sol
        JOIN item_master im ON sol.item_id = im.item_id
        WHERE sol.so_id = ?
    ");
    $linesStmt->execute([$soId]);
    $lines = $linesStmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($lines)) {
        echo json_encode(['error' => 'No line items found for this order.']);
        exit;
    }

    // 3. For each line item, find candidate recipes
    $result = [
        'so_id'        => $soId,
        'company_name' => $order['company_name'],
        'total_price'  => (float)$order['total_price'],
        'items'        => [],
        'strategies'   => [
            'fastest'  => ['total_curing_hours' => 0, 'total_material_cost' => 0, 'recipes' => []],
            'cheapest' => ['total_curing_hours' => 0, 'total_material_cost' => 0, 'recipes' => []],
        ],
    ];

    foreach ($lines as $line) {
        $itemId   = $line['item_id'];
        $orderQty = (float)$line['quantity'];

        // Find all recipes that can produce this item
        $candidateStmt = $pdo->prepare("
            SELECT mo.recipe_id, mo.output_quantity, 
                   r.recipe_name, r.curing_time_hours, r.base_yield_qty
            FROM mix_outputs mo
            JOIN recipes r ON mo.recipe_id = r.recipe_id
            WHERE mo.item_id = ?
        ");
        $candidateStmt->execute([$itemId]);
        $candidates = $candidateStmt->fetchAll(PDO::FETCH_ASSOC);

        $itemData = [
            'item_id'    => $itemId,
            'item_name'  => $line['item_name'],
            'order_qty'  => $orderQty,
            'unit_price' => (float)$line['unit_price'],
            'candidates' => [],
        ];

        foreach ($candidates as $cand) {
            $outputPerRun = (float)$cand['output_quantity'];
            $runsNeeded   = ($outputPerRun > 0) ? (int)ceil($orderQty / $outputPerRun) : 1;
            
            // Assume 5 parallel molds/stations in the factory so curing happens concurrently
            $parallelCapacity = 5;
            $curingHours  = ceil($runsNeeded / $parallelCapacity) * (int)$cand['curing_time_hours'];

            // Calculate material cost for this recipe
            $ingStmt = $pdo->prepare("
                SELECT ri.quantity_required, im.standard_cost
                FROM recipe_ingredients ri
                JOIN item_master im ON ri.raw_material_id = im.item_id
                WHERE ri.recipe_id = ?
            ");
            $ingStmt->execute([$cand['recipe_id']]);
            $ingredients = $ingStmt->fetchAll(PDO::FETCH_ASSOC);

            $costPerRun = 0;
            foreach ($ingredients as $ing) {
                $costPerRun += (float)$ing['quantity_required'] * (float)$ing['standard_cost'];
            }
            $totalCost = $costPerRun * $runsNeeded;

            // Check raw material feasibility
            $feasible = true;
            $materialDetails = [];
            $ingDetailStmt = $pdo->prepare("
                SELECT ri.raw_material_id, ri.quantity_required, im.item_name, im.base_uom, im.standard_cost
                FROM recipe_ingredients ri
                JOIN item_master im ON ri.raw_material_id = im.item_id
                WHERE ri.recipe_id = ?
            ");
            $ingDetailStmt->execute([$cand['recipe_id']]);
            foreach ($ingDetailStmt->fetchAll(PDO::FETCH_ASSOC) as $mat) {
                $required = (float)$mat['quantity_required'] * $runsNeeded;
                $stockStmt = $pdo->prepare("SELECT COALESCE(SUM(quantity_change), 0) FROM inventory_ledger WHERE item_id = ?");
                $stockStmt->execute([$mat['raw_material_id']]);
                $stock = (float)$stockStmt->fetchColumn();

                if ($stock < $required) $feasible = false;

                $materialDetails[] = [
                    'item_id'   => $mat['raw_material_id'],
                    'item_name' => $mat['item_name'],
                    'uom'       => $mat['base_uom'],
                    'required'  => round($required, 2),
                    'in_stock'  => round($stock, 2),
                    'sufficient'=> $stock >= $required,
                ];
            }

            $itemData['candidates'][] = [
                'recipe_id'    => (int)$cand['recipe_id'],
                'recipe_name'  => $cand['recipe_name'],
                'output_per_run' => $outputPerRun,
                'runs_needed'  => $runsNeeded,
                'curing_hours' => $curingHours,
                'cost_per_run' => round($costPerRun, 2),
                'total_cost'   => round($totalCost, 2),
                'feasible'     => $feasible,
                'materials'    => $materialDetails,
            ];
        }

        // Determine best candidate for each strategy
        if (!empty($itemData['candidates'])) {
            // Fastest: lowest curing_hours (prefer feasible)
            $fastest = null;
            foreach ($itemData['candidates'] as $c) {
                if ($fastest === null || $c['curing_hours'] < $fastest['curing_hours'] 
                    || ($c['curing_hours'] === $fastest['curing_hours'] && $c['total_cost'] < $fastest['total_cost'])) {
                    $fastest = $c;
                }
            }

            // Cheapest: lowest total_cost (prefer feasible)
            $cheapest = null;
            foreach ($itemData['candidates'] as $c) {
                if ($cheapest === null || $c['total_cost'] < $cheapest['total_cost']
                    || ($c['total_cost'] === $cheapest['total_cost'] && $c['curing_hours'] < $cheapest['curing_hours'])) {
                    $cheapest = $c;
                }
            }

            $itemData['fastest_pick']  = $fastest['recipe_id'];
            $itemData['cheapest_pick'] = $cheapest['recipe_id'];

            $result['strategies']['fastest']['total_curing_hours']  += $fastest['curing_hours'];
            $result['strategies']['fastest']['total_material_cost'] += $fastest['total_cost'];
            $result['strategies']['fastest']['recipes'][] = [
                'item_id'     => $itemId,
                'item_name'   => $line['item_name'],
                'recipe_id'   => $fastest['recipe_id'],
                'recipe_name' => $fastest['recipe_name'],
                'runs_needed' => $fastest['runs_needed'],
                'curing_hours'=> $fastest['curing_hours'],
                'total_cost'  => $fastest['total_cost'],
                'feasible'    => $fastest['feasible'],
            ];

            $result['strategies']['cheapest']['total_curing_hours']  += $cheapest['curing_hours'];
            $result['strategies']['cheapest']['total_material_cost'] += $cheapest['total_cost'];
            $result['strategies']['cheapest']['recipes'][] = [
                'item_id'     => $itemId,
                'item_name'   => $line['item_name'],
                'recipe_id'   => $cheapest['recipe_id'],
                'recipe_name' => $cheapest['recipe_name'],
                'runs_needed' => $cheapest['runs_needed'],
                'curing_hours'=> $cheapest['curing_hours'],
                'total_cost'  => $cheapest['total_cost'],
                'feasible'    => $cheapest['feasible'],
            ];
        } else {
            $itemData['fastest_pick']  = null;
            $itemData['cheapest_pick'] = null;
        }

        $result['items'][] = $itemData;
    }

    // Round totals
    $result['strategies']['fastest']['total_material_cost']  = round($result['strategies']['fastest']['total_material_cost'], 2);
    $result['strategies']['cheapest']['total_material_cost'] = round($result['strategies']['cheapest']['total_material_cost'], 2);

    echo json_encode($result);

} catch (Exception $e) {
    error_log("Optimizer Error: " . $e->getMessage());
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
