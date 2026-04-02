<?php
/**
 * AURA ERP — User Account Management (IT/Admin View)
 * Shows pending account provisioning queue and active accounts.
 */

try {
    // Employees WITHOUT accounts (pending provisioning)
    $pendingAccounts = $pdo->query("
        SELECT e.employee_id, e.first_name, e.last_name, e.email, e.hire_date,
               e.verification_status, e.department_id,
               d.department_name,
               r.role_name, e.role_id
        FROM employees e
        JOIN departments d ON e.department_id = d.department_id
        LEFT JOIN roles r ON e.role_id = r.role_id
        LEFT JOIN users u ON e.employee_id = u.employee_id
        WHERE u.user_id IS NULL
        ORDER BY e.hire_date DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Employees WITH accounts (active accounts)
    $activeAccounts = $pdo->query("
        SELECT e.employee_id, e.first_name, e.last_name, e.email,
               d.department_name,
               r.role_name,
               u.user_id, u.account_status, u.mfa_enabled
        FROM users u
        JOIN employees e ON u.employee_id = e.employee_id
        JOIN departments d ON e.department_id = d.department_id
        LEFT JOIN roles r ON u.role_id = r.role_id
        ORDER BY e.first_name, e.last_name
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Stats
    $totalAccounts = count($activeAccounts);
    $pendingCount = count($pendingAccounts);
    $activeCount = count(array_filter($activeAccounts, fn($a) => $a['account_status'] === 'Active'));
    $suspendedCount = count(array_filter($activeAccounts, fn($a) => $a['account_status'] === 'Suspended'));
} catch (Exception $e) {
    error_log("Admin view error: " . $e->getMessage());
    $pendingAccounts = $activeAccounts = [];
    $totalAccounts = $pendingCount = $activeCount = $suspendedCount = 0;
}
?>

<!-- Stats -->
<div class="stats-grid" style="margin-bottom: 1.5rem;">
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

<!-- Pending Provisioning -->
<div class="card" style="margin-bottom: 1.5rem;">
    <div class="card-header">
        <h3><i class="fa-solid fa-user-plus" style="color:var(--accent-primary); margin-right:0.5rem;"></i>Pending Account Provisioning</h3>
        <span class="badge pending" style="font-size:0.85rem;"><?= $pendingCount ?> waiting</span>
    </div>

    <?php if ($pendingCount > 0): ?>
    <p style="padding:0 1rem; color:var(--text-secondary); font-size:0.85rem; margin-bottom:0.5rem;">
        These employees have been registered by HR but do not have a system login yet. Click <strong>"Create Account"</strong> to provision their access.
    </p>
    <?php endif; ?>

    <div style="overflow-x:auto;">
        <table class="data-table" id="pending-table">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Email</th>
                    <th>Department</th>
                    <th>Role</th>
                    <th>Hire Date</th>
                    <th>HR Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($pendingAccounts)): ?>
                    <tr><td colspan="7" style="text-align:center; padding:3rem; color:var(--text-secondary);">
                        <i class="fa-solid fa-circle-check" style="font-size:2rem; color:#10b981; display:block; margin-bottom:0.75rem;"></i>
                        All employees have been provisioned
                    </td></tr>
                <?php else: ?>
                    <?php foreach ($pendingAccounts as $emp): ?>
                        <?php
                        $fullName = $emp['first_name'] . ' ' . $emp['last_name'];
                        $statusBadge = match ($emp['verification_status']) { 'Approved' => 'completed', 'Rejected' => 'orange', default => 'pending' };
                        $hasRole = !empty($emp['role_id']);
                        ?>
                        <tr>
                            <td>
                                <span style="font-weight:600;"><?= htmlspecialchars($fullName) ?></span><br>
                                <small style="font-family:monospace; color:var(--text-secondary);">EMP-<?= $emp['employee_id'] ?></small>
                            </td>
                            <td><?= htmlspecialchars($emp['email']) ?></td>
                            <td><?= htmlspecialchars($emp['department_name']) ?></td>
                            <td>
                                <?php if ($hasRole): ?>
                                    <span class="badge in-progress"><?= htmlspecialchars($emp['role_name']) ?></span>
                                <?php else: ?>
                                    <span class="badge pending" style="opacity:0.6;">Not Assigned</span>
                                <?php endif; ?>
                            </td>
                            <td><?= $emp['hire_date'] ? date('M d, Y', strtotime($emp['hire_date'])) : '—' ?></td>
                            <td><span class="badge <?= $statusBadge ?>"><?= htmlspecialchars($emp['verification_status']) ?></span></td>
                            <td>
                                <?php if ($hasRole): ?>
                                    <button class="icon-btn" onclick="provisionAccount(<?= $emp['employee_id'] ?>, '<?= addslashes($fullName) ?>', '<?= addslashes($emp['role_name']) ?>')"
                                        style="width:auto; padding:0.4rem 0.9rem; font-size:0.8rem; color:#10b981; border-color:#10b981; gap:0.4rem;">
                                        <i class="fa-solid fa-user-plus"></i> Create Account
                                    </button>
                                <?php else: ?>
                                    <span style="font-size:0.8rem; color:var(--text-secondary);" title="HR must assign a role first">
                                        <i class="fa-solid fa-triangle-exclamation" style="color:#f59e0b;"></i> No role
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Active Accounts -->
<div class="card">
    <div class="card-header" style="flex-direction:column; align-items:flex-start; gap:1rem;">
        <div style="width:100%; display:flex; justify-content:space-between; align-items:center;">
            <h3><i class="fa-solid fa-shield-halved" style="color:var(--accent-primary); margin-right:0.5rem;"></i>Active System Accounts</h3>
        </div>
        <div style="display:flex; gap:0.75rem; flex-wrap:wrap; width:100%;">
            <input type="text" id="acct-search" placeholder="Search accounts..." style="flex:1; min-width:200px; padding:0.5rem 1rem; border:1px solid var(--border-color); border-radius:8px; font-size:0.9rem;" oninput="filterAccountTable()">
            <select id="acct-status-filter" onchange="filterAccountTable()" style="padding:0.5rem 1rem; border:1px solid var(--border-color); border-radius:8px; font-size:0.9rem;">
                <option value="All">All Statuses</option>
                <option value="Active">Active</option>
                <option value="Suspended">Suspended</option>
            </select>
        </div>
    </div>

    <div style="overflow-x:auto;">
        <table class="data-table" id="accounts-table">
            <thead>
                <tr>
                    <th>User ID</th>
                    <th>Employee</th>
                    <th>Email</th>
                    <th>Department</th>
                    <th>Role</th>
                    <th>MFA</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($activeAccounts)): ?>
                    <tr><td colspan="8" style="text-align:center; padding:3rem;">No accounts found</td></tr>
                <?php else: ?>
                    <?php foreach ($activeAccounts as $acct): ?>
                        <?php
                        $fullName = $acct['first_name'] . ' ' . $acct['last_name'];
                        $isActive = $acct['account_status'] === 'Active';
                        $statusBadge = $isActive ? 'completed' : 'orange';
                        $isSelf = ($acct['user_id'] == ($_SESSION['user_id'] ?? 0));
                        ?>
                        <tr data-name="<?= strtolower(htmlspecialchars($fullName)) ?>" data-status="<?= $acct['account_status'] ?>">
                            <td><span style="font-family:monospace;">USR-<?= $acct['user_id'] ?></span></td>
                            <td>
                                <span style="font-weight:600;"><?= htmlspecialchars($fullName) ?></span>
                                <?php if ($isSelf): ?>
                                    <span class="badge in-progress" style="font-size:0.7rem; margin-left:0.4rem;">You</span>
                                <?php endif; ?>
                                <br><small style="font-family:monospace; color:var(--text-secondary);">EMP-<?= $acct['employee_id'] ?></small>
                            </td>
                            <td><?= htmlspecialchars($acct['email']) ?></td>
                            <td><?= htmlspecialchars($acct['department_name']) ?></td>
                            <td><span class="badge in-progress"><?= htmlspecialchars($acct['role_name'] ?? '—') ?></span></td>
                            <td>
                                <?php if ($acct['mfa_enabled']): ?>
                                    <i class="fa-solid fa-shield-check" style="color:#10b981;" title="MFA Enabled"></i>
                                <?php else: ?>
                                    <i class="fa-solid fa-shield" style="color:var(--text-secondary); opacity:0.4;" title="MFA Disabled"></i>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge <?= $statusBadge ?>"><?= $acct['account_status'] ?></span></td>
                            <td>
                                <div class="action-menu-container">
                                    <button class="action-btn" onclick="this.nextElementSibling.classList.toggle('active')"><i class="fa-solid fa-ellipsis-vertical"></i></button>
                                    <div class="dropdown-menu">
                                        <?php if ($isActive): ?>
                                            <button class="dropdown-item" onclick="resetAccountPassword('<?= $acct['employee_id'] ?>')" <?= $isSelf ? 'disabled style="opacity:0.4;"' : '' ?>>
                                                <i class="fa-solid fa-key" style="color:var(--text-secondary);"></i> Reset Password
                                            </button>
                                            <button class="dropdown-item delete" onclick="toggleAccountStatus(<?= $acct['user_id'] ?>, 'suspend', '<?= addslashes($fullName) ?>')" <?= $isSelf ? 'disabled style="opacity:0.4;"' : '' ?>>
                                                <i class="fa-solid fa-user-slash"></i> Suspend
                                            </button>
                                        <?php else: ?>
                                            <button class="dropdown-item" onclick="toggleAccountStatus(<?= $acct['user_id'] ?>, 'activate', '<?= addslashes($fullName) ?>')">
                                                <i class="fa-solid fa-user-check" style="color:#10b981;"></i> Reactivate
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
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
