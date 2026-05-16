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
        /* ── Parallax Orbs ── */
        .background-orbs-container {
            position: fixed;
            top: 0; left: 0; width: 100vw; height: 100vh;
            pointer-events: none;
            z-index: 1;
            overflow: hidden;
        }
        .orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.18;
            transition: transform 0.1s linear;
        }
        .orb-1 { width: 420px; height: 420px; background: #6366f1; top: 10%; left: -80px; }
        .orb-2 { width: 500px; height: 500px; background: #3b82f6; top: 50%; right: -120px; }
        .orb-3 { width: 320px; height: 320px; background: #8b5cf6; top: 80%; left: 25%; }

        /* Lift content above orbs */
        .store-nav, .process-hero, .process-timeline, .cta-section, footer, .modal-overlay {
            position: relative;
            z-index: 2;
        }

        /* ── Hero ── */
        .process-hero {
            padding: 8rem 2rem 4rem;
            text-align: center;
            background: linear-gradient(135deg, #f8fafc, #e2e8f0);
            border-bottom: 1px solid #cbd5e1;
            margin-bottom: 0;
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

        /* ── Timeline container ── */
        .process-timeline {
            max-width: 1200px;
            margin: 0 auto;
            padding: 4rem 2rem 2rem;
            position: relative;
        }

        /* Vertical connector line (background track) */
        .process-timeline::before {
            content: '';
            position: absolute;
            left: 50%;
            top: 4rem;
            bottom: 10rem;
            width: 3px;
            background: linear-gradient(to bottom, #6366f1 0%, #3b82f6 50%, #8b5cf6 100%);
            transform: translateX(-50%);
            opacity: 0.12;
            border-radius: 3px;
        }

        /* Animated fill line */
        .timeline-progress {
            position: absolute;
            left: 50%;
            top: 4rem;
            width: 3px;
            height: 0%;
            max-height: calc(100% - 14rem);
            background: linear-gradient(to bottom, #6366f1, #3b82f6, #8b5cf6);
            transform: translateX(-50%);
            border-radius: 3px;
            transition: height 0.05s linear;
            box-shadow: 0 0 12px rgba(99, 102, 241, 0.4);
        }

        /* ── Step ── */
        .process-step {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            align-items: center;
            margin-bottom: 6rem;
            position: relative;
        }
        .process-step:nth-child(even) {
            direction: rtl;
        }
        .process-step:nth-child(even) > div {
            direction: ltr;
        }

        @media (max-width: 900px) {
            .process-step, .process-step:nth-child(even) {
                grid-template-columns: 1fr;
                direction: ltr;
                gap: 2rem;
                margin-bottom: 4rem;
            }
            .process-timeline::before,
            .timeline-progress { display: none; }
        }

        /* Step number */
        .step-number {
            font-size: 5rem;
            font-weight: 800;
            line-height: 1;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            opacity: 0.25;
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

        /* Step image card */
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
            position: relative;
            overflow: hidden;
        }
        /* Rotating glow behind icon */
        .step-image::before {
            content: '';
            position: absolute;
            top: -50%; left: -50%;
            width: 200%; height: 200%;
            background: conic-gradient(from 0deg, transparent 0%, rgba(99,102,241,0.06) 25%, transparent 50%);
            animation: rotateGlow 8s linear infinite;
            opacity: 0;
            transition: opacity 0.8s ease;
        }
        .step-image.is-visible::before {
            opacity: 1;
        }
        @keyframes rotateGlow {
            to { transform: rotate(360deg); }
        }
        .step-image i {
            font-size: 6rem;
            color: var(--store-accent);
            opacity: 0.8;
            position: relative;
            z-index: 1;
        }

        /* ── Scroll Reveal Animations ── */
        .reveal-left {
            opacity: 0;
            transform: translateX(-60px);
            transition: opacity 0.9s ease-out, transform 0.9s cubic-bezier(0.2, 0.8, 0.2, 1);
        }
        .reveal-right {
            opacity: 0;
            transform: translateX(60px);
            transition: opacity 0.9s ease-out, transform 0.9s cubic-bezier(0.2, 0.8, 0.2, 1);
        }
        .reveal-up {
            opacity: 0;
            transform: translateY(50px);
            transition: opacity 0.9s ease-out, transform 0.9s cubic-bezier(0.2, 0.8, 0.2, 1);
        }
        .reveal-left.is-visible,
        .reveal-right.is-visible,
        .reveal-up.is-visible {
            opacity: 1;
            transform: translate(0);
        }

        /* Delay helpers */
        .anim-delay-1 { transition-delay: 0.15s; }
        .anim-delay-2 { transition-delay: 0.3s; }

        /* ── CTA Section ── */
        .cta-section {
            text-align: center;
            margin-bottom: 6rem;
            padding: 0 2rem;
        }
        .cta-section h2 {
            font-size: 2rem;
            color: var(--store-primary);
            margin-bottom: 1.5rem;
        }
        .cta-btn {
            display: inline-block;
            padding: 1rem 3rem;
            background: linear-gradient(135deg, var(--store-accent), #8b5cf6);
            color: white;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            font-size: 1.1rem;
            box-shadow: 0 10px 20px rgba(99,102,241,0.25);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .cta-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 14px 30px rgba(99,102,241,0.35);
        }
    </style>
</head>
<body>

    <!-- Parallax Background Orbs -->
    <div class="background-orbs-container">
        <div class="orb orb-1"></div>
        <div class="orb orb-2"></div>
        <div class="orb orb-3"></div>
    </div>

    <!-- Navigation -->
    <?php include __DIR__ . '/includes/store_nav.php'; ?>

    <section class="process-hero">
        <h1 class="reveal-up">How We Craft Perfection</h1>
        <p class="reveal-up anim-delay-1">A behind-the-scenes look at how raw materials become premium artificial stone through our state-of-the-art manufacturing processes.</p>
    </section>

    <div class="process-timeline">
        <!-- Animated progress line -->
        <div class="timeline-progress" id="timelineProgress"></div>

        <!-- Step 1 -->
        <div class="process-step">
            <div class="step-content reveal-left">
                <div class="step-number">01</div>
                <h2>Raw Material Selection & Mixing</h2>
                <p>Our journey begins with selecting the finest raw materials. We source high-grade white and grey cement, quartz aggregates, natural marble chips, and premium iron oxide pigments.</p>
                <p>These materials are carefully measured according to our proprietary recipes. Using automated batching plants, the components are blended uniformly to ensure consistent color and structural integrity across every batch we produce.</p>
            </div>
            <div class="step-image reveal-right anim-delay-1">
                <i class="fa-solid fa-blender"></i>
            </div>
        </div>

        <!-- Step 2 -->
        <div class="process-step">
            <div class="step-content reveal-right">
                <div class="step-number">02</div>
                <h2>Vibro-Compression Molding</h2>
                <p>Once the mixture is perfectly homogenized, it is poured into precision-engineered molds. This is where the magic of our technology comes into play.</p>
                <p>We utilize advanced vibro-compression technology. By simultaneously applying intense high-frequency vibration and immense hydraulic pressure, we completely evacuate trapped air pockets. This results in an incredibly dense, non-porous structure that is stronger than natural stone.</p>
            </div>
            <div class="step-image reveal-left anim-delay-1">
                <i class="fa-solid fa-compress"></i>
            </div>
        </div>

        <!-- Step 3 -->
        <div class="process-step">
            <div class="step-content reveal-left">
                <div class="step-number">03</div>
                <h2>Controlled Curing Chambers</h2>
                <p>The molded slabs are not rushed. They are carefully transferred to our climate-controlled curing chambers.</p>
                <p>Here, they cure for 18 to 36 hours at precise humidity and temperature levels. This controlled hydration process allows the cementitious matrix to achieve maximum hardness, significantly reducing the risk of hairline cracks and ensuring long-term durability.</p>
            </div>
            <div class="step-image reveal-right anim-delay-1">
                <i class="fa-solid fa-temperature-arrow-up"></i>
            </div>
        </div>

        <!-- Step 4 -->
        <div class="process-step">
            <div class="step-content reveal-right">
                <div class="step-number">04</div>
                <h2>Finishing & Quality Assurance</h2>
                <p>After curing, the stone is ready for finishing. Depending on the product, it undergoes automated calibration, polishing, or texturing to achieve the desired surface finish—whether that's a high-gloss marble shine or a rugged natural cleft.</p>
                <p>Finally, every piece passes through a rigorous Quality Assurance inspection. We check for dimensional accuracy, color consistency, and structural flaws before the MiskStone seal of approval is applied and the products are carefully packaged for delivery.</p>
            </div>
            <div class="step-image reveal-left anim-delay-1">
                <i class="fa-solid fa-magnifying-glass-chart"></i>
            </div>
        </div>
    </div>

    <div class="cta-section reveal-up">
        <h2>Ready to see the results?</h2>
        <a href="<?= BASE_URL ?>/shop.php" class="cta-btn">Explore Our Catalog</a>
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

    <script>
    document.addEventListener('DOMContentLoaded', () => {
        // ── Intersection Observer for scroll reveals ──
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.15 });

        document.querySelectorAll('.reveal-left, .reveal-right, .reveal-up, .step-image').forEach(el => {
            observer.observe(el);
        });

        // ── Parallax Orbs ──
        const orbs = document.querySelectorAll('.orb');
        window.addEventListener('scroll', () => {
            const scrolled = window.scrollY;
            orbs.forEach((orb, i) => {
                const speed = (i + 1) * 0.12;
                orb.style.transform = `translateY(${-(scrolled * speed)}px)`;
            });
        });

        // ── Timeline Progress Line ──
        const timelineEl = document.querySelector('.process-timeline');
        const progressEl = document.getElementById('timelineProgress');

        if (timelineEl && progressEl) {
            window.addEventListener('scroll', () => {
                const rect = timelineEl.getBoundingClientRect();
                const timelineTop = rect.top + window.scrollY;
                const timelineHeight = timelineEl.offsetHeight;
                const scrollPos = window.scrollY + window.innerHeight * 0.5;
                const progress = Math.min(Math.max((scrollPos - timelineTop) / timelineHeight, 0), 1);
                progressEl.style.height = (progress * 100) + '%';
            });
        }
    });
    </script>

</body>
</html>
