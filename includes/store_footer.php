<?php
/**
 * MiskStone — Shared Storefront Footer
 * Requires lang.php to be loaded for t() translations.
 */
require_once __DIR__ . '/../config/lang.php';
?>
<footer style="background: #0f172a; color: #94a3b8; padding: 3rem 2rem; text-align: center;">
    <div style="max-width: 1200px; margin: auto;">
        <div style="font-size: 1.5rem; font-weight: 700; color: white; margin-bottom: 0.5rem;">
            <i class="fa-solid fa-gem" style="margin-right: 8px; color: #6366f1;"></i> MiskStone
        </div>
        <p style="margin-bottom: 0.5rem; font-size: 1.1rem;">مسك للحجر الصناعي والديكور</p>
        
        <div style="margin: 1.5rem 0; display: flex; justify-content: center; gap: 1.5rem;">
            <a href="https://www.facebook.com/share/1AzQoJaXg3/?mibextid=wwXIfr" target="_blank" style="color: #94a3b8; font-size: 1.5rem; transition: color 0.3s;" onmouseover="this.style.color='#1877F2'" onmouseout="this.style.color='#94a3b8'">
                <i class="fa-brands fa-facebook"></i>
            </a>
            <a href="https://www.instagram.com/miskstone1?igsh=MXJoanAyY2FhOXFraQ==" target="_blank" style="color: #94a3b8; font-size: 1.5rem; transition: color 0.3s;" onmouseover="this.style.color='#E1306C'" onmouseout="this.style.color='#94a3b8'">
                <i class="fa-brands fa-instagram"></i>
            </a>
            <a href="tel:0777486786" style="color: #94a3b8; font-size: 1.5rem; transition: color 0.3s;" onmouseover="this.style.color='#10b981'" onmouseout="this.style.color='#94a3b8'">
                <i class="fa-solid fa-phone"></i>
            </a>
        </div>
        <p style="font-size: 1.1rem; margin-bottom: 0.5rem; color: white; font-weight: 500;"><i class="fa-solid fa-phone" style="margin-right: 5px; color: #6366f1;"></i> 0777486786</p>
        <p style="font-size: 0.85rem; margin-top: 1rem;"><?= t('footer_location') ?></p>
        <p style="font-size: 0.8rem; margin-top: 1.5rem; opacity: 0.6;">&copy; <?= date('Y') ?> MiskStone. <?= t('powered_by') ?></p>
    </div>
</footer>
