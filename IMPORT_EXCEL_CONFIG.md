# Cấu hình Import Excel

## Giới hạn file upload

Hệ thống đã được cấu hình để chấp nhận file Excel với các giới hạn sau:

### 1. Kích thước file
- **Tối đa: 10MB**
- File vượt quá kích thước này sẽ bị từ chối

### 2. Định dạng file
- **Chấp nhận:** `.xls`, `.xlsx`
- Các định dạng khác sẽ bị từ chối

### 3. Tên file
- **Độ dài tối đa:** 255 ký tự
- **Ký tự cho phép:** Chữ cái (a-z, A-Z), số (0-9), dấu gạch ngang (-), gạch dưới (_), khoảng trắng, dấu ngoặc đơn (), dấu ngoặc vuông []
- **Ký tự không cho phép:** `! @ # $ % ^ & * + = { } | \ : ; " ' < > , ? /`

### 4. Nội dung file
- **Số dòng tối đa:** 10,000 dòng dữ liệu
- File có quá nhiều dòng sẽ bị từ chối để đảm bảo hiệu năng

## Trường bắt buộc

Trong file Excel mẫu, các trường bắt buộc được đánh dấu bằng dấu `*` màu đỏ:

- **Tên \***: Bắt buộc phải nhập, không được bỏ trống
- **Slug**: Tùy chọn, sẽ tự động tạo nếu để trống
- **Cấp cha**: Tùy chọn, điền tên loại cha nếu có
- **Trạng thái**: Tùy chọn, mặc định là "Hiển thị"

## Validation rules

### Tên loại sản phẩm
1. **Bắt buộc**: Không được bỏ trống
2. **Độ dài**: Tối đa 250 ký tự
3. **Ký tự không hợp lệ**: Không chứa `< > " ' \`

### Slug
1. **Độ dài**: Tối đa 250 ký tự
2. **Duy nhất**: Không được trùng với slug đã có trong hệ thống

### Trạng thái
- **Giá trị hợp lệ**: "Hiển thị" hoặc "Ẩn"
- Nếu để trống, mặc định là "Hiển thị"

### Cấp cha
- Nếu điền, phải là tên của loại sản phẩm đã tồn tại trong hệ thống
- Nếu không tìm thấy, dòng đó sẽ bị lỗi

## Xử lý lỗi

Khi import file có lỗi:

1. **File bị từ chối hoàn toàn:**
   - File không đúng định dạng
   - File quá kích thước
   - Tên file không hợp lệ
   - File có quá nhiều dòng

2. **Import một phần (Partial):**
   - Một số dòng thành công, một số dòng lỗi
   - Dòng lỗi sẽ không được import vào hệ thống
   - Xem chi tiết lỗi trong **Lịch sử nhập file**

3. **Import thất bại hoàn toàn (Failed):**
   - Tất cả các dòng đều có lỗi
   - Không có dữ liệu nào được import
   - Xem chi tiết lỗi trong **Lịch sử nhập file**

## Lịch sử nhập file

Truy cập: **Admin → Lịch sử nhập file**

Tại đây bạn có thể:
- Xem tất cả các lần import
- Lọc theo module, trạng thái, người nhập
- Xem chi tiết từng dòng dữ liệu
- Xem lỗi cụ thể của từng dòng

## Cấu hình PHP (Nếu cần)

Nếu file `.htaccess` không hoạt động, cập nhật `php.ini`:

```ini
upload_max_filesize = 10M
post_max_size = 10M
max_execution_time = 300
max_input_time = 300
memory_limit = 128M
```

## Thông báo lỗi

Hệ thống sẽ hiển thị thông báo rõ ràng cho từng loại lỗi:

- ✅ **Màu xanh**: Thành công hoàn toàn
- ⚠️ **Màu vàng**: Thành công một phần (có lỗi)
- ❌ **Màu đỏ**: Thất bại hoàn toàn

Thông báo chỉ hiển thị lỗi đầu tiên, xem chi tiết đầy đủ trong **Lịch sử nhập file**.
