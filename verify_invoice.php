<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MiskStone — Tax Invoice Verification</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            color: #e2e8f0;
        }
        .verify-card {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 20px;
            max-width: 520px;
            width: 100%;
            overflow: hidden;
            box-shadow: 0 25px 60px rgba(0,0,0,0.5);
        }
        .verify-header {
            background: linear-gradient(135deg, #059669 0%, #10b981 100%);
            padding: 2rem;
            text-align: center;
            position: relative;
        }
        .verify-header .logo {
            font-size: 1.5rem; font-weight: 800; color: white;
            display: flex; align-items: center; justify-content: center; gap: 0.75rem;
        }
        .verify-header .logo i { font-size: 1.3rem; }
        .verify-header p {
            color: rgba(255,255,255,0.85); font-size: 0.85rem; margin-top: 0.5rem;
        }
        .verify-badge {
            display: inline-flex; align-items: center; gap: 0.5rem;
            background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.3);
            padding: 0.4rem 1rem; border-radius: 50px;
            font-weight: 600; font-size: 0.85rem; color: white; margin-top: 1rem;
        }
        .verify-body { padding: 2rem; }
        .field-row {
            display: flex; justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid #334155;
            font-size: 0.95rem;
        }
        .field-row:last-child { border-bottom: none; }
        .field-label { color: #94a3b8; font-weight: 500; }
        .field-value { font-weight: 600; text-align: right; color: #e2e8f0; }
        .field-value.income { color: #34d399; }
        .field-value.expense { color: #f87171; }
        .total-row {
            display: flex; justify-content: space-between;
            padding: 1.25rem 0 0.5rem;
            font-size: 1.25rem; font-weight: 800;
            border-top: 2px solid #059669;
            margin-top: 0.5rem;
        }
        .total-row .field-value { color: #34d399; font-size: 1.35rem; }
        .footer-note {
            text-align: center; padding: 1.5rem 2rem;
            background: #0f172a;
            border-top: 1px solid #334155;
            font-size: 0.8rem; color: #64748b; line-height: 1.6;
        }
        .footer-note strong { color: #94a3b8; }
        .error-state {
            text-align: center; padding: 3rem 2rem;
        }
        .error-state i { font-size: 3rem; color: #f87171; margin-bottom: 1rem; }
        .error-state h2 { margin-bottom: 0.5rem; }
        .error-state p { color: #94a3b8; }
        .ar-text { font-family: 'Inter', sans-serif; direction: rtl; }
    </style>
</head>
<body>
<?php
require_once __DIR__ . '/config/db_connect.php';

$invoiceId = trim($_GET['id'] ?? '');
$invoice = null;
$payload = null;

if (!empty($invoiceId)) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM invoices_jo WHERE invoice_id = ?");
        $stmt->execute([$invoiceId]);
        $invoice = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($invoice) {
            $payload = json_decode($invoice['payload'], true);
        }
    } catch (Exception $e) {
        // Table may not exist
    }
}
?>

<div class="verify-card">
    <?php if ($invoice && $payload): ?>
        <!-- Header -->
        <div class="verify-header">
            <div class="logo">
                <i class="fa-solid fa-gem"></i>
                MiskStone
            </div>
            <p>مسك للحجر الصناعي والديكور</p>
            <div class="verify-badge">
                <i class="fa-solid fa-circle-check"></i>
                Tax Invoice Verified
            </div>
        </div>

        <!-- Body -->
        <div class="verify-body">
            <div class="field-row">
                <span class="field-label">Invoice Number</span>
                <span class="field-value"><?= htmlspecialchars($invoice['invoice_id']) ?></span>
            </div>
            <div class="field-row">
                <span class="field-label">Issue Date</span>
                <span class="field-value"><?= htmlspecialchars($payload['IssueDate'] ?? '—') ?></span>
            </div>
            <div class="field-row">
                <span class="field-label">Seller</span>
                <span class="field-value">MiskStone (مسك)</span>
            </div>
            <div class="field-row">
                <span class="field-label">Buyer</span>
                <span class="field-value"><?= htmlspecialchars($payload['BuyerName'] ?? '—') ?></span>
            </div>
            <div class="field-row">
                <span class="field-label">Order Reference</span>
                <span class="field-value"><?= htmlspecialchars($payload['ReferenceOrderID'] ?? '—') ?></span>
            </div>
            <div class="field-row">
                <span class="field-label">Payment Method</span>
                <span class="field-value"><?= htmlspecialchars($payload['PaymentMethod'] ?? 'Bank Transfer') ?></span>
            </div>

            <hr style="border: none; border-top: 1px solid #334155; margin: 0.75rem 0;">

            <div class="field-row">
                <span class="field-label">Net Amount</span>
                <span class="field-value"><?= number_format($payload['TotalAmount'] ?? 0, 2) ?> JOD</span>
            </div>
            <div class="field-row">
                <span class="field-label">Sales Tax (16%)</span>
                <span class="field-value expense"><?= number_format($payload['TaxAmount'] ?? 0, 2) ?> JOD</span>
            </div>
            <div class="total-row">
                <span class="field-label">Grand Total</span>
                <span class="field-value"><?= number_format($payload['GrandTotal'] ?? 0, 2) ?> JOD</span>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer-note">
            <i class="fa-solid fa-shield-halved" style="color:#059669; margin-right:0.25rem;"></i>
            This invoice complies with the <strong>Jordanian Electronic National Billing System</strong><br>
            <span class="ar-text">نظام الفوترة الوطني الإلكتروني — دائرة ضريبة الدخل والمبيعات</span><br>
            <span style="color:#475569; margin-top:0.5rem; display:inline-block;">
                ISTD Ref: <?= htmlspecialchars($invoice['istd_ref'] ?? 'N/A') ?> · 
                Status: <?= htmlspecialchars($invoice['submission_status'] ?? 'Pending') ?>
            </span>
        </div>

    <?php else: ?>
        <!-- Error State -->
        <div class="verify-header" style="background: linear-gradient(135deg, #dc2626, #ef4444);">
            <div class="logo"><i class="fa-solid fa-gem"></i> MiskStone</div>
            <p>مسك للحجر الصناعي والديكور</p>
        </div>
        <div class="error-state">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <h2>Invoice Not Found</h2>
            <p>The invoice ID "<strong><?= htmlspecialchars($invoiceId ?: 'none') ?></strong>" could not be verified.</p>
            <p style="margin-top: 1rem;">Please ensure you scanned a valid MiskStone QR code.</p>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
