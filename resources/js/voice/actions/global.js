/**
 * Actions available on every screen. `assistant` is the Alpine component instance.
 */
export function globalActions(assistant) {
    return {
        navigate({ to }) {
            if (to === 'back') {
                window.history.back();
                return { navigates: true };
            }
            const url = assistant.routes[to];
            if (!url) return { error: `I can't open ${String(to).replace(/_/g, ' ')} from here.` };
            window.location.assign(url);
            return { navigates: true };
        },

        show_help() {
            assistant.setOpen(true);
            assistant.showHelp = true;
            return {};
        },

        stop_listening() {
            assistant.stopAfterReply = true;
            return {};
        },

        continue_task(input) {
            assistant.noteContinuation(input || {});
            return {};
        },
    };
}
