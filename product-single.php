<?php
/**
 * MiskStone — Product Single Page
 * View product details, set quantity, add description, and add to cart.
 */
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/db_connect.php';

// ── Get product by ID ──
$itemId = trim($_GET['id'] ?? '');
$product = null;

if ($itemId !== '') {
    try {
        $stmt = $pdo->prepare("SELECT fg.item_id, fg.item_name, fg.category, fg.stone_measurement,
                                      fg.unit_cost, fg.quantity_in_stock, fg.warehouse_location, fg.image_path,
                                      im.base_uom, im.selling_price
                               FROM inventory_finished_goods fg
                               JOIN item_master im ON fg.item_id = im.item_id
                               WHERE fg.item_id = ?");
        $stmt->execute([$itemId]);
        $product = $stmt->fetch();
    } catch (PDOException $e) {
        $product = null;
    }
}

// ── Handle Add to Cart ──
$addedMsg = '';
$isLoggedIn = isset($_SESSION['user_id']);
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_to_cart') {
    if (!$isLoggedIn) {
        // Redirect to login, then back here
        header('Location: ' . BASE_URL . '/modules/auth/login.php');
        exit;
    }
    if ($product) {
    $qty = max(1, intval($_POST['quantity'] ?? 1));
    $desc = trim($_POST['custom_description'] ?? '');

    if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

    // Check if item already in cart — update qty
    $found = false;
    foreach ($_SESSION['cart'] as &$ci) {
        if ($ci['item_id'] === $product['item_id']) {
            $ci['qty'] += $qty;
            $ci['description'] = $desc ?: $ci['description'];
            $found = true;
            break;
        }
    }
    unset($ci);

    if (!$found) {
        $price = (float)(!empty($product['selling_price']) && $product['selling_price'] > 0 ? $product['selling_price'] : ($product['unit_cost'] ?? 0));
        $_SESSION['cart'][] = [
            'item_id'     => $product['item_id'],
            'name'        => $product['item_name'],
            'category'    => $product['category'],
            'unit_price'  => $price,
            'qty'         => $qty,
            'description' => $desc,
            'measurement' => $product['stone_measurement'] ?? '',
        ];
    }

    $addedMsg = htmlspecialchars($product['item_name']) . ' × ' . $qty . ' added to your cart!';
    }
}

// Cart count for badge
$cartCount = 0;
if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $ci) $cartCount += $ci['qty'];
}

// Icon helper
function getStoneIconSingle($name) {
    $n = strtolower($name);
    if (strpos($n, 'marble') !== false)     return 'fa-chess-board';
    if (strpos($n, 'granite') !== false)     return 'fa-cubes';
    if (strpos($n, 'decorative') !== false)  return 'fa-leaf';
    if (strpos($n, 'countertop') !== false)  return 'fa-kitchen-set';
    if (strpos($n, 'vanity') !== false)      return 'fa-sink';
    if (strpos($n, 'cladding') !== false)    return 'fa-border-all';
    return 'fa-layer-group';
}
?>
<!DOCTYPE html>
<html lang="<?= $currentLang ?? 'en' ?>" dir="<?= ($currentLang ?? 'en') === 'ar' ? 'rtl' : 'ltr' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $product ? htmlspecialchars($product['item_name']) . ' — MiskStone' : 'Product Not Found' ?></title>
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
            <a href="<?= BASE_URL ?>/"><?= t('home') ?></a>
            <span>/</span>
            <a href="<?= BASE_URL ?>/shop.php"><?= t('shop') ?></a>
            <span>/</span>
            <span><?= $product ? htmlspecialchars($product['item_name']) : t('product_not_found') ?></span>
        </div>

        <?php if (!$product): ?>
            <div class="cart-empty">
                <i class="fa-solid fa-cube"></i>
                <h2><?= t('product_not_found') ?></h2>
                <p><?= t('product_not_found_desc') ?></p>
                <a href="<?= BASE_URL ?>/shop.php" class="btn-continue" style="margin-top: 1.5rem;">
                    <i class="fa-solid fa-arrow-left"></i> <?= t('back_to_shop') ?>
                </a>
            </div>
        <?php else: ?>

            <?php if ($addedMsg): ?>
                <div class="alert-success" style="max-width: 700px; margin: 0 auto 2rem;">
                    <i class="fa-solid fa-check-circle" style="margin-right: 8px;"></i> <?= $addedMsg ?>
                    <a href="<?= BASE_URL ?>/cart.php" style="margin-left: 12px; color: #166534; font-weight: 600;"><?= t('view_cart') ?> →</a>
                </div>
            <?php endif; ?>

            <div class="single-product-layout">

                <!-- Product Image Area -->
                <div class="single-product-image">
                    <?php if (!empty($product['image_path'])): ?>
                        <img src="<?= BASE_URL ?>/<?= htmlspecialchars($product['image_path']) ?>" alt="<?= htmlspecialchars($product['item_name']) ?>" style="width: 100%; height: 100%; object-fit: cover; border-radius: 24px;">
                    <?php else: ?>
                        <i class="fa-solid <?= getStoneIconSingle($product['item_name']) ?>"></i>
                    <?php endif; ?>
                </div>

                <!-- Product Info -->
                <div class="single-product-info">
                    <div class="single-category"><?= htmlspecialchars($product['category']) ?></div>
                    <h1><?= htmlspecialchars($product['item_name']) ?></h1>
                    
                    <div class="single-price">
                        <?php $displayPrice = (float)(!empty($product['selling_price']) && $product['selling_price'] > 0 ? $product['selling_price'] : ($product['unit_cost'] ?? 0)); ?>
                        <?= number_format($displayPrice, 2) ?> JOD <small>/ <?= htmlspecialchars($product['base_uom'] ?? 'unit') ?></small>
                    </div>

                    <div class="single-meta">
                        <strong><?= t('measurement') ?>:</strong> <?= htmlspecialchars($product['stone_measurement'] ?? t('standard_size')) ?><br>
                        <strong><?= t('warehouse') ?>:</strong> <?= htmlspecialchars($product['warehouse_location'] ?? t('main_warehouse')) ?><br>
                        <strong><?= t('stock_status') ?>:</strong> 
                        <?php if ((int)($product['quantity_in_stock'] ?? 0) > 0): ?>
                            <span style="color: #16a34a; font-weight: 600;">
                                <i class="fa-solid fa-circle-check"></i> <?= t('in_stock') ?> (<?= (int)$product['quantity_in_stock'] ?> <?= t('available') ?>)
                            </span>
                        <?php else: ?>
                            <span style="color: #dc2626; font-weight: 600;">
                                <i class="fa-solid fa-circle-xmark"></i> <?= t('out_of_stock') ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <!-- Add to Cart Form -->
                    <form method="POST">
                        <input type="hidden" name="action" value="add_to_cart">

                        <label style="font-weight: 600; font-size: 0.9rem; color: var(--store-primary); display: block; margin-bottom: 0.5rem;"><?= t('quantity') ?></label>
                        <div class="qty-control">
                            <button type="button" onclick="changeQty(-1)">−</button>
                            <input type="number" name="quantity" id="qtyInput" value="1" min="1" max="<?= max(1, (int)($product['quantity_in_stock'] ?? 9999)) ?>">
                            <button type="button" onclick="changeQty(1)">+</button>
                        </div>

                        <div class="form-group">
                            <label><?= t('custom_description') ?></label>
                            <textarea name="custom_description" rows="4" placeholder="<?= t('custom_desc_placeholder') ?>"></textarea>
                        </div>

                        <?php if ($isLoggedIn): ?>
                            <button type="submit" class="btn-add-cart">
                                <i class="fa-solid fa-cart-plus"></i> <?= t('add_to_cart') ?>
                            </button>
                        <?php else: ?>
                            <div style="display: flex; flex-direction: column; gap: 0.75rem; margin-top: 1rem;">
                                <a href="<?= BASE_URL ?>/modules/auth/login.php" class="btn-add-cart" style="text-align: center; text-decoration: none;">
                                    <?= t('sign_up_to_order') ?>
                                </a>
                                <a href="<?= BASE_URL ?>/modules/auth/login.php" class="btn-continue" style="text-align: center; justify-content: center; width: 100%; box-sizing: border-box;">
                                    <?= t('log_in') ?>
                                </a>
                            </div>
                        <?php endif; ?>
                    </form>

                    <!-- Quick Links -->
                    <div style="margin-top: 2rem; display: flex; gap: 1rem;">
                        <a href="<?= BASE_URL ?>/shop.php" class="btn-continue">
                            <i class="fa-solid fa-arrow-left"></i> <?= t('continue_shopping') ?>
                        </a>
                        <?php if ($cartCount > 0): ?>
                            <a href="<?= BASE_URL ?>/cart.php" class="btn-continue" style="background: var(--store-accent); color: #fff;">
                                <i class="fa-solid fa-bag-shopping"></i> <?= t('view_cart') ?> (<?= $cartCount ?>)
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <?php include __DIR__ . '/includes/store_footer.php'; ?>

    <script>
        function changeQty(delta) {
            const input = document.getElementById('qtyInput');
            let val = parseInt(input.value) || 1;
            val = Math.max(1, val + delta);
            const max = parseInt(input.getAttribute('max')) || 9999;
            val = Math.min(val, max);
            input.value = val;
        }
    </script>
</body>
</html>
