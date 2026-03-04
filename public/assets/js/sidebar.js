// public/assets/js/sidebar.js

/**
 * Handles the Desktop (PC) Sidebar Collapse/Expand
 */
function togglePCSidebar() {
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('mainContent');
    const logoExpanded = document.getElementById('logo-expanded');
    const logoCollapsed = document.getElementById('logo-collapsed');
    const toggleBtn = document.getElementById('pc-toggle-btn');

    if (!sidebar) return;

    // Toggle Width Classes
    const isCollapsed = sidebar.classList.toggle('w-20');
    sidebar.classList.toggle('w-72');
    sidebar.classList.toggle('collapsed');

    // Handle Main Content Margin
    if (mainContent) {
        mainContent.classList.toggle('lg:ml-72');
        mainContent.classList.toggle('lg:ml-20');
    }

    // Toggle Logos
    if (logoExpanded && logoCollapsed) {
        if (isCollapsed) {
            logoExpanded.classList.add('hidden');
            logoCollapsed.classList.remove('hidden');
        } else {
            logoExpanded.classList.remove('hidden');
            logoCollapsed.classList.add('hidden');
        }
    }

    // Rotate Chevron
    if (toggleBtn) {
        const icon = toggleBtn.querySelector('.material-symbols-rounded');
        if (icon) {
            icon.style.transform = isCollapsed ? 'rotate(180deg)' : 'rotate(0deg)';
        }
    }

    // Save state for persistence
    localStorage.setItem('bigfun-sidebar-state', isCollapsed);
}

/**
 * Handles the Mobile Hamburger Slide-in/out
 */
function toggleMobileSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');

    if (!sidebar) return;

    // CRITICAL FIX: Ensure mobile sidebar is NEVER skinny/collapsed before sliding
    sidebar.classList.remove('w-20', 'collapsed');
    sidebar.classList.add('w-72');

    const isHidden = sidebar.classList.contains('-translate-x-full');

    if (isHidden) {
        // OPEN SIDEBAR
        sidebar.classList.remove('-translate-x-full');
        sidebar.classList.add('translate-x-0');
        if (overlay) {
            overlay.classList.remove('hidden');
            setTimeout(() => overlay.classList.remove('opacity-0'), 10);
        }
    } else {
        // CLOSE SIDEBAR
        sidebar.classList.remove('translate-x-0');
        sidebar.classList.add('-translate-x-full');
        if (overlay) {
            overlay.classList.add('opacity-0');
            setTimeout(() => overlay.classList.add('hidden'), 300);
        }
    }
}

/**
 * Initialize Sidebar on Page Load and handle Window Resizing
 */
document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('mainContent');
    const logoExpanded = document.getElementById('logo-expanded');
    const logoCollapsed = document.getElementById('logo-collapsed');
    const toggleBtn = document.getElementById('pc-toggle-btn');

    if (!sidebar) return;

    function applyScreenState() {
        if (window.innerWidth >= 1024) {
            // DESKTOP: Load saved state
            const savedState = localStorage.getItem('bigfun-sidebar-state') === 'true';
            sidebar.classList.remove('-translate-x-full', 'translate-x-0'); // Remove mobile transforms

            if (savedState) {
                sidebar.classList.add('w-20', 'collapsed');
                sidebar.classList.remove('w-72');
                if (mainContent) {
                    mainContent.classList.add('lg:ml-20');
                    mainContent.classList.remove('lg:ml-72');
                }
                if (logoExpanded) logoExpanded.classList.add('hidden');
                if (logoCollapsed) logoCollapsed.classList.remove('hidden');
                if (toggleBtn) {
                    const icon = toggleBtn.querySelector('.material-symbols-rounded');
                    if (icon) icon.style.transform = 'rotate(180deg)';
                }
            } else {
                sidebar.classList.add('w-72');
                sidebar.classList.remove('w-20', 'collapsed');
                if (mainContent) {
                    mainContent.classList.add('lg:ml-72');
                    mainContent.classList.remove('lg:ml-20');
                }
            }
        } else {
            // MOBILE: Force hidden & Full Width (w-72)
            sidebar.classList.remove('translate-x-0', 'w-20', 'collapsed');
            sidebar.classList.add('-translate-x-full', 'w-72');

            // Force logos to normal mode
            if (logoExpanded) logoExpanded.classList.remove('hidden');
            if (logoCollapsed) logoCollapsed.classList.add('hidden');
        }
    }

    // 1. Run on load
    // Disable transitions temporarily to prevent visual "flicker"
    sidebar.classList.add('no-transition');
    applyScreenState();
    setTimeout(() => sidebar.classList.remove('no-transition'), 100);

    // 2. Run on resize (in case user turns tablet sideways)
    window.addEventListener('resize', () => {
        applyScreenState();

        // Hide overlay safely on resize
        const overlay = document.getElementById('sidebarOverlay');
        if (overlay && window.innerWidth >= 1024) {
            overlay.classList.add('opacity-0', 'hidden');
        }
    });
});