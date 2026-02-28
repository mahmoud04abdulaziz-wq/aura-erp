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
                if (viewId === 'orders') renderOrdersModule();
                if (viewId === 'hr') renderHRModule();
                if (viewId === 'procurement') renderProcurementModule();
            } else {
                // If view doesn't exist (e.g. Inventory), show a placeholder or nothing
                // For now we just stay blank or we could show an 'Under Construction' toast
                console.log(`View ${viewId} not implemented yet.`);
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
