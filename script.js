document.addEventListener('DOMContentLoaded', () => {

    // --- ELEMENTS ---
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebar-toggle');
    const profilePanel = document.getElementById('profile-panel');
    const profileClose = document.getElementById('profile-close');
    const pageTitle = document.querySelector('.page-title');

    // View Handling
    const navItems = document.querySelectorAll('.sidebar-nav li[data-view]');
    const views = document.querySelectorAll('.view-section');

    // --- SIDEBAR TOGGLE ---
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
        });
    }

    // --- PROFILE TOGGLE LOGIC ---
    window.toggleProfile = () => {
        if (profilePanel) profilePanel.classList.add('active');
    };

    if (profileClose) {
        profileClose.addEventListener('click', () => {
            profilePanel.classList.remove('active');
        });
    }

    // Close profile if clicking outside
    document.addEventListener('click', (e) => {
        if (profilePanel &&
            !profilePanel.contains(e.target) &&
            !e.target.closest('.user-profile-widget') &&
            profilePanel.classList.contains('active')) {
            profilePanel.classList.remove('active');
        }
    });

    // --- NAVIGATION LOGIC ---
    navItems.forEach(item => {
        item.addEventListener('click', (e) => {
            e.preventDefault();

            // UI Updates
            navItems.forEach(nav => nav.classList.remove('active'));
            item.classList.add('active');

            // View Switching
            const viewId = item.getAttribute('data-view');
            const targetView = document.getElementById(`view-${viewId}`);

            // Update Title
            const titleText = item.querySelector('span').textContent;
            if (pageTitle) pageTitle.textContent = titleText;

            // Hide all views first
            views.forEach(view => {
                view.style.display = 'none';
                view.classList.remove('active');
            });

            if (targetView) {
                targetView.style.display = 'block';
                // Small timeout to allow display:block to apply before adding class for potential transition
                setTimeout(() => targetView.classList.add('active'), 10);

                // Module Loading
                if (viewId === 'orders') renderOrdersModule();
                if (viewId === 'hr') renderHRModule();
                if (viewId === 'procurement') renderProcurementModule();
            } else {
                // If view doesn't exist (e.g. Inventory), show a placeholder or nothing
                // For now we just stay blank or we could show an 'Under Construction' toast
                console.log(`View ${viewId} not implemented yet.`);
            }
        });
    });

    // --- DATA & RENDERERS ---

    const ordersData = [
        { id: '#ORD-7829', client: 'Marbex Corp', date: 'Feb 14, 2026', progress: 65, status: 'Processing', amount: '$4,200.00' },
        { id: '#ORD-7830', client: 'StoneHome Ltd', date: 'Feb 15, 2026', progress: 15, status: 'Pending', amount: '$1,850.00' },
        { id: '#ORD-7831', client: 'Elite Tiles', date: 'Feb 10, 2026', progress: 100, status: 'Completed', amount: '$9,300.00' },
        { id: '#ORD-7832', client: 'Urban Builds', date: 'Feb 12, 2026', progress: 85, status: 'Quality Check', amount: '$3,120.00' },
        { id: '#ORD-7833', client: 'ArchiStruct', date: 'Feb 16, 2026', progress: 0, status: 'Pending', amount: '$750.00' }
    ];

    const employees = [
        { id: 'EMP-001', name: 'Mohammed Ali', dept: 'Production', title: 'Shift Supervisor', status: 'Active', salary: '1,200 JOD' },
        { id: 'EMP-002', name: 'Sarah Ahmed', dept: 'HR', title: 'HR Manager', status: 'Active', salary: '1,500 JOD' },
        { id: 'EMP-003', name: 'John Smith', dept: 'Finance', title: 'Accountant', status: 'On Leave', salary: '1,100 JOD' },
        { id: 'EMP-004', name: 'Lina Kareem', dept: 'Sales', title: 'Sales Rep', status: 'Active', salary: '950 JOD' },
        { id: 'EMP-005', name: 'Omar Yassin', dept: 'IT', title: 'Sys Admin', status: 'Active', salary: '1,300 JOD' }
    ];

    const purchaseOrders = [
        { id: 'PO-PR-001', supplier: 'Jordan Stone Co.', item: 'Raw Marble Blocks', amount: '15,000 JOD', payStatus: 'Paid', appStatus: 'Approved' },
        { id: 'PO-PR-002', supplier: 'Global Tools Ltd', item: 'Diamond Cutting Blades', amount: '$2,450', payStatus: 'Pending', appStatus: 'Pending' },
        { id: 'PO-PR-003', supplier: 'Logistics Pro', item: 'Shipping Container #44', amount: '1,200 JOD', payStatus: 'Paid', appStatus: 'Approved' },
        { id: 'PO-PR-004', supplier: 'TechSoft', item: 'Annual ERP License', amount: '$5,000', payStatus: 'Pending', appStatus: 'Approved' },
        { id: 'PO-PR-005', supplier: 'Office Supplies Inc', item: 'Stationery', amount: '250 JOD', payStatus: 'Paid', appStatus: 'Approved' }
    ];

    function getStatusBadgeClass(status) {
        switch (status) {
            case 'Pending': return 'gray';
            case 'Processing': return 'blue';
            case 'Quality Check': return 'orange';
            case 'Completed': return 'green';
            default: return 'gray';
        }
    }

    function renderOrdersModule() {
        const container = document.getElementById('view-orders');

        // Only build the outer shell once or if missing
        if (!container.dataset.shellBuilt) {
            container.innerHTML = `
                <div class="card">
                    <div class="card-header" style="flex-direction:column; align-items:flex-start; gap:1rem;">
                        <div style="width:100%; display:flex; justify-content:space-between; align-items:center;">
                            <h3>All Orders</h3>
                            <button class="icon-btn" style="background:var(--accent-primary); color:white; border:none;"><i class="fa-solid fa-plus"></i></button>
                        </div>
                        
                        <div style="width:100%; display:flex; flex-direction:column; gap:1rem;">
                            <input type="text" id="order-search" placeholder="Search by Client or Order ID..." style="padding: 0.75rem 1rem; border: 1px solid var(--border-color); border-radius: 8px; width:100%;">
                            
                            <div class="filter-chips">
                                <span class="filter-chip active" data-filter="All">All Orders</span>
                                <span class="filter-chip" data-filter="Pending">Pending</span>
                                <span class="filter-chip" data-filter="Processing">In Production</span>
                                <span class="filter-chip" data-filter="Completed">Completed</span>
                            </div>
                        </div>
                    </div>

                    <div style="overflow-x:auto; min-height: 300px;">
                        <table class="data-table" id="orders-table">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Client Name</th>
                                    <th>Order Date</th>
                                    <th>Production Progress</th>
                                    <th>Status</th>
                                    <th>Total Amount</th>
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

            // Attach Global Events for this module only once
            const searchInput = document.getElementById('order-search');
            const filterChips = container.querySelectorAll('.filter-chip');

            searchInput.addEventListener('input', (e) => {
                currentOrderSearch = e.target.value;
                updateOrdersTable();
            });

            filterChips.forEach(chip => {
                chip.addEventListener('click', () => {
                    filterChips.forEach(c => c.classList.remove('active'));
                    chip.classList.add('active');
                    currentOrderFilter = chip.dataset.filter;
                    updateOrdersTable();
                });
            });

            // Close dropdowns when clicking outside
            document.addEventListener('click', (e) => {
                if (!e.target.closest('.action-menu-container')) {
                    document.querySelectorAll('.dropdown-menu.active').forEach(menu => {
                        menu.classList.remove('active');
                    });
                }
            });
        }

        updateOrdersTable();
    }

    // State for Orders
    let currentOrderFilter = 'All';
    let currentOrderSearch = '';

    window.updateOrdersTable = function () {
        const tableBody = document.querySelector('#orders-table tbody');
        if (!tableBody) return;

        const filtered = ordersData.filter(order => {
            const matchesFilter = currentOrderFilter === 'All' ||
                (currentOrderFilter === 'In Production' && (order.status === 'Processing' || order.status === 'Quality Check')) ||
                order.status === currentOrderFilter;
            const matchesSearch = order.client.toLowerCase().includes(currentOrderSearch.toLowerCase()) ||
                order.id.toLowerCase().includes(currentOrderSearch.toLowerCase());
            return matchesFilter && matchesSearch;
        });

        tableBody.innerHTML = filtered.map(order => `
            <tr>
                <td><span style="font-weight:700; color:var(--accent-primary);">${order.id}</span></td>
                <td><span style="font-weight:600;">${order.client}</span></td>
                <td>${order.date}</td>
                <td style="width:150px;">
                    <div style="display:flex; justify-content:space-between; font-size:0.75rem; color:var(--text-secondary); margin-bottom:0.25rem;">
                        <span>${order.progress}%</span>
                    </div>
                    <div class="progress-bar-container">
                        <div class="progress-bar-fill" style="width: ${order.progress}%"></div>
                    </div>
                </td>
                <td><span class="badge ${getStatusBadgeClass(order.status)}">${order.status}</span></td>
                <td><span style="font-weight:600; font-family:monospace;">${order.amount}</span></td>
                <td>
                    <div class="action-menu-container">
                        <button class="action-btn" onclick="toggleOrderMenu(this, '${order.id}')"><i class="fa-solid fa-ellipsis-vertical"></i></button>
                        <div class="dropdown-menu" id="menu-${order.id}">
                            <button class="dropdown-item" onclick="viewOrderDetails('${order.id}')"><i class="fa-regular fa-eye"></i> View Details</button>
                            <div style="border-top:1px solid #eee; margin:4px 0;"></div>
                            <span style="padding:4px 16px; font-size:0.75rem; color:#94a3b8; font-weight:600; text-transform:uppercase;">Set Status</span>
                            <button class="dropdown-item" onclick="updateOrderStatus('${order.id}', 'Pending')">Note as Pending</button>
                            <button class="dropdown-item" onclick="updateOrderStatus('${order.id}', 'Processing')">Start Production</button>
                            <button class="dropdown-item" onclick="updateOrderStatus('${order.id}', 'Quality Check')">Quality Check</button>
                            <button class="dropdown-item" onclick="updateOrderStatus('${order.id}', 'Completed')">Mark Completed</button>
                            <div style="border-top:1px solid #eee; margin:4px 0;"></div>
                            <button class="dropdown-item delete" onclick="deleteOrder('${order.id}')"><i class="fa-solid fa-trash"></i> Delete Order</button>
                        </div>
                    </div>
                </td>
            </tr>
        `).join('');
    };

    // --- ORDER ACTIONS ---

    window.toggleOrderMenu = function (btn, orderId) {
        // Close others
        document.querySelectorAll('.dropdown-menu.active').forEach(menu => {
            if (menu.id !== `menu-${orderId}`) menu.classList.remove('active');
        });

        const menu = document.getElementById(`menu-${orderId}`);
        if (menu) menu.classList.toggle('active');
    };

    window.updateOrderStatus = function (orderId, newStatus) {
        const order = ordersData.find(o => o.id === orderId);
        if (order) {
            order.status = newStatus;

            // Auto-update progress for fun
            if (newStatus === 'Pending') order.progress = 0;
            if (newStatus === 'Processing') order.progress = 50;
            if (newStatus === 'Quality Check') order.progress = 90;
            if (newStatus === 'Completed') order.progress = 100;

            updateOrdersTable();
            // Close menu
            const menu = document.getElementById(`menu-${orderId}`);
            if (menu) menu.classList.remove('active');
        }
    };

    window.deleteOrder = function (orderId) {
        if (confirm(`Are you sure you want to delete order ${orderId}?`)) {
            const index = ordersData.findIndex(o => o.id === orderId);
            if (index !== -1) {
                ordersData.splice(index, 1);
                updateOrdersTable();
            }
        }
    };

    window.viewOrderDetails = function (orderId) {
        const order = ordersData.find(o => o.id === orderId);
        if (!order) return;

        const modal = document.getElementById('generic-modal');
        const content = document.getElementById('modal-content-body');

        content.innerHTML = `
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
                <h2 style="margin:0;">Order Details</h2>
                <button onclick="document.getElementById('generic-modal').classList.remove('active')" style="background:none; border:none; font-size:1.5rem; cursor:pointer;">&times;</button>
            </div>
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1.5rem;">
                <div>
                    <label style="color:var(--text-secondary); font-size:0.85rem;">Order ID</label>
                    <p style="font-weight:600; font-size:1.1rem;">${order.id}</p>
                </div>
                 <div>
                    <label style="color:var(--text-secondary); font-size:0.85rem;">Client</label>
                    <p style="font-weight:600; font-size:1.1rem;">${order.client}</p>
                </div>
                 <div>
                    <label style="color:var(--text-secondary); font-size:0.85rem;">Status</label>
                    <p><span class="badge ${getStatusBadgeClass(order.status)}">${order.status}</span></p>
                </div>
                 <div>
                    <label style="color:var(--text-secondary); font-size:0.85rem;">Total Amount</label>
                    <p style="font-weight:600; font-size:1.1rem; color:var(--accent-primary);">${order.amount}</p>
                </div>
            </div>
            <div style="margin-top:1.5rem; padding-top:1rem; border-top:1px solid #eee;">
                <label style="color:var(--text-secondary); font-size:0.85rem;">Shipping Address</label>
                <p>Industrial Area, Street 15, Warehouse 4<br>Amman, Jordan</p>
            </div>
            <div style="margin-top:2rem; display:flex; justify-content:flex-end;">
                <button onclick="document.getElementById('generic-modal').classList.remove('active')" style="padding:0.75rem 1.5rem; background:var(--accent-primary); color:white; border:none; border-radius:8px; cursor:pointer;">Close</button>
            </div>
        `;

        modal.classList.add('active');

        // Close actions menu
        document.querySelectorAll('.dropdown-menu.active').forEach(m => m.classList.remove('active'));
    };

    // --- HR MODULE ---

    // HR State
    let currentHRSearch = '';
    let currentHRFilterDept = 'All';
    let currentHRFilterStatus = 'All';

    function renderHRModule() {
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

    // OLD FUNCTIONS TO BE REMOVED/REPLACED by the above block:
    function _dummy_() { }

    function renderProcurementModule() {
        const container = document.getElementById('view-procurement');
        if (!container || container.dataset.rendered === 'true') return;

        container.innerHTML = `
             <div class="card">
                <div class="card-header">
                    <h3>Purchase Orders</h3>
                    <div style="display:flex; gap:0.5rem;">
                        <button class="icon-btn"><i class="fa-solid fa-filter"></i></button>
                        <button class="icon-btn"><i class="fa-solid fa-download"></i></button>
                    </div>
                </div>
                <div style="overflow-x:auto;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Supplier</th>
                                <th>Item</th>
                                <th>Amount</th>
                                <th>Payment</th>
                                <th>Approval</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${purchaseOrders.map(po => `
                                <tr>
                                    <td><span style="font-family:monospace; color:var(--text-secondary);">${po.id}</span></td>
                                    <td>${po.supplier}</td>
                                    <td>${po.item}</td>
                                    <td>${po.amount}</td>
                                    <td><span class="badge ${po.payStatus === 'Paid' ? 'completed' : 'pending'}">${po.payStatus}</span></td>
                                    <td><span class="badge ${po.appStatus === 'Approved' ? 'in-progress' : 'pending'}">${po.appStatus}</span></td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            </div>
        `;
        container.dataset.rendered = 'true';
    }

    // Scroll Responsive Animation (Keep existing fancy effect)
    const shapes = document.querySelectorAll('.shape');
    window.addEventListener('scroll', () => {
        const scrolled = window.scrollY;
        shapes.forEach((shape, index) => {
            const speed = (index + 1) * 0.15;
            const yPos = -(scrolled * speed);
            const rotate = scrolled * 0.1;
            shape.style.transform = `translateY(${yPos}px) rotate(${rotate}deg)`;
        });
    });

});
