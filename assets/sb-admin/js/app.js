import "./editinplace";
import "./batch_actions";
import './input_date';
import "./input_format";
import "./markdown_textarea";
import './filters';
import './form';
import './multisearch';
import * as bootstrap from "bootstrap";

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
    let popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'))
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl)
    })
    let tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    })
});
