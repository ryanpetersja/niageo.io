<?php

namespace App\Services\Voice\Pages;

use App\Services\Voice\PageCapability;
use App\Services\Voice\Support\Screen;
use App\Services\Voice\Support\Tool;

/**
 * Voice capabilities for the invoice editor (create and edit share one form).
 */
class InvoiceFormPage implements PageCapability
{
    public const FIELDS = ['client_id', 'title', 'issue_date', 'due_date', 'tax_rate', 'notes', 'internal_notes'];

    public function name(): string
    {
        return 'invoices.form';
    }

    public function title(): string
    {
        return 'Invoice editor';
    }

    public function tools(array $context): array
    {
        return self::formTools('');
    }

    /**
     * The editing tools, reusable from the invoice view (where they apply after edit_invoice).
     */
    public static function formTools(string $preface): array
    {
        $p = $preface === '' ? '' : $preface . ' ';

        return [
            Tool::make(
                'set_field',
                $p . 'Change one field of the invoice form on screen. Call this when the user wants to change the client, the title, the issue date, the due date, the tax rate, the client-visible notes or the internal notes. Call it once per field. Dates must be YYYY-MM-DD; client_id must be an id from the CURRENT SCREEN client list; tax_rate is a percentage number; pass an empty string to clear a text field.',
                [
                    'field' => Tool::string('Which form field to change.', self::FIELDS),
                    'value' => Tool::string('New value for the field, as text.'),
                ],
                ['field', 'value']
            ),
            Tool::make(
                'add_line_item',
                $p . 'Add a new line item (a billable line with description, quantity and unit price) to the invoice. Call this when the user wants to add, bill, charge or include something. Quantity defaults to 1 and unit price to 0 when not mentioned.',
                [
                    'description' => Tool::string('Line item description as it should appear on the invoice.'),
                    'quantity' => Tool::number('Quantity (defaults to 1).'),
                    'unit_price' => Tool::number('Price per unit in dollars (defaults to 0).'),
                ],
                ['description']
            ),
            Tool::make(
                'update_line_item',
                $p . 'Change an existing line item. Call this when the user wants to rename, reprice, or change the quantity of a line that is already on the invoice. Identify the line by its number from the CURRENT SCREEN line item list (match spoken descriptions loosely) and include only the properties that change. "Change the hosting line to 250" means unit_price=250.',
                [
                    'line' => Tool::integer('1-based line number from the CURRENT SCREEN line item list.'),
                    'description' => Tool::string('New description, if it changes.'),
                    'quantity' => Tool::number('New quantity, if it changes.'),
                    'unit_price' => Tool::number('New unit price in dollars, if it changes.'),
                ],
                ['line']
            ),
            Tool::make(
                'remove_line_item',
                $p . 'Remove a line item from the invoice. Call this when the user wants to delete, remove or drop a line. Identify it by its number from the CURRENT SCREEN line item list.',
                ['line' => Tool::integer('1-based line number from the CURRENT SCREEN line item list.')],
                ['line']
            ),
            Tool::make(
                'apply_preset',
                $p . 'Replace all line items with one of the client\'s pricing presets (a saved bundle of line items). Only available when the CURRENT SCREEN lists presets. Call this when the user asks to apply, use or load a preset or bundle.',
                ['preset_id' => Tool::integer('Preset id from the CURRENT SCREEN preset list.')],
                ['preset_id']
            ),
            Tool::make(
                'save_invoice',
                $p . 'Save the invoice form (creates the invoice or saves the changes). Call this when the user says to save, update, create, finish or submit the invoice. Call it last, after any field changes in the same response.'
            ),
            Tool::make(
                'discard_changes',
                $p . 'Leave the editor without saving. Call this when the user says to cancel, discard the changes or go back without saving.'
            ),
        ];
    }

    public function screen(array $context): string
    {
        $mode = ($context['mode'] ?? 'create') === 'edit' ? 'edit' : 'create';
        $fields = is_array($context['fields'] ?? null) ? $context['fields'] : [];
        $clients = Screen::list($context['clients'] ?? []);
        $presets = Screen::list($context['presets'] ?? []);
        $items = Screen::list($context['line_items'] ?? [], 60);
        $totals = is_array($context['totals'] ?? null) ? $context['totals'] : [];

        $lines = [];
        $lines[] = $mode === 'edit'
            ? 'Screen: Invoice editor — editing draft invoice ' . Screen::text($context['invoice_number'] ?? '', 40) . '. Changes are not saved until save_invoice is called.'
            : 'Screen: Invoice editor — creating a new invoice. Nothing is saved until save_invoice is called.';

        $clientId = Screen::text($fields['client_id'] ?? '', 20);
        $clientName = Screen::text($fields['client_name'] ?? '', 80);
        $lines[] = 'Form fields: '
            . 'client=' . ($clientId === '' ? '(not selected)' : "{$clientName} (id {$clientId})")
            . '; title=' . Screen::quoted($fields['title'] ?? '', 120)
            . '; issue_date=' . Screen::text($fields['issue_date'] ?? '', 20)
            . '; due_date=' . Screen::text($fields['due_date'] ?? '', 20)
            . '; tax_rate=' . Screen::text($fields['tax_rate'] ?? '0', 10) . '%'
            . '; notes=' . Screen::quoted($fields['notes'] ?? '', 160)
            . '; internal_notes=' . Screen::quoted($fields['internal_notes'] ?? '', 160) . '.';

        $lines[] = 'Clients (id: name): ' . ($clients === []
            ? 'none'
            : implode('; ', array_map(
                fn ($c) => (int) ($c['id'] ?? 0) . ': ' . Screen::text($c['name'] ?? '', 80),
                $clients
            ))) . '.';

        $lines[] = 'Line items (line. description × quantity @ unit price = total):';
        if ($items === []) {
            $lines[] = '  (no line items yet)';
        }
        foreach ($items as $index => $item) {
            $lines[] = sprintf(
                '  %d. %s × %s @ %s = %s',
                (int) ($item['line'] ?? $index + 1),
                Screen::quoted($item['description'] ?? '', 120),
                rtrim(rtrim(number_format((float) ($item['quantity'] ?? 0), 2, '.', ''), '0'), '.'),
                Screen::money($item['unit_price'] ?? 0),
                Screen::money($item['total'] ?? ((float) ($item['quantity'] ?? 0) * (float) ($item['unit_price'] ?? 0)))
            );
        }

        $lines[] = sprintf(
            'Totals: subtotal %s, tax %s, total %s.',
            Screen::money($totals['subtotal'] ?? 0),
            Screen::money($totals['tax'] ?? 0),
            Screen::money($totals['total'] ?? 0)
        );

        $lines[] = 'Pricing presets available (id: name — total): ' . ($presets === []
            ? 'none'
            : implode('; ', array_map(
                fn ($p) => (int) ($p['id'] ?? 0) . ': ' . Screen::text($p['name'] ?? '', 80) . ' — ' . Screen::money($p['total'] ?? 0),
                $presets
            ))) . '.';

        $lines[] = 'Saving requires a client, an issue date, a due date on or after the issue date, and at least one line item with a description and a quantity above zero.';

        return implode("\n", $lines);
    }

    public function confirmation(string $tool, array $input, array $context): ?string
    {
        return null;
    }

    public function examples(): array
    {
        return [
            'Set the title to March maintenance',
            'Make it due on the 30th',
            'Add a line for website hosting, 12 months at 45 dollars',
            'Change the quantity on line 2 to 3',
            'Change the hosting line to 250',
            'Remove the last line item',
            'Set the tax rate to 15 percent',
            'Save the invoice',
        ];
    }
}
