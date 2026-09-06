/**
 * Small DOM helpers shared by the voice page adapters.
 */

export function readJson(id) {
    const el = document.getElementById(id);
    if (!el) return {};
    try {
        return JSON.parse(el.textContent || '{}');
    } catch (e) {
        console.warn('[voice] could not parse', id, e);
        return {};
    }
}

/** Set an input/select/textarea value the way a user would (fires input + change). */
export function setValue(el, value) {
    if (!el) return false;
    if (el.tagName === 'SELECT') {
        const wanted = String(value ?? '');
        const option = [...el.options].find((o) => o.value === wanted)
            || [...el.options].find((o) => o.textContent.trim().toLowerCase() === wanted.toLowerCase());
        if (!option) return false;
        el.value = option.value;
    } else {
        el.value = value ?? '';
    }
    el.dispatchEvent(new Event('input', { bubbles: true }));
    el.dispatchEvent(new Event('change', { bubbles: true }));
    highlight(el);
    return true;
}

/** Briefly glow an element so the user can see what the assistant changed. */
export function highlight(el) {
    if (!el) return;
    el.classList.remove('voice-touched');
    // Force a reflow so the animation restarts when the same field changes twice.
    void el.offsetWidth;
    el.classList.add('voice-touched');
    el.scrollIntoView?.({ block: 'nearest', behavior: 'smooth' });
    setTimeout(() => el.classList.remove('voice-touched'), 1800);
}

/** Loose text match for spoken names ("ackme" ~ "Acme Corp"). */
export function normalize(text) {
    return String(text ?? '').toLowerCase().replace(/[^a-z0-9]+/g, ' ').trim();
}

export function money(value) {
    return '$' + Number(value || 0).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

export function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

/** Submit a form programmatically. `bypassHandlers` skips inline onsubmit confirm() dialogs. */
export function submitForm(form, { bypassHandlers = false } = {}) {
    if (!form) return { error: "I couldn't find that form on this screen." };
    if (!bypassHandlers && typeof form.checkValidity === 'function' && !form.checkValidity()) {
        form.reportValidity?.();
        const invalid = form.querySelector(':invalid');
        const label = invalid?.placeholder || invalid?.name || 'a required field';
        return { error: `The form isn't complete yet: ${label} needs a value.` };
    }
    if (bypassHandlers) {
        HTMLFormElement.prototype.submit.call(form);
    } else {
        form.requestSubmit ? form.requestSubmit() : form.submit();
    }
    return { navigates: true };
}
