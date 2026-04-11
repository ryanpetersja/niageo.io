<x-app-layout>
    <x-slot name="header">
        <x-breadcrumbs :items="[['label' => 'Reports', 'url' => route('reports.index')], ['label' => $report->report_number, 'url' => route('reports.show', $report)], ['label' => 'Edit']]" />
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Report — {{ $report->report_number }}</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            @if(session('error'))
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">{{ session('error') }}</div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6" x-data="reportForm()">
                <form method="POST" action="{{ route('reports.update', $report) }}">
                    @csrf
                    @method('PUT')
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <x-input-label for="client_id" value="Client" />
                            <select id="client_id" name="client_id" required x-model="clientId" @change="fetchInvoices()"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Select a client...</option>
                                @foreach($clients as $c)
                                    <option value="{{ $c->id }}" {{ old('client_id', $report->client_id) == $c->id ? 'selected' : '' }}>{{ $c->company_name }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('client_id')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="title" value="Report Title" />
                            <x-text-input id="title" name="title" type="text" class="mt-1 block w-full" :value="old('title', $report->title)" required />
                            <x-input-error :messages="$errors->get('title')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="date_from" value="From Date" />
                            <x-text-input id="date_from" name="date_from" type="date" class="mt-1 block w-full" :value="old('date_from', $report->date_from->format('Y-m-d'))" required />
                            <x-input-error :messages="$errors->get('date_from')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="date_to" value="To Date" />
                            <x-text-input id="date_to" name="date_to" type="date" class="mt-1 block w-full" :value="old('date_to', $report->date_to->format('Y-m-d'))" required />
                            <x-input-error :messages="$errors->get('date_to')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="invoice_id" value="Link Invoice (optional)" />
                            <select id="invoice_id" name="invoice_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">No linked invoice</option>
                                <template x-for="inv in invoices" :key="inv.id">
                                    <option :value="inv.id" :selected="inv.id == selectedInvoiceId" x-text="inv.invoice_number + ' — $' + Number(inv.total).toFixed(2) + ' (' + inv.status + ')'"></option>
                                </template>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <x-input-label for="notes" value="Notes (visible in report)" />
                            <textarea id="notes" name="notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">{{ old('notes', $report->notes) }}</textarea>
                        </div>
                        <div>
                            <x-input-label for="internal_notes" value="Internal Notes" />
                            <textarea id="internal_notes" name="internal_notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">{{ old('internal_notes', $report->internal_notes) }}</textarea>
                        </div>
                    </div>

                    <div class="flex justify-end gap-4">
                        <a href="{{ route('reports.show', $report) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">Cancel</a>
                        <x-primary-button>Update Report</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function reportForm() {
            return {
                clientId: '{{ old("client_id", $report->client_id) }}',
                selectedInvoiceId: '{{ old("invoice_id", $report->invoice_id ?? "") }}',
                invoices: [],
                init() {
                    if (this.clientId) this.fetchInvoices();
                },
                async fetchInvoices() {
                    if (!this.clientId) { this.invoices = []; return; }
                    try {
                        const resp = await fetch(`/api/clients/${this.clientId}/invoices`, {
                            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                        });
                        if (resp.ok) this.invoices = await resp.json();
                    } catch (e) {
                        this.invoices = [];
                    }
                }
            }
        }
    </script>
</x-app-layout>
