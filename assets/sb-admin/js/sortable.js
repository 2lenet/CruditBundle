import Sortable from 'sortablejs';

window.addEventListener('load', () => {
    document.querySelectorAll('table.crudit-sortable').forEach((table) => {
        const tbody = table.querySelector('tbody');
        const sortUrl = table.dataset.sortUrl;
        if (!tbody || !sortUrl) return;

        Sortable.create(tbody, {
            handle: '.crudit-sortable-handle',
            animation: 150,
            onUpdate: () => {
                const rows = [...tbody.querySelectorAll('tr[data-id]')];
                const ids = rows.map((tr) => tr.dataset.id);

                fetch(sortUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(ids),
                }).then((response) => {
                    if (!response.ok) {
                        rows.forEach((tr) => tbody.appendChild(tr));
                    }
                }).catch(() => {
                    rows.forEach((tr) => tbody.appendChild(tr));
                });
            },
        });
    });
});