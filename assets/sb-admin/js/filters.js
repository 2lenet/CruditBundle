import TomSelect from 'tom-select';

window.addEventListener('DOMContentLoaded', function () {

    // Change the operator of the filter
    document.querySelectorAll('.valuesetter').forEach(choice => {
        choice.addEventListener('click', (e) => {
            const hidden = document.getElementById(e.target.dataset.valueid);
            hidden.value = e.target.dataset.value;

            // Set into the button the icon selected in the dropdown
            const icon =  document.getElementById(e.target.dataset.valueid + '_icon');
            icon.classList = e.target.querySelector('i').classList;
        })
    });


    // Submit form on press on Enter and prevent operators dropdown opening
    const filtersContainer = document.getElementById('collapse-filters');
    if (filtersContainer) {
        filtersContainer.addEventListener('keydown', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                filtersContainer.closest('form').submit();
            }
        });
    }

    document.querySelectorAll('.entity-select').forEach(select => {
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
                    'virtual_scroll',
                    'remove_button'
                ],
                onChange(value) {
                    let items = [];
                    if (value != '') {
                        value.split(',').forEach(v => {
                            items.push({id: v, text: this.options[v].text});
                        });
                    }
                    document.getElementById(select.id + '_items').value = JSON.stringify(items);
                },
                onItemAdd() {
                    select.parentElement.querySelector('.ts-input > input').value = '';
                    select.parentElement.querySelector('.ts-dropdown').style.display = 'none';
                },
                firstUrl(query) {
                    let params = new URLSearchParams({
                        q: encodeURIComponent(query),
                        limit: 20,
                    });

                    return dataurl + "?" + params.toString();
                },
                load(query, callback) {
                    let url = this.getUrl(query);

                    fetch(url)
                        .then(response => response.json())
                        .then(json => {
                            if (json.next_offset < json.total_count) {
                                let params = new URLSearchParams({
                                    q: encodeURIComponent(query),
                                    limit: 20,
                                    offset: json.next_offset,
                                });
                                let nextUrl = dataurl + "?" + params.toString();

                                this.setNextUrl(query, nextUrl);
                            }
                            // add data to the results
                            callback(json.items);
                        }).catch((e) => {
                        console.log('error', e);
                        callback();
                    });
                },
                render: {
                    loading_more() {
                        return `<div class="loading-more-results py-2 d-flex align-items-center"><div class="spinner"></div> Chargement en cours</div>`;
                    },
                    no_more_results() {
                        return '';
                    }
                }
            }
        );
    });

    // Normal select
    document.querySelectorAll(".tom-select").forEach(select => {
        new TomSelect("#" + select.id,
            {
                maxItems: select.dataset.maxitems,
                plugins: [
                    "remove_button"
                ],
                onChange(value) {
                    let items = [];
                    if (value != '') {
                        value.split(',').forEach(v => {
                            items.push({id: v, text: this.options[v].text});
                        });
                    }
                    document.getElementById(select.id + "_items").value = JSON.stringify(items);
                },
                onItemAdd() {
                    select.parentElement.querySelector('.ts-input > input').value = "";
                    select.parentElement.querySelector('.ts-dropdown').style.display = "none";
                },
            }
        );
    });

});
