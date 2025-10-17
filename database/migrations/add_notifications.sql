-- Migration: Thêm hệ thống thông báo và cập nhật safety_stock
-- Date: 2025-01-17

USE mini_market;

-- Cập nhật safety_stock mặc định cho các bản ghi hiện tại
UPDATE stocks SET safety_stock = 10 WHERE safety_stock = 0;

-- Tạo bảng thông báo
CREATE TABLE IF NOT EXISTS notifications (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  user_id BIGINT NOT NULL,                    -- Người nhận thông báo
  type ENUM('warning','info','success','error') NOT NULL DEFAULT 'info',
  title VARCHAR(255) NOT NULL,                -- Tiêu đề thông báo
  message TEXT NOT NULL,                      -- Nội dung thông báo
  link VARCHAR(255) NULL,                     -- Link liên quan (nếu có)
  is_read BOOLEAN NOT NULL DEFAULT FALSE,     -- Đã đọc chưa
  read_at DATETIME NULL,                      -- Thời gian đọc
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_notif_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_notif_user_read (user_id, is_read),
  INDEX idx_notif_created (created_at)
) ENGINE=InnoDB;

-- Kiểm tra và tạo thông báo cho các sản phẩm đã có tồn kho thấp
INSERT INTO notifications (user_id, type, title, message, link)
SELECT 
    u.id,
    'warning',
    'Cảnh báo tồn kho thấp',
    CONCAT('Sản phẩm "', p.name, '" chỉ còn ', s.qty, ' (mức an toàn: ', s.safety_stock, ')'),
    '/admin/stocks'
FROM stocks s
JOIN products p ON p.id = s.product_id
CROSS JOIN users u
LEFT JOIN staff_profiles sp ON sp.user_id = u.id
WHERE s.qty <= s.safety_stock 
  AND s.qty >= 0
  AND (u.role_id IN (2, 3, 4) OR sp.staff_role IN ('Kho', 'Admin'))
  AND NOT EXISTS (
    SELECT 1 FROM notifications n 
    WHERE n.user_id = u.id 
      AND n.title LIKE CONCAT('%', p.name, '%')
      AND n.created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
  );
