-- Migration: Thêm theo dõi sử dụng mã giảm giá theo khách hàng
-- Date: 2025-10-29

USE mini_market;

-- 1. Thêm cột max_uses_per_customer vào bảng coupons
ALTER TABLE coupons 
ADD COLUMN max_uses_per_customer INT DEFAULT 0 COMMENT '0 = không giới hạn, >0 = số lần tối đa mỗi khách được dùng'
AFTER max_uses;

-- 2. Thêm cột max_discount (giảm tối đa cho loại phần trăm)
ALTER TABLE coupons 
ADD COLUMN max_discount DECIMAL(12,2) DEFAULT 0 COMMENT 'Số tiền giảm tối đa (cho loại phần trăm)'
AFTER discount_value;

-- 3. Tạo bảng coupon_usage để theo dõi lịch sử sử dụng chi tiết
CREATE TABLE IF NOT EXISTS coupon_usage (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  coupon_id BIGINT NOT NULL,
  user_id BIGINT NULL COMMENT 'NULL = khách vãng lai',
  order_id BIGINT NULL,
  discount_amount DECIMAL(12,2) NOT NULL COMMENT 'Số tiền giảm thực tế',
  used_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  
  CONSTRAINT fk_coupon_usage_coupon FOREIGN KEY(coupon_id) REFERENCES coupons(id) ON DELETE CASCADE,
  CONSTRAINT fk_coupon_usage_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_coupon_usage_order FOREIGN KEY(order_id) REFERENCES orders(id) ON DELETE SET NULL,
  
  INDEX idx_coupon_user (coupon_id, user_id),
  INDEX idx_used_at (used_at)
) ENGINE=InnoDB COMMENT='Lịch sử sử dụng mã giảm giá';

-- 4. Thêm comment cho bảng user_coupons
ALTER TABLE user_coupons 
COMMENT = 'Liên kết user-coupon (cho hệ thống phát mã)';

-- 5. Tạo view để xem số lần sử dụng theo khách hàng
CREATE OR REPLACE VIEW v_coupon_usage_by_customer AS
SELECT 
    cu.coupon_id,
    c.code AS coupon_code,
    c.name AS coupon_name,
    cu.user_id,
    u.full_name AS customer_name,
    COUNT(*) AS usage_count,
    SUM(cu.discount_amount) AS total_discount,
    MAX(cu.used_at) AS last_used_at
FROM coupon_usage cu
INNER JOIN coupons c ON c.id = cu.coupon_id
LEFT JOIN users u ON u.id = cu.user_id
GROUP BY cu.coupon_id, cu.user_id;

-- 6. Tạo stored procedure để kiểm tra khách đã dùng chưa
DELIMITER $$

CREATE PROCEDURE check_coupon_usage_limit(
    IN p_coupon_code VARCHAR(64),
    IN p_user_id BIGINT,
    OUT p_can_use BOOLEAN,
    OUT p_message VARCHAR(255)
)
BEGIN
    DECLARE v_coupon_id BIGINT;
    DECLARE v_max_uses_per_customer INT;
    DECLARE v_current_usage_count INT;
    
    -- Lấy thông tin coupon
    SELECT id, max_uses_per_customer 
    INTO v_coupon_id, v_max_uses_per_customer
    FROM coupons 
    WHERE UPPER(code) = UPPER(p_coupon_code)
    LIMIT 1;
    
    IF v_coupon_id IS NULL THEN
        SET p_can_use = FALSE;
        SET p_message = 'Mã giảm giá không tồn tại';
        LEAVE;
    END IF;
    
    -- Nếu không giới hạn (= 0) thì cho phép
    IF v_max_uses_per_customer = 0 THEN
        SET p_can_use = TRUE;
        SET p_message = 'OK';
        LEAVE;
    END IF;
    
    -- Đếm số lần khách này đã dùng mã
    SELECT COUNT(*) 
    INTO v_current_usage_count
    FROM coupon_usage
    WHERE coupon_id = v_coupon_id 
      AND user_id = p_user_id;
    
    -- So sánh với giới hạn
    IF v_current_usage_count >= v_max_uses_per_customer THEN
        SET p_can_use = FALSE;
        SET p_message = CONCAT('Bạn đã sử dụng mã này ', v_current_usage_count, '/', v_max_uses_per_customer, ' lần. Không thể dùng thêm.');
    ELSE
        SET p_can_use = TRUE;
        SET p_message = CONCAT('Còn ', v_max_uses_per_customer - v_current_usage_count, ' lần sử dụng');
    END IF;
END$$

DELIMITER ;

-- 7. Tạo trigger để tự động ghi log khi dùng mã
-- (Optional - có thể dùng hoặc gọi trực tiếp từ PHP)
DELIMITER $$

CREATE TRIGGER after_order_coupon_insert
AFTER INSERT ON orders
FOR EACH ROW
BEGIN
    DECLARE v_coupon_id BIGINT;
    
    IF NEW.coupon_code IS NOT NULL AND NEW.coupon_code != '' THEN
        -- Lấy coupon_id từ code
        SELECT id INTO v_coupon_id 
        FROM coupons 
        WHERE UPPER(code) = UPPER(NEW.coupon_code)
        LIMIT 1;
        
        IF v_coupon_id IS NOT NULL THEN
            -- Ghi vào coupon_usage
            INSERT INTO coupon_usage (coupon_id, user_id, order_id, discount_amount, used_at)
            VALUES (v_coupon_id, NEW.user_id, NEW.id, NEW.discount_total, NEW.created_at);
        END IF;
    END IF;
END$$

DELIMITER ;

-- 8. Cập nhật dữ liệu cũ (nếu có) - migrate từ orders sang coupon_usage
INSERT INTO coupon_usage (coupon_id, user_id, order_id, discount_amount, used_at, created_at)
SELECT 
    c.id AS coupon_id,
    o.user_id,
    o.id AS order_id,
    o.discount_total,
    o.created_at AS used_at,
    o.created_at
FROM orders o
INNER JOIN coupons c ON UPPER(c.code) = UPPER(o.coupon_code)
WHERE o.coupon_code IS NOT NULL 
  AND o.coupon_code != ''
  AND NOT EXISTS (
      SELECT 1 FROM coupon_usage cu 
      WHERE cu.order_id = o.id
  );

COMMIT;
