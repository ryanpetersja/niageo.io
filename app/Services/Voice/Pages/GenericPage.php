<?php

namespace App\Services\Voice\Pages;

use App\Services\Voice\PageCapability;

/**
 * Fallback for screens that have no voice actions yet: only global tools apply.
 */
class GenericPage implements PageCapability
{
    public function __construct(private string $pageName = 'unknown') {}

    public function name(): string
    {
        return $this->pageName;
    }

    public function title(): string
    {
        return 'Screen without voice actions';
    }

    public function tools(array $context): array
    {
        return [];
    }

    public function screen(array $context): string
    {
        return "Screen: {$this->pageName}. This screen has no voice actions yet — only navigation to other screens is possible. Tell the user that briefly if they ask for something else.";
    }

    public function confirmation(string $tool, array $input, array $context): ?string
    {
        return null;
    }

    public function examples(): array
    {
        return ['Go to invoices', 'Open the dashboard', 'Stop listening'];
    }
}
