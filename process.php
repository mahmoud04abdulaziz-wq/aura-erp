<?php
/**
 * MiskStone ERP — Our Process Page
 */
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/db_connect.php';

$pageTitle = 'Our Process';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Process | MiskStone</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/store.css?v=2.0">
    <style>
        .process-hero {
            padding: 8rem 2rem 4rem;
            text-align: center;
            background: linear-gradient(135deg, #f8fafc, #e2e8f0);
            border-bottom: 1px solid #cbd5e1;
            margin-bottom: 4rem;
        }
        .process-hero h1 {
            font-size: 3rem;
            color: var(--store-primary);
            margin-bottom: 1rem;
        }
        .process-hero p {
            color: #64748b;
            font-size: 1.25rem;
            max-width: 700px;
            margin: 0 auto;
        }
        
        .process-step {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            align-items: center;
            margin-bottom: 6rem;
            max-width: 1200px;
            margin-left: auto;
            margin-right: auto;
            padding: 0 2rem;
        }
        .process-step:nth-child(even) {
            grid-template-columns: 1fr 1fr;
            direction: rtl; /* reverse order visually */
        }
        .process-step:nth-child(even) > div {
            direction: ltr; /* keep text left-to-right */
        }
        
        @media (max-width: 900px) {
            .process-step, .process-step:nth-child(even) {
                grid-template-columns: 1fr;
                direction: ltr;
                gap: 2rem;
                margin-bottom: 4rem;
            }
        }
        
        .step-number {
            font-size: 5rem;
            font-weight: 800;
            color: #e2e8f0;
            line-height: 1;
            margin-bottom: 1rem;
        }
        .step-content h2 {
            font-size: 2rem;
            color: var(--store-primary);
            margin-bottom: 1.5rem;
        }
        .step-content p {
            font-size: 1.1rem;
            color: #475569;
            line-height: 1.8;
            margin-bottom: 1rem;
        }
        .step-image {
            background: #ffffff;
            border-radius: 24px;
            padding: 4rem;
            text-align: center;
            box-shadow: 0 10px 40px rgba(0,0,0,0.05);
            border: 1px solid rgba(226, 232, 240, 0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 300px;
        }
        .step-image i {
            font-size: 6rem;
            color: var(--store-accent);
            opacity: 0.8;
        }
    </style>
</head>
<body>

    <!-- Navigation -->
    <?php include __DIR__ . '/includes/store_nav.php'; ?>

    <section class="process-hero">
        <h1>How We Craft Perfection</h1>
        <p>A behind-the-scenes look at how raw materials become premium artificial stone through our state-of-the-art manufacturing processes.</p>
    </section>

    <!-- Step 1 -->
    <div class="process-step">
        <div class="step-content">
            <div class="step-number">01</div>
            <h2>Raw Material Selection & Mixing</h2>
            <p>Our journey begins with selecting the finest raw materials. We source high-grade white and grey cement, quartz aggregates, natural marble chips, and premium iron oxide pigments.</p>
            <p>These materials are carefully measured according to our proprietary recipes. Using automated batching plants, the components are blended uniformly to ensure consistent color and structural integrity across every batch we produce.</p>
        </div>
        <div class="step-image">
            <i class="fa-solid fa-blender"></i>
        </div>
    </div>

    <!-- Step 2 -->
    <div class="process-step">
        <div class="step-content">
            <div class="step-number">02</div>
            <h2>Vibro-Compression Molding</h2>
            <p>Once the mixture is perfectly homogenized, it is poured into precision-engineered molds. This is where the magic of our technology comes into play.</p>
            <p>We utilize advanced vibro-compression technology. By simultaneously applying intense high-frequency vibration and immense hydraulic pressure, we completely evacuate trapped air pockets. This results in an incredibly dense, non-porous structure that is stronger than natural stone.</p>
        </div>
        <div class="step-image">
            <i class="fa-solid fa-compress"></i>
        </div>
    </div>

    <!-- Step 3 -->
    <div class="process-step">
        <div class="step-content">
            <div class="step-number">03</div>
            <h2>Controlled Curing Chambers</h2>
            <p>The molded slabs are not rushed. They are carefully transferred to our climate-controlled curing chambers.</p>
            <p>Here, they cure for 18 to 36 hours at precise humidity and temperature levels. This controlled hydration process allows the cementitious matrix to achieve maximum hardness, significantly reducing the risk of hairline cracks and ensuring long-term durability.</p>
        </div>
        <div class="step-image">
            <i class="fa-solid fa-temperature-arrow-up"></i>
        </div>
    </div>

    <!-- Step 4 -->
    <div class="process-step">
        <div class="step-content">
            <div class="step-number">04</div>
            <h2>Finishing & Quality Assurance</h2>
            <p>After curing, the stone is ready for finishing. Depending on the product, it undergoes automated calibration, polishing, or texturing to achieve the desired surface finish—whether that's a high-gloss marble shine or a rugged natural cleft.</p>
            <p>Finally, every piece passes through a rigorous Quality Assurance inspection. We check for dimensional accuracy, color consistency, and structural flaws before the MiskStone seal of approval is applied and the products are carefully packaged for delivery.</p>
        </div>
        <div class="step-image">
            <i class="fa-solid fa-magnifying-glass-chart"></i>
        </div>
    </div>

    <div style="text-align: center; margin-bottom: 6rem;">
        <h2 style="font-size: 2rem; color: var(--store-primary); margin-bottom: 1.5rem;">Ready to see the results?</h2>
        <a href="<?= BASE_URL ?>/shop.php" style="display: inline-block; padding: 1rem 3rem; background: linear-gradient(135deg, var(--store-accent), #8b5cf6); color: white; text-decoration: none; border-radius: 50px; font-weight: 600; font-size: 1.1rem; box-shadow: 0 10px 20px rgba(99,102,241,0.25);">
            Explore Our Catalog
        </a>
    </div>

    <!-- Footer -->
    <footer style="background: #0f172a; color: #94a3b8; padding: 3rem 2rem; text-align: center;">
        <div style="max-width: 1200px; margin: auto;">
            <div style="font-size: 1.5rem; font-weight: 700; color: white; margin-bottom: 0.5rem;">
                <i class="fa-solid fa-gem" style="margin-right: 8px; color: #6366f1;"></i> MiskStone
            </div>
            <p style="margin-bottom: 0.5rem;">مسك للحجر الصناعي والديكور</p>
            <p style="font-size: 0.8rem; margin-top: 1.5rem; opacity: 0.6;">&copy; <?= date('Y') ?> MiskStone. Powered by AURA ERP.</p>
        </div>
    </footer>

</body>
</html>
