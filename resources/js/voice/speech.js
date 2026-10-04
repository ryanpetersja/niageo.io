/**
 * Thin wrappers around the browser speech APIs (Web Speech recognition + speech synthesis).
 * No external services: recognition runs through the browser (Chrome/Edge/Safari).
 */

export function recognitionSupported() {
    return Boolean(window.SpeechRecognition || window.webkitSpeechRecognition);
}

const normalise = (text) => text.toLowerCase().replace(/[^\p{L}\p{N}\s]/gu, '').replace(/\s+/g, ' ').trim();

/**
 * Collapse a session's result list into { final, interim } text.
 *
 * Desktop Chrome reports one result per phrase. Android Chrome (continuous mode) instead adds a new
 * result for every partial, each repeating the whole utterance so far and often already marked final
 * ("you find all", "can you find all", "can you find all the" …). Appending those produced a stutter,
 * so a result that contains the previous one (or is contained by it) replaces it instead.
 */
export function collapseResults(results) {
    const segments = [];
    for (let i = 0; i < results.length; i++) {
        const text = results[i][0]?.transcript?.trim();
        if (!text) continue;
        const isFinal = Boolean(results[i].isFinal);
        const last = segments[segments.length - 1];
        if (last) {
            const current = normalise(text);
            const previous = normalise(last.text);
            if (current.includes(previous)) {
                segments[segments.length - 1] = { text, isFinal };
                continue;
            }
            if (previous.includes(current)) {
                last.isFinal = last.isFinal || isFinal;
                continue;
            }
        }
        segments.push({ text, isFinal });
    }
    const join = (list) => list.map((s) => s.text).join(' ').trim();
    return { final: join(segments.filter((s) => s.isFinal)), interim: join(segments.filter((s) => !s.isFinal)) };
}

/**
 * onResult receives the whole current session's transcript every time ({ final, interim }),
 * so callers replace rather than append within a session.
 */
export function createRecognizer({ lang = 'en-US', onResult, onEnd, onError }) {
    const Recognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    if (!Recognition) return null;

    const recognizer = new Recognition();
    recognizer.lang = lang;
    recognizer.interimResults = true;
    // Continuous: the session survives the user's natural pauses; the widget decides when a command is complete.
    recognizer.continuous = true;
    recognizer.maxAlternatives = 1;

    recognizer.onresult = (event) => onResult?.(collapseResults(event.results));
    recognizer.onerror = (event) => onError?.(event.error);
    recognizer.onend = () => onEnd?.();

    return recognizer;
}

let cachedVoice = null;

function pickVoice() {
    if (!('speechSynthesis' in window)) return null;
    if (cachedVoice) return cachedVoice;
    const voices = window.speechSynthesis.getVoices();
    if (!voices.length) return null;
    const preferred = ['Samantha', 'Google US English', 'Karen', 'Daniel', 'Microsoft Aria', 'Moira'];
    for (const name of preferred) {
        const match = voices.find((v) => v.name.includes(name));
        if (match) return (cachedVoice = match);
    }
    return (cachedVoice = voices.find((v) => v.lang?.startsWith('en')) || voices[0]);
}

if ('speechSynthesis' in window) {
    window.speechSynthesis.addEventListener?.('voiceschanged', () => { cachedVoice = null; });
}

export function synthesisSupported() {
    return 'speechSynthesis' in window && 'SpeechSynthesisUtterance' in window;
}

/** Speak text aloud; resolves when speech ends (or immediately when unsupported). */
export function speak(text, { rate = 1.05 } = {}) {
    return new Promise((resolve) => {
        if (!synthesisSupported() || !text) {
            resolve();
            return;
        }
        const synth = window.speechSynthesis;
        synth.cancel();
        const utterance = new SpeechSynthesisUtterance(text);
        const voice = pickVoice();
        if (voice) utterance.voice = voice;
        utterance.lang = voice?.lang || 'en-US';
        utterance.rate = rate;
        let done = false;
        const finish = () => { if (!done) { done = true; resolve(); } };
        utterance.onend = finish;
        utterance.onerror = finish;
        synth.speak(utterance);
        // Safety net: some browsers never fire onend for cancelled utterances.
        setTimeout(finish, Math.min(30000, 1500 + text.length * 90));
    });
}

export function stopSpeaking() {
    if (synthesisSupported()) window.speechSynthesis.cancel();
}
