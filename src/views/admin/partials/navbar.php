<style>
    .navbar-nav .nav-item .nav-link {
        border: 1px solid #002795;
        color: #fff;
        background-color: #002795;
        border-radius: 10px;
        margin: 4px;
        padding: 5px 15px;
    }

    .navbar-nav .nav-item .nav-link:hover {
        transform: scale(1.1);
    }

    .dropdown-menu li {
        padding: 5px 10px;
    }

    .dropdown-menu li a:hover {
        background-color: #002795;
        color: white;
        border-radius: 5px;
    }
</style>

<nav class="navbar navbar-expand-lg navbar-light bg-light">
    <div class="container-fluid">
        <div class="collapse navbar-collapse" id="navbarContent">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link active" aria-current="page" href="index_admin.php">
                        <i class="fa fa-home"></i>
                        <span>Trang chủ</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="manage_truyen.php">Quản lý truyện</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="manage_chapter.php">Quản lý chương</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="manage_theloai.php">Quản lý thể loại</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="manage_users.php">Quản lý người dùng</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="manage_huyhieu.php">Quản lý huy hiệu</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="manage_nhiemvu.php">Quản lý nhiệm vụ</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="manage_VIP.php">VIP</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="manage_binhluan.php">Bình luận</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="manage_theodoi.php">Theo dõi</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="manage_lichsu.php">Lịch sử</a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button"
                        data-bs-toggle="dropdown" aria-expanded="false"> Thêm mới
                    </a>
                    <ul class="dropdown-menu text-center" aria-labelledby="navbarDropdown">
                        <li><a class="dropdown-item" href="add_truyen.php">Thêm truyện</a></li>
                        <li><a class="dropdown-item" href="add_chapter.php">Thêm chương</a></li>
                        <li><a class="dropdown-item" href="add_huyhieu.php">Thêm huy hiệu</a></li>
                        <li><a class="dropdown-item" href="add_nhiemvu.php">Thêm nhiệm vụ</a></li>
                        <li><a class="dropdown-item" href="add_theloai.php">Thêm thể loại</a></li>
                        <li><a class="dropdown-item" href="add_truyen_theloai.php">Thêm liên kết</a></li>
                        <li><a class="dropdown-item" href="add_user.php">Thêm user/admin</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const searchButton = document.getElementById('searchButton');
        const searchInput = document.getElementById('searchInput');
        const searchForm = document.getElementById('searchForm');

        // Sự kiện khi nhấn nút tìm kiếm
        searchButton.addEventListener('click', function () {
            const query = searchInput.value.trim();

            if (query !== '') {
                // Gửi yêu cầu tìm kiếm đến máy chủ
                window.location.href = `search.php?query=${encodeURIComponent(query)}`;
            } else {
                alert('Vui lòng nhập nội dung cần tìm!');
            }
        });

        // Sự kiện khi nhấn Enter trong ô tìm kiếm
        searchInput.addEventListener('keypress', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault(); // Ngăn chặn form submit mặc định
                searchButton.click(); // Kích hoạt nút tìm kiếm
            }
        });
    });
</script>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>