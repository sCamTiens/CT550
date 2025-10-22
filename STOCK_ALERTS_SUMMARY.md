# 📦 HỆ THỐNG THÔNG BÁO TỒN KHO TỰ ĐỘNG

## ✨ Tính năng đã hoàn thiện

### 🔔 Thông báo bell icon
- ✅ Hiển thị badge đỏ số lượng thông báo chưa đọc
- ✅ Dropdown danh sách thông báo với icon theo loại (⚠️ warning, 🔴 error)
- ✅ Phân biệt đã đọc/chưa đọc
- ✅ Đánh dấu đã đọc khi click
- ✅ Link đến trang tồn kho
- ✅ Xóa từng thông báo
- ✅ Auto refresh mỗi 30s

### 🤖 Tự động hóa
- ✅ Chạy mỗi ngày lúc **7:00 sáng** (qua Task Scheduler)
- ✅ **XÓA** tất cả thông báo tồn kho cũ
- ✅ **TẠO MỚI** thông báo cho sản phẩm ĐANG tồn kho thấp
- ✅ **TỰ DỪNG** cảnh báo khi sản phẩm đã nhập đủ hàng
- ✅ Tự động cleanup thông báo cũ >30 ngày

### 📊 Logic thông minh
```
Mỗi ngày 7h sáng:
1. Xóa tất cả thông báo tồn kho cũ (reset)
2. Quét database tìm sản phẩm: qty <= safety_stock
3. CHỈ tạo thông báo cho sản phẩm VẪN còn thấp
4. Nếu đã nhập đủ → KHÔNG tạo thông báo
```

### 📝 Loại thông báo
- 🔴 **Hết hàng** (qty = 0): Type `error`
- ⚠️ **Tồn kho thấp** (qty > 0 but <= safety_stock): Type `warning`
- Gửi đến: Admin + Quản lý + Nhân viên kho

## 📁 CẤU TRÚC FILE

```
CT550/
├── daily_stock_check.bat           # Script chạy tự động (7h sáng)
├── daily_stock_check.php           # PHP entry point
├── test_stock_alerts.bat           # Script test hệ thống
├── QUICK_SETUP_ALERTS.md           # Hướng dẫn nhanh (2 phút)
├── SETUP_AUTO_STOCK_ALERTS.md      # Hướng dẫn chi tiết đầy đủ
├── logs/
│   └── daily_stock_check.log       # Log file ghi kết quả
├── src/
│   ├── Services/
│   │   └── DailyStockAlertService.php  # Logic chính
│   ├── Controllers/Admin/
│   │   └── StockAlertController.php    # API endpoints
│   └── views/admin/
│       ├── partials/
│       │   └── header.php              # Bell icon với dropdown
│       └── stock-alerts/
│           └── index.php               # Trang quản lý
```

## 🚀 HƯỚNG DẪN SỬ DỤNG

### Lần đầu setup (chỉ 1 lần):
1. **Chạy test**: Double-click `test_stock_alerts.bat`
2. **Kiểm tra**: Xem thông báo ở bell icon 🔔
3. **Cài Task Scheduler**: Xem `QUICK_SETUP_ALERTS.md` (2 phút)
4. **Xong!** Hệ thống tự chạy mỗi ngày

### Sử dụng hàng ngày:
- 🔔 **Xem thông báo**: Click bell icon góc phải
- ✅ **Đánh dấu đã đọc**: Click vào thông báo
- 🗑️ **Xóa thông báo**: Click nút X
- 📊 **Xem chi tiết**: Click "Xem tồn kho →"

### Quản lý:
- Vào: **Admin** → **Quản lý tồn kho** → **Cảnh báo tự động**
- Chạy thủ công: Nút "🔄 Chạy kiểm tra ngay"
- Xem thống kê: Số sản phẩm hết hàng, tồn kho thấp
- Xem log: `logs/daily_stock_check.log`

## 🔧 API ENDPOINTS

```
GET  /admin/api/notifications              # Lấy danh sách thông báo
GET  /admin/api/notifications/unread-count # Đếm thông báo chưa đọc
POST /admin/api/notifications/{id}/read    # Đánh dấu đã đọc
POST /admin/api/notifications/read-all     # Đánh dấu tất cả đã đọc
DELETE /admin/api/notifications/{id}       # Xóa thông báo

POST /admin/api/stock-alerts/run-check     # Chạy kiểm tra ngay
GET  /admin/api/stock-alerts/stats         # Lấy thống kê
POST /admin/api/stock-alerts/cleanup       # Dọn dẹp thông báo cũ
```

## 📊 DATABASE

### Bảng: `notifications`
```sql
- id: int (PK)
- user_id: int (FK → users)
- type: enum('info', 'success', 'warning', 'error')
- title: varchar(255)
- message: text
- link: varchar(255)
- is_read: tinyint(1)
- read_at: datetime
- created_at: timestamp
- updated_at: timestamp
```

## 🎯 VÍ DỤ FLOW

**Scenario: Sản phẩm "Bánh mì" tồn kho thấp**

### Ngày 1 - 7:00 AM
```
Trạng thái: Bánh mì (tồn: 3, an toàn: 20)
Hành động:
  1. Xóa thông báo cũ về Bánh mì
  2. Tạo thông báo mới: "⚠️ Cảnh báo tồn kho thấp"
  3. Gửi đến: Admin, Quản lý kho
Kết quả: Bell icon hiện badge đỏ "1"
```

### Ngày 1 - 10:00 AM
```
Hành động: Nhân viên nhập hàng → Tồn: 50
Kết quả: Thông báo vẫn hiển thị (chưa đến 7h sáng hôm sau)
```

### Ngày 2 - 7:00 AM
```
Trạng thái: Bánh mì (tồn: 50, an toàn: 20) ✅ ĐÃ ĐỦ
Hành động:
  1. Xóa thông báo cũ về Bánh mì
  2. KHÔNG tạo thông báo mới (vì đã đủ hàng)
Kết quả: Bell icon không còn badge đỏ
```

### Ngày 3 - 7:00 AM
```
Trạng thái: Bánh mì (tồn: 0, an toàn: 20) ❌ HẾT HÀNG
Hành động:
  1. Tạo thông báo mới: "🔴 Sản phẩm hết hàng"
  2. Gửi đến: Admin, Quản lý kho
Kết quả: Bell icon hiện badge đỏ "1" với icon đỏ
```

## 🐛 TROUBLESHOOTING

### Không có thông báo?
✅ Kiểm tra có sản phẩm tồn kho thấp: `SELECT * FROM stocks WHERE qty <= safety_stock`
✅ Xem log: `logs/daily_stock_check.log`
✅ Chạy test: `test_stock_alerts.bat`

### Task Scheduler không chạy?
✅ Mở Task Scheduler → Xem History
✅ Check quyền admin
✅ Verify đường dẫn file bat

### Thông báo cũ không xóa?
✅ Chạy cleanup: Vào trang Stock Alerts → Click "Dọn dẹp"
✅ Hoặc gọi API: `POST /admin/api/stock-alerts/cleanup`

## 📈 PERFORMANCE

- ⚡ **Cleanup tự động**: Xóa thông báo >30 ngày
- 🔄 **Poll mỗi 30s**: Update số lượng chưa đọc
- 📊 **Index database**: Đã optimize queries
- 🚀 **Async loading**: Không block UI

## ✅ CHECKLIST HOÀN THIỆN

- [x] Bell icon với badge đỏ
- [x] Dropdown thông báo
- [x] Phân loại theo type (warning/error)
- [x] Đánh dấu đã đọc
- [x] Xóa thông báo
- [x] Auto reset mỗi ngày 7h
- [x] Chỉ cảnh báo sản phẩm còn thấp
- [x] Tự dừng khi đã đủ hàng
- [x] Cleanup thông báo cũ
- [x] File bat chạy tự động
- [x] Hướng dẫn setup
- [x] Script test
- [x] Log file
- [x] API đầy đủ

## 🎉 KẾT LUẬN

Hệ thống thông báo tồn kho đã HOÀN THIỆN:
- ✅ **Tự động 100%**: Không cần can thiệp thủ công
- ✅ **Thông minh**: Chỉ cảnh báo khi cần, tự dừng khi đủ
- ✅ **Real-time**: Hiển thị ngay trên bell icon
- ✅ **Dễ setup**: Chỉ mất 2 phút cài Task Scheduler
- ✅ **Maintainable**: Code sạch, có log, có test

**🚀 Sẵn sàng triển khai production!**
