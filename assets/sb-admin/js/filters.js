import TomSelect from 'tom-select';

function setupOverflowCounter(tomselect, originalInput) {
    const formFloating = originalInput.closest('.form-floating');
    if (!formFloating) {
        return;
    }
    const prepend = formFloating.previousElementSibling;
    if (!prepend || !prepend.classList.contains('input-group-prepend')) {
        return;
    }

    const wrapper = tomselect.wrapper;
    const control = wrapper.querySelector('.ts-control');
    if (!control) {
        return;
    }

    const counter = document.createElement('button');
    counter.type = 'button';
    counter.className = 'ts-overflow-counter';
    counter.hidden = true;
    counter.setAttribute('aria-label', 'Voir les éléments sélectionnés');

    const popover = document.createElement('div');
    popover.className = 'ts-overflow-popover';
    popover.hidden = true;
    formFloating.appendChild(popover);

    const placeCounter = () => {
        const searchInput = control.querySelector(':scope > input');
        if (searchInput) {
            control.insertBefore(counter, searchInput);
        } else {
            control.appendChild(counter);
        }
    };
    placeCounter();

    const renderPopover = () => {
        popover.innerHTML = '';
        tomselect.items.forEach((id) => {
            const option = tomselect.options[id];
            if (!option) {
                return;
            }
            const row = document.createElement('div');
            row.className = 'ts-overflow-row';

            const text = document.createElement('span');
            text.className = 'ts-overflow-text';
            text.textContent = option[tomselect.settings.labelField] || option.text || id;

            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'ts-overflow-remove';
            remove.setAttribute('aria-label', 'Retirer');
            remove.textContent = '×';
            remove.addEventListener('click', (e) => {
                e.stopPropagation();
                tomselect.removeItem(id);
            });

            row.appendChild(text);
            row.appendChild(remove);
            popover.appendChild(row);
        });
    };

    const update = () => {
        // Re-place counter in case TomSelect reordered children
        placeCounter();

        const overflow = tomselect.items.length - 1;
        if (overflow > 0) {
            counter.textContent = '+' + overflow;
            counter.hidden = false;
        } else {
            counter.hidden = true;
            popover.hidden = true;
        }

        if (!popover.hidden) {
            renderPopover();
        }
    };

    counter.addEventListener('mousedown', (e) => {
        // Stop TomSelect from grabbing focus and opening its own dropdown
        e.preventDefault();
        e.stopPropagation();
    });
    counter.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        if (popover.hidden) {
            renderPopover();
            popover.hidden = false;
        } else {
            popover.hidden = true;
        }
    });

    document.addEventListener('click', (e) => {
        if (popover.hidden) {
            return;
        }
        if (!popover.contains(e.target) && e.target !== counter) {
            popover.hidden = true;
        }
    });

    tomselect.on('item_add', update);
    tomselect.on('item_remove', update);

    update();
}

export function initTomSelect() {
    document.querySelectorAll('input.entity-select:not(.tomselected)').forEach(select => {
        const dataurl = select.dataset.url;
        const inioptions = JSON.parse(select.dataset.options);

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
                    item: function (data, escape) {
                        return '<div><div class="item-text">' + escape(data.text) + '</div></div>';
                    },
                    no_more_results() {
                        return '';
                    },
                },
            },
        );

        setupOverflowCounter(ts, select);
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
        setupOverflowCounter(ts, select);
    });
}

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

    initTomSelect();
});
