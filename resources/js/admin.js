/* Nothing of the kit or of the packages here: each compiles and ships its own
   script, which the kit lays in the pages it draws. */

import './app.js';
import { reloadOnARefusal } from './admin/refusals.js';
import { holdTheBarOverTheContent } from './admin/sidebar-over.js';

// Livewire may already be there; otherwise it announces its start.
if (window.Livewire) {
    reloadOnARefusal(window.Livewire);
} else {
    document.addEventListener('livewire:init', () => reloadOnARefusal(window.Livewire), { once: true });
}

holdTheBarOverTheContent();
