<?php

// Kết nối với database và lấy danh sách truyện, bao gồm id, tên truyện, và hình ảnh
$sql = "SELECT maTruyen, tenTruyen, hinhAnh FROM truyen";
$stmt = $PDO->query($sql);

// Kiểm tra và xử lý kết quả
if ($stmt) {
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    echo "Không có kết quả.";
    exit;
}
?>

<!-- swiper -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11.1.14/swiper-bundle.min.css" />

<!-- font -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Potta+One&display=swap" rel="stylesheet">
<style>
    /* swiper */
    .swiper {
        width: 100%;
        padding-top: 30px;
        padding-bottom: 75px;
    }

    .swiper-slide {
        background-position: center;
        background-size: cover;
        width: 285px;
        height: auto;
        position: relative;
        overflow: hidden;
        border-radius: 12px;
    }

    .swiper-slide img {
        display: block;
        width: 100%;
        height: 90%;
        border-radius: 12px;
        object-fit: cover;
        transition: all .2s ease-in-out;
    }

    .swiper-slide:hover img {
        transform: scale(1.05);
        transition: transform 0.3s ease;
    }

    .swiper-slide-active img:hover {
        transform: scale(1.1);
        /* Phóng to thêm khi hover */
    }

    /* kiểu chữ ở dưới ảnh trong slider */
    .swiper-slide a {
        text-decoration: none;
        color: black;
        text-align: center;
        font-size: 18px;
    }

    .swiper-slide a:hover,
    img:hover {
        color: rgb(16, 5, 171);
        transform: scale(1.05);
    }

    .swiper-slide a img:hover {
        transform: scale(1.0);
    }

    .gradient-bar1 {
        width: 20%;
        height: 45px;
        background: linear-gradient(to bottom, #a0b2da 0%, #6b88c3 15%, #002795 50%, #6b88c3 85%, #a0b2da 100%);
        border-radius: 30px;
        margin-left: 40%;
    }
    
    a p {
        margin-top: 6px;
    }

    .right,
    .left {
        position: absolute;
        padding: 10px;
        text-align: center;
        z-index: 100;
        top: -25%;
        right: 34.5%;
    }

    .right:hover {
        transform: scaleX(-1);
    }

    .left {
        transform: scaleX(-1);
        left: 34.5%;
    }

    /* Mặc định hiển thị ảnh sáng và ẩn ảnh tối */
    .light-image {
        display: inline-block;
        /* Hiển thị */
    }

    .dark-image {
        display: none;
        /* Ẩn */
    }

    /* Khi chế độ dark-mode được bật */
    body.dark-mode .light-image {
        display: none;
        /* Ẩn ảnh sáng */
    }

    body.dark-mode .dark-image {
        display: inline-block;
        /* Hiển thị ảnh tối */
    }

    .gradient-bar {
        background: linear-gradient(to bottom, #ffffff, #1565c0, #ffffff);
    }

    body.dark-mode .gradient-bar {
        background: #000;
    }

</style>

<!-- Swipper slide -->
<div>
    <div class="position-relative">
        <img src="/images/light.png" alt="Ảnh sáng bên phải" width="100" class="right light-image">
        <img src="/images/light.png" alt="Ảnh sáng bên trái" width="100" class="left light-image">

        <img src="/images/dark.png" alt="Ảnh tối bên phải" width="100" class="right dark-image">
        <img src="/images/dark.png" alt="Ảnh tối bên trái" width="100" class="left dark-image">
    </div>
</div>
<section class="slider-container gradient-bar">
    <h2 class="text-center animate__animated animate__bounce mt-5 gradient-bar1 potta-one-regular">Truyện nổi bật</h2>
    <div class="container-fluid">
        <div class="swiper mySwiper">
            <div class="swiper-wrapper">
                <!-- Lặp qua từng truyện và hiển thị trong swiper slide nếu ảnh tồn tại -->
                <?php foreach ($result as $row): ?>
                    <?php
                    // Kiểm tra sự tồn tại của file ảnh trong thư mục images/Noibat
                    $imagePath = 'images/Noibat/' . $row['hinhAnh'];
                    if (file_exists($imagePath)):
                        ?>
                        <div class="swiper-slide">
                            <a href="ct_truyen.php?maTruyen=<?php echo $row['maTruyen']; ?>">
                                <img src="<?php echo $imagePath; ?>" loading="lazy">
                                <p><?php echo $row['tenTruyen']; ?></p>
                            </a>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>
<!-- swiper -->
<script src="https://cdn.jsdelivr.net/npm/swiper@11.1.14/swiper-bundle.min.js"></script>
<script>
    // Truyện nổi bật
    var swiper = new Swiper(".mySwiper", {
        effect: "coverflow", //làm nổi bật slide giữa, làm mờ dần các slide bên cạnh
        grabCursor: true, //Con trỏ chuột chuyển thành bàn tay
        centeredSlides: true, //Slide hiện tại sẽ được căn giữa
        slidesPerView: "auto", // Tự động hiển thị số slide tùy theo kích thước
        spaceBetween: 30, //Khoảng cách giữa các slide
        loop: true,
        autoplay: {
            delay: 4000, // Đặt delay 4 giây
            disableOnInteraction: false, // Cho phép autoplay tiếp tục sau khi tương tác
        },
        speed: 800,
        coverflowEffect: {
            rotate: 0,
            stretch: 0,
            depth: 100,
            modifier: 2,
            slideShadows: true
        },
        navigation: {
            nextEl: ".swiper-button-next", // Thêm các nút điều hướng
            prevEl: ".swiper-button-prev"
        }
    });
</script>