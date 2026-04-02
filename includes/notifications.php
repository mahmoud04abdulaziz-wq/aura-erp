<?php
/**
 * AURA ERP — Notification Helper
 * Provides a universal function to push live events to the notification feed.
 */

if (!function_exists('addNotification')) {
    /**
     * Pushes a new event to the live notification system.
     * 
     * @param PDO    $pdo      Database connection
     * @param string $title    Short headline (e.g. "New Employee")
     * @param string $message  Detailed description
     * @param string $type     Category: 'hr', 'inventory', 'finance', 'production', 'system'
     * @param int|null $userId Optional specific recipient
     * @return bool            Success status
     */
    function addNotification($pdo, $title, $message, $type = 'system', $userId = null) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO notifications (title, message, type, user_id) 
                VALUES (?, ?, ?, ?)
            ");
            return $stmt->execute([$title, $message, $type, $userId]);
        } catch (Exception $e) {
            error_log("Notification Error: " . $e->getMessage());
            return false;
        }
    }
}
