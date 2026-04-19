<?php
/**
 * MiskStone ERP — Sales Order Inbox
 * Card-based view of web leads with structured order items.
 * Sales can Accept (creates SO + sales_order_lines) or Decline leads.
 */

// Handle Accept/Decline POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['lead_action'])) {
    $customerId = intval($_POST['customer_id'] ?? 0);
    $action = $_POST['lead_action'];

    if ($action === 'accept' && $customerId > 0) {
        try {
            // Get customer info
            $custInfo = $pdo->prepare("SELECT company_name, default_address FROM customers WHERE customer_id = ?");
            $custInfo->execute([$customerId]);
            $cust = $custInfo->fetch();

            // Get structured order items
            $itemsStmt = $pdo->prepare("SELECT item_id, item_name, quantity, unit_price FROM web_order_items WHERE customer_id = ?");
            $itemsStmt->execute([$customerId]);
            $orderItems = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

            // Calculate total from structured items
            $totalPrice = 0;
            foreach ($orderItems as $oi) {
                $totalPrice += $oi['quantity'] * $oi['unit_price'];
            }

            // Generate SO ID
            $soId = 'SO-' . date('ymd') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
            $handledBy = $_SESSION['user_id'] ?? 4;

            // Create Sales Order with real total
            $stmt = $pdo->prepare("INSERT INTO sales_orders (so_id, customer_id, order_date, total_price, delivery_location, order_status, handled_by, payment_method) VALUES (?, ?, CURRENT_DATE(), ?, ?, 'Pending', ?, 'TBD')");
            $stmt->execute([$soId, $customerId, $totalPrice, $cust['default_address'] ?? '', $handledBy]);

            // Copy web_order_items → sales_order_lines
            $lineStmt = $pdo->prepare("INSERT INTO sales_order_lines (so_id, item_id, quantity, unit_price) VALUES (?, ?, ?, ?)");
            foreach ($orderItems as $oi) {
                $lineStmt->execute([$soId, $oi['item_id'], $oi['quantity'], $oi['unit_price']]);
            }

            // Update lead status
            $pdo->prepare("UPDATE customers SET lead_status = 'Converted' WHERE customer_id = ?")->execute([$customerId]);

            // Notification
            require_once __DIR__ . '/../includes/notifications.php';
            addNotification($pdo, "✅ Lead Accepted", "Web lead '{$cust['company_name']}' converted to Order {$soId} (\${$totalPrice})", 'crm');

        } catch (Exception $e) {
            error_log("Lead accept error: " . $e->getMessage());
        }
    } elseif ($action === 'decline' && $customerId > 0) {
        $pdo->prepare("UPDATE customers SET lead_status = 'Contacted' WHERE customer_id = ?")->execute([$customerId]);
    }

    echo '<script>window.location.href = "' . BASE_URL . '/app.php?view=sales_inbox";</script>';
    exit;
}

// Fetch Web Leads with their structured items
$webLeads = $pdo->query("
    SELECT customer_id, company_name, contact_person, email, phone_number, default_address
    FROM customers 
    WHERE lead_status = 'New'
    ORDER BY customer_id DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Get structured items for each lead
$leadItems = [];
foreach ($webLeads as $lead) {
    $itemsStmt = $pdo->prepare("SELECT item_id, item_name, quantity, unit_price FROM web_order_items WHERE customer_id = ?");
    $itemsStmt->execute([$lead['customer_id']]);
    $leadItems[$lead['customer_id']] = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<div class="dashboard-container">
    <div class="welcome-banner" style="background: linear-gradient(135deg, #1e293b, #0ea5e9); color: white; padding: 2rem; border-radius: 16px; margin-bottom: 2rem;">
        <h1 style="margin-bottom: 0.5rem;"><i class="fa-solid fa-inbox" style="margin-right: 0.75rem;"></i>Order Inbox</h1>
        <p style="opacity: 0.9;">Review incoming web orders from the storefront. Accept to create a Sales Order, or Decline.</p>
    </div>

    <!-- Stats -->
    <div class="stats-grid" style="margin-bottom: 2rem;">
        <div class="stat-card" style="border-left: 4px solid #0ea5e9;">
            <div class="stat-info">
                <h3 style="color: var(--text-secondary); font-size: 0.9rem;">New Web Leads</h3>
                <p class="stat-value"><?= count($webLeads) ?></p>
            </div>
        </div>
    </div>

    <?php if (empty($webLeads)): ?>
        <div class="card" style="text-align: center; padding: 4rem;">
            <i class="fa-solid fa-inbox" style="font-size: 3rem; color: var(--text-secondary); opacity: 0.3; margin-bottom: 1rem;"></i>
            <h3 style="color: var(--text-secondary); margin-bottom: 0.5rem;">Inbox is empty</h3>
            <p style="color: var(--text-secondary); opacity: 0.7;">No new web orders to review. Check back later or visit the storefront.</p>
        </div>
    <?php else: ?>
        <!-- Lead Cards Grid -->
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(420px, 1fr)); gap: 1.5rem;">
            <?php foreach ($webLeads as $lead): ?>
                <?php
                $items = $leadItems[$lead['customer_id']] ?? [];
                $itemCount = count($items);
                $totalValue = 0;
                foreach ($items as $itm) {
                    $totalValue += $itm['quantity'] * $itm['unit_price'];
                }
                ?>
                <div class="card" style="padding: 0; overflow: hidden; transition: transform 0.2s, box-shadow 0.2s;" 
                     onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 12px 40px rgba(0,0,0,0.12)'"
                     onmouseout="this.style.transform=''; this.style.boxShadow=''">
                    
                    <!-- Card Header -->
                    <div style="background: linear-gradient(135deg, #f0f9ff, #e0f2fe); padding: 1.25rem 1.5rem; border-bottom: 1px solid #bae6fd;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                            <div>
                                <h3 style="margin: 0; font-size: 1.1rem; color: #0f172a;">
                                    <i class="fa-solid fa-building" style="color: #0ea5e9; margin-right: 0.5rem;"></i>
                                    <?= htmlspecialchars($lead['company_name']) ?>
                                </h3>
                                <p style="margin: 0.25rem 0 0; font-size: 0.85rem; color: #475569;">
                                    <i class="fa-solid fa-user" style="margin-right: 0.25rem;"></i>
                                    <?= htmlspecialchars($lead['contact_person']) ?>
                                </p>
                            </div>
                            <span style="background: #bae6fd; color: #0369a1; padding: 0.3rem 0.75rem; border-radius: 9999px; font-size: 0.7rem; font-weight: 700;">NEW LEAD</span>
                        </div>
                    </div>

                    <!-- Order Items (Cart-Style) -->
                    <div style="padding: 1.25rem 1.5rem;">
                        <?php if (!empty($items)): ?>
                            <p style="font-size: 0.75rem; font-weight: 600; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.75rem;">
                                <i class="fa-solid fa-cart-shopping" style="margin-right: 0.25rem;"></i> Order Items (<?= $itemCount ?>)
                            </p>
                            <div style="border: 1px solid var(--border-color); border-radius: 10px; overflow: hidden;">
                                <?php foreach ($items as $idx => $itm): ?>
                                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem 1rem; <?= $idx < count($items) - 1 ? 'border-bottom: 1px solid var(--border-color);' : '' ?> background: <?= $idx % 2 === 0 ? 'var(--bg-body)' : 'white' ?>;">
                                        <div>
                                            <span style="font-weight: 600; font-size: 0.9rem;"><?= htmlspecialchars($itm['item_name']) ?></span>
                                            <span style="color: var(--text-secondary); font-size: 0.8rem;"> × <?= $itm['quantity'] ?></span>
                                        </div>
                                        <span style="font-weight: 700; color: var(--accent-primary);">$<?= number_format($itm['quantity'] * $itm['unit_price'], 2) ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <!-- Total -->
                            <div style="display: flex; justify-content: space-between; margin-top: 0.75rem; padding: 0.75rem 0; border-top: 2px solid var(--border-color);">
                                <span style="font-weight: 700; font-size: 1rem;">Total</span>
                                <span style="font-weight: 800; font-size: 1.15rem; color: var(--success);">$<?= number_format($totalValue, 2) ?></span>
                            </div>
                        <?php else: ?>
                            <!-- Fallback: show text summary -->
                            <div style="background: #f8fafc; padding: 1rem; border-radius: 8px; font-size: 0.85rem; color: #334155; max-height: 120px; overflow-y: auto; white-space: pre-line;">
                                <?= htmlspecialchars($lead['default_address'] ?? 'No order details available') ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Contact Info -->
                    <div style="padding: 0 1.5rem 1rem; display: flex; gap: 1rem; flex-wrap: wrap; font-size: 0.8rem; color: #64748b;">
                        <?php if (!empty($lead['email'])): ?>
                            <span><i class="fa-solid fa-envelope"></i> <?= htmlspecialchars($lead['email']) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($lead['phone_number'])): ?>
                            <span><i class="fa-solid fa-phone"></i> <?= htmlspecialchars($lead['phone_number']) ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- Action Buttons -->
                    <div style="padding: 1rem 1.5rem; background: #f8fafc; border-top: 1px solid var(--border-color); display: flex; gap: 0.5rem;">
                        <form method="POST" style="flex: 1;">
                            <input type="hidden" name="customer_id" value="<?= $lead['customer_id'] ?>">
                            <input type="hidden" name="lead_action" value="accept">
                            <button type="submit" style="width: 100%; background: #22c55e; color: white; border: none; padding: 0.7rem; border-radius: 8px; font-weight: 700; cursor: pointer; font-size: 0.85rem; transition: background 0.2s;">
                                <i class="fa-solid fa-check"></i> Accept & Create Order
                            </button>
                        </form>
                        <form method="POST" style="flex-shrink: 0;">
                            <input type="hidden" name="customer_id" value="<?= $lead['customer_id'] ?>">
                            <input type="hidden" name="lead_action" value="decline">
                            <button type="submit" style="background: #fee2e2; color: #991b1b; border: none; padding: 0.7rem 1rem; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 0.85rem;">
                                <i class="fa-solid fa-times"></i> Decline
                            </button>
                        </form>
                        <a href="mailto:<?= htmlspecialchars($lead['email']) ?>" 
                           style="background: white; border: 1px solid var(--border-color); padding: 0.7rem 1rem; border-radius: 8px; font-size: 0.85rem; color: #0369a1; font-weight: 600; display: flex; align-items: center; gap: 0.25rem; text-decoration: none;">
                            <i class="fa-solid fa-envelope"></i>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
