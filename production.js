// --- PRODUCTION MODULE ---

const productionBatches = [
    { id: 'BATCH-401', material: 'Carrara Marble', machine: 'Cutter A1', progress: 80, status: 'Cutting', operator: 'M. Ali' },
    { id: 'BATCH-402', material: 'Granite Black', machine: 'Polisher P2', progress: 0, status: 'Maintenance', operator: 'S. K.' },
    { id: 'BATCH-403', material: 'Travertine', machine: 'CNC Router X1', progress: 45, status: 'Routing', operator: 'A. Naser' },
    { id: 'BATCH-404', material: 'Onyx White', machine: 'Polisher P1', progress: 100, status: 'Completed', operator: 'L. Kareem' },
    { id: 'BATCH-405', material: 'Limestone', machine: 'Cutter A2', progress: 15, status: 'Pending', operator: 'M. Ali' }
];

window.renderProductionModule = function () {
    const container = document.getElementById('view-production');

    if (!container.dataset.shellBuilt) {
        container.innerHTML = `
            <div class="card">
                <div class="card-header" style="flex-direction:column; align-items:flex-start; gap:1rem;">
                     <div style="width:100%; display:flex; justify-content:space-between; align-items:center;">
                        <h3>Active Production Batches</h3>
                        <button class="icon-btn" style="width:auto; padding:0 1rem; color:var(--accent-primary); border-color:var(--accent-primary);" onclick="alert('Start New Batch modal would open here.')"><i class="fa-solid fa-play"></i> Start Batch</button>
                    </div>
                </div>

                <div style="overflow-x:auto;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Batch ID</th>
                                <th>Material</th>
                                <th>Assigned Machine</th>
                                <th>Operator</th>
                                <th>Progress</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${productionBatches.map(batch => `
                                <tr>
                                    <td><span style="font-weight:700; color:var(--text-secondary);">${batch.id}</span></td>
                                    <td><span style="font-weight:500;">${batch.material}</span></td>
                                    <td>${batch.machine}</td>
                                    <td>${batch.operator}</td>
                                    <td style="width:150px;">
                                        <div style="display:flex; justify-content:space-between; font-size:0.75rem; color:var(--text-secondary); margin-bottom:0.25rem;">
                                            <span>${batch.progress}%</span>
                                        </div>
                                        <div class="progress-bar-container">
                                            <div class="progress-bar-fill" style="width: ${batch.progress}%; background: ${batch.progress === 100 ? 'var(--success)' : 'var(--accent-primary)'}"></div>
                                        </div>
                                    </td>
                                    <td><span class="badge ${batch.status === 'Completed' ? 'completed' : batch.status === 'Maintenance' ? 'pending' : 'in-progress'}">${batch.status}</span></td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div style="margin-top: 1.5rem;" class="card production-status">
                <div class="card-header">
                    <h3>Machine Floor Status</h3>
                </div>
                <div class="machine-list" style="display:grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap:1rem;">
                    ${['Cutter A1 (Running)', 'Cutter A2 (Idle)', 'Polisher P1 (Idle)', 'Polisher P2 (Maintenance)', 'CNC Router X1 (Running)'].map(m => {
            const [name, statusStr] = m.split(' (');
            const status = statusStr.replace(')', '');
            const statusClass = status === 'Running' ? 'running' : status === 'Maintenance' ? 'maintenance' : 'idle';
            return `
                        <div class="machine-item" style="border: 1px solid var(--border-color); padding: 1rem; border-radius: 8px;">
                            <span class="machine-name" style="font-weight:600;">${name}</span>
                            <div style="display:flex; align-items:center; gap:0.5rem; margin-top:0.5rem;">
                                <div class="status-indicator ${statusClass}"></div>
                                <span class="status-text">${status}</span>
                            </div>
                        </div>
                        `;
        }).join('')}
                </div>
            </div>
        `;
        container.dataset.shellBuilt = 'true';
    }
}
