<?php
/**
 * AURA ERP — Public Storefront
 * Public-facing site for customers to view products and request quotes.
 */
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/db_connect.php';

$successMsg = '';
$errorMsg = '';

// Handle Quote Request Form Submission (Option A: Insert direct as New Lead)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'request_quote') {
    $companyName = trim($_POST['company_name'] ?? '');
    $contactPerson = trim($_POST['contact_person'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $productInt = trim($_POST['product_interest'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (empty($companyName) || empty($contactPerson) || empty($email)) {
        $errorMsg = 'Please fill out all required fields.';
    } else {
        try {
            // Generate a unique Customer ID (e.g., WEB-TIMESTAMP)
            $customerId = 'WEB-' . time();
            
            // We append the product interest to the message/default address field or just leave it for Sales to see
            // Since there is no "notes" field in customers in seed_all, we'll prefix it to the default_address or just skip.
            // Actually, wait, let's just insert basic lead info into `customers`.
            // Columns: customer_id, company_name, contact_person, phone_number, email, lead_status, default_address
            $addressNotes = "Inquiry about: " . $productInt . " - Note: " . $message;

            $stmt = $pdo->prepare("INSERT INTO customers 
                (customer_id, company_name, contact_person, phone_number, email, lead_status, default_address) 
                VALUES (?, ?, ?, ?, ?, 'New', ?)");
                
            $stmt->execute([
                $customerId,
                $companyName,
                $contactPerson,
                $phone,
                $email,
                $addressNotes
            ]);

            $successMsg = 'Thank you! Your inquiry has been received. Our sales team will contact you shortly.';
        } catch (PDOException $e) {
            $errorMsg = 'An error occurred while submitting your request. Please try again.';
            error_log("Web Lead Error: " . $e->getMessage());
        }
    }
}

// Fetch Finished Goods from Item Master
try {
    $stmt = $pdo->query("SELECT item_name, category, stone_measurement 
                         FROM inventory_finished_goods 
                         ORDER BY item_name ASC");
    $products = $stmt->fetchAll();
} catch (PDOException $e) {
    // Fallback if inventory_finished_goods fails
    try {
        $stmt = $pdo->query("SELECT item_name, category, 'Standard Size' as stone_measurement 
                             FROM item_master 
                             WHERE category != 'Raw Material'
                             ORDER BY item_name ASC");
        $products = $stmt->fetchAll();
    } catch (PDOException $ex) {
        $products = [];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AURA | Premium Stone & Marble</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/store.css">
</head>
<body>

    <!-- Navigation -->
    <nav class="store-nav">
        <a href="#" class="store-brand">
            <i class="fa-solid fa-gem"></i>
            AURA
        </a>
        <div class="nav-links">
            <a href="#products">Our Products</a>
            <a href="#about">About Us</a>
            <a href="<?= BASE_URL ?>/modules/auth/login.php" class="btn-login-header">
                <i class="fa-solid fa-lock" style="margin-right: 6px;"></i> Employee Login
            </a>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <h1>Crafting Excellence in Stone & Marble</h1>
            <p>Direct from our factory to your project. Premium artificial marble, granite, and decorative stone specifically engineered for durability and aesthetic perfection.</p>
            <button class="btn-login-header" onclick="document.getElementById('products').scrollIntoView({behavior: 'smooth'})" style="padding: 1rem 2rem; font-size: 1.1rem;">
                Explore Catalog
            </button>
        </div>
    </section>

    <!-- Products Grid -->
    <section id="products" class="products-section">
        
        <?php if ($successMsg): ?>
            <div class="alert-success">
                <i class="fa-solid fa-check-circle" style="margin-right: 8px;"></i> <?= htmlspecialchars($successMsg) ?>
            </div>
        <?php endif; ?>
        <?php if ($errorMsg): ?>
            <div class="alert-success" style="background:#fee2e2; color:#991b1b; border-color:#fecaca;">
                <i class="fa-solid fa-exclamation-circle" style="margin-right: 8px;"></i> <?= htmlspecialchars($errorMsg) ?>
            </div>
        <?php endif; ?>

        <h2 class="section-title">Featured Products</h2>
        
        <div class="grid">
            <?php foreach ($products as $prod): ?>
                <?php
                    // Assign icon based on keyword
                    $icon = 'fa-layer-group'; // default
                    $nameLower = strtolower($prod['item_name']);
                    if (strpos($nameLower, 'marble') !== false) $icon = 'fa-chess-board';
                    if (strpos($nameLower, 'granite') !== false) $icon = 'fa-cubes';
                    if (strpos($nameLower, 'decorative') !== false) $icon = 'fa-leaf';
                    if (strpos($nameLower, 'countertop') !== false) $icon = 'fa-kitchen-set';
                ?>
                <div class="product-card">
                    <div class="product-icon">
                        <i class="fa-solid <?= $icon ?>"></i>
                    </div>
                    <div class="product-category"><?= htmlspecialchars($prod['category']) ?></div>
                    <h3><?= htmlspecialchars($prod['item_name']) ?></h3>
                    <p class="product-desc">
                        <?= htmlspecialchars($prod['stone_measurement'] ?? 'Standard Factory Size') ?>
                    </p>
                    <button class="btn-quote" onclick="openQuoteModal('<?= htmlspecialchars(addslashes($prod['item_name'])) ?>')">
                        Request Quote
                    </button>
                </div>
            <?php endforeach; ?>
            
            <?php if(empty($products)): ?>
                <div style="grid-column: 1/-1; text-align: center; color: #94a3b8; padding: 3rem;">
                    <i class="fa-solid fa-box-open" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                    <p>No catalog items found. Contact sales for availability.</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Quote Modal -->
    <div id="quoteModal" class="modal-overlay">
        <div class="modal">
            <button class="close-modal" onclick="closeQuoteModal()"><i class="fa-solid fa-times"></i></button>
            <h2>Request a Quote</h2>
            <p style="color: #64748b; margin-bottom: 1.5rem; font-size: 0.95rem;">
                Our sales team will get back to you with pricing and lead times for: <br>
                <strong id="modalProductName" style="color: #0f172a;"></strong>
            </p>

            <form method="POST">
                <input type="hidden" name="action" value="request_quote">
                <input type="hidden" name="product_interest" id="productInterestInput" value="">
                
                <div class="form-group">
                    <label>Company Name *</label>
                    <input type="text" name="company_name" required placeholder="e.g. Al-Aqsa Construction Co.">
                </div>
                
                <div class="form-group">
                    <label>Contact Person *</label>
                    <input type="text" name="contact_person" required placeholder="Your full name">
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
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
                    <label>Project Details / Quantity Required</label>
                    <textarea name="message" rows="3" placeholder="Tell us about your project..."></textarea>
                </div>

                <button type="submit" class="btn-submit">Submit Inquiry</button>
            </form>
        </div>
    </div>

    <script>
        function openQuoteModal(productName) {
            document.getElementById('modalProductName').innerText = productName;
            document.getElementById('productInterestInput').value = productName;
            document.getElementById('quoteModal').style.display = 'flex';
        }

        function closeQuoteModal() {
            document.getElementById('quoteModal').style.display = 'none';
        }

        // Close on clicking outside
        document.getElementById('quoteModal').addEventListener('click', function(e) {
            if(e.target === this) {
                closeQuoteModal();
            }
        });
    </script>
</body>
</html>
