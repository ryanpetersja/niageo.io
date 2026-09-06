<?php

namespace App\Services\Voice;

/**
 * Describes what the voice assistant can do on one screen of the app.
 *
 * The browser sends a JSON snapshot of the screen ("context"); the capability
 * turns it into text for the model and exposes the tools (actions) the model
 * may call. Every tool maps 1:1 to an action handler in resources/js/voice/pages.
 */
interface PageCapability
{
    /** Page key, matches the `voice-page` attribute on <x-app-layout>. */
    public function name(): string;

    /** Human-readable screen title used in prompts. */
    public function title(): string;

    /** Messages API tool definitions available on this screen. */
    public function tools(array $context): array;

    /** Plain-text description of the current screen state for the model. */
    public function screen(array $context): string;

    /** Confirmation prompt when the action needs a spoken "yes" first, or null. */
    public function confirmation(string $tool, array $input, array $context): ?string;

    /** Example commands shown to the user for help. */
    public function examples(): array;
}
