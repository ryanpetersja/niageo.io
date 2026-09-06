<?php

namespace App\Services\Voice\Pages;

use App\Services\Voice\PageCapability;
use App\Services\Voice\Support\Screen;
use App\Services\Voice\Support\Tool;

/**
 * Voice capabilities for the invoice list (filters, opening rows, pagination, downloads).
 */
class InvoiceIndexPage implements PageCapability
{
    public const STATUSES = ['draft', 'sent', 'paid', 'overdue', 'cancelled'];

    public function name(): string
    {
        return 'invoices.index';
    }

    public function title(): string
    {
        return 'Invoice list';
    }

    public function tools(array $context): array
    {
        return [
            Tool::make(
                'set_filters',
                'Filter the invoice list. Call this whenever the user describes which invoices they want to see (by status, by client, by invoice number or by text). Only include the filters the user mentioned; omitted filters keep their current value. To remove a single filter pass an empty string for status or search, or 0 for client_id. Pass clear=true to reset every filter and show all invoices.',
                [
                    'search' => Tool::string('Text to search for in invoice numbers and client names. Empty string clears the search box.'),
                    'status' => Tool::string('Invoice status to show. Empty string shows all statuses.', array_merge([''], self::STATUSES)),
                    'client_id' => Tool::integer('Client id taken from the CURRENT SCREEN client list. 0 shows all clients.'),
                    'clear' => Tool::boolean('true to remove all filters.'),
                ]
            ),
            Tool::make(
                'open_invoice',
                'Open one invoice from the list on screen. Call this when the user asks to open, view or show a specific invoice — by number, by client name, or by position such as "the first one" or "the overdue one for Acme". Use the exact invoice number shown on the CURRENT SCREEN.',
                ['invoice_number' => Tool::string('Exact invoice number as listed on the CURRENT SCREEN, e.g. INV-202609-0003.')],
                ['invoice_number']
            ),
            Tool::make(
                'start_new_invoice',
                'Start creating a new invoice. Call this when the user wants to create, draft or make a new invoice. Include client_id when they name a client.',
                ['client_id' => Tool::integer('Client id from the CURRENT SCREEN client list, when the user named a client.')]
            ),
            Tool::make(
                'download_all_pdfs',
                'Download PDFs of every invoice matching the current filters as one zip file. Call this when the user asks to download or export all invoices.'
            ),
            Tool::make(
                'go_to_page',
                'Show another page of the invoice list when it is paginated. Call this for "next page", "previous page" or "page 3"; compute the page number from the CURRENT SCREEN pagination line.',
                ['page' => Tool::integer('1-based page number to show.')],
                ['page']
            ),
        ];
    }

    public function screen(array $context): string
    {
        $filters = is_array($context['filters'] ?? null) ? $context['filters'] : [];
        $clients = Screen::list($context['clients'] ?? []);
        $rows = Screen::list($context['invoices'] ?? [], 50);
        $pagination = is_array($context['pagination'] ?? null) ? $context['pagination'] : [];

        $clientFilter = (string) ($filters['client_id'] ?? '');
        $clientName = '(all clients)';
        foreach ($clients as $client) {
            if ($clientFilter !== '' && $clientFilter !== '0' && (string) ($client['id'] ?? '') === $clientFilter) {
                $clientName = Screen::text($client['name'] ?? '', 80) . " (id {$clientFilter})";
            }
        }

        $status = Screen::text($filters['status'] ?? '', 20);
        $lines = [];
        $lines[] = 'Screen: Invoice list — a filterable table of invoices.';
        $lines[] = sprintf(
            'Current filters: search=%s; status=%s; client=%s.',
            Screen::quoted($filters['search'] ?? '', 80),
            $status === '' ? '(all statuses)' : $status,
            $clientName
        );
        $lines[] = 'Statuses: ' . implode(', ', self::STATUSES) . '.';
        $lines[] = 'Clients available for filtering (id: name): ' . ($clients === []
            ? 'none'
            : implode('; ', array_map(
                fn ($c) => (int) ($c['id'] ?? 0) . ': ' . Screen::text($c['name'] ?? '', 80),
                $clients
            ))) . '.';

        if ($pagination !== []) {
            $lines[] = sprintf(
                'Showing invoices %s–%s of %s (page %s of %s).',
                (int) ($pagination['from'] ?? 0),
                (int) ($pagination['to'] ?? 0),
                (int) ($pagination['total'] ?? 0),
                (int) ($pagination['current_page'] ?? 1),
                (int) ($pagination['last_page'] ?? 1)
            );
        }

        $lines[] = 'Invoices on this page (position. number — client — status — total — balance due — due date):';
        if ($rows === []) {
            $lines[] = '  (no invoices match the current filters)';
        }
        foreach ($rows as $index => $row) {
            $title = Screen::text($row['title'] ?? '', 80);
            $lines[] = sprintf(
                '  %d. %s — %s — %s — %s — balance %s — due %s%s',
                $index + 1,
                Screen::text($row['number'] ?? '?', 40),
                Screen::text($row['client'] ?? '?', 80),
                Screen::text($row['status'] ?? '?', 20),
                Screen::money($row['total'] ?? 0),
                Screen::money($row['balance_due'] ?? 0),
                Screen::text($row['due_date'] ?? '?', 20),
                $title !== '' ? " — titled \"{$title}\"" : ''
            );
        }

        return implode("\n", $lines);
    }

    public function confirmation(string $tool, array $input, array $context): ?string
    {
        return null;
    }

    public function examples(): array
    {
        return [
            'Show me overdue invoices',
            'Only invoices for Acme that are still unpaid',
            'Search for INV-2026',
            'Clear the filters',
            'Open the first invoice',
            'Start a new invoice for Globex',
            'Download all the PDFs',
        ];
    }
}
