window.addEventListener('load', function () {

    let sidebarToggle = document.getElementById('sidebarToggle');
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function () {
            collapseSidebar();
        });
    }

    let sidebarCollapser = document.getElementById('sidebar-collapser');
    if (sidebarCollapser) {
        sidebarCollapser.addEventListener('click', function () {
            collapseSidebar();
        });
    }

    function collapseSidebar() {
        document.querySelector('body').classList.toggle('sidebar-toggled');
        document.querySelector('.sidebar').classList.toggle('toggled');
    }

});
