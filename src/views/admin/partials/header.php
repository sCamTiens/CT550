<!-- Header -->
<header class="flex items-center justify-between shadow px-6 py-3 bg-blue-50 text-slate-800">
    <!-- Bên trái: tiêu đề -->
    <div class="text-lg font-semibold text-gray-700">
        <h1 class="p-4 font-bold text-lg tracking-wide text-[#002975]" style="font-size: 50px;">MiniGo</h1>
    </div>

    <!-- Bên phải: dropdown user -->
    <div class="relative">
        <?php
        $user = $_SESSION['admin_user'] ?? [];
        $avatar = !empty($user['avatar_url']) ? '/assets/images/avatar/' . $user['avatar_url'] : '/assets/images/default.png';
        $fullName = htmlspecialchars($user['full_name'] ?? 'Admin');
        ?>
        <button id="user-menu-btn" class="flex items-center gap-2 px-3 py-2 bg-white rounded-lg focus:outline-none
           text-[#002975] hover:bg-[#002975] hover:text-white transition-colors border border-[#002975]">

            <!-- Avatar -->
            <img src="<?= $avatar ?>" alt="avatar" class="w-8 h-8 rounded-full object-cover border border-gray-300" />

            <!-- Tên -->
            <span><?= $fullName ?></span>
            <i class="fa-solid fa-caret-down ml-1"></i>
        </button>

        <!-- Dropdown -->
        <div id="user-dropdown" class="hidden absolute right-0 mt-2 w-40 bg-white rounded-lg shadow-lg border z-50">
            <a href="/admin/profile"
                class="group flex items-center px-4 py-2 text-sm text-[#002975] hover:bg-[#002975] hover:text-white transition-colors rounded-t-lg">
                <i class="fa-solid fa-user mr-2 group-hover:text-white text-[#002975]"></i>
                Hồ sơ cá nhân
            </a>
            <div class="border-t"></div>
            <a href="/admin/logout"
                class="group block px-4 py-2 text-sm text-red-600 hover:bg-[#002975] hover:text-white rounded-b-lg">
                <i class="fa-solid fa-right-from-bracket mr-2 text-red-600 group-hover:text-white"></i>
                Đăng xuất
            </a>
        </div>
    </div>
</header>

<script>
    const btn = document.getElementById('user-menu-btn');
    const dropdown = document.getElementById('user-dropdown');
    btn?.addEventListener('click', () => {
        dropdown.classList.toggle('hidden');
    });

    // Click ra ngoài thì ẩn dropdown
    document.addEventListener('click', (e) => {
        if (!btn.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.classList.add('hidden');
        }
    });
</script>