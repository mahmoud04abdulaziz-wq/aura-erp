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
                                      fg.unit_cost, fg.quantity_in_stock, fg.warehouse_location,
                                      im.base_uom
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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_to_cart' && $product) {
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
        $_SESSION['cart'][] = [
            'item_id'     => $product['item_id'],
            'name'        => $product['item_name'],
            'category'    => $product['category'],
            'unit_price'  => (float)($product['unit_cost'] ?? 0),
            'qty'         => $qty,
            'description' => $desc,
            'measurement' => $product['stone_measurement'] ?? '',
        ];
    }

    $addedMsg = htmlspecialchars($product['item_name']) . ' × ' . $qty . ' added to your cart!';
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
<html lang="en">
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
    <nav class="store-nav">
        <a href="<?= BASE_URL ?>/" class="store-brand">
            <i class="fa-solid fa-gem"></i> MiskStone
        </a>
        <div class="nav-links">
            <a href="<?= BASE_URL ?>/">Home</a>
            <a href="<?= BASE_URL ?>/shop.php">Shop</a>
            <a href="<?= BASE_URL ?>/cart.php" class="cart-link">
                <i class="fa-solid fa-bag-shopping"></i> Cart
                <?php if ($cartCount > 0): ?>
                    <span class="cart-badge"><?= $cartCount ?></span>
                <?php endif; ?>
            </a>
            <a href="<?= BASE_URL ?>/modules/auth/login.php" class="btn-login-header">
                <i class="fa-solid fa-lock" style="margin-right: 6px;"></i> Employee Login
            </a>
        </div>
    </nav>

    <div class="store-page">

        <!-- Breadcrumb -->
        <div class="breadcrumb">
            <a href="<?= BASE_URL ?>/">Home</a>
            <span>/</span>
            <a href="<?= BASE_URL ?>/shop.php">Shop</a>
            <span>/</span>
            <span><?= $product ? htmlspecialchars($product['item_name']) : 'Not Found' ?></span>
        </div>

        <?php if (!$product): ?>
            <div class="cart-empty">
                <i class="fa-solid fa-cube"></i>
                <h2>Product Not Found</h2>
                <p>The product you're looking for doesn't exist or has been removed.</p>
                <a href="<?= BASE_URL ?>/shop.php" class="btn-continue" style="margin-top: 1.5rem;">
                    <i class="fa-solid fa-arrow-left"></i> Back to Shop
                </a>
            </div>
        <?php else: ?>

            <?php if ($addedMsg): ?>
                <div class="alert-success" style="max-width: 700px; margin: 0 auto 2rem;">
                    <i class="fa-solid fa-check-circle" style="margin-right: 8px;"></i> <?= $addedMsg ?>
                    <a href="<?= BASE_URL ?>/cart.php" style="margin-left: 12px; color: #166534; font-weight: 600;">View Cart →</a>
                </div>
            <?php endif; ?>

            <div class="single-product-layout">

                <!-- Product Image Area -->
                <div class="single-product-image">
                    <i class="fa-solid <?= getStoneIconSingle($product['item_name']) ?>"></i>
                </div>

                <!-- Product Info -->
                <div class="single-product-info">
                    <div class="single-category"><?= htmlspecialchars($product['category']) ?></div>
                    <h1><?= htmlspecialchars($product['item_name']) ?></h1>
                    
                    <div class="single-price">
                        $<?= number_format((float)($product['unit_cost'] ?? 0), 2) ?> <small>/ <?= htmlspecialchars($product['base_uom'] ?? 'unit') ?></small>
                    </div>

                    <div class="single-meta">
                        <strong>Measurement:</strong> <?= htmlspecialchars($product['stone_measurement'] ?? 'Standard Size') ?><br>
                        <strong>Warehouse:</strong> <?= htmlspecialchars($product['warehouse_location'] ?? 'Main Warehouse') ?><br>
                        <strong>Stock Status:</strong> 
                        <?php if ((int)($product['quantity_in_stock'] ?? 0) > 0): ?>
                            <span style="color: #16a34a; font-weight: 600;">
                                <i class="fa-solid fa-circle-check"></i> In Stock (<?= (int)$product['quantity_in_stock'] ?> available)
                            </span>
                        <?php else: ?>
                            <span style="color: #dc2626; font-weight: 600;">
                                <i class="fa-solid fa-circle-xmark"></i> Out of Stock
                            </span>
                        <?php endif; ?>
                    </div>

                    <!-- Add to Cart Form -->
                    <form method="POST">
                        <input type="hidden" name="action" value="add_to_cart">

                        <label style="font-weight: 600; font-size: 0.9rem; color: var(--store-primary); display: block; margin-bottom: 0.5rem;">Quantity</label>
                        <div class="qty-control">
                            <button type="button" onclick="changeQty(-1)">−</button>
                            <input type="number" name="quantity" id="qtyInput" value="1" min="1" max="<?= max(1, (int)($product['quantity_in_stock'] ?? 9999)) ?>">
                            <button type="button" onclick="changeQty(1)">+</button>
                        </div>

                        <div class="form-group">
                            <label>Custom Description / Project Notes</label>
                            <textarea name="custom_description" rows="4" placeholder="e.g. Cut to 40×40cm panels, polished finish, for hotel lobby project..."></textarea>
                        </div>

                        <button type="submit" class="btn-add-cart">
                            <i class="fa-solid fa-cart-plus"></i> Add to Cart
                        </button>
                    </form>

                    <!-- Quick Links -->
                    <div style="margin-top: 2rem; display: flex; gap: 1rem;">
                        <a href="<?= BASE_URL ?>/shop.php" class="btn-continue">
                            <i class="fa-solid fa-arrow-left"></i> Continue Shopping
                        </a>
                        <?php if ($cartCount > 0): ?>
                            <a href="<?= BASE_URL ?>/cart.php" class="btn-continue" style="background: var(--store-accent); color: #fff;">
                                <i class="fa-solid fa-bag-shopping"></i> View Cart (<?= $cartCount ?>)
                            </a>
                        <?php endif; ?>
                    </div>
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
