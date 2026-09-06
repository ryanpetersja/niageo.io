/**
 * Page adapters: each screen with voice control registers its name, example commands,
 * a context() snapshot for the model, and the action handlers the model may call.
 * Handlers return {} when done, { navigates: true } when they trigger a page load,
 * or { error: 'spoken message' } when the action cannot be performed.
 */
const pages = {};

export function registerPage(name, adapter) {
    pages[name] = { name, examples: [], context: () => ({}), actions: {}, ...adapter };
}

export function currentPageName() {
    return document.querySelector('[data-voice-page]')?.getAttribute('data-voice-page') || '';
}

export function currentPage() {
    const name = currentPageName();
    return pages[name] || {
        name: name || 'unknown',
        examples: ['Go to invoices', 'Open the dashboard', 'Stop listening'],
        context: () => ({}),
        actions: {},
    };
}
