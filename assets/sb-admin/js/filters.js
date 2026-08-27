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

    const MIN_CHIP_WIDTH = 56;

    const update = () => {
        placeCounter();

        const items = Array.from(control.querySelectorAll(':scope > .item'));
        if (items.length === 0) {
            counter.hidden = true;
            popover.hidden = true;
            return;
        }

        items.forEach((el) => {
            el.style.display = '';
            el.style.flexShrink = '0';
            el.style.maxWidth = '';
        });

        const cs = window.getComputedStyle(control);
        const paddingRight = parseFloat(cs.paddingRight) || 0;
        const gap = parseFloat(cs.columnGap) || parseFloat(cs.gap) || 4;
        const availableRight = control.getBoundingClientRect().right - paddingRight;

        if (items[items.length - 1].getBoundingClientRect().right <= availableRight) {
            counter.hidden = true;
            popover.hidden = true;
            return;
        }

        counter.hidden = false;
        counter.textContent = '+' + items.length;
        const counterWidth = counter.offsetWidth;
        const limit = availableRight - counterWidth - gap;

        let shownCount = 0;
        while (shownCount < items.length && items[shownCount].getBoundingClientRect().right <= limit) {
            shownCount++;
        }

        if (shownCount < items.length) {
            const boundary = items[shownCount];
            const prevRight = shownCount > 0
                ? items[shownCount - 1].getBoundingClientRect().right + gap
                : boundary.getBoundingClientRect().left;
            const remaining = limit - prevRight;

            if (remaining >= MIN_CHIP_WIDTH) {
                boundary.style.maxWidth = remaining + 'px';
                shownCount++;
            }
        }

        const hiddenCount = items.length - shownCount;

        if (hiddenCount === 0) {
            counter.hidden = true;
            popover.hidden = true;
            const lastEl = items[items.length - 1];
            const prevRight = items.length > 1
                ? items[items.length - 2].getBoundingClientRect().right + gap
                : lastEl.getBoundingClientRect().left;
            lastEl.style.maxWidth = (availableRight - prevRight) + 'px';
        } else {
            for (let idx = shownCount; idx < items.length; idx++) {
                items[idx].style.display = 'none';
            }
            counter.textContent = '+' + hiddenCount;
        }

        if (!popover.hidden) {
            renderPopover();
        }
    };

    let scheduled = false;
    const scheduleUpdate = () => {
        if (scheduled) {
            return;
        }
        scheduled = true;
        window.requestAnimationFrame(() => {
            scheduled = false;
            update();
        });
    };

    counter.addEventListener('mousedown', (e) => {
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

    tomselect.on('item_add', scheduleUpdate);
    tomselect.on('item_remove', scheduleUpdate);
    tomselect.on('change', scheduleUpdate);

    window.addEventListener('resize', scheduleUpdate);

    if (typeof ResizeObserver !== 'undefined') {
        let firstObserverCall = true;
        const observer = new ResizeObserver(() => {
            // Skip the initial fire — `update()` below already runs once.
            if (firstObserverCall) {
                firstObserverCall = false;
                return;
            }
            scheduleUpdate();
        });
        observer.observe(control);
    }

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
