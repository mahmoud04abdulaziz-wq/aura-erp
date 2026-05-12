<?php
/**
 * MiskStone — Shopping Cart
 * View cart items, update quantities, remove items, and proceed to checkout.
 */
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/db_connect.php';

// Require login to use cart
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/modules/auth/login.php');
    exit;
}

// ── Handle cart actions ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['cart_action'] ?? '';

    if ($action === 'update' && isset($_POST['quantities']) && is_array($_POST['quantities'])) {
        foreach ($_POST['quantities'] as $idx => $qty) {
            $idx = (int) $idx;
            $qty = max(1, intval($qty));
            if (isset($_SESSION['cart'][$idx])) {
                $_SESSION['cart'][$idx]['qty'] = $qty;
            }
        }
    }

    if ($action === 'remove' && isset($_POST['remove_index'])) {
        $idx = (int) $_POST['remove_index'];
        if (isset($_SESSION['cart'][$idx])) {
            array_splice($_SESSION['cart'], $idx, 1);
        }
    }

    if ($action === 'clear') {
        $_SESSION['cart'] = [];
    }

    // Redirect to prevent form resubmission
    header("Location: " . BASE_URL . "/cart.php");
    exit;
}

$cart = $_SESSION['cart'] ?? [];

// Cart totals
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
    <title>Cart — MiskStone</title>
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
            <span>Cart</span>
        </div>

        <h1 class="section-title" style="margin-bottom: 2rem;">Your Cart</h1>

        <?php if (empty($cart)): ?>
            <div class="cart-empty">
                <i class="fa-solid fa-bag-shopping"></i>
                <h2>Your cart is empty</h2>
                <p>Looks like you haven't added any products yet.</p>
                <a href="<?= BASE_URL ?>/shop.php" class="btn-continue" style="margin-top: 1.5rem; display: inline-flex;">
                    <i class="fa-solid fa-arrow-left"></i> Browse Products
                </a>
            </div>
        <?php else: ?>

            <form method="POST">
                <input type="hidden" name="cart_action" value="update">
                <table class="cart-table">
                    <thead>
                        <tr>
                            <th style="width: 40%;">Product</th>
                            <th>Unit Price</th>
                            <th>Quantity</th>
                            <th>Subtotal</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cart as $idx => $item): ?>
                            <tr>
                                <td>
                                    <div class="cart-item-name"><?= htmlspecialchars($item['name']) ?></div>
                                    <?php if (!empty($item['description'])): ?>
                                        <div class="cart-item-desc">"<?= htmlspecialchars($item['description']) ?>"</div>
                                    <?php endif; ?>
                                    <div style="font-size: 0.8rem; color: #94a3b8; margin-top: 0.2rem;"><?= htmlspecialchars($item['category']) ?> · <?= htmlspecialchars($item['measurement'] ?? '') ?></div>
                                </td>
                                <td><?= number_format($item['unit_price'], 2) ?> JOD</td>
                                <td>
                                    <input type="number" name="quantities[<?= $idx ?>]" value="<?= $item['qty'] ?>" min="1" 
                                           style="width: 70px; padding: 0.5rem; border: 1.5px solid #e2e8f0; border-radius: 8px; text-align: center; font-weight: 600; font-family: inherit;">
                                </td>
                                <td class="cart-subtotal"><?= number_format($item['qty'] * $item['unit_price'], 2) ?> JOD</td>
                                <td>
                                    <!-- Remove button via separate form -->
                                    <button type="submit" formaction="<?= BASE_URL ?>/cart.php" name="cart_action" value="remove" class="btn-remove" title="Remove item"
                                            onclick="this.form.querySelector('[name=cart_action]').value='remove'; 
                                                     let h = document.createElement('input'); h.type='hidden'; h.name='remove_index'; h.value='<?= $idx ?>'; this.form.appendChild(h);">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 1.5rem; flex-wrap: wrap; gap: 1rem;">
                    <div style="display: flex; gap: 0.75rem;">
                        <a href="<?= BASE_URL ?>/shop.php" class="btn-continue">
                            <i class="fa-solid fa-arrow-left"></i> Continue Shopping
                        </a>
                        <button type="submit" class="btn-filter primary" style="font-size: 0.9rem;">
                            <i class="fa-solid fa-arrows-rotate" style="margin-right: 4px;"></i> Update Cart
                        </button>
                    </div>
                </div>
            </form>

            <!-- Cart Summary -->
            <div class="cart-summary">
                <div class="cart-summary-box">
                    <h3>Order Summary</h3>
                    <div class="cart-summary-row">
                        <span>Items</span>
                        <span><?= $totalItems ?></span>
                    </div>
                    <div class="cart-summary-row">
                        <span>Subtotal</span>
                        <span><?= number_format($totalPrice, 2) ?> JOD</span>
                    </div>
                    <div class="cart-summary-row total">
                        <span>Total</span>
                        <span><?= number_format($totalPrice, 2) ?> JOD</span>
                    </div>
                    <a href="<?= BASE_URL ?>/checkout.php" class="btn-checkout">
                        Proceed to Checkout <i class="fa-solid fa-arrow-right" style="margin-left: 8px;"></i>
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
