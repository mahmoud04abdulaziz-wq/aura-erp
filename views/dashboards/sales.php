<?php
/**
 * MiskStone ERP — Sales & CRM Dashboard
 * Includes: Web lead intake, 1-click Accept/Decline, Top Customers, Sales Ledger Feed.
 */

// Handle Accept/Decline actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['lead_action'])) {
    $customerId = intval($_POST['customer_id'] ?? 0);
    $action = $_POST['lead_action'];
    
    if ($action === 'accept' && $customerId > 0) {
        try {
            // Update lead status to Converted
            $pdo->prepare("UPDATE customers SET lead_status = 'Converted' WHERE customer_id = ?")->execute([$customerId]);
            
            // Auto-create a pending sales order from the lead
            $custInfo = $pdo->prepare("SELECT company_name, default_address FROM customers WHERE customer_id = ?");
            $custInfo->execute([$customerId]);
            $cust = $custInfo->fetch();
            
            $soId = 'SO-' . date('ymd') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
            $handledBy = $_SESSION['user_id'] ?? 4;
            
            $stmt = $pdo->prepare("INSERT INTO sales_orders (so_id, customer_id, order_date, total_price, delivery_location, order_status, handled_by, payment_method) VALUES (?, ?, CURRENT_DATE(), 0, ?, 'Pending', ?, 'TBD')");
            $stmt->execute([$soId, $customerId, $cust['default_address'] ?? '', $handledBy]);
            
            // Send notification
            require_once __DIR__ . '/../../includes/notifications.php';
            addNotification($pdo, "Lead Accepted", "Web lead '{$cust['company_name']}' converted to Order {$soId}", 'crm');
        } catch (Exception $e) {
            error_log("Lead accept error: " . $e->getMessage());
        }
    } elseif ($action === 'decline' && $customerId > 0) {
        $pdo->prepare("UPDATE customers SET lead_status = 'Contacted' WHERE customer_id = ?")->execute([$customerId]);
    }
    
    // Redirect to prevent form resubmission
    header("Location: " . BASE_URL . "/app.php?view=dashboard");
    exit;
}

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

// Top Customers by Revenue
$topCustomers = $pdo->query("
    SELECT c.company_name, SUM(so.total_price) as total_revenue, COUNT(so.so_id) as order_count
    FROM sales_orders so
    JOIN customers c ON so.customer_id = c.customer_id
    GROUP BY c.customer_id, c.company_name
    ORDER BY total_revenue DESC
    LIMIT 5
")->fetchAll();

// Sales Ledger Feed (finance_ledger entries tagged as Sales Revenue)
$salesLedger = $pdo->query("
    SELECT transaction_id, transaction_date, amount, reference_id
    FROM finance_ledger
    WHERE category = 'Sales Revenue' AND transaction_type = 'Income'
    ORDER BY transaction_date DESC
    LIMIT 10
")->fetchAll();
?>

<div class="dashboard-container">
    <div class="welcome-banner" style="background: linear-gradient(135deg, #1e293b, #0ea5e9); color: white;">
        <h1>Sales & CRM Dashboard</h1>
        <p>Web Inquiry Pipeline · Customer Relationship Management · Revenue Tracking</p>
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

    <!-- Web Leads + Pending Orders -->
    <div class="content-split" style="margin-top: 2rem;">
        
        <!-- Web Leads List with Accept/Decline -->
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
                                <strong>Details:</strong><br>
                                <i>"<?= htmlspecialchars($lead['notes']) ?>"</i>
                            </div>

                            <!-- CRM Actions: Accept / Decline / Contact -->
                            <div style="display:flex; gap: 0.5rem; flex-wrap: wrap;">
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="customer_id" value="<?= $lead['customer_id'] ?>">
                                    <input type="hidden" name="lead_action" value="accept">
                                    <button type="submit" style="background:#22c55e; color:white; border:none; padding:0.4rem 0.8rem; border-radius:6px; font-size:0.8rem; cursor:pointer; font-weight:600;">
                                        <i class="fa-solid fa-check"></i> Accept Lead
                                    </button>
                                </form>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="customer_id" value="<?= $lead['customer_id'] ?>">
                                    <input type="hidden" name="lead_action" value="decline">
                                    <button type="submit" style="background:#ef4444; color:white; border:none; padding:0.4rem 0.8rem; border-radius:6px; font-size:0.8rem; cursor:pointer; font-weight:600;">
                                        <i class="fa-solid fa-times"></i> Decline
                                    </button>
                                </form>
                                <a href="mailto:<?= htmlspecialchars($lead['email']) ?>?subject=MiskStone%20-%20Your%20Product%20Inquiry&body=Dear%20<?= urlencode($lead['contact_person']) ?>%2C%0A%0AThank%20you%20for%20your%20interest%20in%20MiskStone%20products.%0A%0A" 
                                   style="background:white; padding:0.4rem 0.8rem; border-radius:6px; font-size:0.8rem; border:1px solid #bae6fd; text-decoration:none; color:#0369a1; font-weight:600;">
                                    <i class="fa-solid fa-envelope"></i> Contact Client
                                </a>
                                <?php if (!empty($lead['phone_number'])): ?>
                                <a href="tel:<?= htmlspecialchars($lead['phone_number']) ?>" 
                                   style="background:white; padding:0.4rem 0.8rem; border-radius:6px; font-size:0.8rem; border:1px solid #bae6fd; text-decoration:none; color:#0369a1;">
                                    <i class="fa-solid fa-phone"></i> Call
                                </a>
                                <?php endif; ?>
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
                            <td><a href="<?= BASE_URL ?>/app.php?view=kanban" class="btn-text" style="text-decoration:none;">Track Progress</a></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>

    <!-- Bottom Row: Top Customers + Sales Ledger Feed -->
    <div class="content-split" style="margin-top: 2rem;">
        <!-- Top Customers -->
        <div class="card" style="flex: 1;">
            <div class="card-header">
                <h3><i class="fa-solid fa-trophy" style="color:#f59e0b; margin-right:0.5rem;"></i>Best Customers</h3>
            </div>
            <table class="data-table">
                <thead><tr><th>Company</th><th>Revenue</th></tr></thead>
                <tbody>
                    <?php foreach ($topCustomers as $c): ?>
                        <tr>
                            <td style="font-weight:600;"><?= htmlspecialchars($c['company_name']) ?></td>
                            <td style="font-weight:700; color:var(--success);">$<?= number_format($c['total_revenue'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($topCustomers)): ?>
                        <tr><td colspan="2" style="text-align:center; color:var(--text-secondary);">No data yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Sales Ledger Feed (General Ledger — Sales perspective) -->
        <div class="card" style="flex: 1;">
            <div class="card-header">
                <h3><i class="fa-solid fa-book-open" style="color:var(--accent-primary); margin-right:0.5rem;"></i>General Ledger — Sales Revenue</h3>
                <span class="badge completed" style="font-size:0.75rem;">Read-only</span>
            </div>
            <table class="data-table">
                <thead><tr><th>Date</th><th>Ref</th><th>Amount</th></tr></thead>
                <tbody>
                    <?php foreach ($salesLedger as $entry): ?>
                        <tr>
                            <td style="font-size:0.85rem;"><?= date('M d, Y', strtotime($entry['transaction_date'])) ?></td>
                            <td style="font-family:monospace; font-size:0.85rem;"><?= htmlspecialchars($entry['reference_id'] ?? '—') ?></td>
                            <td style="font-weight:700; color:var(--success);">+$<?= number_format($entry['amount'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($salesLedger)): ?>
                        <tr><td colspan="3" style="text-align:center; color:var(--text-secondary); padding:1.5rem;">No sales revenue entries recorded yet. Complete an order to see the pipeline reflected here.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
