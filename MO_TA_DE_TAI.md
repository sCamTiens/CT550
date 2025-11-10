# MÔ TẢ ĐỀ TÀI

## THÔNG TIN CHUNG

**Tên đề tài:** Website Siêu thị mini MINIGO

**Học phần:** CT550 - Luận văn tốt nghiệp

**Học kỳ:** 1, Năm học 2025-2026

**MSSV:** B2105563

**Họ tên:** Ngô Thị Cẩm Tiên

---

## MÔ TẢ HỆ THỐNG

Website Siêu thị mini MINIGO là một hệ thống quản lý toàn diện cho siêu thị bán lẻ quy mô nhỏ, cung cấp các tính năng quản lý từ danh mục sản phẩm, kho hàng, nhân viên, khách hàng đến báo cáo thống kê và tài chính.

### Mục tiêu
- Số hóa quy trình quản lý siêu thị mini
- Tự động hóa các nghiệp vụ bán hàng, nhập xuất kho
- Theo dõi tồn kho, cảnh báo hết hàng/sắp hết hạn
- Quản lý nhân viên, chấm công, tính lương
- Phân tích dữ liệu kinh doanh qua báo cáo và biểu đồ

---

## CÔNG NGHỆ SỬ DỤNG

### 1. Ngôn ngữ lập trình
- **PHP 8.x** - Backend server-side scripting
- **JavaScript (ES6+)** - Frontend interactivity
- **SQL** - Database queries

### 2. Framework & Libraries Backend

#### PHP Libraries (Composer)
```json
{
  "vlucas/phpdotenv": "^5.6",           // Quản lý biến môi trường
  "phpoffice/phpspreadsheet": "*"       // Xuất/nhập Excel
}
```

**Autoloading:** PSR-4 standard (`App\` namespace)

### 3. Frontend Framework & Libraries

#### CSS Framework
- **Tailwind CSS 3.x** (CDN)
  - Utility-first CSS framework
  - Responsive design
  - Custom theming với màu primary: `#0ea5e9`

#### JavaScript Frameworks
- **Alpine.js 3.x** (CDN)
  - Reactive UI components
  - Component state management
  - Event handling
  - Plugin: `@alpinejs/collapse` - Collapsible UI elements

#### UI/UX Libraries
- **Animate.css 4.1.1** - CSS animations (fadeIn, zoomIn, slide effects)
- **Font Awesome 6.6.0** - Icon library
- **Flatpickr** - Date & time picker với localization tiếng Việt
- **Chart.js 4.4.0** - Biểu đồ thống kê (line, bar, pie charts)

### 4. Database
- **MySQL 8.x** (hoặc MariaDB)
  - Character set: `utf8mb4_unicode_ci`
  - Storage engine: InnoDB
  - Foreign key constraints
  - Triggers và stored procedures

### 5. Architecture Pattern
- **MVC Architecture** (Model-View-Controller)
  - Models: Repository pattern
  - Views: PHP templates
  - Controllers: Request handlers
- **Custom Router** - Route-based navigation
- **Middleware** - Role-based access control

---

## CẤU TRÚC HỆ THỐNG

```
CT550/
├── config/                      # Cấu hình hệ thống
│   ├── app.php                 # App configuration
│   └── database.php            # Database connection
│
├── database/migrations/        # SQL migration files
│   ├── db.sql                  # Main database schema
│   ├── add_attendance_and_payroll_tables.sql
│   ├── add_loyalty_points_system.sql
│   └── ...
│
├── public/                     # Public web root
│   ├── index.php              # Application entry point
│   └── assets/                # Static resources
│       ├── css/
│       ├── js/
│       └── images/
│
├── src/
│   ├── Controllers/           # Request handlers
│   │   ├── Admin/            # Admin controllers
│   │   │   ├── DashboardController.php
│   │   │   ├── ProductController.php
│   │   │   ├── OrderController.php
│   │   │   ├── StaffController.php
│   │   │   └── ...
│   │   ├── AuthController.php
│   │   └── HomeController.php
│   │
│   ├── Models/
│   │   ├── Entities/         # Entity classes
│   │   └── Repositories/     # Data access layer
│   │       ├── ProductRepository.php
│   │       ├── OrderRepository.php
│   │       ├── PurchaseOrderRepository.php
│   │       └── ...
│   │
│   ├── Middlewares/          # HTTP middlewares
│   │   └── RoleMiddleware.php # RBAC
│   │
│   ├── Services/             # Business logic
│   │
│   ├── Core/                 # Core framework
│   │   ├── Router.php       # Request routing
│   │   └── Request.php      # HTTP request wrapper
│   │
│   ├── Support/              # Helper utilities
│   │   └── helpers.php      # Helper functions
│   │
│   └── views/                # View templates
│       ├── admin/           # Admin panel views
│       │   ├── partials/   # Reusable components
│       │   ├── products/
│       │   ├── orders/
│       │   └── ...
│       ├── auth/
│       └── home/
│
├── vendor/                   # Composer dependencies
├── .env                     # Environment variables
└── composer.json            # PHP dependencies
```

---

## CHỨC NĂNG CHÍNH

### 1. Quản lý danh mục sản phẩm
#### 1.1 Sản phẩm (Products)
- CRUD sản phẩm với thông tin: SKU, tên, giá, mô tả, hình ảnh
- Quản lý theo thương hiệu, loại sản phẩm, đơn vị tính
- Nhập/xuất Excel hàng loạt
- Lọc và tìm kiếm nâng cao
- Tổng quan: Tổng sản phẩm, Đang hoạt động, Ngừng kinh doanh, Giá trị tồn kho

#### 1.2 Thương hiệu (Brands)
- Quản lý danh sách thương hiệu
- Slug URL thân thiện SEO

#### 1.3 Loại sản phẩm (Categories)
- Phân cấp cha-con (parent-child)
- Thứ tự hiển thị
- Trạng thái hoạt động

#### 1.4 Đơn vị tính (Units)
- Định nghĩa đơn vị: Cái, Hộp, Kg, Lít...
- Tổng quan: Tổng đơn vị, Đang dùng, Ngừng dùng, Tạo trong tháng

### 2. Quản lý kho hàng (Inventory Management)
#### 2.1 Tồn kho (Stocks)
- Theo dõi số lượng tồn kho theo sản phẩm
- Cảnh báo tồn kho thấp (Low stock alerts)
- Cảnh báo sắp hết hạn (Expiry alerts)
- Lịch sử biến động tồn kho

#### 2.2 Phiếu nhập kho (Purchase Orders)
- Tạo phiếu nhập từ nhà cung cấp
- Quản lý theo lô hàng (Product Batches)
- Thông tin: Mã lô, HSD, NSX, giá nhập
- Trạng thái thanh toán: Chưa đối soát, Đã thanh toán một phần, Đã thanh toán hết
- In phiếu nhập kho (Invoice template)
- Nhập/xuất Excel
- Tổng quan: Tổng phiếu nhập, Tổng giá trị, Đã thanh toán, Chưa đối soát

#### 2.3 Phiếu xuất kho (Stock Outs)
- Xuất kho theo lý do: Bán hàng, Hỏng hóc, Trả lại nhà cung cấp
- Ghi nhận ngày xuất, ghi chú
- Xuất Excel

#### 2.4 Kiểm kê (Stocktake)
- So sánh số lượng thực tế vs hệ thống
- Ghi nhận chênh lệch (Difference)
- Cập nhật tồn kho sau kiểm kê

#### 2.5 Lô hàng (Product Batches)
- Theo dõi chi tiết từng lô hàng
- Mã lô (Batch code), HSD, NSX
- Số lượng ban đầu, tồn kho hiện tại

### 3. Quản lý nhà cung cấp (Suppliers)
- CRUD thông tin: Tên, SĐT, Email, Địa chỉ
- Theo dõi công nợ
- Lịch sử giao dịch
- Tổng quan: Tổng NCC, Đang hoạt động, Ngừng hợp tác, Có email

### 4. Quản lý khách hàng (Customers)
- Thông tin: Họ tên, SĐT, Email, Địa chỉ
- Lịch sử mua hàng
- Điểm tích lũy (Loyalty Points)
- Phân loại: Khách vãng lai, Khách thành viên
- Tổng quan: Tổng khách, Đang hoạt động, Ngừng giao dịch, Đăng ký trong tháng

### 5. Quản lý đơn hàng (Orders)
#### 5.1 Tạo đơn hàng
- Chọn khách hàng (hoặc khách vãng lai)
- Thêm sản phẩm vào giỏ
- Áp dụng mã giảm giá/khuyến mãi
- Tính toán tổng tiền, thuế
- Chọn phương thức thanh toán

#### 5.2 Quản lý đơn hàng
- Trạng thái: Chờ xử lý, Đang giao, Hoàn thành, Đã hủy
- Lịch sử đơn hàng
- In hóa đơn (Invoice template)
- Xuất Excel

### 6. Quản lý khuyến mãi & giảm giá
#### 6.1 Mã giảm giá (Coupons)
- Mã code duy nhất
- Loại giảm: Phần trăm / Số tiền cố định
- Điều kiện: Giá trị đơn tối thiểu
- Thời gian hiệu lực
- Giới hạn số lần dùng

#### 6.2 Chương trình khuyến mãi (Promotions)
- Loại: Giảm giá, Mua X tặng Y, Combo
- Áp dụng cho sản phẩm/danh mục cụ thể
- Thời gian khuyến mãi

### 7. Quản lý nhân viên (Staff Management)
#### 7.1 Nhân viên
- Thông tin cá nhân: Họ tên, SĐT, Email, CCCD
- Vị trí: Kho, Nhân viên bán hàng, Hỗ trợ trực tuyến, Admin
- Ngày vào làm
- Tổng quan: Tổng nhân viên, Đang làm việc, Đã nghỉ, Số Admin

#### 7.2 Phân quyền (RBAC - Role Based Access Control)
- Vai trò: Admin, Kho, Nhân viên bán hàng, Hỗ trợ trực tuyến
- Phân quyền truy cập module/chức năng
- Middleware kiểm tra quyền

#### 7.3 Lịch làm việc (Work Shifts)
- Định nghĩa ca làm: Ca sáng, Ca chiều, Ca tối
- Thời gian: Giờ vào, giờ ra
- Phân ca cho nhân viên

#### 7.4 Lịch trực (Schedules)
- Xếp lịch theo tuần/tháng
- Giao ca cho nhân viên
- Xem lịch theo nhân viên/theo ca

#### 7.5 Chấm công (Attendance)
- Check-in / Check-out
- Theo dõi giờ làm việc thực tế
- Cảnh báo đi muộn/về sớm
- Chấm công qua IP (IP whitelisting)
- Lọc theo tuần/tháng/khoảng thời gian tùy chỉnh

#### 7.6 Tính lương (Payroll)
- Công thức: Lương cơ bản + Thưởng - Phạt - Khấu trừ
- Trừ lương đi muộn (Late deduction)
- Báo cáo lương theo kỳ
- Xuất Excel bảng lương

### 8. Quản lý thu chi (Finance)
#### 8.1 Phiếu thu (Receipt Vouchers)
- Ghi nhận thu từ: Bán hàng, Thu công nợ, Khác
- Người nộp tiền, số tiền, lý do
- Xuất Excel

#### 8.2 Phiếu chi (Expense Vouchers)
- Ghi nhận chi: Nhập hàng, Lương, Tiện ích, Khác
- Người nhận tiền, số tiền, lý do
- Phê duyệt phiếu chi
- Xuất Excel

#### 8.3 Công nợ nhà cung cấp
- Theo dõi nợ phải trả
- Lịch sử thanh toán
- Cảnh báo đến hạn trả nợ

### 9. Báo cáo & Thống kê (Reports & Analytics)
#### 9.1 Dashboard
- Tổng quan hệ thống theo ngày
- Số đơn hàng hôm nay
- Doanh thu hôm nay
- Khách hàng mới hôm nay
- Sản phẩm sắp hết hàng
- Biểu đồ doanh thu theo tuần
- Top sản phẩm bán chạy
- Đơn hàng gần đây

#### 9.2 Báo cáo chi tiết
- **Báo cáo doanh thu:** Theo ngày/tuần/tháng/năm
- **Báo cáo bán hàng:** Sản phẩm bán chạy/ế
- **Báo cáo tồn kho:** Hàng tồn nhiều, ít, sắp hết hạn
- **Báo cáo nhân viên:** Hiệu suất bán hàng theo nhân viên
- **Báo cáo thu chi:** Lợi nhuận = Doanh thu - Chi phí
- Xuất báo cáo ra Excel/PDF

#### 9.3 Biểu đồ (Charts)
- Doanh thu theo thời gian (Line chart)
- Phân bổ sản phẩm theo danh mục (Pie chart)
- So sánh doanh thu vs chi phí (Bar chart)

### 10. Tiện ích & Hỗ trợ
#### 10.1 Nhập/Xuất Excel
- Import hàng loạt: Products, Categories, Brands, Units, Suppliers, Customers, Purchase Orders
- Export danh sách ra Excel
- Download file mẫu (Template)
- Lịch sử nhập file (Import History)
- Xem chi tiết lỗi khi import

#### 10.2 Thông báo (Notifications)
- Thông báo hệ thống
- Cảnh báo tồn kho thấp
- Cảnh báo hết hạn sản phẩm
- Cảnh báo đến hạn trả nợ
- Đánh dấu đã đọc/chưa đọc

#### 10.3 Nhật ký hoạt động (Audit Logs)
- Ghi nhận mọi thao tác: Thêm, Sửa, Xóa
- Thông tin: Người thực hiện, Thời gian, Hành động, Dữ liệu cũ/mới
- Lọc theo module, người dùng, hành động

#### 10.4 Lịch sử nhập file (Import History)
- Danh sách các lần import
- Thống kê: Tổng dòng, Thành công, Thất bại
- Xem chi tiết file đã import
- Xem lỗi từng dòng
- Xóa lịch sử cũ

### 11. Xác thực & Bảo mật
- **Đăng nhập/Đăng xuất**
- **Quên mật khẩu** (Password reset)
- **Bắt buộc đổi mật khẩu** lần đầu đăng nhập
- **Session management**
- **Password hashing** (bcrypt)
- **CSRF protection**
- **SQL injection prevention** (PDO Prepared Statements)

---

## DATABASE SCHEMA

### Core Tables (Hệ thống cốt lõi)
- `roles` - Vai trò người dùng
- `users` - Người dùng (Admin, Nhân viên, Khách hàng)
- `staff_profiles` - Hồ sơ nhân viên
- `provinces` - Tỉnh/Thành phố
- `communes` - Xã/Phường/Thị trấn
- `user_addresses` - Địa chỉ người dùng

### Catalog Tables (Danh mục)
- `brands` - Thương hiệu
- `categories` - Loại sản phẩm
- `units` - Đơn vị tính
- `products` - Sản phẩm
- `product_images` - Hình ảnh sản phẩm

### Inventory Tables (Kho hàng)
- `suppliers` - Nhà cung cấp
- `purchase_orders` - Phiếu nhập kho
- `product_batches` - Lô hàng
- `stock_outs` - Phiếu xuất kho
- `stocktakes` - Kiểm kê
- `inventory_movements` - Biến động tồn kho

### Sales Tables (Bán hàng)
- `orders` - Đơn hàng
- `order_items` - Chi tiết đơn hàng
- `carts` - Giỏ hàng
- `cart_items` - Chi tiết giỏ hàng

### Promotion Tables (Khuyến mãi)
- `promotions` - Chương trình khuyến mãi
- `promotion_products` - Sản phẩm áp dụng KM
- `coupons` - Mã giảm giá
- `user_coupons` - Lịch sử dùng mã
- `loyalty_points` - Điểm tích lũy
- `loyalty_transactions` - Giao dịch điểm

### Finance Tables (Tài chính)
- `receipt_vouchers` - Phiếu thu
- `expense_vouchers` - Phiếu chi
- `supplier_debts` - Công nợ NCC
- `debt_payments` - Thanh toán công nợ

### Staff Management Tables (Quản lý nhân viên)
- `work_shifts` - Ca làm việc
- `schedules` - Lịch trực
- `attendance` - Chấm công
- `payrolls` - Bảng lương

### System Tables (Hệ thống)
- `notifications` - Thông báo
- `audit_logs` - Nhật ký hoạt động
- `import_history` - Lịch sử import file
- `stock_alerts` - Cảnh báo tồn kho
- `payment_due_alerts` - Cảnh báo đến hạn trả nợ

---

## THƯ VIỆN & DEPENDENCIES CHI TIẾT

### Backend (Composer)
```json
{
  "vlucas/phpdotenv": "^5.6"         // Quản lý .env file
  "phpoffice/phpspreadsheet": "*"    // Xử lý Excel (Import/Export)
}
```

### Frontend (CDN)
```javascript
// CSS Frameworks
"tailwindcss": "3.x"                 // Utility-first CSS

// JavaScript Frameworks  
"alpinejs": "3.x.x"                  // Reactive components
"@alpinejs/collapse": "3.x.x"        // Collapse plugin

// UI Libraries
"animate.css": "4.1.1"               // CSS animations
"font-awesome": "6.6.0"              // Icon fonts
"flatpickr": "latest"                // Date picker
  - "flatpickr/l10n/vn.js"          // Vietnamese locale
"chart.js": "4.4.0"                  // Chart rendering
```

### Development Tools
- **VS Code** - IDE
- **XAMPP/WAMP** - Local development environment
- **Git** - Version control
- **Composer** - PHP dependency manager

---

## TÍNH NĂNG NỔI BẬT

### 1. Giao diện người dùng
✅ Responsive design (Mobile, Tablet, Desktop)
✅ Dark theme compatible
✅ Toast notifications
✅ Loading states & skeletons
✅ Confirm dialogs
✅ Modal popups
✅ Dropdown menus
✅ Collapsible sections
✅ Sortable tables
✅ Advanced filtering

### 2. Hiệu năng & UX
✅ Client-side filtering (Alpine.js)
✅ Pagination
✅ Lazy loading
✅ Debounced search
✅ Auto-save states
✅ Keyboard shortcuts
✅ Form validation real-time

### 3. Tính năng nghiệp vụ
✅ Multi-level categories
✅ Batch operations
✅ Bulk import/export
✅ Auto-generate codes (SKU, Order#, PO#)
✅ Expiry date tracking
✅ Low stock alerts
✅ Automatic inventory updates
✅ Point redemption
✅ Coupon validation
✅ Role-based permissions

### 4. Báo cáo & Phân tích
✅ Real-time dashboard
✅ Interactive charts (Chart.js)
✅ Date range filtering
✅ Export to Excel
✅ Comparative analytics
✅ Trend analysis

---

## BẢO MẬT & KIỂM SOÁT

### Authentication
- Session-based authentication
- Password hashing (bcrypt)
- Force password change on first login
- Logout on inactivity

### Authorization
- Role-based access control (RBAC)
- Route-level permissions
- Feature-level restrictions
- Data-level filtering

### Data Protection
- SQL injection prevention (PDO)
- XSS protection (htmlspecialchars)
- CSRF tokens
- Input validation & sanitization

### Audit & Compliance
- Complete audit trail
- User action logging
- Change history tracking
- Compliance reports

---

## DEPLOYMENT & CONFIGURATION

### System Requirements
- **PHP:** >= 8.0
- **MySQL:** >= 8.0 hoặc MariaDB >= 10.5
- **Web Server:** Apache 2.4+ hoặc Nginx
- **PHP Extensions:**
  - PDO, PDO_MySQL
  - mbstring
  - openssl
  - xml
  - zip
  - gd (image processing)

### Environment Variables (.env)
```env
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=mini_market
DB_USERNAME=root
DB_PASSWORD=

APP_ENV=production
APP_DEBUG=false
APP_URL=http://minigo.local
```

### Installation Steps
1. Clone repository
2. Run `composer install`
3. Copy `.env.example` to `.env`
4. Configure database credentials
5. Import `database/migrations/db.sql`
6. Run additional migrations
7. Configure web server (DocumentRoot = `/public`)
8. Access via browser

---

## KẾT LUẬN

Website Siêu thị mini MINIGO là một hệ thống quản lý toàn diện, hiện đại, được xây dựng bằng công nghệ web phổ biến (PHP, MySQL, Tailwind CSS, Alpine.js). Hệ thống cung cấp đầy đủ các tính năng cần thiết cho việc vận hành một siêu thị mini từ quản lý sản phẩm, kho hàng, nhân viên, đến báo cáo kinh doanh.

### Ưu điểm
- ✅ Giao diện thân thiện, responsive
- ✅ Tính năng đầy đủ, phù hợp thực tế
- ✅ Hiệu năng tốt với Alpine.js (client-side reactivity)
- ✅ Bảo mật cao với RBAC và audit logs
- ✅ Dễ mở rộng và bảo trì (MVC architecture)
- ✅ Hỗ trợ import/export Excel tiện lợi

### Hướng phát triển
- 🔄 Tích hợp API thanh toán online (VNPay, MoMo)
- 🔄 Mobile app cho khách hàng
- 🔄 Tích hợp máy quét mã vạch
- 🔄 Báo cáo nâng cao với AI/ML
- 🔄 Multi-store support (nhiều chi nhánh)
- 🔄 Real-time sync với cloud

---

**Ngày tạo:** 07/11/2025
**Phiên bản:** 1.0
