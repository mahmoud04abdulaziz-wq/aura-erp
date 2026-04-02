<?php
/**
 * AURA ERP — User Account Management (IT/Admin View)
 * Shows pending account provisioning queue and active accounts.
 */

try {
    // --- SECTION 1: User Accounts (Existing) ---
    // (Previous queries remain the same)
    $pendingAccounts = $pdo->query("
        SELECT e.employee_id, e.first_name, e.last_name, e.email, e.hire_date,
               e.verification_status, e.department_id, d.department_name, r.role_name, e.role_id
        FROM employees e
        JOIN departments d ON e.department_id = d.department_id
        LEFT JOIN roles r ON e.role_id = r.role_id
        LEFT JOIN users u ON e.employee_id = u.employee_id
        WHERE u.user_id IS NULL
        ORDER BY e.hire_date DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

    $activeAccounts = $pdo->query("
        SELECT e.employee_id, e.first_name, e.last_name, e.email, d.department_name,
               r.role_name, u.user_id, u.account_status, u.mfa_enabled
        FROM users u
        JOIN employees e ON u.employee_id = e.employee_id
        JOIN departments d ON e.department_id = d.department_id
        LEFT JOIN roles r ON u.role_id = r.role_id
        ORDER BY e.first_name, e.last_name
    ")->fetchAll(PDO::FETCH_ASSOC);

    // --- SECTION 2: Role Permissions (New) ---
    $roles = $pdo->query("SELECT role_id, role_name FROM roles ORDER BY role_id")->fetchAll(PDO::FETCH_ASSOC);
    $allPermissions = $pdo->query("SELECT permission_id, module_access, description FROM permissions ORDER BY module_access")->fetchAll(PDO::FETCH_ASSOC);
    
    // Fetch existing mappings into an associative array for quick lookup: $mappings[role_id][perm_id] = true
    $rawMappings = $pdo->query("SELECT role_id, permission_id FROM role_permissions")->fetchAll(PDO::FETCH_ASSOC);
    $mappings = [];
    foreach ($rawMappings as $m) {
        $mappings[$m['role_id']][$m['permission_id']] = true;
    }

    // --- SECTION 3: System Audit Logs (New) ---
    $auditLogs = $pdo->query("
        SELECT l.*, e.first_name, e.last_name, r.role_name
        FROM system_logs l
        JOIN users u ON l.user_id = u.user_id
        JOIN employees e ON u.employee_id = e.employee_id
        JOIN roles r ON u.role_id = r.role_id
        ORDER BY l.timestamp DESC
        LIMIT 100
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Stats
    $totalAccounts = count($activeAccounts);
    $pendingCount = count($pendingAccounts);
    $activeCount = count(array_filter($activeAccounts, fn($a) => $a['account_status'] === 'Active'));
    $suspendedCount = count(array_filter($activeAccounts, fn($a) => $a['account_status'] === 'Suspended'));

} catch (Exception $e) {
    error_log("Admin view error: " . $e->getMessage());
    $pendingAccounts = $activeAccounts = $roles = $allPermissions = $auditLogs = [];
    $totalAccounts = $pendingCount = $activeCount = $suspendedCount = 0;
}
?>

<!-- Admin Tab Navigation -->
<div class="admin-tabs-nav">
    <div class="tab-link active" onclick="switchTab('tab-accounts')"><i class="fa-solid fa-users-gear"></i> User Accounts</div>
    <div class="tab-link" onclick="switchTab('tab-permissions')"><i class="fa-solid fa-shield-keyhole"></i> Role Permissions</div>
    <div class="tab-link" onclick="switchTab('tab-logs')"><i class="fa-solid fa-list-ul"></i> System Audit Log</div>
</div>

<!-- Tab 1: User Accounts -->
<div id="tab-accounts" class="admin-tab active">
    <!-- Existing Layout -->
    <div class="stats-grid" style="margin-bottom: 1.5rem;">
        <!-- ... Stats Content ... -->
        <div class="stat-card">
            <div class="stat-icon primary"><i class="fa-solid fa-users-gear"></i></div>
            <div class="stat-info">
                <h3>Total Accounts</h3>
                <p class="stat-value" style="font-size:1.5rem;"><?= $totalAccounts ?></p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon warning"><i class="fa-solid fa-user-clock"></i></div>
            <div class="stat-info">
                <h3>Pending Provisioning</h3>
                <p class="stat-value" style="font-size:1.5rem;"><?= $pendingCount ?></p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon success"><i class="fa-solid fa-user-check"></i></div>
            <div class="stat-info">
                <h3>Active Accounts</h3>
                <p class="stat-value" style="font-size:1.5rem;"><?= $activeCount ?></p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(239,68,68,0.1); color:#ef4444;"><i class="fa-solid fa-user-slash"></i></div>
            <div class="stat-info">
                <h3>Suspended</h3>
                <p class="stat-value" style="font-size:1.5rem;"><?= $suspendedCount ?></p>
            </div>
        </div>
    </div>

    <!-- Pending Provisioning Table -->
    <div class="card" style="margin-bottom: 1.5rem;">
        <div class="card-header">
            <h3><i class="fa-solid fa-user-plus" style="color:var(--accent-primary); margin-right:0.5rem;"></i>Pending Account Provisioning</h3>
            <span class="badge pending" style="font-size:0.85rem;"><?= $pendingCount ?> waiting</span>
        </div>
        <div style="overflow-x:auto;">
            <table class="data-table">
                <thead><tr><th>Employee</th><th>Email</th><th>Department</th><th>Role</th><th>Action</th></tr></thead>
                <tbody>
                    <?php if (empty($pendingAccounts)): ?>
                        <tr><td colspan="5" style="text-align:center; padding:2rem; color:var(--text-secondary);">No pending accounts.</td></tr>
                    <?php else: ?>
                        <?php foreach ($pendingAccounts as $emp): ?>
                            <tr>
                                <td><b><?= htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']) ?></b></td>
                                <td><?= htmlspecialchars($emp['email']) ?></td>
                                <td><?= htmlspecialchars($emp['department_name']) ?></td>
                                <td><span class="badge in-progress"><?= htmlspecialchars($emp['role_name'] ?? 'Unassigned') ?></span></td>
                                <td>
                                    <?php if ($emp['role_id']): ?>
                                        <button class="icon-btn" onclick="provisionAccount(<?= $emp['employee_id'] ?>, '<?= addslashes($emp['first_name'].' '.$emp['last_name']) ?>', '<?= addslashes($emp['role_name']) ?>')" style="width:auto; padding:0.4rem 0.8rem; font-size:0.8rem; color:var(--success); border-color:var(--success);">Create Account</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Active Accounts Table -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fa-solid fa-shield-halved" style="color:var(--accent-primary); margin-right:0.5rem;"></i>Active System Accounts</h3>
        </div>
        <div style="overflow-x:auto;">
            <table class="data-table">
                <thead><tr><th>User ID</th><th>Employee</th><th>Email</th><th>Role</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php foreach ($activeAccounts as $acct): ?>
                        <tr>
                            <td>USR-<?= $acct['user_id'] ?></td>
                            <td><?= htmlspecialchars($acct['first_name'] . ' ' . $acct['last_name']) ?></td>
                            <td><?= htmlspecialchars($acct['email']) ?></td>
                            <td><span class="badge in-progress"><?= htmlspecialchars($acct['role_name']) ?></span></td>
                            <td><span class="badge <?= $acct['account_status'] === 'Active' ? 'completed' : 'orange' ?>"><?= $acct['account_status'] ?></span></td>
                            <td>
                                <button class="action-btn" onclick="toggleAccountStatus(<?= $acct['user_id'] ?>, '<?= $acct['account_status'] === 'Active' ? 'suspend' : 'activate' ?>', '<?= addslashes($acct['first_name']) ?>')">
                                    <i class="fa-solid <?= $acct['account_status'] === 'Active' ? 'fa-user-slash' : 'fa-user-check' ?>"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Tab 2: Role Permissions Matrix -->
<div id="tab-permissions" class="admin-tab">
    <div class="card">
        <div class="card-header" style="flex-direction:row; justify-content:space-between;">
            <div>
                <h3><i class="fa-solid fa-matrix" style="color:var(--accent-primary); margin-right:0.5rem;"></i>Global Permissions Matrix</h3>
                <p style="font-size:0.85rem; color:var(--text-secondary); margin-top:0.25rem;">Define which roles can access specific system modules.</p>
            </div>
            <button onclick="savePermissions(event)" class="icon-btn" style="width:auto; padding:0.6rem 1.2rem; background:var(--accent-primary); color:white; border:none;">
                <i class="fa-solid fa-floppy-disk"></i> Save Permissions
            </button>
        </div>

        <form id="permissions-form">
            <?php foreach ($roles as $role): ?>
                <input type="hidden" name="role_ids[]" value="<?= $role['role_id'] ?>">
            <?php endforeach; ?>
            <div style="overflow-x:auto;">
                <table class="matrix-table">
                    <thead>
                        <tr>
                            <th class="matrix-module-name">Module / Feature</th>
                            <?php foreach ($roles as $role): ?>
                                <th><?= htmlspecialchars($role['role_name']) ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allPermissions as $perm): ?>
                            <tr>
                                <td class="matrix-module-name">
                                    <div style="text-transform:capitalize;"><?= str_replace('_', ' ', $perm['module_access']) ?></div>
                                    <div style="font-weight:400; font-size:0.75rem; color:var(--text-secondary);"><?= htmlspecialchars($perm['description'] ?? 'No description') ?></div>
                                </td>
                                <?php foreach ($roles as $role): ?>
                                    <td>
                                        <label class="perm-toggle">
                                            <input type="checkbox" name="perms[<?= $role['role_id'] ?>][]" value="<?= $perm['permission_id'] ?>" 
                                                <?= isset($mappings[$role['role_id']][$perm['permission_id']]) ? 'checked' : '' ?>>
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </form>
    </div>
</div>

<!-- Tab 3: System Audit Log -->
<div id="tab-logs" class="admin-tab">
    <div class="card">
        <div class="card-header">
            <h3><i class="fa-solid fa-clock-rotate-left" style="color:var(--accent-primary); margin-right:0.5rem;"></i>System Audit Logs</h3>
            <p style="font-size:0.85rem; color:var(--text-secondary);">Last 100 security and data events.</p>
        </div>
        <div style="overflow-x:auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Timestamp</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Description</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($auditLogs)): ?>
                        <tr><td colspan="5" style="text-align:center; padding:2rem;">No logs found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($auditLogs as $log): ?>
                            <tr>
                                <td style="font-size:0.85rem; color:var(--text-secondary);"><?= date('M d, H:i:s', strtotime($log['timestamp'])) ?></td>
                                <td>
                                    <div style="font-weight:600; font-size:0.9rem;"><?= htmlspecialchars($log['first_name'] . ' ' . $log['last_name']) ?></div>
                                    <div style="font-size:0.75rem; color:var(--text-secondary);"><?= htmlspecialchars($log['role_name']) ?></div>
                                </td>
                                <td><span style="font-family:monospace; font-weight:600;"><?= htmlspecialchars($log['action_type']) ?></span></td>
                                <td style="font-size:0.85rem; max-width:300px;"><?= htmlspecialchars($log['description']) ?></td>
                                <td><span class="log-status <?= $log['status'] ?>"><?= $log['status'] ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    // Close all dropdown menus when clicking elsewhere
    document.addEventListener('click', e => {
        if (!e.target.closest('.action-menu-container')) {
            document.querySelectorAll('.dropdown-menu.active').forEach(m => m.classList.remove('active'));
        }
    });

    // Filter active accounts table
    function filterAccountTable() {
        const search = document.getElementById('acct-search').value.toLowerCase();
        const status = document.getElementById('acct-status-filter').value;
        document.querySelectorAll('#accounts-table tbody tr').forEach(row => {
            if (!row.dataset.name) return; // skip "no results" row
            const matchName = !search || row.dataset.name.includes(search);
            const matchStatus = status === 'All' || row.dataset.status === status;
            row.style.display = (matchName && matchStatus) ? '' : 'none';
        });
    }

    // Provision a new account
    function provisionAccount(empId, name, roleName) {
        if (!confirm(`Create a system account for ${name}?\n\nRole: ${roleName}\nDefault password: Welcome123!\n\nThe employee will be able to log in after this.`)) return;

        const fd = new FormData();
        fd.append('employee_id', empId);

        fetch('<?= BASE_URL ?>/modules/admin/create_user_account.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    window.location.reload();
                } else {
                    alert('Error: ' + data.error);
                }
            })
            .catch(() => alert('Network error'));
    }

    // Suspend or reactivate an account
    function toggleAccountStatus(userId, action, name) {
        const verb = action === 'suspend' ? 'suspend' : 'reactivate';
        if (!confirm(`Are you sure you want to ${verb} the account for ${name}?`)) return;

        const fd = new FormData();
        fd.append('user_id', userId);
        fd.append('action', action);

        fetch('<?= BASE_URL ?>/modules/admin/toggle_account_status.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    window.location.reload();
                } else {
                    alert('Error: ' + data.error);
                }
            })
            .catch(() => alert('Network error'));
    }

    // Reset password (reuses existing endpoint)
    async function resetAccountPassword(empId) {
        if (!confirm('Reset this account\'s password to the default (Welcome123!)?')) return;
        try {
            const fd = new FormData();
            fd.append('employee_id', empId);
            const res = await fetch('<?= BASE_URL ?>/modules/auth/reset_password.php', { method: 'POST', body: fd });
            const data = await res.json();
            if (data.success) alert(data.message);
            else alert('Error: ' + data.error);
        } catch (err) { alert('Network error'); }
    }
</script>
