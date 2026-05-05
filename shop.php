<?php
/**
 * MiskStone — Shop / Product Catalog
 * Browse finished goods with name search and price filter.
 */
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/db_connect.php';

// ── Filter inputs ──
$searchName = trim($_GET['search'] ?? '');
$maxPrice   = trim($_GET['max_price'] ?? '');

// ── Fetch products ──
try {
    $sql = "SELECT fg.item_id, fg.item_name, fg.category, fg.stone_measurement,
                   fg.unit_cost, fg.quantity_in_stock, im.selling_price
            FROM inventory_finished_goods fg
            LEFT JOIN item_master im ON fg.item_id = im.item_id
            WHERE 1=1";
    $params = [];

    if ($searchName !== '') {
        $sql .= " AND fg.item_name LIKE ?";
        $params[] = '%' . $searchName . '%';
    }
    if ($maxPrice !== '' && is_numeric($maxPrice)) {
        $sql .= " AND fg.unit_cost <= ?";
        $params[] = (float) $maxPrice;
    }
    $sql .= " ORDER BY fg.item_name ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll();
} catch (PDOException $e) {
    $products = [];
}

// Cart count for badge
$cartCount = 0;
if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $ci) $cartCount += $ci['qty'];
}

// Icon helper
function getStoneIcon($name) {
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
    <title>Shop — MiskStone</title>
    <meta name="description" content="Browse the full MiskStone catalog of artificial marble, granite, and decorative stone products.">
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
            <a href="<?= BASE_URL ?>/shop.php" style="color: var(--store-accent); font-weight: 600;">Shop</a>
            <a href="<?= BASE_URL ?>/careers.php">Careers</a>
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
            <span>Shop</span>
        </div>

        <h1 class="section-title" style="margin-bottom: 2rem;">Browse Available Products</h1>

        <!-- Filter Bar -->
        <form method="GET" class="filter-bar" id="shopFilterForm">
            <div class="filter-group" style="flex: 2;">
                <label for="filter-search">Search</label>
                <input type="text" id="filter-search" name="search" placeholder="Search products by name..." value="<?= htmlspecialchars($searchName) ?>">
            </div>
            <div class="filter-group" style="flex: 1;">
                <label for="filter-max-price">Max Price (per unit)</label>
                <input type="number" id="filter-max-price" name="max_price" placeholder="e.g. 50" min="0" step="0.01" value="<?= htmlspecialchars($maxPrice) ?>">
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn-filter primary">
                    <i class="fa-solid fa-magnifying-glass" style="margin-right: 5px;"></i> Apply Filters
                </button>
                <a href="<?= BASE_URL ?>/shop.php" class="btn-filter secondary" style="text-decoration:none;">
                    <i class="fa-solid fa-xmark" style="margin-right: 4px;"></i> Clear
                </a>
            </div>
        </form>

        <!-- Results Count -->
        <p class="results-count">Found <strong><?= count($products) ?></strong> product<?= count($products) !== 1 ? 's' : '' ?></p>

        <!-- Product Grid -->
        <div class="shop-grid">
            <?php if (empty($products)): ?>
                <div style="grid-column: 1 / -1; text-align: center; padding: 4rem; color: #94a3b8;">
                    <i class="fa-solid fa-box-open" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.4;"></i>
                    <p>No products match your filters. Try adjusting your search.</p>
                </div>
            <?php else: ?>
                <?php foreach ($products as $prod): ?>
                    <?php
                        $icon = getStoneIcon($prod['item_name']);
                        $price = (float)(!empty($prod['selling_price']) && $prod['selling_price'] > 0 ? $prod['selling_price'] : ($prod['unit_cost'] ?? 0));
                        $inStock = (int)($prod['quantity_in_stock'] ?? 0);
                    ?>
                    <div class="shop-card">
                        <div class="shop-card-header">
                            <i class="fa-solid <?= $icon ?> card-icon"></i>
                            <?php if ($inStock > 0): ?>
                                <span class="card-badge"><i class="fa-solid fa-check" style="margin-right: 3px;"></i> In Stock</span>
                            <?php else: ?>
                                <span class="card-badge" style="background: rgba(220, 38, 38, 0.1); color: #dc2626;"><i class="fa-solid fa-xmark" style="margin-right: 3px;"></i> Out of Stock</span>
                            <?php endif; ?>
                        </div>
                        <div class="shop-card-body">
                            <div class="card-cat"><?= htmlspecialchars($prod['category']) ?></div>
                            <h3><?= htmlspecialchars($prod['item_name']) ?></h3>
                            <p class="card-meta"><?= htmlspecialchars($prod['stone_measurement'] ?? 'Standard Size') ?></p>
                            <div class="card-price">
                                <?= number_format($price, 2) ?> JOD <small>/ unit</small>
                            </div>
                        </div>
                        <a href="<?= BASE_URL ?>/product-single.php?id=<?= urlencode($prod['item_id']) ?>" class="btn-view-details">
                            View Details
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </div>

    <!-- Footer -->
    <footer style="background: #0f172a; color: #94a3b8; padding: 3rem 2rem; text-align: center;">
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
