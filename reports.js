// --- REPORTS MODULE ---

window.renderReportsModule = function () {
    const container = document.getElementById('view-reports');

    if (!container.dataset.shellBuilt) {
        container.innerHTML = `
            <div class="card">
                <div class="card-header">
                    <h3>Available Reports</h3>
                </div>
                <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem; padding: 1rem 0;">
                    
                    <!-- Report Card -->
                    <div style="border: 1px solid var(--border-color); border-radius: 8px; padding: 1.5rem; display:flex; flex-direction:column; gap: 1rem;">
                        <div style="display:flex; align-items:center; gap: 1rem;">
                            <div style="width:48px; height:48px; background:var(--bg-main); border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:1.5rem; color:var(--accent-primary);">
                                <i class="fa-solid fa-file-invoice-dollar"></i>
                            </div>
                            <div>
                                <h4 style="margin:0; font-size:1.1rem;">Monthly Sales Summary</h4>
                                <p style="margin:0; font-size:0.85rem; color:var(--text-secondary);">Breakdown of revenue by client and product.</p>
                            </div>
                        </div>
                        <div style="display:flex; gap:0.5rem; margin-top:auto;">
                            <button style="flex:1; padding:0.5rem; background:var(--accent-primary); color:white; border:none; border-radius:4px; cursor:pointer;" onclick="showGenericModal('Downloading PDF', 'Generating Monthly Sales Summary...')">PDF</button>
                            <button style="flex:1; padding:0.5rem; background:var(--bg-main); color:var(--text-primary); border:1px solid var(--border-color); border-radius:4px; cursor:pointer;" onclick="showGenericModal('Downloading CSV', 'Exporting Monthly Sales Summary data...')">CSV</button>
                        </div>
                    </div>

                    <!-- Report Card -->
                    <div style="border: 1px solid var(--border-color); border-radius: 8px; padding: 1.5rem; display:flex; flex-direction:column; gap: 1rem;">
                        <div style="display:flex; align-items:center; gap: 1rem;">
                            <div style="width:48px; height:48px; background:var(--bg-main); border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:1.5rem; color:var(--success);">
                                <i class="fa-solid fa-shapes"></i>
                            </div>
                            <div>
                                <h4 style="margin:0; font-size:1.1rem;">Production Efficiency</h4>
                                <p style="margin:0; font-size:0.85rem; color:var(--text-secondary);">Machine usage, downtime, and output volume.</p>
                            </div>
                        </div>
                        <div style="display:flex; gap:0.5rem; margin-top:auto;">
                            <button style="flex:1; padding:0.5rem; background:var(--accent-primary); color:white; border:none; border-radius:4px; cursor:pointer;" onclick="showGenericModal('Downloading PDF', 'Generating Production Efficiency Report...')">PDF</button>
                            <button style="flex:1; padding:0.5rem; background:var(--bg-main); color:var(--text-primary); border:1px solid var(--border-color); border-radius:4px; cursor:pointer;" onclick="showGenericModal('Downloading CSV', 'Exporting Production Efficiency data...')">CSV</button>
                        </div>
                    </div>

                    <!-- Report Card -->
                    <div style="border: 1px solid var(--border-color); border-radius: 8px; padding: 1.5rem; display:flex; flex-direction:column; gap: 1rem;">
                        <div style="display:flex; align-items:center; gap: 1rem;">
                            <div style="width:48px; height:48px; background:var(--bg-main); border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:1.5rem; color:var(--warning);">
                                <i class="fa-solid fa-warehouse"></i>
                            </div>
                            <div>
                                <h4 style="margin:0; font-size:1.1rem;">Inventory Valuation</h4>
                                <p style="margin:0; font-size:0.85rem; color:var(--text-secondary);">Current stock levels and estimated ledger value.</p>
                            </div>
                        </div>
                        <div style="display:flex; gap:0.5rem; margin-top:auto;">
                            <button style="flex:1; padding:0.5rem; background:var(--accent-primary); color:white; border:none; border-radius:4px; cursor:pointer;" onclick="showGenericModal('Downloading PDF', 'Generating Inventory Valuation Report...')">PDF</button>
                            <button style="flex:1; padding:0.5rem; background:var(--bg-main); color:var(--text-primary); border:1px solid var(--border-color); border-radius:4px; cursor:pointer;" onclick="showGenericModal('Downloading CSV', 'Exporting Inventory Valuation data...')">CSV</button>
                        </div>
                    </div>

                      <!-- Report Card -->
                    <div style="border: 1px solid var(--border-color); border-radius: 8px; padding: 1.5rem; display:flex; flex-direction:column; gap: 1rem;">
                        <div style="display:flex; align-items:center; gap: 1rem;">
                            <div style="width:48px; height:48px; background:var(--bg-main); border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:1.5rem; color:var(--text-primary);">
                                <i class="fa-solid fa-users"></i>
                            </div>
                            <div>
                                <h4 style="margin:0; font-size:1.1rem;">Employee Payroll</h4>
                                <p style="margin:0; font-size:0.85rem; color:var(--text-secondary);">Monthly salary disbursements and overtime.</p>
                            </div>
                        </div>
                        <div style="display:flex; gap:0.5rem; margin-top:auto;">
                            <button style="flex:1; padding:0.5rem; background:var(--accent-primary); color:white; border:none; border-radius:4px; cursor:pointer;" onclick="showGenericModal('Downloading PDF', 'Generating Employee Payroll Report...')">PDF</button>
                            <button style="flex:1; padding:0.5rem; background:var(--bg-main); color:var(--text-primary); border:1px solid var(--border-color); border-radius:4px; cursor:pointer;" onclick="showGenericModal('Downloading CSV', 'Exporting Employee Payroll data...')">CSV</button>
                        </div>
                    </div>

                </div>
            </div>
        `;
        container.dataset.shellBuilt = 'true';
    }
}
