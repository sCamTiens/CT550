# Triển khai Nhập Excel cho Chương trình Khuyến mãi

## Tổng quan
Đã triển khai đầy đủ chức năng nhập/xuất Excel cho module Chương trình khuyến mãi, theo mẫu của module Mã giảm giá.

## Các file đã thay đổi

### 1. Frontend - UI và JavaScript

#### `src/views/admin/promotions/promotion.php`
**Thay đổi:**
- ✅ Thêm nút "Lịch sử nhập" và "Nhập Excel" vào toolbar
- ✅ Thêm modal nhập Excel với các thành phần:
  - Input file với validation
  - Hiển thị file đã chọn
  - Hướng dẫn nhập liệu chi tiết
  - Nút tải file mẫu
  - Nút submit import
- ✅ Thêm các hàm JavaScript:
  - `openImportModal()` - Mở modal import
  - `handleFileSelect(e)` - Validate file (4 bước: định dạng, kích thước, tên, ký tự đặc biệt)
  - `clearFile()` - Xóa file đã chọn
  - `downloadTemplate()` - Tải file mẫu Excel
  - `submitImport()` - Gửi file lên server để import

**Validation trên Frontend:**
1. Định dạng file: Chỉ chấp nhận .xls, .xlsx
2. Kích thước: Tối đa 5MB
3. Độ dài tên file: Tối đa 255 ký tự
4. Ký tự đặc biệt: Không cho phép < > : " | ? *

---

### 2. Backend - Controller

#### `src/Controllers/Admin/PromotionController.php`
**Thêm mới 4 methods:**

##### a) `downloadTemplate()` - GET /admin/api/promotions/template
- Tạo file Excel mẫu với 13 cột:
  1. STT
  2. Tên chương trình*
  3. Mô tả
  4. Loại khuyến mãi* (discount)
  5. Loại giảm giá* (percentage/fixed)
  6. Giá trị*
  7. Áp dụng cho* (category/product)
  8. Danh mục (IDs, cách nhau bởi dấu phẩy)
  9. Sản phẩm (IDs, cách nhau bởi dấu phẩy)
  10. Độ ưu tiên
  11. Ngày bắt đầu* (dd/mm/yyyy HH:MM:SS)
  12. Ngày kết thúc* (dd/mm/yyyy HH:MM:SS)
  13. Trạng thái* (0/1)

- Bao gồm 2 dòng dữ liệu mẫu

##### b) `importExcel()` - POST /admin/api/promotions/import
- Validate file upload (định dạng, kích thước, tên file, ký tự đặc biệt)
- Đọc và parse file Excel
- Validate từng dòng dữ liệu:
  - Tên không trống và duy nhất
  - Loại khuyến mãi = 'discount'
  - Loại giảm giá = 'percentage' hoặc 'fixed'
  - Giá trị > 0 (và ≤ 100 nếu percentage)
  - Áp dụng cho = 'category' hoặc 'product'
  - Category IDs hoặc Product IDs tương ứng
  - Độ ưu tiên ≥ 0
  - Ngày bắt đầu và kết thúc đúng định dạng
  - Trạng thái = 0 hoặc 1
- Tạo bản ghi khuyến mãi cho dữ liệu hợp lệ
- Thu thập lỗi cho dữ liệu không hợp lệ
- Lưu lịch sử import vào database

##### c) `convertDateTimeFormat($dateStr)`
- Chuyển đổi định dạng ngày từ `dd/mm/yyyy HH:MM:SS` sang `yyyy-mm-dd HH:MM:SS`
- Sử dụng regex để parse và format lại

##### d) `saveImportHistory(...)`
- Lưu chi tiết quá trình import vào bảng `import_history`
- Lưu cả dữ liệu thành công và lỗi dưới dạng JSON

##### e) `currentUserName()`
- Lấy tên người dùng từ session
- Trả về 'Unknown' nếu không tìm thấy

---

### 3. Repository - Database Layer

#### `src/Models/Repositories/PromotionRepository.php`
**Thêm method:**

##### `findByName(string $name): ?Promotion`
- Tìm khuyến mãi theo tên
- Dùng để kiểm tra trùng lặp khi import
- Load đầy đủ thông tin liên quan (products, bundle rules, gift rules, combo items)

---

### 4. Routes

#### `public/index.php`
**Thêm 2 routes mới:**
```php
$r->get('/api/promotions/template', [AdminPromotion::class, 'downloadTemplate']);
$r->post('/api/promotions/import', [AdminPromotion::class, 'importExcel']);
```

---

### 5. Import History

#### `src/views/admin/import-history/index.php`
**Thêm hỗ trợ cho module promotions:**

1. **Dropdown filter:**
   - Thêm option "Chương trình khuyến mãi"

2. **getModuleName():**
   - Thêm mapping: `'promotions' => 'Chương trình khuyến mãi'`

3. **getModuleColor():**
   - Thêm màu: `'promotions' => 'bg-indigo-100 text-indigo-700'`

4. **getTableHeaders():**
   - Thêm cấu hình 11 cột cho promotions:
     * Dòng
     * Tên
     * Mô tả
     * Loại KM
     * Loại giảm
     * Giá trị
     * Áp dụng
     * Ưu tiên
     * Bắt đầu
     * Kết thúc
     * Trạng thái

---

## Luồng xử lý Import

### Frontend Flow:
1. User click "Nhập Excel" → Mở modal
2. User chọn file → Validate 4 bước
3. User click "Nhập dữ liệu" → Gửi POST request
4. Hiển thị kết quả (thành công/lỗi)

### Backend Flow:
1. Nhận file upload
2. Validate file (format, size, name)
3. Parse Excel file
4. Validate từng dòng dữ liệu
5. Tạo promotions cho dữ liệu hợp lệ
6. Thu thập errors cho dữ liệu không hợp lệ
7. Lưu import history
8. Trả về kết quả JSON

---

## Validation Rules

### File Level:
- ✅ Định dạng: .xls hoặc .xlsx
- ✅ Kích thước: ≤ 5MB
- ✅ Tên file: ≤ 255 ký tự
- ✅ Ký tự đặc biệt: Không có < > : " | ? *
- ✅ Số dòng: ≤ 1000 dòng dữ liệu

### Row Level:
- ✅ Tên: Bắt buộc, duy nhất
- ✅ Loại khuyến mãi: Chỉ nhận 'discount'
- ✅ Loại giảm giá: 'percentage' hoặc 'fixed'
- ✅ Giá trị: Số dương, ≤ 100 nếu percentage
- ✅ Áp dụng cho: 'category' hoặc 'product'
- ✅ Category/Product IDs: Bắt buộc theo apply_to
- ✅ Độ ưu tiên: Số không âm
- ✅ Ngày bắt đầu/kết thúc: Đúng định dạng dd/mm/yyyy HH:MM:SS
- ✅ Trạng thái: 0 hoặc 1

---

## Giới hạn hiện tại

**Chỉ hỗ trợ loại khuyến mãi 'discount':**
- ✅ discount (Giảm giá theo % hoặc số tiền cố định)
- ❌ bundle (Mua combo giá ưu đãi)
- ❌ gift (Mua tặng)
- ❌ combo (Combo sản phẩm)

**Lý do:**
- Loại 'discount' là phổ biến nhất
- Các loại khác có cấu trúc phức tạp (rules array, combo items)
- Có thể mở rộng trong Phase 2

---

## Test Cases

### Successful Import:
1. ✅ Import file mẫu → Thành công
2. ✅ Import với category_ids → Thành công
3. ✅ Import với product_ids → Thành công
4. ✅ Import với percentage discount → Thành công
5. ✅ Import với fixed discount → Thành công

### Failed Import:
1. ✅ File không đúng định dạng → Báo lỗi
2. ✅ File > 5MB → Báo lỗi
3. ✅ Tên file quá dài → Báo lỗi
4. ✅ Tên trùng lặp → Báo lỗi trong import history
5. ✅ Loại KM không hợp lệ → Báo lỗi trong import history
6. ✅ Giá trị không hợp lệ → Báo lỗi trong import history
7. ✅ Ngày không đúng format → Báo lỗi trong import history

---

## Tính năng tương tự đã triển khai

Cùng pattern đã áp dụng cho:
1. ✅ Nhà cung cấp (Suppliers)
2. ✅ Mã giảm giá (Coupons)
3. ✅ Chương trình khuyến mãi (Promotions) ← Mới thêm

---

## Hướng dẫn sử dụng

1. Vào trang "Quản lý khuyến mãi"
2. Click "Tải file mẫu" để download template
3. Điền dữ liệu vào file Excel (chú ý các cột bắt buộc có dấu *)
4. Click "Nhập Excel" và chọn file
5. Click "Nhập dữ liệu"
6. Xem kết quả import
7. Kiểm tra "Lịch sử nhập" để xem chi tiết (cả thành công và lỗi)

---

## Kết luận

✅ Đã hoàn thành triển khai đầy đủ chức năng nhập Excel cho Chương trình khuyến mãi
✅ Tuân thủ pattern của Coupon import
✅ Validation đầy đủ cả frontend và backend
✅ Import history đầy đủ
✅ Hỗ trợ loại 'discount' với category/product apply_to
✅ Sẵn sàng mở rộng cho bundle/gift/combo trong tương lai
