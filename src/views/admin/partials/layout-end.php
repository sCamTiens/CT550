</main>
</div>

<!-- Flatpickr CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

<!-- Flatpickr core -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<!-- Locale tiếng Việt -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/vn.js"></script>

<!-- Khởi tạo Flatpickr -->
<script>
    window.__initFlatpickr = function (rootEl) {
        if (!window.flatpickr) return;

        const pickers = [];

        rootEl.querySelectorAll('input.flatpickr:not([data-fp])').forEach(el => {
            const fp = flatpickr(el, {
                altInput: true,
                altFormat: "Y/m/d",
                dateFormat: "Y-m-d",
                locale: "vn",
                onChange: function (selectedDates, dateStr, instance) {
                    // Nếu là filter theo "từ ngày" hoặc "đến ngày"
                    const parent = instance.input.closest('[x-show]');
                    if (!parent) return;

                    const fromInput = parent.querySelector('input[x-model*="_from"]');
                    const toInput = parent.querySelector('input[x-model*="_to"]');

                    if (fromInput && toInput) {
                        const fromPicker = fromInput._flatpickr;
                        const toPicker = toInput._flatpickr;
                        if (fromPicker && toPicker) {
                            // Đảm bảo từ ngày không > đến ngày
                            if (fromPicker.selectedDates[0] && toPicker.selectedDates[0]) {
                                const from = fromPicker.selectedDates[0];
                                const to = toPicker.selectedDates[0];
                                if (from > to) {
                                    toPicker.clear();
                                }
                            }
                            // Cập nhật min/max hợp lý
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
            pickers.push(fp);
        });
    };
</script>


<!-- Hàm mở Flatpickr khi click icon -->
<script>
    window.openFlatpickr = function (el) {
        try {
            const parent = el.closest('div');
            const input = parent?.querySelector('input.flatpickr');
            if (!input) return;

            if (input._flatpickr) input._flatpickr.open();
            else {
                input.focus();
                setTimeout(() => input._flatpickr && input._flatpickr.open(), 200);
            }
        } catch (e) {
            console.debug('Cannot open Flatpickr:', e);
        }
    };
</script>

<!-- Flatpickr JS for date picker -->
<link rel="stylesheet" href="/assets/css/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script type="module" src="/assets/js/flatpickr-vi.js"></script>

</body>

</html>