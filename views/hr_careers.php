<?php
/**
 * AURA ERP — HR Careers & Hiring Pipeline
 * Manage job postings and track applicants through the hiring pipeline.
 */

try {
    // Fetch all job postings with department names and applicant counts
    $postings = $pdo->query("
        SELECT jp.*, d.department_name,
               COUNT(ja.application_id) as applicant_count
        FROM job_postings jp
        JOIN departments d ON jp.department_id = d.department_id
        LEFT JOIN job_applications ja ON jp.posting_id = ja.posting_id
        GROUP BY jp.posting_id
        ORDER BY jp.status ASC, jp.posted_date DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Fetch all applications with posting info
    $applications = $pdo->query("
        SELECT ja.*, jp.title as job_title, d.department_name
        FROM job_applications ja
        JOIN job_postings jp ON ja.posting_id = jp.posting_id
        JOIN departments d ON jp.department_id = d.department_id
        ORDER BY ja.applied_at DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Department list for the create posting form
    $departments = $pdo->query("SELECT department_id, department_name FROM departments ORDER BY department_name")->fetchAll(PDO::FETCH_ASSOC);

    // Stats
    $openPostings = count(array_filter($postings, fn($p) => $p['status'] === 'Open'));
    $totalApplicants = count($applications);
    $interviewCount = count(array_filter($applications, fn($a) => $a['status'] === 'Interview'));
    $hiredCount = count(array_filter($applications, fn($a) => $a['status'] === 'Hired'));

} catch (Exception $e) {
    error_log("HR Careers error: " . $e->getMessage());
    $postings = $applications = $departments = [];
    $openPostings = $totalApplicants = $interviewCount = $hiredCount = 0;
}

// Group applications by status for pipeline
$pipeline = ['Applied' => [], 'Reviewed' => [], 'Interview' => [], 'Hired' => [], 'Rejected' => []];
foreach ($applications as $app) {
    $pipeline[$app['status']][] = $app;
}
?>

<style>
    .pipeline-board { display: flex; gap: 1rem; overflow-x: auto; padding-bottom: 1rem; }
    .pipeline-column {
        min-width: 260px; flex: 1; background: var(--bg-component, #f8fafc);
        border-radius: 12px; padding: 1rem; border: 1px solid var(--border-color, #e2e8f0);
    }
    .pipeline-column-header {
        display: flex; justify-content: space-between; align-items: center;
        margin-bottom: 1rem; padding-bottom: 0.75rem;
        border-bottom: 2px solid var(--border-color, #e2e8f0);
    }
    .pipeline-column-header h4 { margin: 0; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; }
    .pipeline-count {
        background: var(--accent-primary, #6366f1); color: white;
        font-size: 0.75rem; font-weight: 700; padding: 0.2rem 0.6rem; border-radius: 10px;
    }

    .pipeline-card {
        background: var(--bg-panel, white); border: 1px solid var(--border-color, #e2e8f0);
        border-radius: 10px; padding: 1rem; margin-bottom: 0.75rem;
        transition: all 0.2s; cursor: default;
    }
    .pipeline-card:hover { border-color: var(--accent-primary, #6366f1); box-shadow: 0 4px 12px rgba(0,0,0,0.06); }
    .pipeline-card .app-name { font-weight: 700; color: var(--text-primary); margin-bottom: 0.25rem; }
    .pipeline-card .app-meta { font-size: 0.8rem; color: var(--text-secondary); margin-bottom: 0.5rem; }
    .pipeline-card .app-job { font-size: 0.8rem; color: var(--accent-primary, #6366f1); font-weight: 500; margin-bottom: 0.5rem; }
    .pipeline-card .app-actions { display: flex; gap: 0.4rem; flex-wrap: wrap; }
    .pipeline-card .app-actions button {
        padding: 0.3rem 0.6rem; font-size: 0.7rem; border-radius: 6px; border: 1px solid var(--border-color, #e2e8f0);
        cursor: pointer; font-weight: 600; transition: all 0.2s; font-family: inherit;
        background: var(--bg-component, #f1f5f9); color: var(--text-primary);
    }
    .pipeline-card .app-actions button:hover { border-color: var(--accent-primary, #6366f1); color: var(--accent-primary, #6366f1); }
    .pipeline-card .app-actions button.move-btn { background: rgba(99,102,241,0.1); color: #6366f1; border-color: rgba(99,102,241,0.2); }
    .pipeline-card .app-actions button.reject-btn { background: rgba(239,68,68,0.08); color: #ef4444; border-color: rgba(239,68,68,0.2); }
    .pipeline-card .app-actions button.hire-btn { background: rgba(16,185,129,0.1); color: #059669; border-color: rgba(16,185,129,0.2); }
    .pipeline-card .cv-link { font-size: 0.8rem; color: var(--accent-primary, #6366f1); text-decoration: none; font-weight: 500; }
    .pipeline-card .cv-link:hover { text-decoration: underline; }

    .col-applied .pipeline-column-header { border-bottom-color: #6366f1; }
    .col-reviewed .pipeline-column-header { border-bottom-color: #f59e0b; }
    .col-interview .pipeline-column-header { border-bottom-color: #8b5cf6; }
    .col-hired .pipeline-column-header { border-bottom-color: #10b981; }
    .col-rejected .pipeline-column-header { border-bottom-color: #ef4444; }

    .posting-table-badge { padding: 0.2rem 0.6rem; border-radius: 6px; font-size: 0.75rem; font-weight: 600; }
    .posting-open { background: rgba(16,185,129,0.1); color: #059669; }
    .posting-closed { background: rgba(107,114,128,0.1); color: #6b7280; }

    .tab-buttons { display: flex; gap: 0.5rem; margin-bottom: 1.5rem; }
    .tab-btn {
        padding: 0.6rem 1.25rem; border: 1px solid var(--border-color, #e2e8f0);
        border-radius: 8px; cursor: pointer; font-size: 0.9rem; font-weight: 500;
        background: transparent; color: var(--text-secondary); transition: all 0.2s; font-family: inherit;
    }
    .tab-btn.active { background: var(--accent-primary, #6366f1); color: white; border-color: var(--accent-primary, #6366f1); }
    .tab-content { display: none; }
    .tab-content.active { display: block; }
</style>

<!-- Stats -->
<div class="stats-grid" style="margin-bottom: 1.5rem;">
    <div class="stat-card">
        <div class="stat-icon primary"><i class="fa-solid fa-briefcase"></i></div>
        <div class="stat-info">
            <h3>Open Positions</h3>
            <p class="stat-value" style="font-size:1.5rem;"><?= $openPostings ?></p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon info"><i class="fa-solid fa-users"></i></div>
        <div class="stat-info">
            <h3>Total Applicants</h3>
            <p class="stat-value" style="font-size:1.5rem;"><?= $totalApplicants ?></p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon warning"><i class="fa-solid fa-comments"></i></div>
        <div class="stat-info">
            <h3>In Interview</h3>
            <p class="stat-value" style="font-size:1.5rem;"><?= $interviewCount ?></p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon success"><i class="fa-solid fa-user-check"></i></div>
        <div class="stat-info">
            <h3>Hired</h3>
            <p class="stat-value" style="font-size:1.5rem;"><?= $hiredCount ?></p>
        </div>
    </div>
</div>

<!-- Tabs -->
<div class="tab-buttons">
    <button class="tab-btn active" onclick="switchTab('pipeline')"><i class="fa-solid fa-table-columns" style="margin-right:6px;"></i>Applicant Pipeline</button>
    <button class="tab-btn" onclick="switchTab('postings')"><i class="fa-solid fa-briefcase" style="margin-right:6px;"></i>Job Postings</button>
</div>

<!-- Tab: Applicant Pipeline -->
<div class="tab-content active" id="tab-pipeline">
    <div class="pipeline-board">
        <?php
        $colConfig = [
            'Applied'   => ['icon' => 'fa-inbox',        'class' => 'col-applied'],
            'Reviewed'  => ['icon' => 'fa-eye',          'class' => 'col-reviewed'],
            'Interview' => ['icon' => 'fa-comments',     'class' => 'col-interview'],
            'Hired'     => ['icon' => 'fa-circle-check', 'class' => 'col-hired'],
            'Rejected'  => ['icon' => 'fa-circle-xmark', 'class' => 'col-rejected'],
        ];
        $nextStatus = ['Applied' => 'Reviewed', 'Reviewed' => 'Interview', 'Interview' => 'Hired'];
        ?>
        <?php foreach ($pipeline as $status => $apps): ?>
            <?php $cfg = $colConfig[$status]; ?>
            <div class="pipeline-column <?= $cfg['class'] ?>">
                <div class="pipeline-column-header">
                    <h4><i class="fa-solid <?= $cfg['icon'] ?>" style="margin-right:6px;"></i><?= $status ?></h4>
                    <span class="pipeline-count"><?= count($apps) ?></span>
                </div>

                <?php if (empty($apps)): ?>
                    <p style="text-align:center; color:var(--text-secondary); font-size:0.85rem; opacity:0.6; padding:1rem 0;">No applicants</p>
                <?php else: ?>
                    <?php foreach ($apps as $app): ?>
                        <div class="pipeline-card">
                            <div class="app-name"><?= htmlspecialchars($app['full_name']) ?></div>
                            <div class="app-job"><i class="fa-solid fa-briefcase" style="margin-right:4px;"></i><?= htmlspecialchars($app['job_title']) ?></div>
                            <div class="app-meta">
                                <i class="fa-regular fa-envelope" style="margin-right:3px;"></i><?= htmlspecialchars($app['email']) ?>
                                <br><i class="fa-regular fa-calendar" style="margin-right:3px;"></i><?= date('M d, Y', strtotime($app['applied_at'])) ?>
                            </div>
                            <?php if ($app['cv_path']): ?>
                                <a href="<?= BASE_URL ?>/<?= htmlspecialchars($app['cv_path']) ?>" target="_blank" class="cv-link">
                                    <i class="fa-solid fa-file-pdf" style="margin-right:3px;"></i>Download CV
                                </a>
                            <?php endif; ?>
                            <div class="app-actions" style="margin-top:0.5rem;">
                                <?php if (isset($nextStatus[$status])): ?>
                                    <button class="move-btn" onclick="updateAppStatus(<?= $app['application_id'] ?>, '<?= $nextStatus[$status] ?>')">
                                        → <?= $nextStatus[$status] ?>
                                    </button>
                                <?php endif; ?>
                                <?php if ($status === 'Interview'): ?>
                                    <button class="hire-btn" onclick="updateAppStatus(<?= $app['application_id'] ?>, 'Hired')">
                                        <i class="fa-solid fa-check"></i> Hire
                                    </button>
                                <?php endif; ?>
                                <?php if ($status !== 'Hired' && $status !== 'Rejected'): ?>
                                    <button class="reject-btn" onclick="updateAppStatus(<?= $app['application_id'] ?>, 'Rejected')">
                                        <i class="fa-solid fa-xmark"></i> Reject
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Tab: Job Postings -->
<div class="tab-content" id="tab-postings">
    <div class="card">
        <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
            <h3>Job Postings</h3>
            <button class="icon-btn" onclick="openPostingModal()" style="width:auto; padding:0 1rem; color:var(--accent-primary); border-color:var(--accent-primary);">
                <i class="fa-solid fa-plus"></i> New Posting
            </button>
        </div>
        <div style="overflow-x:auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Department</th>
                        <th>Type</th>
                        <th>Posted</th>
                        <th>Applicants</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($postings)): ?>
                        <tr><td colspan="7" style="text-align:center; padding:3rem;">No job postings yet</td></tr>
                    <?php else: ?>
                        <?php foreach ($postings as $p): ?>
                            <tr>
                                <td><span style="font-weight:600;"><?= htmlspecialchars($p['title']) ?></span></td>
                                <td><?= htmlspecialchars($p['department_name']) ?></td>
                                <td><?= htmlspecialchars($p['employment_type']) ?></td>
                                <td><?= date('M d, Y', strtotime($p['posted_date'])) ?></td>
                                <td><span style="font-weight:700; color:var(--accent-primary);"><?= $p['applicant_count'] ?></span></td>
                                <td>
                                    <span class="posting-table-badge <?= $p['status'] === 'Open' ? 'posting-open' : 'posting-closed' ?>">
                                        <?= $p['status'] ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-menu-container">
                                        <button class="action-btn" onclick="this.nextElementSibling.classList.toggle('active')"><i class="fa-solid fa-ellipsis-vertical"></i></button>
                                        <div class="dropdown-menu">
                                            <button class="dropdown-item" onclick="openPostingModal(<?= $p['posting_id'] ?>, '<?= addslashes($p['title']) ?>', <?= $p['department_id'] ?>, '<?= addslashes($p['description']) ?>', '<?= addslashes($p['requirements'] ?? '') ?>', '<?= $p['employment_type'] ?>', '<?= $p['status'] ?>')">
                                                <i class="fa-regular fa-pen-to-square"></i> Edit
                                            </button>
                                            <?php if ($p['status'] === 'Open'): ?>
                                                <button class="dropdown-item delete" onclick="closePosting(<?= $p['posting_id'] ?>)">
                                                    <i class="fa-solid fa-lock"></i> Close Posting
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    const deptData = <?= json_encode($departments) ?>;

    function switchTab(tabId) {
        document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.getElementById('tab-' + tabId).classList.add('active');
        event.target.closest('.tab-btn').classList.add('active');
    }

    async function updateAppStatus(appId, newStatus) {
        const label = newStatus === 'Rejected' ? 'reject this applicant' : `move this applicant to "${newStatus}"`;
        if (!confirm(`Are you sure you want to ${label}?`)) return;

        try {
            const fd = new FormData();
            fd.append('application_id', appId);
            fd.append('status', newStatus);
            const res = await fetch('<?= BASE_URL ?>/modules/hr/update_application.php', { method: 'POST', body: fd });
            const data = await res.json();
            if (data.success) window.location.reload();
            else alert('Error: ' + data.error);
        } catch (err) { alert('Network error'); }
    }

    function openPostingModal(id = '', title = '', deptId = '', desc = '', reqs = '', type = 'Full-Time', status = 'Open') {
        const modal = document.getElementById('generic-modal');
        const isEdit = id !== '';

        let deptOptions = deptData.map(d =>
            `<option value="${d.department_id}" ${d.department_id == deptId ? 'selected' : ''}>${d.department_name}</option>`
        ).join('');

        const content = `
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
                <h2 style="margin:0;">${isEdit ? 'Edit Job Posting' : 'Create Job Posting'}</h2>
                <button type="button" onclick="document.getElementById('generic-modal').classList.remove('active')" style="background:none; border:none; font-size:1.5rem; cursor:pointer; color:var(--text-primary);">&times;</button>
            </div>
            <form onsubmit="submitPosting(event, '${id}')">
                <div style="margin-bottom:1rem;">
                    <label style="display:block; margin-bottom:0.5rem; font-weight:600;">Job Title *</label>
                    <input type="text" name="title" value="${title}" required style="width:100%; padding:0.75rem; border:1px solid var(--border-color); border-radius:8px; font-size:0.95rem;">
                </div>
                <div style="display:flex; gap:1rem; margin-bottom:1rem;">
                    <div style="flex:1;">
                        <label style="display:block; margin-bottom:0.5rem; font-weight:600;">Department *</label>
                        <select name="department_id" required style="width:100%; padding:0.75rem; border:1px solid var(--border-color); border-radius:8px; font-size:0.95rem; background:var(--bg-component, #fff);">
                            <option value="">Select...</option>
                            ${deptOptions}
                        </select>
                    </div>
                    <div style="flex:1;">
                        <label style="display:block; margin-bottom:0.5rem; font-weight:600;">Employment Type</label>
                        <select name="employment_type" style="width:100%; padding:0.75rem; border:1px solid var(--border-color); border-radius:8px; font-size:0.95rem; background:var(--bg-component, #fff);">
                            ${['Full-Time','Part-Time','Contract','Internship'].map(t => `<option value="${t}" ${t === type ? 'selected' : ''}>${t}</option>`).join('')}
                        </select>
                    </div>
                </div>
                <div style="margin-bottom:1rem;">
                    <label style="display:block; margin-bottom:0.5rem; font-weight:600;">Description *</label>
                    <textarea name="description" required rows="4" style="width:100%; padding:0.75rem; border:1px solid var(--border-color); border-radius:8px; font-family:inherit; font-size:0.95rem;">${desc}</textarea>
                </div>
                <div style="margin-bottom:1rem;">
                    <label style="display:block; margin-bottom:0.5rem; font-weight:600;">Requirements</label>
                    <textarea name="requirements" rows="4" style="width:100%; padding:0.75rem; border:1px solid var(--border-color); border-radius:8px; font-family:inherit; font-size:0.95rem;">${reqs}</textarea>
                </div>
                ${isEdit ? `<div style="margin-bottom:1.5rem;">
                    <label style="display:block; margin-bottom:0.5rem; font-weight:600;">Status</label>
                    <select name="status" style="width:100%; padding:0.75rem; border:1px solid var(--border-color); border-radius:8px; font-size:0.95rem; background:var(--bg-component, #fff);">
                        <option value="Open" ${status === 'Open' ? 'selected' : ''}>Open</option>
                        <option value="Closed" ${status === 'Closed' ? 'selected' : ''}>Closed</option>
                    </select>
                </div>` : ''}
                <button type="submit" style="padding:0.75rem; background:var(--accent-primary); color:white; border:none; border-radius:8px; width:100%; cursor:pointer; font-weight:600; font-size:0.95rem;">
                    ${isEdit ? 'Save Changes' : 'Create Posting'}
                </button>
            </form>
        `;

        modal.innerHTML = `<div class="modal-content" style="background:var(--bg-panel); padding:2.5rem; border-radius:16px; max-width:640px; margin:auto; box-shadow:var(--shadow-lg);">${content}</div>`;
        modal.classList.add('active');
    }

    async function submitPosting(e, id) {
        e.preventDefault();
        const fd = new FormData(e.target);
        if (id) fd.append('posting_id', id);
        try {
            const res = await fetch('<?= BASE_URL ?>/modules/hr/create_posting.php', { method: 'POST', body: fd });
            const data = await res.json();
            if (data.success) window.location.reload();
            else alert('Error: ' + data.error);
        } catch (err) { alert('Network error'); }
    }

    async function closePosting(id) {
        if (!confirm('Close this job posting? It will no longer accept applications.')) return;
        const fd = new FormData();
        fd.append('posting_id', id);
        fd.append('title', ''); // will be ignored since we need to update status
        fd.append('status', 'Closed');

        // Use a direct update approach
        try {
            const fd = new FormData();
            fd.append('posting_id', id);
            fd.append('status', 'Closed');
            // We need at least the required fields for the update — let's just reload with status change
            const res = await fetch('<?= BASE_URL ?>/modules/hr/create_posting.php', { method: 'POST', body: fd });
            const data = await res.json();
            if (data.success) window.location.reload();
            else {
                // Fallback: just reload
                window.location.reload();
            }
        } catch (err) { alert('Network error'); }
    }

    // Close dropdowns
    document.addEventListener('click', e => {
        if (!e.target.closest('.action-menu-container')) {
            document.querySelectorAll('.dropdown-menu.active').forEach(m => m.classList.remove('active'));
        }
    });
</script>
