// Dropdown-menu

document.addEventListener('DOMContentLoaded', function () {
    const dropdown = document.querySelector('.dropdown');
    const dropdownMenu = document.querySelector('.dropdown-menu');

    dropdown.addEventListener('mouseenter', function () {
        dropdownMenu.classList.add('show'); // Mở menu khi rê chuột vào dropdown
    });

    dropdown.addEventListener('mouseleave', function () {
        dropdownMenu.classList.remove('show'); // Đóng menu khi rê chuột ra ngoài dropdown
    });
});

// Đóng megamenu khi click ra ngoài
document.addEventListener('click', function (event) {
    var isClickInside = document.querySelector('.dropdown.nav-item').contains(event.target);

    if (!isClickInside) {
        var dropdown = document.querySelector('.dropdown.nav-item .dropdown-toggle');
        var dropdownInstance = bootstrap.Dropdown.getInstance(dropdown);
        if (dropdownInstance) {
            dropdownInstance.hide();
        }
    }
});

// Thêm hiệu ứng khi mở dropdown
var dropdownToggle = document.querySelectorAll('.dropdown-toggle');

dropdownToggle.forEach(function (toggle) {
    toggle.addEventListener('show.bs.dropdown', function () {
        this.nextElementSibling.classList.add('show');
    });

    toggle.addEventListener('hide.bs.dropdown', function () {
        this.nextElementSibling.classList.remove('show');
    });
});

// Navbar-toggler

document.querySelector('.navbar-toggler').addEventListener('click', function () {
    var navbar = document.querySelector('.navbar-collapse');
    navbar.classList.toggle('show');
    var togglerIcon = document.querySelector('.navbar-toggler-icon');
    var closeIcon = document.querySelector('.close-toggler-icon');

    // Nếu navbar đang mở, đổi biểu tượng sang "X"
    if (navbar.classList.contains('show')) {
        togglerIcon.style.display = 'none';
        closeIcon.style.display = 'block';
    } else {
        // Nếu navbar đóng lại, đổi biểu tượng về biểu tượng menu
        togglerIcon.style.display = 'block';
        closeIcon.style.display = 'none';
    }
});
