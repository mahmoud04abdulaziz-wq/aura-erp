// --- KANBAN (ORDERS PROGRESS) MODULE ---

const activeKanbanOrders = [
    { id: '#ORD-7830', client: 'StoneHome Ltd', status: 'Pending' },
    { id: '#ORD-7833', client: 'ArchiStruct', status: 'Pending' },
    { id: '#ORD-7829', client: 'Marbex Corp', status: 'Processing' },
    { id: '#ORD-7832', client: 'Urban Builds', status: 'Quality Check' }
];

window.renderKanbanModule = function () {
    const container = document.getElementById('view-kanban');

    if (!container.dataset.shellBuilt) {
        container.innerHTML = `
            <div class="card-header" style="margin-bottom: 1.5rem; background: var(--card-bg); padding: 1.5rem; border-radius: 12px; border: 1px solid var(--border-color);">
                <h3>Orders Progress Board</h3>
                <p style="color:var(--text-secondary); font-size:0.9rem;">Drag and drop cards to update order status (Visual Only Mockup)</p>
            </div>

            <div class="kanban-board">
                <!-- Pending Column -->
                <div class="kanban-column" data-status="Pending">
                    <div class="kanban-column-header">
                        <h4>Pending</h4>
                        <span class="badge gray">2</span>
                    </div>
                    <div class="kanban-cards-container">
                        ${activeKanbanOrders.filter(o => o.status === 'Pending').map(o => createKanbanCard(o)).join('')}
                    </div>
                </div>

                <!-- Processing Column -->
                <div class="kanban-column" data-status="Processing">
                    <div class="kanban-column-header">
                        <h4>Processing</h4>
                        <span class="badge blue">1</span>
                    </div>
                    <div class="kanban-cards-container">
                         ${activeKanbanOrders.filter(o => o.status === 'Processing').map(o => createKanbanCard(o)).join('')}
                    </div>
                </div>

                <!-- Quality Check Column -->
                <div class="kanban-column" data-status="Quality Check">
                    <div class="kanban-column-header">
                        <h4>Quality Check</h4>
                        <span class="badge orange">1</span>
                    </div>
                    <div class="kanban-cards-container">
                         ${activeKanbanOrders.filter(o => o.status === 'Quality Check').map(o => createKanbanCard(o)).join('')}
                    </div>
                </div>

                <!-- Completed Column -->
                <div class="kanban-column" data-status="Completed">
                    <div class="kanban-column-header">
                        <h4>Ready for Dispatch</h4>
                        <span class="badge green">0</span>
                    </div>
                    <div class="kanban-cards-container">
                        <!-- Cards would go here -->
                        <div style="padding: 1rem; text-align:center; color: var(--border-color); font-style: italic;">Drop areas here...</div>
                    </div>
                </div>
            </div>
        `;
        container.dataset.shellBuilt = 'true';
    }
}

function createKanbanCard(order) {
    return `
        <div class="kanban-card" draggable="true" onclick="showGenericModal('Order Details', 'Quick view for ${order.id}')">
            <div style="display:flex; justify-content:space-between; margin-bottom: 0.5rem;">
                <span style="font-weight:700; color:var(--accent-primary); font-size:0.9rem;">${order.id}</span>
            </div>
            <strong style="display:block; margin-bottom: 0.5rem;">${order.client}</strong>
            <div style="display:flex; justify-content:space-between; align-items:center; font-size:0.8rem; color:var(--text-secondary);">
                <span>${order.status}</span>
                <i class="fa-solid fa-grip-lines" style="opacity:0.5; cursor:grab;"></i>
            </div>
        </div>
    `;
}
