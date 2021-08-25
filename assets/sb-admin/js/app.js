import "bootstrap";
import TomSelect from "tom-select/dist/js/tom-select.complete";
//import { Tab } from 'bootstrap'

import "./map";
import "./editinplace";
import "./batch_actions";
import "./input_format";

window.addEventListener('load', function () {

    document.querySelectorAll(".valuesetter").forEach(choice => {
        choice.addEventListener("click", (e) => {
            const hidden = document.getElementById(e.target.dataset.valueid);
            hidden.value = e.target.dataset.value;
            /*
                TODO: SET the right icon when selected ( maybe use real svg ? )
                const button_icon =  document.getElementById(e.target.dataset.valueid+"_icon");
                console.log(button_icon);
                button_icon.classList = e.target.querySelector("i").classList;
            */
        })
    });

    // sidebar Toggle
    document.getElementById("sidebarToggle").addEventListener('click', function () {
        document.querySelector("body").classList.toggle("sidebar-toggled");
        document.querySelector(".sidebar").classList.toggle("toggled");
        /*if ($(".sidebar").hasClass("toggled")) {
            $('.sidebar .collapse').collapse('hide');
        }*/
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

    // Entity filter
    document.querySelectorAll(".entity-select").forEach(select => {
        const dataurl = select.dataset.url;
        const inioptions = JSON.parse(select.dataset.options);
        new TomSelect('#' + select.id,
            {
                valueField: 'id',
                labelField: 'text',
                searchField: 'text',
                maxOptions: 2000,
                maxItems: select.dataset.maxitems,
                preload: true,
                options: inioptions,
                plugins: [
                        //'checkbox_options',
                        'virtual_scroll',
                        'remove_button'
                    ],
                onChange: function (value) {
                    let items = [];
                    if (value!='') {
                        value.split(',').forEach(v => {
                            items.push({id: v, text: this.options[v].text});
                        })
                    }
                    document.getElementById(select.id+"_items").value=JSON.stringify(items);
                },
                firstUrl: function(query){
                    return dataurl + encodeURIComponent(query)+"&limit=20";
                },
                load: function (query, callback) {
                    let url = this.getUrl(query);
                    fetch(url)
                        .then(response => response.json())
                        .then(json => {
                            if (json.next_offset< json.total_count) {
                                const next_url = dataurl + encodeURIComponent(query) + "&limit=20&offset=" + json.next_offset;
                                this.setNextUrl(query, next_url);
                            }
                            // add data to the results
                            callback(json.items);
                            }).catch((e) => {
                                console.log("error", e)
                                callback();
                            });
                },
                render: {
                    loading_more: function() {
                        return `<div class="loading-more-results py-2 d-flex align-items-center"><div class="spinner"></div> Chargement en cours </div>`;
                    },
                    no_more_results: function () {
                        return "";
                    }
                },
            }
        );
    });
});
