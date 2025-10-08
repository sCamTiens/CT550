// Flatpickr loader for profile page
import flatpickr from "https://cdn.jsdelivr.net/npm/flatpickr";
import { Vietnamese } from './flatpickr-vi.js';

flatpickr.localize(Vietnamese);

flatpickr("input[name='date_of_birth']", {
    dateFormat: "d/m/Y",
    locale: Vietnamese,
    allowInput: true,
    maxDate: "today",
    defaultDate: document.querySelector("input[name='date_of_birth']").value || undefined,
});
