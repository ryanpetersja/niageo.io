<?php

namespace App\Services\Voice\Pages;

use App\Services\Voice\PageCapability;
use App\Services\Voice\Support\Screen;
use App\Services\Voice\Support\Tool;

/**
 * Voice capabilities for a single invoice view (status changes, payments, PDFs, editing).
 */
class InvoiceShowPage implements PageCapability
{
    public const STATUSES = ['draft', 'sent', 'paid', 'overdue', 'cancelled'];

    public const PAYMENT_METHODS = ['bank_transfer', 'check', 'credit_card', 'cash', 'other'];

    public function name(): string
    {
        return 'invoices.show';
    }

    public function title(): string
    {
        return 'Invoice view';
    }

    public function tools(array $context): array
    {
        return array_merge([
            Tool::make(
                'edit_invoice',
                'Open the editor for this invoice (only possible while the CURRENT SCREEN says it can be edited, i.e. the invoice is a draft). Call this when the user wants to edit the invoice or change its details. When the user also says what to change, call edit_invoice FIRST and then the editing tools (set_field, add_line_item, update_line_item, remove_line_item, save_invoice) in the same response — they run once the editor opens.'
            ),
            Tool::make(
                'change_status',
                'Change the invoice status, e.g. mark it as sent, paid, overdue, cancelled or back to draft. Only the statuses listed under "Available status changes" on the CURRENT SCREEN are possible right now; if the user asks for another one, explain instead of calling this.',
                ['status' => Tool::string('Target status.', self::STATUSES)],
                ['status']
            ),
            Tool::make(
                'record_payment',
                'Record a payment received against this invoice (only when the CURRENT SCREEN says payments can be recorded). Call this when the user says a payment came in, the client paid, or asks to record or log a payment. Amount defaults to the balance due and the date to today when not mentioned.',
                [
                    'amount' => Tool::number('Amount received in dollars.'),
                    'payment_date' => Tool::string('Payment date as YYYY-MM-DD.'),
                    'payment_method' => Tool::string('How the payment was made.', self::PAYMENT_METHODS),
                    'reference' => Tool::string('Cheque number, transaction id or other reference.'),
                ]
            ),
            Tool::make(
                'delete_payment',
                'Delete a previously recorded payment. Identify it by its number from the CURRENT SCREEN payments list.',
                ['payment_number' => Tool::integer('1-based payment number from the CURRENT SCREEN payments list.')],
                ['payment_number']
            ),
            Tool::make(
                'duplicate_invoice',
                'Create a copy of this invoice as a new draft and open it for editing. Call this when the user asks to duplicate, copy or clone the invoice.'
            ),
            Tool::make(
                'delete_invoice',
                'Delete this invoice permanently (only when the CURRENT SCREEN says it can be deleted). Call this when the user asks to delete or remove the invoice.'
            ),
            Tool::make(
                'open_pdf',
                'Preview the invoice PDF in a new tab or download it. Call this when the user asks to see, preview, print, download or export the PDF.',
                ['mode' => Tool::string('preview opens the PDF in a new tab; download saves the file.', ['preview', 'download'])],
                ['mode']
            ),
            Tool::make(
                'start_new_invoice',
                'Start a brand-new invoice (not a copy). Call this when the user asks to create another or a new invoice; defaults to this invoice\'s client unless they name a different one.',
                ['client_id' => Tool::integer('Client id; defaults to this invoice\'s client.')]
            ),
        ], InvoiceFormPage::formTools('Editing tool — only valid in the same response as edit_invoice, and only when the invoice can be edited.'));
    }

    public function screen(array $context): string
    {
        $invoice = is_array($context['invoice'] ?? null) ? $context['invoice'] : [];
        $items = Screen::list($context['line_items'] ?? [], 60);
        $payments = Screen::list($context['payments'] ?? [], 30);
        $transitions = array_values(array_filter(
            is_array($context['valid_transitions'] ?? null) ? $context['valid_transitions'] : [],
            'is_string'
        ));

        $number = Screen::text($invoice['number'] ?? '', 40);
        $status = Screen::text($invoice['status'] ?? '', 20);
        $canEdit = (bool) ($context['can_edit'] ?? false);
        $canDelete = (bool) ($context['can_delete'] ?? false);
        $canPay = (bool) ($context['can_record_payment'] ?? false);

        $lines = [];
        $lines[] = "Screen: Invoice view — invoice {$number}, status {$status}.";
        $lines[] = sprintf(
            'Client: %s (id %d). Title: %s. Issue date %s, due date %s. Tax rate %s%%.',
            Screen::text($invoice['client'] ?? '', 80),
            (int) ($invoice['client_id'] ?? 0),
            Screen::quoted($invoice['title'] ?? '', 120),
            Screen::text($invoice['issue_date'] ?? '', 20),
            Screen::text($invoice['due_date'] ?? '', 20),
            Screen::text($invoice['tax_rate'] ?? '0', 10)
        );

        $lines[] = 'Line items (line. description × quantity @ unit price = total):';
        foreach ($items as $index => $item) {
            $lines[] = sprintf(
                '  %d. %s × %s @ %s = %s',
                (int) ($item['line'] ?? $index + 1),
                Screen::quoted($item['description'] ?? '', 120),
                rtrim(rtrim(number_format((float) ($item['quantity'] ?? 0), 2, '.', ''), '0'), '.'),
                Screen::money($item['unit_price'] ?? 0),
                Screen::money($item['total'] ?? 0)
            );
        }

        $lines[] = sprintf(
            'Totals: subtotal %s, tax %s, total %s, paid %s, balance due %s.',
            Screen::money($invoice['subtotal'] ?? 0),
            Screen::money($invoice['tax_amount'] ?? 0),
            Screen::money($invoice['total'] ?? 0),
            Screen::money($invoice['amount_paid'] ?? 0),
            Screen::money($invoice['balance_due'] ?? 0)
        );

        if (Screen::text($invoice['notes'] ?? '') !== '') {
            $lines[] = 'Notes to client: ' . Screen::quoted($invoice['notes'], 200) . '.';
        }
        if (Screen::text($invoice['internal_notes'] ?? '') !== '') {
            $lines[] = 'Internal notes: ' . Screen::quoted($invoice['internal_notes'], 200) . '.';
        }

        $lines[] = 'Available status changes: ' . ($transitions === [] ? 'none' : implode(', ', $transitions)) . '.';
        $lines[] = 'Can be edited: ' . ($canEdit ? 'yes (it is a draft)' : 'no (only drafts can be edited)') . '. '
            . 'Can be deleted: ' . ($canDelete ? 'yes' : 'no (only draft or cancelled invoices can be deleted)') . '. '
            . 'Payments can be recorded: ' . ($canPay ? 'yes' : 'no (' . ($status === 'paid' ? 'already paid' : 'only sent or overdue invoices take payments') . ')') . '.';

        $lines[] = 'Payments recorded (number. amount — date — method — reference):';
        if ($payments === []) {
            $lines[] = '  (none)';
        }
        foreach ($payments as $index => $payment) {
            $lines[] = sprintf(
                '  %d. %s — %s — %s — %s',
                (int) ($payment['number'] ?? $index + 1),
                Screen::money($payment['amount'] ?? 0),
                Screen::text($payment['date'] ?? '', 20),
                Screen::text($payment['method'] ?? '', 30) ?: '(no method)',
                Screen::text($payment['reference'] ?? '', 60) ?: '(no reference)'
            );
        }

        return implode("\n", $lines);
    }

    public function confirmation(string $tool, array $input, array $context): ?string
    {
        $invoice = is_array($context['invoice'] ?? null) ? $context['invoice'] : [];
        $number = Screen::text($invoice['number'] ?? 'this invoice', 40) ?: 'this invoice';

        switch ($tool) {
            case 'delete_invoice':
                return "Delete invoice {$number}? This cannot be undone. Say yes to confirm.";

            case 'record_payment':
                $amount = isset($input['amount']) && is_numeric($input['amount'])
                    ? (float) $input['amount']
                    : (float) ($invoice['balance_due'] ?? 0);
                $method = isset($input['payment_method']) && $input['payment_method'] !== ''
                    ? ' by ' . str_replace('_', ' ', (string) $input['payment_method'])
                    : '';
                $date = isset($input['payment_date']) && $input['payment_date'] !== ''
                    ? ' dated ' . Screen::text($input['payment_date'], 20)
                    : ' dated today';

                return 'Record a payment of ' . Screen::money($amount) . $method . $date . " on {$number}? Say yes to confirm.";

            case 'delete_payment':
                $payments = Screen::list($context['payments'] ?? [], 30);
                $target = null;
                foreach ($payments as $index => $payment) {
                    if ((int) ($payment['number'] ?? $index + 1) === (int) ($input['payment_number'] ?? 0)) {
                        $target = $payment;
                    }
                }
                $label = $target ? ' of ' . Screen::money($target['amount'] ?? 0) : '';

                return 'Delete payment ' . (int) ($input['payment_number'] ?? 0) . "{$label}? Say yes to confirm.";

            case 'change_status':
                $status = (string) ($input['status'] ?? '');
                if ($status === 'paid') {
                    return "Mark invoice {$number} as paid in full? Say yes to confirm.";
                }
                if ($status === 'cancelled') {
                    return "Cancel invoice {$number}? Say yes to confirm.";
                }

                return null;
        }

        return null;
    }

    public function examples(): array
    {
        return [
            'Mark this invoice as sent',
            'The client paid 500 dollars by bank transfer today',
            'Record a payment for the full balance',
            'Edit this invoice and change the title to March maintenance',
            'Duplicate this invoice',
            'Download the PDF',
            'What is the balance due?',
        ];
    }
}
