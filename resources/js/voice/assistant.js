import { currentPage } from './pages/registry';
import { globalActions } from './actions/global';
import { createRecognizer, recognitionSupported, speak, stopSpeaking, synthesisSupported } from './speech';
import { csrfToken } from './dom';

const STORAGE_KEY = 'niageo.voice';
const PREFS_KEY = 'niageo.voice.prefs';
const YES = /^\s*(yes|yeah|yep|yup|sure|confirm(ed)?|go ahead|do it|correct|ok(ay)?|affirmative|proceed|please do|absolutely)\b/i;
const NO = /^\s*(no|nope|cancel|never ?mind|don'?t|do not|stop|abort|negative|forget it|leave it)\b/i;
const PAUSE_OPTIONS = [2000, 3000, 5000, 8000];
const DEFAULT_PAUSE_MS = 3000;
const MAX_TASK_SCREENS = 8;

function formatTokens(n) {
    return n >= 1000 ? `${(n / 1000).toFixed(n >= 10000 ? 0 : 1).replace(/\.0$/, '')}k` : String(n);
}

function localDate() {
    const d = new Date();
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
}

function loadPrefs() {
    try {
        return JSON.parse(localStorage.getItem(PREFS_KEY) || '{}') || {};
    } catch (e) {
        return {};
    }
}

/**
 * Alpine component behind the floating voice widget.
 *
 * Listening: the microphone stays open through natural pauses. Speech accumulates
 * and is only sent once the user has been quiet for `pauseMs` (a visible countdown),
 * or when they tap "Send now" / the mic.
 *
 * One turn = transcript → POST /voice/interpret → actions on the current page →
 * spoken reply → listen again. Multi-step requests that span screens are carried
 * as a `task` (original request + the planner's note of what remains); after each
 * navigation the widget resumes queued actions and asks the planner to continue.
 * State that must survive page loads lives in sessionStorage.
 */
export function voiceAssistant() {
    return {
        supported: recognitionSupported(),
        canSpeak: synthesisSupported(),
        open: false,
        listening: false,
        status: 'idle', // idle | listening | thinking | speaking
        input: '',
        muted: false,
        showHelp: false,
        log: [],
        history: [],
        pending: null, // confirmation awaiting yes/no: { actions, reply }
        page: null,
        routes: {},
        lang: 'en-US',
        busy: false,
        stopAfterReply: false,
        recognizer: null,
        recognizerActive: false,
        restartTimer: null,
        resumeState: null,
        // patient listening
        pauseMs: DEFAULT_PAUSE_MS,
        pauseTimer: null,
        pendingText: '', // finalised speech from earlier recognition sessions, not yet sent
        sessionText: '', // finalised speech in the current recognition session
        liveText: '', // words still being recognised
        countdownKey: 0,
        flushRequested: false,
        // idle auto-off
        idleMs: 15000,
        idleTimer: null,
        lastActivity: 0,
        // usage
        usage: { commands: 0, input: 0, output: 0, cached: 0 },
        budget: null,
        // multi-step request in progress
        task: null, // { original, remaining, step, done: [] }
        currentTranscript: '',
        currentReply: '',

        /* ---------- derived ---------- */

        get statusLabel() {
            if (this.status === 'listening') return this.dictation ? 'Listening… pause to send' : 'Listening…';
            return { idle: this.supported ? 'Mic off' : 'Text only', thinking: this.task ? 'Working through your request…' : 'Working…', speaking: 'Speaking' }[this.status] || '';
        },

        get dictation() {
            return [this.pendingText, this.sessionText, this.liveText].filter(Boolean).join(' ').trim();
        },

        get examples() {
            return this.page?.examples || [];
        },

        get pageLabel() {
            return this.page?.label || 'this screen';
        },

        get shortcutHint() {
            return 'Ctrl+Shift+Space';
        },

        get pauseOptions() {
            return PAUSE_OPTIONS;
        },

        get taskLabel() {
            return this.task ? `Multi-step request · screen ${this.task.step + 1}` : '';
        },

        /** Session token summary shown under the input, e.g. "12 commands · 41k tokens · 78% cached". */
        get usageLabel() {
            const u = this.usage;
            if (!u.commands) return '';
            const promptTokens = u.input + u.cached;
            const total = promptTokens + u.output;
            const cachedPct = promptTokens ? Math.round((u.cached / promptTokens) * 100) : 0;
            const budget = this.budget && this.budget.percent !== null ? ` · budget ${this.budget.percent}% used` : '';
            return `${u.commands} command${u.commands === 1 ? '' : 's'} · ${formatTokens(total)} tokens · ${cachedPct}% cached${budget}`;
        },

        /* ---------- lifecycle ---------- */

        init() {
            // Livewire navigation can mount a new widget before the old one is torn down.
            this.instanceId = Date.now() + Math.random();
            window.__voiceAssistant?.teardown?.();
            window.__voiceAssistant = this;

            this.routes = JSON.parse(this.$el.dataset.voiceRoutes || '{}');
            this.lang = this.$el.dataset.voiceLang || 'en-US';
            this.idleMs = Math.max(0, Number(this.$el.dataset.voiceIdleSeconds ?? 15) || 0) * 1000;
            const prefs = loadPrefs();
            if (PAUSE_OPTIONS.includes(Number(prefs.pauseMs))) this.pauseMs = Number(prefs.pauseMs);

            const resume = this.restore();
            // Docked by default on wide screens; the user's last choice wins afterwards.
            this.open = typeof prefs.open === 'boolean' ? prefs.open : window.innerWidth >= 1280;
            this.applyDock();
            this.$watch('open', () => this.applyDock());
            this.page = currentPage();

            this.keyHandler = (event) => {
                if (event.ctrlKey && event.shiftKey && event.code === 'Space') {
                    event.preventDefault();
                    this.toggle();
                }
            };
            window.addEventListener('keydown', this.keyHandler);
            this.unloadHandler = () => this.persist();
            window.addEventListener('beforeunload', this.unloadHandler);
            this.$watch('log', () => this.$nextTick(() => this.scrollLog()));

            this.resume(resume);
        },

        destroy() {
            this.teardown();
        },

        setOpen(open) {
            this.open = Boolean(open);
            try { localStorage.setItem(PREFS_KEY, JSON.stringify({ ...loadPrefs(), open: this.open })); } catch (e) { /* ignore */ }
        },

        /** Push the page content aside while the panel is docked. */
        applyDock() {
            document.body.classList.toggle('voice-docked', this.open);
        },

        teardown() {
            if (window.__voiceAssistant?.instanceId === this.instanceId) window.__voiceAssistant = null;
            window.removeEventListener('keydown', this.keyHandler);
            window.removeEventListener('beforeunload', this.unloadHandler);
            clearTimeout(this.idleTimer);
            clearTimeout(this.pauseTimer);
            this.persist();
            this.stopRecognizer();
            document.body.classList.remove('voice-docked');
        },

        /* ---------- persistence across page loads ---------- */

        restore() {
            try {
                const saved = JSON.parse(sessionStorage.getItem(STORAGE_KEY) || '{}');
                this.listening = Boolean(saved.listening) && this.supported;
                this.muted = Boolean(saved.muted);
                this.history = Array.isArray(saved.history) ? saved.history : [];
                this.log = Array.isArray(saved.log) ? saved.log : [];
                this.task = saved.task && typeof saved.task === 'object' ? saved.task : null;
                if (saved.usage && typeof saved.usage === 'object') {
                    this.usage = { commands: 0, input: 0, output: 0, cached: 0, ...saved.usage };
                }
                return saved.resume || null;
            } catch (e) {
                return null;
            }
        },

        persist() {
            try {
                sessionStorage.setItem(STORAGE_KEY, JSON.stringify({
                    listening: this.listening,
                    muted: this.muted,
                    history: this.history.slice(-10),
                    log: this.log.slice(-12),
                    resume: this.resumeState,
                    usage: this.usage,
                    task: this.task,
                }));
            } catch (e) { /* storage unavailable */ }
        },

        setPause(ms) {
            this.pauseMs = ms;
            try { localStorage.setItem(PREFS_KEY, JSON.stringify({ ...loadPrefs(), pauseMs: ms })); } catch (e) { /* ignore */ }
            if (this.dictation) this.armPauseTimer();
        },

        /** Finish a turn that triggered a page load: run queued actions, announce the outcome, then continue or listen. */
        async resume(resume) {
            if (!resume) {
                // A page opened by hand while a request was in progress: the request is abandoned.
                if (this.task) {
                    this.task = null;
                    this.persist();
                }
                if (this.listening) {
                    this.touchActivity();
                    this.startRecognizer();
                }
                return;
            }
            this.resumeState = null;
            this.persist();
            this.status = 'thinking';
            // Queued actions may include continue_task, which needs the request that started all this.
            this.currentTranscript = resume.transcript || this.lastUserText();
            this.currentReply = resume.reply || '';

            const outcome = await this.runActions(resume.actions || [], resume.reply);
            if (outcome.navigated) return;

            const flash = this.readFlash();
            let message = outcome.error || resume.reply || '';
            let role = outcome.error ? 'system' : 'assistant';
            if (flash) {
                message = flash.text + (outcome.error ? ' ' + outcome.error : '');
                role = flash.type === 'error' ? 'system' : 'assistant';
            }
            if (outcome.error || flash?.type === 'error') {
                // Something went wrong mid-request: stop rather than carry on blindly.
                this.task = null;
                this.persist();
            }
            if (message) {
                this.addLog(role, message);
                await this.say(message);
            }
            this.nextStep();
        },

        readFlash() {
            const el = document.querySelector('[data-voice-flash]');
            const text = el?.textContent?.trim();
            return text ? { type: el.dataset.voiceFlash, text } : null;
        },

        /* ---------- microphone ---------- */

        toggle() {
            if (!this.supported) {
                this.setOpen(true);
                this.addLog('system', "Voice input isn't supported in this browser. Type your commands below.");
                return;
            }
            // Tapping the mic while dictating means "I'm done, send it".
            if (this.listening && this.dictation) {
                this.sendNow();
                return;
            }
            this.listening ? this.stopListening() : this.startListening();
        },

        startListening() {
            this.listening = true;
            this.status = 'listening';
            stopSpeaking();
            this.touchActivity();
            this.startRecognizer();
            this.persist();
        },

        stopListening() {
            this.listening = false;
            this.status = 'idle';
            clearTimeout(this.idleTimer);
            clearTimeout(this.pauseTimer);
            this.pendingText = '';
            this.sessionText = '';
            this.liveText = '';
            this.flushRequested = false;
            this.stopRecognizer();
            stopSpeaking();
            this.persist();
        },

        startRecognizer() {
            if (!this.supported || !this.listening) return;
            if (!this.recognizer) {
                this.recognizer = createRecognizer({
                    lang: this.lang,
                    onResult: (result) => this.handleSpeech(result),
                    onEnd: () => {
                        this.recognizerActive = false;
                        this.commitSession();
                        if (this.flushRequested) {
                            this.flushRequested = false;
                            this.flushTranscript();
                            return;
                        }
                        if (this.listening && this.status === 'listening') this.scheduleRestart();
                    },
                    onError: (error) => {
                        this.recognizerActive = false;
                        if (error === 'not-allowed' || error === 'service-not-allowed') {
                            this.listening = false;
                            this.status = 'idle';
                            this.addLog('system', 'Microphone access was blocked. Allow the microphone for this site, or type commands below.');
                            this.persist();
                        } else if (error !== 'no-speech' && error !== 'aborted') {
                            console.warn('[voice] recognition error:', error);
                        }
                    },
                });
            }
            if (this.recognizerActive) return;
            try {
                this.recognizer.start();
                this.recognizerActive = true;
                this.status = 'listening';
            } catch (e) {
                // Chrome throws when a session is already running; treat it as active.
                this.recognizerActive = true;
            }
        },

        scheduleRestart() {
            clearTimeout(this.restartTimer);
            this.restartTimer = setTimeout(() => this.startRecognizer(), 300);
        },

        stopRecognizer() {
            clearTimeout(this.restartTimer);
            if (this.recognizer && this.recognizerActive) {
                try { this.recognizer.abort(); } catch (e) { /* already stopped */ }
            }
            this.recognizerActive = false;
            this.sessionText = '';
            this.liveText = '';
        },

        /* ---------- patient listening: send after a pause, not mid-sentence ---------- */

        handleSpeech({ interim, final }) {
            if (this.status !== 'listening') return;
            this.lastActivity = Date.now();
            // Each result carries the whole session so far, so replace instead of appending.
            this.sessionText = final;
            this.liveText = interim;
            this.armPauseTimer();
        },

        /** A recognition session ended: keep its finalised words before the next session starts afresh. */
        commitSession() {
            this.pendingText = [this.pendingText, this.sessionText].filter(Boolean).join(' ').trim();
            this.sessionText = '';
        },

        armPauseTimer() {
            clearTimeout(this.pauseTimer);
            this.countdownKey++;
            this.pauseTimer = setTimeout(() => this.onPauseElapsed(), this.pauseMs);
        },

        onPauseElapsed() {
            if (this.status !== 'listening') return;
            if (this.pendingText || this.sessionText) {
                this.flushTranscript();
                return;
            }
            if (this.liveText) {
                // The browser has not finalised the last words yet: end the session to force it, then send.
                this.flushRequested = true;
                try {
                    this.recognizer?.stop();
                } catch (e) {
                    this.flushRequested = false;
                    this.flushTranscript();
                }
            }
        },

        flushTranscript() {
            clearTimeout(this.pauseTimer);
            const text = [this.pendingText, this.sessionText, this.liveText].filter(Boolean).join(' ').trim();
            this.pendingText = '';
            this.sessionText = '';
            this.liveText = '';
            this.countdownKey++;
            if (text) this.send(text);
        },

        sendNow() {
            if (!this.dictation) return;
            this.flushRequested = false;
            this.flushTranscript();
        },

        /* ---------- idle auto-off ---------- */

        touchActivity() {
            this.lastActivity = Date.now();
            this.armIdleTimer();
        },

        armIdleTimer() {
            clearTimeout(this.idleTimer);
            if (!this.idleMs || !this.listening) return;
            const remaining = Math.max(250, this.idleMs - (Date.now() - this.lastActivity));
            this.idleTimer = setTimeout(() => this.checkIdle(), remaining);
        },

        checkIdle() {
            if (!this.listening || !this.idleMs) return;
            if (this.status !== 'listening' || this.dictation) {
                this.idleTimer = setTimeout(() => this.checkIdle(), 1000);
                return;
            }
            if (Date.now() - this.lastActivity < this.idleMs) {
                this.armIdleTimer();
                return;
            }
            this.stopListening();
            this.addLog('system', `Mic switched off after ${Math.round(this.idleMs / 1000)} seconds of silence. Tap the mic or press ${this.shortcutHint} to resume.`);
        },

        /* ---------- conversation ---------- */

        submitText() {
            const text = this.input.trim();
            this.input = '';
            if (text) this.send(text);
        },

        /** The most recent thing the user actually said (continuation turns excluded). */
        lastUserText() {
            const entry = [...this.history].reverse().find((h) => h.role === 'user' && !h.text.startsWith('(continuing on '));
            return entry ? entry.text : '';
        },

        /**
         * Send one command. With `task`, this is the planner continuing a multi-step request
         * on a new screen: the original request is resent with the note of what remains.
         * Resolves to false when nothing was sent.
         */
        async send(rawText, { task = null } = {}) {
            const text = String(rawText || '').trim();
            if (!text || this.busy) return false;

            if (!task) {
                this.addLog('user', text);
                this.showHelp = false;

                if (this.pending) {
                    const pending = this.pending;
                    this.pending = null;
                    if (YES.test(text)) {
                        await this.performTurn(pending.actions, pending.reply);
                        return;
                    }
                    if (NO.test(text)) {
                        this.task = null;
                        this.addLog('assistant', 'Okay, cancelled.');
                        await this.say('Okay, cancelled.');
                        this.afterTurn();
                        return;
                    }
                }
                // A fresh command replaces any request still in progress.
                this.task = null;
            }

            this.busy = true;
            this.status = 'thinking';
            this.stopRecognizer();
            stopSpeaking();
            this.page = currentPage();
            this.currentTranscript = text;

            let data;
            try {
                const response = await fetch(this.routes.interpret, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': csrfToken(),
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({
                        transcript: text,
                        page: { name: this.page.name, context: this.page.context(), today: localDate() },
                        history: this.history.slice(-10),
                        task: task ? { original: task.original, remaining: task.remaining, step: task.step, done: task.done } : null,
                    }),
                });
                data = await response.json().catch(() => ({}));
                if (!response.ok) throw new Error(data.reply || data.message || `Request failed (${response.status})`);
            } catch (error) {
                this.busy = false;
                this.task = null;
                const message = error.message || 'Something went wrong.';
                this.addLog('system', message);
                await this.say(message);
                this.afterTurn();
                return true;
            }
            this.busy = false;

            // The request was cancelled while the planner was thinking.
            if (task && this.task !== task) {
                this.afterTurn();
                return true;
            }

            this.recordUsage(data.usage);
            if (data.budget !== undefined) this.budget = data.budget;
            this.pushHistory('user', task ? `(continuing on ${this.pageLabel}: ${task.remaining})` : text);
            this.pushHistory('assistant', (data.reply || '') + this.summarize(data.actions));

            const actions = data.actions || [];
            const continues = actions.some((a) => a.name === 'continue_task');
            const progress = actions.some((a) => a.name !== 'continue_task');

            // A response without continue_task means the request is complete (or was never multi-step).
            if (!continues) {
                this.task = null;
                this.persist();
            } else if (task && !progress) {
                // The planner only re-noted what remains without doing anything: stop rather than loop.
                this.task = null;
                this.persist();
                const message = data.reply || "I couldn't make progress on the rest of that request.";
                this.addLog('system', message);
                await this.say(message);
                this.afterTurn();
                return true;
            }

            if (data.confirm?.prompt) {
                this.pending = { actions, reply: data.reply || 'Done.' };
                this.addLog('assistant', data.confirm.prompt);
                await this.say(data.confirm.prompt);
                this.afterTurn();
                return true;
            }

            await this.performTurn(actions, data.reply || 'Done.');
            return true;
        },

        /** Execute actions, then speak the outcome (unless a page load will do it after reload). */
        async performTurn(actions, reply) {
            this.busy = true;
            this.status = 'thinking';
            this.stopRecognizer();
            this.currentReply = reply;
            const outcome = await this.runActions(actions, reply);
            this.busy = false;
            if (outcome.navigated) {
                // If the page is still here after a while, the navigation didn't happen (e.g. a download).
                setTimeout(() => {
                    if (this.resumeState) {
                        this.resumeState = null;
                        this.persist();
                        this.nextStep();
                    }
                }, 8000);
                return;
            }
            if (outcome.error) {
                this.task = null;
                this.persist();
            }
            const message = outcome.error || reply;
            this.addLog(outcome.error ? 'system' : 'assistant', message);
            await this.say(message);
            this.nextStep();
        },

        async runActions(actions, reply) {
            const handlers = { ...globalActions(this), ...(this.page?.actions || {}) };
            for (let i = 0; i < actions.length; i++) {
                const action = actions[i];
                const handler = handlers[action.name];
                if (!handler) return { error: `I can't ${String(action.name).replace(/_/g, ' ')} on this screen.` };
                let result;
                try {
                    result = (await handler(action.input || {})) || {};
                } catch (error) {
                    console.error('[voice] action failed', action, error);
                    return { error: 'Something went wrong while doing that.' };
                }
                if (result.error) return { error: result.error };
                if (result.navigates) {
                    this.resumeState = { actions: actions.slice(i + 1), reply, transcript: this.currentTranscript };
                    this.persist();
                    return { navigated: true };
                }
            }
            return { done: true };
        },

        /* ---------- multi-step requests ---------- */

        /** Called by the continue_task action: remember what remains for the next screen. */
        noteContinuation({ remaining, done }) {
            const previous = this.task;
            this.task = {
                original: previous?.original || this.currentTranscript || this.lastUserText(),
                remaining: String(remaining || ''),
                step: (previous?.step || 0) + 1,
                done: [...(previous?.done || []), done || this.currentReply].filter(Boolean).slice(-10),
            };
            this.persist();
        },

        /** After a turn finishes on this screen: continue the request here, or go back to listening. */
        nextStep() {
            if (this.task && !this.pending) {
                this.continueTask();
                return;
            }
            this.afterTurn();
        },

        async continueTask() {
            const task = this.task;
            if (!task) {
                this.afterTurn();
                return;
            }
            if (task.step >= MAX_TASK_SCREENS) {
                this.task = null;
                this.persist();
                const message = `I stopped after ${MAX_TASK_SCREENS} screens. Tell me what to do next.`;
                this.addLog('system', message);
                await this.say(message);
                this.afterTurn();
                return;
            }
            if (!task.original) {
                this.task = null;
                this.persist();
                this.addLog('system', "I lost track of the original request, so I stopped. Please say the rest again.");
                this.afterTurn();
                return;
            }
            this.addLog('system', `Continuing: ${task.remaining}`);
            const sent = await this.send(task.original, { task });
            if (sent === false) this.afterTurn();
        },

        cancelTask() {
            this.task = null;
            this.pending = null;
            this.persist();
            this.addLog('system', 'Cancelled the multi-step request.');
            if (!this.busy) this.afterTurn();
        },

        /* ---------- output ---------- */

        async say(text) {
            if (!text || this.muted || !this.canSpeak) return;
            this.status = 'speaking';
            this.stopRecognizer();
            await speak(text);
        },

        afterTurn() {
            if (this.stopAfterReply) {
                this.stopAfterReply = false;
                this.stopListening();
                return;
            }
            this.status = this.listening ? 'listening' : 'idle';
            if (this.listening) {
                this.touchActivity();
                this.startRecognizer();
            }
        },

        recordUsage(usage) {
            if (!usage || typeof usage !== 'object') return;
            this.usage.commands += 1;
            // Cache writes are billed at full price or more, so they count as uncached input.
            this.usage.input += Number(usage.input || 0) + Number(usage.cache_write || 0);
            this.usage.cached += Number(usage.cache_read || 0);
            this.usage.output += Number(usage.output || 0);
            this.persist();
        },

        summarize(actions) {
            if (!Array.isArray(actions) || !actions.length) return '';
            const parts = actions.map((a) => `${a.name} ${JSON.stringify(a.input || {})}`).join('; ');
            return ` [actions: ${parts.slice(0, 300)}]`;
        },

        pushHistory(role, text) {
            this.history.push({ role, text: String(text).slice(0, 1500) });
            this.history = this.history.slice(-10);
            this.persist();
        },

        addLog(role, text) {
            this.log.push({ role, text });
            this.log = this.log.slice(-12);
            this.persist();
        },

        clearConversation() {
            this.log = [];
            this.history = [];
            this.pending = null;
            this.task = null;
            this.usage = { commands: 0, input: 0, output: 0, cached: 0 };
            this.persist();
        },

        toggleMute() {
            this.muted = !this.muted;
            if (this.muted) stopSpeaking();
            this.persist();
        },

        scrollLog() {
            const el = this.$refs.log;
            if (el) el.scrollTop = el.scrollHeight;
        },
    };
}
