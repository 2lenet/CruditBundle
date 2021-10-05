import "bootstrap";

import "./map";
import "./editinplace";
import "./batch_actions";
import "./input_format";
import './filters';
import './form';

window.addEventListener('load', function () {

    // sidebar Toggle
    document.getElementById("sidebarToggle").addEventListener('click', function () {
        document.querySelector("body").classList.toggle("sidebar-toggled");
        document.querySelector(".sidebar").classList.toggle("toggled");
    });

    // tabs select from anchor
    var hash = window.location.hash;
    var triggerEl = document.querySelector('ul.nav a[href="' + hash + '"]')
    if (triggerEl) {
        triggerEl.click();
    }

    // update anchor on click
    var triggerTabList = [].slice.call(document.querySelectorAll('ul.nav-tabs a'));
    triggerTabList.forEach(function (tabEl) {
        tabEl.addEventListener('click', function () {
            window.location.hash = tabEl.attributes.href.value;
        });
    });
});
