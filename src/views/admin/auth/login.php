<?php
$errors = $_SESSION['errors'] ?? [];
$flash_error = $_SESSION['flash_error'] ?? null;
unset($_SESSION['errors'], $_SESSION['flash_error']);
?>
<!doctype html>
<html lang="vi">

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
  <style>
    html,
    body {
      height: 100%;
    }

    .shake {
      animation: shake 0.3s;
    }

    @keyframes shake {

      0%,
      100% {
        transform: translateX(0)
      }

      20%,
      60% {
        transform: translateX(-8px)
      }

      40%,
      80% {
        transform: translateX(8px)
      }
    }
  </style>
</head>

<body class="min-h-screen grid place-items-center bg-cover bg-center bg-no-repeat bg-fixed"
  style="background-image:url('/assets/images/Background.png');">

  <div class="fixed inset-0 bg-black/10"></div>

  <div id="form-card"
    class="relative w-full max-w-md mt-20 bg-white/90 backdrop-blur rounded-2xl shadow-xl ring-1 ring-black/5 p-6">

    <!-- Logo -->
    <div class="mb-4 text-center">
      <div class="text-3xl font-bold" style="color:#002795;">MiniGo</div>
    </div>

    <!-- Đường ngăn cách -->
    <div class="border-t border-gray-300 my-4"></div>

    <div class="mb-6 text-center">
      <div class="text-2xl font-semibold" style="color:#002795;">Đăng nhập</div>
    </div>

    <form id="login-form" class="space-y-4">
      <?php if (function_exists('csrf_token_input'))
        echo csrf_token_input(); ?>

      <!-- Username -->
      <div>
        <label class="block text-sm text-slate-600 mb-1 font-bold flex items-center gap-2">
          <i class="fa-solid fa-user text-sky-600"></i>
          Tài khoản
        </label>
        <input id="username" name="username" type="text"
          class="w-full border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-sky-500"
          placeholder="Vui lòng nhập tài khoản">
        <p id="username-error" class="hidden text-red-600 text-xs mt-1"></p>
      </div>

      <!-- Password -->
      <div>
        <label class="block text-sm text-slate-600 mb-1 font-bold flex items-center gap-2">
          <i class="fa-solid fa-lock text-sky-600"></i>
          Mật khẩu
        </label>

        <!-- wrapper để đặt icon mắt -->
        <div class="relative">
          <input id="password" name="password" type="password"
            class="w-full border rounded-lg px-3 pr-10 py-2 focus:outline-none focus:ring-2 focus:ring-sky-500"
            placeholder="Vui lòng nhập mật khẩu">
            
          <!-- nút toggle hiện/ẩn -->
          <button type="button" id="toggle-password"
            class="absolute inset-y-0 right-0 px-3 flex items-center text-slate-500 hover:text-slate-700"
            aria-label="Hiện mật khẩu" aria-pressed="false">
            <i id="eye-icon" class="fa-regular fa-eye-slash"></i>
          </button>

        </div>

        <p id="password-error" class="hidden text-red-600 text-xs mt-1"></p>
      </div>

      <!-- Ghi nhớ + Quên mật khẩu -->
      <div class="flex items-center justify-between text-sm">
        <label class="inline-flex items-center gap-2">
          <input id="remember" name="remember" type="checkbox" class="rounded border-gray-300">
          <span>Ghi nhớ đăng nhập</span>
        </label>
        <a href="/forgot-password" class="text-sky-600 hover:underline">Quên mật khẩu?</a>
      </div>


      <!-- Nút đăng nhập -->
      <button id="login-btn" class="w-full bg-sky-500 hover:bg-sky-600 text-white rounded-lg px-4 py-2 font-medium">
        Đăng nhập
      </button>
    </form>

    <div class="mt-6 text-center text-xs text-slate-400">
      © <?= date('Y') ?> MiniGo - B2105563
    </div>
  </div>

  <!-- Toast lỗi nổi -->
  <div id="toast-container" class="z-[60]"></div>

  <!-- Toggle hiện/ẩn mật khẩu -->
  <script>
    (() => {
      const toggleBtn = document.getElementById('toggle-password');
      const eyeIcon = document.getElementById('eye-icon');
      const pwdInput = document.getElementById('password'); // <— đổi tên

      toggleBtn?.addEventListener('click', () => {
        const isHidden = pwdInput.type === 'password';
        pwdInput.type = isHidden ? 'text' : 'password';

        eyeIcon.classList.toggle('fa-eye', isHidden);
        eyeIcon.classList.toggle('fa-eye-slash', !isHidden);
        toggleBtn.setAttribute('aria-pressed', String(isHidden));
        toggleBtn.setAttribute('aria-label', isHidden ? 'Ẩn mật khẩu' : 'Hiện mật khẩu');
      });
    })();
  </script>

  <!-- Validate + submit -->
  <script>
    (() => {
      const username = document.getElementById('username');
      const pwdInput = document.getElementById('password'); // <— dùng cùng tên
      const usernameError = document.getElementById('username-error');
      const passwordError = document.getElementById('password-error');
      const form = document.getElementById('login-form');
      const formCard = document.getElementById('form-card');
      const toastContainer = document.getElementById('toast-container');

      let toastTimer = null;
      function showToast(msg) {
        toastContainer.innerHTML = '';
        if (toastTimer) { clearTimeout(toastTimer); toastTimer = null; }
        const toast = document.createElement('div');
        toast.className =
          "fixed top-5 right-5 z-[60] flex items-center w-[500px] p-6 mb-4 text-base font-semibold text-red-700 bg-white rounded-xl shadow-lg border-2 border-red-400";
        toast.innerHTML = `
      <svg class="flex-shrink-0 w-6 h-6 text-red-600 me-3" xmlns="http://www.w3.org/2000/svg" fill="none"
        viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M12 9v2m0 4h.01M12 5a7 7 0 100 14 7 7 0 000-14z" />
      </svg>
      <div class="flex-1">${msg}</div>`;
        toastContainer.appendChild(toast);
        toastTimer = setTimeout(() => toast.remove(), 3000);
      }

      function validateInput(input, errorEl, message) {
        if (input.value.trim() === '') {
          errorEl.textContent = message;
          errorEl.classList.remove('hidden');
          input.classList.add('border-red-500');
          input.setAttribute('aria-invalid', 'true');
          return false;
        } else {
          errorEl.textContent = '';
          errorEl.classList.add('hidden');
          input.classList.remove('border-red-500');
          input.removeAttribute('aria-invalid');
          return true;
        }
      }

      function validateForm() {
        const userOk = validateInput(username, usernameError, 'Tài khoản không được bỏ trống');
        const passOk = validateInput(pwdInput, passwordError, 'Mật khẩu không được bỏ trống');
        return userOk && passOk;
      }

      [username, pwdInput].forEach(el => {
        el.addEventListener('blur', validateForm);
        el.addEventListener('input', validateForm);
      });

      form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const userEmpty = username.value.trim() === '';
        const passEmpty = pwdInput.value.trim() === '';
        if (!validateForm()) {
          showToast(userEmpty ? 'Tài khoản không được bỏ trống' : 'Mật khẩu không được bỏ trống');
          formCard.classList.add('shake');
          setTimeout(() => formCard.classList.remove('shake'), 400);
          return;
        }

        const csrfInput = form.querySelector('input[name="_token"]');
        const csrf = csrfInput ? csrfInput.value : null;

        try {
          const res = await fetch('/admin/login', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', ...(csrf ? { 'X-CSRF-Token': csrf } : {}) },
            credentials: 'same-origin',
            body: JSON.stringify({
              username: username.value.trim(),
              password: pwdInput.value,                 // <— dùng pwdInput
              remember: (document.getElementById('remember')?.checked ? 1 : 0)
            })
          });

          const ct = res.headers.get('content-type') || '';
          if (ct.includes('application/json')) {
            const data = await res.json().catch(() => ({}));
            if (res.ok && (data.ok || data.success)) {
              window.location.href = '/admin';
              return;
            }
            showToast(data.message || 'Tài khoản hoặc mật khẩu sai');
          } else {
            const txt = await res.text();
            showToast('Tài khoản hoặc mật khẩu sai');
          }

          formCard.classList.add('shake');
          setTimeout(() => formCard.classList.remove('shake'), 400);
        } catch {
          showToast('Lỗi kết nối tới server.');
        }
      });
    })();
  </script>

</body>

</html>