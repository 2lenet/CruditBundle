/*
 * Autocomplete and lists of the forms (Tom Select), until the Stimulus controller of the form types (lot 3.2.c).
 *
 * This file was the script of the filters of the list too: the operator menu, the Enter key and the counter of
 * selected items are gone, the filters are the Filter:Bar of CruditUiBundle (controllers `filters`, `filter-field`,
 * `filter-period`). The name of the file stays, projects import it.
 */
import TomSelect from 'tom-select';

export function initTomSelect() {
    document.querySelectorAll('input.entity-select:not(.tomselected)').forEach(select => {
        const dataurl = select.dataset.url;
        const inioptions = JSON.parse(select.dataset.options);
        let keepDropdownClosedOnLoad = false;

        const ts = new TomSelect('#' + select.id,
            {
                valueField: 'id',
                labelField: 'text',
                searchField: 'text',
                maxOptions: 2000,
                maxItems: parseInt(select.dataset.maxitems),
                preload: true,
                options: inioptions,
                plugins: [
                    'virtual_scroll',
                    'remove_button',
                ],
                onChange(value) {
                    let items = [];
                    if (value != '') {
                        value.split(',').forEach(v => {
                            items.push({ id: v, text: this.options[v].text });
                        });
                    }
                    document.getElementById(select.id + '_items').value = JSON.stringify(items);
                },
                onItemAdd() {
                    select.parentElement.querySelector('.ts-control > input').value = '';
                    select.parentElement.querySelector('.ts-dropdown').style.display = 'none';
                },
                onItemRemove() {
                    keepDropdownClosedOnLoad = true;
                    this.clearPagination();
                    this.load('');
                },
                firstUrl(query) {
                    let params = new URLSearchParams({
                        q: encodeURIComponent(query),
                        limit: 20,
                    });

                    return dataurl + '?' + params.toString();
                },
                load(query, callback) {
                    let url = this.getUrl(query);

                    fetch(url)
                        .then(response => response.json())
                        .then(json => {
                            // Fix Virtual Scroll Plugin scrolls to top when next url loads
                            const _scrollToOption = this.scrollToOption;
                            this.scrollToOption = () => {};

                            if (json.next_offset < json.total_count) {
                                let params = new URLSearchParams({
                                    q: encodeURIComponent(query),
                                    limit: 20,
                                    offset: json.next_offset,
                                });
                                let nextUrl = dataurl + '?' + params.toString();

                                this.setNextUrl(query, nextUrl);
                            }

                            if (keepDropdownClosedOnLoad) {
                                // Prevent refreshOptions() from auto-opening the dropdown
                                // when this load() was triggered by onItemRemove's reset.
                                const wasFocused = this.isFocused;
                                this.isFocused = false;
                                callback(json.items);
                                //restore isFocused's real value so as not to disrupt subsequent normal behavior
                                this.isFocused = wasFocused;
                                keepDropdownClosedOnLoad = false;
                            } else {
                                // add data to the results
                                callback(json.items);
                            }

                            // Fix Virtual Scroll Plugin scrolls to top when next url loads
                            this.scrollToOption = _scrollToOption;
                        }).catch((e) => {
                        console.log('error', e);
                        callback();
                    });
                },
                render: {
                    loading_more() {
                        return `<div class="loading-more-results py-2 d-flex align-items-center"><div class="spinner"></div> Chargement en cours</div>`;
                    },
                    item: function (data, escape) {
                        return '<div><div class="item-text">' + escape(data.text) + '</div></div>';
                    },
                    no_more_results() {
                        return '';
                    },
                },
            },
        );
    });
    // Normal select
    document.querySelectorAll('input.tom-select:not(.tomselected)').forEach(select => {
        const settings = {
            maxItems: parseInt(select.dataset.maxitems),
            plugins: [
                'remove_button',
            ],
            valueField: 'id',
            labelField: 'text',
            searchField: 'text',
            onChange(value) {
                let items = [];
                let values;
                if (value != '') {

                    if (Array.isArray(value)) {
                        values = value;
                    } else {
                        values = value.split(',');
                    }

                    values.forEach(v => {
                        items.push({ id: v, text: this.options[v].text });
                    });
                }
                document.getElementById(select.id + '_items').value = JSON.stringify(items);
            },
            onItemAdd() {
                select.parentElement.querySelector('.ts-control > input').value = '';
                select.parentElement.querySelector('.ts-dropdown').style.display = 'none';
            },
            render: {
                item: function (data, escape) {
                    return '<div><div class="item-text">' + escape(data.text) + '</div></div>';
                },
            },
        };

        if (select.dataset.options !== undefined) {
            settings.options = JSON.parse(select.dataset.options);
        }

        const ts = new TomSelect('#' + select.id, settings);
    });
}

window.addEventListener('DOMContentLoaded', function () {
    initTomSelect();
});
