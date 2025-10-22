# ⚡ NHANH: Cài đặt thông báo tồn kho tự động

## 🎯 Mục đích
Tự động cảnh báo tồn kho thấp mỗi ngày lúc 7h sáng qua icon 🔔

## 🚀 Cài đặt (2 phút)

### Windows:
1. Nhấn `Win + R`, gõ `taskschd.msc`
2. Create Basic Task → Name: `Daily Stock Alert - 7AM`
3. Trigger: Daily at `07:00:00`
4. Action: Start program
   ```
   C:\Users\Dell\OneDrive\Documents\Course\CT550\daily_stock_check.bat
   ```
5. Finish!

### Kiểm tra:
- Double-click file `daily_stock_check.bat` để chạy thử
- Hoặc vào website: Admin → Cảnh báo tự động → Chạy ngay
- Xem thông báo ở icon 🔔 góc phải trên

## 📖 Chi tiết
Xem file: `SETUP_AUTO_STOCK_ALERTS.md`

## 🎉 Xong!
