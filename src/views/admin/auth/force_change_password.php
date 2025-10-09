<?php
// views/admin/auth/force_change_password.php
$errors = $_SESSION['errors'] ?? [];
$flash_error = $_SESSION['flash_error'] ?? null;
unset($_SESSION['errors'], $_SESSION['flash_error']);
?>
<head>
  <?php
  $partials = __DIR__ . '/../partials';
  if (is_file($partials . '/head.php')) {
    require $partials . '/head.php';
  } else {
    echo '<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
          <title>Admin • Đăng nhập</title>
          <script src="https://cdn.tailwindcss.com"></script>
          <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">';
  }
  ?>
</head>
<body class="min-h-screen grid place-items-center bg-cover bg-center bg-no-repeat bg-fixed" style="background-image:url('/assets/images/Background.png');">
  <div class="fixed inset-0 bg-black/10"></div>
  <div id="form-card" class="relative w-full max-w-md mt-20 bg-white/90 backdrop-blur rounded-2xl shadow-xl ring-1 ring-black/5 p-6">
    <div class="mb-4 flex items-center justify-center gap-2">
      <a href="/admin/logout-force" class="flex items-center justify-center w-10 h-10 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-full" title="Quay lại đăng nhập">
        <i class="fa-solid fa-arrow-left"></i>
      </a>
      <div class="text-3xl font-bold flex-1 text-center" style="color:#002795;">MiniGo</div>
    </div>
    <div class="border-t border-gray-300 my-4"></div>
    <div class="mb-6 text-center">
      <div class="text-2xl font-semibold" style="color:#002795;">Đổi mật khẩu mới</div>
      <div class="text-sm text-slate-500 mt-2">Bạn cần đổi mật khẩu trước khi sử dụng hệ thống</div>
    </div>
    <form id="change-password-form" class="space-y-4" method="post" action="/admin/force-change-password">
      <?php if (function_exists('csrf_token_input')) echo csrf_token_input(); ?>
      <div>
        <label class="block text-sm text-slate-600 mb-1 font-bold flex items-center gap-2">
          <i class="fa-solid fa-lock text-sky-600"></i> Mật khẩu mới <span class="text-red-500">*</span>
        </label>
        <div class="relative">
          <input id="password" name="password" type="password" class="w-full border rounded-lg px-3 pr-10 py-2 focus:outline-none focus:ring-2 focus:ring-sky-500" placeholder="Nhập mật khẩu mới" minlength="8" maxlength="50" required autocomplete="new-password">
          <button type="button" id="toggle-password" class="absolute inset-y-0 right-0 px-3 flex items-center text-slate-500 hover:text-slate-700" aria-label="Hiện mật khẩu" aria-pressed="false"><i id="eye-icon" class="fa-regular fa-eye-slash"></i></button>
        </div>
        <p id="password-error" class="hidden text-red-600 text-xs mt-1"></p>
      </div>
      <div>
        <label class="block text-sm text-slate-600 mb-1 font-bold flex items-center gap-2">
          <i class="fa-solid fa-lock text-sky-600"></i> Xác nhận mật khẩu <span class="text-red-500">*</span>
        </label>
        <div class="relative">
          <input id="password_confirm" name="password_confirm" type="password" class="w-full border rounded-lg px-3 pr-10 py-2 focus:outline-none focus:ring-2 focus:ring-sky-500" placeholder="Nhập lại mật khẩu mới" minlength="6" maxlength="50" required autocomplete="new-password">
          <button type="button" id="toggle-password-confirm" class="absolute inset-y-0 right-0 px-3 flex items-center text-slate-500 hover:text-slate-700" aria-label="Hiện mật khẩu" aria-pressed="false"><i id="eye-icon-confirm" class="fa-regular fa-eye-slash"></i></button>
        </div>
        <p id="password-confirm-error" class="hidden text-red-600 text-xs mt-1"></p>
      </div>
      <button class="w-full bg-sky-500 hover:bg-sky-600 text-white rounded-lg px-4 py-2 font-medium">Đổi mật khẩu</button>
    </form>
    <div class="mt-6 text-center text-xs text-slate-400">© <?= date('Y') ?> MiniGo - B2105563</div>
  </div>
  <div id="toast-container" class="z-[60]"></div>
  <script>
    (() => {
      // Toggle password
      const toggleBtn = document.getElementById('toggle-password');
      const eyeIcon = document.getElementById('eye-icon');
      const pwdInput = document.getElementById('password');
      toggleBtn?.addEventListener('click', () => {
        const isHidden = pwdInput.type === 'password';
        pwdInput.type = isHidden ? 'text' : 'password';
        eyeIcon.classList.toggle('fa-eye', isHidden);
        eyeIcon.classList.toggle('fa-eye-slash', !isHidden);
        toggleBtn.setAttribute('aria-pressed', String(isHidden));
        toggleBtn.setAttribute('aria-label', isHidden ? 'Ẩn mật khẩu' : 'Hiện mật khẩu');
      });
      // Toggle password confirm
      const toggleBtn2 = document.getElementById('toggle-password-confirm');
      const eyeIcon2 = document.getElementById('eye-icon-confirm');
      const pwdInput2 = document.getElementById('password_confirm');
      toggleBtn2?.addEventListener('click', () => {
        const isHidden = pwdInput2.type === 'password';
        pwdInput2.type = isHidden ? 'text' : 'password';
        eyeIcon2.classList.toggle('fa-eye', isHidden);
        eyeIcon2.classList.toggle('fa-eye-slash', !isHidden);
        toggleBtn2.setAttribute('aria-pressed', String(isHidden));
        toggleBtn2.setAttribute('aria-label', isHidden ? 'Ẩn mật khẩu' : 'Hiện mật khẩu');
      });
      // Validate
      const form = document.getElementById('change-password-form');
      const password = document.getElementById('password');
      const passwordConfirm = document.getElementById('password_confirm');
      const passwordError = document.getElementById('password-error');
      const passwordConfirmError = document.getElementById('password-confirm-error');
      // Hàm validate từng trường
      function validatePassword() {
        const val = password.value;
        if (val.trim() === '') {
          passwordError.textContent = 'Vui lòng nhập mật khẩu mới';
          passwordError.classList.remove('hidden');
          password.classList.add('border-red-500');
          return false;
        }
        const valid = val.length >= 8 && /[A-Z]/.test(val) && /[a-z]/.test(val) && /[0-9]/.test(val) && /[^A-Za-z0-9]/.test(val);
        if (!valid) {
          passwordError.textContent = 'Mật khẩu phải có ít nhất 8 ký tự bao gồm ít nhất 1 chữ hoa, 1 chữ thường, 1 số và 1 ký tự đặc biệt';
          passwordError.classList.remove('hidden');
          password.classList.add('border-red-500');
          return false;
        } else {
          passwordError.textContent = '';
          passwordError.classList.add('hidden');
          password.classList.remove('border-red-500');
          return true;
        }
      }
      function validatePasswordConfirm() {
        if (passwordConfirm.value.trim() === '') {
          passwordConfirmError.textContent = 'Vui lòng xác nhận mật khẩu';
          passwordConfirmError.classList.remove('hidden');
          passwordConfirm.classList.add('border-red-500');
          return false;
        } else if (password.value !== passwordConfirm.value) {
          passwordConfirmError.textContent = 'Mật khẩu xác nhận không khớp';
          passwordConfirmError.classList.remove('hidden');
          passwordConfirm.classList.add('border-red-500');
          return false;
        } else {
          passwordConfirmError.textContent = '';
          passwordConfirmError.classList.add('hidden');
          passwordConfirm.classList.remove('border-red-500');
          return true;
        }
      }

      password.addEventListener('blur', validatePassword);
      passwordConfirm.addEventListener('blur', validatePasswordConfirm);
      password.addEventListener('input', validatePassword);
      passwordConfirm.addEventListener('input', validatePasswordConfirm);

      form.addEventListener('submit', function(e) {
        let ok = true;
        if (!validatePassword()) ok = false;
        if (!validatePasswordConfirm()) ok = false;
        if (!ok) e.preventDefault();
      });
    })();
  </script>

