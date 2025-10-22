# 🔔 CÀI ĐẶT THÔNG BÁO TỒN KHO TỰ ĐỘNG

## 📋 Tổng quan
Hệ thống sẽ **TỰ ĐỘNG** kiểm tra tồn kho mỗi ngày lúc **7:00 sáng** và:
- ✅ **XÓA** tất cả thông báo tồn kho cũ
- ✅ **TẠO MỚI** thông báo cho các sản phẩm **ĐANG CÒN** tồn kho thấp
- ✅ **TỰ ĐỘNG DỪNG** cảnh báo khi sản phẩm đã được nhập đủ hàng

---

## 🚀 HƯỚNG DẪN CÀI ĐẶT (Windows)

### Bước 1: Mở Task Scheduler
1. Nhấn **Windows + R**
2. Gõ: `taskschd.msc`
3. Nhấn **Enter**

### Bước 2: Tạo Task mới
1. Bên phải, click **"Create Basic Task..."**
2. **Name**: `Daily Stock Alert - 7AM`
3. **Description**: `Kiểm tra tồn kho và tạo thông báo tự động lúc 7h sáng`
4. Click **Next**

### Bước 3: Chọn Trigger (Kích hoạt)
1. Chọn: **Daily** (Hàng ngày)
2. Click **Next**
3. **Start**: Chọn ngày bắt đầu (hôm nay)
4. **Start time**: `07:00:00` (7 giờ sáng)
5. **Recur every**: `1 days` (Mỗi ngày)
6. Click **Next**

### Bước 4: Chọn Action (Hành động)
1. Chọn: **Start a program** (Chạy chương trình)
2. Click **Next**
3. **Program/script**: Browse đến file
   ```
   C:\Users\Dell\OneDrive\Documents\Course\CT550\daily_stock_check.bat
   ```
4. Click **Next**

### Bước 5: Hoàn tất
1. Đánh dấu: ☑ **"Open the Properties dialog for this task when I click Finish"**
2. Click **Finish**

### Bước 6: Cấu hình nâng cao (Tùy chọn)
Trong Properties dialog:
1. Tab **General**:
   - ☑ **Run whether user is logged on or not** (Chạy kể cả khi không đăng nhập)
   - ☑ **Run with highest privileges** (Chạy với quyền admin)

2. Tab **Conditions**:
   - ☐ Bỏ tick **"Start the task only if the computer is on AC power"** (Cho phép chạy khi dùng pin)

3. Tab **Settings**:
   - ☑ **Allow task to be run on demand** (Cho phép chạy thủ công)
   - ☑ **If the task fails, restart every**: `10 minutes` (Thử lại nếu lỗi)

4. Click **OK**

---

## 🧪 KIỂM TRA THỬ

### Cách 1: Chạy thủ công từ file bat
1. Mở **File Explorer**
2. Đến folder: `C:\Users\Dell\OneDrive\Documents\Course\CT550\`
3. **Double-click** file `daily_stock_check.bat`
4. Xem kết quả trong cửa sổ CMD

### Cách 2: Chạy từ Task Scheduler
1. Mở **Task Scheduler**
2. Tìm task: **"Daily Stock Alert - 7AM"**
3. Click chuột phải → **Run**
4. Kiểm tra:
   - Tab **History** để xem lịch sử chạy
   - File log: `logs\daily_stock_check.log`

### Cách 3: Kiểm tra từ website
1. Đăng nhập vào admin
2. Vào: **Quản lý tồn kho** → **Cảnh báo tự động**
3. Click nút **"🔄 Chạy kiểm tra ngay"**
4. Xem thông báo xuất hiện ở icon **🔔** góc phải trên

---

## 📊 CÁCH HOẠT ĐỘNG

### Mỗi ngày lúc 7h sáng:

```
1. XÓA tất cả thông báo tồn kho cũ
   └─ Bao gồm: đã đọc + chưa đọc
   └─ Làm sạch bell icon

2. KIỂM TRA tồn kho hiện tại
   └─ Chỉ sản phẩm đang bán (is_active = 1)
   └─ So sánh: qty <= safety_stock

3. TẠO THÔNG BÁO MỚI
   └─ CHỈ cho sản phẩm CÒN tồn kho thấp
   └─ Gửi đến: Admin + Quản lý + Nhân viên kho
   
4. DỪNG CẢNH BÁO
   └─ Nếu sản phẩm đã nhập đủ hàng
   └─ Không tạo thông báo cho sản phẩm đó nữa
```

### Ví dụ:

**Ngày 1 (7h sáng):**
- Sản phẩm A: Tồn 5, An toàn 20 → ⚠️ Tạo thông báo
- Sản phẩm B: Tồn 0, An toàn 10 → 🔴 Tạo thông báo

**Ngày 1 (10h sáng):**
- Nhập hàng cho Sản phẩm A: Tồn → 50

**Ngày 2 (7h sáng):**
- XÓA tất cả thông báo cũ
- Sản phẩm A: Tồn 50 → ✅ KHÔNG tạo thông báo (đã đủ)
- Sản phẩm B: Tồn 0 → 🔴 Tạo thông báo mới

---

## 📁 FILE LIÊN QUAN

| File | Mô tả |
|------|-------|
| `daily_stock_check.bat` | Script batch để chạy tự động |
| `daily_stock_check.php` | Script PHP thực hiện kiểm tra |
| `logs/daily_stock_check.log` | File log ghi lại kết quả |
| `src/Services/DailyStockAlertService.php` | Service xử lý logic |

---

## 🔧 XỬ LÝ LỖI

### Lỗi: Task không chạy
- ✅ Kiểm tra quyền admin
- ✅ Kiểm tra đường dẫn file bat
- ✅ Xem History trong Task Scheduler

### Lỗi: Không có thông báo
- ✅ Kiểm tra có sản phẩm tồn kho thấp không
- ✅ Xem file log: `logs/daily_stock_check.log`
- ✅ Kiểm tra PHP có chạy được không

### Lỗi: Thông báo cũ không xóa
- ✅ Chạy thủ công từ website
- ✅ Kiểm tra database: bảng `notifications`
- ✅ Xem log để debug

---

## 💡 GHI CHÚ

- ⏰ Thời gian mặc định: **7:00 sáng** (có thể thay đổi trong Task Scheduler)
- 🗑️ Thông báo cũ hơn **30 ngày** sẽ tự động bị xóa
- 📧 Chỉ gửi cho: Admin, Quản lý, Nhân viên kho
- 🔄 Script tự động retry nếu lỗi (mỗi 10 phút)

---

## ✅ CHECKLIST HOÀN TẤT

- [ ] Đã tạo Task trong Task Scheduler
- [ ] Đã test chạy thủ công thành công
- [ ] Thông báo xuất hiện ở bell icon
- [ ] File log ghi đúng kết quả
- [ ] Task chạy đúng giờ vào sáng hôm sau

---

**🎉 HOÀN TẤT! Hệ thống thông báo tự động đã sẵn sàng!**
