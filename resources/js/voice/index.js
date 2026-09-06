import { voiceAssistant } from './assistant';
import './pages/invoices-index';
import './pages/invoice-form';
import './pages/invoice-show';

// Livewire bundles Alpine and fires alpine:init before starting it.
document.addEventListener('alpine:init', () => {
    window.Alpine.data('voiceAssistant', voiceAssistant);
});
