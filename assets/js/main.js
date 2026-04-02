/**
 * AURA ERP — Main JavaScript
 * Handles sidebar toggle, profile panel, modal, and UI interactions.
 */
document.addEventListener('DOMContentLoaded', () => {

    // --- ELEMENTS ---
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebar-toggle');
    const sidebarClose = document.getElementById('sidebar-close');
    const profilePanel = document.getElementById('profile-panel');
    const profileClose = document.getElementById('profile-close');

    // --- SIDEBAR TOGGLE ---
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
        });
    }
    if (sidebarClose) {
        sidebarClose.addEventListener('click', () => {
            sidebar.classList.remove('collapsed');
            sidebar.classList.remove('active');
        });
    }

    // --- PROFILE TOGGLE LOGIC ---
    window.toggleProfile = () => {
        if (profilePanel) profilePanel.classList.toggle('active');
    };

    if (profileClose) {
        profileClose.addEventListener('click', () => {
            profilePanel.classList.remove('active');
        });
    }

    // Close profile or modals if clicking outside
    document.addEventListener('click', (e) => {
        // Profile Panel
        if (profilePanel &&
            !profilePanel.contains(e.target) &&
            !e.target.closest('.user-profile-widget') &&
            profilePanel.classList.contains('active')) {
            profilePanel.classList.remove('active');
        }

        // Generic Modals (Clicking the blurry background overlay)
        if (e.target && e.target.id === 'generic-modal') {
            e.target.classList.remove('active');
        }

        // Notification Dropdown (Close if clicking outside)
        const notifDropdown = document.getElementById('notification-dropdown');
        if (notifDropdown && 
            !notifDropdown.contains(e.target) && 
            !e.target.closest('.notification-wrapper') &&
            notifDropdown.classList.contains('active')) {
            notifDropdown.classList.remove('active');
        }
    });

    // --- NOTIFICATION TOGGLE ---
    window.toggleNotifications = (e) => {
        if (e) e.stopPropagation();
        const dropdown = document.getElementById('notification-dropdown');
        if (dropdown) dropdown.classList.toggle('active');
        
        // Close profile panel if open
        const profilePanel = document.getElementById('profile-panel');
        if (profilePanel) profilePanel.classList.remove('active');
    };

    // --- CUSTOM UI COMPONENTS ---
    window.initCustomSelects = (container = document) => {
        const wrappers = container.querySelectorAll('.custom-select-wrapper');
        
        wrappers.forEach(wrapper => {
            const select = wrapper.querySelector('.custom-select');
            const options = wrapper.querySelectorAll('.custom-option');
            const hiddenInput = wrapper.querySelector('input[type="hidden"]');
            const textSpan = wrapper.querySelector('.selected-text');
            
            if(wrapper.dataset.initialized) return;
            wrapper.dataset.initialized = 'true';
            
            select.addEventListener('click', (e) => {
                e.stopPropagation();
                const wasOpen = wrapper.classList.contains('open');
                document.querySelectorAll('.custom-select-wrapper.open').forEach(w => w.classList.remove('open'));
                if (!wasOpen) wrapper.classList.add('open');
            });
            
            options.forEach(opt => {
                opt.addEventListener('click', (e) => {
                    e.stopPropagation();
                    if (opt.dataset.value === "") return;
                    
                    hiddenInput.value = opt.dataset.value;
                    textSpan.textContent = opt.textContent;
                    textSpan.style.opacity = '1';
                    
                    options.forEach(o => o.classList.remove('selected'));
                    opt.classList.add('selected');
                    wrapper.classList.remove('open');
                });
            });
        });
        
        if(!window._customSelectDocClickInit) {
            document.addEventListener('click', () => {
                document.querySelectorAll('.custom-select-wrapper.open').forEach(w => w.classList.remove('open'));
            });
            window._customSelectDocClickInit = true;
        }
    };

    // --- GENERIC MODAL ---
    window.showGenericModal = (title, message) => {
        const modal = document.getElementById('generic-modal');
        const content = document.getElementById('modal-content-body');
        if (modal && content) {
            content.innerHTML = `
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
                    <h2 style="margin:0;">${title}</h2>
                    <button onclick="document.getElementById('generic-modal').classList.remove('active')" style="background:none; border:none; font-size:1.5rem; cursor:pointer;">&times;</button>
                </div>
                <div>
                    <p style="color:var(--text-secondary); line-height:1.6;">${message}</p>
                </div>
                <div style="margin-top:2rem; display:flex; justify-content:flex-end;">
                    <button onclick="document.getElementById('generic-modal').classList.remove('active')" style="padding:0.75rem 1.5rem; background:var(--accent-primary); color:white; border:none; border-radius:8px; cursor:pointer;">Close</button>
                </div>
            `;
            modal.classList.add('active');
        }
    };

    // --- HEADER ACTIONS ---
    const envBtn = document.querySelector('.fa-envelope')?.parentElement;

    if (envBtn) envBtn.addEventListener('click', () => showGenericModal('Messages', 'You have no new messages.'));

    // --- SCROLL ANIMATION ---
    const shapes = document.querySelectorAll('.shape');
    window.addEventListener('scroll', () => {
        const scrolled = window.scrollY;
        shapes.forEach((shape, index) => {
            const speed = (index + 1) * 0.15;
            const yPos = -(scrolled * speed);
            const rotate = scrolled * 0.1;
            shape.style.transform = `translateY(${yPos}px) rotate(${rotate}deg)`;
        });
    });

    // --- ADMIN OVERHAUL ---
    window.switchTab = (tabId) => {
        // Toggle tab links
        document.querySelectorAll('.tab-link').forEach(link => {
            const isActive = link.getAttribute('onclick')?.includes(tabId);
            link.classList.toggle('active', isActive);
        });
        // Toggle tab content
        document.querySelectorAll('.admin-tab').forEach(tab => {
            tab.classList.toggle('active', tab.id === tabId);
        });
    };

    window.savePermissions = async (event) => {
        if (event) event.preventDefault();
        const form = document.getElementById('permissions-form');
        if (!form) return;

        const btn = document.querySelector('button[onclick^="savePermissions"]');
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving...';
        btn.disabled = true;

        try {
            const formData = new FormData(form);
            const res = await fetch('modules/admin/update_permissions.php', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();
            
            if (data.success) {
                alert('Permissions updated successfully!');
                window.location.reload();
            } else {
                alert('Error: ' + data.error);
            }
        } catch (err) {
            console.error(err);
            alert('Network error while saving permissions.');
        } finally {
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    };
});
