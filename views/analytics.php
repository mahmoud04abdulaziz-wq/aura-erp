<?php
/**
 * AURA ERP — Analytics Reports Hub
 */
$tab = $_GET['tab'] ?? 'rop';
?>

<div class="card header-card" style="margin-bottom: 2rem; border-left: 4px solid var(--accent-primary);">
    <div style="display:flex; justify-content:space-between; align-items:center;">
        <div>
            <h2>Mathematical Analytics</h2>
            <p style="color:var(--text-secondary);">Enterprise Resource Planning Mathematical Models & Cost Analysis</p>
        </div>
    </div>
</div>

<div class="card" style="margin-bottom: 2rem; padding: 0; background: var(--bg-panel);">
    <div style="display:flex; border-bottom:1px solid var(--border-color);">
        <a href="<?= BASE_URL ?>/app.php?view=analytics&tab=rop" style="padding:1.5rem 2rem; text-decoration:none; font-weight:600; color: <?= $tab === 'rop' ? 'var(--accent-primary)' : 'var(--text-secondary)' ?>; border-bottom: 3px solid <?= $tab === 'rop' ? 'var(--accent-primary)' : 'transparent' ?>; display:flex; align-items:center; gap:0.75rem; transition: background 0.2s;">
            <i class="fa-solid fa-boxes-stacked"></i> Reorder Point & Safety Stock
        </a>
        <a href="<?= BASE_URL ?>/app.php?view=analytics&tab=bom" style="padding:1.5rem 2rem; text-decoration:none; font-weight:600; color: <?= $tab === 'bom' ? 'var(--accent-primary)' : 'var(--text-secondary)' ?>; border-bottom: 3px solid <?= $tab === 'bom' ? 'var(--accent-primary)' : 'transparent' ?>; display:flex; align-items:center; gap:0.75rem; transition: background 0.2s;">
            <i class="fa-solid fa-industry"></i> BOM Cost Explosion
        </a>
    </div>
</div>

<div class="analytics-content">
    <?php
    if ($tab === 'rop') {
        require_once __DIR__ . '/reports/rop_analysis.php';
    } elseif ($tab === 'bom') {
        require_once __DIR__ . '/reports/bom_analysis.php';
    }
    ?>
</div>