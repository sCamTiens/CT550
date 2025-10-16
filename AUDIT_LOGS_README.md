# 📋 Hệ Thống Audit Logs (Lịch Sử Thao Tác)

## Tổng Quan
Hệ thống audit logs tự động ghi lại mọi hành động thêm/sửa/xóa trong hệ thống, giúp:
- **Truy vết**: Ai làm gì, khi nào, ở đâu
- **Bảo mật**: Phát hiện hành vi bất thường
- **Tuân thủ**: Đáp ứng yêu cầu audit
- **Rollback**: Xem lại trạng thái trước khi thay đổi

## Các Thành Phần

### 1. Database Table
```sql
CREATE TABLE audit_logs (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  actor_user_id BIGINT NULL,           -- Người thực hiện
  entity_type VARCHAR(64) NOT NULL,    -- Loại đối tượng (products, orders, ...)
  entity_id BIGINT NOT NULL,           -- ID của đối tượng
  action VARCHAR(32) NOT NULL,         -- Hành động (create, update, delete, ...)
  before_data JSON,                    -- Dữ liệu TRƯỚC khi thay đổi
  after_data JSON,                     -- Dữ liệu SAU khi thay đổi
  created_at DATETIME NOT NULL,
  INDEX idx_audit_entity (entity_type, entity_id),
  CONSTRAINT fk_audit_user FOREIGN KEY(actor_user_id) REFERENCES users(id)
) ENGINE=InnoDB;
```

### 2. AuditLogRepository
**File**: `src/Models/Repositories/AuditLogRepository.php`

**Các method chính**:
- `log()` - Ghi log
- `all()` - Lấy toàn bộ logs với filter
- `getByEntity()` - Lấy logs của 1 entity cụ thể
- `getByUser()` - Lấy logs của 1 user
- `statsByAction()` - Thống kê theo hành động
- `statsByEntity()` - Thống kê theo đối tượng
- `statsByUser()` - Thống kê theo người dùng

### 3. Auditable Trait
**File**: `src/Support/Auditable.php`

Trait giúp dễ dàng thêm audit log vào các Repository.

**Cách sử dụng**:

```php
<?php
namespace App\Models\Repositories;

use App\Support\Auditable;

class ProductRepository
{
    use Auditable;

    public function create(array $data, int $currentUser): int
    {
        // ... logic tạo sản phẩm ...
        $id = (int) $pdo->lastInsertId();
        
        // Ghi log
        $this->logCreate('products', $id, $data, $currentUser);
        
        return $id;
    }

    public function update(int $id, array $data, int $currentUser): void
    {
        // Lấy dữ liệu cũ
        $beforeData = $this->findOne($id);
        
        // ... logic update ...
        
        // Ghi log
        if ($beforeData) {
            $this->logUpdate('products', $id, $beforeData, $data, $currentUser);
        }
    }

    public function delete(int $id, ?int $currentUser = null): void
    {
        // Lấy dữ liệu trước khi xóa
        $beforeData = $this->findOne($id);
        
        // ... logic xóa ...
        
        // Ghi log
        if ($beforeData) {
            $this->logDelete('products', $id, $beforeData, $currentUser);
        }
    }
}
```

**Các method có sẵn**:
- `logCreate($entityType, $entityId, $afterData, $actorUserId)` - Log tạo mới
- `logUpdate($entityType, $entityId, $beforeData, $afterData, $actorUserId)` - Log cập nhật
- `logDelete($entityType, $entityId, $beforeData, $actorUserId)` - Log xóa
- `logRestore($entityType, $entityId, $afterData, $actorUserId)` - Log khôi phục
- `logStatusChange($entityType, $entityId, $oldStatus, $newStatus, $actorUserId)` - Log đổi trạng thái

### 4. Controller & View
**File**: 
- Controller: `src/Controllers/Admin/AuditLogController.php`
- View: `src/views/admin/audit-logs/audit-log.php`

**Routes**:
```
GET  /admin/audit-logs                      - Giao diện xem logs
GET  /admin/api/audit-logs                  - API lấy danh sách logs
GET  /admin/api/audit-logs/entity/{type}/{id} - Logs của 1 entity
GET  /admin/api/audit-logs/stats/action     - Thống kê theo action
GET  /admin/api/audit-logs/stats/entity     - Thống kê theo entity
GET  /admin/api/audit-logs/stats/user       - Thống kê theo user
```

## Tính Năng Giao Diện

### 1. Bộ Lọc Mạnh Mẽ
- **Tìm kiếm**: Tìm theo tên người dùng, nội dung
- **Loại đối tượng**: Sản phẩm, đơn hàng, người dùng, ...
- **Hành động**: Thêm, sửa, xóa, khôi phục, đổi trạng thái
- **Khoảng thời gian**: Từ ngày - đến ngày
- **Người thực hiện**: Lọc theo user ID

### 2. Xem Chi Tiết
- So sánh **TRƯỚC** và **SAU** khi thay đổi
- Hiển thị JSON format đẹp, dễ đọc
- Màu đỏ cho dữ liệu cũ, xanh cho dữ liệu mới

### 3. Thống Kê
- **Theo hành động**: Bao nhiêu lần create/update/delete
- **Theo đối tượng**: Đối tượng nào bị thao tác nhiều nhất
- **Theo người dùng**: Top người dùng active nhất

### 4. Phân Trang
- Hiển thị 20 bản ghi/trang
- Điều hướng trước/sau dễ dàng
- Hiển thị tổng số bản ghi

## Cách Triển Khai

### Bước 1: Database đã có sẵn
Table `audit_logs` đã được tạo trong `db.sql`.

### Bước 2: Áp Dụng Audit cho Repository

**Ví dụ với ProductRepository**:

```php
<?php
namespace App\Models\Repositories;

use App\Core\DB;
use App\Support\Auditable;

class ProductRepository
{
    use Auditable;  // <-- Thêm trait này

    public function create(array $data, int $currentUser): int
    {
        $pdo = DB::pdo();
        // ... INSERT logic ...
        $id = (int) $pdo->lastInsertId();
        
        // Ghi log audit
        $this->logCreate('products', $id, $data, $currentUser);
        
        return $id;
    }

    public function update(int $id, array $data, int $currentUser): void
    {
        $beforeData = $this->findOne($id);  // Lấy data cũ
        
        $pdo = DB::pdo();
        // ... UPDATE logic ...
        
        // Ghi log audit
        if ($beforeData) {
            $this->logUpdate('products', $id, $beforeData, $data, $currentUser);
        }
    }

    public function delete(int $id, ?int $currentUser = null): void
    {
        $beforeData = $this->findOne($id);  // Lấy data trước khi xóa
        
        $pdo = DB::pdo();
        // ... DELETE logic ...
        
        // Ghi log audit
        if ($beforeData) {
            $this->logDelete('products', $id, $beforeData, $currentUser);
        }
    }
}
```

### Bước 3: Áp Dụng cho Các Repository Khác

Làm tương tự cho:
- ✅ **CouponRepository** (đã áp dụng)
- ⏳ **PromotionRepository**
- ⏳ **ProductRepository**
- ⏳ **CategoryRepository**
- ⏳ **BrandRepository**
- ⏳ **SupplierRepository**
- ⏳ **OrderRepository**
- ⏳ **PurchaseOrderRepository**
- ⏳ **UserRepository** (StaffRepository, CustomerRepository)

### Bước 4: Truy Cập

1. Đăng nhập admin
2. Vào `/admin/audit-logs`
3. Xem lịch sử, lọc, xem chi tiết, xem thống kê

## Entity Types Mapping

| Entity Type | Mô Tả | Repository |
|------------|-------|------------|
| `products` | Sản phẩm | ProductRepository |
| `categories` | Danh mục | CategoryRepository |
| `brands` | Thương hiệu | BrandRepository |
| `suppliers` | Nhà cung cấp | SupplierRepository |
| `orders` | Đơn hàng | OrderRepository |
| `users` | Người dùng | UserRepository |
| `coupons` | Mã giảm giá | CouponRepository |
| `promotions` | Khuyến mãi | PromotionRepository |
| `purchase_orders` | Phiếu nhập | PurchaseOrderRepository |
| `product_batches` | Lô hàng | ProductBatchRepository |
| `stock_outs` | Phiếu xuất | StockOutRepository |
| `expense_vouchers` | Phiếu chi | ExpenseVoucherRepository |
| `receipt_vouchers` | Phiếu thu | ReceiptVoucherRepository |

## Action Types

| Action | Mô Tả | Badge Color |
|--------|-------|-------------|
| `create` | Thêm mới | 🟢 Green |
| `update` | Cập nhật | 🔵 Blue |
| `delete` | Xóa | 🔴 Red |
| `restore` | Khôi phục | 🟡 Yellow |
| `status_change` | Đổi trạng thái | 🟣 Purple |

## Best Practices

### 1. Luôn Lấy Data Cũ Trước Khi Update/Delete
```php
// ✅ ĐÚNG
$beforeData = $this->findOne($id);
// ... update logic ...
$this->logUpdate('products', $id, $beforeData, $data, $currentUser);

// ❌ SAI - Không có beforeData
$this->logUpdate('products', $id, [], $data, $currentUser);
```

### 2. Chỉ Log Những Trường Quan Trọng
```php
// Bỏ các trường không cần thiết
unset($data['created_at']);
unset($data['updated_at']);
unset($data['created_by_name']);
unset($data['updated_by_name']);

$this->logUpdate('products', $id, $beforeData, $data, $currentUser);
```

### 3. Sử Dụng Transaction Khi Log
```php
try {
    $pdo->beginTransaction();
    
    // ... business logic ...
    $id = $pdo->lastInsertId();
    
    // Log audit
    $this->logCreate('products', $id, $data, $currentUser);
    
    $pdo->commit();
} catch (\Exception $e) {
    $pdo->rollBack();
    throw $e;
}
```

### 4. Log Status Change Riêng
```php
// Khi chỉ đổi trạng thái, dùng logStatusChange
$oldStatus = $order['status'];
// ... update status ...
$newStatus = 'completed';

$this->logStatusChange('orders', $id, $oldStatus, $newStatus, $currentUser);
```

## Performance Tips

### 1. Index
Database đã có index tốt:
```sql
INDEX idx_audit_entity (entity_type, entity_id)
```

### 2. Phân Trang
View đã có pagination (20 records/page).

### 3. Cleanup Old Logs
Nên có cronjob xóa logs cũ (> 1 năm):
```sql
DELETE FROM audit_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 YEAR);
```

### 4. Archive
Với hệ thống lớn, nên archive logs sang bảng khác:
```sql
CREATE TABLE audit_logs_archive LIKE audit_logs;
INSERT INTO audit_logs_archive SELECT * FROM audit_logs WHERE created_at < '2024-01-01';
DELETE FROM audit_logs WHERE created_at < '2024-01-01';
```

## Ví Dụ Sử Dụng API

### Lấy logs của 1 sản phẩm
```javascript
fetch('/admin/api/audit-logs/entity/products/123')
  .then(res => res.json())
  .then(data => console.log(data.items));
```

### Lấy logs với filter
```javascript
const params = new URLSearchParams({
  entity_type: 'orders',
  action: 'create',
  from_date: '2024-10-01',
  to_date: '2024-10-31'
});

fetch(`/admin/api/audit-logs?${params}`)
  .then(res => res.json())
  .then(data => console.log(data.items));
```

### Lấy thống kê
```javascript
fetch('/admin/api/audit-logs/stats/user')
  .then(res => res.json())
  .then(data => console.log(data.stats));
```

## Troubleshooting

### Logs không được ghi
1. Kiểm tra table `audit_logs` đã tạo chưa
2. Kiểm tra Repository đã `use Auditable` chưa
3. Kiểm tra đã gọi `logCreate/Update/Delete` chưa
4. Kiểm tra `$currentUser` có đúng không

### Lỗi foreign key
- `actor_user_id` phải tồn tại trong `users` table
- Hoặc set `actor_user_id = NULL` nếu là system action

### View không hiển thị
- Kiểm tra routes đã đăng ký chưa
- Kiểm tra permission (chỉ admin mới xem được)
- Kiểm tra console browser có lỗi JS không

## Tóm Tắt

Hệ thống audit logs giúp:
- ✅ Tự động ghi lại mọi thao tác
- ✅ So sánh trước/sau thay đổi
- ✅ Thống kê chi tiết
- ✅ Dễ dàng tích hợp vào repository
- ✅ Giao diện đẹp, filter mạnh mẽ

**Access**: `/admin/audit-logs`
