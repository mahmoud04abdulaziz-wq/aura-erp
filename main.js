document.addEventListener('DOMContentLoaded', () => {

    // --- ELEMENTS ---
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebar-toggle');
    const profilePanel = document.getElementById('profile-panel');
    const profileClose = document.getElementById('profile-close');
    const pageTitle = document.querySelector('.page-title');

    // View Handling
    const navItems = document.querySelectorAll('.sidebar-nav li[data-view]');
    const views = document.querySelectorAll('.view-section');

    // --- SIDEBAR TOGGLE ---
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
        });
    }

    // --- PROFILE TOGGLE LOGIC ---
    window.toggleProfile = () => {
        if (profilePanel) profilePanel.classList.add('active');
    };

    if (profileClose) {
        profileClose.addEventListener('click', () => {
            profilePanel.classList.remove('active');
        });
    }

    // Close profile if clicking outside
    document.addEventListener('click', (e) => {
        if (profilePanel &&
            !profilePanel.contains(e.target) &&
            !e.target.closest('.user-profile-widget') &&
            profilePanel.classList.contains('active')) {
            profilePanel.classList.remove('active');
        }
    });

    // --- GLOBAL BUTTON WIRE-UP ---
    const showGenericModal = (title, message) => {
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

    // Header actions
    const bellBtn = document.querySelector('.fa-bell')?.parentElement;
    const envBtn = document.querySelector('.fa-envelope')?.parentElement;

    if (bellBtn) bellBtn.addEventListener('click', () => showGenericModal('Notifications', 'You have 3 new notifications regarding recent order status changes.'));
    if (envBtn) envBtn.addEventListener('click', () => showGenericModal('Messages', 'You have no new messages at this time.'));

    // Sidebar settings and profile links
    const settingsLink = document.querySelector('.sidebar-footer a');
    if (settingsLink) settingsLink.addEventListener('click', (e) => {
        e.preventDefault();
        showGenericModal('Settings', 'System configuration and preferences will be available in the final release.');
    });

    const profileLinks = document.querySelectorAll('.profile-menu a');
    profileLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            const text = link.textContent;
            if (text === 'Logout') {
                showGenericModal('Logout', 'You have been successfully logged out. (Demo)');
            } else {
                showGenericModal(text, `This section (${text}) is under construction.`);
            }
            if (profilePanel) profilePanel.classList.remove('active');
        });
    });

    // View All Recent Orders
    const viewAllOrdersBtn = document.querySelector('.recent-orders .btn-text');
    if (viewAllOrdersBtn) {
        viewAllOrdersBtn.addEventListener('click', () => {
            const ordersNav = document.querySelector('.sidebar-nav li[data-view="orders"]');
            if (ordersNav) ordersNav.click();
        });
    }

    // --- NAVIGATION LOGIC ---
    navItems.forEach(item => {
        item.addEventListener('click', (e) => {
            e.preventDefault();

            // UI Updates
            navItems.forEach(nav => nav.classList.remove('active'));
            item.classList.add('active');

            // View Switching
            const viewId = item.getAttribute('data-view');
            const targetView = document.getElementById(`view-${viewId}`);

            // Update Title
            const titleText = item.querySelector('span').textContent;
            if (pageTitle) pageTitle.textContent = titleText;

            // Hide all views first
            views.forEach(view => {
                view.style.display = 'none';
                view.classList.remove('active');
            });

            if (targetView) {
                targetView.style.display = 'block';
                // Small timeout to allow display:block to apply before adding class for potential transition
                setTimeout(() => targetView.classList.add('active'), 10);

                // Module Loading
                if (viewId === 'orders') window.renderOrdersModule?.();
                if (viewId === 'hr') window.renderHRModule?.();
                if (viewId === 'procurement') window.renderProcurementModule?.();
                if (viewId === 'production') window.renderProductionModule?.();
                if (viewId === 'inventory') window.renderInventoryModule?.();
                if (viewId === 'finance') window.renderFinanceModule?.();
            } else {
                // If view doesn't exist, show under construction
                console.log(`View ${viewId} not implemented yet.`);
                showGenericModal('Under Construction', `The ${titleText} module is currently under development.`);
            }
        });
    });



    // Scroll Responsive Animation (Keep existing fancy effect)
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

});
