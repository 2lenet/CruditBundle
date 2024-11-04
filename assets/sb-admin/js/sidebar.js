import {mobile} from './variables_responsive';

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
        document.querySelector('.sidebar__container').classList.toggle('toggled');
        if (
            window.innerWidth > mobile
            && document.querySelector('.sidebar__container').classList.contains('toggled')
        ) {
            document.cookie = 'sidebarToggled=1; path=/';
        } else {
            document.cookie = 'sidebarToggled=0; path=/';
        }
    }

});
