// --- DATA & RENDERERS ---

const ordersData = [
    { id: '#ORD-7829', client: 'Marbex Corp', date: 'Feb 14, 2026', progress: 65, status: 'Processing', amount: '$4,200.00' },
    { id: '#ORD-7830', client: 'StoneHome Ltd', date: 'Feb 15, 2026', progress: 15, status: 'Pending', amount: '$1,850.00' },
    { id: '#ORD-7831', client: 'Elite Tiles', date: 'Feb 10, 2026', progress: 100, status: 'Completed', amount: '$9,300.00' },
    { id: '#ORD-7832', client: 'Urban Builds', date: 'Feb 12, 2026', progress: 85, status: 'Quality Check', amount: '$3,120.00' },
    { id: '#ORD-7833', client: 'ArchiStruct', date: 'Feb 16, 2026', progress: 0, status: 'Pending', amount: '$750.00' }
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

window.renderOrdersModule = function () {
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
