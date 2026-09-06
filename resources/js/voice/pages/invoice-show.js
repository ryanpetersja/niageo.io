import { registerPage } from './registry';
import { readJson, setValue, highlight, submitForm } from '../dom';

registerPage('invoices.show', {
    label: 'Invoice',
    examples: [
        'Mark this invoice as sent',
        'The client paid 500 dollars by bank transfer today',
        'Record a payment for the full balance',
        'Edit this invoice and change the title to March maintenance',
        'Duplicate this invoice',
        'Download the PDF',
        'What is the balance due?',
    ],

    context() {
        return readJson('voice-context');
    },

    actions: {
        edit_invoice() {
            const ctx = readJson('voice-context');
            if (!ctx.can_edit || !ctx.urls?.edit) return { error: 'Only draft invoices can be edited.' };
            window.location.assign(ctx.urls.edit);
            return { navigates: true };
        },

        change_status({ status }) {
            const form = document.querySelector(`form[data-voice-action="transition"][data-status="${CSS.escape(String(status))}"]`);
            if (!form) return { error: `Marking this invoice as ${status} isn't available right now.` };
            highlight(form.querySelector('button'));
            return submitForm(form, { bypassHandlers: true });
        },

        record_payment({ amount, payment_date, payment_method, reference } = {}) {
            const form = document.querySelector('form[data-voice-form="payment"]');
            if (!form) return { error: "Payments can't be recorded on this invoice right now." };
            if (amount !== undefined) {
                const value = Number(amount);
                if (!(value > 0)) return { error: 'The payment amount has to be more than zero.' };
                setValue(form.elements.amount, value.toFixed(2));
            }
            if (payment_date) setValue(form.elements.payment_date, payment_date);
            if (payment_method && !setValue(form.elements.payment_method, payment_method)) {
                return { error: "That payment method isn't one of the options." };
            }
            if (reference !== undefined) setValue(form.elements.reference, reference);
            return submitForm(form);
        },

        delete_payment({ payment_number }) {
            const form = document.querySelector(`form[data-voice-action="delete-payment"][data-payment-number="${Number(payment_number)}"]`);
            if (!form) return { error: `There is no payment number ${payment_number} on this invoice.` };
            return submitForm(form, { bypassHandlers: true });
        },

        duplicate_invoice() {
            return submitForm(document.querySelector('form[data-voice-action="duplicate"]'), { bypassHandlers: true });
        },

        delete_invoice() {
            const form = document.querySelector('form[data-voice-action="delete-invoice"]');
            if (!form) return { error: 'This invoice cannot be deleted in its current status.' };
            return submitForm(form, { bypassHandlers: true });
        },

        open_pdf({ mode }) {
            const { urls = {} } = readJson('voice-context');
            if (mode === 'download') {
                if (!urls.pdf_download) return { error: "I couldn't find the download link." };
                window.location.assign(urls.pdf_download);
                return {};
            }
            if (!urls.pdf) return { error: "I couldn't find the PDF link." };
            const tab = window.open(urls.pdf, '_blank');
            if (!tab) {
                window.location.assign(urls.pdf);
                return { navigates: true };
            }
            return {};
        },

        start_new_invoice({ client_id } = {}) {
            const ctx = readJson('voice-context');
            if (!ctx.urls?.create) return { error: "I can't start a new invoice from here." };
            const id = client_id || ctx.invoice?.client_id;
            window.location.assign(ctx.urls.create + (id ? `?client_id=${encodeURIComponent(id)}` : ''));
            return { navigates: true };
        },
    },
});
