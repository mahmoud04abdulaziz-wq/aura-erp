// --- HR MODULE ---

const employees = [
    { id: 'EMP-001', name: 'Mohammed Ali', dept: 'Production', title: 'Shift Supervisor', status: 'Active', salary: '1,200 JOD' },
    { id: 'EMP-002', name: 'Sarah Ahmed', dept: 'HR', title: 'HR Manager', status: 'Active', salary: '1,500 JOD' },
    { id: 'EMP-003', name: 'John Smith', dept: 'Finance', title: 'Accountant', status: 'On Leave', salary: '1,100 JOD' },
    { id: 'EMP-004', name: 'Lina Kareem', dept: 'Sales', title: 'Sales Rep', status: 'Active', salary: '950 JOD' },
    { id: 'EMP-005', name: 'Omar Yassin', dept: 'IT', title: 'Sys Admin', status: 'Active', salary: '1,300 JOD' }
];

let leaveTickets = [
    { id: 'LT-001', empId: 'EMP-004', empName: 'Lina Kareem', dept: 'Sales', startDate: '2026-03-10', endDate: '2026-03-15', reason: 'Annual Leave', stage: 1, status: 'Pending Supervisor', history: [{ date: new Date(Date.now() - 172800000).toISOString(), action: 'Submitted', by: 'Lina Kareem' }], lastActionDate: Date.now() - 172800000 },
    { id: 'LT-002', empId: 'EMP-003', empName: 'John Smith', dept: 'Finance', startDate: '2026-04-01', endDate: '2026-04-05', reason: 'Sick Leave', stage: 3, status: 'Pending HR', history: [{ date: new Date(Date.now() - 86400000).toISOString(), action: 'Approved Stage 1', by: 'Supervisor' }, { date: new Date(Date.now() - 4000000).toISOString(), action: 'Approved Stage 2', by: 'PM' }], lastActionDate: Date.now() - 4000000 }
];

let currentHRSearch = '';
let currentHRFilterDept = 'All';
let currentHRFilterStatus = 'All';
let currentHRTab = 'employees';
let currentUserRole = 'Admin';

window.handleRoleChange = function (role) {
    currentUserRole = role;
    if (document.getElementById('view-hr')?.classList.contains('active')) {
        if (currentHRTab === 'leave-tickets') renderLeaveTicketsTab();
        const m = document.getElementById('generic-modal');
        if (m && m.classList.contains('active') && m.innerHTML.includes('Leave Ticket Workflow')) m.classList.remove('active');
    }
};

window.renderHRModule = function () {
    const container = document.getElementById('view-hr');
    if (!container.dataset.shellBuilt) {
        container.innerHTML = `
            <div class="card">
                <div class="card-header" style="flex-direction:column; align-items:flex-start; gap:1rem; border-bottom:none; padding-bottom:0;">
                    <div style="width:100%; display:flex; justify-content:space-between; align-items:center;">
                        <h3>Human Resources</h3>
                        <div id="hr-header-actions"></div>
                    </div>
                </div>
                <div class="hr-tabs" style="display:flex; gap:2rem; padding: 0 1.5rem; border-bottom: 1px solid var(--border-color); margin-bottom: 1.5rem;">
                    <button class="tab-btn active" data-tab="employees" style="background:none; border:none; padding:1rem 0; font-weight:600; color:var(--accent-primary); border-bottom: 3px solid var(--accent-primary); cursor:pointer;">Employee Management</button>
                    <button class="tab-btn" data-tab="leave-tickets" style="background:none; border:none; padding:1rem 0; font-weight:600; color:var(--text-secondary); cursor:pointer;">Leave Tickets (3-Stage Workflow)</button>
                </div>
                <div id="hr-tab-content" style="padding: 0 1.5rem 1.5rem 1.5rem;"></div>
            </div>`;
        container.dataset.shellBuilt = 'true';

        container.querySelectorAll('.tab-btn').forEach(tab => {
            tab.addEventListener('click', (e) => {
                container.querySelectorAll('.tab-btn').forEach(t => {
                    t.style.color = 'var(--text-secondary)';
                    t.style.borderBottom = 'none';
                    t.classList.remove('active');
                });
                e.target.style.color = 'var(--accent-primary)';
                e.target.style.borderBottom = '3px solid var(--accent-primary)';
                e.target.classList.add('active');
                currentHRTab = e.target.dataset.tab;
                currentHRTab === 'employees' ? renderEmployeesTab() : renderLeaveTicketsTab();
            });
        });
    }
    currentHRTab === 'employees' ? renderEmployeesTab() : renderLeaveTicketsTab();
};

function renderEmployeesTab() {
    document.getElementById('hr-header-actions').innerHTML = `<button id="btn-add-employee" class="icon-btn" style="width:auto; padding:0 1rem; color:var(--accent-primary); border-color:var(--accent-primary);"><i class="fa-solid fa-plus"></i> Add Employee</button>`;
    document.getElementById('hr-tab-content').innerHTML = `
        <div style="width:100%; display:flex; gap:1rem; flex-wrap:wrap; margin-bottom:1.5rem;">
            <div class="action-menu-container">
                <button id="btn-hr-filter" style="padding:0.5rem 1rem; border:1px solid var(--border-color); background:white; border-radius:6px; display:flex; align-items:center; gap:0.5rem; cursor:pointer;"><i class="fa-solid fa-filter"></i> Filter</button>
                <div class="dropdown-menu left-align" id="hr-filter-menu" style="width:220px; padding:1rem;">
                    <div style="margin-bottom:1rem;"><label style="display:block; font-size:0.75rem; color:var(--text-secondary); margin-bottom:0.5rem; font-weight:600;">Department</label><select id="filter-dept" style="width:100%; padding:0.5rem; border:1px solid var(--border-color); border-radius:4px;"><option value="All">All</option><option value="Production">Production</option><option value="HR">HR</option><option value="Finance">Finance</option><option value="Sales">Sales</option><option value="IT">IT</option></select></div>
                    <div><label style="display:block; font-size:0.75rem; color:var(--text-secondary); margin-bottom:0.5rem; font-weight:600;">Status</label><select id="filter-status" style="width:100%; padding:0.5rem; border:1px solid var(--border-color); border-radius:4px;"><option value="All">All</option><option value="Active">Active</option><option value="On Leave">On Leave</option></select></div>
                </div>
            </div>
            <input type="text" id="hr-search" placeholder="Search employees..." style="padding:0.5rem 1rem; border:1px solid var(--border-color); border-radius:6px; flex:1; min-width:200px;">
        </div>
        <div style="overflow-x:auto;">
            <table class="data-table" id="hr-table">
                <thead><tr><th>Emp ID</th><th>Full Name</th><th>Department</th><th>Job Title</th><th>Status</th><th>Salary</th><th>Actions</th></tr></thead>
                <tbody></tbody>
            </table>
        </div>`;

    document.getElementById('hr-search').value = currentHRSearch;
    document.getElementById('filter-dept').value = currentHRFilterDept;
    document.getElementById('filter-status').value = currentHRFilterStatus;

    document.getElementById('hr-search').addEventListener('input', e => { currentHRSearch = e.target.value; updateHRTable(); });
    document.getElementById('btn-hr-filter').addEventListener('click', e => { e.stopPropagation(); document.getElementById('hr-filter-menu').classList.toggle('active'); });
    document.getElementById('filter-dept').addEventListener('change', e => { currentHRFilterDept = e.target.value; updateHRTable(); });
    document.getElementById('filter-status').addEventListener('change', e => { currentHRFilterStatus = e.target.value; updateHRTable(); });
    document.getElementById('btn-add-employee').addEventListener('click', () => openEmployeeModal(null));
    updateHRTable();
}

window.updateHRTable = function () {
    const tableBody = document.querySelector('#hr-table tbody');
    if (!tableBody) return;
    const filtered = employees.filter(emp => {
        const matchSearch = emp.name.toLowerCase().includes(currentHRSearch.toLowerCase()) || emp.id.toLowerCase().includes(currentHRSearch.toLowerCase());
        const matchDept = currentHRFilterDept === 'All' || emp.dept === currentHRFilterDept;
        const matchStatus = currentHRFilterStatus === 'All' || emp.status === currentHRFilterStatus;
        return matchSearch && matchDept && matchStatus;
    });
    tableBody.innerHTML = filtered.map(emp => `<tr>
        <td><span style="font-family:monospace; color:var(--text-secondary);">${emp.id}</span></td>
        <td><div style="display:flex; align-items:center; gap:0.75rem;"><div style="width:32px; height:32px; background:var(--bg-sidebar); color:white; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:0.8rem;">${emp.name.charAt(0)}</div><span style="font-weight:500;">${emp.name}</span></div></td>
        <td>${emp.dept}</td><td>${emp.title}</td>
        <td><span class="badge ${emp.status === 'Active' ? 'completed' : 'pending'}">${emp.status}</span></td>
        <td>${emp.salary}</td>
        <td><div class="action-menu-container"><button class="action-btn" onclick="toggleHRMenu(this, '${emp.id}')"><i class="fa-solid fa-ellipsis-vertical"></i></button><div class="dropdown-menu" id="hr-menu-${emp.id}" style="width:160px;"><button class="dropdown-item" onclick="editEmployee('${emp.id}')"><i class="fa-regular fa-pen-to-square"></i> Edit</button><button class="dropdown-item delete" onclick="deleteEmployee('${emp.id}')"><i class="fa-solid fa-trash"></i> Remove</button></div></div></td>
    </tr>`).join('');
};

function renderLeaveTicketsTab() {
    document.getElementById('hr-header-actions').innerHTML = `
        <button id="btn-escalate-sim" class="icon-btn" style="width:auto; padding:0 1rem; color:var(--warning); border-color:var(--warning); margin-right: 0.5rem;" title="Simulate 24h Pass (Escalation)"><i class="fa-solid fa-clock-rotate-left"></i> Simulate 24h Pass</button>
        <button id="btn-new-ticket" class="icon-btn" style="width:auto; padding:0 1rem; color:var(--accent-primary); border-color:var(--accent-primary);"><i class="fa-solid fa-plus"></i> New Leave Request</button>
    `;
    document.getElementById('btn-escalate-sim').addEventListener('click', simulate24HourPass);
    document.getElementById('btn-new-ticket').addEventListener('click', openNewTicketModal);
    document.getElementById('hr-tab-content').innerHTML = `
        <div style="overflow-x:auto;">
            <table class="data-table" id="leave-ticket-table">
                <thead><tr><th>Ticket ID</th><th>Requester</th><th>Department</th><th>Dates</th><th>Reason</th><th>Current Stage</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody></tbody>
            </table>
        </div>`;
    updateLeaveTicketsTable();
}

function updateLeaveTicketsTable() {
    const tbody = document.querySelector('#leave-ticket-table tbody');
    if (!tbody) return;
    tbody.innerHTML = leaveTickets.map(ticket => {
        let bc = ticket.status === 'Approved' ? 'completed' : (ticket.status === 'Rejected' ? 'error' : 'pending');
        const escBadge = ticket.status.includes('Escalated HR') ? `<span class="badge" style="background:var(--warning); color:white; margin-left:0.5rem; font-size:0.65rem;">ESCALATED</span>` : '';
        return `<tr>
            <td><span style="font-family:monospace; color:var(--text-secondary);">${ticket.id}</span></td>
            <td><span style="font-weight:500;">${ticket.empName}</span><br><small style="color:var(--text-secondary);">${ticket.empId}</small></td>
            <td>${ticket.dept}</td><td>${ticket.startDate} to ${ticket.endDate}</td><td>${ticket.reason}</td>
            <td>Stage ${ticket.stage} ${escBadge}</td>
            <td><span class="badge ${bc}" ${ticket.status === 'Rejected' ? 'style="background:var(--danger); color:white;"' : ''}>${ticket.status}</span></td>
            <td><button class="btn-text" onclick="viewLeaveTicket('${ticket.id}')">Review Workflow</button></td>
        </tr>`;
    }).join('');
}

window.viewLeaveTicket = function (ticketId) {
    const ticket = leaveTickets.find(t => t.id === ticketId);
    if (!ticket) return;

    let canAct = false, actionText = "";
    if (currentUserRole === 'Admin' && ticket.status !== 'Approved' && ticket.status !== 'Rejected') canAct = true;
    else if (ticket.status !== 'Approved' && ticket.status !== 'Rejected') {
        if (ticket.stage === 1 && (currentUserRole === 'Production Supervisor' || ticket.status.includes('Escalated HR'))) {
            if (currentUserRole === 'Production Supervisor' && !ticket.status.includes('Escalated')) canAct = true;
            actionText = "Verify no halt to priority orders.";
        } else if (ticket.stage === 2 && (currentUserRole === 'Project Manager' || ticket.status.includes('Escalated HR'))) {
            if (currentUserRole === 'Project Manager' && !ticket.status.includes('Escalated')) canAct = true;
            actionText = "Verify overall project deadlines are met.";
        } else if (ticket.stage === 3 && currentUserRole === 'HR Manager') {
            canAct = true; actionText = "Final check against leave balance & Payroll.";
        }
        if ((ticket.stage === 1 || ticket.stage === 2) && ticket.status.includes('Escalated') && currentUserRole === 'HR Manager') {
            canAct = true; actionText = "ESCALATION: Acting on behalf of pending approver.";
        }
    }

    const s1 = ticket.stage > 1 || ticket.status === 'Approved' ? 'completed' : (ticket.stage === 1 ? 'active' : 'pending');
    const s2 = ticket.stage > 2 || ticket.status === 'Approved' ? 'completed' : (ticket.stage === 2 ? 'active' : 'pending');
    const s3 = ticket.status === 'Approved' ? 'completed' : (ticket.stage === 3 ? 'active' : 'pending');

    let historyHtml = ticket.history.map(h => `
        <div style="font-size:0.8rem; border-left:2px solid var(--border-color); padding-left:1rem; margin-bottom:0.5rem;">
            <div style="color:var(--text-secondary)">${new Date(h.date).toLocaleString()}</div>
            <div><strong>${h.by}:</strong> ${h.action}</div>
            ${h.reason ? `<div style="color:red; margin-top:0.25rem;">Reason: ${h.reason}</div>` : ''}
        </div>`).join('');

    let actionHtml = '';
    if (canAct || currentUserRole === 'Admin') {
        actionHtml = `
            <div style="margin-top:1.5rem; padding-top:1.5rem; border-top:1px solid var(--border-color);">
                <h4 style="margin-bottom:0.5rem;">Your Action needed as ${currentUserRole}</h4>
                ${actionText ? `<p style="font-size:0.8rem; color:var(--text-secondary); margin-bottom:1rem;">Guideline: ${actionText}</p>` : ''}
                <div style="display:flex; gap:1rem;">
                    <button style="padding:0.5rem 1rem; background:green; color:white; border:none; border-radius:4px; cursor:pointer;" onclick="processTicket('${ticket.id}', 'approve')">Approve</button>
                    <div style="display:flex; flex-direction:column; gap:0.5rem; flex:1;">
                        <input type="text" id="reject-reason-${ticket.id}" placeholder="Mandatory reason for rejection..." style="padding:0.5rem; border:1px solid var(--border-color); border-radius:4px;">
                        <button style="padding:0.5rem 1rem; background:red; color:white; border:none; border-radius:4px; cursor:pointer; align-self:flex-start;" onclick="processTicket('${ticket.id}', 'reject')">Reject</button>
                    </div>
                </div>
                ${currentUserRole === 'Admin' ? `<p style="font-size:0.75rem; color:var(--warning); margin-top:1rem;">* Super-User bypass active.</p>` : ''}
            </div>`;
    }

    const content = `
        <div class="modal-card" style="width:600px; max-width:90vw;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
                <h2 style="margin:0;">Workflow: ${ticket.id}</h2>
                <button onclick="document.getElementById('generic-modal').classList.remove('active')" style="background:none; border:none; font-size:1.5rem; cursor:pointer;">&times;</button>
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-bottom:1.5rem; background:var(--bg-sidebar); color:white; padding:1rem; border-radius:8px;">
                <div><small style="color:#94a3b8;">Requester</small><div><strong>${ticket.empName}</strong></div></div>
                <div><small style="color:#94a3b8;">Reason</small><div>${ticket.reason}</div></div>
            </div>
            <div style="display:flex; justify-content:space-between; position:relative; margin-bottom: 2rem;">
                <div style="position:absolute; top:15px; left:10%; right:10%; height:2px; background:var(--border-color); z-index:0;"></div>
                <div style="display:flex; flex-direction:column; align-items:center; z-index:1; width:33%;"><div style="width:30px; height:30px; border-radius:50%; background:${s1 === 'completed' ? 'var(--success)' : (s1 === 'active' ? 'var(--accent-primary)' : 'white')}; border:2px solid ${s1 === 'pending' ? 'var(--border-color)' : 'transparent'}; color:white; display:flex; align-items:center; justify-content:center;">1</div><div>Supervisor</div></div>
                <div style="display:flex; flex-direction:column; align-items:center; z-index:1; width:33%;"><div style="width:30px; height:30px; border-radius:50%; background:${s2 === 'completed' ? 'var(--success)' : (s2 === 'active' ? 'var(--accent-primary)' : 'white')}; border:2px solid ${s2 === 'pending' ? 'var(--border-color)' : 'transparent'}; color:white; display:flex; align-items:center; justify-content:center;">2</div><div>PM</div></div>
                <div style="display:flex; flex-direction:column; align-items:center; z-index:1; width:33%;"><div style="width:30px; height:30px; border-radius:50%; background:${s3 === 'completed' ? 'var(--success)' : (s3 === 'active' ? 'var(--accent-primary)' : 'white')}; border:2px solid ${s3 === 'pending' ? 'var(--border-color)' : 'transparent'}; color:white; display:flex; align-items:center; justify-content:center;">3</div><div>HR Manager</div></div>
            </div>
            ${actionHtml}
            <div style="margin-top:2rem; max-height:150px; overflow-y:auto; padding-right:1rem;">${historyHtml}</div>
        </div>`;

    const modal = document.getElementById('generic-modal');
    modal.innerHTML = `<div class="modal-content" id="modal-content-body" style="background:var(--bg-panel); padding:2rem; border-radius:16px; box-shadow:var(--shadow-lg); max-width:700px; margin:auto;">${content}</div>`;
    modal.classList.add('active');
};

window.processTicket = function (id, action) {
    const ticket = leaveTickets.find(t => t.id === id);
    if (!ticket) return;

    if (action === 'reject') {
        const reason = document.getElementById(`reject-reason-${ticket.id}`).value;
        if (!reason) return alert("MANDATORY: You must provide a reason for rejection.");
        ticket.status = 'Rejected';
        ticket.history.push({ date: new Date().toISOString(), action: `Rejected at Stage ${ticket.stage}`, by: currentUserRole, reason: reason });
    } else {
        let actionDesc = '';
        if (currentUserRole === 'Admin') {
            ticket.status = 'Approved'; ticket.stage = 3; actionDesc = 'Admin Bypass: Approved';
        } else {
            if (ticket.stage === 1) { ticket.stage = 2; ticket.status = 'Pending PM'; actionDesc = 'Approved Stage 1'; }
            else if (ticket.stage === 2) { ticket.stage = 3; ticket.status = 'Pending HR'; actionDesc = 'Approved Stage 2'; }
            else if (ticket.stage === 3) { ticket.status = 'Approved'; actionDesc = 'Final Approval'; }
        }
        ticket.lastActionDate = Date.now();
        ticket.history.push({ date: new Date().toISOString(), action: actionDesc, by: currentUserRole });
    }
    document.getElementById('generic-modal').classList.remove('active');
    updateLeaveTicketsTable();
};

window.simulate24HourPass = function () {
    let esc = 0;
    const now = Date.now(), day = 86400000;
    leaveTickets.forEach(t => {
        if (t.status !== 'Approved' && t.status !== 'Rejected' && !t.status.includes('Escalated HR') && (now - t.lastActionDate > day)) {
            t.status += ' (Escalated HR)';
            t.history.push({ date: new Date().toISOString(), action: 'Auto-Escalation (>24h SLA breached)', by: 'System' });
            esc++;
        }
    });
    alert(esc > 0 ? `${esc} ticket(s) escalated to HR.` : 'No tickets breached SLA.');
    if (esc > 0) updateLeaveTicketsTable();
};

window.openNewTicketModal = function () {
    const modalContent = `
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;"><h2 style="margin:0;">New Leave Request</h2><button onclick="document.getElementById('generic-modal').classList.remove('active')" style="background:none; border:none; font-size:1.5rem; cursor:pointer;">&times;</button></div>
        <form onsubmit="submitNewTicket(event)">
            <label style="display:block; margin-bottom:0.5rem; font-weight:500;">Select Employee</label>
            <select name="empId" required style="width:100%; padding:0.75rem; border:1px solid var(--border-color); border-radius:8px; margin-bottom:1rem;">${employees.map(e => `<option value="${e.id}">${e.name} (${e.dept})</option>`).join('')}</select>
            <div style="display:flex; gap:1rem; margin-bottom:1rem;">
                <div style="flex:1;"><label style="display:block; margin-bottom:0.5rem;">Start Date</label><input type="date" name="sd" required style="width:100%; padding:0.75rem; border:1px solid var(--border-color); border-radius:8px;"></div>
                <div style="flex:1;"><label style="display:block; margin-bottom:0.5rem;">End Date</label><input type="date" name="ed" required style="width:100%; padding:0.75rem; border:1px solid var(--border-color); border-radius:8px;"></div>
            </div>
            <label style="display:block; margin-bottom:0.5rem; font-weight:500;">Reason</label>
            <select name="reason" required style="width:100%; padding:0.75rem; border:1px solid var(--border-color); border-radius:8px; margin-bottom:1rem;"><option>Annual Leave</option><option>Sick Leave</option><option>Unpaid Leave</option></select>
            <button type="submit" style="padding:0.75rem; background:var(--accent-primary); color:white; border:none; border-radius:8px; cursor:pointer; width:100%;">Submit Ticket</button>
        </form>`;
    const m = document.getElementById('generic-modal');
    m.innerHTML = `<div class="modal-content" id="modal-content-body" style="background:white; padding:2rem; border-radius:12px; max-width:500px; margin:2rem auto;">${modalContent}</div>`;
    m.classList.add('active');
};

window.submitNewTicket = function (e) {
    e.preventDefault();
    const fd = new FormData(e.target);
    const emp = employees.find(x => x.id === fd.get('empId'));
    leaveTickets.push({
        id: `LT-00${leaveTickets.length + 1}`, empId: emp.id, empName: emp.name, dept: emp.dept,
        startDate: fd.get('sd'), endDate: fd.get('ed'), reason: fd.get('reason'), stage: 1, status: 'Pending Supervisor',
        lastActionDate: Date.now(), history: [{ date: new Date().toISOString(), action: 'Ticket Submitted', by: currentUserRole }]
    });
    document.getElementById('generic-modal').classList.remove('active');
    updateLeaveTicketsTable();
};

window.toggleHRMenu = function (btn, id) {
    document.querySelectorAll('.dropdown-menu.active').forEach(m => { if (m.id !== `hr-menu-${id}` && m.id !== 'hr-filter-menu') m.classList.remove('active'); });
    const m = document.getElementById(`hr-menu-${id}`); if (m) m.classList.toggle('active');
};

window.openEmployeeModal = function (emp = null) {
    const isEdit = !!emp;
    const idVal = isEdit ? emp.id : `EMP-0${employees.length + 1}`.padEnd(7, '0');
    const content = `
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
            <h2 style="margin:0;">${isEdit ? 'Edit' : 'Add'} Employee</h2><button onclick="document.getElementById('generic-modal').classList.remove('active')" style="background:none; border:none; font-size:1.5rem; cursor:pointer;">&times;</button>
        </div>
        <form onsubmit="handleEmployeeSubmit(event, '${idVal}', ${isEdit})">
            <input type="text" name="name" value="${isEdit ? emp.name : ''}" placeholder="Full Name" required style="width:100%; padding:0.75rem; border:1px solid var(--border-color); border-radius:8px; margin-bottom:1rem;">
            <select name="dept" required style="width:100%; padding:0.75rem; border:1px solid var(--border-color); border-radius:8px; margin-bottom:1rem;">
                <option value="Production" ${isEdit && emp.dept === 'Production' ? 'selected' : ''}>Production</option>
                <option value="HR" ${isEdit && emp.dept === 'HR' ? 'selected' : ''}>HR</option>
                <option value="Finance" ${isEdit && emp.dept === 'Finance' ? 'selected' : ''}>Finance</option>
                <option value="Sales" ${isEdit && emp.dept === 'Sales' ? 'selected' : ''}>Sales</option>
                <option value="IT" ${isEdit && emp.dept === 'IT' ? 'selected' : ''}>IT</option>
            </select>
            <input type="text" name="title" value="${isEdit ? emp.title : ''}" placeholder="Job Title" required style="width:100%; padding:0.75rem; border:1px solid var(--border-color); border-radius:8px; margin-bottom:1rem;">
            <select name="status" required style="width:100%; padding:0.75rem; border:1px solid var(--border-color); border-radius:8px; margin-bottom:1rem;"><option value="Active" ${isEdit && emp.status === 'Active' ? 'selected' : ''}>Active</option><option value="On Leave" ${isEdit && emp.status === 'On Leave' ? 'selected' : ''}>On Leave</option></select>
            <input type="text" name="salary" value="${isEdit ? emp.salary : ''}" placeholder="Salary" required style="width:100%; padding:0.75rem; border:1px solid var(--border-color); border-radius:8px; margin-bottom:1rem;">
            <button type="submit" style="padding:0.75rem; background:var(--accent-primary); color:white; border:none; border-radius:8px; width:100%; cursor:pointer;">Save</button>
        </form>`;
    const m = document.getElementById('generic-modal');
    m.innerHTML = `<div class="modal-content" id="modal-content-body" style="background:white; padding:2rem; border-radius:12px; max-width:500px; margin:2rem auto;">${content}</div>`;
    m.classList.add('active');
};
window.handleEmployeeSubmit = function (e, id, isEdit) {
    e.preventDefault(); const fd = new FormData(e.target);
    const dat = { id: id, name: fd.get('name'), dept: fd.get('dept'), title: fd.get('title'), status: fd.get('status'), salary: fd.get('salary') };
    if (isEdit) employees[employees.findIndex(x => x.id === id)] = dat; else employees.push(dat);
    updateHRTable(); document.getElementById('generic-modal').classList.remove('active');
};
window.deleteEmployee = function (id) {
    if (confirm('Remove employee?')) { employees.splice(employees.findIndex(e => e.id === id), 1); updateHRTable(); }
};
window.editEmployee = function (id) { openEmployeeModal(employees.find(e => e.id === id)); };
