// --- INVOICES MODULE ---

const invoiceData = [
    { id: 'INV-2026-045', client: 'Elite Tiles', date: 'Feb 10, 2026', due: 'Mar 10, 2026', amount: '$9,300.00', status: 'Paid' },
    { id: 'INV-2026-046', client: 'Marbex Corp', date: 'Feb 14, 2026', due: 'Mar 14, 2026', amount: '$4,200.00', status: 'Sent' },
    { id: 'INV-2026-047', client: 'StoneHome Ltd', date: 'Feb 15, 2026', due: 'Mar 15, 2026', amount: '$1,850.00', status: 'Draft' },
    { id: 'INV-2026-048', client: 'Urban Builds', date: 'Feb 18, 2026', due: 'Mar 18, 2026', amount: '$3,120.00', status: 'Sent' },
    { id: 'INV-2026-049', client: 'ArchiStruct', date: 'Feb 20, 2026', due: 'Mar 20, 2026', amount: '$750.00', status: 'Overdue' }
];

window.renderInvoicesModule = function () {
    const container = document.getElementById('view-invoices');

    if (!container.dataset.shellBuilt) {
        container.innerHTML = `
            <div class="card">
                <div class="card-header" style="justify-content:space-between;">
                    <h3>Client Invoices</h3>
                    <button class="icon-btn" style="width:auto; padding:0 1rem; color:var(--text-primary); border-color:var(--border-color);" onclick="showGenericModal('New Invoice', 'Open Invoice Creation dialog')"><i class="fa-solid fa-plus"></i> Create Invoice</button>
                </div>
                <div style="overflow-x:auto;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Invoice #</th>
                                <th>Client Name</th>
                                <th>Issue Date</th>
                                <th>Due Date</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${invoiceData.map(inv => {
            let badgeClass = 'pending';
            if (inv.status === 'Paid') badgeClass = 'completed';
            if (inv.status === 'Sent') badgeClass = 'blue';
            if (inv.status === 'Overdue') badgeClass = 'orange';

            return `
                                <tr>
                                    <td><span style="font-weight:700; color:var(--accent-primary);">${inv.id}</span></td>
                                    <td><span style="font-weight:600;">${inv.client}</span></td>
                                    <td>${inv.date}</td>
                                    <td>${inv.due}</td>
                                    <td><span style="font-family:monospace; font-weight:600;">${inv.amount}</span></td>
                                    <td><span class="badge ${badgeClass}">${inv.status}</span></td>
                                    <td>
                                        <button class="action-btn" title="View" onclick="showGenericModal('View Invoice', 'Viewing PDF for ${inv.id}')"><i class="fa-regular fa-eye"></i></button>
                                        <button class="action-btn" title="Send" onclick="showGenericModal('Send Invoice', 'Emailing invoice ${inv.id} to client')"><i class="fa-regular fa-paper-plane"></i></button>
                                    </td>
                                </tr>
                                `
        }).join('')}
                        </tbody>
                    </table>
                </div>
            </div>
        `;
        container.dataset.shellBuilt = 'true';
    }
}
