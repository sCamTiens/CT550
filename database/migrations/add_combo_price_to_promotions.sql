-- Migration: Thêm cột combo_price vào bảng promotions
-- Created: 2025-10-28

ALTER TABLE promotions 
ADD COLUMN combo_price DECIMAL(12,2) NULL COMMENT 'Giá combo (cho promo_type = combo)' 
AFTER discount_value;
