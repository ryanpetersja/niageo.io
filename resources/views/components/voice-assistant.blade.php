@php
    $user = auth()->user();
    $voiceRoutes = array_filter([
        'interpret' => route('voice.interpret'),
        'dashboard' => route('dashboard'),
        'invoices' => route('invoices.index'),
        'new_invoice' => route('invoices.create'),
        'billing_plans' => route('billing-plans.index'),
        'products' => route('products.index'),
        'clients' => route('clients.index'),
        'reports' => route('reports.index'),
        'scopes' => route('scopes.index'),
        'monitoring' => route('uptime.index'),
        'subscriptions' => route('subscriptions.index'),
        'users' => $user?->can('manage-users') ? route('users.index') : null,
        'settings' => $user?->can('manage-settings') ? route('settings.branding') : null,
    ]);
@endphp
<div
    x-data="voiceAssistant"
    x-cloak
    data-voice-routes='@json($voiceRoutes)'
    data-voice-lang="{{ config('app.voice_lang', 'en-US') }}"
    data-voice-idle-seconds="{{ (int) config('services.anthropic.voice_idle_seconds', 15) }}"
>
    {{-- Docked assistant panel: sits beside the page content instead of over it --}}
    <aside x-show="open" class="voice-dock" role="complementary" aria-label="Voice assistant">
        <div class="flex items-center justify-between gap-2 px-3 py-2.5 border-b shrink-0" style="border-color: var(--border);">
            <div class="flex items-center gap-2 min-w-0 flex-1">
                <span class="voice-dot" :class="status"></span>
                <span class="text-sm font-semibold text-white whitespace-nowrap">Assistant</span>
                <span class="text-xs text-faint truncate" x-text="statusLabel"></span>
            </div>
            <div class="flex items-center gap-0.5 shrink-0">
                <button type="button" @click="showHelp = !showHelp" class="voice-icon-btn" :class="{ 'accent-ink': showHelp }" title="What can I say?">?</button>
                <button type="button" @click="toggleMute()" class="voice-icon-btn" :title="muted ? 'Unmute spoken replies' : 'Mute spoken replies'">
                    <svg x-show="!muted" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 5L6 9H2v6h4l5 4V5z"/><path d="M15.5 8.5a5 5 0 010 7"/><path d="M19 5a10 10 0 010 14"/></svg>
                    <svg x-show="muted" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 5L6 9H2v6h4l5 4V5z"/><path d="M23 9l-6 6"/><path d="M17 9l6 6"/></svg>
                </button>
                <button type="button" @click="clearConversation()" class="voice-icon-btn" title="Clear conversation">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/></svg>
                </button>
                <button type="button" @click="setOpen(false)" class="voice-icon-btn" title="Collapse the assistant">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg>
                </button>
            </div>
        </div>

        <div x-show="task" x-cloak class="px-3 py-2 border-b flex items-center justify-between gap-2 text-xs shrink-0" style="border-color: var(--border); background: var(--accent-soft);">
            <span class="accent-ink" x-text="taskLabel"></span>
            <button type="button" @click="cancelTask()" class="text-faint hover:underline">Cancel</button>
        </div>

        <div x-show="showHelp" class="px-3 py-3 border-b text-xs shrink-0 overflow-y-auto" style="border-color: var(--border); background: var(--surface-2); max-height: 45%;">
            <div class="font-semibold text-white mb-1">Things you can say on <span x-text="pageLabel"></span></div>
            <ul class="space-y-0.5 text-muted">
                <template x-for="example in examples" :key="example">
                    <li><button type="button" class="hover:underline text-left accent-ink" @click="send(example)" x-text="'“' + example + '”'"></button></li>
                </template>
            </ul>
            <div class="mt-2 text-faint">Also: “go to clients”, “what can I do here?”, “stop listening”. Shortcut: <span x-text="shortcutHint"></span>.</div>
            <div class="mt-1 text-faint">Steps can be strung together across screens: “open that invoice, cut every price by 15 percent, save it, then show me Acme's invoices”.</div>
            <div x-show="idleMs > 0" class="mt-1 text-faint">The mic switches itself off after <span x-text="Math.round(idleMs / 1000)"></span> seconds of silence.</div>
            <div class="mt-3 pt-3 border-t" style="border-color: var(--border);">
                <div class="font-semibold text-white mb-1">Wait after I stop talking</div>
                <div class="flex gap-1">
                    <template x-for="ms in pauseOptions" :key="ms">
                        <button type="button" @click="setPause(ms)" class="btn btn-sm" :class="pauseMs === ms ? 'btn-primary' : 'btn-secondary'" x-text="(ms / 1000) + 's'"></button>
                    </template>
                </div>
                <div class="mt-1 text-faint">Speech is sent only after this much silence, so you can pause mid-sentence. Tap the mic or “Send now” to send sooner.</div>
            </div>
        </div>

        <div x-ref="log" class="flex-1 min-h-0 overflow-y-auto px-3 py-3 space-y-2">
            <template x-if="log.length === 0">
                <div class="text-xs text-muted">
                    <span x-show="supported">Tap the mic and describe what you want, e.g. </span>
                    <span x-show="!supported">This browser has no speech recognition; type a command, e.g. </span>
                    <span class="accent-ink" x-text="examples[0] ? '“' + examples[0] + '”' : '“go to invoices”'"></span>
                </div>
            </template>
            <template x-for="(entry, index) in log" :key="index">
                <div class="voice-bubble" :class="'voice-bubble-' + entry.role" x-text="entry.text"></div>
            </template>
            <div x-show="dictation" x-cloak class="voice-bubble voice-bubble-dictation">
                <div x-text="dictation"></div>
                <template x-for="k in [countdownKey]" :key="k">
                    <div class="voice-countdown-track"><div class="voice-countdown" :style="'animation-duration:' + pauseMs + 'ms'"></div></div>
                </template>
                <div class="flex justify-between items-center mt-1 text-[11px] text-faint">
                    <span>Sending when you pause…</span>
                    <button type="button" @click="sendNow()" class="accent-ink hover:underline">Send now</button>
                </div>
            </div>
            <div x-show="status === 'thinking'" class="text-xs text-faint" x-text="task ? 'Working through your request…' : 'Working on it…'"></div>
        </div>

        <div class="border-t shrink-0 p-3 space-y-2" style="border-color: var(--border);">
            <form @submit.prevent="submitText()" class="flex items-center gap-2">
                <input x-model="input" @keydown.enter.prevent="submitText()" type="text" class="field text-sm" placeholder="Type a command…" autocomplete="off" aria-label="Command">
                <button type="submit" class="btn btn-secondary btn-sm" :disabled="busy">Send</button>
                <button
                    type="button"
                    @click="toggle()"
                    class="voice-mic voice-mic-sm"
                    :class="{ 'is-listening': status === 'listening', 'is-thinking': status === 'thinking', 'is-speaking': status === 'speaking', 'is-unsupported': !supported }"
                    :title="listening ? (dictation ? 'Send now' : 'Stop listening (Ctrl+Shift+Space)') : 'Start listening (Ctrl+Shift+Space)'"
                    :aria-pressed="listening ? 'true' : 'false'"
                    aria-label="Toggle voice assistant"
                >
                    <svg x-show="!listening" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 1a3 3 0 00-3 3v8a3 3 0 006 0V4a3 3 0 00-3-3z"/><path d="M19 10v2a7 7 0 01-14 0v-2"/><path d="M12 19v4"/><path d="M8 23h8"/></svg>
                    <svg x-show="listening" width="18" height="18" viewBox="0 0 24 24" fill="currentColor" stroke="none"><rect x="6" y="6" width="12" height="12" rx="2"/></svg>
                </button>
            </form>
            <div x-show="usageLabel" class="text-[11px] text-faint" x-text="usageLabel" title="Tokens sent to Claude in this browser session. Cached tokens are billed at about a tenth of the normal input price."></div>
        </div>
    </aside>

    {{-- Collapsed launcher --}}
    <div x-show="!open" class="fixed bottom-5 right-5 z-50 flex items-center gap-2">
        <button type="button" @click="setOpen(true)" class="btn btn-secondary btn-sm" title="Open the assistant">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
            Assistant
        </button>
        <button
            type="button"
            @click="setOpen(true); if (!listening) toggle()"
            class="voice-mic"
            :class="{ 'is-listening': status === 'listening', 'is-thinking': status === 'thinking', 'is-speaking': status === 'speaking', 'is-unsupported': !supported }"
            :title="listening ? 'Open the assistant' : 'Start listening (Ctrl+Shift+Space)'"
            aria-label="Open the voice assistant"
        >
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 1a3 3 0 00-3 3v8a3 3 0 006 0V4a3 3 0 00-3-3z"/><path d="M19 10v2a7 7 0 01-14 0v-2"/><path d="M12 19v4"/><path d="M8 23h8"/></svg>
        </button>
    </div>
</div>
