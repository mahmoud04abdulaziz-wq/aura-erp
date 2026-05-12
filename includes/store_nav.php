<?php
/**
 * MiskStone — Shared Storefront Navigation
 * Session-aware: shows Login or user dropdown with logout.
 * Includes hamburger menu for mobile.
 */
require_once __DIR__ . '/../config/lang.php';

$hCartCount = 0;
if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $hci) $hCartCount += $hci['qty'];
}
$isCustomerLoggedIn = isset($_SESSION['user_id']) && ($_SESSION['role_name'] ?? '') === 'Customer';
$customerName = $_SESSION['customer_name'] ?? 'User';
$langToggleTarget = ($currentLang === 'en') ? 'ar' : 'en';
$langToggleLabel = ($currentLang === 'en') ? 'العربية' : 'English';
$langToggleUrl = '?lang=' . $langToggleTarget;
// Preserve existing query params
if (!empty($_SERVER['QUERY_STRING'])) {
    $existingParams = $_GET;
    $existingParams['lang'] = $langToggleTarget;
    $langToggleUrl = '?' . http_build_query($existingParams);
}
?>

<nav class="store-nav" id="storeNav">
    <a href="<?= BASE_URL ?>/" class="store-brand">
        <i class="fa-solid fa-gem"></i>
        MiskStone
    </a>
    
    <!-- Hamburger toggle (mobile) -->
    <button class="nav-hamburger" id="navHamburger" aria-label="Toggle menu">
        <span></span><span></span><span></span>
    </button>
    
    <!-- Slide-out drawer overlay -->
    <div class="nav-overlay" id="navOverlay"></div>
    
    <div class="nav-links" id="navDrawer">
        <button class="nav-close-btn" id="navCloseBtn" aria-label="Close menu">&times;</button>
        <a href="<?= BASE_URL ?>/"><?= t('home') ?></a>
        <a href="<?= BASE_URL ?>/shop.php"><?= t('shop') ?></a>
        <a href="<?= BASE_URL ?>/process.php"><?= t('our_process') ?></a>
        <a href="<?= BASE_URL ?>/careers.php"><?= t('careers') ?></a>
        <a href="<?= htmlspecialchars($langToggleUrl) ?>" class="lang-toggle" title="Switch language" style="display: flex; align-items: center; gap: 5px; font-weight: 600; color: var(--store-accent);">
            <i class="fa-solid fa-globe" style="font-size: 0.9rem;"></i> <?= $langToggleLabel ?>
        </a>
        <a href="<?= BASE_URL ?>/cart.php" class="cart-link">
            <i class="fa-solid fa-bag-shopping"></i> <?= t('cart') ?>
            <?php if ($hCartCount > 0): ?>
                <span class="cart-badge"><?= $hCartCount ?></span>
            <?php endif; ?>
        </a>
        
        <?php if ($isCustomerLoggedIn): ?>
            <div class="user-dropdown">
                <button class="user-dropdown-toggle" onclick="toggleStoreProfile()">
                    <i class="fa-solid fa-circle-user"></i>
                    <span class="user-name-text"><?= htmlspecialchars($customerName) ?></span>
                </button>
            </div>
        <?php else: ?>
            <a href="<?= BASE_URL ?>/modules/auth/login.php" class="btn-login-header">
                <i class="fa-solid fa-user" style="margin-right: 6px;"></i> <?= t('login') ?>
            </a>
        <?php endif; ?>
    </div>
</nav>

<!-- Customer Profile Panel -->
<?php if ($isCustomerLoggedIn): ?>
<div class="profile-panel" id="store-profile-panel" style="position: fixed; top: 0; left: -400px; width: 320px; max-width: 100vw; height: 100vh; background: #fff; z-index: 2000; transition: left 0.3s ease; box-shadow: 10px 0 30px rgba(0,0,0,0.1); padding: 2rem; box-sizing: border-box;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 3rem;">
        <h3 style="margin: 0; color: var(--store-primary);"><?= t('user_profile') ?></h3>
        <button onclick="toggleStoreProfile()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #64748b;"><i class="fa-solid fa-times"></i></button>
    </div>
    <div style="text-align: center;">
        <div style="width: 80px; height: 80px; background: #1e293b; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2rem; margin: 0 auto 1.5rem;">
            <?= substr($customerName, 0, 1) ?>
        </div>
        <h4 style="margin: 0 0 0.5rem 0; color: var(--store-primary); font-size: 1.25rem;"><?= htmlspecialchars($customerName) ?></h4>
        <p style="color: #64748b; margin: 0 0 2rem 0; font-size: 0.9rem;"><?= t('customer_account') ?></p>
        <hr style="border: 0; border-top: 1px solid #e2e8f0; margin-bottom: 2rem;">
        
        <ul style="list-style: none; padding: 0; margin: 0; text-align: left;">
            <li>
                <a href="#" onclick="openStorePasswordModal(event)" style="display: flex; align-items: center; gap: 0.75rem; padding: 1rem; color: var(--store-primary); text-decoration: none; font-weight: 500; border-radius: 8px; transition: background 0.2s;">
                    <i class="fa-solid fa-key" style="width: 20px; text-align: center;"></i> <?= t('change_password') ?>
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>/modules/auth/logout.php" style="display: flex; align-items: center; gap: 0.75rem; padding: 1rem; color: #ef4444; text-decoration: none; font-weight: 500; border-radius: 8px; transition: background 0.2s;">
                    <i class="fa-solid fa-right-from-bracket" style="width: 20px; text-align: center;"></i> <?= t('logout') ?>
                </a>
            </li>
        </ul>
    </div>
</div>

<!-- Store Generic Modal Overlay -->
<div id="store-modal-overlay" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15,23,42,0.6); backdrop-filter: blur(4px); z-index: 3000; display: none; align-items: center; justify-content: center;">
    <div id="store-modal-content" style="background: #fff; padding: 2.5rem; border-radius: 16px; width: 100%; max-width: 400px; box-shadow: 0 20px 40px rgba(0,0,0,0.1);">
    </div>
</div>
<?php endif; ?>

<script>
(function() {
    const hamburger = document.getElementById('navHamburger');
    const drawer = document.getElementById('navDrawer');
    const overlay = document.getElementById('navOverlay');
    const closeBtn = document.getElementById('navCloseBtn');
    
    function openDrawer() {
        drawer.classList.add('open');
        overlay.classList.add('open');
        document.body.style.overflow = 'hidden';
    }
    function closeDrawer() {
        drawer.classList.remove('open');
        overlay.classList.remove('open');
        document.body.style.overflow = '';
    }
    
    if (hamburger) hamburger.addEventListener('click', openDrawer);
    if (overlay) overlay.addEventListener('click', closeDrawer);
    if (closeBtn) closeBtn.addEventListener('click', closeDrawer);
    
    // Close drawer when clicking a nav link (mobile)
    if (drawer) {
        drawer.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', closeDrawer);
        });
    }
})();

function toggleStoreProfile() {
    const panel = document.getElementById('store-profile-panel');
    const drawer = document.getElementById('navDrawer');
    const overlay = document.getElementById('navOverlay');
    
    if (panel.style.left === '0px') {
        panel.style.left = '-400px';
    } else {
        // Close nav drawer if open
        if (drawer && drawer.classList.contains('open')) {
            drawer.classList.remove('open');
            if (overlay) overlay.classList.remove('open');
            document.body.style.overflow = '';
        }
        panel.style.left = '0px';
    }
}

function openStorePasswordModal(e) {
    if (e) e.preventDefault();
    toggleStoreProfile(); // Close sidebar
    
    const modal = document.getElementById('store-modal-overlay');
    const contentContainer = document.getElementById('store-modal-content');
    
    const content = `
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
            <h2 style="margin:0; font-size: 1.5rem; color: #1e293b;">Change Password</h2>
            <button type="button" onclick="document.getElementById('store-modal-overlay').style.display='none'" style="background:none; border:none; font-size:1.5rem; cursor:pointer; color:#64748b;">&times;</button>
        </div>
        <form onsubmit="submitStoreChangePassword(event)">
            <div style="margin-bottom:1rem; text-align: left;">
                <label style="display:block; margin-bottom:0.5rem; font-weight: 600; color: #334155; font-size: 0.9rem;">Current Password *</label>
                <input type="password" name="old_password" required style="width:100%; padding:0.85rem; border:1px solid #e2e8f0; border-radius:8px; box-sizing: border-box;">
            </div>
            <div style="margin-bottom:1rem; text-align: left;">
                <label style="display:block; margin-bottom:0.5rem; font-weight: 600; color: #334155; font-size: 0.9rem;">New Password *</label>
                <input type="password" name="new_password" required minlength="8" style="width:100%; padding:0.85rem; border:1px solid #e2e8f0; border-radius:8px; box-sizing: border-box;">
            </div>
            <div style="margin-bottom:1.5rem; text-align: left;">
                <label style="display:block; margin-bottom:0.5rem; font-weight: 600; color: #334155; font-size: 0.9rem;">Confirm New Password *</label>
                <input type="password" name="confirm_password" required minlength="8" style="width:100%; padding:0.85rem; border:1px solid #e2e8f0; border-radius:8px; box-sizing: border-box;">
            </div>
            <button type="submit" style="padding:1rem; background:linear-gradient(135deg, #6366f1, #8b5cf6); color:white; border:none; border-radius:8px; width:100%; cursor:pointer; font-weight:600; font-size: 1rem;">
                Update Password
            </button>
        </form>
    `;
    
    contentContainer.innerHTML = content;
    modal.style.display = 'flex';
}

async function submitStoreChangePassword(e) {
    e.preventDefault();
    try {
        const fd = new FormData(e.target);
        const res = await fetch('<?= BASE_URL ?>/modules/auth/change_password.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            alert('Password changed successfully!');
            document.getElementById('store-modal-overlay').style.display='none';
        } else {
            alert('Error: ' + data.error);
        }
    } catch (err) {
        alert('Network error while changing password.');
    }
}
</script>
