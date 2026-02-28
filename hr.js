// --- HR MODULE ---

const employees = [
    { id: 'EMP-001', name: 'Mohammed Ali', dept: 'Production', title: 'Shift Supervisor', status: 'Active', salary: '1,200 JOD' },
    { id: 'EMP-002', name: 'Sarah Ahmed', dept: 'HR', title: 'HR Manager', status: 'Active', salary: '1,500 JOD' },
    { id: 'EMP-003', name: 'John Smith', dept: 'Finance', title: 'Accountant', status: 'On Leave', salary: '1,100 JOD' },
    { id: 'EMP-004', name: 'Lina Kareem', dept: 'Sales', title: 'Sales Rep', status: 'Active', salary: '950 JOD' },
    { id: 'EMP-005', name: 'Omar Yassin', dept: 'IT', title: 'Sys Admin', status: 'Active', salary: '1,300 JOD' }
];

// HR State
let currentHRSearch = '';
let currentHRFilterDept = 'All';
let currentHRFilterStatus = 'All';

window.renderHRModule = function () {
    const container = document.getElementById('view-hr');

    // Build Shell if not present
    if (!container.dataset.shellBuilt) {
        container.innerHTML = `
            <div class="card">
                <div class="card-header" style="flex-direction:column; align-items:flex-start; gap:1rem;">
                    <div style="width:100%; display:flex; justify-content:space-between; align-items:center;">
                        <h3>Employee Management</h3>
                        <button id="btn-add-employee" class="icon-btn" style="width:auto; padding:0 1rem; color:var(--accent-primary); border-color:var(--accent-primary);"><i class="fa-solid fa-plus"></i> Add Employee</button>
                    </div>
                    
                    <div style="width:100%; display:flex; gap:1rem; flex-wrap:wrap;">
                        <div class="action-menu-container">
                            <button id="btn-hr-filter" style="padding: 0.5rem 1rem; border: 1px solid var(--border-color); background:white; border-radius: 6px; display:flex; align-items:center; gap:0.5rem; cursor:pointer;">
                                <i class="fa-solid fa-filter"></i> Filter
                            </button>
                            <div class="dropdown-menu left-align" id="hr-filter-menu" style="width:220px; padding:1rem;">
                                <div style="margin-bottom:1rem;">
                                    <label style="display:block; font-size:0.75rem; color:var(--text-secondary); margin-bottom:0.5rem; font-weight:600;">Department</label>
                                    <select id="filter-dept" style="width:100%; padding:0.5rem; border:1px solid var(--border-color); border-radius:4px;">
                                        <option value="All">All Departments</option>
                                        <option value="Production">Production</option>
                                        <option value="HR">HR</option>
                                        <option value="Finance">Finance</option>
                                        <option value="Sales">Sales</option>
                                        <option value="IT">IT</option>
                                    </select>
                                </div>
                                <div>
                                    <label style="display:block; font-size:0.75rem; color:var(--text-secondary); margin-bottom:0.5rem; font-weight:600;">Status</label>
                                    <select id="filter-status" style="width:100%; padding:0.5rem; border:1px solid var(--border-color); border-radius:4px;">
                                        <option value="All">All Statuses</option>
                                        <option value="Active">Active</option>
                                        <option value="On Leave">On Leave</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <input type="text" id="hr-search" placeholder="Search employees by Name, ID or Job..." style="padding: 0.5rem 1rem; border: 1px solid var(--border-color); border-radius: 6px; flex:1; min-width:200px;">
                    </div>
                </div>

                <div style="overflow-x:auto;">
                    <table class="data-table" id="hr-table">
                        <thead>
                            <tr>
                                <th>Emp ID</th>
                                <th>Full Name</th>
                                <th>Department</th>
                                <th>Job Title</th>
                                <th>Status</th>
                                <th>Salary</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Rows injected by JS -->
                        </tbody>
                    </table>
                </div>
            </div>
        `;
        container.dataset.shellBuilt = 'true';

        // Events

        // Search
        document.getElementById('hr-search').addEventListener('input', (e) => {
            currentHRSearch = e.target.value;
            updateHRTable();
        });

        // Filter Dropdown Toggle
        document.getElementById('btn-hr-filter').addEventListener('click', (e) => {
            e.stopPropagation();
            const menu = document.getElementById('hr-filter-menu');
            menu.classList.toggle('active');
        });

        // Filter Logic
        document.getElementById('filter-dept').addEventListener('change', (e) => {
            currentHRFilterDept = e.target.value;
            updateHRTable();
        });

        document.getElementById('filter-status').addEventListener('change', (e) => {
            currentHRFilterStatus = e.target.value;
            updateHRTable();
        });

        // Add Employee Modal
        document.getElementById('btn-add-employee').addEventListener('click', () => openEmployeeModal(null));
    }

    updateHRTable();
}

window.updateHRTable = function () {
    const tableBody = document.querySelector('#hr-table tbody');
    if (!tableBody) return;

    const filtered = employees.filter(emp => {
        const matchesSearch = emp.name.toLowerCase().includes(currentHRSearch.toLowerCase()) ||
            emp.id.toLowerCase().includes(currentHRSearch.toLowerCase()) ||
            emp.title.toLowerCase().includes(currentHRSearch.toLowerCase());

        const matchesDept = currentHRFilterDept === 'All' || emp.dept === currentHRFilterDept;
        const matchesStatus = currentHRFilterStatus === 'All' || emp.status === currentHRFilterStatus;

        return matchesSearch && matchesDept && matchesStatus;
    });

    tableBody.innerHTML = filtered.map(emp => `
        <tr>
            <td><span style="font-family:monospace; color:var(--text-secondary);">${emp.id}</span></td>
            <td>
                <div style="display:flex; align-items:center; gap:0.75rem;">
                    <div style="width:32px; height:32px; background:var(--bg-sidebar); color:white; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:0.8rem;">${emp.name.charAt(0)}</div>
                    <span style="font-weight:500;">${emp.name}</span>
                </div>
            </td>
            <td>${emp.dept}</td>
            <td>${emp.title}</td>
            <td><span class="badge ${emp.status === 'Active' ? 'completed' : 'pending'}">${emp.status}</span></td>
            <td>${emp.salary}</td>
            <td>
                <div class="action-menu-container">
                    <button class="action-btn" onclick="toggleHRMenu(this, '${emp.id}')"><i class="fa-solid fa-ellipsis-vertical"></i></button>
                    <div class="dropdown-menu" id="hr-menu-${emp.id}" style="width:160px;">
                        <button class="dropdown-item" onclick="editEmployee('${emp.id}')"><i class="fa-regular fa-pen-to-square"></i> Edit Employee</button>
                        <button class="dropdown-item delete" onclick="deleteEmployee('${emp.id}')"><i class="fa-solid fa-trash"></i> Remove</button>
                    </div>
                </div>
            </td>
        </tr>
    `).join('');
};

// HR Helpers
window.toggleHRMenu = function (btn, empId) {
    // Close others
    document.querySelectorAll('.dropdown-menu.active').forEach(menu => {
        if (menu.id !== `hr-menu-${empId}` && menu.id !== 'hr-filter-menu') menu.classList.remove('active');
    });

    const menu = document.getElementById(`hr-menu-${empId}`);
    if (menu) menu.classList.toggle('active');
};

// Unified Modal for Add/Edit
window.openEmployeeModal = function (employee = null) {
    const modal = document.getElementById('generic-modal');
    const content = document.getElementById('modal-content-body');

    const isEdit = !!employee;
    const title = isEdit ? 'Edit Employee' : 'Add New Employee';
    const submitText = isEdit ? 'Save Changes' : 'Add Employee';
    const idVal = isEdit ? employee.id : `EMP-0${employees.length + 1}`.padEnd(7, '0');

    // Pre-fill values if editing
    const getData = (key) => isEdit ? employee[key] : '';
    const getSel = (key, val) => isEdit && employee[key] === val ? 'selected' : '';

    content.innerHTML = `
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
            <h2 style="margin:0;">${title}</h2>
            <button onclick="document.getElementById('generic-modal').classList.remove('active')" style="background:none; border:none; font-size:1.5rem; cursor:pointer;">&times;</button>
        </div>
        <form id="employee-form" onsubmit="handleEmployeeSubmit(event, '${idVal}', ${isEdit})">
            <div style="display:grid; gap:1rem;">
                <div>
                    <label style="display:block; font-size:0.9rem; margin-bottom:0.5rem; font-weight:500;">Full Name</label>
                    <input type="text" name="name" value="${getData('name')}" required style="width:100%; padding:0.75rem; border:1px solid var(--border-color); border-radius:8px;">
                </div>
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem;">
                    <div>
                        <label style="display:block; font-size:0.9rem; margin-bottom:0.5rem; font-weight:500;">Department</label>
                        <select name="dept" required style="width:100%; padding:0.75rem; border:1px solid var(--border-color); border-radius:8px;">
                            <option value="Production" ${getSel('dept', 'Production')}>Production</option>
                            <option value="HR" ${getSel('dept', 'HR')}>HR</option>
                            <option value="Finance" ${getSel('dept', 'Finance')}>Finance</option>
                            <option value="Sales" ${getSel('dept', 'Sales')}>Sales</option>
                            <option value="IT" ${getSel('dept', 'IT')}>IT</option>
                        </select>
                    </div>
                    <div>
                            <label style="display:block; font-size:0.9rem; margin-bottom:0.5rem; font-weight:500;">Job Title</label>
                            <input type="text" name="title" value="${getData('title')}" required style="width:100%; padding:0.75rem; border:1px solid var(--border-color); border-radius:8px;">
                    </div>
                </div>
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem;">
                    <div>
                        <label style="display:block; font-size:0.9rem; margin-bottom:0.5rem; font-weight:500;">Status</label>
                        <select name="status" required style="width:100%; padding:0.75rem; border:1px solid var(--border-color); border-radius:8px;">
                            <option value="Active" ${getSel('status', 'Active')}>Active</option>
                            <option value="On Leave" ${getSel('status', 'On Leave')}>On Leave</option>
                        </select>
                    </div>
                    <div>
                            <label style="display:block; font-size:0.9rem; margin-bottom:0.5rem; font-weight:500;">Salary (JOD)</label>
                            <input type="text" name="salary" value="${getData('salary')}" placeholder="e.g. 1,200 JOD" required style="width:100%; padding:0.75rem; border:1px solid var(--border-color); border-radius:8px;">
                    </div>
                </div>
                <button type="submit" style="margin-top:1rem; padding:0.75rem; background:var(--accent-primary); color:white; border:none; border-radius:8px; cursor:pointer; font-weight:600;">${submitText}</button>
            </div>
        </form>
    `;

    modal.classList.add('active');
    // Close other menus
    document.querySelectorAll('.dropdown-menu.active').forEach(m => m.classList.remove('active'));
};

window.openAddEmployeeModal = function () {
    openEmployeeModal(null);
};

window.handleEmployeeSubmit = function (e, id, isEdit) {
    e.preventDefault();
    const formData = new FormData(e.target);

    const empData = {
        id: id,
        name: formData.get('name'),
        dept: formData.get('dept'),
        title: formData.get('title'),
        status: formData.get('status'),
        salary: formData.get('salary')
    };

    if (isEdit) {
        const index = employees.findIndex(emp => emp.id === id);
        if (index !== -1) {
            employees[index] = empData;
        }
    } else {
        employees.push(empData);
    }

    updateHRTable();
    document.getElementById('generic-modal').classList.remove('active');
};

window.deleteEmployee = function (empId) {
    if (confirm(`Are you sure you want to remove ${empId}?`)) {
        const index = employees.findIndex(e => e.id === empId);
        if (index !== -1) {
            employees.splice(index, 1);
            updateHRTable();
        }
    }
};

window.editEmployee = function (empId) {
    const emp = employees.find(e => e.id === empId);
    if (emp) {
        openEmployeeModal(emp);
    }
};
