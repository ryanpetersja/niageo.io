import { currentPage } from './pages/registry';
import { globalActions } from './actions/global';
import { createRecognizer, recognitionSupported, speak, stopSpeaking, synthesisSupported } from './speech';
import { csrfToken } from './dom';

const STORAGE_KEY = 'niageo.voice';
const YES = /^\s*(yes|yeah|yep|yup|sure|confirm(ed)?|go ahead|do it|correct|ok(ay)?|affirmative|proceed|please do|absolutely)\b/i;
const NO = /^\s*(no|nope|cancel|never ?mind|don'?t|do not|stop|abort|negative|forget it|leave it)\b/i;

function localDate() {
    const d = new Date();
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
}

/**
 * Alpine component behind the floating voice widget.
 *
 * One turn = transcript → POST /voice/interpret → actions executed on the current
 * page adapter → spoken reply → listen again (conversation mode). State that must
 * survive page loads (mode, history, a reply/actions to resume after a navigation)
 * lives in sessionStorage.
 */
export function voiceAssistant() {
    return {
        supported: recognitionSupported(),
        canSpeak: synthesisSupported(),
        open: false,
        listening: false,
        status: 'idle', // idle | listening | thinking | speaking
        interim: '',
        input: '',
        muted: false,
        showHelp: false,
        log: [],
        history: [],
        pending: null,
        page: null,
        routes: {},
        lang: 'en-US',
        busy: false,
        stopAfterReply: false,
        recognizer: null,
        recognizerActive: false,
        restartTimer: null,
        resumeState: null,

        get statusLabel() {
            return { idle: this.supported ? 'Mic off' : 'Text only', listening: 'Listening…', thinking: 'Working…', speaking: 'Speaking' }[this.status] || '';
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

        init() {
            // Livewire navigation can mount a new widget before the old one is torn down.
            this.instanceId = Date.now() + Math.random();
            window.__voiceAssistant?.teardown?.();
            window.__voiceAssistant = this;

            this.routes = JSON.parse(this.$el.dataset.voiceRoutes || '{}');
            this.lang = this.$el.dataset.voiceLang || 'en-US';
            const resume = this.restore();
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

        teardown() {
            if (window.__voiceAssistant?.instanceId === this.instanceId) window.__voiceAssistant = null;
            window.removeEventListener('keydown', this.keyHandler);
            window.removeEventListener('beforeunload', this.unloadHandler);
            this.persist();
            this.stopRecognizer();
        },

        /* ---------- persistence across page loads ---------- */

        restore() {
            try {
                const saved = JSON.parse(sessionStorage.getItem(STORAGE_KEY) || '{}');
                this.open = Boolean(saved.open);
                this.listening = Boolean(saved.listening) && this.supported;
                this.muted = Boolean(saved.muted);
                this.history = Array.isArray(saved.history) ? saved.history : [];
                this.log = Array.isArray(saved.log) ? saved.log : [];
                return saved.resume || null;
            } catch (e) {
                return null;
            }
        },

        persist() {
            try {
                sessionStorage.setItem(STORAGE_KEY, JSON.stringify({
                    open: this.open,
                    listening: this.listening,
                    muted: this.muted,
                    history: this.history.slice(-10),
                    log: this.log.slice(-12),
                    resume: this.resumeState,
                }));
            } catch (e) { /* storage unavailable */ }
        },

        /** Finish a turn that triggered a page load: run queued actions, announce the outcome, listen again. */
        async resume(resume) {
            if (!resume) {
                if (this.listening) this.startRecognizer();
                return;
            }
            this.resumeState = null;
            this.persist();
            this.open = true;
            this.status = 'thinking';

            const outcome = await this.runActions(resume.actions || [], resume.reply);
            if (outcome.navigated) return;

            const flash = this.readFlash();
            let message = outcome.error || resume.reply || '';
            let role = outcome.error ? 'system' : 'assistant';
            if (flash) {
                message = flash.text + (outcome.error ? ' ' + outcome.error : '');
                role = flash.type === 'error' ? 'system' : 'assistant';
            }
            if (message) {
                this.addLog(role, message);
                await this.say(message);
            }
            this.afterTurn();
        },

        readFlash() {
            const el = document.querySelector('[data-voice-flash]');
            const text = el?.textContent?.trim();
            return text ? { type: el.dataset.voiceFlash, text } : null;
        },

        /* ---------- microphone ---------- */

        toggle() {
            if (!this.supported) {
                this.open = true;
                this.addLog('system', "Voice input isn't supported in this browser. Type your commands below.");
                return;
            }
            this.listening ? this.stopListening() : this.startListening();
        },

        startListening() {
            this.listening = true;
            this.open = true;
            this.status = 'listening';
            stopSpeaking();
            this.startRecognizer();
            this.persist();
        },

        stopListening() {
            this.listening = false;
            this.status = 'idle';
            this.stopRecognizer();
            stopSpeaking();
            this.persist();
        },

        startRecognizer() {
            if (!this.supported || !this.listening) return;
            if (!this.recognizer) {
                this.recognizer = createRecognizer({
                    lang: this.lang,
                    onInterim: (text) => { this.interim = text; },
                    onFinal: (text) => { this.interim = ''; this.send(text); },
                    onEnd: () => {
                        this.recognizerActive = false;
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
            this.interim = '';
        },

        /* ---------- conversation ---------- */

        submitText() {
            const text = this.input.trim();
            this.input = '';
            if (text) this.send(text);
        },

        async send(rawText) {
            const text = String(rawText || '').trim();
            if (!text || this.busy) return;

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
                    this.addLog('assistant', 'Okay, cancelled.');
                    await this.say('Okay, cancelled.');
                    this.afterTurn();
                    return;
                }
            }

            this.busy = true;
            this.status = 'thinking';
            this.stopRecognizer();
            stopSpeaking();
            this.page = currentPage();

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
                    }),
                });
                data = await response.json().catch(() => ({}));
                if (!response.ok) throw new Error(data.reply || data.message || `Request failed (${response.status})`);
            } catch (error) {
                this.busy = false;
                const message = error.message || 'Something went wrong.';
                this.addLog('system', message);
                await this.say(message);
                this.afterTurn();
                return;
            }
            this.busy = false;

            this.pushHistory('user', text);
            this.pushHistory('assistant', (data.reply || '') + this.summarize(data.actions));

            if (data.confirm?.prompt) {
                this.pending = { actions: data.actions || [], reply: data.reply || 'Done.' };
                this.addLog('assistant', data.confirm.prompt);
                await this.say(data.confirm.prompt);
                this.afterTurn();
                return;
            }

            await this.performTurn(data.actions || [], data.reply || 'Done.');
        },

        /** Execute actions, then speak the outcome (unless a page load will do it after reload). */
        async performTurn(actions, reply) {
            this.busy = true;
            this.status = 'thinking';
            this.stopRecognizer();
            const outcome = await this.runActions(actions, reply);
            this.busy = false;
            if (outcome.navigated) {
                // If the page is still here after a while, the navigation didn't happen (e.g. a download).
                setTimeout(() => {
                    if (this.resumeState) {
                        this.resumeState = null;
                        this.persist();
                        this.afterTurn();
                    }
                }, 8000);
                return;
            }
            const message = outcome.error || reply;
            this.addLog(outcome.error ? 'system' : 'assistant', message);
            await this.say(message);
            this.afterTurn();
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
                    this.resumeState = { actions: actions.slice(i + 1), reply };
                    this.persist();
                    return { navigated: true };
                }
            }
            return { done: true };
        },

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
            if (this.listening) this.startRecognizer();
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
