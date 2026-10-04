<?php

namespace App\Services\Voice;

/**
 * A screen whose tools include lookups that run on the server during the request
 * (rather than as actions in the browser). Their results go back to Claude before it
 * answers, and compact actions can be expanded into the browser actions they stand for.
 */
interface ResolvesServerTools
{
    /** @return string[] names of tools that run on the server and return a result to the model */
    public function serverToolNames(): array;

    /** Run a server tool; the returned text is the tool_result shown to the model. */
    public function runServerTool(string $name, array $input, array $context): string;

    /**
     * Expand a validated action into the browser actions it stands for (or [] to drop it).
     * Return null to leave the action unchanged.
     *
     * @return array<int, array{name: string, input: array}>|null
     */
    public function expandAction(array $action, array $context): ?array;
}
