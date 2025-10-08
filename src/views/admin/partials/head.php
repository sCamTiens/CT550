<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="Website siêu thị mini">

<title>MiniGo</title>

<link rel="icon" href="/assets/images/minigo.png" type="image/png"> <!-- Đảm bảo file tồn tại -->


<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">

<!-- Flatpickr CSS & JS for date picker -->
<link rel="stylesheet" href="/assets/css/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script type="module" src="/assets/js/flatpickr-vi.js"></script>

<script src="https://cdn.tailwindcss.com"></script>
<script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
<script defer src="https://unpkg.com/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>

<script>
    tailwind.config = { theme: { extend: { colors: { primary: { DEFAULT: '#0ea5e9' } } } } }
</script>

<script>
// Tự động bật Flatpickr cho input ngày sinh nếu có trên trang
document.addEventListener('DOMContentLoaded', function() {
    if (window.flatpickr && document.querySelector("input[name='date_of_birth']")) {
        flatpickr("input[name='date_of_birth']", {
            dateFormat: "d/m/Y",
            locale: window.Vietnamese || undefined,
            allowInput: true,
            maxDate: "today"
        });
    }
});
</script>