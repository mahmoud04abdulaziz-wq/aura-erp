// --- ANALYTICS MODULE ---

window.renderAnalyticsModule = function () {
    const container = document.getElementById('view-analytics');

    if (!container.dataset.shellBuilt) {
        container.innerHTML = `
            <div class="card" style="margin-bottom: 1.5rem;">
                <div class="card-header">
                    <h3>Performance Overview</h3>
                </div>
                <div style="display: flex; gap: 2rem; padding: 1rem 0;">
                    
                    <!-- Mock Chart 1: Revenue -->
                    <div style="flex: 2; border: 1px solid var(--border-color); border-radius: 8px; padding: 1.5rem; display:flex; flex-direction:column; align-items:center; justify-content:center; min-height: 250px; background: repeating-linear-gradient(45deg, var(--bg-main), var(--bg-main) 10px, transparent 10px, transparent 20px);">
                        <i class="fa-solid fa-chart-line" style="font-size: 3rem; color: var(--accent-primary); margin-bottom: 1rem; opacity: 0.5;"></i>
                        <p style="color: var(--text-secondary); font-weight: 500;">Revenue Trend Chart Placeholder</p>
                        <span style="font-size:0.8rem; color: var(--text-secondary); margin-top:0.5rem;">Requires charting library (e.g. Chart.js)</span>
                    </div>

                    <!-- Mock Chart 2: Order Distribution -->
                     <div style="flex: 1; border: 1px solid var(--border-color); border-radius: 8px; padding: 1.5rem; display:flex; flex-direction:column; align-items:center; justify-content:center; min-height: 250px; background: radial-gradient(circle at center, var(--bg-main) 0%, transparent 100%);">
                        <i class="fa-solid fa-chart-pie" style="font-size: 3rem; color: var(--warning); margin-bottom: 1rem; opacity: 0.5;"></i>
                         <p style="color: var(--text-secondary); font-weight: 500; text-align:center;">Material Distribution Placeholder</p>
                    </div>

                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-info">
                        <h3>Customer Acquisition</h3>
                        <p class="stat-value" style="font-size:1.5rem; color:var(--success);">+24%</p>
                        <span class="stat-trend neutral">vs last month</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-info">
                        <h3>Avg. Order Value</h3>
                        <p class="stat-value" style="font-size:1.5rem; color:var(--accent-primary);">$5,420</p>
                         <span class="stat-trend positive">+12% vs last month</span>
                    </div>
                </div>
                 <div class="stat-card">
                    <div class="stat-info">
                        <h3>Production Waste</h3>
                        <p class="stat-value" style="font-size:1.5rem; color:var(--warning);">4.2%</p>
                         <span class="stat-trend negative">+0.5% vs last month</span>
                    </div>
                </div>
            </div>
        `;
        container.dataset.shellBuilt = 'true';
    }
}
