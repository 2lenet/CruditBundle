import "bootstrap";

import "./editinplace";
import "./batch_actions";
import './input_date';
import "./input_format";
import "./markdown_textarea";
import './filters';
import './form';
import './multisearch';
import './scroll-to-top';
import './sidebar';

window.addEventListener('load', function () {
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
