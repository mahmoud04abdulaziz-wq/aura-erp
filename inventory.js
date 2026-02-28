// --- INVENTORY MODULE ---

const inventoryItems = [
    { sku: 'BLK-MRB-01', name: 'Raw Marble Block', category: 'Raw Material', quantity: 45, unit: 'Tons', location: 'Yard A', status: 'In Stock' },
    { sku: 'SLB-GRN-02', name: 'Granite Slab (Black)', category: 'Finished Goods', quantity: 120, unit: 'm²', location: 'Warehouse 1', status: 'In Stock' },
    { sku: 'TIL-TRV-01', name: 'Travertine Tiles 60x60', category: 'Finished Goods', quantity: 800, unit: 'Boxes', location: 'Warehouse 2', status: 'Low Stock' },
    { sku: 'CNS-BLD-04', name: 'Diamond Blades 14"', category: 'Consumables', quantity: 12, unit: 'Pcs', location: 'Tool Room', status: 'Reorder' },
    { sku: 'BLK-LMS-02', name: 'Limestone Block', category: 'Raw Material', quantity: 5, unit: 'Tons', location: 'Yard B', status: 'Low Stock' }
];

window.renderInventoryModule = function () {
    const container = document.getElementById('view-inventory');

    if (!container.dataset.shellBuilt) {
        container.innerHTML = `
            <div class="stats-grid" style="margin-bottom: 1.5rem;">
                <div class="stat-card">
                    <div class="stat-info">
                        <h3>Total Raw Material</h3>
                        <p class="stat-value" style="font-size:1.5rem;">320 Tons</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-info">
                        <h3>Finished Goods</h3>
                        <p class="stat-value" style="font-size:1.5rem;">4,500 m²</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-info">
                        <h3>Items Low Stock</h3>
                        <p class="stat-value" style="font-size:1.5rem; color:var(--warning);">8 Items</p>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header" style="flex-direction:column; align-items:flex-start; gap:1rem;">
                     <div style="width:100%; display:flex; justify-content:space-between; align-items:center;">
                        <h3>Inventory Master List</h3>
                        <button class="icon-btn" style="width:auto; padding:0 1rem; color:var(--success); border-color:var(--success);" onclick="alert('Receive Shipment form would open.')"><i class="fa-solid fa-truck-ramp-box"></i> Receive</button>
                    </div>
                </div>

                <div style="overflow-x:auto;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>SKU</th>
                                <th>Item Name</th>
                                <th>Category</th>
                                <th>Quantity</th>
                                <th>Location</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${inventoryItems.map(item => `
                                <tr>
                                    <td><span style="font-family:monospace; color:var(--text-secondary);">${item.sku}</span></td>
                                    <td><span style="font-weight:500;">${item.name}</span></td>
                                    <td>${item.category}</td>
                                    <td><span style="font-weight:600;">${item.quantity} ${item.unit}</span></td>
                                    <td>${item.location}</td>
                                    <td><span class="badge ${item.status === 'In Stock' ? 'completed' : item.status === 'Low Stock' ? 'blue' : 'pending'}">${item.status}</span></td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            </div>
        `;
        container.dataset.shellBuilt = 'true';
    }
}
