<?php
/** MiskStone ERP — Supplier Management */
$suppliers = $pdo->query("SELECT * FROM suppliers ORDER BY supplier_id")->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="dashboard-container">
    <div class="welcome-banner" style="background: linear-gradient(135deg, #1e293b, #0ea5e9); color: white; padding: 2rem; border-radius: 16px; margin-bottom: 2rem;">
        <h1><i class="fa-solid fa-building" style="margin-right: 0.75rem;"></i>Suppliers</h1>
        <p style="opacity: 0.9;">Manage supplier contacts, currency, and what they supply.</p>
    </div>
    <div class="card">
        <div class="card-header">
            <h3>All Suppliers</h3>
            <span class="badge in-progress"><?= count($suppliers) ?> registered</span>
        </div>
        <div style="overflow-x: auto;">
            <table class="data-table">
                <thead><tr><th>ID</th><th>Name</th><th>Currency</th><th>Contact</th><th>Phone</th><th>Email</th><th>Supplies</th></tr></thead>
                <tbody>
                    <?php foreach ($suppliers as $s): ?>
                        <tr>
                            <td style="font-family: monospace;"><?= $s['supplier_id'] ?></td>
                            <td style="font-weight: 600;"><?= htmlspecialchars($s['supplier_name']) ?></td>
                            <td><span class="badge gray"><?= htmlspecialchars($s['preferred_currency'] ?? 'JOD') ?></span></td>
                            <td><?= htmlspecialchars($s['contact_person'] ?? '—') ?></td>
                            <td style="font-size: 0.85rem;"><?= htmlspecialchars($s['phone'] ?? '—') ?></td>
                            <td style="font-size: 0.85rem;"><?= htmlspecialchars($s['email'] ?? '—') ?></td>
                            <td style="font-size: 0.85rem; max-width: 200px; color: var(--text-secondary);"><?= htmlspecialchars($s['supplies_items'] ?? '—') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
