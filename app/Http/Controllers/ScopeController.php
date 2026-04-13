<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Scope;
use App\Models\ScopeItem;
use App\Services\ScopePdfService;
use App\Services\ScopeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ScopeController extends Controller
{
    public function __construct(
        private ScopeService $scopeService,
        private ScopePdfService $pdfService,
    ) {}

    public function index(Request $request)
    {
        $query = Scope::with('client');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('scope_number', 'like', "%{$search}%")
                  ->orWhere('title', 'like', "%{$search}%")
                  ->orWhereHas('client', fn ($q) => $q->where('company_name', 'like', "%{$search}%"));
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($clientId = $request->input('client_id')) {
            $query->where('client_id', $clientId);
        }

        $scopes = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();
        $clients = Client::where('is_active', true)->orderBy('company_name')->get();

        return view('scopes.index', compact('scopes', 'clients'));
    }

    public function create(Request $request)
    {
        $clients = Client::where('is_active', true)->orderBy('company_name')->get();
        $selectedClientId = $request->input('client_id');

        return view('scopes.create', compact('clients', 'selectedClientId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'currency' => 'nullable|string|max:10',
            'notes' => 'nullable|string',
            'internal_notes' => 'nullable|string',
        ]);

        $scope = Scope::create([
            ...$validated,
            'created_by' => auth()->id(),
            'currency' => $validated['currency'] ?? 'USD',
            'status' => 'draft',
        ]);

        // If description provided and AI generation requested
        if ($request->boolean('generate_ai') && !empty($validated['description'])) {
            try {
                $this->scopeService->generateSections($scope);
                $this->scopeService->generateItems($scope);
                return redirect()->route('scopes.show', $scope)->with('success', 'Scope created and AI content generated successfully.');
            } catch (\Exception $e) {
                Log::error('AI scope generation failed', ['error' => $e->getMessage()]);
                return redirect()->route('scopes.show', $scope)->with('success', 'Scope created. AI generation failed: ' . $e->getMessage());
            }
        }

        return redirect()->route('scopes.show', $scope)->with('success', 'Scope created successfully.');
    }

    public function show(Scope $scope)
    {
        $scope->load(['client', 'creator', 'invoice', 'items']);

        return view('scopes.show', compact('scope'));
    }

    public function edit(Scope $scope)
    {
        $scope->load(['client', 'items', 'invoice']);
        $clients = Client::where('is_active', true)->orderBy('company_name')->get();

        return view('scopes.edit', compact('scope', 'clients'));
    }

    public function update(Request $request, Scope $scope)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'currency' => 'nullable|string|max:10',
            'notes' => 'nullable|string',
            'internal_notes' => 'nullable|string',
            'sections' => 'nullable|array',
        ]);

        $scope->update($validated);

        // Handle scope items if submitted
        if ($request->has('items')) {
            $this->syncItems($scope, $request->input('items', []));
        }

        return redirect()->route('scopes.show', $scope)->with('success', 'Scope updated successfully.');
    }

    public function destroy(Scope $scope)
    {
        $scope->delete();

        return redirect()->route('scopes.index')->with('success', 'Scope deleted successfully.');
    }

    public function generate(Request $request, Scope $scope)
    {
        if (empty($scope->description)) {
            return back()->with('error', 'Please add a project description before generating AI content.');
        }

        try {
            $what = $request->input('generate', 'all');

            if ($what === 'sections' || $what === 'all') {
                $this->scopeService->generateSections($scope);
            }

            if ($what === 'items' || $what === 'all') {
                $this->scopeService->generateItems($scope);
            }

            return back()->with('success', 'AI content generated successfully.');
        } catch (\Exception $e) {
            Log::error('AI scope generation failed', ['error' => $e->getMessage()]);
            return back()->with('error', 'AI generation failed: ' . $e->getMessage());
        }
    }

    public function refineSection(Request $request, Scope $scope)
    {
        $validated = $request->validate([
            'section_key' => 'required|string|in:purpose_statement,problem_description,solution_overview,goals_objectives,assumptions,out_of_scope,timeline_summary,next_steps',
            'instruction' => 'required|string|max:2000',
        ]);

        try {
            $refined = $this->scopeService->refineSection($scope, $validated['section_key'], $validated['instruction']);

            return response()->json([
                'success' => true,
                'content' => $refined,
                'sections' => $scope->fresh()->sections,
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function updateSections(Request $request, Scope $scope)
    {
        $validated = $request->validate([
            'sections' => 'required|array',
        ]);

        $scope->update(['sections' => $validated['sections']]);

        return response()->json(['success' => true]);
    }

    public function updateItems(Request $request, Scope $scope)
    {
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'nullable|integer',
            'items.*.title' => 'required|string|max:255',
            'items.*.description' => 'nullable|string|max:2000',
            'items.*.category' => 'nullable|string|max:100',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.is_mandatory' => 'boolean',
            'items.*.is_optional' => 'boolean',
            'items.*.is_recommended' => 'boolean',
            'items.*.business_value_statement' => 'nullable|string|max:1000',
            'items.*.effort_description' => 'nullable|string|max:500',
            'items.*.deliverable_description' => 'nullable|string|max:1000',
        ]);

        $this->syncItems($scope, $validated['items']);

        return response()->json(['success' => true]);
    }

    public function linkInvoice(Request $request, Scope $scope)
    {
        $validated = $request->validate([
            'invoice_id' => 'required|exists:invoices,id',
        ]);

        $scope->update(['invoice_id' => $validated['invoice_id']]);

        return redirect()->route('scopes.show', $scope)->with('success', 'Invoice linked successfully.');
    }

    public function unlinkInvoice(Scope $scope)
    {
        $scope->update(['invoice_id' => null]);

        return redirect()->route('scopes.show', $scope)->with('success', 'Invoice unlinked.');
    }

    public function pdf(Scope $scope)
    {
        return $this->pdfService->stream($scope);
    }

    public function downloadPdf(Scope $scope)
    {
        return $this->pdfService->download($scope);
    }

    public function send(Scope $scope)
    {
        $scope->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        return redirect()->route('scopes.show', $scope)->with('success', 'Scope marked as sent.');
    }

    public function approve(Scope $scope)
    {
        $scope->update([
            'status' => 'approved',
            'approved_at' => now(),
        ]);

        return redirect()->route('scopes.show', $scope)->with('success', 'Scope approved.');
    }

    private function syncItems(Scope $scope, array $items): void
    {
        $existingIds = $scope->items()->pluck('id')->toArray();
        $submittedIds = [];

        foreach ($items as $i => $itemData) {
            $data = [
                'title' => $itemData['title'],
                'description' => $itemData['description'] ?? null,
                'category' => $itemData['category'] ?? null,
                'price' => $itemData['price'] ?? 0,
                'is_mandatory' => $itemData['is_mandatory'] ?? false,
                'is_optional' => $itemData['is_optional'] ?? true,
                'is_recommended' => $itemData['is_recommended'] ?? false,
                'sort_order' => $i,
                'business_value_statement' => $itemData['business_value_statement'] ?? null,
                'effort_description' => $itemData['effort_description'] ?? null,
                'deliverable_description' => $itemData['deliverable_description'] ?? null,
            ];

            if (!empty($itemData['id']) && in_array($itemData['id'], $existingIds)) {
                $scope->items()->where('id', $itemData['id'])->update($data);
                $submittedIds[] = $itemData['id'];
            } else {
                $newItem = $scope->items()->create($data);
                $submittedIds[] = $newItem->id;
            }
        }

        // Delete items that were removed
        $toDelete = array_diff($existingIds, $submittedIds);
        if (!empty($toDelete)) {
            ScopeItem::whereIn('id', $toDelete)->delete();
        }
    }
}
