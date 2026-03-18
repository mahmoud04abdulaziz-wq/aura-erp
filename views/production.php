<?php
/**
 * AURA ERP — Production View
 * Displays production orders and machine status from the database.
 * Tables: production_orders, item_master, users, employees
 */

try {
    $batches = $pdo->query(
        "SELECT po.production_id, po.machine_id, po.production_date, po.target_quantity,
                po.actual_yield, po.status, po.qa_status,
                im.item_name,
                CONCAT(e.first_name, ' ', e.last_name) as operator_name
         FROM production_orders po
         JOIN item_master im ON po.item_id = im.item_id
         JOIN users u ON po.operator_user_id = u.user_id
         JOIN employees e ON u.employee_id = e.employee_id
         ORDER BY po.created_at DESC"
    )->fetchAll();

    // Get unique machines and their statuses
    $machines = $pdo->query(
        "SELECT DISTINCT machine_id,
                CASE
                    WHEN status IN ('Mixing','Curing') THEN 'Running'
                    WHEN status = 'Failed' THEN 'Maintenance'
                    ELSE 'Idle'
                END as machine_status
         FROM production_orders
         WHERE machine_id IS NOT NULL
         ORDER BY machine_id"
    )->fetchAll();
} catch (Exception $e) {
    error_log("Production view error: " . $e->getMessage());
    $batches = [];
    $machines = [];
}
?>

<div class="card">
    <div class="card-header" style="flex-direction:column; align-items:flex-start; gap:1rem;">
        <div style="width:100%; display:flex; justify-content:space-between; align-items:center;">
            <h3>Active Production Batches</h3>
            <button class="icon-btn"
                style="width:auto; padding:0 1rem; color:var(--accent-primary); border-color:var(--accent-primary);">
                <i class="fa-solid fa-play"></i> Start Batch
            </button>
        </div>
    </div>

    <div style="overflow-x:auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Batch ID</th>
                    <th>Material</th>
                    <th>Machine</th>
                    <th>Operator</th>
                    <th>Progress</th>
                    <th>Status</th>
                    <th>QA</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($batches)): ?>
                    <tr>
                        <td colspan="7" style="text-align:center; color:var(--text-secondary); padding:3rem;">
                            <i class="fa-solid fa-industry"
                                style="font-size:2rem; opacity:0.3; display:block; margin-bottom:0.75rem;"></i>
                            No production orders found
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($batches as $batch): ?>
                        <?php
                        $progress = $batch['target_quantity'] > 0
                            ? min(100, round(($batch['actual_yield'] / $batch['target_quantity']) * 100))
                            : 0;

                        $statusClass = match ($batch['status']) {
                            'Completed' => 'completed',
                            'Failed' => 'orange',
                            'Planned' => 'pending',
                            default => 'in-progress'
                        };

                        $qaClass = match ($batch['qa_status']) {
                            'Passed' => 'completed',
                            'Failed' => 'orange',
                            'Rework' => 'blue',
                            default => 'gray'
                        };
                        ?>
                        <tr>
                            <td><span style="font-weight:700; color:var(--text-secondary);">
                                    <?= htmlspecialchars($batch['production_id']) ?>
                                </span></td>
                            <td><span style="font-weight:500;">
                                    <?= htmlspecialchars($batch['item_name']) ?>
                                </span></td>
                            <td>
                                <?= htmlspecialchars($batch['machine_id'] ?? '—') ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($batch['operator_name']) ?>
                            </td>
                            <td style="width:150px;">
                                <div
                                    style="display:flex; justify-content:space-between; font-size:0.75rem; color:var(--text-secondary); margin-bottom:0.25rem;">
                                    <span>
                                        <?= $progress ?>%
                                    </span>
                                </div>
                                <div class="progress-bar-container">
                                    <div class="progress-bar-fill"
                                        style="width:<?= $progress ?>%; background:<?= $progress >= 100 ? 'var(--success)' : 'var(--accent-primary)' ?>;">
                                    </div>
                                </div>
                            </td>
                            <td><span class="badge <?= $statusClass ?>">
                                    <?= htmlspecialchars($batch['status']) ?>
                                </span></td>
                            <td><span class="badge <?= $qaClass ?>">
                                    <?= htmlspecialchars($batch['qa_status']) ?>
                                </span></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Machine Floor Status -->
<?php if (!empty($machines)): ?>
    <div style="margin-top: 1.5rem;" class="card production-status">
        <div class="card-header">
            <h3>Machine Floor Status</h3>
        </div>
        <div class="machine-list"
            style="display:grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap:1rem;">
            <?php foreach ($machines as $machine): ?>
                <?php
                $statusClass = match ($machine['machine_status']) {
                    'Running' => 'running',
                    'Maintenance' => 'maintenance',
                    default => 'idle'
                };
                ?>
                <div class="machine-item" style="border: 1px solid var(--border-color); padding: 1rem; border-radius: 8px;">
                    <span class="machine-name" style="font-weight:600;">
                        <?= htmlspecialchars($machine['machine_id']) ?>
                    </span>
                    <div style="display:flex; align-items:center; gap:0.5rem; margin-top:0.5rem;">
                        <div class="status-indicator <?= $statusClass ?>"></div>
                        <span class="status-text">
                            <?= htmlspecialchars($machine['machine_status']) ?>
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>