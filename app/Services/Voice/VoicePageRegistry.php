<?php

namespace App\Services\Voice;

use App\Services\Voice\Pages\GenericPage;
use App\Services\Voice\Pages\InvoiceFormPage;
use App\Services\Voice\Pages\InvoiceIndexPage;
use App\Services\Voice\Pages\InvoiceShowPage;

/**
 * Maps a page key (the `voice-page` layout attribute) to its capability class.
 * Add a line here (plus a JS page adapter) to give a new screen voice control.
 */
class VoicePageRegistry
{
    /** @var array<string, class-string<PageCapability>> */
    protected array $pages = [
        'invoices.index' => InvoiceIndexPage::class,
        'invoices.form' => InvoiceFormPage::class,
        'invoices.show' => InvoiceShowPage::class,
    ];

    public function resolve(string $name): PageCapability
    {
        if (isset($this->pages[$name])) {
            return app($this->pages[$name]);
        }

        return new GenericPage($name);
    }

    public function has(string $name): bool
    {
        return isset($this->pages[$name]);
    }

    /**
     * One line per voice-enabled screen listing its tools, for the planner's system prompt.
     * Deterministic so the prompt prefix stays cacheable.
     */
    public function capabilityMap(): string
    {
        $lines = [];
        foreach ($this->pages as $name => $class) {
            /** @var PageCapability $page */
            $page = app($class);
            $tools = array_column($page->tools([]), 'name');
            $lines[] = "- {$page->title()} ({$name}): " . implode(', ', $tools);
        }
        $lines[] = '- Every screen: navigate, continue_task, show_help, stop_listening.';
        $lines[] = '- Other screens (dashboard, clients, billing plans, products, reports, scopes, monitoring, subscriptions, users, settings): navigation only for now; say so if asked for more there.';

        return implode("\n", $lines);
    }
}
