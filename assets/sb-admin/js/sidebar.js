window.addEventListener('load', function () {

    document.getElementById('sidebarToggle').addEventListener('click', function () {
        collapseSidebar();
    });
    document.getElementById('sidebar-collapser').addEventListener('click', function () {
        collapseSidebar();
    });

    function collapseSidebar() {
        document.querySelector('body').classList.toggle('sidebar-toggled');
        document.querySelector('.sidebar').classList.toggle('toggled');
    }

});
