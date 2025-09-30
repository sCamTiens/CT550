<?php
require_once __DIR__ . '/../bootstrap.php'; // Kết nối tới cơ sở dữ liệu

// Giả sử bạn đã có một session lưu thông tin người dùng sau khi đăng nhập
$isLoggedIn = isset($_SESSION['user']);

// Lấy danh sách thể loại cho LH001
$theLoaiLH001 = getTheLoaiForLH001($PDO);

// Lấy danh sách thể loại cho LH002 
$theLoaiLH002 = getTheLoaiForLH002($PDO);

// Lấy dữ liệu từ bảng theLoai
$theLoai = getTheLoai($PDO);

// Chia dữ liệu thành 3 cột
$theLoaiChunks1 = array_chunk($theLoai, ceil(count($theLoai) / 3));
?>


<style>
    /* navbar */
    /* Đảm bảo megamenu hiển thị rộng hơn */
    .megamenu,
    .megamenu1 {
        width: 500px;
        padding: 10px 20px;
        background-color: #ffffff;
        border: none;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .megamenu1 {
        width: 120px;
    }

    /* Sử dụng Flexbox để bố trí các cột */
    .megamenu .container {
        display: flex;
        flex-wrap: nowrap;
        /* Đảm bảo các cột không bị wrap xuống hàng */
        justify-content: space-between;
        /* Giãn đều các cột */
    }

    /* Đảm bảo các cột có chiều rộng phù hợp */
    .megamenu .col-md-4 {
        flex: 0 0 30%;
        /* Giảm chiều rộng của cột để vừa với 3 cột */
        max-width: 40%;
        /* Đảm bảo các cột có cùng kích thước */
        padding: 0 25px;
        /* Giảm padding giữa các cột */
    }

    /* Định dạng các liên kết */
    .megamenu .nav a {
        display: block;
        padding: 5px 40px 5px 5px;
        color: #333;
        text-decoration: none;
        white-space: nowrap;
        /* Ngăn xuống dòng */
        overflow: hidden;
        /* Cắt bớt nếu quá dài */
        text-overflow: ellipsis;
        /* Thêm dấu ba chấm nếu văn bản quá dài */
    }

    .megamenu .nav a:hover,
    .megamenu1 li a:hover {
        color: #002795;
        font-weight: bold;
    }

    /* Phản hồi cho các thiết bị nhỏ */
    @media (max-width: 768px) {
        .megamenu .col-md-4 {
            flex: 0 0 100%;
            /* Đảm bảo các cột chiếm toàn bộ màn hình khi trên thiết bị nhỏ */
            max-width: 100%;
        }
    }

    .navbar-nav .nav-item .nav-link {
        border: 1px solid #002795;
        color: #fff;
        background-color: #002795;
        border-radius: 10px;
        margin: 4px;
        padding: 5px 15px;
        text-align: center;
    }

    .navbar-nav .nav-item .nav-link:hover {
        transform: scale(1.1);
    }


    /*--------------------- Menu Desktop ---------------------*/
    .sub_menu {
        background: #fff;
        position: absolute;
        z-index: 999;
        width: 150px;
        padding: 5px 10px;
        -webkit-transition: all .5s ease;
        -moz-transition: all .5s ease;
        -o-transition: all .5s ease;
        transition: all .5s ease;
        border-radius: 5px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    li:hover>.sub_menu {
        -webkit-transform: rotate3d(0, 0, 0, 0deg);
        -moz-transform: rotate3d(0, 0, 0, 0deg);
        -o-transform: rotate3d(0, 0, 0, 0deg);
        -ms-transform: rotate3d(0, 0, 0, 0deg);
        transform: rotate3d(0, 0, 0, 0deg);
    }

    .sub_menu a {
        text-align: center;
        padding: 10px;
        white-space: nowrap;
        display: block;
        color: black;
        text-decoration: none;
        overflow: hidden;
    }

    .sub_menu .sub_menu a {
        padding: 10px 20px;
        text-align: left;
    }

    .sub_menu a:hover {
        color: #002795;
        font-weight: bold;
    }

    /* điều chỉnh dropdown menu */
    .sub_menu .sub_menu {
        left: 100%;
        top: -5px;
        margin-left: 10px;
        margin-top: 0;
        border-radius: 5px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        -webkit-transform: rotate3d(0, 1, 0, 90deg);
        -moz-transform: rotate3d(0, 1, 0, 90deg);
        -o-transform: rotate3d(0, 1, 0, 90deg);
        -ms-transform: rotate3d(0, 1, 0, 90deg);
        transform: rotate3d(0, 1, 0, 90deg);
        width: 400px;
        overflow: hidden;
    }

    .megamenu1 .sub_menu {
        width: 450px;
    }

    ul li .sub_menu li {
        position: relative;
        list-style: none;
    }

    #description,
    #truyentranh,
    #tieuthuyet {
        margin-top: 10px;
        font-size: 14px;
        color: #555;
        padding: 10px;
        border-top: 1px solid #ccc;
    }
</style>

<?php
// Kiểm tra nếu người dùng VIP mới có hiệu ứng border
$borderClass = '';
if (isset($_SESSION['user']['userID'])) {
    $stmt = $PDO->prepare("SELECT 1 FROM vip WHERE userID = ? AND ngayKetThuc >= CURDATE()");
    $stmt->execute([$_SESSION['user']['userID']]);
    if ($stmt->fetchColumn()) {
        $borderClass = 'led-run-border2';
    }
}
echo '<nav class="navbar navbar-expand-lg navbar-light bg-light ' . $borderClass . ' mt-2 mb-4">';

?>

<div class="container-fluid">
    <div class="collapse navbar-collapse" id="navbarContent">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link active" aria-current="page" href="index.php">
                    <i class="fa fa-home"></i>
                    <span>Trang chủ</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link following-link" href="following.php"
                    data-bs-toggle="<?php echo $isLoggedIn ? '' : 'modal'; ?>"
                    data-bs-target="<?php echo $isLoggedIn ? '' : '#loginModal'; ?>">Theo dõi</a>
            </li>
            <li class="nav-item">
                <a class="nav-link history-link" href="history.php"
                    data-bs-toggle="<?php echo $isLoggedIn ? '' : 'modal'; ?>"
                    data-bs-target="<?php echo $isLoggedIn ? '' : '#loginModal'; ?>">Lịch sử</a>
            </li>
            <li class="dropdown nav-item position-relative">
                <a class="dropdown-toggle nav-link" id="megamenu" data-bs-toggle="dropdown" role="button"
                    aria-expanded="false" href="#">Loại hình</a>
                <ul class="dropdown-menu sub_menu megamenu megamenu1">
                    <li>
                        <a href="search_loaihinh.php?maLH=LH001">Truyện Tranh</a>
                        <ul class="sub_menu">
                            <div class="container">
                                <div class="row">
                                    <?php
                                    // Chia thể loại thành 3 cột
                                    $theLoaiChunks = array_chunk($theLoaiLH001, ceil(count($theLoaiLH001) / 3));

                                    foreach ($theLoaiChunks as $chunk): ?>
                                        <div class="col-md-4">
                                            <ul class="nav flex-column">
                                                <?php foreach ($chunk as $theLoai): ?>
                                                    <li>
                                                        <a href="search_theloai.php?maTL=<?= htmlspecialchars($theLoai['maTL']) ?>"
                                                            data-title="<?= htmlspecialchars($theLoai['title']) ?>">
                                                            <?= htmlspecialchars($theLoai['tenTL']) ?>
                                                        </a>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    <?php endforeach; ?>
                                    <div id="truyentranh" class="col-sm-12" style="display: none;"></div>
                                </div>
                            </div>
                        </ul>
                    </li>
                    <li>
                        <a href="search_loaihinh.php?maLH=LH002">Tiểu thuyết</a>
                        <ul class="sub_menu">
                            <div class="container">
                                <div class="row">
                                    <?php
                                    // Chia thể loại thành 3 cột
                                    $theLoaiChunks = array_chunk($theLoaiLH002, ceil(count($theLoaiLH002) / 3));

                                    foreach ($theLoaiChunks as $chunk): ?>
                                        <div class="col-md-4">
                                            <ul class="nav flex-column">
                                                <?php foreach ($chunk as $theLoai): ?>
                                                    <li>
                                                        <a href="search_theloai.php?maTL=<?= htmlspecialchars($theLoai['maTL']) ?>"
                                                            data-title="<?= htmlspecialchars($theLoai['title']) ?>">
                                                            <?= htmlspecialchars($theLoai['tenTL']) ?>
                                                        </a>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    <?php endforeach; ?>
                                    <div id="tieuthuyet" class="col-sm-12" style="display: none;"></div>
                                </div>
                            </div>
                        </ul>
                    </li>
                </ul>
            </li>
            <li class="dropdown nav-item">
                <a class="dropdown-toggle nav-link" id="megaMenu" data-bs-toggle="dropdown" role="button"
                    aria-expanded="false" href="#">Thể loại</a>
                <ul class="dropdown-menu megamenu">
                    <div class="container">
                        <div class="row">
                            <!-- Thể loại -->
                            <?php foreach ($theLoaiChunks1 as $chunk): ?>
                                <div class="col-md-4">
                                    <ul class="nav flex-column">
                                        <?php foreach ($chunk as $theLoai): ?>
                                            <li>
                                                <a href="search_theloai.php?maTL=<?= htmlspecialchars($theLoai['maTL']) ?>"
                                                    data-title="<?= htmlspecialchars($theLoai['title']) ?>">
                                                    <?= htmlspecialchars($theLoai['tenTL']) ?>
                                                </a>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endforeach; ?>
                            <div id="description" class="col-sm-12" style="display: none;"></div>
                        </div>
                    </div>
                </ul>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="search.php">Tìm truyện</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="nearuser.php" data-bs-toggle="<?php echo $isLoggedIn ? '' : 'modal'; ?>"
                    data-bs-target="<?php echo $isLoggedIn ? '' : '#loginModal'; ?>">Gợi ý kết bạn</a>
            </li>
        </ul>
    </div>
</div>
<?php
$isVIP = false;
if (isset($_SESSION['user']['userID'])) {
    $userID = $_SESSION['user']['userID'];

    $stmt = $PDO->prepare("SELECT * FROM vip WHERE userID = ? AND ngayKetThuc >= CURDATE()");
    $stmt->execute([$userID]);
    $isVIP = $stmt->rowCount() > 0;
}

// Chỉ hiển thị nút trên trang index.php
if (basename($_SERVER['PHP_SELF']) === 'index.php'):
    ?>
    <!-- Nếu user đã là VIP -->
    <?php if ($isVIP): ?>
        <button class="btn btn-warning text-white" style="white-space: nowrap;" disabled>🎉 Bạn đã là VIP</button>
    <?php else: ?>
        <!-- Nếu chưa VIP thì hiển thị nút nâng cấp -->
        <button id="openVipModal" class="btn" style="white-space: nowrap; width: 200px;" <?php if (!$isLoggedIn): ?>
                data-bs-toggle="modal" data-bs-target="#loginModal" <?php endif; ?>>🔥Nâng cấp VIP ngay</button>
    <?php endif; ?>

<?php endif; ?>

<!-- Modal Thanh Toán -->
<div id="vipModal" class="modal modal1">
    <div class="modal-content modal-content1">
        <span class="close">&times;</span>
        <div class="d-flex justify-content-center">
            <h2>Nâng cấp VIP</h2>
        </div>
        <p>Giá: <strong>100.000 VND (Hạn 1 tháng)</strong></p>
        <p><b>Ưu đãi:</b> Sở hữu ngay khung avatar VIP siêu ngầu cùng giao diện xịn. Miễn phí đọc truyện mà không tốn bất kỳ chìa khóa.
            Mua ngay nào~~🔥</p>

        <!-- Nút Thanh toán qua PayPal -->
        <div id="paypal-button-container"></div>
    </div>
</div>
</nav>

<style>
    /* CSS cho Modal */
    .modal1 {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 50%;
        top: 40%;
        transform: translate(-50%, -50%);
        width: 500px;
        max-height: 450px;
        overflow-y: auto;
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        text-align: center;
    }

    /* CSS cho nội dung bên trong modal */
    .modal-content1 {
        position: relative;
        padding: 15px;
    }

    /* Nút đóng modal */
    .close {
        position: absolute;
        top: 5px;
        right: 10px;
        font-size: 22px;
        cursor: pointer;
    }

    /* Giới hạn nội dung để không làm modal cao quá */
    .modal-content1 p {
        font-size: 14px;
        line-height: 1.5;
        margin-bottom: 10px;
    }
</style>

<!-- Script mở & đóng modal -->
<script>
    document.getElementById("openVipModal").addEventListener("click", function () {
        console.log("Button clicked"); // Debug log

        const isLoggedIn = <?= $isLoggedIn ? 'true' : 'false' ?>;

        if (!isLoggedIn) {
            console.log("User chưa đăng nhập, hiển thị modal login");

            // Hiển thị modal đăng nhập
            const loginModal = new bootstrap.Modal(document.getElementById('loginModal'));
            loginModal.show();
        } else {
            console.log("User đã đăng nhập, hiển thị modal VIP");

            // Hiển thị modal VIP
            document.getElementById("vipModal").style.display = "block";
        }
    });

    // Đóng modal khi click nút đóng
    document.querySelector(".close").addEventListener("click", function () {
        document.getElementById("vipModal").style.display = "none";
    });

    // Đóng modal khi click bên ngoài
    window.onclick = function (event) {
        let modal = document.getElementById("vipModal");
        if (event.target == modal) {
            modal.style.display = "none";
        }
    };
</script>

<!-- Tích hợp PayPal Smart Buttons -->
<script
    src="https://www.paypal.com/sdk/js?client-id=AY4WLHyXOKgcNnG_IcqyUCSTf9bJojbzTb0k9UolBzIxLB8O-FssSaHa5WiCG7AMZWsyVzMhw4nNbF22">
    </script>
<script>
    paypal.Buttons({
        createOrder: function (data, actions) {
            return actions.order.create({
                purchase_units: [{
                    amount: {
                        value: '4.00' // Giá trị tương đương 100.000 VND
                    }
                }]
            });
        },
        onApprove: function (data, actions) {
            return actions.order.capture().then(function (details) {
                alert("Thanh toán thành công! Cảm ơn " + details.payer.name.given_name);
                window.location.href = "xuly_vip.php?success=true&token=" + data.orderID;
            });
        },
        onError: function (err) {
            console.log("Lỗi thanh toán: ", err);
            alert("Đã xảy ra lỗi khi xử lý thanh toán. Vui lòng thử lại!");
        }
    }).render("#paypal-button-container");
</script>

<!-- Thể loại và loại hình -->
<script>
    const dropdownItems = document.querySelectorAll('.megamenu a'); // Chọn tất cả các phần tử <a> trong .megamenu
    const description = document.getElementById('description');

    dropdownItems.forEach(item => {
        item.addEventListener('mouseover', function () {
            // Khi rê chuột vào, lấy nội dung từ data-title và hiển thị
            description.textContent = item.getAttribute('data-title');
            description.style.display = 'block'; // Hiển thị phần mô tả
        });

        item.addEventListener('mouseout', function () {
            // Khi chuột rời khỏi, ẩn phần mô tả
            description.style.display = 'none';
        });
    });
</script>

<script>
    const dropdownItems1 = document.querySelectorAll('.megamenu1 a'); // Chọn tất cả các phần tử <a> trong .megamenu
    const truyentranh = document.getElementById('truyentranh');

    dropdownItems1.forEach(item => {
        item.addEventListener('mouseover', function () {
            // Khi rê chuột vào, lấy nội dung từ data-title và hiển thị
            truyentranh.textContent = item.getAttribute('data-title');
            truyentranh.style.display = 'block'; // Hiển thị phần mô tả
        });

        item.addEventListener('mouseout', function () {
            // Khi chuột rời khỏi, ẩn phần mô tả
            truyentranh.style.display = 'none';
        });
    });
</script>

<script>
    const dropdownItems2 = document.querySelectorAll('.megamenu1 a'); // Chọn tất cả các phần tử <a> trong .megamenu
    const tieuthuyet = document.getElementById('tieuthuyet');

    dropdownItems2.forEach(item => {
        item.addEventListener('mouseover', function () {
            // Khi rê chuột vào, lấy nội dung từ data-title và hiển thị
            tieuthuyet.textContent = item.getAttribute('data-title');
            tieuthuyet.style.display = 'block'; // Hiển thị phần mô tả
        });

        item.addEventListener('mouseout', function () {
            // Khi chuột rời khỏi, ẩn phần mô tả
            tieuthuyet.style.display = 'none';
        });
    });
</script>

<!-- Lịch sử đọc -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const chuongLinks = document.querySelectorAll('.history-link');

        chuongLinks.forEach(link => {
            link.addEventListener('click', function (e) {
                <?php if (!$isLoggedIn): ?>
                    e.preventDefault(); // Ngăn chặn chuyển trang
                    $('#loginModal').modal('show'); // Hiển thị modal đăng nhập
                <?php endif; ?>
            });
        });
    });
</script>

<!-- Truyện theo dõi -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const chuongLinks = document.querySelectorAll('.following-link');

        chuongLinks.forEach(link => {
            link.addEventListener('click', function (e) {
                <?php if (!$isLoggedIn): ?>
                    e.preventDefault(); // Ngăn chặn chuyển trang
                    $('#loginModal').modal('show'); // Hiển thị modal đăng nhập
                <?php endif; ?>
            });
        });
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous">
    </script>