</main>
</div>

<!-- FLATPICKR: Load CSS + JS + Locale + Init -->
<link rel="stylesheet" href="/assets/css/flatpickr.min.css">
<script src="/assets/js/flatpickr.min.js"></script>
<script src="/assets/js/vn.js"></script>

<script>
    // 1. Đăng ký locale ngay sau khi load
    if (window.flatpickr && window.flatpickr.l10ns && window.flatpickr.l10ns.vn) {
        flatpickr.localize(flatpickr.l10ns.vn);
    }

    // 2. Hàm __initFlatpickr cho filter popover
    window.__initFlatpickr = function (rootEl) {
        if (!window.flatpickr) return;

        rootEl.querySelectorAll('input.flatpickr:not([data-fp])').forEach(el => {
            flatpickr(el, {
                altInput: true,
                altFormat: "d/m/Y",
                dateFormat: "Y-m-d",
                locale: "vn",
                onChange: function (selectedDates, dateStr, instance) {
                    const parent = instance.input.closest('[x-show]');
                    if (!parent) return;

                    const fromInput = parent.querySelector('input[x-model*="_from"]');
                    const toInput = parent.querySelector('input[x-model*="_to"]');

                    if (fromInput && toInput) {
                        const fromPicker = fromInput._flatpickr;
                        const toPicker = toInput._flatpickr;
                        if (fromPicker && toPicker) {
                            if (fromPicker.selectedDates[0] && toPicker.selectedDates[0]) {
                                const from = fromPicker.selectedDates[0];
                                const to = toPicker.selectedDates[0];
                                if (from > to) toPicker.clear();
                            }
                            if (fromPicker.selectedDates[0]) {
                                toPicker.set('minDate', fromPicker.selectedDates[0]);
                            }
                            if (toPicker.selectedDates[0]) {
                                fromPicker.set('maxDate', toPicker.selectedDates[0]);
                            }
                        }
                    }
                }
            });
            el.setAttribute('data-fp', '1');
        });
    };

    // 3. Hàm openFlatpickr (click icon)
    window.openFlatpickr = function (el) {
        try {
            const parent = el.closest('div');
            const input = parent?.querySelector('input.flatpickr');
            if (!input) return;

            if (input._flatpickr) input._flatpickr.open();
            else {
                input.focus();
                setTimeout(() => input._flatpickr?.open(), 200);
            }
        } catch (e) {
            console.debug('Cannot open Flatpickr:', e);
        }
    };

    // 4. Auto init cho date_of_birth (profile page)
    document.addEventListener('DOMContentLoaded', function () {
        const dobInput = document.querySelector("input[name='date_of_birth']");
        if (dobInput && !dobInput._flatpickr) {
            flatpickr(dobInput, {
                dateFormat: "d/m/Y",
                locale: "vn",
                allowInput: true,
                maxDate: "today"
            });
        }
    });
</script>

</body>

</html>