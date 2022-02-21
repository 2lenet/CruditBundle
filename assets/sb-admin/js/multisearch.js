import TomSelect from 'tom-select';

window.addEventListener('DOMContentLoaded', function () {
    // Global search
    const globalSearch = document.getElementById('input_global_search');

    if (globalSearch) {
        const dataUrl = JSON.parse(globalSearch.dataset.url);
        const dataOptions = JSON.parse(globalSearch.dataset.options);

        new TomSelect('#' + globalSearch.id, {
            valueField: 'id',
            labelField: 'text',
            searchField: 'text',
            maxOptions: 2000,
            maxItems: globalSearch.dataset.maxitems,
            preload: false,
            options: dataOptions,
            plugins: [
                'virtual_scroll',
                'remove_button',
                'optgroup_columns',
            ],
            optgroups: dataUrl.map((url) => {
                return {
                    value: url['entity'],
                    label: url['title'],
                };
            }),
            lockOptgroupOrder: true,
            onChange(value) {
                if (value != '') {
                    let shortClass = value.split('#')[0];
                    let id = value.split('#')[1];

                    dataUrl.forEach(url => {
                        if (url['entity'] == shortClass) {
                            window.location.replace(url['destUrl'] + id);
                        }
                    });
                }
            },
            onItemAdd() {
                globalSearch.parentElement.querySelector('.ts-input > input').value = '';
                globalSearch.parentElement.querySelector('.ts-dropdown').style.display = 'none';
            },
            firstUrl(query) {
                return dataUrl + encodeURIComponent(query) + '&limit=20';
            },
            load(query, callback) {
                let datas = [];

                let fetchsFinished = 0;

                dataUrl.forEach(url => {
                    let fetchUrl = '';

                    if (Object.keys(url)[0] == 'url') {
                        fetchUrl = url['url'] + '?q=' + encodeURIComponent(query) + '&limit=' + (url['limit'] || '10') + '&offset=';
                    } else {
                        fetchUrl = '/' + url['entity'] + '/autocomplete?q=' + encodeURIComponent(query) + '&limit=' + (url['limit'] || '10') + '&offset=';
                    }

                    fetch(fetchUrl)
                        .then(response => response.json())
                        .then(json => {
                            fetchsFinished++;

                            for (let item of json.items) {
                                item.id = url['entity'] + '#' + item.id;
                                item.optgroup = url['entity'];
                            }

                            datas.push(...json.items);

                            // show 'no results' ONLY if last ajax call returns no results
                            if (datas.length > 0 || fetchsFinished === dataUrl.length) {
                                callback(datas);
                            }
                        })
                        .catch((e) => {
                            console.log('error', e);
                            callback();
                        });
                });
            },
            render: {
                loading_more() {
                    return '<div class="loading-more-results py-2 d-flex align-items-center"><div class="spinner"></div> Chargement en cours</div>';
                },
                no_more_results() {
                    return '';
                },
            }
        });
    }
});