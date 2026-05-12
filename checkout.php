<?php
/**
 * MiskStone — Checkout
 * Collects contact information, posts order as a new Web Lead for the Sales team.
 */
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/db_connect.php';

// Require login to checkout
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/modules/auth/login.php');
    exit;
}

$cart = $_SESSION['cart'] ?? [];
$orderPlaced = false;
$errorMsg = '';

// ── Process Checkout ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'place_order') {

    if (empty($cart)) {
        $errorMsg = 'Your cart is empty. Please add products before checking out.';
    } else {
        $companyName   = trim($_POST['company_name'] ?? '');
        $contactPerson = trim($_POST['contact_person'] ?? '');
        $email         = trim($_POST['email'] ?? '');
        $phone         = trim($_POST['phone'] ?? '');
        $paymentMethod = trim($_POST['payment_method'] ?? 'Bank Transfer');
        $deliveryAddr  = trim($_POST['delivery_address'] ?? '');
        $projectNotes  = trim($_POST['project_notes'] ?? '');

        if (empty($companyName) || empty($contactPerson) || empty($email)) {
            $errorMsg = 'Please fill out all required fields (Company, Contact, Email).';
        } else {
            try {
                // Build order summary from cart
                $orderLines = [];
                $grandTotal = 0;
                foreach ($cart as $item) {
                    $subtotal = $item['qty'] * $item['unit_price'];
                    $grandTotal += $subtotal;
                    $line = $item['name'] . ' ×' . $item['qty'] . ' @$' . number_format($item['unit_price'], 2) . ' = $' . number_format($subtotal, 2);
                    if (!empty($item['description'])) {
                        $line .= ' [Note: ' . $item['description'] . ']';
                    }
                    $orderLines[] = $line;
                }

                $orderSummary  = "=== ONLINE ORDER ===\n";
                $orderSummary .= implode("\n", $orderLines);
                $orderSummary .= "\n---\nTotal: $" . number_format($grandTotal, 2);
                $orderSummary .= "\nPayment: " . $paymentMethod;
                if ($paymentMethod === 'Bank Transfer') {
                    $bankName = trim($_POST['bank_name'] ?? '');
                    $bankIban = trim($_POST['bank_iban'] ?? '');
                    $bankSwift = trim($_POST['bank_swift'] ?? '');
                    if ($bankName) $orderSummary .= "\nBank: " . $bankName;
                    if ($bankIban) $orderSummary .= "\nAccount/IBAN: " . $bankIban;
                    if ($bankSwift) $orderSummary .= "\nSwift/Ref: " . $bankSwift;
                }
                if ($deliveryAddr) $orderSummary .= "\nDelivery: " . $deliveryAddr;
                if ($projectNotes) $orderSummary .= "\nProject Notes: " . $projectNotes;

                // Insert as new Web Lead (same pipeline the Sales dashboard reads)
                $stmt = $pdo->prepare("INSERT INTO customers 
                    (company_name, contact_person, phone_number, email, lead_status, default_address) 
                    VALUES (?, ?, ?, ?, 'New', ?)");
                $stmt->execute([
                    $companyName,
                    $contactPerson,
                    $phone,
                    $email,
                    $orderSummary
                ]);

                $customerId = $pdo->lastInsertId();

                // Save structured cart items to web_order_items
                $itemStmt = $pdo->prepare("INSERT INTO web_order_items (customer_id, item_id, item_name, quantity, unit_price) VALUES (?, ?, ?, ?, ?)");
                foreach ($cart as $item) {
                    $itemStmt->execute([
                        $customerId,
                        $item['item_id'] ?? 'FG-001',
                        $item['name'],
                        $item['qty'],
                        $item['unit_price']
                    ]);
                }

                // Clear the cart
                $_SESSION['cart'] = [];
                $orderPlaced = true;

            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                    // Company already exists — update their notes instead
                    try {
                        $stmt = $pdo->prepare("UPDATE customers SET default_address = ?, lead_status = 'New', contact_person = ?, phone_number = ?, email = ? WHERE company_name = ?");
                        $stmt->execute([$orderSummary, $contactPerson, $phone, $email, $companyName]);

                        // Get the existing customer ID and save structured items
                        $custIdStmt = $pdo->prepare("SELECT customer_id FROM customers WHERE company_name = ?");
                        $custIdStmt->execute([$companyName]);
                        $existingCustId = $custIdStmt->fetchColumn();

                        if ($existingCustId) {
                            // Remove old web order items for this customer
                            $pdo->prepare("DELETE FROM web_order_items WHERE customer_id = ?")->execute([$existingCustId]);
                            // Save new structured cart items
                            $itemStmt = $pdo->prepare("INSERT INTO web_order_items (customer_id, item_id, item_name, quantity, unit_price) VALUES (?, ?, ?, ?, ?)");
                            foreach ($cart as $item) {
                                $itemStmt->execute([
                                    $existingCustId,
                                    $item['item_id'] ?? 'FG-001',
                                    $item['name'],
                                    $item['qty'],
                                    $item['unit_price']
                                ]);
                            }
                        }

                        $_SESSION['cart'] = [];
                        $orderPlaced = true;
                    } catch (PDOException $ex) {
                        $errorMsg = 'An error occurred. Please try again.';
                        error_log("Checkout update error: " . $ex->getMessage());
                    }
                } else {
                    $errorMsg = 'An error occurred while placing your order. Please try again.';
                    error_log("Checkout error: " . $e->getMessage());
                }
            }
        }
    }
}

// Recalculate totals (for the review panel)
$cart = $_SESSION['cart'] ?? [];
$totalItems = 0;
$totalPrice = 0;
foreach ($cart as $item) {
    $totalItems += $item['qty'];
    $totalPrice += $item['qty'] * $item['unit_price'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout — MiskStone</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/store.css?v=2.0">
</head>
<body>

    <!-- Navigation -->
    <?php include __DIR__ . '/includes/store_nav.php'; ?>

    <div class="store-page">

        <!-- Breadcrumb -->
        <div class="breadcrumb">
            <a href="<?= BASE_URL ?>/">Home</a>
            <span>/</span>
            <a href="<?= BASE_URL ?>/shop.php">Shop</a>
            <span>/</span>
            <a href="<?= BASE_URL ?>/cart.php">Cart</a>
            <span>/</span>
            <span>Checkout</span>
        </div>

        <?php if ($orderPlaced): ?>
            <!-- Success State -->
            <div class="success-box">
                <div class="success-icon">
                    <i class="fa-solid fa-check"></i>
                </div>
                <h1>Order Submitted!</h1>
                <p>Thank you for your order. Our sales team has received your inquiry and will review it shortly. You'll be contacted at the email you provided with a formal quotation and delivery timeline.</p>
                <div style="margin-top: 2rem; display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                    <a href="<?= BASE_URL ?>/shop.php" class="btn-continue" style="padding: 0.9rem 1.75rem;">
                        <i class="fa-solid fa-bag-shopping"></i> Continue Shopping
                    </a>
                    <a href="<?= BASE_URL ?>/" class="btn-continue" style="padding: 0.9rem 1.75rem; background: var(--store-accent); color: #fff;">
                        <i class="fa-solid fa-house"></i> Back to Home
                    </a>
                </div>
            </div>

        <?php elseif (empty($cart)): ?>
            <div class="cart-empty">
                <i class="fa-solid fa-bag-shopping"></i>
                <h2>Nothing to checkout</h2>
                <p>Your cart is empty. Add some products first.</p>
                <a href="<?= BASE_URL ?>/shop.php" class="btn-continue" style="margin-top: 1.5rem; display: inline-flex;">
                    <i class="fa-solid fa-arrow-left"></i> Browse Products
                </a>
            </div>

        <?php else: ?>

            <h1 class="section-title" style="margin-bottom: 2rem;">Checkout</h1>

            <?php if ($errorMsg): ?>
                <div class="alert-success" style="background:#fee2e2; color:#991b1b; border-color:#fecaca; max-width: 800px; margin: 0 auto 2rem;">
                    <i class="fa-solid fa-exclamation-circle" style="margin-right: 8px;"></i> <?= htmlspecialchars($errorMsg) ?>
                </div>
            <?php endif; ?>

            <div class="checkout-layout">

                <!-- Contact Information Form -->
                <div class="checkout-form-card">
                    <h2><i class="fa-solid fa-user-pen" style="margin-right: 8px; color: var(--store-accent);"></i> Contact Information</h2>
                    <p class="subtitle">Provide your details so our sales team can process your order.</p>

                    <form method="POST">
                        <input type="hidden" name="action" value="place_order">

                        <div class="form-row">
                            <div class="form-group">
                                <label>Company Name *</label>
                                <input type="text" name="company_name" required placeholder="e.g. Al-Aqsa Construction Co.">
                            </div>
                            <div class="form-group">
                                <label>Contact Person *</label>
                                <input type="text" name="contact_person" required placeholder="Your full name">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Email Address *</label>
                                <input type="email" name="email" required placeholder="name@company.com">
                            </div>
                            <div class="form-group">
                                <label>Phone Number</label>
                                <input type="tel" name="phone" placeholder="+962 79 555 1234">
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Delivery Address</label>
                            <input type="text" name="delivery_address" placeholder="Street, City, Country">
                        </div>

                        <div class="form-group">
                            <label>Preferred Payment Method</label>
                            <select id="paymentMethodSelect" name="payment_method" onchange="toggleBankFields()" style="width: 100%; padding: 0.8rem 1rem; border: 1.5px solid #e2e8f0; border-radius: 10px; font-family: inherit; font-size: 0.95rem; background: #fff; color: var(--store-primary);">
                                <option value="Bank Transfer">Bank Transfer</option>
                                <option value="Credit Card">Credit Card</option>
                                <option value="Cash on Delivery">Cash on Delivery</option>
                                <option value="Letter of Credit (L/C)">Letter of Credit (L/C)</option>
                            </select>
                        </div>
                        
                        <div id="bankDetailsGroup" style="background: #f8fafc; padding: 1.5rem; border-radius: 10px; border: 1px solid #e2e8f0; margin-bottom: 1.5rem;">
                            <h4 style="margin: 0 0 1rem 0; color: var(--store-primary); font-size: 0.95rem;"><i class="fa-solid fa-building-columns" style="margin-right: 6px;"></i> Bank Transfer Information</h4>
                            
                            <div class="form-row">
                                <div class="form-group" style="margin-bottom: 0.75rem;">
                                    <label style="font-size: 0.85rem;">Bank Name</label>
                                    <input type="text" name="bank_name" placeholder="Your Bank Name">
                                </div>
                                <div class="form-group" style="margin-bottom: 0.75rem;">
                                    <label style="font-size: 0.85rem;">Account Number / IBAN</label>
                                    <input type="text" name="bank_iban" placeholder="JO00 ...">
                                </div>
                            </div>
                            <div class="form-group" style="margin-bottom: 0;">
                                <label style="font-size: 0.85rem;">Transfer Reference / Swift Code</label>
                                <input type="text" name="bank_swift" placeholder="Optional reference">
                            </div>
                        </div>

                        <script>
                            function toggleBankFields() {
                                const select = document.getElementById('paymentMethodSelect');
                                const bankGroup = document.getElementById('bankDetailsGroup');
                                if (select.value === 'Bank Transfer') {
                                    bankGroup.style.display = 'block';
                                } else {
                                    bankGroup.style.display = 'none';
                                }
                            }
                        </script>

                        <div class="form-group">
                            <label>Additional Project Notes</label>
                            <textarea name="project_notes" rows="3" placeholder="Any special instructions, delivery timeframes, or project requirements..."></textarea>
                        </div>

                        <button type="submit" class="btn-checkout" style="margin-top: 0.5rem;">
                            <i class="fa-solid fa-paper-plane" style="margin-right: 8px;"></i> Submit Order
                        </button>
                    </form>
                </div>

                <!-- Order Review Panel -->
                <div class="order-review-card">
                    <h3><i class="fa-solid fa-receipt" style="margin-right: 8px; color: var(--store-accent);"></i> Order Review</h3>

                    <?php foreach ($cart as $item): ?>
                        <div class="order-item-row">
                            <div>
                                <div class="item-name"><?= htmlspecialchars($item['name']) ?></div>
                                <div class="item-qty">× <?= $item['qty'] ?></div>
                            </div>
                            <div class="item-total"><?= number_format($item['qty'] * $item['unit_price'], 2) ?> JOD</div>
                        </div>
                    <?php endforeach; ?>

                    <div class="cart-summary-row total" style="margin-top: 1rem;">
                        <span>Total</span>
                        <span><?= number_format($totalPrice, 2) ?> JOD</span>
                    </div>

                    <a href="<?= BASE_URL ?>/cart.php" class="btn-continue" style="margin-top: 1.5rem; display: flex; justify-content: center;">
                        <i class="fa-solid fa-pen"></i> Edit Cart
                    </a>
                </div>

            </div>

        <?php endif; ?>

    </div>

    <!-- Footer -->
    <footer style="background: #0f172a; color: #94a3b8; padding: 3rem 2rem; text-align: center; margin-top: 4rem;">
        <div style="max-width: 1200px; margin: auto;">
            <div style="font-size: 1.5rem; font-weight: 700; color: white; margin-bottom: 0.5rem;">
                <i class="fa-solid fa-gem" style="margin-right: 8px; color: #6366f1;"></i> MiskStone
            </div>
            <p style="margin-bottom: 0.5rem;">مسك للحجر الصناعي والديكور</p>
            <p style="font-size: 0.8rem; margin-top: 1rem; opacity: 0.6;">&copy; <?= date('Y') ?> MiskStone. Powered by AURA ERP.</p>
        </div>
    </footer>

</body>
</html>
