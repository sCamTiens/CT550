-- =====================================================================
-- MINI MARKET DATABASE - COMPLETE MIGRATION
-- Gộp tất cả migration thành 1 file hoàn chỉnh
-- =====================================================================
-- Xóa database nếu đã tồn tại
DROP DATABASE IF EXISTS mini_market;

CREATE DATABASE IF NOT EXISTS mini_market DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE mini_market;

-- =====================================================================
-- 1) USERS / AUTH / RBAC
-- =====================================================================
CREATE TABLE roles (
  id TINYINT PRIMARY KEY,
  name VARCHAR(64) NOT NULL
) ENGINE=InnoDB;

INSERT IGNORE INTO roles (id, name) VALUES
 (1,'Khách hàng'),
 (2,'Quản trị viên');

CREATE TABLE users (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  username VARCHAR(50) NOT NULL UNIQUE,
  role_id TINYINT NOT NULL DEFAULT 1,
  email VARCHAR(250) NOT NULL UNIQUE,
  loyalty_points INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Điểm tích lũy hiện tại',
  phone VARCHAR(32),
  password_hash VARCHAR(255),
  force_change_password BOOLEAN NOT NULL DEFAULT TRUE,
  full_name VARCHAR(250),
  avatar_url VARCHAR(255),
  gender ENUM('Nam','Nữ') NULL,
  date_of_birth DATE NULL,
  is_active BOOLEAN NOT NULL DEFAULT TRUE,
  is_deleted BOOLEAN NOT NULL DEFAULT FALSE,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  created_by BIGINT NULL,
  updated_by BIGINT NULL,
  CONSTRAINT fk_users_role FOREIGN KEY(role_id) REFERENCES roles(id),
  CONSTRAINT fk_users_created_by FOREIGN KEY(created_by) REFERENCES users(id),
  CONSTRAINT fk_users_updated_by FOREIGN KEY(updated_by) REFERENCES users(id)
) ENGINE=InnoDB;

-- Tạo admin mặc định
INSERT INTO users (role_id, username, email, phone, password_hash, full_name, is_active, force_change_password)
VALUES (
    2,
    'admin',
    'thicamtien2003@gmail.com',
    '0909000000',
    '$2b$10$b0.RGLmD391S.468j6b5FuoaBSv7OZKVT9/hqDR75Qlf2OzR/egxC',
    'Administrator',
    TRUE,
    TRUE
);

-- Danh mục TỈNH
CREATE TABLE provinces (
  code VARCHAR(10) PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  is_active BOOLEAN NOT NULL DEFAULT TRUE,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  created_by BIGINT NULL,
  updated_by BIGINT NULL,
  CONSTRAINT fk_provinces_created_by FOREIGN KEY(created_by) REFERENCES users(id),
  CONSTRAINT fk_provinces_updated_by FOREIGN KEY(updated_by) REFERENCES users(id)
) ENGINE=InnoDB;

-- Danh mục XÃ
CREATE TABLE communes (
  code VARCHAR(15) PRIMARY KEY,
  province_code VARCHAR(10) NOT NULL,
  name VARCHAR(120) NOT NULL,
  is_active BOOLEAN NOT NULL DEFAULT TRUE,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  created_by BIGINT NULL,
  updated_by BIGINT NULL,
  CONSTRAINT fk_communes_created_by FOREIGN KEY(created_by) REFERENCES users(id),
  CONSTRAINT fk_communes_updated_by FOREIGN KEY(updated_by) REFERENCES users(id),
  CONSTRAINT fk_commune_prov FOREIGN KEY (province_code) REFERENCES provinces(code)
) ENGINE=InnoDB;

-- Địa chỉ người nhận
CREATE TABLE user_addresses (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  user_id BIGINT NOT NULL,
  receiver_name VARCHAR(250),
  receiver_phone VARCHAR(32),
  line1 VARCHAR(255),
  commune_code VARCHAR(15) NULL,
  province_code VARCHAR(10) NULL,
  is_default BOOLEAN NOT NULL DEFAULT FALSE,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  created_by BIGINT NULL,
  updated_by BIGINT NULL,
  CONSTRAINT fk_addr_created_by FOREIGN KEY(created_by) REFERENCES users(id),
  CONSTRAINT fk_addr_updated_by FOREIGN KEY(updated_by) REFERENCES users(id),
  CONSTRAINT fk_addr_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_addr_commune FOREIGN KEY(commune_code) REFERENCES communes(code),
  CONSTRAINT fk_addr_province FOREIGN KEY(province_code) REFERENCES provinces(code),
  INDEX idx_addr_commune (commune_code),
  INDEX idx_addr_province (province_code)
) ENGINE=InnoDB;

-- Phân loại nhân viên
CREATE TABLE staff_profiles (
  user_id BIGINT PRIMARY KEY,
  staff_role ENUM('Kho','Nhân viên bán hàng','Hỗ trợ trực tuyến','Admin') NOT NULL,
  hired_at DATE,
  note VARCHAR(255),
  base_salary DECIMAL(15,2) DEFAULT 0 COMMENT 'Lương cơ bản tháng',
  wage_per_shift DECIMAL(15,2) DEFAULT 0 COMMENT 'Lương mỗi ca (nếu tính theo ca)',
  salary_type ENUM('Theo tháng', 'Theo ca') DEFAULT 'Theo ca' COMMENT 'Loại lương',
  required_shifts_per_month TINYINT UNSIGNED DEFAULT 28 COMMENT 'Số ca yêu cầu để được full lương',
  CONSTRAINT fk_staff_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Tạo staff profile cho admin
INSERT INTO staff_profiles (user_id, staff_role, hired_at, note)
SELECT id, 'Admin', CURDATE(), 'Tài khoản admin mặc định'
FROM users WHERE username = 'admin';

-- Lịch sử thay đổi lương
CREATE TABLE salary_history (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT NOT NULL COMMENT 'ID nhân viên',
  salary DECIMAL(15,2) NOT NULL DEFAULT 0 COMMENT 'Mức lương cơ bản',
  from_date DATE NOT NULL COMMENT 'Áp dụng từ ngày',
  to_date DATE DEFAULT NULL COMMENT 'Đến ngày (NULL = hiện tại)',
  note TEXT DEFAULT NULL COMMENT 'Ghi chú thay đổi',
  created_by BIGINT DEFAULT NULL COMMENT 'Người tạo',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_user_id (user_id),
  KEY idx_dates (from_date, to_date),
  CONSTRAINT fk_salary_history_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_salary_history_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =====================================================================
-- 2) CATALOG: BRAND / CATEGORY / PRODUCT
-- =====================================================================

-- Thương hiệu
CREATE TABLE brands (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(250) NOT NULL UNIQUE,
  slug VARCHAR(250) UNIQUE,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  created_by BIGINT NULL,
  updated_by BIGINT NULL,
  CONSTRAINT fk_brands_created_by FOREIGN KEY(created_by) REFERENCES users(id),
  CONSTRAINT fk_brands_updated_by FOREIGN KEY(updated_by) REFERENCES users(id)
) ENGINE=InnoDB;

-- Loại sản phẩm
CREATE TABLE categories (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  parent_id BIGINT NULL,
  name VARCHAR(250) NOT NULL,
  slug VARCHAR(250) UNIQUE,
  is_active BOOLEAN NOT NULL DEFAULT TRUE,
  sort_order INT DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  created_by BIGINT NULL,
  updated_by BIGINT NULL,
  CONSTRAINT fk_cat_parent FOREIGN KEY(parent_id) REFERENCES categories(id) ON DELETE SET NULL,
  CONSTRAINT fk_cat_created_by FOREIGN KEY(created_by) REFERENCES users(id),
  CONSTRAINT fk_cat_updated_by FOREIGN KEY(updated_by) REFERENCES users(id)
) ENGINE=InnoDB;

-- Đơn vị tính
CREATE TABLE units (
  id TINYINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(32) NOT NULL UNIQUE,
  slug VARCHAR(32) NOT NULL UNIQUE,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  created_by BIGINT NULL,
  updated_by BIGINT NULL,
  CONSTRAINT fk_units_created_by FOREIGN KEY(created_by) REFERENCES users(id),
  CONSTRAINT fk_units_updated_by FOREIGN KEY(updated_by) REFERENCES users(id)
) ENGINE=InnoDB;

-- Sản phẩm
CREATE TABLE products (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  sku VARCHAR(64) NOT NULL UNIQUE,
  name VARCHAR(255) NOT NULL,
  slug VARCHAR(255) UNIQUE,
  brand_id BIGINT NULL,
  category_id BIGINT NULL,
  pack_size VARCHAR(64),
  unit_id TINYINT UNSIGNED NULL,
  barcode VARCHAR(64),
  description TEXT,
  sale_price DECIMAL(12,2) NOT NULL DEFAULT 0,
  cost_price DECIMAL(12,2) NOT NULL DEFAULT 0,
  tax_rate DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  is_active BOOLEAN NOT NULL DEFAULT TRUE,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  created_by BIGINT NULL,
  updated_by BIGINT NULL,
  CONSTRAINT fk_prod_created_by FOREIGN KEY(created_by) REFERENCES users(id),
  CONSTRAINT fk_prod_updated_by FOREIGN KEY(updated_by) REFERENCES users(id),
  CONSTRAINT fk_prod_brand FOREIGN KEY(brand_id) REFERENCES brands(id) ON DELETE SET NULL,
  CONSTRAINT fk_prod_cat FOREIGN KEY(category_id) REFERENCES categories(id) ON DELETE SET NULL,
  CONSTRAINT fk_prod_unit FOREIGN KEY(unit_id) REFERENCES units(id) ON DELETE SET NULL,
  INDEX idx_prod_name (name),
  INDEX idx_prod_cat (category_id)
) ENGINE=InnoDB;

-- Ảnh sản phẩm
CREATE TABLE product_images (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  product_id BIGINT NOT NULL,
  image_url VARCHAR(255) NOT NULL,
  is_primary BOOLEAN NOT NULL DEFAULT FALSE,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  created_by BIGINT NULL,
  updated_by BIGINT NULL,
  CONSTRAINT fk_pimg_created_by FOREIGN KEY(created_by) REFERENCES users(id),
  CONSTRAINT fk_pimg_updated_by FOREIGN KEY(updated_by) REFERENCES users(id),
  CONSTRAINT fk_pimg_prod FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================================
-- 3) PROMOTIONS / VOUCHERS
-- =====================================================================

-- Chương trình khuyến mãi
CREATE TABLE promotions (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(250) NOT NULL,
  description TEXT,
  discount_type ENUM('percentage','fixed') NOT NULL DEFAULT 'percentage',
  discount_value DECIMAL(12,2) NOT NULL DEFAULT 0,
  apply_to ENUM('all','category','product') NOT NULL DEFAULT 'all',
  priority INT NOT NULL DEFAULT 0,
  starts_at DATETIME NOT NULL,
  ends_at DATETIME NOT NULL,
  is_active BOOLEAN NOT NULL DEFAULT TRUE,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  created_by BIGINT NULL,
  updated_by BIGINT NULL,
  CONSTRAINT fk_promotions_created_by FOREIGN KEY(created_by) REFERENCES users(id),
  CONSTRAINT fk_promotions_updated_by FOREIGN KEY(updated_by) REFERENCES users(id),
  CHECK (ends_at > starts_at)
) ENGINE=InnoDB;

-- Mối quan hệ khuyến mãi - sản phẩm
CREATE TABLE promotion_products (
  promotion_id BIGINT NOT NULL,
  product_id BIGINT NOT NULL,
  PRIMARY KEY (promotion_id, product_id),
  CONSTRAINT fk_ppromo_p FOREIGN KEY(promotion_id) REFERENCES promotions(id) ON DELETE CASCADE,
  CONSTRAINT fk_ppromo_prod FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Mối quan hệ khuyến mãi - danh mục
CREATE TABLE promotion_categories (
  promotion_id BIGINT NOT NULL,
  category_id BIGINT NOT NULL,
  PRIMARY KEY (promotion_id, category_id),
  CONSTRAINT fk_pcat_promo FOREIGN KEY(promotion_id) REFERENCES promotions(id) ON DELETE CASCADE,
  CONSTRAINT fk_pcat_cat FOREIGN KEY(category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Mã giảm giá
CREATE TABLE coupons (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  code VARCHAR(64) NOT NULL UNIQUE,
  name VARCHAR(250),
  discount_type ENUM('Phần trăm','Số tiền') NOT NULL,
  discount_value DECIMAL(12,2) NOT NULL,
  max_discount DECIMAL(12,2) DEFAULT 0 COMMENT 'Giảm tối đa (chỉ áp dụng cho loại Phần trăm)',
  max_uses INT DEFAULT 0,
  max_uses_per_customer INT DEFAULT 0 COMMENT 'Số lần tối đa mỗi khách có thể dùng mã này (0 = không giới hạn)',
  used_count INT NOT NULL DEFAULT 0,
  starts_at DATETIME NOT NULL,
  ends_at DATETIME NOT NULL,
  min_order_value DECIMAL(12,2) DEFAULT 0,
  is_active BOOLEAN NOT NULL DEFAULT TRUE,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  created_by BIGINT NULL,
  updated_by BIGINT NULL,
  CONSTRAINT fk_coupons_created_by FOREIGN KEY(created_by) REFERENCES users(id),
  CONSTRAINT fk_coupons_updated_by FOREIGN KEY(updated_by) REFERENCES users(id),
  CHECK (ends_at > starts_at)
) ENGINE=InnoDB COMMENT='Quản lý mã giảm giá với giới hạn sử dụng tổng thể và theo từng khách hàng';

-- =====================================================================
-- 4) CARTS
-- =====================================================================

-- Giỏ hàng
CREATE TABLE carts (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  user_id BIGINT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  created_by BIGINT NULL,
  updated_by BIGINT NULL,
  CONSTRAINT fk_cart_created_by FOREIGN KEY(created_by) REFERENCES users(id),
  CONSTRAINT fk_cart_updated_by FOREIGN KEY(updated_by) REFERENCES users(id),
  CONSTRAINT fk_cart_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
  UNIQUE KEY uniq_cart_user (user_id)
) ENGINE=InnoDB;

-- Mặt hàng trong giỏ
CREATE TABLE cart_items (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  cart_id BIGINT NOT NULL,
  product_id BIGINT NOT NULL,
  qty INT NOT NULL CHECK (qty > 0),
  price DECIMAL(12,2) NOT NULL CHECK (price >= 0),
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  created_by BIGINT NULL,
  updated_by BIGINT NULL,
  CONSTRAINT fk_citem_created_by FOREIGN KEY(created_by) REFERENCES users(id),
  CONSTRAINT fk_citem_updated_by FOREIGN KEY(updated_by) REFERENCES users(id),
  CONSTRAINT fk_citem_cart FOREIGN KEY(cart_id) REFERENCES carts(id) ON DELETE CASCADE,
  CONSTRAINT fk_citem_prod FOREIGN KEY(product_id) REFERENCES products(id),
  UNIQUE KEY uniq_cartitem (cart_id, product_id)
) ENGINE=InnoDB;

-- =====================================================================
-- 5) ORDERS / PAYMENTS / REVIEWS
-- =====================================================================

-- Thanh toán
CREATE TABLE payments (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  amount DECIMAL(12,2) NOT NULL CHECK (amount >= 0),
  method ENUM(
    'Tiền mặt','Chuyển khoản','Quẹt thẻ',
    'PayPal','Thanh toán khi nhận hàng (COD)'
  ) NOT NULL,
  txn_ref VARCHAR(250),
  paid_at DATETIME NULL,
  meta JSON,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Đơn hàng
CREATE TABLE orders (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  code VARCHAR(32) NOT NULL UNIQUE,
  user_id BIGINT NULL,
  order_type ENUM('Online','Offline') NOT NULL DEFAULT 'Online',
  status ENUM('Chờ xử lý','Đang xử lý','Hoàn tất','Đã hủy') NOT NULL DEFAULT 'Chờ xử lý',
  subtotal DECIMAL(12,2) NOT NULL DEFAULT 0,
  promotion_discount DECIMAL(12,2) NOT NULL DEFAULT 0,
  discount_total DECIMAL(12,2) NOT NULL DEFAULT 0,
  shipping_fee DECIMAL(12,2) NOT NULL DEFAULT 0,
  cod_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  grand_total DECIMAL(12,2) NOT NULL DEFAULT 0,
  coupon_code VARCHAR(64) NULL,
  loyalty_points_used INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Số điểm đã sử dụng',
  loyalty_discount DECIMAL(15,2) NOT NULL DEFAULT 0 COMMENT 'Số tiền giảm bằng điểm',
  loyalty_points_earned INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Số điểm tích được từ đơn này',
  payment_method ENUM(
    'Tiền mặt','Chuyển khoản','Quẹt thẻ',
    'PayPal','Thanh toán khi nhận hàng (COD)'
  ) NOT NULL,
  payment_status ENUM('Chưa thanh toán','Đã thanh toán','Hoàn tiền') NOT NULL DEFAULT 'Chưa thanh toán',
  payment_id BIGINT NULL,
  shipping_address_id BIGINT NULL,
  note VARCHAR(255),
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  created_by BIGINT NULL,
  updated_by BIGINT NULL,
  CONSTRAINT fk_order_created_by FOREIGN KEY(created_by) REFERENCES users(id),
  CONSTRAINT fk_order_updated_by FOREIGN KEY(updated_by) REFERENCES users(id),
  CONSTRAINT fk_order_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_order_shipaddr FOREIGN KEY(shipping_address_id) REFERENCES user_addresses(id) ON DELETE SET NULL,
  CONSTRAINT fk_order_payment FOREIGN KEY(payment_id) REFERENCES payments(id) ON DELETE SET NULL,
  CONSTRAINT chk_order_payment_method_by_type CHECK (
    (order_type = 'Offline' AND payment_method IN ('Tiền mặt','Chuyển khoản','Quẹt thẻ'))
    OR
    (order_type = 'Online' AND payment_method IN ('PayPal','Thanh toán khi nhận hàng (COD)'))
  ),
  INDEX idx_orders_user (user_id),
  INDEX idx_orders_status (status),
  INDEX idx_orders_createdat (created_at),
  INDEX idx_orders_payment (payment_id)
) ENGINE=InnoDB;

-- Mặt hàng trong đơn
CREATE TABLE order_items (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  order_id BIGINT NOT NULL,
  product_id BIGINT NOT NULL,
  qty INT NOT NULL CHECK (qty > 0),
  unit_price DECIMAL(12,2) NOT NULL CHECK (unit_price >= 0),
  unit_cost DECIMAL(12,2) NULL,
  discount DECIMAL(12,2) NOT NULL DEFAULT 0,
  tax DECIMAL(12,2) NOT NULL DEFAULT 0,
  line_total DECIMAL(12,2) NOT NULL DEFAULT 0,
  line_cogs DECIMAL(14,2) NULL,
  is_gift BOOLEAN NOT NULL DEFAULT FALSE,
  promotion_id BIGINT NULL,
  CONSTRAINT fk_oit_order FOREIGN KEY(order_id) REFERENCES orders(id) ON DELETE CASCADE,
  CONSTRAINT fk_oit_prod FOREIGN KEY(product_id) REFERENCES products(id),
  INDEX idx_oit_order (order_id),
  INDEX idx_oit_prod (product_id),
  INDEX idx_oit_isgift (is_gift),
  INDEX idx_oit_promo (promotion_id)
) ENGINE=InnoDB;

-- Liên kết người dùng - mã giảm giá (theo dõi nhiều lần sử dụng)
CREATE TABLE user_coupons (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  user_id BIGINT NOT NULL,
  coupon_id BIGINT NOT NULL,
  order_id BIGINT NULL,
  status ENUM('Được cấp','Đã sử dụng','Hết hạn') NOT NULL DEFAULT 'Được cấp',
  assigned_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  used_at DATETIME NULL,
  expires_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  created_by BIGINT NULL,
  updated_by BIGINT NULL,
  CONSTRAINT fk_uc_created_by FOREIGN KEY(created_by) REFERENCES users(id),
  CONSTRAINT fk_uc_updated_by FOREIGN KEY(updated_by) REFERENCES users(id),
  CONSTRAINT fk_uc_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_uc_coupon FOREIGN KEY(coupon_id) REFERENCES coupons(id) ON DELETE CASCADE,
  CONSTRAINT fk_uc_order FOREIGN KEY(order_id) REFERENCES orders(id) ON DELETE SET NULL,
  INDEX idx_uc_user_coupon (user_id, coupon_id),
  INDEX idx_uc_order (order_id),
  INDEX idx_uc_status (status)
) ENGINE=InnoDB COMMENT='Theo dõi việc sử dụng mã giảm giá của từng người dùng. Mỗi lần sử dụng tạo 1 bản ghi mới.';

-- Loyalty transactions
CREATE TABLE loyalty_transactions (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL COMMENT 'Mã khách hàng',
    order_id BIGINT NULL COMMENT 'Mã đơn hàng (nếu có)',
    points INT NOT NULL COMMENT 'Số điểm (+/-)',
    transaction_type ENUM('earn', 'redeem', 'manual_adjust') NOT NULL,
    description VARCHAR(255) NULL,
    balance_before INT UNSIGNED NOT NULL DEFAULT 0,
    balance_after INT UNSIGNED NOT NULL DEFAULT 0,
    created_by BIGINT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_lt_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_lt_order FOREIGN KEY(order_id) REFERENCES orders(id) ON DELETE SET NULL,
    CONSTRAINT fk_lt_created_by FOREIGN KEY(created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_lt_user (user_id),
    INDEX idx_lt_order (order_id),
    INDEX idx_lt_created (created_at),
    INDEX idx_lt_type (transaction_type)
) ENGINE=InnoDB COMMENT='Lịch sử giao dịch điểm thành viên';

-- Lịch sử sử dụng mã giảm giá
CREATE TABLE coupon_usage (
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

-- Đánh giá sản phẩm
CREATE TABLE product_reviews (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  order_id BIGINT NULL,
  product_id BIGINT NOT NULL,
  user_id BIGINT NOT NULL,
  rating TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
  title VARCHAR(250),
  content TEXT,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  created_by BIGINT NULL,
  CONSTRAINT fk_prev_created_by FOREIGN KEY(created_by) REFERENCES users(id),
  CONSTRAINT fk_prev_prod FOREIGN KEY(product_id) REFERENCES products(id),
  CONSTRAINT fk_prev_user FOREIGN KEY(user_id) REFERENCES users(id),
  CONSTRAINT fk_prev_order FOREIGN KEY(order_id) REFERENCES orders(id) ON DELETE SET NULL,
  UNIQUE (product_id, user_id)
) ENGINE=InnoDB;

-- Phiếu thu
CREATE TABLE receipt_vouchers (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  code VARCHAR(32) NOT NULL UNIQUE,
  order_id BIGINT NULL,
  payment_id BIGINT NULL,
  payer_user_id BIGINT NULL,
  payer_name VARCHAR(250) NULL,
  txn_ref VARCHAR(250) NULL,
  bank_time DATETIME NULL,
  method ENUM(
    'Tiền mặt','Chuyển khoản','Quẹt thẻ',
    'PayPal','Thanh toán khi nhận hàng (COD)'
  ) NOT NULL,
  amount DECIMAL(12,2) NOT NULL CHECK (amount >= 0),
  received_by BIGINT NULL,
  received_at DATETIME NULL,
  note VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  created_by BIGINT NULL,
  CONSTRAINT fk_rv_created_by FOREIGN KEY(created_by) REFERENCES users(id),
  CONSTRAINT fk_rv_order FOREIGN KEY(order_id) REFERENCES orders(id) ON DELETE SET NULL,
  CONSTRAINT fk_rv_payment FOREIGN KEY(payment_id) REFERENCES payments(id) ON DELETE SET NULL,
  CONSTRAINT fk_rv_payer FOREIGN KEY(payer_user_id) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_rv_receiver FOREIGN KEY(received_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_rv_order (order_id),
  INDEX idx_rv_payment (payment_id),
  INDEX idx_rv_payer (payer_user_id),
  INDEX idx_rv_received (received_at)
) ENGINE=InnoDB;

-- =====================================================================
-- 6) WAREHOUSE / INVENTORY / PURCHASE
-- =====================================================================

-- Tồn kho
CREATE TABLE stocks (
  product_id BIGINT PRIMARY KEY,
  qty INT NOT NULL DEFAULT 0,
  safety_stock INT NOT NULL DEFAULT 10,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  updated_by BIGINT NULL,
  CONSTRAINT fk_st_updated_by FOREIGN KEY(updated_by) REFERENCES users(id),
  CONSTRAINT fk_st_prod FOREIGN KEY(product_id) REFERENCES products(id)
) ENGINE=InnoDB;

-- Lịch sử thay đổi tồn kho
CREATE TABLE stock_movements (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  product_id BIGINT NOT NULL,
  type ENUM('Nhập kho','Xuất kho','Điều chỉnh','Bán hàng','Trả hàng') NOT NULL,
  ref_type ENUM('Phiếu nhập','Đơn hàng','Kiểm kê','Thủ công') NOT NULL,
  ref_id BIGINT NULL,
  qty INT NOT NULL,
  unit_cost DECIMAL(12,2) NULL,
  note VARCHAR(255),
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_sm_prod FOREIGN KEY(product_id) REFERENCES products(id)
) ENGINE=InnoDB;

-- Nhà cung cấp
CREATE TABLE suppliers (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(250) NOT NULL,
  phone VARCHAR(32),
  email VARCHAR(250),
  address VARCHAR(255),
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  created_by BIGINT NULL,
  updated_by BIGINT NULL,
  CONSTRAINT fk_sp_updated_by FOREIGN KEY(updated_by) REFERENCES users(id),
  CONSTRAINT fk_sp_created_by FOREIGN KEY(created_by) REFERENCES users(id)
) ENGINE=InnoDB;

-- Tài khoản ngân hàng nhà cung cấp
CREATE TABLE supplier_bank_accounts (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  supplier_id BIGINT NOT NULL,
  bank_name VARCHAR(250) NOT NULL,
  account_number VARCHAR(50) NOT NULL,
  account_name VARCHAR(250) NOT NULL,
  branch VARCHAR(250) NULL,
  is_default BOOLEAN NOT NULL DEFAULT FALSE,
  is_active BOOLEAN NOT NULL DEFAULT TRUE,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  created_by BIGINT NULL,
  updated_by BIGINT NULL,
  CONSTRAINT fk_sba_supplier FOREIGN KEY(supplier_id) REFERENCES suppliers(id) ON DELETE CASCADE,
  CONSTRAINT fk_sba_created_by FOREIGN KEY(created_by) REFERENCES users(id),
  CONSTRAINT fk_sba_updated_by FOREIGN KEY(updated_by) REFERENCES users(id),
  INDEX idx_sba_supplier (supplier_id)
) ENGINE=InnoDB;

-- Liên kết Nhà cung cấp - Sản phẩm
CREATE TABLE supplier_products (
  supplier_id BIGINT NOT NULL,
  product_id BIGINT NOT NULL,
  supplier_sku VARCHAR(64) NULL,
  default_cost DECIMAL(12,2) NULL,
  moq INT NOT NULL DEFAULT 1,
  lead_time_days INT NOT NULL DEFAULT 0,
  preference_score INT NOT NULL DEFAULT 100,
  is_active BOOLEAN NOT NULL DEFAULT TRUE,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  created_by BIGINT NULL,
  CONSTRAINT fk_supplier_products_created_by FOREIGN KEY(created_by) REFERENCES users(id),
  PRIMARY KEY (supplier_id, product_id),
  CONSTRAINT fk_supplier_products_supplier FOREIGN KEY(supplier_id) REFERENCES suppliers(id) ON DELETE CASCADE,
  CONSTRAINT fk_supplier_products_product FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE CASCADE,
  INDEX idx_sp_prod_pref (product_id, preference_score),
  INDEX idx_sp_supplier (supplier_id)
) ENGINE=InnoDB;

-- Phiếu nhập kho
CREATE TABLE purchase_orders (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  code VARCHAR(32) NOT NULL UNIQUE,
  supplier_id BIGINT NOT NULL,
  total_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  paid_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  payment_status ENUM(
    'Chưa đối soát',
    'Đã thanh toán một phần',
    'Đã thanh toán hết'
  ) NOT NULL DEFAULT 'Chưa đối soát',
  due_date DATE NULL,
  note VARCHAR(255) NULL,
  received_at DATETIME NULL,
  created_by BIGINT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  updated_by BIGINT NULL,
  CONSTRAINT fk_po_updated_by FOREIGN KEY(updated_by) REFERENCES users(id),
  CONSTRAINT fk_po_sup FOREIGN KEY(supplier_id) REFERENCES suppliers(id),
  CONSTRAINT fk_po_user FOREIGN KEY(created_by) REFERENCES users(id),
  INDEX idx_po_supplier (supplier_id),
  INDEX idx_po_created_by (created_by),
  INDEX idx_po_code (code),
  INDEX idx_po_created_at (created_at),
  INDEX idx_po_payment (payment_status),
  INDEX idx_po_due (due_date)
) ENGINE=InnoDB;

-- Quản lý lô sản phẩm và hạn sử dụng
CREATE TABLE product_batches (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  product_id BIGINT NOT NULL,
  batch_code VARCHAR(64) NOT NULL,
  mfg_date DATE NULL,
  exp_date DATE NOT NULL,
  initial_qty INT NOT NULL CHECK (initial_qty >= 0),
  current_qty INT NOT NULL DEFAULT 0,
  unit_cost DECIMAL(12,2) NOT NULL DEFAULT 0,
  purchase_order_id BIGINT NULL,
  note VARCHAR(255) NULL,
  is_active BOOLEAN NOT NULL DEFAULT TRUE,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  created_by BIGINT NULL,
  updated_by BIGINT NULL,
  CONSTRAINT fk_pb_prod FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE CASCADE,
  CONSTRAINT fk_pb_po FOREIGN KEY(purchase_order_id) REFERENCES purchase_orders(id) ON DELETE SET NULL,
  CONSTRAINT fk_pb_user FOREIGN KEY(created_by) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_pb_user_updated FOREIGN KEY(updated_by) REFERENCES users(id) ON DELETE SET NULL,
  UNIQUE KEY uniq_prod_batch (product_id, batch_code),
  INDEX idx_pb_prod (product_id),
  INDEX idx_pb_exp (exp_date)
) ENGINE=InnoDB;

-- Chi tiết phiếu nhập kho
CREATE TABLE purchase_order_items (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  purchase_order_id BIGINT NOT NULL,
  product_id BIGINT NOT NULL,
  batch_code VARCHAR(64) NULL,
  qty INT NOT NULL CHECK (qty > 0),
  unit_cost DECIMAL(12,2) NOT NULL CHECK (unit_cost >= 0),
  line_total DECIMAL(12,2) NOT NULL CHECK (line_total >= 0),
  mfg_date DATE NULL,
  exp_date DATE NULL,
  CONSTRAINT fk_poi_po FOREIGN KEY(purchase_order_id) REFERENCES purchase_orders(id) ON DELETE CASCADE,
  CONSTRAINT fk_poi_prod FOREIGN KEY(product_id) REFERENCES products(id),
  INDEX idx_poi_po (purchase_order_id),
  INDEX idx_poi_prod (product_id)
) ENGINE=InnoDB;

-- Phiếu chi
CREATE TABLE expense_vouchers (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  code VARCHAR(32) NOT NULL UNIQUE,
  type ENUM('Nhà cung cấp','Lương nhân viên') NOT NULL DEFAULT 'Nhà cung cấp',
  purchase_order_id BIGINT NULL,
  supplier_id BIGINT NULL,
  bank_account_id BIGINT NULL,
  payroll_id BIGINT NULL,
  staff_user_id BIGINT NULL,
  method ENUM('Tiền mặt','Chuyển khoản') NOT NULL,
  txn_ref VARCHAR(250) NULL,
  amount DECIMAL(12,2) NOT NULL CHECK (amount >= 0),
  paid_by BIGINT NULL,
  paid_at DATETIME NULL,
  bank_time DATETIME NULL,
  note VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  created_by BIGINT NULL,
  CONSTRAINT fk_ev_created_by FOREIGN KEY(created_by) REFERENCES users(id),
  CONSTRAINT fk_ev_po FOREIGN KEY(purchase_order_id) REFERENCES purchase_orders(id) ON DELETE RESTRICT,
  CONSTRAINT fk_ev_sup FOREIGN KEY(supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL,
  CONSTRAINT fk_ev_bank_account FOREIGN KEY(bank_account_id) REFERENCES supplier_bank_accounts(id) ON DELETE SET NULL,
  CONSTRAINT fk_ev_paid_by FOREIGN KEY(paid_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_ev_type (type),
  INDEX idx_ev_po (purchase_order_id),
  INDEX idx_ev_sup (supplier_id),
  INDEX idx_ev_staff (staff_user_id),
  INDEX idx_ev_paid (paid_at)
) ENGINE=InnoDB;

-- Công nợ nhà cung cấp
CREATE TABLE ap_ledger (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  supplier_id BIGINT NOT NULL,
  ref_type ENUM('Phiếu nhập','Phiếu chi','Điều chỉnh') NOT NULL,
  ref_id BIGINT NULL,
  debit DECIMAL(14,2) NOT NULL DEFAULT 0,
  credit DECIMAL(14,2) NOT NULL DEFAULT 0,
  note VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  created_by BIGINT NULL,
  CONSTRAINT fk_ap_created_by FOREIGN KEY(created_by) REFERENCES users(id),
  CONSTRAINT fk_ap_sup FOREIGN KEY(supplier_id) REFERENCES suppliers(id) ON DELETE CASCADE,
  INDEX idx_ap_sup (supplier_id),
  INDEX idx_ap_reft (ref_type),
  INDEX idx_ap_refid (ref_id),
  INDEX idx_ap_time (created_at),
  CHECK (debit >= 0 AND credit >= 0)
) ENGINE=InnoDB;

-- Kiểm kê kho
CREATE TABLE stocktakes (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  note VARCHAR(255),
  created_by BIGINT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_stt_by FOREIGN KEY(created_by) REFERENCES users(id)
) ENGINE=InnoDB;

-- Chi tiết kiểm kê kho
CREATE TABLE stocktake_items (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  stocktake_id BIGINT NOT NULL,
  product_id BIGINT NOT NULL,
  system_qty INT NOT NULL,
  counted_qty INT NOT NULL,
  difference INT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  created_by BIGINT NULL,
  CONSTRAINT fk_stti_stt FOREIGN KEY(stocktake_id) REFERENCES stocktakes(id) ON DELETE CASCADE,
  CONSTRAINT fk_stti_prod FOREIGN KEY(product_id) REFERENCES products(id),
  CONSTRAINT fk_stti_created_by FOREIGN KEY(created_by) REFERENCES users(id)
) ENGINE=InnoDB;

-- Phiếu xuất kho
CREATE TABLE stock_outs (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  code VARCHAR(32) NOT NULL UNIQUE,
  type ENUM('sale','return','damage','other') NOT NULL DEFAULT 'sale',
  order_id BIGINT NULL,
  status ENUM('pending','approved','completed','cancelled') NOT NULL DEFAULT 'pending',
  out_date DATETIME NOT NULL,
  total_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
  note VARCHAR(500) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  created_by BIGINT NULL,
  updated_by BIGINT NULL,
  CONSTRAINT fk_so_order FOREIGN KEY(order_id) REFERENCES orders(id) ON DELETE SET NULL,
  CONSTRAINT fk_so_created_by FOREIGN KEY(created_by) REFERENCES users(id),
  CONSTRAINT fk_so_updated_by FOREIGN KEY(updated_by) REFERENCES users(id),
  INDEX idx_so_type (type),
  INDEX idx_so_status (status),
  INDEX idx_so_date (out_date),
  INDEX idx_so_order (order_id)
) ENGINE=InnoDB;

-- Chi tiết phiếu xuất kho
CREATE TABLE stock_out_items (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  stock_out_id BIGINT NOT NULL,
  product_id BIGINT NOT NULL,
  batch_id BIGINT NULL,
  qty INT NOT NULL CHECK (qty > 0),
  unit_price DECIMAL(12,2) NOT NULL DEFAULT 0,
  total_price DECIMAL(14,2) NOT NULL DEFAULT 0,
  note VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_soi_so FOREIGN KEY(stock_out_id) REFERENCES stock_outs(id) ON DELETE CASCADE,
  CONSTRAINT fk_soi_prod FOREIGN KEY(product_id) REFERENCES products(id),
  CONSTRAINT fk_soi_batch FOREIGN KEY(batch_id) REFERENCES product_batches(id) ON DELETE SET NULL,
  INDEX idx_soi_so (stock_out_id),
  INDEX idx_soi_prod (product_id)
) ENGINE=InnoDB;

-- =====================================================================
-- 7) GIAO HÀNG
-- =====================================================================

-- Thông tin vận đơn
CREATE TABLE shipments (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  order_id BIGINT NOT NULL,
  mode ENUM('third_party') NOT NULL DEFAULT 'third_party',
  carrier VARCHAR(50) NOT NULL,
  package_no INT NOT NULL DEFAULT 1,
  service_type_id TINYINT NULL,
  service_code VARCHAR(30) NULL,
  tracking_code VARCHAR(100) NULL,
  carrier_order_code VARCHAR(100) NULL,
  tracking_url VARCHAR(255) NULL,
  status ENUM(
    'Mới tạo',
    'Đã gửi yêu cầu',
    'Đã lấy hàng',
    'Đang vận chuyển',
    'Đang giao',
    'Giao thành công',
    'Phát không thành',
    'Chuyển hoàn',
    'Đã hủy'
  ) NOT NULL DEFAULT 'Mới tạo',
  fee DECIMAL(12,2) NOT NULL DEFAULT 0,
  cod_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  label_url VARCHAR(255),
  expected_delivery_date DATE NULL,
  pickup_time DATETIME NULL,
  handover_at DATETIME NULL,
  last_synced_at DATETIME NULL,
  meta JSON,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  created_by BIGINT NULL,
  CONSTRAINT fk_ship_created_by FOREIGN KEY(created_by) REFERENCES users(id),
  UNIQUE KEY uniq_ship (order_id, package_no),
  UNIQUE KEY uniq_tracking (tracking_code),
  INDEX idx_ship_order (order_id),
  INDEX idx_ship_status (status),
  INDEX idx_ship_created (created_at),
  INDEX idx_ship_updated (updated_at),
  INDEX idx_ship_track (tracking_code),
  INDEX idx_ship_carrier_order (carrier_order_code),
  CONSTRAINT fk_shp_order FOREIGN KEY(order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Sự kiện giao hàng
CREATE TABLE shipment_events (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  shipment_id BIGINT NOT NULL,
  tracking_code VARCHAR(100) NOT NULL,
  status VARCHAR(80) NOT NULL,
  mapped_status ENUM(
    'Mới tạo',
    'Đã gửi yêu cầu',
    'Đã lấy hàng',
    'Đang vận chuyển',
    'Đang giao',
    'Giao thành công',
    'Phát không thành',
    'Chuyển hoàn',
    'Đã hủy'
  ) NULL,
  detail VARCHAR(255),
  location VARCHAR(250) NULL,
  hub_code VARCHAR(64) NULL,
  event_time DATETIME NOT NULL,
  raw_payload JSON,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  created_by BIGINT NULL,
  CONSTRAINT fk_sev_created_by FOREIGN KEY(created_by) REFERENCES users(id),
  INDEX idx_sev_shipment (shipment_id),
  INDEX idx_sev_track (tracking_code),
  INDEX idx_sev_event_at (event_time),
  INDEX idx_sev_mapped (mapped_status),
  CONSTRAINT fk_sev_shipment FOREIGN KEY (shipment_id) REFERENCES shipments(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================================
-- 8) DỊCH VỤ KHÁCH HÀNG
-- =====================================================================

-- Yêu cầu hậu mãi
CREATE TABLE aftersales_requests (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  order_id BIGINT NOT NULL,
  user_id BIGINT NOT NULL,
  request_type ENUM('Khiếu nại','Trả hàng','Hủy đơn') NOT NULL,
  reason ENUM('Hư hỏng','Hết hạn','Giao nhầm','Thiếu hàng','Khác') NOT NULL,
  description TEXT NULL,
  status ENUM('Mở','Đang xử lý','Chấp thuận','Từ chối','Hoàn tất') NOT NULL DEFAULT 'Mở',
  refund_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  approved_by BIGINT NULL,
  resolved_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  created_by BIGINT NULL,
  CONSTRAINT fk_afs_created_by FOREIGN KEY(created_by) REFERENCES users(id),
  CONSTRAINT fk_afs_order FOREIGN KEY(order_id) REFERENCES orders(id) ON DELETE CASCADE,
  CONSTRAINT fk_afs_user FOREIGN KEY(user_id) REFERENCES users(id),
  CONSTRAINT fk_afs_approved FOREIGN KEY(approved_by) REFERENCES users(id),
  INDEX idx_afs_order (order_id),
  INDEX idx_afs_user (user_id),
  INDEX idx_afs_status (status),
  INDEX idx_afs_type (request_type)
) ENGINE=InnoDB;

-- Chi tiết hậu mãi
CREATE TABLE aftersales_items (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  request_id BIGINT NOT NULL,
  order_item_id BIGINT NOT NULL,
  qty INT NOT NULL CHECK (qty > 0),
  refund_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  CONSTRAINT fk_afsi_req FOREIGN KEY(request_id) REFERENCES aftersales_requests(id) ON DELETE CASCADE,
  CONSTRAINT fk_afsi_oit FOREIGN KEY(order_item_id) REFERENCES order_items(id),
  INDEX idx_afsi_req (request_id),
  INDEX idx_afsi_oit (order_item_id)
) ENGINE=InnoDB;

-- =====================================================================
-- 9) LƯU LỊCH SỬ CHỈNH SỬA
-- =====================================================================

CREATE TABLE audit_logs (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  actor_user_id BIGINT NULL,
  entity_type VARCHAR(64) NOT NULL,
  entity_id BIGINT NOT NULL,
  action VARCHAR(32) NOT NULL,
  before_data JSON,
  after_data JSON,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_audit_entity (entity_type, entity_id),
  CONSTRAINT fk_audit_user FOREIGN KEY(actor_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =====================================================================
-- 10) ANALYTICS / RECOMMENDATION
-- =====================================================================

-- Lịch sử tương tác
CREATE TABLE events (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  user_id BIGINT NULL,
  session_id VARCHAR(64) NULL,
  product_id BIGINT NOT NULL,
  action ENUM('view','add','purchase') NOT NULL,
  qty INT DEFAULT 1,
  ts DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY (user_id), KEY (session_id), KEY (product_id), KEY (ts),
  CONSTRAINT fk_evt_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_evt_prod FOREIGN KEY(product_id) REFERENCES products(id)
) ENGINE=InnoDB;

-- Gợi ý
CREATE TABLE recommendations (
  user_id BIGINT NOT NULL,
  product_id BIGINT NOT NULL,
  score DOUBLE NOT NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, product_id),
  CONSTRAINT fk_rec_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_rec_prod FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Sản phẩm tương tự
CREATE TABLE similar_items (
  product_id BIGINT NOT NULL,
  similar_id BIGINT NOT NULL,
  score DOUBLE NOT NULL,
  PRIMARY KEY (product_id, similar_id),
  CONSTRAINT fk_sim_prod FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE CASCADE,
  CONSTRAINT fk_sim_sim FOREIGN KEY(similar_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================================
-- 11) CONFIG
-- =====================================================================

-- Zones giao hàng
CREATE TABLE shipping_zones (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL,
  base_fee DECIMAL(12,2) NOT NULL DEFAULT 0,
  fee_per_km DECIMAL(12,2) NOT NULL DEFAULT 0,
  cod_surcharge DECIMAL(12,2) NOT NULL DEFAULT 0,
  is_active BOOLEAN NOT NULL DEFAULT TRUE
) ENGINE=InnoDB;

-- Cấu hình carrier
CREATE TABLE carrier_configs (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  carrier VARCHAR(50) NOT NULL UNIQUE,
  api_key VARCHAR(255) NOT NULL,
  api_secret VARCHAR(255) NULL,
  sandbox BOOLEAN NOT NULL DEFAULT TRUE,
  webhook_secret VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================================
-- 12) CHẤM CÔNG & LƯƠNG
-- =====================================================================

-- Ca làm việc
CREATE TABLE work_shifts (
    id TINYINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by BIGINT,
    updated_by BIGINT,
    CONSTRAINT fk_shifts_created_by FOREIGN KEY(created_by) REFERENCES users(id),
    CONSTRAINT fk_shifts_updated_by FOREIGN KEY(updated_by) REFERENCES users(id),
    INDEX idx_shift_active (is_active)
) ENGINE=InnoDB;

-- Dữ liệu mẫu cho ca làm việc
INSERT INTO work_shifts (name, start_time, end_time, is_active) VALUES
('Ca sáng', '06:00:00', '14:00:00', 1),
('Ca chiều', '14:00:00', '22:00:00', 1);

-- Lịch làm việc nhân viên
CREATE TABLE staff_shift_schedule (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    staff_id BIGINT NOT NULL,
    shift_id TINYINT UNSIGNED NOT NULL,
    work_date DATE NOT NULL,
    status ENUM('Làm việc', 'Nghỉ', 'Có phép', 'Không phép') DEFAULT 'Làm việc',
    note VARCHAR(255),
    created_by BIGINT,
    updated_by BIGINT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_schedule_staff FOREIGN KEY (staff_id) REFERENCES staff_profiles(user_id) ON DELETE CASCADE,
    CONSTRAINT fk_schedule_shift FOREIGN KEY (shift_id) REFERENCES work_shifts(id),
    CONSTRAINT fk_schedule_created FOREIGN KEY (created_by) REFERENCES users(id),
    CONSTRAINT fk_schedule_updated FOREIGN KEY (updated_by) REFERENCES users(id),
    UNIQUE KEY uniq_staff_shift_date (staff_id, shift_id, work_date),
    INDEX idx_schedule_date (work_date),
    INDEX idx_schedule_staff (staff_id),
    INDEX idx_schedule_status (status)
) ENGINE=InnoDB;

-- Chấm công
CREATE TABLE attendances (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL,
    shift_id TINYINT UNSIGNED NOT NULL,
    attendance_date DATE NOT NULL,
    check_in_time DATETIME,
    check_out_time DATETIME,
    check_in_status ENUM('Đúng giờ', 'Muộn', 'Chưa chấm') DEFAULT 'Chưa chấm',
    check_in_ip VARCHAR(45) NULL COMMENT 'IP address khi check-in',
    check_out_status ENUM('Đúng giờ', 'Sớm', 'Chưa chấm') DEFAULT 'Chưa chấm',
    check_out_ip VARCHAR(45) NULL COMMENT 'IP address khi check-out',
    status ENUM('Có mặt', 'Vắng mặt', 'Đi muộn', 'Về sớm', 'Chưa hoàn thành') DEFAULT 'Chưa hoàn thành',
    work_hours DECIMAL(5,2) DEFAULT 0,
    notes TEXT,
    is_approved TINYINT(1) DEFAULT 0,
    approved_by BIGINT,
    approved_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_att_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_att_shift FOREIGN KEY(shift_id) REFERENCES work_shifts(id),
    CONSTRAINT fk_att_approved_by FOREIGN KEY(approved_by) REFERENCES users(id),
    UNIQUE KEY uniq_att_user_shift_date (user_id, shift_id, attendance_date),
    INDEX idx_att_date (attendance_date),
    INDEX idx_att_user (user_id),
    INDEX idx_att_status (status),
    INDEX idx_att_check_in_status (check_in_status),
    INDEX idx_att_check_out_status (check_out_status),
    INDEX idx_check_in_ip (check_in_ip),
    INDEX idx_check_out_ip (check_out_ip)
) ENGINE=InnoDB;

-- Bảng lương
CREATE TABLE payrolls (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL,
    month TINYINT UNSIGNED NOT NULL,
    year SMALLINT UNSIGNED NOT NULL,
    total_shifts_worked SMALLINT UNSIGNED DEFAULT 0,
    required_shifts TINYINT UNSIGNED DEFAULT 28,
    base_salary DECIMAL(15,2) DEFAULT 0,
    actual_salary DECIMAL(15,2) DEFAULT 0,
    bonus DECIMAL(15,2) DEFAULT 0,
    deduction DECIMAL(15,2) DEFAULT 0,
    late_deduction DECIMAL(15,2) DEFAULT 0 COMMENT 'Phạt đi trễ/về sớm',
    total_salary DECIMAL(15,2) DEFAULT 0 COMMENT 'Tổng lương = actual_salary + bonus - deduction - late_deduction',
    status ENUM('Nháp', 'Đã duyệt', 'Đã trả') DEFAULT 'Nháp',
    notes TEXT,
    created_by BIGINT,
    updated_by BIGINT,
    approved_by BIGINT,
    approved_at DATETIME,
    paid_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_payroll_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_payroll_created_by FOREIGN KEY(created_by) REFERENCES users(id),
    CONSTRAINT fk_payroll_updated_by FOREIGN KEY(updated_by) REFERENCES users(id),
    CONSTRAINT fk_payroll_approved_by FOREIGN KEY(approved_by) REFERENCES users(id),
    UNIQUE KEY uniq_payroll_user_month_year (user_id, month, year),
    INDEX idx_payroll_month_year (month, year),
    INDEX idx_payroll_status (status)
) ENGINE=InnoDB;

-- Thêm FK cho expense_vouchers
ALTER TABLE expense_vouchers
ADD CONSTRAINT fk_ev_payroll FOREIGN KEY(payroll_id) REFERENCES payrolls(id) ON DELETE RESTRICT,
ADD CONSTRAINT fk_ev_staff FOREIGN KEY(staff_user_id) REFERENCES users(id) ON DELETE SET NULL;

-- Chi tiết bảng lương
CREATE TABLE payroll_details (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    payroll_id BIGINT NOT NULL,
    attendance_id BIGINT NOT NULL,
    shift_id TINYINT UNSIGNED NOT NULL,
    shift_date DATE NOT NULL,
    wage_amount DECIMAL(15,2) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_pd_payroll FOREIGN KEY(payroll_id) REFERENCES payrolls(id) ON DELETE CASCADE,
    CONSTRAINT fk_pd_attendance FOREIGN KEY(attendance_id) REFERENCES attendances(id) ON DELETE CASCADE,
    CONSTRAINT fk_pd_shift FOREIGN KEY(shift_id) REFERENCES work_shifts(id),
    INDEX idx_pd_payroll (payroll_id),
    INDEX idx_pd_date (shift_date)
) ENGINE=InnoDB;

-- =====================================================================
-- 13) THÔNG BÁO & QUẢN LÝ HỆ THỐNG
-- =====================================================================

-- Thông báo
CREATE TABLE notifications (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  user_id BIGINT NOT NULL,
  type ENUM('warning','info','success','error') NOT NULL DEFAULT 'info',
  title VARCHAR(255) NOT NULL,
  message TEXT NOT NULL,
  link VARCHAR(255) NULL,
  is_read BOOLEAN NOT NULL DEFAULT FALSE,
  read_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_notif_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_notif_user_read (user_id, is_read),
  INDEX idx_notif_created (created_at)
) ENGINE=InnoDB;

-- Quản lý công việc hệ thống
CREATE TABLE system_jobs (
    job_name VARCHAR(100) PRIMARY KEY,
    last_run DATETIME DEFAULT NULL
) ENGINE=InnoDB;

-- Lịch sử nhập file
CREATE TABLE import_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    table_name VARCHAR(100) NOT NULL COMMENT 'Tên bảng được nhập (categories, products, etc)',
    file_name VARCHAR(255) NOT NULL COMMENT 'Tên file gốc',
    total_rows INT DEFAULT 0 COMMENT 'Tổng số dòng trong file',
    success_rows INT DEFAULT 0 COMMENT 'Số dòng nhập thành công',
    failed_rows INT DEFAULT 0 COMMENT 'Số dòng thất bại',
    status ENUM('success', 'partial', 'failed') DEFAULT 'success' COMMENT 'Trạng thái nhập',
    error_details TEXT COMMENT 'Chi tiết lỗi (JSON)',
    file_content LONGTEXT COMMENT 'Nội dung file (JSON)',
    imported_by INT COMMENT 'ID người nhập',
    imported_by_name VARCHAR(255) COMMENT 'Tên người nhập',
    imported_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Thời gian nhập',
    INDEX idx_table_name (table_name),
    INDEX idx_imported_by (imported_by),
    INDEX idx_imported_at (imported_at)
) ENGINE=InnoDB COMMENT='Lịch sử nhập file Excel';

-- =====================================================================
-- 14) VIEWS & STORED PROCEDURES
-- =====================================================================

-- View: Số lần sử dụng coupon theo khách hàng
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

-- Stored Procedure: Kiểm tra giới hạn sử dụng coupon
DELIMITER $$

CREATE PROCEDURE check_coupon_usage_limit(
    IN p_coupon_code VARCHAR(64),
    IN p_user_id BIGINT,
    OUT p_can_use BOOLEAN,
    OUT p_message VARCHAR(255)
)
BEGIN
    DECLARE v_coupon_id BIGINT DEFAULT NULL;
    DECLARE v_max_uses_per_customer INT DEFAULT 0;
    DECLARE v_current_usage_count INT DEFAULT 0;

    -- Lấy thông tin coupon
    SELECT id, max_uses_per_customer 
    INTO v_coupon_id, v_max_uses_per_customer
    FROM coupons 
    WHERE UPPER(code) = UPPER(p_coupon_code)
    LIMIT 1;

    -- Kiểm tra mã không tồn tại
    IF v_coupon_id IS NULL THEN
        SET p_can_use = FALSE;
        SET p_message = 'Mã giảm giá không tồn tại';
        
    -- Nếu không giới hạn (= 0) thì cho phép
    ELSEIF v_max_uses_per_customer = 0 THEN
        SET p_can_use = TRUE;
        SET p_message = 'OK';

    ELSE
        -- Đếm số lần khách này đã dùng mã
        SELECT COUNT(*) 
        INTO v_current_usage_count
        FROM coupon_usage
        WHERE coupon_id = v_coupon_id 
          AND user_id = p_user_id;

        -- So sánh với giới hạn
        IF v_current_usage_count >= v_max_uses_per_customer THEN
            SET p_can_use = FALSE;
            SET p_message = CONCAT(
                'Bạn đã sử dụng mã này ',
                v_current_usage_count, '/',
                v_max_uses_per_customer,
                ' lần. Không thể dùng thêm.'
            );
        ELSE
            SET p_can_use = TRUE;
            SET p_message = CONCAT(
                'Còn ',
                v_max_uses_per_customer - v_current_usage_count,
                ' lần sử dụng.'
            );
        END IF;
    END IF;
END$$

DELIMITER ;

-- =====================================================================
-- 15) TRIGGERS
-- =====================================================================

-- Trigger: Tự động ghi log khi dùng mã giảm giá
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

-- =====================================================================
-- 16) MIGRATE DỮ LIỆU CŨ (NẾU CÓ)
-- =====================================================================

-- Migrate lương từ staff_profiles sang salary_history
INSERT INTO salary_history (user_id, salary, from_date, note, created_by)
SELECT 
    sp.user_id,
    sp.base_salary,
    DATE_FORMAT(NOW(), '%Y-%m-01') as from_date,
    'Lương khởi điểm' as note,
    NULL as created_by
FROM staff_profiles sp
WHERE sp.base_salary > 0
  AND NOT EXISTS (
    SELECT 1 FROM salary_history sh WHERE sh.user_id = sp.user_id
  );

-- Migrate lịch sử sử dụng coupon từ orders
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

-- Tạo thông báo cho tồn kho thấp hiện có
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
  AND (u.role_id = 2 OR sp.staff_role IN ('Kho', 'Admin'))
  AND NOT EXISTS (
    SELECT 1 FROM notifications n 
    WHERE n.user_id = u.id 
      AND n.title LIKE CONCAT('%', p.name, '%')
      AND n.created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
  );

-- =====================================================================
-- 17) INDEXES BỔ SUNG (ĐÃ TÍCH HỢP VÀO CREATE TABLE Ở TRÊN)
-- =====================================================================

-- Tất cả indexes đã được thêm trực tiếp vào các câu lệnh CREATE TABLE

-- =====================================================================
-- KẾT THÚC MIGRATION
-- =====================================================================

COMMIT;

-- Hiển thị thông báo hoàn thành
SELECT 'Database migration completed successfully!' AS status;
SELECT COUNT(*) AS total_tables FROM information_schema.tables 
WHERE table_schema = 'mini_market' AND table_type = 'BASE TABLE';