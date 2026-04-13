<?php

namespace App\Services;

use App\Models\Scope;
use Illuminate\Support\Facades\Log;

class ScopeService
{
    protected ClaudeService $claudeService;

    public function __construct(ClaudeService $claudeService)
    {
        $this->claudeService = $claudeService;
    }

    public function generateSections(Scope $scope): array
    {
        $scope->loadMissing('client');
        $clientName = $scope->client->company_name ?? 'the client';
        $description = $scope->description ?? $scope->title;

        $sections = $this->claudeService->generateScopeSections($description, $clientName, $scope->title);

        $scope->update(['sections' => $sections]);

        return $sections;
    }

    public function generateItems(Scope $scope): array
    {
        $scope->loadMissing('client');
        $clientName = $scope->client->company_name ?? 'the client';
        $description = $scope->description ?? $scope->title;

        $items = $this->claudeService->generateScopeItems($description, $clientName, $scope->title, $scope->sections);

        // Clear existing items and create new ones
        $scope->items()->delete();

        foreach ($items as $i => $item) {
            $scope->items()->create(array_merge($item, ['sort_order' => $i]));
        }

        return $items;
    }

    public function refineSection(Scope $scope, string $sectionKey, string $instruction): string
    {
        $scope->loadMissing('client');
        $clientName = $scope->client->company_name ?? 'the client';
        $currentContent = $scope->sections[$sectionKey] ?? '';

        $refined = $this->claudeService->refineScopeSection(
            $sectionKey,
            $currentContent,
            $instruction,
            $scope->title,
            $clientName
        );

        $sections = $scope->sections ?? [];
        $sections[$sectionKey] = $refined;
        $scope->update(['sections' => $sections]);

        return $refined;
    }

    public function calculateTotal(Scope $scope, ?array $selectedItemIds = null): array
    {
        $scope->loadMissing('items');

        if ($selectedItemIds !== null) {
            $items = $scope->items->filter(fn ($item) => $item->is_mandatory || in_array($item->id, $selectedItemIds));
        } else {
            $items = $scope->items;
        }

        $mandatoryTotal = $items->where('is_mandatory', true)->sum('price');
        $optionalTotal = $items->where('is_mandatory', false)->sum('price');

        return [
            'mandatory_total' => (float) $mandatoryTotal,
            'optional_total' => (float) $optionalTotal,
            'total' => (float) ($mandatoryTotal + $optionalTotal),
            'item_count' => $items->count(),
        ];
    }
}
