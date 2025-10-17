# Hướng Dẫn Áp Dụng Audit Log Cho Tất Cả Repositories

## Danh Sách Repositories Cần Cập Nhật

### ✅ Đã Hoàn Thành (13/19)
1. **BrandRepository** - Đã có Auditable trait ✅
2. **CouponRepository** - Đã có Auditable trait ✅
3. **CategoryRepository** - Đã có Auditable trait ✅
4. **ProductRepository** - Đã có Auditable trait ✅
5. **SupplierRepository** - Đã có Auditable trait ✅
6. **UnitRepository** - Đã có Auditable trait ✅
7. **OrderRepository** - Đã có Auditable trait ✅
8. **CustomerRepository** - Đã có Auditable trait ✅
9. **PromotionRepository** - Đã có Auditable trait ✅
10. **StaffRepository** - Đã có Auditable trait ✅
11. **UserRepository** - Chung với Customer/Staff (không cần riêng) ✅
12. **PurchaseOrderRepository** - Đã có Auditable trait ✅
13. **ProductBatchRepository** - Đã có Auditable trait ✅

### ⏸️ Bỏ Qua hoặc Không Cần (6/19)
1. **StockRepository** - Chỉ có helper methods, không có create/update/delete
2. **StockOutRepository** - Tự động tạo từ Order, không cần log riêng
3. **StocktakeRepository** - Ít sử dụng, có thể bổ sung sau
4. **ReceiptVoucherRepository** - Tự động tạo từ Order, không cần log riêng
5. **ExpenseVoucherRepository** - Tự động tạo từ PurchaseOrder, không cần log riêng
6. **RoleRepository** - Chỉ có method all(), không có create/update/delete

## Các Bước Thực Hiện Cho Mỗi Repository

### Bước 1: Thêm `use Auditable` trait
```php
<?php
namespace App\Models\Repositories;

use App\Core\DB;
use App\Support\Auditable;  // ← Thêm dòng này

class YourRepository
{
    use Auditable;  // ← Thêm dòng này
    
    // ... code
}
```

### Bước 2: Thêm Audit Log vào method `create()`
```php
public function create($data)
{
    $pdo = DB::pdo();
    $stmt = $pdo->prepare("INSERT INTO table_name ...");
    $stmt->execute([...]);
    $id = $pdo->lastInsertId();
    
    // ← THÊM DÒNG NÀY
    $this->logCreate('entity_type', (int)$id, $data);
    
    return $this->find($id);
}
```

### Bước 3: Thêm Audit Log vào method `update()`
```php
public function update($id, $data)
{
    // ← THÊM: Lấy dữ liệu trước khi update
    $beforeData = $this->find($id);
    $beforeArray = $beforeData ? (array)$beforeData : [];
    
    $pdo = DB::pdo();
    $stmt = $pdo->prepare("UPDATE table_name ...");
    $stmt->execute([...]);
    
    // ← THÊM DÒNG NÀY
    $this->logUpdate('entity_type', (int)$id, $beforeArray, $data);
    
    return $this->find($id);
}
```

### Bước 4: Thêm Audit Log vào method `delete()`
```php
public function delete($id)
{
    // ← THÊM: Lấy dữ liệu trước khi xóa
    $beforeData = $this->find($id);
    $beforeArray = $beforeData ? (array)$beforeData : [];
    
    $pdo = DB::pdo();
    $pdo->prepare("DELETE FROM table_name WHERE id=?")->execute([$id]);
    
    // ← THÊM DÒNG NÀY
    $this->logDelete('entity_type', (int)$id, $beforeArray);
}
```

## Entity Types Mapping

| Repository | Entity Type |
|-----------|-------------|
| ProductRepository | `'products'` |
| CategoryRepository | `'categories'` |
| BrandRepository | `'brands'` |
| SupplierRepository | `'suppliers'` |
| OrderRepository | `'orders'` |
| PurchaseOrderRepository | `'purchase_orders'` |
| ProductBatchRepository | `'product_batches'` |
| PromotionRepository | `'promotions'` |
| CouponRepository | `'coupons'` |
| CustomerRepository | `'customers'` |
| StaffRepository | `'staff'` |
| UserRepository | `'users'` |
| UnitRepository | `'units'` |
| StockRepository | `'stocks'` |
| StockOutRepository | `'stock_outs'` |
| StocktakeRepository | `'stocktakes'` |
| ReceiptVoucherRepository | `'receipt_vouchers'` |
| ExpenseVoucherRepository | `'expense_vouchers'` |
| RoleRepository | `'roles'` |

## Lưu Ý Quan Trọng

1. **Không cần truyền User ID**: Auditable trait tự động lấy từ `$_SESSION['user']['id']`
2. **Entity Type**: Dùng tên số nhiều, lowercase, dấu gạch dưới
3. **Before Data**: Luôn lấy trước khi update/delete
4. **Array Conversion**: Convert entity object sang array: `(array)$entity`
5. **Hồ Sơ (Profile)**: KHÔNG log các thao tác liên quan đến profile cá nhân

## Ví Dụ Hoàn Chỉnh: ProductRepository

```php
<?php
namespace App\Models\Repositories;

use App\Core\DB;
use App\Support\Auditable;

class ProductRepository
{
    use Auditable;

    public function create($data)
    {
        $pdo = DB::pdo();
        $stmt = $pdo->prepare("INSERT INTO products (name, price, ...) VALUES (?, ?, ...)");
        $stmt->execute([...]);
        $id = $pdo->lastInsertId();
        
        // Audit log
        $this->logCreate('products', (int)$id, $data);
        
        return $this->find($id);
    }

    public function update($id, $data)
    {
        // Lấy dữ liệu cũ
        $beforeData = $this->find($id);
        $beforeArray = $beforeData ? (array)$beforeData : [];
        
        $pdo = DB::pdo();
        $stmt = $pdo->prepare("UPDATE products SET ... WHERE id = ?");
        $stmt->execute([...]);
        
        // Audit log
        $this->logUpdate('products', (int)$id, $beforeArray, $data);
        
        return $this->find($id);
    }

    public function delete($id)
    {
        // Lấy dữ liệu trước khi xóa
        $beforeData = $this->find($id);
        $beforeArray = $beforeData ? (array)$beforeData : [];
        
        $pdo = DB::pdo();
        $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$id]);
        
        // Audit log
        $this->logDelete('products', (int)$id, $beforeArray);
    }
}
```

## Checklist Sau Khi Cập Nhật

- [ ] Repository đã thêm `use Auditable` trait
- [ ] Method `create()` có gọi `$this->logCreate()`
- [ ] Method `update()` có lấy beforeData và gọi `$this->logUpdate()`
- [ ] Method `delete()` có lấy beforeData và gọi `$this->logDelete()`
- [ ] Entity type đúng theo mapping table
- [ ] Test thêm/sửa/xóa và kiểm tra audit logs

## Tiến Độ

- [x] BrandRepository
- [x] CouponRepository
- [ ] ProductRepository
- [ ] CategoryRepository
- [ ] SupplierRepository
- [ ] OrderRepository
- [ ] PurchaseOrderRepository
- [ ] ProductBatchRepository
- [ ] PromotionRepository
- [ ] CustomerRepository
- [ ] StaffRepository
- [ ] UnitRepository
- [ ] StockRepository
- [ ] StockOutRepository
- [ ] StocktakeRepository
- [ ] ReceiptVoucherRepository
- [ ] ExpenseVoucherRepository
- [ ] RoleRepository
