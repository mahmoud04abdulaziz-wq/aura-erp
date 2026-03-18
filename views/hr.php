<?php
/**
 * AURA ERP — HR View
 * Displays employees, departments, and employee management.
 * Tables: employees, departments, users, roles
 */

try {
    // Fetch all employees with department info
    $employees = $pdo->query(
        "SELECT e.employee_id, e.first_name, e.last_name, e.email, e.hire_date,
                e.verification_status, e.performance_score, e.base_allowances,
                d.department_name,
                r.role_name
         FROM employees e
         JOIN departments d ON e.department_id = d.department_id
         LEFT JOIN users u ON e.employee_id = u.employee_id
         LEFT JOIN roles r ON u.role_id = r.role_id
         ORDER BY e.first_name, e.last_name"
    )->fetchAll();

    // Department list for filters
    $departments = $pdo->query("SELECT department_name FROM departments ORDER BY department_name")->fetchAll(PDO::FETCH_COLUMN);

    // Stats
    $totalEmployees = count($employees);
    $activeCount = count(array_filter($employees, fn($e) => $e['verification_status'] === 'Approved'));
    $pendingCount = count(array_filter($employees, fn($e) => $e['verification_status'] === 'Pending'));
    $deptCount = count($departments);
} catch (Exception $e) {
    error_log("HR view error: " . $e->getMessage());
    $employees = [];
    $departments = [];
    $totalEmployees = $activeCount = $pendingCount = $deptCount = 0;
}
?>

<!-- Stats -->
<div class="stats-grid" style="margin-bottom: 1.5rem;">
    <div class="stat-card">
        <div class="stat-icon primary"><i class="fa-solid fa-users"></i></div>
        <div class="stat-info">
            <h3>Total Employees</h3>
            <p class="stat-value" style="font-size:1.5rem;">
                <?= $totalEmployees ?>
            </p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon success"><i class="fa-solid fa-user-check"></i></div>
        <div class="stat-info">
            <h3>Approved</h3>
            <p class="stat-value" style="font-size:1.5rem;">
                <?= $activeCount ?>
            </p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon warning"><i class="fa-solid fa-user-clock"></i></div>
        <div class="stat-info">
            <h3>Pending Verification</h3>
            <p class="stat-value" style="font-size:1.5rem;">
                <?= $pendingCount ?>
            </p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon info"><i class="fa-solid fa-building"></i></div>
        <div class="stat-info">
            <h3>Departments</h3>
            <p class="stat-value" style="font-size:1.5rem;">
                <?= $deptCount ?>
            </p>
        </div>
    </div>
</div>

<!-- Employee Table -->
<div class="card">
    <div class="card-header" style="flex-direction:column; align-items:flex-start; gap:1rem;">
        <div style="width:100%; display:flex; justify-content:space-between; align-items:center;">
            <h3>Employee Directory</h3>
            <button class="icon-btn"
                style="width:auto; padding:0 1rem; color:var(--accent-primary); border-color:var(--accent-primary);">
                <i class="fa-solid fa-user-plus"></i> Add Employee
            </button>
        </div>

        <!-- Filter Row -->
        <div style="display:flex; gap:0.75rem; flex-wrap:wrap; width:100%;">
            <input type="text" id="hr-search" placeholder="Search employees..."
                style="flex:1; min-width:200px; padding:0.5rem 1rem; border:1px solid var(--border-color); border-radius:8px; font-size:0.9rem;"
                oninput="filterHRTable()">
            <select id="hr-dept-filter" onchange="filterHRTable()"
                style="padding:0.5rem 1rem; border:1px solid var(--border-color); border-radius:8px; font-size:0.9rem;">
                <option value="All">All Departments</option>
                <?php foreach ($departments as $dept): ?>
                    <option value="<?= htmlspecialchars($dept) ?>">
                        <?= htmlspecialchars($dept) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select id="hr-status-filter" onchange="filterHRTable()"
                style="padding:0.5rem 1rem; border:1px solid var(--border-color); border-radius:8px; font-size:0.9rem;">
                <option value="All">All Status</option>
                <option value="Approved">Approved</option>
                <option value="Pending">Pending</option>
                <option value="Rejected">Rejected</option>
            </select>
        </div>
    </div>

    <div style="overflow-x:auto;">
        <table class="data-table" id="hr-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Department</th>
                    <th>Role</th>
                    <th>Hire Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($employees)): ?>
                    <tr>
                        <td colspan="7" style="text-align:center; color:var(--text-secondary); padding:3rem;">
                            <i class="fa-solid fa-users"
                                style="font-size:2rem; opacity:0.3; display:block; margin-bottom:0.75rem;"></i>
                            No employees found
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($employees as $emp): ?>
                        <?php
                        $statusBadge = match ($emp['verification_status']) {
                            'Approved' => 'completed',
                            'Rejected' => 'orange',
                            default => 'pending'
                        };
                        $fullName = $emp['first_name'] . ' ' . $emp['last_name'];
                        ?>
                        <tr data-name="<?= strtolower(htmlspecialchars($fullName)) ?>"
                            data-dept="<?= htmlspecialchars($emp['department_name']) ?>"
                            data-status="<?= htmlspecialchars($emp['verification_status']) ?>">
                            <td><span style="font-family:monospace; color:var(--text-secondary);">EMP-
                                    <?= $emp['employee_id'] ?>
                                </span></td>
                            <td><span style="font-weight:600;">
                                    <?= htmlspecialchars($fullName) ?>
                                </span></td>
                            <td>
                                <?= htmlspecialchars($emp['department_name']) ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($emp['role_name'] ?? '—') ?>
                            </td>
                            <td>
                                <?= $emp['hire_date'] ? date('M d, Y', strtotime($emp['hire_date'])) : '—' ?>
                            </td>
                            <td><span class="badge <?= $statusBadge ?>">
                                    <?= htmlspecialchars($emp['verification_status']) ?>
                                </span></td>
                            <td>
                                <div class="action-menu-container">
                                    <button class="action-btn" onclick="this.nextElementSibling.classList.toggle('active')">
                                        <i class="fa-solid fa-ellipsis-vertical"></i>
                                    </button>
                                    <div class="dropdown-menu">
                                        <button class="dropdown-item"><i class="fa-regular fa-eye"></i> View Profile</button>
                                        <button class="dropdown-item"><i class="fa-regular fa-pen-to-square"></i> Edit</button>
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
    function filterHRTable() {
        const search = document.getElementById('hr-search').value.toLowerCase();
        const dept = document.getElementById('hr-dept-filter').value;
        const status = document.getElementById('hr-status-filter').value;

        document.querySelectorAll('#hr-table tbody tr').forEach(row => {
            const matchName = !search || row.dataset.name.includes(search);
            const matchDept = dept === 'All' || row.dataset.dept === dept;
            const matchStatus = status === 'All' || row.dataset.status === status;
            row.style.display = (matchName && matchDept && matchStatus) ? '' : 'none';
        });
    }
    document.addEventListener('click', e => {
        if (!e.target.closest('.action-menu-container')) {
            document.querySelectorAll('.dropdown-menu.active').forEach(m => m.classList.remove('active'));
        }
    });
</script>