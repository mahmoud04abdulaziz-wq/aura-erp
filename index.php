<?php
/**
 * MiskStone ERP — Public Storefront
 * مسك للحجر الصناعي والديكور
 * Public-facing site for customers to view products and request quotes.
 */
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/db_connect.php';

$successMsg = '';
$errorMsg = '';

// Handle Quote Request Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'request_quote') {
    $companyName = trim($_POST['company_name'] ?? '');
    $contactPerson = trim($_POST['contact_person'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $productInt = trim($_POST['product_interest'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $paymentMethod = trim($_POST['payment_method'] ?? 'Not specified');

    if (empty($companyName) || empty($contactPerson) || empty($email)) {
        $errorMsg = 'Please fill out all required fields.';
    } else {
        try {
            $addressNotes = "Product: " . $productInt . " | Payment: " . $paymentMethod . " | Note: " . $message;

            $stmt = $pdo->prepare("INSERT INTO customers 
                (company_name, contact_person, phone_number, email, lead_status, default_address) 
                VALUES (?, ?, ?, ?, 'New', ?)");
                
            $stmt->execute([
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

// Fetch Finished Goods
try {
    $stmt = $pdo->query("SELECT item_name, category, stone_measurement 
                         FROM inventory_finished_goods 
                         ORDER BY item_name ASC");
    $products = $stmt->fetchAll();
} catch (PDOException $e) {
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
    <title>MiskStone | مسك للحجر الصناعي والديكور</title>
    <meta name="description" content="MiskStone — Premium artificial stone, marble, and decorative panels manufactured in Jordan. Request a quote today for your construction or interior design project.">
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
            MiskStone
        </a>
        <div class="nav-links">
            <a href="#about">About Us</a>
            <a href="#products">Products</a>
            <a href="#process">Our Process</a>
            <a href="<?= BASE_URL ?>/modules/auth/login.php" class="btn-login-header">
                <i class="fa-solid fa-lock" style="margin-right: 6px;"></i> Employee Login
            </a>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <p style="font-size: 1.1rem; letter-spacing: 3px; text-transform: uppercase; margin-bottom: 1rem; opacity: 0.8;">مسك للحجر الصناعي والديكور</p>
            <h1>Premium Artificial Stone<br>& Decorative Solutions</h1>
            <p>Engineered in Jordan. Built to last. MiskStone manufactures high-quality artificial marble, granite, and decorative panels using advanced vibro-compression and curing technologies for construction, interior design, and commercial projects across the Middle East.</p>
            <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                <button class="btn-login-header" onclick="document.getElementById('products').scrollIntoView({behavior: 'smooth'})" style="padding: 1rem 2rem; font-size: 1.1rem;">
                    <i class="fa-solid fa-cube" style="margin-right: 8px;"></i> Explore Catalog
                </button>
                <button class="btn-login-header" onclick="document.getElementById('about').scrollIntoView({behavior: 'smooth'})" style="padding: 1rem 2rem; font-size: 1.1rem; background: rgba(255,255,255,0.15); backdrop-filter: blur(10px);">
                    Learn More
                </button>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" style="padding: 5rem 2rem; max-width: 1200px; margin: auto;">
        <h2 class="section-title">About MiskStone</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2.5rem; margin-top: 2rem;">
            <div style="padding: 2rem; border-radius: 16px; background: linear-gradient(135deg, #f8fafc, #e2e8f0); border: 1px solid #e2e8f0;">
                <i class="fa-solid fa-industry" style="font-size: 2.5rem; color: #6366f1; margin-bottom: 1rem;"></i>
                <h3 style="margin-bottom: 0.75rem;">Advanced Manufacturing</h3>
                <p style="color: #475569; line-height: 1.7;">Our factory employs vibro-compression technology and controlled curing environments to produce artificial stone that rivals natural marble and granite in durability and aesthetics, at a fraction of the cost.</p>
            </div>
            <div style="padding: 2rem; border-radius: 16px; background: linear-gradient(135deg, #f8fafc, #e2e8f0); border: 1px solid #e2e8f0;">
                <i class="fa-solid fa-flask" style="font-size: 2.5rem; color: #6366f1; margin-bottom: 1rem;"></i>
                <h3 style="margin-bottom: 0.75rem;">Engineered Formulas</h3>
                <p style="color: #475569; line-height: 1.7;">Each product line uses a proprietary mix of white/grey cement, quartz aggregates, marble chips, iron oxide pigments, and polyester resin — precisely calibrated for color consistency, structural integrity, and weather resistance.</p>
            </div>
            <div style="padding: 2rem; border-radius: 16px; background: linear-gradient(135deg, #f8fafc, #e2e8f0); border: 1px solid #e2e8f0;">
                <i class="fa-solid fa-earth-americas" style="font-size: 2.5rem; color: #6366f1; margin-bottom: 1rem;"></i>
                <h3 style="margin-bottom: 0.75rem;">Regional Leader</h3>
                <p style="color: #475569; line-height: 1.7;">Based in Jordan, MiskStone serves clients across the Middle East — from residential villas in Amman to large-scale commercial developments in the Gulf. We combine local craftsmanship with international quality standards.</p>
            </div>
        </div>
    </section>

    <!-- Manufacturing Process -->
    <section id="process" style="padding: 4rem 2rem; background: #f1f5f9;">
        <div style="max-width: 1200px; margin: auto;">
            <h2 class="section-title">Our Manufacturing Process</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.5rem; margin-top: 2rem;">
                <div style="text-align: center; padding: 2rem 1rem;">
                    <div style="width: 60px; height: 60px; border-radius: 50%; background: #6366f1; color: white; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: 800; margin: 0 auto 1rem;">1</div>
                    <h4>Raw Material Mixing</h4>
                    <p style="font-size: 0.9rem; color: #64748b;">Cement, aggregates, and pigments are measured to recipe specifications and mixed uniformly.</p>
                </div>
                <div style="text-align: center; padding: 2rem 1rem;">
                    <div style="width: 60px; height: 60px; border-radius: 50%; background: #6366f1; color: white; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: 800; margin: 0 auto 1rem;">2</div>
                    <h4>Vibro-Compression</h4>
                    <p style="font-size: 0.9rem; color: #64748b;">The mixture is poured into molds and subjected to vibration + hydraulic pressure to remove air pockets.</p>
                </div>
                <div style="text-align: center; padding: 2rem 1rem;">
                    <div style="width: 60px; height: 60px; border-radius: 50%; background: #6366f1; color: white; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: 800; margin: 0 auto 1rem;">3</div>
                    <h4>Controlled Curing</h4>
                    <p style="font-size: 0.9rem; color: #64748b;">Slabs cure for 18–36 hours in temperature-controlled chambers for maximum hardness.</p>
                </div>
                <div style="text-align: center; padding: 2rem 1rem;">
                    <div style="width: 60px; height: 60px; border-radius: 50%; background: #6366f1; color: white; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: 800; margin: 0 auto 1rem;">4</div>
                    <h4>QA & Finishing</h4>
                    <p style="font-size: 0.9rem; color: #64748b;">Each piece undergoes quality inspection, polishing, and edge finishing before packaging.</p>
                </div>
            </div>
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

        <h2 class="section-title">Our Product Catalog</h2>
        
        <div class="grid">
            <?php foreach ($products as $prod): ?>
                <?php
                    $icon = 'fa-layer-group';
                    $nameLower = strtolower($prod['item_name']);
                    if (strpos($nameLower, 'marble') !== false) $icon = 'fa-chess-board';
                    if (strpos($nameLower, 'granite') !== false) $icon = 'fa-cubes';
                    if (strpos($nameLower, 'decorative') !== false) $icon = 'fa-leaf';
                    if (strpos($nameLower, 'countertop') !== false) $icon = 'fa-kitchen-set';
                    if (strpos($nameLower, 'vanity') !== false) $icon = 'fa-sink';
                    if (strpos($nameLower, 'cladding') !== false) $icon = 'fa-border-all';
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

    <!-- Footer -->
    <footer style="background: #0f172a; color: #94a3b8; padding: 3rem 2rem; text-align: center;">
        <div style="max-width: 1200px; margin: auto;">
            <div style="font-size: 1.5rem; font-weight: 700; color: white; margin-bottom: 0.5rem;">
                <i class="fa-solid fa-gem" style="margin-right: 8px; color: #6366f1;"></i> MiskStone
            </div>
            <p style="margin-bottom: 0.5rem;">مسك للحجر الصناعي والديكور</p>
            <p style="font-size: 0.85rem;">Jordan · Middle East · International Shipping Available</p>
            <p style="font-size: 0.8rem; margin-top: 1.5rem; opacity: 0.6;">&copy; <?= date('Y') ?> MiskStone. Powered by AURA ERP.</p>
        </div>
    </footer>

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
                    <label>Preferred Payment Method</label>
                    <select name="payment_method" style="width: 100%; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.95rem; background: white; color: #0f172a;">
                        <option value="Bank Transfer">Bank Transfer</option>
                        <option value="Credit Card">Credit Card</option>
                        <option value="Cash on Delivery">Cash on Delivery</option>
                        <option value="Letter of Credit (L/C)">Letter of Credit (L/C)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Project Details / Quantity Required</label>
                    <textarea name="message" rows="3" placeholder="Tell us about your project, required quantities, and delivery timeline..."></textarea>
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

        document.getElementById('quoteModal').addEventListener('click', function(e) {
            if(e.target === this) {
                closeQuoteModal();
            }
        });
    </script>
</body>
</html>
