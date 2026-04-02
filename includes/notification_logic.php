<?php
/**
 * AURA ERP — Notification Engine
 * Calculates real-time alerts based on system thresholds.
 */
function getSystemNotifications($pdo) {
    $notifications = [];

    // --- 1. LIVE EVENT FEED (New Notifications Table) ---
    try {
        // Fetch fresh events from the last 24 hours
        $events = $pdo->query("
            SELECT n.*, 
                   CASE 
                     WHEN n.created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR) THEN 'Just now'
                     WHEN n.created_at >= DATE_SUB(NOW(), INTERVAL 6 HOUR) THEN 'Recent'
                     ELSE 'Earlier today'
                   END as time_label
            FROM notifications n
            WHERE n.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ORDER BY n.created_at DESC
            LIMIT 10
        ")->fetchAll(PDO::FETCH_ASSOC);

        foreach ($events as $event) {
            $icon = match($event['type']) {
                'hr' => 'fa-solid fa-user-plus',
                'inventory' => 'fa-solid fa-boxes-stacked',
                'finance' => 'fa-solid fa-hand-holding-dollar',
                'production' => 'fa-solid fa-industry',
                default => 'fa-solid fa-circle-info'
            };
            $color = match($event['type']) {
                'hr' => '#f59e0b',
                'inventory' => '#ef4444',
                'finance' => '#10b981',
                'production' => '#6366f1',
                default => '#94a3b8'
            };

            $notifications[] = [
                'type' => $event['type'],
                'icon' => $icon,
                'color' => $color,
                'title' => $event['title'],
                'message' => $event['message'],
                'time' => $event['time_label'],
                'timestamp' => strtotime($event['created_at'])
            ];
        }
    } catch (Exception $e) { error_log("Notify Error (Feed): " . $e->getMessage()); }

    // --- 2. PERMANENT STATUS ALERTS (Calculated) ---
    // (We keep these as "always-on" warnings until solved)
    
    // Low Stock
    try {
        $lowStock = $pdo->query("
            SELECT im.item_name, im.min_stock_level, 
                   COALESCE(SUM(il.quantity_change), 0) as current_stock
            FROM item_master im
            LEFT JOIN inventory_ledger il ON im.item_id = il.item_id
            GROUP BY im.item_id
            HAVING current_stock <= im.min_stock_level
            LIMIT 2
        ")->fetchAll(PDO::FETCH_ASSOC);

        foreach ($lowStock as $item) {
            $notifications[] = [
                'type' => 'inventory',
                'icon' => 'fa-solid fa-triangle-exclamation',
                'color' => '#ef4444',
                'title' => 'Critical Stock',
                'message' => "{$item['item_name']} remains below minimum levels.",
                'time' => 'System Warning',
                'timestamp' => 0 // Keep at bottom of list
            ];
        }
    } catch (Exception $e) { error_log("Notify Error (Inventory): " . $e->getMessage()); }

    // Sort the final list: Event feed (by timestamp) first, then static warnings
    usort($notifications, function($a, $b) {
        return ($b['timestamp'] ?? 0) <=> ($a['timestamp'] ?? 0);
    });

    return $notifications;
}
