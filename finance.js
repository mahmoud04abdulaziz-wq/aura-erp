// --- FINANCE MODULE ---

const recentTransactions = [
    { id: 'TRX-1092', date: 'Feb 16, 2026', description: 'Client Payment - Elite Tiles', type: 'Income', amount: '+$9,300.00' },
    { id: 'TRX-1091', date: 'Feb 15, 2026', description: 'Supplier Payment - Jordan Stone Co.', type: 'Expense', amount: '-$15,000.00' },
    { id: 'TRX-1090', date: 'Feb 12, 2026', description: 'Utility Bill - Electricity', type: 'Expense', amount: '-$450.00' },
    { id: 'TRX-1089', date: 'Feb 10, 2026', description: 'Client Payment - Marbex Corp', type: 'Income', amount: '+$4,200.00' },
    { id: 'TRX-1088', date: 'Feb 08, 2026', description: 'Payroll - Feb 1st Week', type: 'Expense', amount: '-$12,500.00' }
];

window.renderFinanceModule = function () {
    const container = document.getElementById('view-finance');

    if (!container.dataset.shellBuilt) {
        container.innerHTML = `
            <div class="stats-grid" style="margin-bottom: 1.5rem;">
                <div class="stat-card">
                    <div class="stat-icon primary"><i class="fa-solid fa-wallet"></i></div>
                    <div class="stat-info">
                        <h3>Total Balance</h3>
                        <p class="stat-value" style="font-size:1.5rem;">$142,500.00</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon success"><i class="fa-solid fa-arrow-trend-up"></i></div>
                    <div class="stat-info">
                        <h3>Monthly Income</h3>
                        <p class="stat-value" style="font-size:1.5rem;">$45,200.00</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon warning"><i class="fa-solid fa-arrow-trend-down"></i></div>
                    <div class="stat-info">
                        <h3>Monthly Expenses</h3>
                        <p class="stat-value" style="font-size:1.5rem;">$28,150.00</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon info"><i class="fa-solid fa-file-invoice-dollar"></i></div>
                    <div class="stat-info">
                        <h3>Pending Invoices</h3>
                        <p class="stat-value" style="font-size:1.5rem;">14</p>
                    </div>
                </div>
            </div>

            <div class="content-split">
                <div class="card" style="flex: 2;">
                    <div class="card-header" style="justify-content:space-between;">
                        <h3>Recent Transactions</h3>
                        <button class="icon-btn" style="width:auto; padding:0 1rem; color:var(--text-primary); border-color:var(--border-color);" onclick="alert('Open New Transaction logic here.')"><i class="fa-solid fa-plus"></i> New Entry</button>
                    </div>
                    <div style="overflow-x:auto;">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Ref ID</th>
                                    <th>Date</th>
                                    <th>Description</th>
                                    <th>Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${recentTransactions.map(trx => `
                                    <tr>
                                        <td><span style="font-family:monospace; color:var(--text-secondary);">${trx.id}</span></td>
                                        <td>${trx.date}</td>
                                        <td><span style="font-weight:500;">${trx.description}</span></td>
                                        <td><span style="font-weight:700; color:${trx.type === 'Income' ? 'var(--success)' : 'var(--text-primary)'};">${trx.amount}</span></td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="card" style="flex: 1;">
                    <div class="card-header">
                        <h3>Quick Actions</h3>
                    </div>
                    <div style="display:flex; flex-direction:column; gap:1rem; padding: 1rem 0;">
                        <button style="padding:1rem; background:var(--bg-main); border:1px solid var(--border-color); border-radius:8px; cursor:pointer; display:flex; align-items:center; gap:1rem; text-align:left;" onclick="alert('Create Invoice Feature')">
                            <i class="fa-solid fa-file-invoice" style="font-size:1.5rem; color:var(--accent-primary);"></i>
                            <div>
                                <h4 style="margin:0;">Create Invoice</h4>
                                <span style="font-size:0.8rem; color:var(--text-secondary);">Bill a client</span>
                            </div>
                        </button>
                        <button style="padding:1rem; background:var(--bg-main); border:1px solid var(--border-color); border-radius:8px; cursor:pointer; display:flex; align-items:center; gap:1rem; text-align:left;" onclick="alert('Record Expense Feature')">
                            <i class="fa-solid fa-receipt" style="font-size:1.5rem; color:var(--warning);"></i>
                            <div>
                                <h4 style="margin:0;">Record Expense</h4>
                                <span style="font-size:0.8rem; color:var(--text-secondary);">Log a payment</span>
                            </div>
                        </button>
                        <button style="padding:1rem; background:var(--bg-main); border:1px solid var(--border-color); border-radius:8px; cursor:pointer; display:flex; align-items:center; gap:1rem; text-align:left;" onclick="alert('Generate Report Feature')">
                            <i class="fa-solid fa-chart-pie" style="font-size:1.5rem; color:var(--success);"></i>
                            <div>
                                <h4 style="margin:0;">Financial Reports</h4>
                                <span style="font-size:0.8rem; color:var(--text-secondary);">View P&L and Balance</span>
                            </div>
                        </button>
                    </div>
                </div>
            </div>
        `;
        container.dataset.shellBuilt = 'true';
    }
}
