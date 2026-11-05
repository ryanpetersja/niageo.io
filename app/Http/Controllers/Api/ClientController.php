<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\ClientResource;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search', ''));

        $clients = Client::query()
            ->when($search !== '', fn ($q) => $q->where('company_name', 'like', "%{$search}%"))
            ->when(! $request->boolean('include_inactive'), fn ($q) => $q->where('is_active', true))
            ->withSum(['invoices as open_total' => fn ($q) => $q->whereIn('status', ['sent', 'overdue'])], 'total')
            ->withSum(['invoices as open_paid' => fn ($q) => $q->whereIn('status', ['sent', 'overdue'])], 'amount_paid')
            ->orderBy('company_name')
            ->get();

        return ClientResource::collection($clients);
    }

    public function show(Client $client)
    {
        $client->load([
            'contacts',
            'pricingPresets' => fn ($q) => $q->where('is_active', true)->with('items'),
            'invoices' => fn ($q) => $q->latest('issue_date')->latest('id')->limit(10),
        ]);

        return new ClientResource($client);
    }

    /** Quick-create from the phone: company plus an optional primary contact. */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'billing_terms' => 'nullable|in:net_15,net_30,net_60,due_on_receipt',
            'billing_email' => 'nullable|email|max:255',
            'notes' => 'nullable|string',
            'contact_name' => 'nullable|string|max:255',
            'contact_email' => 'nullable|email|max:255',
            'contact_phone' => 'nullable|string|max:50',
        ]);

        $client = DB::transaction(function () use ($validated) {
            $client = Client::create([
                'company_name' => $validated['company_name'],
                'billing_terms' => $validated['billing_terms'] ?? 'net_30',
                'billing_email' => $validated['billing_email'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'is_active' => true,
            ]);

            if (! empty($validated['contact_name'])) {
                $client->contacts()->create([
                    'name' => $validated['contact_name'],
                    'email' => $validated['contact_email'] ?? null,
                    'phone' => $validated['contact_phone'] ?? null,
                    'is_primary' => true,
                ]);
            }

            return $client;
        });

        return (new ClientResource($client->load('contacts')))->response()->setStatusCode(201);
    }
}
