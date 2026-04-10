<?php
/**
 * AURA ERP — Mathematical Core Models
 * Implementation of BOM (Bill of Materials) and ROP (Reorder Point) algorithms.
 */

/**
 * Calculates exactly how much of each raw material is required for a specific production batch.
 * Formula: Required = (Target Batch Yield / Recipe Base Yield) * Ingredient Quantity
 *
 * @param PDO $pdo Database connection
 * @param string $recipeId The ID of the recipe to use
 * @param float $targetQuantity The desired output quantity of the finished good
 * @return array Array of required raw materials
 */
function calculateBOM(PDO $pdo, $recipeId, $targetQuantity) {
    try {
        // Get base yield of the recipe
        $stmt = $pdo->prepare("SELECT base_yield_qty, recipe_name FROM recipes WHERE recipe_id = ?");
        $stmt->execute([$recipeId]);
        $recipe = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$recipe || $recipe['base_yield_qty'] <= 0) return [];

        $scalingFactor = $targetQuantity / $recipe['base_yield_qty'];

        // Get ingredients and scale them
        $stmt = $pdo->prepare("
            SELECT ri.raw_material_id, im.item_name, ri.quantity_required, im.base_uom,
                   (ri.quantity_required * ?) as calculated_requirement,
                   COALESCE((SELECT SUM(quantity_change) FROM inventory_ledger WHERE item_id = ri.raw_material_id), 0) as current_stock
            FROM recipe_ingredients ri
            JOIN item_master im ON ri.raw_material_id = im.item_id
            WHERE ri.recipe_id = ?
        ");
        $stmt->execute([$scalingFactor, $recipeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log("BOM calculation error: " . $e->getMessage());
        return [];
    }
}

/**
 * Advanced Reorder Point (ROP) calculation including Safety Stock based on Service Level.
 *
 * Traditional Formula:
 * Safety Stock = (Max Daily Usage * Max Lead Time) - (Avg Daily Usage * Avg Lead Time)
 * ROP = (Avg Daily Usage * Avg Lead Time) + Safety Stock
 *
 * @param PDO $pdo Database connection
 * @return array List of items and their full ROP breakdown
 */
function calculateReorderPoints(PDO $pdo) {
    try {
        // Step 1: Fetch Raw Materials and current stock
        $stmt = $pdo->query("
            SELECT im.item_id, im.item_name, im.base_uom, im.min_stock_level,
                   COALESCE(SUM(il.quantity_change), 0) as current_stock
            FROM item_master im
            LEFT JOIN inventory_ledger il ON im.item_id = il.item_id
            WHERE im.category = 'Raw Material'
            GROUP BY im.item_id
        ");
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $results = [];

        foreach ($items as $item) {
            $itemId = $item['item_id'];
            
            // Step 2: Calculate daily usage statistics for the last 30 days
            $usageStmt = $pdo->prepare("
                SELECT 
                    ABS(SUM(quantity_change)) / 30 as avg_daily_usage,
                    ABS(MIN(quantity_change)) as max_single_day_usage -- Simplified substitute for daily max
                FROM inventory_ledger 
                WHERE item_id = ? AND transaction_type = 'Consumed'
                AND timestamp >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ");
            $usageStmt->execute([$itemId]);
            $usageStats = $usageStmt->fetch(PDO::FETCH_ASSOC);

            // Default fallback if no usage data exists
            $avgDailyUsage = $usageStats['avg_daily_usage'] ?? 10;
            if ($avgDailyUsage == 0) $avgDailyUsage = 10; 
            
            $maxDailyUsage = $usageStats['max_single_day_usage'] ?? ($avgDailyUsage * 1.5);
            if ($maxDailyUsage == 0) $maxDailyUsage = $avgDailyUsage * 1.5;

            // ERP Configuration Assumptions (Could be pushed to DB)
            $avgLeadTime = 7; // days to deliver
            $maxLeadTime = 10; // worst case days to deliver
            $serviceLevel = "95%"; // Standard Service Level

            // Math: Safety Stock
            $safetyStock = ($maxDailyUsage * $maxLeadTime) - ($avgDailyUsage * $avgLeadTime);
            if ($safetyStock < 0) $safetyStock = $avgDailyUsage * 3; // Fallback minimum safety buffer

            // Math: Reorder Point
            $rop = ($avgDailyUsage * $avgLeadTime) + $safetyStock;

            // Determine if Alert should trigger
            $status = 'Safe';
            $action = 'None';
            if ($item['current_stock'] <= $rop && $item['current_stock'] > $safetyStock) {
                $status = 'Warning';
                $action = 'Prepare PO';
            } elseif ($item['current_stock'] <= $safetyStock) {
                $status = 'Critical';
                $action = 'Order Immediately';
            }

            $results[] = [
                'item_id' => $item['item_id'],
                'item_name' => $item['item_name'],
                'uom' => $item['base_uom'],
                'current_stock' => round($item['current_stock'], 2),
                'avg_daily_usage' => round($avgDailyUsage, 2),
                'lead_time' => $avgLeadTime,
                'service_level' => $serviceLevel,
                'safety_stock' => round($safetyStock, 2),
                'calculated_rop' => round($rop, 2),
                'status' => $status,
                'recommended_action' => $action
            ];
        }

        return $results;

    } catch (PDOException $e) {
        error_log("ROP calculation error: " . $e->getMessage());
        return [];
    }
}
