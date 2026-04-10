<?php
$employees = $pdo->query("SELECT COUNT(*) FROM employees WHERE verification_status = 'Approved'")->fetchColumn();
$pendingEmps = $pdo->query("SELECT COUNT(*) FROM employees WHERE verification_status = 'Pending'")->fetchColumn();
$leaveReqs = $pdo->query("SELECT COUNT(*) FROM leave_tickets WHERE hr_status = 'Pending'")->fetchColumn();

// Fetch Pending Leave Tickets
$tickets = $pdo->query("
    SELECT lt.ticket_id, e.first_name, e.last_name, lt.start_date, lt.end_date, lt.reason 
    FROM leave_tickets lt
    JOIN employees e ON lt.employee_id = e.employee_id
    WHERE lt.hr_status = 'Pending'
    ORDER BY lt.created_at ASC
")->fetchAll();
?>

<div class="dashboard-container">
    <div class="welcome-banner" style="background: linear-gradient(135deg, #1e293b, #8b5cf6); color: white;">
        <h1>HR & Operations Dashboard</h1>
        <p>Employee Management & Leave Verification</p>
    </div>

    <!-- Stats Grid -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon info" style="background: rgba(139, 92, 246, 0.1); color: #8b5cf6;">
                <i class="fa-solid fa-users"></i>
            </div>
            <div class="stat-info">
                <h3>Active Staff</h3>
                <p class="stat-value"><?= $employees ?></p>
                <span class="stat-trend positive">Approved Employees</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon warning" style="background: rgba(245, 158, 11, 0.1); color: #f59e0b;">
                <i class="fa-solid fa-user-clock"></i>
            </div>
            <div class="stat-info">
                <h3>Pending Profiles</h3>
                <p class="stat-value"><?= $pendingEmps ?></p>
                <span class="stat-trend neutral">Awaiting HR Review</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon primary">
                <i class="fa-solid fa-calendar-day"></i>
            </div>
            <div class="stat-info">
                <h3>Leave Requests</h3>
                <p class="stat-value"><?= $leaveReqs ?></p>
                <span class="stat-trend neutral">Requires Action</span>
            </div>
        </div>
    </div>

    <div class="card" style="margin-top: 2rem;">
        <div class="card-header">
            <h3>Pending Leave Requests</h3>
            <a href="<?= BASE_URL ?>/app.php?view=hr" class="btn-text">Go to HR Module</a>
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Ticket ID</th>
                    <th>Employee</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Reason</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($tickets)): ?>
                    <tr><td colspan="6" style="text-align:center;">No pending leave requests.</td></tr>
                <?php else: ?>
                    <?php foreach ($tickets as $t): ?>
                        <tr>
                            <td>LVE-<?= htmlspecialchars($t['ticket_id']) ?></td>
                            <td style="font-weight:600;"><?= htmlspecialchars($t['first_name'] . ' ' . $t['last_name']) ?></td>
                            <td><?= htmlspecialchars($t['start_date']) ?></td>
                            <td><?= htmlspecialchars($t['end_date']) ?></td>
                            <td><i style="color:#64748b;"><?= htmlspecialchars($t['reason']) ?></i></td>
                            <td>
                                <button class="badge completed" style="border:none; cursor:pointer;"><i class="fa-solid fa-check"></i> Approve</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
