# Hệ thống Thông báo Tồn kho

## Tính năng

### 1. Cảnh báo tồn kho thấp
- Tự động tạo thông báo khi tồn kho (`qty`) <= mức an toàn (`safety_stock`)
- Mức an toàn mặc định: **10 đơn vị**
- Thông báo gửi đến: Admin, Quản lý, nhân viên kho

### 2. Icon chuông thông báo
- Vị trí: Header bên trái ảnh đại diện
- Badge đỏ hiển thị số thông báo chưa đọc
- Click vào chuông để xem danh sách thông báo

### 3. Quản lý thông báo
- **Đọc tất cả**: Đánh dấu tất cả thông báo đã đọc → Badge đỏ biến mất
- **Đọc từng cái**: Click vào thông báo → Chuyển nền sang xám nhẹ
- **Xóa thông báo**: Click icon X để xóa từng thông báo
- **Tự động cập nhật**: Kiểm tra thông báo mới mỗi 30 giây

### 4. Hiển thị thông báo
- ✅ **Chưa đọc**: Nền trắng, in đậm
- ✅ **Đã đọc**: Nền xám nhẹ, bình thường
- Icon màu sắc theo loại:
  - 🟡 Warning (Cảnh báo): Màu vàng
  - 🔵 Info (Thông tin): Màu xanh dương
  - 🟢 Success (Thành công): Màu xanh lá
  - 🔴 Error (Lỗi): Màu đỏ

## Cài đặt

### Bước 1: Chạy migration
```bash
mysql -u root -p mini_market < database/migrations/add_notifications.sql
```

### Bước 2: Kiểm tra bảng
```sql
-- Kiểm tra bảng notifications đã tạo
SHOW TABLES LIKE 'notifications';

-- Kiểm tra safety_stock đã cập nhật
SELECT product_id, qty, safety_stock FROM stocks LIMIT 10;
```

## API Endpoints

### GET /admin/api/notifications
Lấy danh sách thông báo của user hiện tại

**Response:**
```json
[
  {
    "id": 1,
    "user_id": 1,
    "type": "warning",
    "title": "Cảnh báo tồn kho thấp",
    "message": "Sản phẩm 'Nước giải khát Coca Cola' chỉ còn 5 (mức an toàn: 10)",
    "link": "/admin/stocks",
    "is_read": 0,
    "read_at": null,
    "created_at": "2025-01-17 10:30:00"
  }
]
```

### GET /admin/api/notifications/unread-count
Đếm số thông báo chưa đọc

**Response:**
```json
{
  "count": 3
}
```

### POST /admin/api/notifications/{id}/read
Đánh dấu thông báo đã đọc

**Response:**
```json
{
  "success": true
}
```

### POST /admin/api/notifications/read-all
Đánh dấu tất cả thông báo đã đọc

**Response:**
```json
{
  "success": true
}
```

### DELETE /admin/api/notifications/{id}
Xóa thông báo

**Response:**
```json
{
  "success": true
}
```

## Cách hoạt động

### 1. Tự động tạo thông báo
Khi xuất kho (qua `StockRepository::allocateBatches()`):
1. Cập nhật tồn kho
2. Kiểm tra `qty <= safety_stock`
3. Nếu đúng → Tạo thông báo cho Admin/Kho
4. Tránh spam: Chỉ tạo 1 thông báo cho 1 sản phẩm trong 24h

### 2. Hiển thị thông báo
- Header load số thông báo chưa đọc khi trang tải
- Poll API mỗi 30s để cập nhật realtime
- Click chuông → Load danh sách đầy đủ

### 3. Đánh dấu đã đọc
- Click vào thông báo → Gọi API `markAsRead`
- Cập nhật `is_read = 1`, `read_at = NOW()`
- Giảm badge số lượng
- Nếu có link → Chuyển đến trang tương ứng

## Tùy chỉnh

### Thay đổi mức an toàn mặc định
Trong `db.sql`:
```sql
safety_stock INT NOT NULL DEFAULT 10,  -- Đổi 10 thành giá trị khác
```

### Thay đổi thời gian poll
Trong `header.php`:
```javascript
setInterval(() => {
    this.fetchUnreadCount();
    if (this.isOpen) {
        this.fetchNotifications();
    }
}, 30000); // 30000ms = 30s, đổi thành giá trị khác
```

### Thêm loại thông báo mới
Trong `NotificationRepository::create()`:
```php
self::create([
    'user_id' => $userId,
    'type' => 'info',  // warning, info, success, error
    'title' => 'Tiêu đề',
    'message' => 'Nội dung',
    'link' => '/admin/path'  // Link tùy chọn
]);
```

## Lưu ý
- Thông báo chỉ hiển thị cho user đã đăng nhập
- Mỗi user có thông báo riêng
- Thông báo tự động xóa khi xóa user (ON DELETE CASCADE)
- Badge đỏ chỉ biến mất khi TẤT CẢ thông báo đã đọc
