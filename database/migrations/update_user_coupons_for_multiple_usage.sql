-- Migration: Cập nhật bảng user_coupons để theo dõi nhiều lần sử dụng
-- Thay đổi: Xóa UNIQUE constraint để cho phép 1 user sử dụng 1 coupon nhiều lần
-- Thêm trường order_id để liên kết với đơn hàng

-- 1. Xóa UNIQUE constraint cũ
-- ALTER TABLE user_coupons 
-- DROP INDEX uniq_user_coupon;

-- 2. Thêm trường order_id để tham chiếu đến đơn hàng
ALTER TABLE user_coupons
ADD COLUMN order_id BIGINT NULL AFTER coupon_id,
ADD CONSTRAINT fk_uc_order FOREIGN KEY(order_id) REFERENCES orders(id) ON DELETE SET NULL;

-- 3. Thêm index mới để tối ưu query
ALTER TABLE user_coupons
ADD INDEX idx_uc_user_coupon (user_id, coupon_id),
ADD INDEX idx_uc_order (order_id);

-- 4. Cập nhật comment cho bảng
ALTER TABLE user_coupons 
COMMENT = 'Theo dõi việc sử dụng mã giảm giá của từng người dùng. Mỗi lần sử dụng tạo 1 bản ghi mới.';
