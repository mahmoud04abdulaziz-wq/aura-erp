<?php
/**
 * MiskStone — Careers Page
 * Public page displaying open job postings with application functionality.
 */
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/db_connect.php';

// Fetch open job postings with department names
try {
    $postings = $pdo->query("
        SELECT jp.*, d.department_name
        FROM job_postings jp
        JOIN departments d ON jp.department_id = d.department_id
        WHERE jp.status = 'Open'
        ORDER BY jp.posted_date DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $postings = [];
}

// Cart count for nav badge
$hCartCount = 0;
if (!empty($_SESSION['cart'])) { foreach ($_SESSION['cart'] as $hci) $hCartCount += $hci['qty']; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Careers — MiskStone | مسك للحجر الصناعي والديكور</title>
    <meta name="description" content="Join the MiskStone team. Browse open positions in manufacturing, sales, finance, and more. Apply online today.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/store.css?v=2.0">
    <style>
        .careers-hero {
            background: linear-gradient(135deg, #1e1b4b 0%, #312e81 40%, #4338ca 100%);
            color: white;
            padding: 6rem 2rem 4rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .careers-hero::before {
            content: '';
            position: absolute;
            top: -50%; left: -50%;
            width: 200%; height: 200%;
            background: radial-gradient(circle at 30% 70%, rgba(99, 102, 241, 0.15) 0%, transparent 50%),
                        radial-gradient(circle at 70% 30%, rgba(168, 85, 247, 0.1) 0%, transparent 50%);
            animation: heroFloat 20s ease-in-out infinite;
        }
        @keyframes heroFloat {
            0%, 100% { transform: translate(0, 0); }
            50% { transform: translate(-3%, 3%); }
        }
        .careers-hero h1 { font-size: 2.75rem; font-weight: 800; margin-bottom: 1rem; position: relative; z-index: 1; }
        .careers-hero p { font-size: 1.15rem; max-width: 650px; margin: 0 auto; opacity: 0.85; line-height: 1.7; position: relative; z-index: 1; }

        .careers-stats {
            display: flex; gap: 2rem; justify-content: center; margin-top: 2.5rem; position: relative; z-index: 1;
        }
        .careers-stat {
            background: rgba(255,255,255,0.1); backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.15); border-radius: 12px;
            padding: 1.25rem 2rem; text-align: center; min-width: 120px;
        }
        .careers-stat .num { font-size: 2rem; font-weight: 800; }
        .careers-stat .lbl { font-size: 0.8rem; opacity: 0.7; margin-top: 0.25rem; text-transform: uppercase; letter-spacing: 1px; }

        .postings-section { padding: 4rem 2rem; max-width: 900px; margin: 0 auto; }
        .postings-section h2 { text-align: center; font-size: 1.75rem; margin-bottom: 0.5rem; color: #1e1b4b; }
        .postings-section .subtitle { text-align: center; color: #64748b; margin-bottom: 3rem; font-size: 1rem; }

        .posting-card {
            background: white; border: 1px solid #e2e8f0; border-radius: 16px;
            padding: 2rem; margin-bottom: 1.5rem;
            transition: all 0.3s ease; position: relative;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        }
        .posting-card:hover {
            border-color: #6366f1; box-shadow: 0 8px 30px rgba(99,102,241,0.1);
            transform: translateY(-2px);
        }
        .posting-header { display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; margin-bottom: 1rem; flex-wrap: wrap; }
        .posting-title { font-size: 1.35rem; font-weight: 700; color: #1e1b4b; margin: 0; }
        .posting-badges { display: flex; gap: 0.5rem; flex-wrap: wrap; }
        .posting-badge {
            padding: 0.3rem 0.75rem; border-radius: 20px; font-size: 0.75rem; font-weight: 600;
            text-transform: uppercase; letter-spacing: 0.5px;
        }
        .badge-dept { background: rgba(99,102,241,0.1); color: #6366f1; }
        .badge-type { background: rgba(16,185,129,0.1); color: #059669; }

        .posting-desc { color: #475569; line-height: 1.7; margin-bottom: 1.25rem; font-size: 0.95rem; }

        .posting-reqs { margin-bottom: 1.5rem; }
        .posting-reqs h4 { font-size: 0.85rem; font-weight: 700; color: #1e1b4b; margin-bottom: 0.5rem; text-transform: uppercase; letter-spacing: 0.5px; }
        .posting-reqs pre {
            font-family: 'Inter', sans-serif; font-size: 0.9rem; color: #475569;
            white-space: pre-wrap; line-height: 1.8; margin: 0;
            background: #f8fafc; padding: 1rem; border-radius: 10px; border: 1px solid #e2e8f0;
        }

        .posting-footer { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem; }
        .posting-date { font-size: 0.85rem; color: #94a3b8; }
        .posting-date i { margin-right: 4px; }

        .btn-apply {
            display: inline-flex; align-items: center; gap: 0.5rem;
            padding: 0.75rem 1.75rem; background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: white; border: none; border-radius: 10px; font-size: 0.95rem; font-weight: 600;
            cursor: pointer; transition: all 0.3s; text-decoration: none; font-family: 'Inter', sans-serif;
        }
        .btn-apply:hover { transform: translateY(-1px); box-shadow: 0 8px 25px rgba(99,102,241,0.35); }

        /* Application Modal */
        .apply-modal {
            display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.5); backdrop-filter: blur(4px);
            z-index: 9999; align-items: center; justify-content: center; padding: 1rem;
        }
        .apply-modal.active { display: flex; }
        .apply-modal-content {
            background: white; border-radius: 20px; padding: 2.5rem; width: 100%; max-width: 560px;
            max-height: 90vh; overflow-y: auto;
            box-shadow: 0 25px 60px rgba(0,0,0,0.15);
            animation: slideUp 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }
        @keyframes slideUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }

        .apply-modal h2 { font-size: 1.5rem; margin: 0 0 0.25rem; color: #1e1b4b; }
        .apply-modal .modal-subtitle { color: #64748b; font-size: 0.9rem; margin-bottom: 1.75rem; }

        .apply-modal .form-group { margin-bottom: 1.25rem; }
        .apply-modal .form-group label { display: block; font-weight: 600; font-size: 0.85rem; color: #374151; margin-bottom: 0.4rem; }
        .apply-modal .form-group input,
        .apply-modal .form-group textarea {
            width: 100%; padding: 0.8rem 1rem; border: 1.5px solid #e2e8f0; border-radius: 10px;
            font-family: 'Inter', sans-serif; font-size: 0.95rem; color: #1f2937;
            transition: all 0.2s; box-sizing: border-box; background: #fafafa;
        }
        .apply-modal .form-group input:focus,
        .apply-modal .form-group textarea:focus {
            outline: none; border-color: #6366f1; background: #fff; box-shadow: 0 0 0 3px rgba(99,102,241,0.1);
        }

        .cv-upload-area {
            border: 2px dashed #d1d5db; border-radius: 12px; padding: 1.5rem;
            text-align: center; cursor: pointer; transition: all 0.3s; background: #fafafa;
        }
        .cv-upload-area:hover, .cv-upload-area.dragover { border-color: #6366f1; background: rgba(99,102,241,0.04); }
        .cv-upload-area i { font-size: 2rem; color: #9ca3af; margin-bottom: 0.5rem; }
        .cv-upload-area p { margin: 0; color: #6b7280; font-size: 0.9rem; }
        .cv-upload-area .selected-file { color: #6366f1; font-weight: 600; margin-top: 0.5rem; }

        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }

        .btn-submit-app {
            width: 100%; padding: 0.9rem; background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: white; border: none; border-radius: 10px; font-size: 1rem; font-weight: 600;
            cursor: pointer; transition: all 0.3s; font-family: 'Inter', sans-serif; margin-top: 0.5rem;
        }
        .btn-submit-app:hover { transform: translateY(-1px); box-shadow: 0 8px 25px rgba(99,102,241,0.35); }
        .btn-submit-app:disabled { opacity: 0.6; cursor: not-allowed; transform: none; box-shadow: none; }

        .modal-close {
            position: absolute; top: 1rem; right: 1rem; background: none; border: none;
            font-size: 1.5rem; cursor: pointer; color: #6b7280; transition: color 0.2s;
        }
        .modal-close:hover { color: #1f2937; }

        .apply-success {
            text-align: center; padding: 2rem 0;
        }
        .apply-success i { font-size: 4rem; color: #10b981; margin-bottom: 1rem; }
        .apply-success h3 { font-size: 1.25rem; color: #1e1b4b; margin-bottom: 0.5rem; }
        .apply-success p { color: #64748b; }

        .no-postings {
            text-align: center; padding: 4rem 2rem; color: #94a3b8;
        }
        .no-postings i { font-size: 4rem; margin-bottom: 1rem; opacity: 0.5; }

        @media (max-width: 640px) {
            .careers-hero h1 { font-size: 1.75rem; }
            .careers-stats { flex-direction: column; align-items: center; }
            .posting-header { flex-direction: column; }
            .form-row { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <!-- Navigation -->
    <nav class="store-nav">
        <a href="<?= BASE_URL ?>/" class="store-brand">
            <i class="fa-solid fa-gem"></i> MiskStone
        </a>
        <div class="nav-links">
            <a href="<?= BASE_URL ?>/">Home</a>
            <a href="<?= BASE_URL ?>/shop.php">Shop</a>
            <a href="<?= BASE_URL ?>/careers.php" style="color: #6366f1; font-weight: 600;">Careers</a>
            <a href="<?= BASE_URL ?>/cart.php" class="cart-link">
                <i class="fa-solid fa-bag-shopping"></i> Cart
                <?php if ($hCartCount > 0): ?>
                    <span class="cart-badge"><?= $hCartCount ?></span>
                <?php endif; ?>
            </a>
            <a href="<?= BASE_URL ?>/modules/auth/login.php" class="btn-login-header">
                <i class="fa-solid fa-lock" style="margin-right: 6px;"></i> Employee Login
            </a>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="careers-hero">
        <h1><i class="fa-solid fa-briefcase" style="margin-right: 12px; opacity: 0.8;"></i>Join Our Team</h1>
        <p>Build the future of artificial stone manufacturing with us. At MiskStone, we value craftsmanship, innovation, and teamwork. Explore our open positions and take the next step in your career.</p>
        <div class="careers-stats">
            <div class="careers-stat">
                <div class="num"><?= count($postings) ?></div>
                <div class="lbl">Open Positions</div>
            </div>
            <div class="careers-stat">
                <div class="num"><?= count(array_unique(array_column($postings, 'department_name'))) ?></div>
                <div class="lbl">Departments</div>
            </div>
            <div class="careers-stat">
                <div class="num">Amman</div>
                <div class="lbl">Location</div>
            </div>
        </div>
    </section>

    <!-- Postings Section -->
    <section class="postings-section">
        <h2>Open Positions</h2>
        <p class="subtitle">Find the perfect role for your skills and ambitions</p>

        <?php if (empty($postings)): ?>
            <div class="no-postings">
                <i class="fa-solid fa-briefcase"></i>
                <h3>No Open Positions Right Now</h3>
                <p>Check back later — we're always growing!</p>
            </div>
        <?php else: ?>
            <?php foreach ($postings as $post): ?>
                <div class="posting-card" id="posting-<?= $post['posting_id'] ?>">
                    <div class="posting-header">
                        <h3 class="posting-title"><?= htmlspecialchars($post['title']) ?></h3>
                        <div class="posting-badges">
                            <span class="posting-badge badge-dept"><i class="fa-solid fa-building" style="margin-right: 4px;"></i><?= htmlspecialchars($post['department_name']) ?></span>
                            <span class="posting-badge badge-type"><i class="fa-solid fa-clock" style="margin-right: 4px;"></i><?= htmlspecialchars($post['employment_type']) ?></span>
                        </div>
                    </div>
                    <p class="posting-desc"><?= htmlspecialchars($post['description']) ?></p>

                    <?php if ($post['requirements']): ?>
                        <div class="posting-reqs">
                            <h4><i class="fa-solid fa-list-check" style="margin-right: 6px;"></i>Requirements</h4>
                            <pre><?= htmlspecialchars($post['requirements']) ?></pre>
                        </div>
                    <?php endif; ?>

                    <div class="posting-footer">
                        <span class="posting-date"><i class="fa-regular fa-calendar"></i> Posted <?= date('M d, Y', strtotime($post['posted_date'])) ?></span>
                        <button class="btn-apply" onclick="openApplyModal(<?= $post['posting_id'] ?>, '<?= addslashes(htmlspecialchars($post['title'])) ?>')">
                            <i class="fa-solid fa-paper-plane"></i> Apply Now
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>

    <!-- Application Modal -->
    <div class="apply-modal" id="applyModal">
        <div class="apply-modal-content" style="position: relative;">
            <button class="modal-close" onclick="closeApplyModal()">&times;</button>

            <div id="applyFormContainer">
                <h2><i class="fa-solid fa-file-pen" style="margin-right: 8px; color: #6366f1;"></i>Apply for Position</h2>
                <p class="modal-subtitle">Applying for: <strong id="applyJobTitle"></strong></p>

                <form id="applicationForm" enctype="multipart/form-data">
                    <input type="hidden" name="posting_id" id="applyPostingId">

                    <div class="form-row">
                        <div class="form-group">
                            <label>Full Name *</label>
                            <input type="text" name="full_name" required placeholder="Your full name">
                        </div>
                        <div class="form-group">
                            <label>Email Address *</label>
                            <input type="email" name="email" required placeholder="name@email.com">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Phone Number</label>
                        <input type="tel" name="phone" placeholder="+962 79 555 1234">
                    </div>

                    <div class="form-group">
                        <label>Upload CV (PDF, max 5MB) *</label>
                        <div class="cv-upload-area" id="cvUploadArea" onclick="document.getElementById('cvFileInput').click()">
                            <i class="fa-solid fa-cloud-arrow-up"></i>
                            <p>Click to browse or drag & drop your CV</p>
                            <p style="font-size: 0.8rem; color: #9ca3af; margin-top: 0.25rem;">PDF format only, max 5MB</p>
                            <div class="selected-file" id="selectedFileName" style="display: none;"></div>
                        </div>
                        <input type="file" name="cv_file" id="cvFileInput" accept=".pdf" required style="display: none;">
                    </div>

                    <div class="form-group">
                        <label>Cover Letter / Message</label>
                        <textarea name="cover_letter" rows="4" placeholder="Tell us why you're a great fit for this role..."></textarea>
                    </div>

                    <button type="submit" class="btn-submit-app" id="submitBtn">
                        <i class="fa-solid fa-paper-plane" style="margin-right: 8px;"></i>Submit Application
                    </button>
                </form>
            </div>

            <div id="applySuccessContainer" style="display: none;">
                <div class="apply-success">
                    <i class="fa-solid fa-circle-check"></i>
                    <h3>Application Submitted!</h3>
                    <p id="successMessage">Thank you for applying. We'll review your application and get back to you soon.</p>
                    <button class="btn-apply" onclick="closeApplyModal()" style="margin-top: 1.5rem;">
                        <i class="fa-solid fa-check"></i> Got it!
                    </button>
                </div>
            </div>
        </div>
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

    <script>
        function openApplyModal(postingId, title) {
            document.getElementById('applyPostingId').value = postingId;
            document.getElementById('applyJobTitle').textContent = title;
            document.getElementById('applyFormContainer').style.display = '';
            document.getElementById('applySuccessContainer').style.display = 'none';
            document.getElementById('applicationForm').reset();
            document.getElementById('selectedFileName').style.display = 'none';
            document.getElementById('applyModal').classList.add('active');
        }

        function closeApplyModal() {
            document.getElementById('applyModal').classList.remove('active');
        }

        // Close on backdrop click
        document.getElementById('applyModal').addEventListener('click', function(e) {
            if (e.target === this) closeApplyModal();
        });

        // CV file selection display
        document.getElementById('cvFileInput').addEventListener('change', function() {
            const nameEl = document.getElementById('selectedFileName');
            if (this.files.length > 0) {
                nameEl.textContent = '📄 ' + this.files[0].name;
                nameEl.style.display = 'block';
            } else {
                nameEl.style.display = 'none';
            }
        });

        // Drag and drop for CV
        const uploadArea = document.getElementById('cvUploadArea');
        ['dragenter', 'dragover'].forEach(evt => {
            uploadArea.addEventListener(evt, e => { e.preventDefault(); uploadArea.classList.add('dragover'); });
        });
        ['dragleave', 'drop'].forEach(evt => {
            uploadArea.addEventListener(evt, e => { e.preventDefault(); uploadArea.classList.remove('dragover'); });
        });
        uploadArea.addEventListener('drop', e => {
            const files = e.dataTransfer.files;
            if (files.length > 0 && files[0].type === 'application/pdf') {
                document.getElementById('cvFileInput').files = files;
                document.getElementById('selectedFileName').textContent = '📄 ' + files[0].name;
                document.getElementById('selectedFileName').style.display = 'block';
            }
        });

        // Form submission
        document.getElementById('applicationForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const btn = document.getElementById('submitBtn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin" style="margin-right: 8px;"></i>Submitting...';

            try {
                const fd = new FormData(this);
                const res = await fetch('<?= BASE_URL ?>/modules/hr/submit_application.php', { method: 'POST', body: fd });
                const data = await res.json();

                if (data.success) {
                    document.getElementById('applyFormContainer').style.display = 'none';
                    document.getElementById('successMessage').textContent = data.message;
                    document.getElementById('applySuccessContainer').style.display = '';
                } else {
                    alert('Error: ' + data.error);
                }
            } catch (err) {
                alert('Network error. Please try again.');
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-paper-plane" style="margin-right: 8px;"></i>Submit Application';
            }
        });
    </script>

</body>
</html>
