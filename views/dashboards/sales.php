<?php
// Fetch Web Leads from Storefront (customers with lead_status 'New')
$webLeadsStmt = $pdo->query("
    SELECT customer_id, company_name, contact_person, email, default_address as notes, phone_number
    FROM customers 
    WHERE lead_status = 'New'
    ORDER BY customer_id DESC
");
$webLeads = $webLeadsStmt->fetchAll();

// Fetch Pending Orders
$pendingOrdersStmt = $pdo->query("
    SELECT so_id, c.company_name, order_date, total_price 
    FROM sales_orders so
    JOIN customers c ON so.customer_id = c.customer_id
    WHERE order_status = 'Pending'
    ORDER BY order_date ASC
");
$pendingOrders = $pendingOrdersStmt->fetchAll();

$totalSalesMonth = $pdo->query("SELECT COALESCE(SUM(total_price),0) FROM sales_orders WHERE MONTH(order_date) = MONTH(CURRENT_DATE())")->fetchColumn();
?>

<div class="dashboard-container">
    <div class="welcome-banner" style="background: linear-gradient(135deg, #1e293b, #0ea5e9); color: white;">
        <h1>Sales & CRM Dashboard</h1>
        <p>Web Inquiry Pipeline & Order Management</p>
    </div>

    <!-- Stats Grid -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon info" style="background: rgba(14, 165, 233, 0.1); color: #0ea5e9;">
                <i class="fa-solid fa-globe"></i>
            </div>
            <div class="stat-info">
                <h3>New Web Leads</h3>
                <p class="stat-value"><?= count($webLeads) ?></p>
                <span class="stat-trend positive">From public storefront</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon warning"><i class="fa-solid fa-clock-rotate-left"></i></div>
            <div class="stat-info">
                <h3>Pending Orders</h3>
                <p class="stat-value"><?= count($pendingOrders) ?></p>
                <span class="stat-trend neutral">Awaiting Fulfillment</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon success"><i class="fa-solid fa-chart-line"></i></div>
            <div class="stat-info">
                <h3>Monthly Volume</h3>
                <p class="stat-value">$<?= number_format($totalSalesMonth) ?></p>
                <span class="stat-trend positive">Total Sales Order Value</span>
            </div>
        </div>
    </div>

    <div class="content-split" style="margin-top: 2rem;">
        
        <!-- Web Leads List -->
        <div class="card" style="flex: 1;">
            <div class="card-header">
                <h3><i class="fa-solid fa-globe" style="color:#0ea5e9;margin-right:8px;"></i> Incoming Web Inquiries</h3>
                <span class="badge in-progress" style="background:#e0f2fe; color:#0369a1;">Direct from Storefront</span>
            </div>
            
            <div style="display:flex; flex-direction: column; gap: 1rem;">
                <?php if (empty($webLeads)): ?>
                    <p style="color:#94a3b8; text-align:center; padding: 2rem;">No new leads at this time.</p>
                <?php else: ?>
                    <?php foreach ($webLeads as $lead): ?>
                        <div style="padding: 1.25rem; border: 1px solid #e0f2fe; border-radius: 12px; background: #f0f9ff; border-left: 4px solid #0ea5e9;">
                            <div style="display:flex; justify-content:space-between; margin-bottom:0.5rem; align-items:flex-start;">
                                <div>
                                    <h4 style="margin:0; color:#0f172a; font-size:1.05rem;"><?= htmlspecialchars($lead['company_name']) ?></h4>
                                    <span style="font-size:0.85rem; color:#475569;"><i class="fa-solid fa-user"></i> <?= htmlspecialchars($lead['contact_person']) ?></span>
                                </div>
                                <span class="badge pending" style="font-size:0.7rem; background:#bae6fd; color:#0369a1;">New Lead</span>
                            </div>
                            
                            <div style="font-size:0.85rem; color:#334155; margin-bottom: 0.75rem;">
                                <strong>Message:</strong><br>
                                <i>"<?= htmlspecialchars($lead['notes']) ?>"</i>
                            </div>

                            <div style="display:flex; gap: 0.5rem;">
                                <a href="mailto:<?= htmlspecialchars($lead['email']) ?>" class="btn-text" style="background:white; padding:0.4rem 0.8rem; border-radius:6px; font-size:0.8rem; border:1px solid #bae6fd;"><i class="fa-solid fa-envelope"></i> Email</a>
                                <a href="<?= BASE_URL ?>/app.php?view=orders&action=newFromLead&lead_id=<?= urlencode($lead['customer_id']) ?>" class="btn-text" style="background:white; padding:0.4rem 0.8rem; border-radius:6px; font-size:0.8rem; border:1px solid #bae6fd; color:var(--accent-primary); text-decoration:none;"><i class="fa-solid fa-file-invoice"></i> Convert to Order</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Pending Orders -->
        <div class="card" style="flex: 2;">
            <div class="card-header">
                <h3>Orders Pending Fulfillment</h3>
            </div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Client</th>
                        <th>Date</th>
                        <th>Value</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($pendingOrders)): ?>
                        <tr><td colspan="5" style="text-align:center;">No pending orders.</td></tr>
                    <?php else: ?>
                        <?php foreach ($pendingOrders as $order): ?>
                        <tr>
                            <td style="font-weight:600;">
                                <a href="<?= BASE_URL ?>/app.php?view=orders" style="color:var(--accent-primary); text-decoration:none;">
                                    <i class="fa-solid fa-link" style="font-size:0.8rem; margin-right:4px; opacity:0.7;"></i><?= htmlspecialchars($order['so_id']) ?>
                                </a>
                            </td>
                            <td><?= htmlspecialchars($order['company_name']) ?></td>
                            <td><?= htmlspecialchars($order['order_date']) ?></td>
                            <td style="font-weight:600; color:var(--accent-primary);">$<?= number_format($order['total_price'], 2) ?></td>
                            <td><button class="btn-text">Check Progress</button></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>
</div>
