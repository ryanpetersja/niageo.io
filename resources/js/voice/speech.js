/**
 * Thin wrappers around the browser speech APIs (Web Speech recognition + speech synthesis).
 * No external services: recognition runs through the browser (Chrome/Edge/Safari).
 */

export function recognitionSupported() {
    return Boolean(window.SpeechRecognition || window.webkitSpeechRecognition);
}

export function createRecognizer({ lang = 'en-US', onInterim, onFinal, onEnd, onError }) {
    const Recognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    if (!Recognition) return null;

    const recognizer = new Recognition();
    recognizer.lang = lang;
    recognizer.interimResults = true;
    recognizer.continuous = false;
    recognizer.maxAlternatives = 1;

    recognizer.onresult = (event) => {
        let interim = '';
        let finalText = '';
        for (let i = event.resultIndex; i < event.results.length; i++) {
            const result = event.results[i];
            if (result.isFinal) finalText += result[0].transcript;
            else interim += result[0].transcript;
        }
        if (interim) onInterim?.(interim);
        if (finalText.trim()) onFinal?.(finalText.trim());
    };
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
