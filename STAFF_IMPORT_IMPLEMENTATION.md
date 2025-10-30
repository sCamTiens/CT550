# Triển khai Nhập Excel cho Nhân viên (Staff)

## Tổng quan
Đã triển khai đầy đủ chức năng nhập/xuất Excel cho module Nhân viên, theo cùng pattern với Promotions và Coupons.

## Các file đã thay đổi

### 1. Frontend - UI và JavaScript

#### `src/views/admin/staff/staff.php`
**Thay đổi:**
- ✅ Thêm nút "Lịch sử nhập" và "Nhập Excel" vào toolbar
- ✅ Thêm modal nhập Excel với các thành phần:
  - Input file với validation
  - Hiển thị file đã chọn
  - Hướng dẫn nhập liệu chi tiết (tài khoản, mật khẩu, vai trò, email, SĐT, ngày vào làm, trạng thái)
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

#### `src/Controllers/Admin/StaffController.php`
**Thêm mới 5 methods:**

##### a) `downloadTemplate()` - GET /admin/api/staff/template
- Tạo file Excel mẫu với 10 cột:
  1. STT
  2. Tài khoản* (username)
  3. Mật khẩu*
  4. Họ tên*
  5. Vai trò* (Admin/Kho/Nhân viên bán hàng/Hỗ trợ trực tuyến)
  6. Email*
  7. Số điện thoại*
  8. Ngày vào làm (dd/mm/yyyy)
  9. Ghi chú
  10. Trạng thái* (0/1)

- Bao gồm 2 dòng dữ liệu mẫu

##### b) `importExcel()` - POST /admin/api/staff/import
- Validate file upload (định dạng, kích thước, tên file, ký tự đặc biệt)
- Đọc và parse file Excel
- Validate từng dòng dữ liệu:
  - Tài khoản: Không trống, >= 6 ký tự, chỉ chữ cái không dấu/dấu chấm/gạch dưới, duy nhất
  - Mật khẩu: Không trống, >= 8 ký tự
  - Họ tên: Không trống, >= 3 ký tự
  - Vai trò: Phải thuộc danh sách ['Admin', 'Kho', 'Nhân viên bán hàng', 'Hỗ trợ trực tuyến']
  - Email: Không trống, đúng định dạng
  - Số điện thoại: Không trống, bắt đầu bằng 0, có 10 chữ số
  - Ngày vào làm: Định dạng dd/mm/yyyy (optional)
  - Trạng thái: 0 hoặc 1
- Tạo bản ghi nhân viên cho dữ liệu hợp lệ (mật khẩu được hash với bcrypt)
- Thu thập lỗi cho dữ liệu không hợp lệ
- Lưu lịch sử import vào database

##### c) `convertDateFormat($dateStr)`
- Chuyển đổi định dạng ngày từ `dd/mm/yyyy` sang `yyyy-mm-dd`
- Sử dụng regex để parse và format lại

##### d) `saveImportHistory(...)`
- Lưu chi tiết quá trình import vào bảng `import_history`
- Lưu cả dữ liệu thành công và lỗi dưới dạng JSON

##### e) `currentUserName()`
- Lấy tên người dùng từ session
- Trả về 'Unknown' nếu không tìm thấy

---

### 3. Repository - Database Layer

#### `src/Models/Repositories/StaffRepository.php`
**Thêm method:**

##### `findByUsername(string $username): ?array`
- Tìm nhân viên theo username
- Dùng để kiểm tra trùng lặp khi import
- Chỉ lấy nhân viên chưa bị xóa (is_deleted = 0)
- Trả về thông tin cơ bản: user_id, username, full_name, email, phone, is_active, staff_role, hired_at, note

---

### 4. Routes

#### `public/index.php`
**Thêm 2 routes mới:**
```php
$r->get('/api/staff/template', [AdminStaff::class, 'downloadTemplate']);
$r->post('/api/staff/import', [AdminStaff::class, 'importExcel']);
```

---

### 5. Import History

#### `src/views/admin/import-history/index.php`
**Cập nhật table headers cho module staff:**

**getTableHeaders() - 'staff':**
- Cập nhật từ dạng index sang field:
  1. Dòng (row)
  2. Tài khoản (username)
  3. Họ tên (full_name)
  4. Vai trò (staff_role)
  5. Email (email)
  6. Số điện thoại (phone)
  7. Trạng thái (is_active)

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
5. Hash mật khẩu với bcrypt
6. Tạo nhân viên cho dữ liệu hợp lệ (gọi `StaffRepository->create()`)
7. Thu thập errors cho dữ liệu không hợp lệ
8. Lưu import history
9. Trả về kết quả JSON

---

## Validation Rules

### File Level:
- ✅ Định dạng: .xls hoặc .xlsx
- ✅ Kích thước: ≤ 5MB
- ✅ Tên file: ≤ 255 ký tự
- ✅ Ký tự đặc biệt: Không có < > : " | ? *
- ✅ Số dòng: ≤ 1000 dòng dữ liệu

### Row Level:
- ✅ Tài khoản: Bắt buộc, ≥6 ký tự, chỉ chữ cái không dấu/dấu chấm/gạch dưới, duy nhất
- ✅ Mật khẩu: Bắt buộc, ≥8 ký tự, được hash với bcrypt
- ✅ Họ tên: Bắt buộc, ≥3 ký tự
- ✅ Vai trò: Bắt buộc, thuộc ['Admin', 'Kho', 'Nhân viên bán hàng', 'Hỗ trợ trực tuyến']
- ✅ Email: Bắt buộc, đúng định dạng email
- ✅ Số điện thoại: Bắt buộc, bắt đầu bằng 0, 10 chữ số
- ✅ Ngày vào làm: Optional, định dạng dd/mm/yyyy
- ✅ Ghi chú: Optional
- ✅ Trạng thái: Bắt buộc, 0 hoặc 1

---

## Đặc điểm riêng của Staff Import

### 1. Bảo mật:
- ✅ Mật khẩu được hash với `password_hash($password, PASSWORD_BCRYPT)` trước khi lưu
- ✅ Không lưu mật khẩu plain text
- ✅ Mật khẩu phải tối thiểu 8 ký tự

### 2. Vai trò:
- ✅ 4 vai trò cố định: Admin, Kho, Nhân viên bán hàng, Hỗ trợ trực tuyến
- ✅ Validate chính xác tên vai trò

### 3. Tài khoản:
- ✅ Username phải duy nhất trong hệ thống
- ✅ Chỉ chấp nhận chữ cái không dấu, dấu chấm, gạch dưới
- ✅ Tối thiểu 6 ký tự

### 4. Liên hệ:
- ✅ Email phải đúng định dạng (validate bằng filter_var)
- ✅ Số điện thoại Việt Nam: bắt đầu bằng 0, đủ 10 số

---

## Test Cases

### Successful Import:
1. ✅ Import file mẫu → Thành công
2. ✅ Import với vai trò Admin → Thành công
3. ✅ Import với vai trò Kho → Thành công
4. ✅ Import với ngày vào làm → Thành công
5. ✅ Import không có ngày vào làm → Thành công

### Failed Import:
1. ✅ File không đúng định dạng → Báo lỗi
2. ✅ File > 5MB → Báo lỗi
3. ✅ Username trùng lặp → Báo lỗi trong import history
4. ✅ Username < 6 ký tự → Báo lỗi trong import history
5. ✅ Username có ký tự đặc biệt → Báo lỗi trong import history
6. ✅ Mật khẩu < 8 ký tự → Báo lỗi trong import history
7. ✅ Email không hợp lệ → Báo lỗi trong import history
8. ✅ SĐT không đúng format → Báo lỗi trong import history
9. ✅ Vai trò không hợp lệ → Báo lỗi trong import history

---

## Tính năng tương tự đã triển khai

Cùng pattern đã áp dụng cho:
1. ✅ Nhà cung cấp (Suppliers)
2. ✅ Mã giảm giá (Coupons)
3. ✅ Chương trình khuyến mãi (Promotions)
4. ✅ Nhân viên (Staff) ← Mới thêm

---

## Hướng dẫn sử dụng

1. Vào trang "Quản lý nhân viên"
2. Click "Tải file mẫu" để download template
3. Điền dữ liệu vào file Excel (chú ý các cột bắt buộc có dấu *)
4. Vai trò phải chính xác: Admin, Kho, Nhân viên bán hàng, hoặc Hỗ trợ trực tuyến
5. Mật khẩu sẽ được hash tự động (tối thiểu 8 ký tự)
6. Click "Nhập Excel" và chọn file
7. Click "Nhập dữ liệu"
8. Xem kết quả import
9. Kiểm tra "Lịch sử nhập" để xem chi tiết (cả thành công và lỗi)

---

## Lưu ý quan trọng

### Mật khẩu:
- Mật khẩu trong file Excel sẽ được hash với bcrypt trước khi lưu
- Người dùng cần đổi mật khẩu sau lần đăng nhập đầu tiên (force_change_password = 1)

### Vai trò:
- Nhập chính xác tên vai trò (có dấu nếu có)
- Không viết tắt hoặc thay đổi tên vai trò

### Username:
- Chỉ chứa: chữ cái không dấu (a-z, A-Z), dấu chấm (.), gạch dưới (_)
- Ví dụ hợp lệ: `nhanvien01`, `thu.kho`, `admin_user`
- Ví dụ không hợp lệ: `nhân-viên`, `user@01`, `nv 01`

---

## Kết luận

✅ Đã hoàn thành triển khai đầy đủ chức năng nhập Excel cho Nhân viên
✅ Tuân thủ pattern của Coupon/Promotion import
✅ Validation đầy đủ cả frontend và backend
✅ Bảo mật mật khẩu với bcrypt hashing
✅ Import history đầy đủ
✅ Kiểm tra username trùng lặp
✅ Validate vai trò và thông tin liên hệ chính xác
