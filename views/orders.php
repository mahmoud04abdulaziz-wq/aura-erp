<?php
/**
 * AURA ERP — Orders View
 * Displays sales orders from the database with filter chips and status badges.
 * Tables: sales_orders, customers
 */

// Fetch all sales orders with customer info
try {
    $orders = $pdo->query(
        "SELECT so.so_id, so.order_date, so.total_price, so.order_status, so.payment_method,
                c.company_name
         FROM sales_orders so
         JOIN customers c ON so.customer_id = c.customer_id
         ORDER BY so.order_date DESC"
    )->fetchAll();

    // Fetch customers for the Add Order modal
    $customersList = $pdo->query("SELECT customer_id, company_name FROM customers ORDER BY company_name ASC")->fetchAll();

    // Count by status for filter badges
    $statusCounts = ['All' => count($orders)];
    foreach ($orders as $o) {
        $s = $o['order_status'];
        $statusCounts[$s] = ($statusCounts[$s] ?? 0) + 1;
    }
} catch (Exception $e) {
    error_log("Orders view error: " . $e->getMessage());
    $orders = [];
    $customersList = [];
    $statusCounts = ['All' => 0];
}

$badgeMap = [
    'Pending' => 'pending',
    'In Production' => 'blue',
    'Pending Delivery' => 'orange',
    'Delivered' => 'completed',
];
?>

<style>
    .custom-select-wrapper {
        position: relative;
        user-select: none;
        width: 100%;
    }
    .custom-select {
        position: relative;
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.75rem 1rem;
        background: var(--bg-component, transparent);
        border: 1px solid var(--border-color, #e2e8f0);
        border-radius: 8px;
        cursor: pointer;
        font-size: 0.95rem;
        color: var(--text-primary);
        transition: all 0.2s ease;
    }
    .custom-select:hover { border-color: var(--accent-primary, #6366f1); }
    .custom-select-wrapper.open .custom-select { border-color: var(--accent-primary, #6366f1); box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1); }
    
    .custom-options {
        position: absolute;
        top: calc(100% + 5px);
        left: 0;
        right: 0;
        background: var(--bg-panel, #fff);
        border: 1px solid var(--border-color, #e2e8f0);
        border-radius: 8px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
        transform: translateY(-10px);
        transition: all 0.2s ease;
        z-index: 999;
        max-height: 200px;
        overflow-y: auto;
    }
    .custom-select-wrapper.open .custom-options {
        opacity: 1;
        visibility: visible;
        pointer-events: all;
        transform: translateY(0);
    }
    
    .custom-option {
        padding: 0.75rem 1rem;
        cursor: pointer;
        transition: background 0.2s;
        color: var(--text-primary);
    }
    .custom-option:hover { background: var(--bg-hover, #f8fafc); color: var(--accent-primary, #6366f1); }
    .custom-option.selected { font-weight: 600; background: var(--bg-active, #e0e7ff); color: var(--accent-primary, #6366f1); }
    
    .custom-select-trigger { display:flex; justify-content:space-between; width:100%; align-items:center; }
    .custom-select-trigger i { transition: transform 0.2s; color: var(--text-secondary); }
    .custom-select-wrapper.open .custom-select-trigger i { transform: rotate(180deg); }
    
    @keyframes shake {
        10%, 90% { transform: translate3d(-1px, 0, 0); }
        20%, 80% { transform: translate3d(2px, 0, 0); }
        30%, 50%, 70% { transform: translate3d(-4px, 0, 0); }
        40%, 60% { transform: translate3d(4px, 0, 0); }
    }
</style>

<div class="card">
    <div class="card-header" style="flex-direction:column; align-items:flex-start; gap:1rem;">
        <div style="width:100%; display:flex; justify-content:space-between; align-items:center;">
            <h3>Sales Orders</h3>
            <div style="display:flex; gap:0.5rem;">
                <button class="icon-btn" style="width:auto; padding:0 1rem; border-radius:8px;" onclick="openCustomerModal()"><i class="fa-solid fa-user-plus" style="margin-right:0.5rem;"></i> New Customer</button>
                <button class="icon-btn" style="background:var(--accent-primary); color:white; width:auto; padding:0 1rem; border-radius:8px;" onclick="openOrderModal()"><i class="fa-solid fa-plus" style="margin-right:0.5rem;"></i> New Order</button>
            </div>
        </div>

        <!-- Filter Chips -->
        <div class="filter-chips">
            <?php foreach ($statusCounts as $status => $count): ?>
                <button class="filter-chip <?= $status === 'All' ? 'active' : '' ?>"
                    onclick="filterOrders(this, '<?= $status ?>')">
                    <?= htmlspecialchars($status) ?> (<?= $count ?>)
                </button>
            <?php endforeach; ?>
        </div>
    </div>

    <div style="overflow-x:auto;">
        <table class="data-table" id="orders-table">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Client</th>
                    <th>Order Date</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($orders)): ?>
                    <tr>
                        <td colspan="6" style="text-align:center; color:var(--text-secondary); padding:3rem;">
                            <i class="fa-solid fa-inbox" style="font-size:2rem; opacity:0.3; display:block; margin-bottom:0.75rem;"></i>
                            No sales orders found
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($orders as $order): ?>
                        <tr data-status="<?= htmlspecialchars($order['order_status']) ?>">
                            <td><span style="font-weight:700; color:var(--accent-primary);"><?= htmlspecialchars($order['so_id']) ?></span></td>
                            <td><span style="font-weight:500;"><?= htmlspecialchars($order['company_name']) ?></span></td>
                            <td><?= date('M d, Y', strtotime($order['order_date'])) ?></td>
                            <td><span style="font-family:monospace; font-weight:600;"><?= number_format($order['total_price'] ?? 0, 2) ?> JOD</span></td>
                            <td><span class="badge <?= $badgeMap[$order['order_status']] ?? 'pending' ?>"><?= htmlspecialchars($order['order_status']) ?></span></td>
                            <td>
                                <div class="action-menu-container">
                                    <button class="action-btn" onclick="this.nextElementSibling.classList.toggle('active')">
                                        <i class="fa-solid fa-ellipsis-vertical"></i>
                                    </button>
                                    <div class="dropdown-menu">
                                        <?php if ($order['order_status'] === 'Pending'): ?>
                                            <button class="dropdown-item" onclick="changeOrderStatus('<?= htmlspecialchars($order['so_id']) ?>', 'In Production')">
                                                <i class="fa-solid fa-industry"></i> Send to Production
                                            </button>
                                        <?php elseif ($order['order_status'] === 'Pending Delivery'): ?>
                                            <button class="dropdown-item" onclick="changeOrderStatus('<?= htmlspecialchars($order['so_id']) ?>', 'Delivered')">
                                                <i class="fa-solid fa-truck-fast"></i> Deliver Order
                                            </button>
                                        <?php endif; ?>
                                        <button class="dropdown-item delete" onclick="deleteOrder('<?= htmlspecialchars($order['so_id']) ?>')">
                                            <i class="fa-regular fa-trash-can"></i> Delete
                                        </button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    // Safe JSON encoding of PHP array
    const customersData = <?= json_encode($customersList, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

    function filterOrders(btn, status) {
        document.querySelectorAll('.filter-chip').forEach(c => c.classList.remove('active'));
        btn.classList.add('active');
        document.querySelectorAll('#orders-table tbody tr').forEach(row => {
            if (status === 'All' || row.dataset.status === status) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    document.addEventListener('click', e => {
        if (!e.target.closest('.action-menu-container')) {
            document.querySelectorAll('.dropdown-menu.active').forEach(m => m.classList.remove('active'));
        }
    });

    // --- Modal Logic ---
    function openCustomerModal() {
        const modal = document.getElementById('generic-modal');
        const content = `
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
                <h2 style="margin:0;">New Customer</h2>
                <button type="button" onclick="document.getElementById('generic-modal').classList.remove('active')" style="background:none; border:none; font-size:1.5rem; cursor:pointer;">&times;</button>
            </div>
            <form onsubmit="submitCustomer(event)">
                <label style="display:block; margin-bottom:0.5rem; font-weight:500;">Company Name *</label>
                <input type="text" name="company_name" required style="width:100%; padding:0.75rem; border:1px solid var(--border-color); border-radius:8px; margin-bottom:1rem;">
                
                <label style="display:block; margin-bottom:0.5rem; font-weight:500;">Contact Person</label>
                <input type="text" name="contact_person" style="width:100%; padding:0.75rem; border:1px solid var(--border-color); border-radius:8px; margin-bottom:1rem;">
                
                <div style="display:flex; gap:1rem; margin-bottom:1rem;">
                    <div style="flex:1;">
                        <label style="display:block; margin-bottom:0.5rem; font-weight:500;">Email</label>
                        <input type="email" name="email" style="width:100%; padding:0.75rem; border:1px solid var(--border-color); border-radius:8px;">
                    </div>
                    <div style="flex:1;">
                        <label style="display:block; margin-bottom:0.5rem; font-weight:500;">Phone</label>
                        <input type="text" name="phone_number" style="width:100%; padding:0.75rem; border:1px solid var(--border-color); border-radius:8px;">
                    </div>
                </div>

                <label style="display:block; margin-bottom:0.5rem; font-weight:500;">Address</label>
                <input type="text" name="default_address" style="width:100%; padding:0.75rem; border:1px solid var(--border-color); border-radius:8px; margin-bottom:1.5rem;">

                <button type="submit" style="padding:0.75rem; background:var(--accent-primary); color:white; border:none; border-radius:8px; width:100%; cursor:pointer;">Create Customer</button>
            </form>
        `;
        // We use innerHTML to place a custom modal content struct in the generic modal overlay
        modal.innerHTML = `<div class="modal-content" style="background:var(--bg-panel); padding:2rem; border-radius:16px; max-width:500px; margin:auto; box-shadow:var(--shadow-lg);">${content}</div>`;
        modal.classList.add('active');
    }

    async function submitCustomer(e) {
        e.preventDefault();
        const fd = new FormData(e.target);
        try {
            const res = await fetch('<?= BASE_URL ?>/modules/customers/create_customer.php', { method: 'POST', body: fd });
            const data = await res.json();
            if (data.success) {
                alert('Customer created successfully!');
                window.location.reload();
            } else {
                alert('Error: ' + data.error);
            }
        } catch (err) {
            alert('A network error occurred.');
        }
    }

    function openOrderModal() {
        const modal = document.getElementById('generic-modal');
        let customerOptions = customersData.map(c => `<div class="custom-option" data-value="${c.customer_id}">${c.company_name}</div>`).join('');
        if(customersData.length === 0) customerOptions = `<div class="custom-option" data-value="">No customers found. Create one first.</div>`;

        const content = `
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
                <h2 style="margin:0;">New Sales Order</h2>
                <button type="button" onclick="document.getElementById('generic-modal').classList.remove('active')" style="background:none; border:none; font-size:1.5rem; cursor:pointer; color:var(--text-primary);">&times;</button>
            </div>
            <form onsubmit="submitOrder(event)" id="new-order-form">
                <label style="display:block; margin-bottom:0.5rem; font-weight:500;">Customer *</label>
                
                <div class="custom-select-wrapper" id="customer-select" style="margin-bottom:1rem;">
                    <div class="custom-select">
                        <div class="custom-select-trigger">
                            <span class="selected-text" style="opacity:0.6;">Select Customer...</span>
                            <i class="fa-solid fa-chevron-down"></i>
                        </div>
                    </div>
                    <div class="custom-options">
                        ${customerOptions}
                    </div>
                    <!-- The actual form value gets updated here -->
                    <input type="hidden" name="customer_id" required>
                </div>
                
                <div style="display:flex; gap:1rem; margin-bottom:1rem;">
                    <div style="flex:1;">
                        <label style="display:block; margin-bottom:0.5rem; font-weight:500;">Order Date</label>
                        <input type="date" name="order_date" value="<?= date('Y-m-d') ?>" required style="width:100%; padding:0.75rem; border:1px solid var(--border-color); border-radius:8px; background:transparent; color:var(--text-primary);">
                    </div>
                    <div style="flex:1;">
                        <label style="display:block; margin-bottom:0.5rem; font-weight:500;">Total Price ($) *</label>
                        <input type="number" step="0.01" name="total_price" required style="width:100%; padding:0.75rem; border:1px solid var(--border-color); border-radius:8px; background:transparent; color:var(--text-primary);">
                    </div>
                </div>

                <div style="display:flex; gap:1rem; margin-bottom:1.5rem;">
                    <div style="flex:1;">
                        <label style="display:block; margin-bottom:0.5rem; font-weight:500;">Payment Method</label>
                        
                        <div class="custom-select-wrapper" id="payment-select">
                            <div class="custom-select">
                                <div class="custom-select-trigger">
                                    <span class="selected-text">Credit Card</span>
                                    <i class="fa-solid fa-chevron-down"></i>
                                </div>
                            </div>
                            <div class="custom-options">
                                <div class="custom-option selected" data-value="Credit Card">Credit Card</div>
                                <div class="custom-option" data-value="Bank Transfer">Bank Transfer</div>
                                <div class="custom-option" data-value="Cash">Cash</div>
                            </div>
                            <input type="hidden" name="payment_method" value="Credit Card" required>
                        </div>
                    </div>
                    <div style="flex:1;">
                        <label style="display:block; margin-bottom:0.5rem; font-weight:500;">Delivery Location</label>
                        <input type="text" name="delivery_location" style="width:100%; padding:0.75rem; border:1px solid var(--border-color); border-radius:8px; background:transparent; color:var(--text-primary);">
                    </div>
                </div>

                <button type="submit" style="padding:0.75rem; background:var(--accent-primary); color:white; border:none; border-radius:8px; width:100%; cursor:pointer; font-weight:500; font-size:1rem; box-shadow:0 4px 12px rgba(99,102,241,0.3); transition:all 0.2s;">Create Order</button>
            </form>
        `;
        
        modal.innerHTML = `<div class="modal-content" style="background:var(--bg-panel); padding:2.5rem; border-radius:16px; max-width:600px; margin:auto; box-shadow:var(--shadow-lg);">${content}</div>`;
        modal.classList.add('active');

        // Initialize Custom Selects Interactivity
        setTimeout(() => {
            const wrappers = modal.querySelectorAll('.custom-select-wrapper');
            let docClickListener;
            
            wrappers.forEach(wrapper => {
                const select = wrapper.querySelector('.custom-select');
                const options = wrapper.querySelectorAll('.custom-option');
                const hiddenInput = wrapper.querySelector('input[type="hidden"]');
                const textSpan = wrapper.querySelector('.selected-text');
                
                select.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const wasOpen = wrapper.classList.contains('open');
                    // Close others
                    wrappers.forEach(w => w.classList.remove('open'));
                    if (!wasOpen) wrapper.classList.add('open');
                });
                
                options.forEach(opt => {
                    opt.addEventListener('click', (e) => {
                        e.stopPropagation();
                        if (opt.dataset.value === "") return; // Disable if empty
                        
                        hiddenInput.value = opt.dataset.value;
                        textSpan.textContent = opt.textContent;
                        textSpan.style.opacity = '1';
                        
                        options.forEach(o => o.classList.remove('selected'));
                        opt.classList.add('selected');
                        wrapper.classList.remove('open');
                    });
                });
            });
            
            // Validate form on submit manually because required hidden inputs don't trigger native bubbles well natively usually
            const form = document.getElementById('new-order-form');
            form.addEventListener('submit', (e) => {
                const customerId = form.querySelector('input[name="customer_id"]').value;
                if (!customerId) {
                    e.preventDefault();
                    let trigger = document.getElementById('customer-select').querySelector('.custom-select');
                    trigger.style.borderColor = 'red';
                    trigger.style.animation = 'shake 0.4s cubic-bezier(.36,.07,.19,.97) both';
                    setTimeout(() => trigger.style.animation = '', 400);
                }
            });

            // Make sure clicking outside closes any open select
            docClickListener = () => {
                wrappers.forEach(w => w.classList.remove('open'));
            };
            document.addEventListener('click', docClickListener);
            
            // Clean up the event listener when form submits or modal closes to prevent memory leaks
            const closeBtn = modal.querySelector('button[type="button"]');
            closeBtn.addEventListener('click', () => { document.removeEventListener('click', docClickListener); });
            form.addEventListener('submit', () => { document.removeEventListener('click', docClickListener); });
            
        }, 50);
    }

    async function submitOrder(e) {
        e.preventDefault();
        const fd = new FormData(e.target);
        if (!fd.get('customer_id')) {
            alert('Please select a customer.');
            return;
        }
        try {
            const res = await fetch('<?= BASE_URL ?>/modules/orders/create_order.php', { method: 'POST', body: fd });
            const data = await res.json();
            if (data.success) {
                alert('Order created: ' + data.so_id);
                window.location.reload();
            } else {
                alert('Error: ' + data.error);
            }
        } catch (err) {
            alert('A network error occurred.');
        }
    }

    async function changeOrderStatus(so_id, newStatus) {
        if (!confirm(`Are you sure you want to mark order ${so_id} as ${newStatus}?`)) return;
        
        try {
            const fd = new FormData();
            fd.append('so_id', so_id);
            fd.append('status', newStatus);
            const res = await fetch('<?= BASE_URL ?>/modules/orders/update_status.php', { method: 'POST', body: fd });
            const data = await res.json();
            if (data.success) {
                window.location.reload();
            } else {
                alert('Error: ' + data.error);
            }
        } catch (err) {
            alert('A network error occurred.');
        }
    }

    async function deleteOrder(so_id) {
        if (!confirm(`Are you sure you want to delete order ${so_id}? This cannot be undone.`)) return;

        try {
            const fd = new FormData();
            fd.append('so_id', so_id);
            const res = await fetch('<?= BASE_URL ?>/modules/orders/delete_order.php', { method: 'POST', body: fd });
            const data = await res.json();
            if (data.success) {
                window.location.reload();
            } else {
                alert('Error: ' + data.error);
            }
        } catch (err) {
            alert('A network error occurred.');
        }
    }
</script>

<?php if (isset($_GET['action']) && $_GET['action'] === 'newFromLead' && isset($_GET['lead_id'])): ?>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        openOrderModal();
        setTimeout(() => {
            const leadId = "<?= htmlspecialchars($_GET['lead_id']) ?>";
            const opt = document.querySelector(`.custom-option[data-value="${leadId}"]`);
            if(opt) {
                opt.click();
            }
        }, 150);
    });
</script>
<?php endif; ?>