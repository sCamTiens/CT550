# Hệ thống phân quyền theo vai trò (Role-Based Access Control)

## Tổng quan

Hệ thống phân quyền đã được triển khai dựa trên bảng `staff_profiles` với 4 vai trò chính:
- **Admin**: Toàn quyền truy cập
- **Kho**: Quản lý kho, sản phẩm, nhập/xuất hàng
- **Nhân viên bán hàng**: Quản lý đơn hàng, khách hàng
- **Hỗ trợ trực tuyến**: Xem thông tin khách hàng và đơn hàng

## Cấu trúc triển khai

### 1. RoleMiddleware (`src/Middlewares/RoleMiddleware.php`)

Middleware chính xử lý phân quyền với các chức năng:

#### Phân quyền theo vai trò:

**Admin** - Truy cập tất cả trang

**Kho** - Quyền truy cập:
- Dashboard (`/admin`)
- Danh mục sản phẩm:
  - Loại sản phẩm (`/admin/categories`)
  - Thương hiệu (`/admin/brands`)
  - Sản phẩm (`/admin/products`)
  - Nhà cung cấp (`/admin/suppliers`)
  - Đơn vị tính (`/admin/units`)
- Quản lý kho:
  - Tồn kho (`/admin/stocks`)
  - Phiếu nhập kho (`/admin/purchase-orders`)
  - Phiếu xuất kho (`/admin/stock-outs`)
  - Kiểm kê kho (`/admin/stocktakes`)
  - Lô sản phẩm (`/admin/product-batches`)
- Quản lý thu chi:
  - Phiếu thu (`/admin/receipt_vouchers`)
  - Phiếu chi (`/admin/expense_vouchers`)

**Nhân viên bán hàng** - Quyền truy cập:
- Dashboard (`/admin`)
- Quản lý đơn hàng (`/admin/orders`)
- Quản lý khách hàng (`/admin/customers`)
- Sản phẩm - chỉ xem (`/admin/products`)
- Ưu đãi:
  - Mã giảm giá (`/admin/coupons`)
  - Chương trình khuyến mãi (`/admin/promotions`)

**Hỗ trợ trực tuyến** - Quyền truy cập:
- Dashboard (`/admin`)
- Quản lý khách hàng (`/admin/customers`)
- Quản lý đơn hàng - chỉ xem (`/admin/orders`)

#### Các phương thức chính:

```php
// Kiểm tra quyền truy cập
RoleMiddleware::checkAccess(string $requestPath): bool

// Kiểm tra và redirect nếu không có quyền
RoleMiddleware::authorize(string $requestPath): void

// Lấy danh sách sections được phép hiển thị trên sidebar
RoleMiddleware::getAllowedSections(): array

// Kiểm tra quyền truy cập vào một section cụ thể
RoleMiddleware::canAccessSection(string $section): bool
```

### 2. BaseAdminController (`src/Controllers/Admin/BaseAdminController.php`)

Controller cơ sở cho tất cả admin controllers, tự động:
1. Kiểm tra đăng nhập admin
2. Kiểm tra quyền truy cập dựa trên `staff_role`
3. Redirect về `/admin/login` nếu không có quyền

**Các trang công khai** (không cần kiểm tra quyền):
- `/admin/login`
- `/admin/logout`
- `/admin/profile`
- `/admin/force-change-password`
- `/admin/logout-force`

### 3. Sidebar (`src/views/admin/partials/sidebar.php`)

Sidebar tự động ẩn/hiện menu items dựa trên quyền của user:

```php
<?php
use App\Middlewares\RoleMiddleware;
$allowedSections = RoleMiddleware::getAllowedSections();
?>

<!-- Ví dụ: Chỉ hiển thị menu "Danh mục sản phẩm" nếu có quyền -->
<?php if ($allowedSections['catalog']): ?>
<div class="mt-2">
    <!-- Menu Danh mục sản phẩm -->
</div>
<?php endif; ?>
```

### 4. Admin Controllers

Tất cả admin controllers đã được cập nhật để extends `BaseAdminController`:
- ✅ BrandController
- ✅ CategoryController
- ✅ CustomerController
- ✅ StaffController
- ✅ StockController
- ✅ StocktakeController
- ✅ StockOutController
- ✅ SupplierController
- ✅ UnitController
- ✅ RoleController
- ✅ (và các controllers khác đã extends BaseAdminController trước đó)

## Luồng hoạt động

1. **User đăng nhập** → `AuthController::login()`
   - Lấy `staff_role` từ bảng `staff_profiles`
   - Lưu vào `$_SESSION['user']['staff_role']`

2. **User truy cập trang admin** → Controller extends `BaseAdminController`
   - Constructor `BaseAdminController` tự động chạy
   - Kiểm tra đăng nhập
   - Kiểm tra quyền truy cập qua `RoleMiddleware::authorize()`
   - Nếu không có quyền → Xóa session và redirect về login

3. **Render sidebar** → `sidebar.php`
   - Gọi `RoleMiddleware::getAllowedSections()`
   - Chỉ hiển thị menu items user có quyền

4. **User cố truy cập trực tiếp bằng URL** → Bị chặn bởi BaseAdminController
   - Session bị xóa
   - Redirect về `/admin/login`

## Bảo mật

✅ **Kiểm tra backend**: Tất cả admin routes được bảo vệ bởi BaseAdminController
✅ **Kiểm tra frontend**: Sidebar tự động ẩn menu không có quyền
✅ **Session cleanup**: Xóa session khi truy cập trái phép
✅ **Whitelist paths**: Các trang public được định nghĩa rõ ràng

## Mở rộng

### Thêm quyền mới cho vai trò:

Sửa file `src/Middlewares/RoleMiddleware.php`:

```php
private static $rolePermissions = [
    'Admin' => '*',
    'Nhân viên bán hàng' => [
        '/admin',
        '/admin/orders',
        '/admin/customers',
        '/admin/products',
        '/admin/coupons',
        '/admin/promotions',
        '/admin/new-feature', // ← Thêm quyền mới
    ],
    // ...
];
```

### Thêm vai trò mới:

1. Cập nhật ENUM trong database:
```sql
ALTER TABLE staff_profiles 
MODIFY staff_role ENUM('Kho','Nhân viên bán hàng','Hỗ trợ trực tuyến','Admin','Vai trò mới') NOT NULL;
```

2. Thêm quyền trong `RoleMiddleware.php`:
```php
private static $rolePermissions = [
    // ... các role hiện tại
    'Vai trò mới' => [
        '/admin',
        '/admin/specific-pages',
    ],
];
```

3. Cập nhật `getAllowedSections()` nếu cần hiển thị menu mới.

## Testing

### Test cases cần kiểm tra:

1. ✅ Admin có thể truy cập tất cả trang
2. ✅ Kho không thể truy cập `/admin/orders`
3. ✅ Nhân viên bán hàng không thể truy cập `/admin/stocks`
4. ✅ Hỗ trợ trực tuyến chỉ xem được orders và customers
5. ✅ Sidebar chỉ hiển thị menu có quyền
6. ✅ Truy cập trực tiếp URL không có quyền → redirect về login
7. ✅ Session bị xóa khi truy cập trái phép

## Lưu ý

- Tất cả admin users PHẢI có `staff_role` trong bảng `staff_profiles`
- Nếu user không có `staff_role` → không thể truy cập bất kỳ trang admin nào
- ForceChangePasswordController extends Controller (không extends BaseAdminController) để cho phép user đổi mật khẩu lần đầu
- AuthController extends Controller để cho phép login/logout không bị chặn
