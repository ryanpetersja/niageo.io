<?php

namespace App\Services\Voice;

use App\Services\Voice\Support\Tool;

/**
 * Tools available on every screen (navigation, help, mic control).
 */
final class GlobalTools
{
    public const NAVIGATE_TARGETS = [
        'dashboard', 'invoices', 'new_invoice', 'billing_plans', 'products', 'clients', 'reports', 'scopes',
        'monitoring', 'subscriptions', 'users', 'settings', 'back',
    ];

    public static function tools(): array
    {
        return [
            Tool::make(
                'navigate',
                'Go to another screen of the app. Call this when the user asks to open or go to a section: the dashboard, the invoices list, a new invoice, billing plans (recurring invoices), products, clients, reports, scopes, monitoring (uptime), subscriptions, users, settings, or to go back to the previous screen.',
                ['to' => Tool::string('Destination screen.', self::NAVIGATE_TARGETS)],
                ['to']
            ),
            Tool::make(
                'continue_task',
                'Carry the rest of a multi-step request over to the next screen. Call this in the same response as a tool that opens another screen (open_invoice, edit_invoice, save_invoice, navigate, start_new_invoice...) whenever steps remain that can only be done there. The assistant reopens you on that screen with the note you write here plus that screen\'s data. Write the remaining steps precisely in your own words (which invoice, which lines, exact percentages, which client). Do NOT call it when nothing remains.',
                [
                    'remaining' => Tool::string('Everything still to do after this screen changes, stated precisely, e.g. "reduce every line item\'s unit price by 15%, save the invoice, then go to the invoices list and filter by client Acme Corp (id 2)".'),
                    'done' => Tool::string('Short past-tense note of what this step did, e.g. "Opened invoice INV-202609-0003".'),
                ],
                ['remaining']
            ),
            Tool::make(
                'show_help',
                'Show the user example commands for the current screen. Call this when the user asks what they can say or do here, or asks for help.'
            ),
            Tool::make(
                'stop_listening',
                'Turn the microphone off. Call this when the user says to stop listening, says they are done, or says goodbye.'
            ),
        ];
    }

    public static function names(): array
    {
        return array_column(self::tools(), 'name');
    }
}
