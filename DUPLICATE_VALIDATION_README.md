# Validation Trùng Email và Số Điện Thoại

## ✅ Đã Cập Nhật

### 1. **StaffRepository** (`src/Models/Repositories/StaffRepository.php`)
- ✅ Thêm method `checkDuplicateContact()` để kiểm tra trùng email/phone
- ✅ Thêm validation trong `create()` - Kiểm tra trước khi thêm nhân viên mới
- ✅ Thêm validation trong `update()` - Kiểm tra trước khi cập nhật (loại trừ chính user đang sửa)
- ✅ Cập nhật `mapDuplicateError()` để bắt lỗi trùng phone từ database constraint

### 2. **CustomerRepository** (`src/Models/Repositories/CustomerRepository.php`)
- ✅ Thêm method `checkDuplicateContact()` để kiểm tra trùng email/phone
- ✅ Thêm validation trong `create()` - Kiểm tra trước khi thêm khách hàng mới
- ✅ Thêm validation trong `update()` - Kiểm tra trước khi cập nhật (loại trừ chính user đang sửa)
- ✅ Cập nhật error mapping để bắt lỗi trùng phone từ database constraint

## 🔍 Cách Hoạt Động

### Khi Thêm Mới (Create)
```php
// Kiểm tra email và phone có tồn tại trong toàn hệ thống chưa
if ($err = $this->checkDuplicateContact($email, $phone)) {
    return $err; // Trả về: "Email đã tồn tại trong hệ thống" hoặc "Số điện thoại đã tồn tại trong hệ thống"
}
```

### Khi Cập Nhật (Update)
```php
// Kiểm tra email và phone có trùng với user khác không (loại trừ chính user đang sửa)
if ($err = $this->checkDuplicateContact($email, $phone, $userId)) {
    return $err;
}
```

## 📋 Thông Báo Lỗi

Các thông báo lỗi sẽ hiển thị:
- ❌ **"Email đã tồn tại trong hệ thống"** - Khi email trùng
- ❌ **"Số điện thoại đã tồn tại trong hệ thống"** - Khi phone trùng
- ❌ **"Tên tài khoản đã tồn tại trong hệ thống"** - Khi username trùng

## 🧪 Test Cases

### Test 1: Thêm Nhân Viên với Email Trùng
1. Vào `/admin/staff`
2. Thêm nhân viên mới với email đã tồn tại
3. Kết quả mong đợi: Hiển thị lỗi "Email đã tồn tại trong hệ thống"

### Test 2: Thêm Khách Hàng với Phone Trùng
1. Vào `/admin/customers`
2. Thêm khách hàng mới với số điện thoại đã tồn tại
3. Kết quả mong đợi: Hiển thị lỗi "Số điện thoại đã tồn tại trong hệ thống"

### Test 3: Sửa Nhân Viên giữ nguyên Email của chính mình
1. Vào `/admin/staff`
2. Sửa nhân viên, giữ nguyên email của chính họ
3. Kết quả mong đợi: ✅ Cho phép update (không báo lỗi trùng)

### Test 4: Sửa Khách Hàng dùng Email của người khác
1. Vào `/admin/customers`
2. Sửa khách hàng, dùng email của khách hàng khác
3. Kết quả mong đợi: ❌ Hiển thị lỗi "Email đã tồn tại trong hệ thống"

## 🔐 Database Constraints

Để tăng cường bảo mật, bạn nên thêm UNIQUE constraint vào database:

```sql
-- Thêm unique constraint cho email
ALTER TABLE users ADD UNIQUE KEY unique_email (email);

-- Thêm unique constraint cho phone
ALTER TABLE users ADD UNIQUE KEY unique_phone (phone);
```

**Lưu ý:** Email và phone có thể NULL, nên cần xử lý đúng constraint để cho phép multiple NULL values.

## 💡 Tips

1. **Email case-insensitive**: Hiện tại so sánh exact match. Nếu muốn case-insensitive, có thể dùng `LOWER()` trong SQL.
2. **Phone format**: Validation đã check format `0xxxxxxxxx` (10-11 số) ở Controller.
3. **Audit Log**: Mọi thay đổi đều được log vào bảng `audit_logs`.

## 🎯 Hoàn Tất!

Bây giờ cả 2 module **Nhân viên** và **Khách hàng** đã có validation đầy đủ để:
- ✅ Ngăn chặn email trùng
- ✅ Ngăn chặn số điện thoại trùng
- ✅ Cho phép user giữ nguyên email/phone của chính mình khi update
- ✅ Hiển thị thông báo lỗi rõ ràng bằng tiếng Việt
