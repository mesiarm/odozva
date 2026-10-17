const searchForm = document.querySelector('form[role="search"]');
const results = document.getElementById('company-results');

if (searchForm && results) {
    const input = searchForm.querySelector('input[name="q"]');
    let timer;
    let controller;

    const refresh = async () => {
        controller?.abort();
        controller = new AbortController();

        const params = new URLSearchParams(new FormData(searchForm));

        if (!params.get('q')) {
            params.delete('q');
        }

        const url = new URL(searchForm.action);
        url.search = params.toString();

        try {
            const response = await fetch(url, { signal: controller.signal });

            if (!response.ok) {
                return;
            }

            const page = new DOMParser().parseFromString(await response.text(), 'text/html');
            const fresh = page.getElementById('company-results');

            if (fresh) {
                results.innerHTML = fresh.innerHTML;
                history.replaceState(null, '', url);
            }
        } catch (error) {
            if (error.name !== 'AbortError') {
                searchForm.submit();
            }
        }
    };

    input.addEventListener('input', () => {
        clearTimeout(timer);
        timer = setTimeout(refresh, 250);
    });

    searchForm.addEventListener('submit', (event) => {
        event.preventDefault();
        clearTimeout(timer);
        refresh();
    });
}
