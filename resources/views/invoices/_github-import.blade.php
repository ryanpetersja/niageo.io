{{-- "Import from GitHub": one invoice line per merged PR / commit in the selected client's linked repos.
     Lives inside the invoiceForm() Alpine scope and pushes onto its lineItems. --}}
<div x-data="githubImport('{{ url('/clients') }}')" class="mb-6" data-github-import>
    <div x-show="open" x-cloak class="rounded-lg border p-4 space-y-4" style="border-color: var(--border); background: var(--surface-2);">
        <div class="flex items-center justify-between gap-3">
            <h4 class="text-sm font-semibold text-white">Import from GitHub</h4>
            <button type="button" @click="open = false" class="text-sm text-muted hover:text-white px-2 -mr-2" aria-label="Close">&times;</button>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 items-end">
            <div class="col-span-2 sm:col-span-1">
                <label class="field-label" for="gh_kind">Bill</label>
                <select id="gh_kind" x-model="kind" class="field text-base sm:text-sm">
                    <option value="merged_pull_requests">Merged pull requests</option>
                    <option value="commits">Commits</option>
                </select>
            </div>
            <div>
                <label class="field-label" for="gh_since">From</label>
                <input id="gh_since" type="date" x-model="since" class="field text-base sm:text-sm">
            </div>
            <div>
                <label class="field-label" for="gh_until">To</label>
                <input id="gh_until" type="date" x-model="until" class="field text-base sm:text-sm">
            </div>
            <button type="button" @click="find()" :disabled="loading" class="btn btn-secondary col-span-2 sm:col-span-1 justify-center">
                <span x-text="loading ? 'Searching…' : 'Find'"></span>
            </button>
        </div>

        <p x-show="error" x-text="error" class="text-sm" style="color: var(--danger);"></p>

        <template x-if="searched && !error">
            <div class="space-y-3">
                <div class="flex items-center justify-between gap-3 text-sm">
                    <span class="text-muted" x-text="summary()"></span>
                    <label x-show="items.length" class="inline-flex items-center gap-2 text-muted whitespace-nowrap">
                        <input type="checkbox" :checked="allSelected()" @change="selectAll($event.target.checked)" class="rounded border-slate-600 bg-slate-800 text-indigo-500 focus:ring-indigo-500"> All
                    </label>
                </div>

                <ul x-show="items.length" class="max-h-72 overflow-y-auto divide-hair rounded-md border" style="border-color: var(--border);">
                    <template x-for="(item, i) in items" :key="item.repo + item.ref">
                        <li>
                            <label class="flex items-start gap-3 px-3 py-2 cursor-pointer">
                                <input type="checkbox" x-model="item.selected" class="mt-1 rounded border-slate-600 bg-slate-800 text-indigo-500 focus:ring-indigo-500">
                                <span class="min-w-0 flex-1">
                                    <span class="block text-sm text-slate-200 break-words" x-text="item.description"></span>
                                    <span class="block text-xs text-faint" x-text="item.date + ' · ' + item.author + ' · ' + item.repo"></span>
                                </span>
                                <a :href="item.url" target="_blank" rel="noopener" class="text-xs accent-ink hover:underline mt-1" @click.stop>View</a>
                            </label>
                        </li>
                    </template>
                </ul>

                <div x-show="items.length" class="grid grid-cols-2 sm:grid-cols-4 gap-3 items-end">
                    <div>
                        <label class="field-label" for="gh_qty">Qty per line</label>
                        <input id="gh_qty" type="number" inputmode="decimal" step="0.01" min="0.01" x-model="quantity" class="field text-base sm:text-sm">
                    </div>
                    <div>
                        <label class="field-label" for="gh_price">Unit price</label>
                        <input id="gh_price" type="number" inputmode="decimal" step="0.01" min="0" x-model="unitPrice" class="field text-base sm:text-sm">
                    </div>
                    <button type="button" @click="addLines()" :disabled="!selectedCount()" class="btn btn-primary col-span-2 justify-center"
                            x-text="'Add ' + selectedCount() + ' line' + (selectedCount() === 1 ? '' : 's')"></button>
                </div>
            </div>
        </template>
    </div>
</div>

@once
<script>
    function githubImport(clientsUrl) {
        const iso = (d) => d.toISOString().slice(0, 10);
        const now = new Date();
        return {
            open: false,
            kind: 'merged_pull_requests',
            // Default to last calendar month, the usual billing period.
            since: iso(new Date(Date.UTC(now.getFullYear(), now.getMonth() - 1, 1))),
            until: iso(new Date(Date.UTC(now.getFullYear(), now.getMonth(), 0))),
            loading: false,
            searched: false,
            error: '',
            repos: [],
            items: [],
            truncated: false,
            quantity: 1,
            unitPrice: 0,

            init() {
                this.$watch('open', (open) => { if (open) this.error = ''; });
                window.addEventListener('github-import:toggle', () => { this.open = !this.open; });
            },
            clientId() { return document.getElementById('client_id')?.value || ''; },
            summary() {
                const label = this.kind === 'commits' ? 'commit' : 'merged pull request';
                const n = this.items.length;
                return (n ? `${n} ${label}${n === 1 ? '' : 's'}` : `No ${label}s`) + ` in ${this.repos.join(', ')}` + (this.truncated ? ' (first 100 shown)' : '');
            },
            selectedCount() { return this.items.filter((i) => i.selected).length; },
            allSelected() { return this.items.length > 0 && this.items.every((i) => i.selected); },
            selectAll(on) { this.items.forEach((i) => { i.selected = on; }); },

            async find() {
                this.error = '';
                if (!this.clientId()) { this.error = 'Select a client first.'; return; }
                if (!this.since || !this.until || this.since > this.until) { this.error = 'Pick a valid date range.'; return; }
                this.loading = true;
                try {
                    const params = new URLSearchParams({ kind: this.kind, since: this.since, until: this.until });
                    const response = await fetch(`${clientsUrl}/${this.clientId()}/github-activity?${params}`, { headers: { Accept: 'application/json' } });
                    const data = await response.json().catch(() => ({}));
                    if (!response.ok) throw new Error(data.message || (data.errors && Object.values(data.errors)[0]?.[0]) || 'GitHub lookup failed.');
                    this.repos = data.repos || [];
                    this.truncated = !!data.truncated;
                    this.items = (data.items || []).map((item) => ({ ...item, selected: true }));
                    this.searched = true;
                } catch (e) {
                    this.error = e.message;
                    this.searched = false;
                } finally {
                    this.loading = false;
                }
            },

            addLines() {
                const qty = Number(this.quantity) > 0 ? Number(this.quantity) : 1;
                const price = Math.max(0, Number(this.unitPrice) || 0);
                // Drop empty placeholder lines (a new invoice starts with one) before adding.
                const kept = this.lineItems.filter((l) => String(l.description || '').trim() || Number(l.unit_price) > 0);
                const added = this.items.filter((i) => i.selected).map((i) => ({ description: i.description.slice(0, 255), quantity: qty, unit_price: price }));
                this.lineItems.splice(0, this.lineItems.length, ...kept, ...added);
                this.open = false;
                this.searched = false;
                this.items = [];
            },
        };
    }
</script>
@endonce
