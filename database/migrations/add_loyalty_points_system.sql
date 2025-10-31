-- =====================================================================
-- LOYALTY POINTS SYSTEM
-- Hệ thống tích điểm thành viên
-- =====================================================================

USE mini_market;

-- 1. Thêm cột loyalty_points vào bảng users
ALTER TABLE users 
ADD COLUMN loyalty_points INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Điểm tích lũy hiện tại' 
AFTER email;

-- 2. Tạo bảng lịch sử giao dịch điểm
CREATE TABLE IF NOT EXISTS loyalty_transactions (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL COMMENT 'Mã khách hàng',
    order_id BIGINT NULL COMMENT 'Mã đơn hàng (nếu có)',
    points INT NOT NULL COMMENT 'Số điểm (+/-)',
    transaction_type ENUM('earn', 'redeem', 'manual_adjust') NOT NULL COMMENT 'Loại giao dịch: earn=tích điểm, redeem=đổi điểm, manual_adjust=điều chỉnh thủ công',
    description VARCHAR(255) NULL COMMENT 'Mô tả giao dịch',
    balance_before INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Số dư điểm trước giao dịch',
    balance_after INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Số dư điểm sau giao dịch',
    created_by BIGINT NULL COMMENT 'Người thực hiện (nếu là thủ công)',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    CONSTRAINT fk_lt_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_lt_order FOREIGN KEY(order_id) REFERENCES orders(id) ON DELETE SET NULL,
    CONSTRAINT fk_lt_created_by FOREIGN KEY(created_by) REFERENCES users(id) ON DELETE SET NULL,
    
    INDEX idx_lt_user (user_id),
    INDEX idx_lt_order (order_id),
    INDEX idx_lt_created (created_at),
    INDEX idx_lt_type (transaction_type)
) ENGINE=InnoDB COMMENT='Lịch sử giao dịch điểm thành viên';

-- 3. Thêm cột loyalty_discount vào bảng orders (số tiền giảm bằng điểm)
ALTER TABLE orders 
ADD COLUMN loyalty_points_used INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Số điểm đã sử dụng' AFTER coupon_code,
ADD COLUMN loyalty_discount DECIMAL(15,2) NOT NULL DEFAULT 0 COMMENT 'Số tiền giảm bằng điểm (1000 điểm = 1000đ)' AFTER loyalty_points_used;

-- 4. Thêm cột loyalty_points_earned vào orders (để tracking)
ALTER TABLE orders 
ADD COLUMN loyalty_points_earned INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Số điểm tích được từ đơn này' AFTER loyalty_discount;

-- 5. Cập nhật lại total_amount để tính loyalty_discount
-- (Không cần ALTER, chỉ cần update logic trong code: final_amount = subtotal - coupon_discount - loyalty_discount + shipping_fee + tax_amount)

-- 6. Insert sample data để test (optional)
-- Cập nhật điểm cho khách hàng hiện tại (nếu có)
-- UPDATE users SET loyalty_points = 5000 WHERE role_id = 1 AND id = 1;

COMMIT;
