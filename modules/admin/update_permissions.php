<?php
/**
 * AURA ERP — Permission Update Handler
 * Processes bulk updates to the role_permissions bridge table.
 */
require_once dirname(__DIR__, 2) . '/modules/auth/session_guard.php';
requireLogin();

// Ensure only Admins can perform this action
if (($_SESSION['role_name'] ?? '') !== 'Admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized. Admin access required.']);
    exit;
}

header('Content-Type: application/json');

$roleIds = $_POST['role_ids'] ?? [];
$perms = $_POST['perms'] ?? []; // Array: [role_id => [perm_id, perm_id, ...]]

if (empty($roleIds)) {
    echo json_encode(['success' => false, 'error' => 'No roles provided for update.']);
    exit;
}

try {
    $pdo->beginTransaction();

    foreach ($roleIds as $roleId) {
        // 1. Clear existing permissions for this role
        $stmt = $pdo->prepare("DELETE FROM role_permissions WHERE role_id = ?");
        $stmt->execute([$roleId]);

        // 2. Insert new permissions if any were checked
        if (isset($perms[$roleId]) && is_array($perms[$roleId])) {
            $insertStmt = $pdo->prepare("INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)");
            foreach ($perms[$roleId] as $permId) {
                $insertStmt->execute([$roleId, $permId]);
            }
        }
    }

    // 3. Log the administrative action
    $logStmt = $pdo->prepare("INSERT INTO system_logs (user_id, action_type, description, status) VALUES (?, 'SECURITY_UPDATE', ?, 'Success')");
    $logStmt->execute([
        $_SESSION['user_id'],
        "Bulk updated module permissions for " . count($roleIds) . " roles."
    ]);

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Permissions updated and audit log recorded.']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log("Permission Update Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
