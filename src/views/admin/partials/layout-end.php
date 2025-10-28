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
            rootEl.querySelectorAll('input.flatpickr:not([data-fp])').forEach(el => {
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

                // Khởi tạo Flatpickr
                const fp = flatpickr(el, {
                    altInput: true,
                    altFormat: "d/m/Y",
                    dateFormat: "Y-m-d",
                    locale: "vn",
                    defaultDate: initialValue || null,
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

                el.setAttribute('data-fp', '1');
                el._flatpickr = fp;
            });
        }, 150);
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

<!-- <script>
    // Chặn reload trang khi click vào link trong sidebar
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('aside a[href]').forEach(link => {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                const url = this.getAttribute('href');
                history.pushState(null, '', url);
                loadPage(url);
            });
        });

        // Xử lý nút Back/Forward của browser
        window.addEventListener('popstate', () => {
            loadPage(window.location.pathname);
        });
    });

    // Hàm load nội dung trang mới không reload toàn bộ
    function loadPage(url) {
        fetch(url)
            .then(res => res.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                
                // Lấy nội dung trong <main id="content">
                const newContent = doc.querySelector('#content');
                const currentContent = document.querySelector('#content');
                
                if (newContent && currentContent) {
                    currentContent.innerHTML = newContent.innerHTML;
                    
                    // Re-init Alpine.js cho nội dung mới
                    if (window.Alpine) {
                        Alpine.initTree(currentContent);
                    }
                    
                    // Re-init Flatpickr nếu có
                    if (window.__initFlatpickr) {
                        setTimeout(() => {
                            const flatpickrInputs = currentContent.querySelectorAll('.flatpickr');
                            flatpickrInputs.forEach(input => {
                                if (!input._flatpickr) {
                                    flatpickr(input, {
                                        dateFormat: 'd/m/Y',
                                        locale: 'vn',
                                        allowInput: true
                                    });
                                }
                            });
                        }, 200);
                    }
                    
                    // Scroll về đầu trang
                    window.scrollTo(0, 0);
                }
            })
            .catch(err => {
                console.error('Không thể load trang:', err);
                // Nếu lỗi thì reload bình thường
                window.location.href = url;
            });
    }
</script> -->

</body>

</html>