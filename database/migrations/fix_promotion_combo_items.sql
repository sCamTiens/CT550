-- Migration: Sửa lại cấu trúc bảng promotion_combo_items
-- Xóa bảng promotion_combo_rules và update promotion_combo_items

-- Bước 1: Xóa constraint và bảng promotion_combo_rules
ALTER TABLE promotion_combo_items DROP FOREIGN KEY fk_pci_combo;
DROP TABLE IF EXISTS promotion_combo_rules;

-- Bước 2: Xóa và tạo lại bảng promotion_combo_items với cấu trúc mới
DROP TABLE IF EXISTS promotion_combo_items;

CREATE TABLE promotion_combo_items (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  promotion_id BIGINT NOT NULL,                 -- FK trực tiếp tới promotions
  product_id BIGINT NOT NULL,
  required_qty INT NOT NULL DEFAULT 1,          -- Số lượng của sản phẩm này trong combo
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  created_by BIGINT NULL,
  updated_by BIGINT NULL,
  CONSTRAINT fk_pci_created_by FOREIGN KEY(created_by) REFERENCES users(id),
  CONSTRAINT fk_pci_updated_by FOREIGN KEY(updated_by) REFERENCES users(id),
  CONSTRAINT fk_pci_promo FOREIGN KEY(promotion_id) REFERENCES promotions(id) ON DELETE CASCADE,
  CONSTRAINT fk_pci_prod FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE CASCADE,
  UNIQUE KEY uniq_pci (promotion_id, product_id),
  CHECK (required_qty > 0)
) ENGINE=InnoDB;
