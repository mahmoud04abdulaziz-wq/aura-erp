// Data and rendering for Procurement Module

const purchaseOrders = [
    { id: 'PO-PR-001', supplier: 'Jordan Stone Co.', item: 'Raw Marble Blocks', amount: '15,000 JOD', payStatus: 'Paid', appStatus: 'Approved' },
    { id: 'PO-PR-002', supplier: 'Global Tools Ltd', item: 'Diamond Cutting Blades', amount: '$2,450', payStatus: 'Pending', appStatus: 'Pending' },
    { id: 'PO-PR-003', supplier: 'Logistics Pro', item: 'Shipping Container #44', amount: '1,200 JOD', payStatus: 'Paid', appStatus: 'Approved' },
    { id: 'PO-PR-004', supplier: 'TechSoft', item: 'Annual ERP License', amount: '$5,000', payStatus: 'Pending', appStatus: 'Approved' },
    { id: 'PO-PR-005', supplier: 'Office Supplies Inc', item: 'Stationery', amount: '250 JOD', payStatus: 'Paid', appStatus: 'Approved' }
];

window.renderProcurementModule = function () {
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
