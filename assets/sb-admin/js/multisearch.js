import TomSelect from 'tom-select';

window.addEventListener('DOMContentLoaded', function () {
    // Global search
    const globalSearch = document.getElementById('input_global_search');

    if (globalSearch) {
        const dataUrl = JSON.parse(globalSearch.dataset.url);
        const dataOptions = JSON.parse(globalSearch.dataset.options);

        // To know if the request has changed
        let registeredQuery = null;

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
                let urls = {};

                dataUrl.forEach(url => {
                    let entity = url['entity'];
                    let params = new URLSearchParams({
                        q: query,
                        limit: url['limit'],
                        offset: 0,
                    });

                    if (Object.keys(url)[0] == 'url') {
                        urls[entity] = url['url'] + '?' + params.toString();
                    } else {
                        urls[entity] = '/' + url['entity'] + '/autocomplete?' + params.toString();
                    }
                });

                return urls;
            },
            load(query, callback) {
                let urls = this.getUrl(query);
                let fetchsFinished = 0;
                let datas = [];

                // If the request has not changed, we save all previous data
                if (query === registeredQuery) {
                    datas = Object.values(this.options);
                } else {
                    datas = [];
                    registeredQuery = query;
                }

                for (let entity in urls) {
                    let url = urls[entity];

                    fetch(url)
                        .then(response => response.json())
                        .then(json => {
                            fetchsFinished++;

                            for (let item of json.items) {
                                item.id = entity + '#' + item.id;
                                item.optgroup = entity;
                            }

                            datas.push(...json.items);

                            // show 'no results' ONLY if last ajax call returns no results
                            if (datas.length > 0 || fetchsFinished === dataUrl.length) {
                                callback(datas);
                            }
                        })
                        .catch((error) => {
                            console.log('error', error);
                            callback();
                        });
                }
            },
            render: {
                loading_more() {
                    return '';
                },
                no_more_results() {
                    return '';
                },
            },
        });
    }
});