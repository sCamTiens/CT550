# 🔔 Hướng dẫn nhanh: Cảnh báo Tồn kho Tự động

## Tính năng

✅ **Reset thông báo mỗi ngày lúc 7h sáng**  
✅ **Tạo lại thông báo cho sản phẩm vẫn còn hết hàng/tồn kho thấp**  
✅ **Chỉ cảnh báo sản phẩm đang bán** (is_active = 1)

---

## Cài đặt Task Scheduler (5 phút)

### Bước 1: Mở Task Scheduler
- Nhấn `Win + R` → Gõ `taskschd.msc` → Enter

### Bước 2: Tạo Task
1. Click **"Create Basic Task..."**
2. **Name**: `Daily Stock Alert - 7AM`
3. **Trigger**: Daily tại `07:00:00`
4. **Action**: Start a program
   - **Program**: `C:\Users\Dell\OneDrive\Documents\Course\CT550\daily_stock_check.bat`
   - **Start in**: `C:\Users\Dell\OneDrive\Documents\Course\CT550`
5. Click **Finish**

### Bước 3: Settings
1. Chuột phải task → **Properties**
2. Tab **Settings**:
   - ✅ Run task as soon as possible after a scheduled start is missed
   - ✅ If the task fails, restart every `10 minutes`, up to `3` times
3. Click **OK**

✅ **Xong!** Script sẽ chạy tự động mỗi sáng 7h.

---

## Test ngay

### Cách 1: Double-click file BAT
```
daily_stock_check.bat
```

### Cách 2: Từ trang Admin
```
http://localhost/admin/stock-alerts
→ Click nút "Chạy Kiểm tra"
```

### Cách 3: Từ CMD
```cmd
cd C:\Users\Dell\OneDrive\Documents\Course\CT550
php daily_stock_check.php
```

---

## Kiểm tra Log

**File:** `logs/daily_stock_check.log`

```
[2025-10-18 07:00:01] DAILY STOCK ALERT CHECK STARTED
[2025-10-18 07:00:02] Reset old notifications: 24
[2025-10-18 07:00:03] Out of stock products: 6
[2025-10-18 07:00:03] Low stock products: 2
[2025-10-18 07:00:04] Total notifications created: 8
[2025-10-18 07:00:04] DAILY STOCK ALERT CHECK COMPLETED
```

---

## Cách hoạt động

### 🌅 Mỗi sáng 7h:
1. Reset tất cả thông báo tồn kho cũ → đánh dấu đã đọc
2. Quét tất cả sản phẩm đang bán (is_active = 1)
3. Tạo thông báo mới cho sản phẩm:
   - 🔴 Hết hàng (qty = 0)
   - 🟠 Tồn kho thấp (qty <= safety_stock)
4. Gửi đến Admin/Quản lý/Nhân viên kho

### 📦 Khi xuất kho:
- Chỉ cập nhật số lượng tồn kho
- **Không tạo thông báo ngay** (tránh spam)
- Thông báo sẽ được tạo vào 7h sáng hôm sau

---

## Troubleshooting

### ❌ Script không chạy tự động
→ Kiểm tra Task Scheduler History  
→ Đảm bảo đường dẫn chính xác

### ❌ Không nhận thông báo
→ Kiểm tra `is_active = 1` cho sản phẩm  
→ Kiểm tra `qty <= safety_stock`

### ❌ Log file không tạo
→ Tạo thủ công: `mkdir logs`

---

**Xem chi tiết:** `DAILY_STOCK_ALERT_README.md`
