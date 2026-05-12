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

// ── Pagination variables for Homepage ──
$limit = 8; // 8 items per page on homepage
$page  = max(1, intval($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

// ── Fetch total products count for pagination ──
try {
    $stmtCount = $pdo->query("SELECT COUNT(*) FROM inventory_finished_goods");
    $totalProducts = $stmtCount->fetchColumn();
    $totalPages = ceil($totalProducts / $limit);
} catch (PDOException $e) {
    $totalProducts = 0;
    $totalPages = 1;
}

// Fetch Finished Goods (Paginated)
try {
    $stmt = $pdo->prepare("SELECT item_id, item_name, category, stone_measurement, image_path 
                         FROM inventory_finished_goods 
                         ORDER BY item_name ASC LIMIT " . intval($limit) . " OFFSET " . intval($offset));
    $stmt->execute();
    $products = $stmt->fetchAll();
} catch (PDOException $e) {
    try {
        $stmt = $pdo->prepare("SELECT item_id, item_name, category, 'Standard Size' as stone_measurement, NULL as image_path 
                             FROM item_master 
                             WHERE category != 'Raw Material'
                             ORDER BY item_name ASC LIMIT " . intval($limit) . " OFFSET " . intval($offset));
        $stmt->execute();
        $products = $stmt->fetchAll();
    } catch (PDOException $ex) {
        $products = [];
    }
}
?>
<!DOCTYPE html>
<html lang="<?= $currentLang ?? 'en' ?>" dir="<?= ($currentLang ?? 'en') === 'ar' ? 'rtl' : 'ltr' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MiskStone | مسك للحجر الصناعي والديكور</title>
    <meta name="description" content="MiskStone — Premium artificial stone, marble, and decorative panels manufactured in Jordan. Request a quote today for your construction or interior design project.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/store.css?v=2.0">
</head>
<body>

    <!-- Navigation -->
    <?php include __DIR__ . '/includes/store_nav.php'; ?>

    <!-- Hero Section -->
    <section class="hero" style="background-image: url('<?= BASE_URL ?>/images/hero_generated.png');">
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <p class="hero-arabic"><?= $currentLang === 'ar' ? t('hero_arabic_name') : 'MiskStone' ?></p>
            <h1><?= t('hero_title') ?></h1>
            <p class="hero-desc"><?= t('hero_desc') ?></p>
            <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                <a href="<?= BASE_URL ?>/shop.php" class="btn-login-header" style="padding: 1rem 2rem; font-size: 1.1rem; text-decoration: none;">
                    <i class="fa-solid fa-cube" style="margin-right: 8px;"></i> <?= t('explore_catalog') ?>
                </a>
                <a href="<?= BASE_URL ?>/shop.php" class="btn-login-header" style="padding: 1rem 2rem; font-size: 1.1rem; text-decoration: none; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.3); backdrop-filter: blur(6px);" onclick="event.preventDefault(); document.getElementById('about').scrollIntoView({behavior: 'smooth'})">
                    <?= t('learn_more') ?>
                </a>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" style="padding: 5rem 2rem; max-width: 1200px; margin: auto;">
        <h2 class="section-title"><?= t('about_miskstone') ?></h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2.5rem; margin-top: 2rem;">
            <div style="padding: 2rem; border-radius: 16px; background: linear-gradient(135deg, #f8fafc, #e2e8f0); border: 1px solid #e2e8f0; text-align: center;">
                <img src="<?= BASE_URL ?>/images/miskstone_13.png" alt="<?= t('advanced_manufacturing') ?>" style="width: 100%; height: 220px; object-fit: cover; border-radius: 12px; margin-bottom: 1.5rem; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                <h3 style="margin-bottom: 0.75rem;"><?= t('advanced_manufacturing') ?></h3>
                <p style="color: #475569; line-height: 1.7;"><?= t('advanced_manufacturing_desc') ?></p>
            </div>
            <div style="padding: 2rem; border-radius: 16px; background: linear-gradient(135deg, #f8fafc, #e2e8f0); border: 1px solid #e2e8f0; text-align: center;">
                <img src="<?= BASE_URL ?>/images/miskstone_18.png" alt="<?= t('engineered_formulas') ?>" style="width: 100%; height: 220px; object-fit: cover; border-radius: 12px; margin-bottom: 1.5rem; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                <h3 style="margin-bottom: 0.75rem;"><?= t('engineered_formulas') ?></h3>
                <p style="color: #475569; line-height: 1.7;"><?= t('engineered_formulas_desc') ?></p>
            </div>
            <div style="padding: 2rem; border-radius: 16px; background: linear-gradient(135deg, #f8fafc, #e2e8f0); border: 1px solid #e2e8f0; text-align: center;">
                <img src="<?= BASE_URL ?>/images/miskstone_16.png" alt="<?= t('regional_leader') ?>" style="width: 100%; height: 220px; object-fit: cover; border-radius: 12px; margin-bottom: 1.5rem; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                <h3 style="margin-bottom: 0.75rem;"><?= t('regional_leader') ?></h3>
                <p style="color: #475569; line-height: 1.7;"><?= t('regional_leader_desc') ?></p>
            </div>
        </div>
    </section>

    <!-- Manufacturing Process -->
    <section id="process" style="padding: 4rem 2rem; background: #f1f5f9;">
        <div style="max-width: 1200px; margin: auto;">
            <h2 class="section-title"><?= t('our_manufacturing_process') ?></h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.5rem; margin-top: 2rem;">
                <div style="text-align: center; padding: 2rem 1rem;">
                    <div style="width: 60px; height: 60px; border-radius: 50%; background: #6366f1; color: white; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: 800; margin: 0 auto 1rem;">1</div>
                    <h4><?= t('raw_material_mixing') ?></h4>
                    <p style="font-size: 0.9rem; color: #64748b;"><?= t('raw_material_desc') ?></p>
                </div>
                <div style="text-align: center; padding: 2rem 1rem;">
                    <div style="width: 60px; height: 60px; border-radius: 50%; background: #6366f1; color: white; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: 800; margin: 0 auto 1rem;">2</div>
                    <h4><?= t('vibro_compression') ?></h4>
                    <p style="font-size: 0.9rem; color: #64748b;"><?= t('vibro_desc') ?></p>
                </div>
                <div style="text-align: center; padding: 2rem 1rem;">
                    <div style="width: 60px; height: 60px; border-radius: 50%; background: #6366f1; color: white; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: 800; margin: 0 auto 1rem;">3</div>
                    <h4><?= t('controlled_curing') ?></h4>
                    <p style="font-size: 0.9rem; color: #64748b;"><?= t('curing_desc') ?></p>
                </div>
                <div style="text-align: center; padding: 2rem 1rem;">
                    <div style="width: 60px; height: 60px; border-radius: 50%; background: #6366f1; color: white; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: 800; margin: 0 auto 1rem;">4</div>
                    <h4><?= t('qa_finishing') ?></h4>
                    <p style="font-size: 0.9rem; color: #64748b;"><?= t('qa_desc') ?></p>
                </div>
            </div>
            <div style="text-align: center; margin-top: 3rem;">
                <a href="<?= BASE_URL ?>/process.php" class="btn-login-header" style="padding: 0.85rem 2rem; text-decoration: none; font-size: 1rem; display: inline-block; background: white; color: var(--store-accent) !important; border: 1px solid var(--store-accent); box-shadow: none;">
                    <?= t('read_detailed_process') ?>
                </a>
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

        <h2 class="section-title"><?= t('our_product_catalog') ?></h2>
        
        <div class="grid">
            <?php foreach ($products as $prod): ?>
                <div class="product-card">
                    <?php if (!empty($prod['image_path'])): ?>
                        <img src="<?= BASE_URL ?>/<?= htmlspecialchars($prod['image_path']) ?>" alt="<?= htmlspecialchars($prod['item_name']) ?>" style="width: 100%; height: 220px; object-fit: cover; border-radius: 12px; margin-bottom: 1rem;">
                    <?php else: ?>
                        <div class="product-icon">
                            <i class="fa-solid fa-layer-group"></i>
                        </div>
                    <?php endif; ?>
                    
                    <div class="product-category"><?= htmlspecialchars($prod['category']) ?></div>
                    <h3><?= htmlspecialchars($prod['item_name']) ?></h3>
                    <p class="product-desc" style="flex-grow: 1;">
                        <?= htmlspecialchars($prod['stone_measurement'] ?? t('standard_factory_size')) ?>
                    </p>
                    <a href="<?= BASE_URL ?>/product-single.php?id=<?= urlencode($prod['item_id']) ?>" class="btn-quote" style="text-decoration:none; display:inline-block; text-align:center; margin-top: auto;">
                        <?= t('view_details') ?>
                    </a>
                </div>
            <?php endforeach; ?>
            
            <?php if(empty($products)): ?>
                <div style="grid-column: 1/-1; text-align: center; color: #94a3b8; padding: 3rem;">
                    <i class="fa-solid fa-box-open" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                    <p><?= t('no_products') ?></p>
                </div>
            <?php endif; ?>
        </div>
        
        <?php if ($totalPages > 1): ?>
        <div style="display: flex; justify-content: center; flex-wrap: wrap; gap: 0.5rem; margin-top: 3rem;">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <?php 
                    $pageUrl = BASE_URL . '/index.php?page=' . $i . '#products';
                ?>
                <a href="<?= htmlspecialchars($pageUrl) ?>" style="padding: 0.5rem 1rem; border-radius: 8px; text-decoration: none; font-weight: 600; <?php if ($i == $page) echo 'background: var(--store-accent); color: white;'; else echo 'background: #fff; color: var(--store-primary); border: 1px solid #e2e8f0;'; ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
        
        <div style="text-align: center; margin-top: 2rem;">
            <a href="<?= BASE_URL ?>/shop.php" class="btn-login-header" style="padding: 1rem 3rem; text-decoration: none; font-size: 1.1rem; display: inline-block;">
                <i class="fa-solid fa-layer-group" style="margin-right: 8px;"></i> <?= t('view_all_products') ?>
            </a>
        </div>
    </section>

    <!-- Footer -->
    <?php include __DIR__ . '/includes/store_footer.php'; ?>

    <!-- Quote Modal -->
    <div id="quoteModal" class="modal-overlay">
        <div class="modal">
            <button class="close-modal" onclick="closeQuoteModal()"><i class="fa-solid fa-times"></i></button>
            <h2><?= t('request_quote') ?></h2>
            <p style="color: #64748b; margin-bottom: 1.5rem; font-size: 0.95rem;">
                <?= t('quote_desc') ?> <br>
                <strong id="modalProductName" style="color: #0f172a;"></strong>
            </p>

            <form method="POST">
                <input type="hidden" name="action" value="request_quote">
                <input type="hidden" name="product_interest" id="productInterestInput" value="">
                
                <div class="form-group">
                    <label><?= t('company_name') ?> *</label>
                    <input type="text" name="company_name" required placeholder="e.g. Al-Aqsa Construction Co.">
                </div>
                
                <div class="form-group">
                    <label><?= t('contact_person') ?> *</label>
                    <input type="text" name="contact_person" required placeholder="<?= t('contact_person') ?>">
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label><?= t('email_address') ?> *</label>
                        <input type="email" name="email" required placeholder="name@company.com">
                    </div>
                    <div class="form-group">
                        <label><?= t('phone_number') ?></label>
                        <input type="tel" name="phone" placeholder="+962 79 555 1234">
                    </div>
                </div>

                <div class="form-group">
                    <label><?= t('preferred_payment') ?></label>
                    <select name="payment_method" style="width: 100%; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.95rem; background: white; color: #0f172a;">
                        <option value="Bank Transfer"><?= t('bank_transfer') ?></option>
                        <option value="Credit Card"><?= t('credit_card') ?></option>
                        <option value="Cash on Delivery"><?= t('cash_on_delivery') ?></option>
                        <option value="Letter of Credit (L/C)"><?= t('letter_of_credit') ?></option>
                    </select>
                </div>

                <div class="form-group">
                    <label><?= t('project_details') ?></label>
                    <textarea name="message" rows="3" placeholder="<?= t('project_placeholder') ?>"></textarea>
                </div>

                <button type="submit" class="btn-submit"><?= t('submit_inquiry') ?></button>
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
