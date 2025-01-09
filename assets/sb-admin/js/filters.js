import TomSelect from 'tom-select';

window.addEventListener('DOMContentLoaded', function () {

    // Change the operator of the filter
    document.querySelectorAll('.valuesetter').forEach(choice => {
        choice.addEventListener('click', (e) => {
            const hidden = document.getElementById(e.target.dataset.valueid);
            hidden.value = e.target.dataset.value;

            // Set into the button the icon selected in the dropdown
            const icon = document.getElementById(e.target.dataset.valueid + '_icon');
            icon.classList = e.target.querySelector('i').classList;
        });
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

                            // add data to the results
                            callback(json.items);

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
                    no_more_results() {
                        return '';
                    },
                },
            },
        );
    });

    // Normal select
    document.querySelectorAll('input.tom-select').forEach(select => {
        const settings = {
            maxItems: select.dataset.maxitems,
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
        };

        if (select.dataset.options !== undefined) {
            settings.options = JSON.parse(select.dataset.options);
        }

        new TomSelect('#' + select.id, settings);
    });
});
