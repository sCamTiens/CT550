-- Migration: Thêm cột max_uses_per_customer vào bảng coupons
-- Mục đích: Giới hạn số lần mỗi khách hàng có thể sử dụng mã giảm giá

-- Kiểm tra và thêm cột nếu chưa có
ALTER TABLE coupons 
ADD COLUMN IF NOT EXISTS max_uses_per_customer INT DEFAULT 0 COMMENT 'Số lần tối đa mỗi khách có thể dùng mã này (0 = không giới hạn)' 
AFTER max_uses;

-- Cập nhật comment cho bảng
ALTER TABLE coupons 
COMMENT = 'Quản lý mã giảm giá với giới hạn sử dụng tổng thể và theo từng khách hàng';
