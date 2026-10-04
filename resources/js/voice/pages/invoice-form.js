import { registerPage } from './registry';
import { readJson, setValue, highlight, submitForm } from '../dom';

const FIELD_IDS = ['client_id', 'title', 'issue_date', 'due_date', 'tax_rate', 'notes', 'internal_notes'];

const formEl = () => document.querySelector('form[data-voice-form="invoice"]');

/** Reactive scope of the page's Alpine `invoiceForm()` component (holds the line items). */
function formData() {
    const root = document.querySelector('[x-data^="invoiceForm"]');
    return root && window.Alpine ? window.Alpine.$data(root) : null;
}

function highlightLine(line) {
    setTimeout(() => highlight(document.querySelectorAll('[data-voice-line]')[line - 1]), 60);
}

/** Totals in the form read the tax field imperatively; poke the reactive array so they refresh. */
function refreshTotals() {
    const data = formData();
    if (data) data.lineItems = [...data.lineItems];
}

registerPage('invoices.form', {
    label: 'Invoice editor',
    examples: [
        'Set the title to March maintenance',
        'Make it due on the 30th',
        'Add a line for website hosting, 12 months at 45 dollars',
        'Change the quantity on line 2 to 3',
        'Change the hosting line to 250',
        'Reduce every item by 15 percent',
        'Remove the last line item',
        'Set the tax rate to 15 percent',
        'Add a line for each pull request merged in September',
        'Bill the commits from last week at 50 dollars each',
        'Save the invoice',
    ],

    context() {
        const meta = readJson('voice-context');
        const $ = (id) => document.getElementById(id);
        const clientSelect = $('client_id');
        const clients = clientSelect
            ? [...clientSelect.options].filter((o) => o.value).map((o) => ({ id: Number(o.value), name: o.textContent.trim() }))
            : [];
        const items = (formData()?.lineItems || []).map((item, index) => {
            const quantity = Number(item.quantity) || 0;
            const unitPrice = Number(item.unit_price) || 0;
            return { line: index + 1, description: item.description || '', quantity, unit_price: unitPrice, total: quantity * unitPrice };
        });
        const subtotal = items.reduce((sum, item) => sum + item.total, 0);
        const taxRate = Number($('tax_rate')?.value || 0);
        const tax = subtotal * (taxRate / 100);

        return {
            ...meta,
            clients,
            fields: {
                client_id: clientSelect?.value || '',
                client_name: clientSelect?.selectedOptions?.[0]?.value ? clientSelect.selectedOptions[0].textContent.trim() : '',
                title: $('title')?.value || '',
                issue_date: $('issue_date')?.value || '',
                due_date: $('due_date')?.value || '',
                tax_rate: $('tax_rate')?.value || '0',
                notes: $('notes')?.value || '',
                internal_notes: $('internal_notes')?.value || '',
            },
            line_items: items,
            totals: { subtotal, tax, total: subtotal + tax },
        };
    },

    actions: {
        set_field({ field, value }) {
            if (!FIELD_IDS.includes(field)) return { error: `There's no ${field} field on this screen.` };
            const el = document.getElementById(field);
            if (!el) return { error: `There's no ${field.replace(/_/g, ' ')} field on this screen.` };
            const text = value == null ? '' : String(value);

            if (field === 'client_id') {
                return setValue(el, text) ? {} : { error: "I couldn't find that client in the list." };
            }
            if (field === 'tax_rate') {
                const rate = parseFloat(text.replace(/[^0-9.]/g, ''));
                if (Number.isNaN(rate)) return { error: 'The tax rate needs to be a number.' };
                setValue(el, String(rate));
                refreshTotals();
                return {};
            }
            if ((field === 'issue_date' || field === 'due_date') && !/^\d{4}-\d{2}-\d{2}$/.test(text)) {
                return { error: 'I need a complete date to set that.' };
            }
            setValue(el, text);
            return {};
        },

        add_line_item({ description, quantity, unit_price }) {
            const data = formData();
            if (!data) return { error: "The line item editor isn't ready." };
            const qty = quantity === undefined ? 1 : Number(quantity);
            const line = {
                description: String(description || ''),
                quantity: Number.isFinite(qty) && qty > 0 ? qty : 1,
                unit_price: Number(unit_price) || 0,
            };
            // A new invoice starts with one empty line: fill it rather than leaving a blank line behind.
            const blank = data.lineItems.findIndex((item) => !String(item.description || '').trim() && !(Number(item.unit_price) > 0));
            if (blank !== -1) data.lineItems.splice(blank, 1, line);
            else data.lineItems.push(line);
            highlightLine(blank !== -1 ? blank + 1 : data.lineItems.length);
            return {};
        },

        update_line_item({ line, description, quantity, unit_price }) {
            const data = formData();
            const item = data?.lineItems[Number(line) - 1];
            if (!item) return { error: `There is no line ${line} on this invoice.` };
            if (description !== undefined) item.description = String(description);
            if (quantity !== undefined) {
                const qty = Number(quantity);
                if (!(qty > 0)) return { error: 'The quantity has to be more than zero.' };
                item.quantity = qty;
            }
            if (unit_price !== undefined) item.unit_price = Math.max(0, Number(unit_price) || 0);
            highlightLine(Number(line));
            return {};
        },

        adjust_prices({ percent, lines }) {
            const data = formData();
            if (!data) return { error: "The line item editor isn't ready." };
            const factor = 1 + (Number(percent) || 0) / 100;
            if (!(factor > 0)) return { error: 'That percentage would make the prices zero or negative.' };
            const targets = Array.isArray(lines) && lines.length ? lines.map(Number) : data.lineItems.map((_, i) => i + 1);
            for (const line of targets) {
                if (!data.lineItems[line - 1]) return { error: `There is no line ${line} on this invoice.` };
            }
            targets.forEach((line) => {
                const item = data.lineItems[line - 1];
                item.unit_price = Math.round((Number(item.unit_price) || 0) * factor * 100) / 100;
                highlightLine(line);
            });
            return {};
        },

        remove_line_item({ line }) {
            const data = formData();
            const index = Number(line) - 1;
            if (!data?.lineItems[index]) return { error: `There is no line ${line} on this invoice.` };
            if (data.lineItems.length === 1) return { error: 'An invoice needs at least one line item, so I left it in place.' };
            data.lineItems.splice(index, 1);
            return {};
        },

        apply_preset({ preset_id }) {
            const form = document.querySelector('form[data-voice-form="preset"]');
            if (!form) return { error: 'This client has no pricing presets to apply.' };
            if (!setValue(form.elements.pricing_preset_id, String(preset_id))) return { error: "I couldn't find that preset." };
            return submitForm(form);
        },

        save_invoice() {
            return submitForm(formEl());
        },

        discard_changes() {
            const link = document.querySelector('[data-voice-action="cancel"]');
            if (!link) return { error: "I couldn't find the cancel link." };
            window.location.assign(link.href);
            return { navigates: true };
        },
    },
});
