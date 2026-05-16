<?php
/**
 * MiskStone — Shop / Product Catalog
 * Browse finished goods with name search and price filter.
 */
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/db_connect.php';

// ── Filter inputs ──
$searchName = trim($_GET['search'] ?? '');
$maxPrice = trim($_GET['max_price'] ?? '');

// ── Pagination variables ──
$limit = 12; // Products per page
$page = max(1, intval($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

// ── Fetch total products count for pagination ──
try {
    $countSql = "SELECT COUNT(*) FROM inventory_finished_goods fg WHERE 1=1";
    $countParams = [];

    if ($searchName !== '') {
        $countSql .= " AND fg.item_name LIKE ?";
        $countParams[] = '%' . $searchName . '%';
    }
    if ($maxPrice !== '' && is_numeric($maxPrice)) {
        $countSql .= " AND fg.unit_cost <= ?";
        $countParams[] = (float) $maxPrice;
    }

    $stmtCount = $pdo->prepare($countSql);
    $stmtCount->execute($countParams);
    $totalProducts = $stmtCount->fetchColumn();
    $totalPages = ceil($totalProducts / $limit);
} catch (PDOException $e) {
    $totalProducts = 0;
    $totalPages = 1;
}

// ── Fetch products ──
try {
    $sql = "SELECT fg.item_id, fg.item_name, fg.category, fg.stone_measurement,
                   fg.unit_cost, fg.quantity_in_stock, fg.image_path, im.selling_price
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
    $sql .= " ORDER BY fg.item_name ASC LIMIT " . intval($limit) . " OFFSET " . intval($offset);

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll();
} catch (PDOException $e) {
    $products = [];
}

// Cart count for badge
$cartCount = 0;
if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $ci)
        $cartCount += $ci['qty'];
}

// Icon helper
function getStoneIcon($name)
{
    $n = strtolower($name);
    if (strpos($n, 'marble') !== false)
        return 'fa-chess-board';
    if (strpos($n, 'granite') !== false)
        return 'fa-cubes';
    if (strpos($n, 'decorative') !== false)
        return 'fa-leaf';
    if (strpos($n, 'countertop') !== false)
        return 'fa-kitchen-set';
    if (strpos($n, 'vanity') !== false)
        return 'fa-sink';
    if (strpos($n, 'cladding') !== false)
        return 'fa-border-all';
    return 'fa-layer-group';
}
?>
<!DOCTYPE html>
<html lang="<?= $currentLang ?? 'en' ?>" dir="<?= ($currentLang ?? 'en') === 'ar' ? 'rtl' : 'ltr' ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop — MiskStone</title>
    <meta name="description"
        content="Browse the full MiskStone catalog of artificial marble, granite, and decorative stone products.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
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
            <span><?= t('shop') ?></span>
        </div>

        <h1 class="section-title" style="margin-bottom: 2rem;"><?= t('browse_products') ?></h1>

        <!-- Filter Bar -->
        <form method="GET" class="filter-bar" id="shopFilterForm">
            <div class="filter-group" style="flex: 2;">
                <label for="filter-search"><?= t('search') ?></label>
                <input type="text" id="filter-search" name="search" placeholder="<?= t('search_placeholder') ?>"
                    value="<?= htmlspecialchars($searchName) ?>">
            </div>
            <div class="filter-group" style="flex: 1;">
                <label for="filter-max-price"><?= t('max_price') ?></label>
                <input type="number" id="filter-max-price" name="max_price" placeholder="e.g. 50" min="0" step="0.01"
                    value="<?= htmlspecialchars($maxPrice) ?>">
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn-filter primary">
                    <i class="fa-solid fa-magnifying-glass" style="margin-right: 5px;"></i> <?= t('apply_filters') ?>
                </button>
                <a href="<?= BASE_URL ?>/shop.php" class="btn-filter secondary" style="text-decoration:none;">
                    <i class="fa-solid fa-xmark" style="margin-right: 4px;"></i> <?= t('clear') ?>
                </a>
            </div>
        </form>

        <!-- Results Count -->
        <p class="results-count"><?= t('found') ?> <strong><?= count($products) ?></strong>
            <?= count($products) !== 1 ? t('products_word') : t('product_word') ?></p>

        <!-- Product Grid -->
        <div class="shop-grid">
            <?php if (empty($products)): ?>
                <div style="grid-column: 1 / -1; text-align: center; padding: 4rem; color: #94a3b8;">
                    <i class="fa-solid fa-box-open" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.4;"></i>
                    <p><?= t('no_filter_results') ?></p>
                </div>
            <?php else: ?>
                <?php foreach ($products as $prod): ?>
                    <?php
                    $icon = getStoneIcon($prod['item_name']);
                    $price = (float) (!empty($prod['selling_price']) && $prod['selling_price'] > 0 ? $prod['selling_price'] : ($prod['unit_cost'] ?? 0));
                    $inStock = (int) ($prod['quantity_in_stock'] ?? 0);
                    ?>
                    <div class="shop-card">
                        <div class="shop-card-header">
                            <?php if (!empty($prod['image_path'])): ?>
                                <img src="<?= BASE_URL ?>/<?= htmlspecialchars($prod['image_path']) ?>"
                                    alt="<?= htmlspecialchars($prod['item_name']) ?>"
                                    style="width: 100%; height: 100%; object-fit: cover; position: absolute; top: 0; left: 0; border-radius: 20px 20px 0 0;">
                            <?php else: ?>
                                <i class="fa-solid <?= $icon ?> card-icon"></i>
                            <?php endif; ?>
                            <?php if ($inStock > 0): ?>
                                <span class="card-badge"><i class="fa-solid fa-check" style="margin-right: 3px;"></i>
                                    <?= t('in_stock') ?></span>
                            <?php else: ?>
                                <span class="card-badge" style="background: rgba(220, 38, 38, 0.1); color: #dc2626;"><i
                                        class="fa-solid fa-xmark" style="margin-right: 3px;"></i> <?= t('out_of_stock') ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="shop-card-body">
                            <div class="card-cat"><?= htmlspecialchars($prod['category']) ?></div>
                            <h3><?= htmlspecialchars($prod['item_name']) ?></h3>
                            <p class="card-meta"><?= htmlspecialchars($prod['stone_measurement'] ?? t('standard_size')) ?></p>
                            <div class="card-price">
                                <?= number_format($price, 2) ?> JOD <small><?= t('per_unit') ?></small>
                            </div>
                        </div>
                        <a href="<?= BASE_URL ?>/product-single.php?id=<?= urlencode($prod['item_id']) ?>"
                            class="btn-view-details">
                            <?= t('view_details') ?>
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <?php if ($totalPages > 1): ?>
            <div style="display: flex; justify-content: center; flex-wrap: wrap; gap: 0.5rem; margin-top: 3rem;">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <?php
                    $queryArgs = $_GET;
                    $queryArgs['page'] = $i;
                    $pageUrl = BASE_URL . '/shop.php?' . http_build_query($queryArgs);
                    ?>
                    <a href="<?= htmlspecialchars($pageUrl) ?>"
                        style="padding: 0.5rem 1rem; border-radius: 8px; text-decoration: none; font-weight: 600; <?php if ($i == $page)
                            echo 'background: var(--store-accent); color: white;';
                        else
                            echo 'background: #fff; color: var(--store-primary); border: 1px solid #e2e8f0;'; ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>

    </div>

    <!-- Footer -->
    <?php include __DIR__ . '/includes/store_footer.php'; ?>

</body>

</html>