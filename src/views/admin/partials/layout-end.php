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

        // Đợi để Alpine.js bind giá trị x-model vào input trước
        setTimeout(() => {
            rootEl.querySelectorAll('input.flatpickr').forEach(el => {
                // Lấy thông tin filter key/field
                const filterKey = el.getAttribute('data-filter-key');
                const filterField = el.getAttribute('data-filter-field');

                // Lấy giá trị hiện tại từ Alpine.js
                let initialValue = '';
                const alpineEl = el.closest('[x-data]');
                if (alpineEl && alpineEl._x_dataStack) {
                    const data = alpineEl._x_dataStack[0];
                    if (data && data.filters && filterKey && filterField) {
                        initialValue = data.filters[`${filterKey}_${filterField}`] || '';
                    }
                }

                // Nếu đã có Flatpickr instance, cập nhật giá trị và return
                if (el._flatpickr) {
                    // Force update giá trị từ Alpine.js filters
                    if (initialValue) {
                        el._flatpickr.setDate(initialValue, false);
                    } else {
                        el._flatpickr.clear();
                    }
                    return;
                }

                // Khởi tạo Flatpickr lần đầu
                const fp = flatpickr(el, {
                    altInput: true,
                    altFormat: "d/m/Y",
                    dateFormat: "Y-m-d",
                    locale: "vn",
                    defaultDate: initialValue || null,
                    onReady: function(selectedDates, dateStr, instance) {
                        // Đảm bảo giá trị ban đầu được set đúng
                        if (initialValue && !dateStr) {
                            instance.setDate(initialValue, false);
                        }
                    },
                    onChange: function (selectedDates, dateStr, instance) {
                        // Cập nhật giá trị vào Alpine.js filters
                        const fKey = instance.input.getAttribute('data-filter-key');
                        const fField = instance.input.getAttribute('data-filter-field');

                        if (fKey && fField) {
                            const aEl = instance.input.closest('[x-data]');
                            if (aEl && aEl._x_dataStack) {
                                const data = aEl._x_dataStack[0];
                                if (data && data.filters) {
                                    // Cập nhật giá trị theo format Y-m-d
                                    data.filters[`${fKey}_${fField}`] = dateStr;
                                }
                            }
                        }

                        const parent = instance.input.closest('[x-show]');
                        if (!parent) return;

                        const fromInput = parent.querySelector('input[data-filter-field="from"]');
                        const toInput = parent.querySelector('input[data-filter-field="to"]');

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

                el._flatpickr = fp;
            });
        }, 150);
    };

    // 2.1. Hàm __initFlatpickrDateTime cho datetime filter (có cả giờ phút)
    window.__initFlatpickrDateTime = function (rootEl) {
        if (!window.flatpickr) return;
        if (!rootEl) return;

        setTimeout(() => {
            rootEl.querySelectorAll('input.flatpickr-datetime').forEach(el => {
                const filterKey = el.getAttribute('data-filter-key');
                const field = el.getAttribute('data-filter-field');
                if (!filterKey || !field) return;

                // Lấy giá trị ban đầu từ Alpine
                const initialValue = el.value || el.getAttribute(':value');

                // Nếu đã có Flatpickr instance, cập nhật giá trị và return
                if (el._flatpickr) {
                    if (initialValue && initialValue !== 'null' && initialValue !== '') {
                        el._flatpickr.setDate(initialValue, false);
                    } else {
                        el._flatpickr.clear();
                    }
                    return;
                }

                // Khởi tạo Flatpickr lần đầu với chế độ datetime
                const fp = flatpickr(el, {
                    dateFormat: "d/m/Y H:i",
                    enableTime: true,
                    time_24hr: true,
                    locale: "vn",
                    allowInput: true,
                    defaultDate: initialValue || null,
                    onChange: function (selectedDates, dateStr, instance) {
                        const component = Alpine.$data(rootEl.closest('[x-data]'));
                        if (!component || !component.filters) return;

                        // Format datetime theo định dạng MySQL: YYYY-MM-DD HH:MM:SS
                        let formattedDate = '';
                        if (selectedDates.length > 0) {
                            const d = selectedDates[0];
                            const year = d.getFullYear();
                            const month = String(d.getMonth() + 1).padStart(2, '0');
                            const day = String(d.getDate()).padStart(2, '0');
                            const hours = String(d.getHours()).padStart(2, '0');
                            const minutes = String(d.getMinutes()).padStart(2, '0');
                            formattedDate = `${year}-${month}-${day} ${hours}:${minutes}:00`;
                        }

                        // Cập nhật filter value
                        component.filters[`${filterKey}_${field}`] = formattedDate;

                        // Đồng bộ min/max nếu là khoảng between
                        const fromInput = rootEl.querySelector(`input[data-filter-key="${filterKey}"][data-filter-field="from"]`);
                        const toInput = rootEl.querySelector(`input[data-filter-key="${filterKey}"][data-filter-field="to"]`);

                        if (field === 'from' && toInput && fromInput._flatpickr && toInput._flatpickr) {
                            toInput._flatpickr.set('minDate', selectedDates[0]);
                        }

                        if (field === 'to' && fromInput && fromInput._flatpickr && toInput._flatpickr) {
                            fromInput._flatpickr.set('maxDate', selectedDates[0]);
                        }

                        // Reset về trang 1
                        if (component.currentPage) {
                            component.currentPage = 1;
                        }
                    }
                });

                el._flatpickr = fp;
            });
        }, 150);
    };

    // 2.2. Hàm openFlatpickrDateTime (click icon cho datetime)
    window.openFlatpickrDateTime = function (el) {
        try {
            const parent = el.closest('div');
            const input = parent?.querySelector('input.flatpickr-datetime');
            if (!input) return;

            if (input._flatpickr) input._flatpickr.open();
            else {
                input.focus();
                setTimeout(() => input._flatpickr?.open(), 200);
            }
        } catch (e) {
            console.debug('Cannot open Flatpickr DateTime:', e);
        }
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

    // 3.1. Ngăn đóng filter khi click vào Flatpickr calendar
    document.addEventListener('click', function (e) {
        const flatpickrCalendar = e.target.closest('.flatpickr-calendar');
        if (flatpickrCalendar) {
            // Thêm thuộc tính để đánh dấu click này là từ Flatpickr
            e._isFlatpickrClick = true;
        }
    }, true); // Use capture phase

    // Override Alpine.js click.outside để bỏ qua click từ Flatpickr
    document.addEventListener('alpine:init', () => {
        Alpine.directive('click-outside', (el, { expression }, { evaluateLater, effect }) => {
            const evaluate = evaluateLater(expression);
            
            const onClick = (e) => {
                // Bỏ qua nếu click vào chính element hoặc con của nó
                if (el.contains(e.target)) return;
                
                // Bỏ qua nếu click vào Flatpickr calendar
                if (e._isFlatpickrClick || e.target.closest('.flatpickr-calendar')) return;
                
                evaluate();
            };
            
            setTimeout(() => {
                document.addEventListener('click', onClick);
            }, 0);
            
            el._x_cleanups = el._x_cleanups || [];
            el._x_cleanups.push(() => {
                document.removeEventListener('click', onClick);
            });
        });
    });

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