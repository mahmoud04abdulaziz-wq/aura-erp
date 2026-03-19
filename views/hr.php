<?php
/**
 * AURA ERP — HR View
 * Displays employees, departments, and employee management.
 */

try {
    // Fetch all employees with department info
    $employees = $pdo->query(
        "SELECT e.employee_id, e.first_name, e.last_name, e.email, e.hire_date,
                e.verification_status, e.performance_score, e.base_allowances,
                e.department_id,
                d.department_name,
                r.role_name,
                u.role_id
         FROM employees e
         JOIN departments d ON e.department_id = d.department_id
         LEFT JOIN users u ON e.employee_id = u.employee_id
         LEFT JOIN roles r ON u.role_id = r.role_id
         ORDER BY e.first_name, e.last_name"
    )->fetchAll(PDO::FETCH_ASSOC);

    // Department list for filters and modals
    $departments = $pdo->query("SELECT department_id, department_name FROM departments ORDER BY department_name")->fetchAll(PDO::FETCH_ASSOC);

    // System roles for the Add Employee form
    $roles = $pdo->query("SELECT role_id, role_name FROM roles ORDER BY role_id")->fetchAll(PDO::FETCH_ASSOC);

    // Leave Tickets query
    $leaveTickets = $pdo->query("
        SELECT lt.*, e.first_name, e.last_name, d.department_name
        FROM leave_tickets lt
        JOIN employees e ON lt.employee_id = e.employee_id
        LEFT JOIN departments d ON e.department_id = d.department_id
        ORDER BY lt.created_at DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Stats
    $totalEmployees = count($employees);
    $activeCount = count(array_filter($employees, fn($e) => $e['verification_status'] === 'Approved'));
    $pendingCount = count(array_filter($employees, fn($e) => $e['verification_status'] === 'Pending'));
    $deptCount = count($departments);
} catch (Exception $e) {
    error_log("HR view error: " . $e->getMessage());
    $employees = $departments = $leaveTickets = [];
    $totalEmployees = $activeCount = $pendingCount = $deptCount = 0;
}
?>

<style>
    .custom-select-wrapper { position: relative; user-select: none; width: 100%; }
    .custom-select { display: flex; justify-content: space-between; align-items: center; padding: 0.75rem 1rem; background: var(--bg-component, transparent); border: 1px solid var(--border-color, #e2e8f0); border-radius: 8px; cursor: pointer; font-size: 0.95rem; color: var(--text-primary); transition: all 0.2s ease; }
    .custom-select:hover { border-color: var(--accent-primary, #6366f1); }
    .custom-select-wrapper.open .custom-select { border-color: var(--accent-primary, #6366f1); box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1); }
    .custom-options { position: absolute; top: calc(100% + 5px); left: 0; right: 0; background: var(--bg-panel, #fff); border: 1px solid var(--border-color, #e2e8f0); border-radius: 8px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); opacity: 0; visibility: hidden; pointer-events: none; transform: translateY(-10px); transition: all 0.2s ease; z-index: 999; max-height: 200px; overflow-y: auto; }
    .custom-select-wrapper.open .custom-options { opacity: 1; visibility: visible; pointer-events: all; transform: translateY(0); }
    .custom-option { padding: 0.75rem 1rem; cursor: pointer; transition: background 0.2s; color: var(--text-primary); }
    .custom-option:hover, .custom-option.selected { background: var(--bg-active, #e0e7ff); color: var(--accent-primary, #6366f1); }
    .custom-select-trigger { display:flex; justify-content:space-between; width:100%; align-items:center; }
    .custom-select-trigger i { transition: transform 0.2s; color: var(--text-secondary); }
    .custom-select-wrapper.open .custom-select-trigger i { transform: rotate(180deg); }
    
    .approval-stage { display:flex; align-items:center; gap:0.5rem; font-size:0.85rem; padding:0.25rem 0.5rem; border-radius:4px; margin-bottom:0.25rem;}
    .approval-stage i { font-size:1rem; }
    .stage-pending { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }
    .stage-approved { background: rgba(16, 185, 129, 0.1); color: #10b981; }
    .stage-rejected { background: rgba(239, 68, 68, 0.1); color: #ef4444; }
</style>

<!-- Stats -->
<div class="stats-grid" style="margin-bottom: 1.5rem;">
    <div class="stat-card">
        <div class="stat-icon primary"><i class="fa-solid fa-users"></i></div>
        <div class="stat-info">
            <h3>Total Employees</h3>
            <p class="stat-value" style="font-size:1.5rem;"><?= $totalEmployees ?></p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon success"><i class="fa-solid fa-user-check"></i></div>
        <div class="stat-info">
            <h3>Approved</h3>
            <p class="stat-value" style="font-size:1.5rem;"><?= $activeCount ?></p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon warning"><i class="fa-solid fa-user-clock"></i></div>
        <div class="stat-info">
            <h3>Pending Verification</h3>
            <p class="stat-value" style="font-size:1.5rem;"><?= $pendingCount ?></p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon info"><i class="fa-solid fa-building"></i></div>
        <div class="stat-info">
            <h3>Departments</h3>
            <p class="stat-value" style="font-size:1.5rem;"><?= $deptCount ?></p>
        </div>
    </div>
</div>

<!-- Employee Table -->
<div class="card" style="margin-bottom: 1.5rem;">
    <div class="card-header" style="flex-direction:column; align-items:flex-start; gap:1rem;">
        <div style="width:100%; display:flex; justify-content:space-between; align-items:center;">
            <h3>Employee Directory</h3>
            <button class="icon-btn" onclick="openAddEmployeeModal()" style="width:auto; padding:0 1rem; color:var(--accent-primary); border-color:var(--accent-primary);">
                <i class="fa-solid fa-user-plus"></i> Add Employee
            </button>
        </div>

        <div style="display:flex; gap:0.75rem; flex-wrap:wrap; width:100%;">
            <input type="text" id="hr-search" placeholder="Search employees..." style="flex:1; min-width:200px; padding:0.5rem 1rem; border:1px solid var(--border-color); border-radius:8px; font-size:0.9rem;" oninput="filterHRTable()">
            <select id="hr-dept-filter" onchange="filterHRTable()" style="padding:0.5rem 1rem; border:1px solid var(--border-color); border-radius:8px; font-size:0.9rem;">
                <option value="All">All Departments</option>
                <?php foreach ($departments as $dept): ?>
                    <option value="<?= htmlspecialchars($dept['department_name']) ?>"><?= htmlspecialchars($dept['department_name']) ?></option>
                <?php endforeach; ?>
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
                    <th>Hire Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($employees)): ?>
                    <tr><td colspan="6" style="text-align:center; padding:3rem;">No employees found</td></tr>
                <?php else: ?>
                    <?php foreach ($employees as $emp): ?>
                        <?php
                        $statusBadge = match ($emp['verification_status']) { 'Approved' => 'completed', 'Rejected' => 'orange', default => 'pending' };
                        $fullName = $emp['first_name'] . ' ' . $emp['last_name'];
                        ?>
                        <tr data-name="<?= strtolower(htmlspecialchars($fullName)) ?>" data-dept="<?= htmlspecialchars($emp['department_name']) ?>">
                            <td><span style="font-family:monospace;">EMP-<?= $emp['employee_id'] ?></span></td>
                            <td><span style="font-weight:600;"><?= htmlspecialchars($fullName) ?></span><br><small style="color:var(--text-secondary);"><?= htmlspecialchars($emp['email']) ?></small></td>
                            <td><?= htmlspecialchars($emp['department_name']) ?></td>
                            <td><?= $emp['hire_date'] ? date('M d, Y', strtotime($emp['hire_date'])) : '—' ?></td>
                            <td><span class="badge <?= $statusBadge ?>"><?= htmlspecialchars($emp['verification_status']) ?></span></td>
                            <td>
                                <div class="action-menu-container">
                                    <button class="action-btn" onclick="this.nextElementSibling.classList.toggle('active')"><i class="fa-solid fa-ellipsis-vertical"></i></button>
                                    <div class="dropdown-menu">
                                        <button class="dropdown-item" onclick="openEditEmployeeModal('<?= $emp['employee_id'] ?>', '<?= addslashes($emp['first_name']) ?>', '<?= addslashes($emp['last_name']) ?>', '<?= addslashes($emp['email']) ?>', '<?= $emp['department_id'] ?>', '<?= $emp['role_id'] ?? '' ?>')"><i class="fa-regular fa-pen-to-square"></i> Edit</button>
                                        <button class="dropdown-item" onclick="resetUserPassword('<?= $emp['employee_id'] ?>')"><i class="fa-solid fa-key" style="color:var(--text-secondary);"></i> Reset Password</button>
                                        <button class="dropdown-item delete" onclick="deleteEmployee('<?= $emp['employee_id'] ?>')"><i class="fa-regular fa-trash-can"></i> Delete</button>
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

<!-- Leave Tickets Workflow -->
<div class="card">
    <div class="card-header" style="flex-direction:column; align-items:flex-start; gap:1rem;">
        <div style="width:100%; display:flex; justify-content:space-between; align-items:center;">
            <div>
                <h3>Leave Tickets & Approval Workflow</h3>
                <p style="font-size:0.85rem; color:var(--text-secondary); margin-top:0.25rem;">2-Stage Hierarchy: Stage 1 (Manager) &rarr; Stage 2 (HR)</p>
            </div>
            <button class="icon-btn" onclick="openSubmitLeaveModal()" style="width:auto; padding:0 1rem; color:var(--accent-primary); border-color:var(--accent-primary);">
                <i class="fa-solid fa-file-signature"></i> Request Leave
            </button>
        </div>
    </div>

    <div style="overflow-x:auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Ticket ID</th>
                    <th>Employee</th>
                    <th>Date Range</th>
                    <th>Stage 1 (Manager)</th>
                    <th>Stage 2 (HR)</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($leaveTickets)): ?>
                    <tr><td colspan="6" style="text-align:center; padding:3rem;">No active leave tickets</td></tr>
                <?php else: ?>
                    <?php foreach ($leaveTickets as $tk): ?>
                        <tr>
                            <td><span style="font-family:monospace;">LVE-<?= $tk['ticket_id'] ?></span></td>
                            <td><span style="font-weight:600;"><?= htmlspecialchars($tk['first_name'] . ' ' . $tk['last_name']) ?></span><br><small><?= htmlspecialchars($tk['department_name']) ?></small></td>
                            <td><?= date('M d', strtotime($tk['start_date'])) ?> to <?= date('M d, Y', strtotime($tk['end_date'])) ?></td>
                            <td>
                                <?php $s1Class = 'stage-' . strtolower($tk['manager_status']); $s1Icon = $tk['manager_status'] === 'Approved' ? 'check-circle' : ($tk['manager_status'] === 'Rejected' ? 'times-circle' : 'clock'); ?>
                                <div class="approval-stage <?= $s1Class ?>"><i class="fa-solid fa-<?= $s1Icon ?>"></i> <?= $tk['manager_status'] ?></div>
                            </td>
                            <td>
                                <?php $s2Class = 'stage-' . strtolower($tk['hr_status']); $s2Icon = $tk['hr_status'] === 'Approved' ? 'check-circle' : ($tk['hr_status'] === 'Rejected' ? 'times-circle' : 'clock'); ?>
                                <div class="approval-stage <?= $s2Class ?>"><i class="fa-solid fa-<?= $s2Icon ?>"></i> <?= $tk['hr_status'] ?></div>
                            </td>
                            <td>
                                <?php if ($tk['manager_status'] === 'Pending' || $tk['hr_status'] === 'Pending'): ?>
                                    <div class="action-menu-container">
                                        <button class="action-btn" onclick="this.nextElementSibling.classList.toggle('active')"><i class="fa-solid fa-ellipsis-vertical"></i></button>
                                        <div class="dropdown-menu">
                                            <button class="dropdown-item" onclick="approveLeave('<?= $tk['ticket_id'] ?>', 'approve')"><i class="fa-solid fa-check" style="color:#10b981;"></i> Approve</button>
                                            <button class="dropdown-item delete" onclick="approveLeave('<?= $tk['ticket_id'] ?>', 'reject')"><i class="fa-solid fa-xmark"></i> Reject</button>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <i class="fa-solid fa-lock" style="color:var(--text-secondary); opacity:0.5;"></i>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    const departmentsData = <?= json_encode($departments) ?>;
    const rolesData = <?= json_encode($roles ?? []) ?>;

    function filterHRTable() {
        const search = document.getElementById('hr-search').value.toLowerCase();
        const dept = document.getElementById('hr-dept-filter').value;
        document.querySelectorAll('#hr-table tbody tr').forEach(row => {
            const matchName = !search || row.dataset.name.includes(search);
            const matchDept = dept === 'All' || row.dataset.dept === dept;
            row.style.display = (matchName && matchDept) ? '' : 'none';
        });
    }

    document.addEventListener('click', e => {
        if (!e.target.closest('.action-menu-container')) {
            document.querySelectorAll('.dropdown-menu.active').forEach(m => m.classList.remove('active'));
        }
    });

    function generateDeptOptions(selectedId) {
        let html = '';
        departmentsData.forEach(d => {
            html += `<div class="custom-option ${d.department_id == selectedId ? 'selected' : ''}" data-value="${d.department_id}">${d.department_name}</div>`;
        });
        return html;
    }

    function generateRoleOptions(selectedId) {
        let html = '';
        rolesData.forEach(r => {
            html += `<div class="custom-option ${r.role_id == selectedId ? 'selected' : ''}" data-value="${r.role_id}">${r.role_name}</div>`;
        });
        return html;
    }

    function initCustomSelect(wrapper) {
        const select = wrapper.querySelector('.custom-select');
        const options = wrapper.querySelectorAll('.custom-option');
        const hiddenInput = wrapper.querySelector('input[type="hidden"]');
        const textSpan = wrapper.querySelector('.selected-text');
        
        select.addEventListener('click', (e) => {
            e.stopPropagation();
            wrapper.classList.toggle('open');
        });
        
        options.forEach(opt => {
            opt.addEventListener('click', (e) => {
                e.stopPropagation();
                hiddenInput.value = opt.dataset.value;
                textSpan.textContent = opt.textContent;
                options.forEach(o => o.classList.remove('selected'));
                opt.classList.add('selected');
                wrapper.classList.remove('open');
            });
        });
        
        const closeDropdown = () => wrapper.classList.remove('open');
        document.addEventListener('click', closeDropdown);
        return closeDropdown;
    }

    // Modal forms
    function openAddEmployeeModal() {
        showEmployeeModal();
    }

    function openEditEmployeeModal(id, fname, lname, email, deptId, roleId) {
        showEmployeeModal(id, fname, lname, email, deptId, roleId);
    }

    function showEmployeeModal(id = '', fname = '', lname = '', email = '', deptId = '', roleId = '') {
        const modal = document.getElementById('generic-modal');
        const isEdit = id !== '';
        
        const deptSelectText = isEdit ? departmentsData.find(d => d.department_id == deptId)?.department_name : 'Select Department...';
        const roleSelectText = isEdit && roleId ? rolesData.find(r => r.role_id == roleId)?.role_name : 'Select System Role...';
        
        const content = `
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
                <h2 style="margin:0;">${isEdit ? 'Edit Employee' : 'Add Employee'}</h2>
                <button type="button" onclick="document.getElementById('generic-modal').classList.remove('active')" style="background:none; border:none; font-size:1.5rem; cursor:pointer; color:var(--text-primary);">&times;</button>
            </div>
            <form onsubmit="submitEmployee(event, '${id}')">
                <div style="display:flex; gap:1rem; margin-bottom:1rem;">
                    <div style="flex:1;">
                        <label style="display:block; margin-bottom:0.5rem;">First Name *</label>
                        <input type="text" name="first_name" value="${fname}" required style="width:100%; padding:0.75rem; border:1px solid var(--border-color); border-radius:8px;">
                    </div>
                    <div style="flex:1;">
                        <label style="display:block; margin-bottom:0.5rem;">Last Name *</label>
                        <input type="text" name="last_name" value="${lname}" required style="width:100%; padding:0.75rem; border:1px solid var(--border-color); border-radius:8px;">
                    </div>
                </div>
                
                <div style="margin-bottom:1rem;">
                    <label style="display:block; margin-bottom:0.5rem;">Email *</label>
                    <input type="email" name="email" value="${email}" required style="width:100%; padding:0.75rem; border:1px solid var(--border-color); border-radius:8px;">
                </div>

                <div style="display:flex; gap:1rem; margin-bottom:1.5rem;">
                    <div style="flex:1;">
                        <label style="display:block; margin-bottom:0.5rem;">Department *</label>
                        <div class="custom-select-wrapper" id="emp-dept-wrapper">
                            <div class="custom-select">
                                <div class="custom-select-trigger">
                                    <span class="selected-text" style="${isEdit ? '' : 'opacity:0.6;'}">${deptSelectText}</span>
                                    <i class="fa-solid fa-chevron-down"></i>
                                </div>
                            </div>
                            <div class="custom-options">
                                ${generateDeptOptions(deptId)}
                            </div>
                            <input type="hidden" name="department_id" value="${deptId}" required>
                        </div>
                    </div>
                    <div style="flex:1;">
                        <label style="display:block; margin-bottom:0.5rem;">System Role *</label>
                        <div class="custom-select-wrapper" id="emp-role-wrapper">
                            <div class="custom-select">
                                <div class="custom-select-trigger">
                                    <span class="selected-text" style="${isEdit && roleId ? '' : 'opacity:0.6;'}">${roleSelectText}</span>
                                    <i class="fa-solid fa-chevron-down"></i>
                                </div>
                            </div>
                            <div class="custom-options">
                                ${generateRoleOptions(roleId)}
                            </div>
                            <input type="hidden" name="role_id" value="${roleId}" required>
                        </div>
                    </div>
                </div>

                ${!isEdit ? `<div style="margin-bottom:1.5rem;">
                    <label style="display:block; margin-bottom:0.5rem;">Hire Date *</label>
                    <input type="date" name="hire_date" required style="width:100%; padding:0.75rem; border:1px solid var(--border-color); border-radius:8px;">
                </div>` : ''}

                <button type="submit" style="padding:0.75rem; background:var(--accent-primary); color:white; border:none; border-radius:8px; width:100%; cursor:pointer; font-weight:500;">
                    ${isEdit ? 'Save Changes' : 'Create Employee'}
                </button>
            </form>
        `;
        
        modal.innerHTML = `<div class="modal-content" style="background:var(--bg-panel); padding:2.5rem; border-radius:16px; max-width:600px; margin:auto; box-shadow:var(--shadow-lg);">${content}</div>`;
        modal.classList.add('active');

        setTimeout(() => {
            initCustomSelect(modal.querySelector('#emp-dept-wrapper'));
            initCustomSelect(modal.querySelector('#emp-role-wrapper'));
        }, 50);
    }

    async function submitEmployee(e, id) {
        e.preventDefault();
        const fd = new FormData(e.target);
        if (id) fd.append('employee_id', id);
        
        const endpoint = id ? '/modules/hr/update_employee.php' : '/modules/hr/create_employee.php';
        
        try {
            const res = await fetch('<?= BASE_URL ?>' + endpoint, { method: 'POST', body: fd });
            const data = await res.json();
            if (data.success) window.location.reload();
            else alert('Error: ' + data.error);
        } catch (err) { alert('Network error'); }
    }

    async function resetUserPassword(id) {
        if (!confirm('Are you sure you want to reset this employee\\'s password back to the default (Welcome123!)?')) return;
        try {
            const fd = new FormData(); fd.append('employee_id', id);
            const res = await fetch('<?= BASE_URL ?>/modules/auth/reset_password.php', { method: 'POST', body: fd });
            const data = await res.json();
            if (data.success) alert(data.message);
            else alert('Error: ' + data.error);
        } catch (err) { alert('Network error'); }
    }

    async function deleteEmployee(id) {
        if (!confirm('Are you sure you want to permanently delete this employee?')) return;
        try {
            const fd = new FormData(); fd.append('employee_id', id);
            const res = await fetch('<?= BASE_URL ?>/modules/hr/delete_employee.php', { method: 'POST', body: fd });
            const data = await res.json();
            if (data.success) window.location.reload();
            else alert('Error: ' + data.error);
        } catch (err) { alert('Network error'); }
    }

    function openSubmitLeaveModal() {
        const modal = document.getElementById('generic-modal');
        const content = `
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
                <h2 style="margin:0;">Request Leave</h2>
                <button type="button" onclick="document.getElementById('generic-modal').classList.remove('active')" style="background:none; border:none; font-size:1.5rem; cursor:pointer; color:var(--text-primary);">&times;</button>
            </div>
            <form onsubmit="submitLeaveForm(event)">
                <div style="margin-bottom:1rem;">
                    <label style="display:block; margin-bottom:0.5rem;">Requesting Employee *</label>
                    <input type="number" name="employee_id" placeholder="Enter your Employee ID (e.g. 1)" required style="width:100%; padding:0.75rem; border:1px solid var(--border-color); border-radius:8px;">
                    <small style="color:var(--text-secondary);">*In a real system, this would be auto-filled from session.</small>
                </div>
                <div style="display:flex; gap:1rem; margin-bottom:1rem;">
                    <div style="flex:1;">
                        <label style="display:block; margin-bottom:0.5rem;">Start Date *</label>
                        <input type="date" name="start_date" required style="width:100%; padding:0.75rem; border:1px solid var(--border-color); border-radius:8px;">
                    </div>
                    <div style="flex:1;">
                        <label style="display:block; margin-bottom:0.5rem;">End Date *</label>
                        <input type="date" name="end_date" required style="width:100%; padding:0.75rem; border:1px solid var(--border-color); border-radius:8px;">
                    </div>
                </div>
                <div style="margin-bottom:1.5rem;">
                    <label style="display:block; margin-bottom:0.5rem;">Reason *</label>
                    <textarea name="reason" required rows="3" style="width:100%; padding:0.75rem; border:1px solid var(--border-color); border-radius:8px; font-family:inherit;"></textarea>
                </div>
                <button type="submit" style="padding:0.75rem; background:var(--accent-primary); color:white; border:none; border-radius:8px; width:100%; cursor:pointer; font-weight:500;">Submit Ticket</button>
            </form>
        `;
        modal.innerHTML = `<div class="modal-content" style="background:var(--bg-panel); padding:2.5rem; border-radius:16px; max-width:500px; margin:auto; box-shadow:var(--shadow-lg);">${content}</div>`;
        modal.classList.add('active');
    }

    async function submitLeaveForm(e) {
        e.preventDefault();
        try {
            const fd = new FormData(e.target);
            const res = await fetch('<?= BASE_URL ?>/modules/hr/submit_leave.php', { method: 'POST', body: fd });
            const data = await res.json();
            if (data.success) window.location.reload();
            else alert('Error: ' + data.error);
        } catch (err) { alert('Network error'); }
    }

    async function approveLeave(ticketId, action) {
        if (!confirm('Are you sure you want to ' + action + ' this ticket?')) return;
        try {
            const fd = new FormData();
            fd.append('ticket_id', ticketId);
            fd.append('action', action);
            const res = await fetch('<?= BASE_URL ?>/modules/hr/approve_leave.php', { method: 'POST', body: fd });
            const data = await res.json();
            if (data.success) window.location.reload();
            else alert('Error: ' + data.error);
        } catch (err) { alert('Network error'); }
    }
</script>