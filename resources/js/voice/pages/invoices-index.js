import { registerPage } from './registry';
import { readJson, setValue, highlight, normalize } from '../dom';

const filterForm = () => document.querySelector('form[data-voice-form="filters"]');

function currentFilters() {
    const form = filterForm();
    return {
        search: form?.elements.search?.value || '',
        status: form?.elements.status?.value || '',
        client_id: form?.elements.client_id?.value || '',
        source: form?.elements.source?.value || '',
    };
}

registerPage('invoices.index', {
    label: 'Invoices',
    examples: [
        'Show me overdue invoices',
        'Only unpaid invoices for Acme',
        'Search for INV-2026',
        'Clear the filters',
        'Open the first invoice',
        'Start a new invoice for Globex',
        'Download all the PDFs',
    ],

    context() {
        const data = readJson('voice-context');
        const select = filterForm()?.elements.client_id;
        const clients = select
            ? [...select.options].filter((o) => o.value).map((o) => ({ id: Number(o.value), name: o.textContent.trim() }))
            : data.clients || [];
        return { ...data, clients, filters: currentFilters() };
    },

    actions: {
        set_filters(input) {
            const form = filterForm();
            if (!form) return { error: "I couldn't find the filters on this screen." };

            const next = input.clear ? { search: '', status: '', client_id: '', source: '' } : currentFilters();
            if (!input.clear) {
                if (input.search !== undefined) next.search = input.search;
                if (input.status !== undefined) next.status = input.status;
                if (input.client_id !== undefined) next.client_id = input.client_id ? String(input.client_id) : '';
                if (input.source !== undefined) next.source = input.source;
            }

            setValue(form.elements.search, next.search);
            if (!setValue(form.elements.status, next.status)) return { error: "That status isn't one of the filter options." };
            if (next.client_id && !setValue(form.elements.client_id, next.client_id)) {
                return { error: "I couldn't find that client in the filter list." };
            }
            if (!next.client_id) setValue(form.elements.client_id, '');
            if (form.elements.source) setValue(form.elements.source, next.source || '');

            const params = new URLSearchParams();
            Object.entries(next).forEach(([key, value]) => { if (value) params.set(key, value); });
            const base = form.getAttribute('action') || window.location.pathname;
            const query = params.toString();
            window.location.assign(query ? `${base}?${query}` : base);
            return { navigates: true };
        },

        open_invoice({ invoice_number }) {
            const rows = [...document.querySelectorAll('[data-voice-invoice]')];
            const wanted = normalize(invoice_number);
            const row = rows.find((r) => normalize(r.dataset.voiceInvoice) === wanted)
                || rows.find((r) => normalize(r.dataset.voiceInvoice).endsWith(wanted))
                || rows.find((r) => normalize(r.dataset.voiceInvoice).includes(wanted));
            if (!row) return { error: `I couldn't find invoice ${invoice_number} on this page.` };
            highlight(row);
            window.location.assign(row.dataset.voiceUrl);
            return { navigates: true };
        },

        start_new_invoice({ client_id } = {}) {
            const { urls = {} } = readJson('voice-context');
            if (!urls.create) return { error: "I can't start a new invoice from here." };
            window.location.assign(urls.create + (client_id ? `?client_id=${encodeURIComponent(client_id)}` : ''));
            return { navigates: true };
        },

        download_all_pdfs() {
            const link = document.querySelector('[data-voice-action="download-all"]');
            if (!link) return { error: 'There are no invoices to download with the current filters.' };
            highlight(link);
            link.click();
            return {};
        },

        go_to_page({ page }) {
            const url = new URL(window.location.href);
            url.searchParams.set('page', String(Math.max(1, Number(page) || 1)));
            window.location.assign(url.toString());
            return { navigates: true };
        },
    },
});
