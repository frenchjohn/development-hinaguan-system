document.addEventListener('DOMContentLoaded', () => {
    const layout = document.querySelector('.dash-layout');
    const sidebarToggle = document.querySelector('[data-dash-sidebar-toggle]');
    const overlay = document.querySelector('.dash-sidebar__overlay');

    const closeSidebar = () => layout?.classList.remove('sidebar-open');
    const openSidebar = () => layout?.classList.add('sidebar-open');

    sidebarToggle?.addEventListener('click', () => {
        if (layout?.classList.contains('sidebar-open')) {
            closeSidebar();
        } else {
            openSidebar();
        }
    });

    overlay?.addEventListener('click', closeSidebar);

    window.addEventListener('resize', () => {
        if (window.innerWidth > 992) {
            closeSidebar();
        }
    });
});
