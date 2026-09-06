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
}
