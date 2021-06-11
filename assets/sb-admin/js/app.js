import "bootstrap";
import TomSelect from "tom-select/dist/js/tom-select.complete";
import "./map";

window.addEventListener('load', function () {

    document.querySelectorAll(".valuesetter").forEach(choice => {
        choice.addEventListener("click", (e) => {
            console.log(e.target.dataset);
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

    // Entity filter
    document.querySelectorAll(".entity-select").forEach(select => {
        const dataurl = select.dataset.url;
        const inioptions = JSON.parse(select.dataset.options);
        new TomSelect('#' + select.id,
            {
                valueField: 'id',
                labelField: 'text',
                searchField: 'text',
                maxItems: select.dataset.maxitems,
                preload: true,
                options: inioptions,
                plugins: [
                        'checkbox_options',
                        'remove_button'
                    ],
                onChange: function (value) {
                    let items = [];
                    value.split(',').forEach(v=>{
                        items.push({id: v, text: this.options[v].text});
                    })
                    document.getElementById(select.id+"_items").value=JSON.stringify(items);
                },
                load: function (query, callback) {
                    let url = dataurl + encodeURIComponent(query);
                    fetch(url)
                        .then(response => response.json())
                        .then(json => {
                            callback(json.items);
                            }).catch(() => {
                                callback();
                            });
                }
            }
        );
    });
});
