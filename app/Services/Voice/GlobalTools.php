<?php

namespace App\Services\Voice;

use App\Services\Voice\Support\Tool;

/**
 * Tools available on every screen (navigation, help, mic control).
 */
final class GlobalTools
{
    public const NAVIGATE_TARGETS = [
        'dashboard', 'invoices', 'new_invoice', 'clients', 'reports', 'scopes',
        'monitoring', 'subscriptions', 'users', 'settings', 'back',
    ];

    public static function tools(): array
    {
        return [
            Tool::make(
                'navigate',
                'Go to another screen of the app. Call this when the user asks to open or go to a section: the dashboard, the invoices list, a new invoice, clients, reports, scopes, monitoring (uptime), subscriptions, users, settings, or to go back to the previous screen.',
                ['to' => Tool::string('Destination screen.', self::NAVIGATE_TARGETS)],
                ['to']
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
