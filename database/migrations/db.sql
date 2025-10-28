
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
  username VARCHAR(50) NOT NULL UNIQUE,          -- tên tài khoản để đăng nhập
  role_id TINYINT NOT NULL DEFAULT 1,
  email VARCHAR(250) UNIQUE,                     -- có thể NULL; vẫn giữ UNIQUE
  phone VARCHAR(32),
  password_hash VARCHAR(255),
  full_name VARCHAR(250),
  avatar_url VARCHAR(255),
  gender ENUM('Nam','Nữ') NULL,
  date_of_birth DATE NULL,
  is_active BOOLEAN NOT NULL DEFAULT TRUE,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  created_by BIGINT NULL,
  updated_by BIGINT NULL,
  CONSTRAINT fk_users_role FOREIGN KEY(role_id) REFERENCES roles(id),
  CONSTRAINT fk_users_created_by FOREIGN KEY(created_by) REFERENCES users(id),
  CONSTRAINT fk_users_updated_by FOREIGN KEY(updated_by) REFERENCES users(id)
) ENGINE=InnoDB;

-- Danh mục TỈNH
CREATE TABLE provinces (
  code VARCHAR(10) PRIMARY KEY,         -- mã tỉnh 
  name VARCHAR(120) NOT NULL,
  is_active BOOLEAN NOT NULL DEFAULT TRUE,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  created_by BIGINT NULL,
  updated_by BIGINT NULL,
  CONSTRAINT fk_provinces_created_by FOREIGN KEY(created_by) REFERENCES users(id),
  CONSTRAINT fk_provinces_updated_by FOREIGN KEY(updated_by) REFERENCES users(id)
) ENGINE=InnoDB;

-- Danh mục XÃ (mỗi xã thuộc một tỉnh)
CREATE TABLE communes (
  code VARCHAR(15) PRIMARY KEY,         -- mã xã
  province_code VARCHAR(10) NOT NULL,   -- FK -> provinces
  name VARCHAR(120) NOT NULL,
  is_active BOOLEAN NOT NULL DEFAULT TRUE,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  created_by BIGINT NULL,
  updated_by BIGINT NULL,
  CONSTRAINT fk_communes_created_by FOREIGN KEY(created_by) REFERENCES users(id),
  CONSTRAINT fk_communes_updated_by FOREIGN KEY(updated_by) REFERENCES users(id),
  CONSTRAINT fk_commune_prov FOREIGN KEY (province_code)
    REFERENCES provinces(code)
) ENGINE=InnoDB;

-- Địa chỉ người nhận
CREATE TABLE user_addresses (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  user_id BIGINT NOT NULL,               -- FK -> users(id)
  receiver_name VARCHAR(250),            -- Tên người nhận
  receiver_phone VARCHAR(32),            -- SĐT người nhận
  line1 VARCHAR(255),                    -- Số nhà / Tên đường
  commune_code VARCHAR(15) NULL,         -- FK -> communes(code)
  province_code VARCHAR(10) NULL,        -- (tuỳ chọn) lưu kèm để lọc nhanh, FK -> provinces(code)
  is_default BOOLEAN NOT NULL DEFAULT FALSE, -- Mặc định
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  created_by BIGINT NULL,
  updated_by BIGINT NULL,
  CONSTRAINT fk_addr_created_by FOREIGN KEY(created_by) REFERENCES users(id),
  CONSTRAINT fk_addr_updated_by FOREIGN KEY(updated_by) REFERENCES users(id),

  CONSTRAINT fk_addr_user     FOREIGN KEY(user_id)       REFERENCES users(id)      ON DELETE CASCADE,
  CONSTRAINT fk_addr_commune  FOREIGN KEY(commune_code)  REFERENCES communes(code),
  CONSTRAINT fk_addr_province FOREIGN KEY(province_code) REFERENCES provinces(code),

  INDEX idx_addr_commune  (commune_code),
  INDEX idx_addr_province (province_code)
) ENGINE=InnoDB;

-- Phân loại nhân viên theo vai trò nội bộ
CREATE TABLE staff_profiles (
  user_id BIGINT PRIMARY KEY,
  staff_role ENUM('Kho','Nhân viên bán hàng','Hỗ trợ trực tuyến','Admin') NOT NULL, 
  hired_at DATE, -- Ngày vào làm
  note VARCHAR(255),
  CONSTRAINT fk_staff_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================================
-- 2) CATALOG: BRAND / CATEGORY / PRODUCT
-- =====================================================================

-- Thương hiệu
CREATE TABLE brands (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(250) NOT NULL UNIQUE,
  slug VARCHAR(250) UNIQUE, -- chuỗi URL thân thiện
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
  is_active BOOLEAN NOT NULL DEFAULT TRUE,  -- Đang hoạt động
  sort_order INT DEFAULT 0, -- Thứ tự hiển thị
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
  sku VARCHAR(64) NOT NULL UNIQUE, -- Mã định danh duy nhất của sản phẩm trong kho
  name VARCHAR(255) NOT NULL,
  slug VARCHAR(255) UNIQUE,
  brand_id BIGINT NULL,     -- Thương hiệu
  category_id BIGINT NULL,  -- Loại
  pack_size VARCHAR(64),    -- 1kg, 5kg, thùng 24 lon...
  unit_id TINYINT UNSIGNED NULL, -- Đơn vị tính (FK -> units)
  barcode VARCHAR(64),      -- mã vạch
  description TEXT,
  sale_price DECIMAL(12,2) NOT NULL DEFAULT 0,  -- giá bán
  cost_price DECIMAL(12,2) NOT NULL DEFAULT 0,  -- giá nhập
  tax_rate DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  is_active BOOLEAN NOT NULL DEFAULT TRUE,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  created_by BIGINT NULL,
  updated_by BIGINT NULL,
  CONSTRAINT fk_prod_created_by FOREIGN KEY(created_by) REFERENCES users(id),
  CONSTRAINT fk_prod_updated_by FOREIGN KEY(updated_by) REFERENCES users(id),
  CONSTRAINT fk_prod_brand FOREIGN KEY(brand_id)   REFERENCES brands(id)      ON DELETE SET NULL,
  CONSTRAINT fk_prod_cat   FOREIGN KEY(category_id) REFERENCES categories(id) ON DELETE SET NULL,
  CONSTRAINT fk_prod_unit  FOREIGN KEY(unit_id)     REFERENCES units(id)      ON DELETE SET NULL
) ENGINE=InnoDB;

-- Ảnh sản phẩm
CREATE TABLE product_images (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  product_id BIGINT NOT NULL,
  image_url VARCHAR(255) NOT NULL,
  is_primary BOOLEAN NOT NULL DEFAULT FALSE, -- Hình ảnh chính
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

-- Chương trình khuyến mãi (Hỗ trợ nhiều loại: giảm giá, mua kèm, tặng quà, combo)
CREATE TABLE promotions (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(250) NOT NULL,
  description TEXT,                             -- Mô tả chi tiết
  
  promo_type ENUM(
    'discount',      -- Giảm giá thường (% hoặc cố định)
    'bundle',        -- Mua kèm: mua N sản phẩm với giá bundle  
    'gift',          -- Tặng quà: mua X → tặng Y
    'combo'          -- Combo: mua sản phẩm A + B = giá combo
  ) NOT NULL DEFAULT 'discount',
  
  -- Dùng cho promo_type = 'discount'
  discount_type ENUM('percentage','fixed') NULL,
  discount_value DECIMAL(12,2) NULL,
  
  -- Dùng cho promo_type = 'combo'
  combo_price DECIMAL(12,2) NULL COMMENT 'Giá combo (cho promo_type = combo)',
  
  -- Áp dụng cho (chỉ dùng cho promo_type = 'discount')
  apply_to ENUM('all','category','product') NULL DEFAULT 'all',
  
  priority INT NOT NULL DEFAULT 0,              -- Độ ưu tiên
  starts_at DATETIME NOT NULL,                  -- Ngày bắt đầu
  ends_at DATETIME NOT NULL,                    -- Ngày kết thúc
  is_active BOOLEAN NOT NULL DEFAULT TRUE,      -- Trạng thái
  
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  created_by BIGINT NULL,
  updated_by BIGINT NULL,
  CONSTRAINT fk_promotions_created_by FOREIGN KEY(created_by) REFERENCES users(id),
  CONSTRAINT fk_promotions_updated_by FOREIGN KEY(updated_by) REFERENCES users(id),
  CHECK (ends_at > starts_at)
) ENGINE=InnoDB;

-- Mối quan hệ giữa khuyến mãi và sản phẩm
CREATE TABLE promotion_products ( 
  promotion_id BIGINT NOT NULL, -- Mã khuyến mãi
  product_id BIGINT NOT NULL, -- Mã sản phẩm
  PRIMARY KEY (promotion_id, product_id),
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  created_by BIGINT NULL,
  updated_by BIGINT NULL,
  CONSTRAINT fk_ppromo_created_by FOREIGN KEY(created_by) REFERENCES users(id),
  CONSTRAINT fk_ppromo_updated_by FOREIGN KEY(updated_by) REFERENCES users(id),
  CONSTRAINT fk_ppromo_p FOREIGN KEY(promotion_id) REFERENCES promotions(id) ON DELETE CASCADE,
  CONSTRAINT fk_ppromo_prod FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- QUY TẮC Mua kèm: mua "required_qty" món => tổng giá = "bundle_price"
-- Ví dụ:
--   - Nước giặt: required_qty=2, bundle_price=165000  (1 bịch lẻ 130k)
--   - Khăn giấy: required_qty=3, bundle_price=29000   (1 bịch lẻ 12k)
CREATE TABLE promotion_bundle_rules (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  promotion_id BIGINT NOT NULL,
  product_id   BIGINT NOT NULL,
  required_qty INT NOT NULL CHECK (required_qty > 1),
  bundle_price DECIMAL(12,2) NOT NULL CHECK (bundle_price >= 0),
  max_cycles_per_order INT NULL,  -- giới hạn số lần lặp bundle/đơn (NULL = không giới hạn)
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  created_by BIGINT NULL,
  updated_by BIGINT NULL,
  CONSTRAINT fk_pbr_created_by FOREIGN KEY(created_by) REFERENCES users(id),
  CONSTRAINT fk_pbr_updated_by FOREIGN KEY(updated_by) REFERENCES users(id),
  CONSTRAINT fk_pbr_promo  FOREIGN KEY(promotion_id) REFERENCES promotions(id) ON DELETE CASCADE,
  CONSTRAINT fk_pbr_prod   FOREIGN KEY(product_id)   REFERENCES products(id)   ON DELETE CASCADE,
  UNIQUE KEY uniq_pbr (promotion_id, product_id, required_qty)
) ENGINE=InnoDB;

-- QUY TẮC QUÀ TẶNG: mua "required_qty" của trigger_product_id => tặng gift_product_id với số lượng gift_qty
-- Ví dụ:
--  - Mua 3 hộp cà phê => tặng 1 ly giữ nhiệt
--  - Mua 1 thùng sữa Milo => tặng 1 balo
CREATE TABLE promotion_gift_rules (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  promotion_id BIGINT NOT NULL,
  trigger_product_id BIGINT NOT NULL,          -- sản phẩm kích hoạt quà
  required_qty INT NOT NULL CHECK (required_qty > 0),
  gift_product_id BIGINT NOT NULL,             -- sản phẩm quà (cũng là product để quản lý tồn kho)
  gift_qty INT NOT NULL CHECK (gift_qty > 0),
  max_gifts_per_order INT NULL,                -- giới hạn số "bộ quà" trong 1 đơn (NULL = không giới hạn)
  auto_add BOOLEAN NOT NULL DEFAULT TRUE,      -- tự động thêm quà vào giỏ/đơn khi đủ điều kiện
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  created_by BIGINT NULL,
  updated_by BIGINT NULL,
  CONSTRAINT fk_pgr_created_by FOREIGN KEY(created_by) REFERENCES users(id),
  CONSTRAINT fk_pgr_updated_by FOREIGN KEY(updated_by) REFERENCES users(id),
  CONSTRAINT fk_pgr_promo   FOREIGN KEY(promotion_id)      REFERENCES promotions(id) ON DELETE CASCADE,
  CONSTRAINT fk_pgr_trigger FOREIGN KEY(trigger_product_id) REFERENCES products(id)   ON DELETE CASCADE,
  CONSTRAINT fk_pgr_gift    FOREIGN KEY(gift_product_id)   REFERENCES products(id)   ON DELETE CASCADE,
  UNIQUE KEY uniq_pgr (promotion_id, trigger_product_id, required_qty, gift_product_id)
) ENGINE=InnoDB;

-- Chi tiết sản phẩm trong combo
-- Lưu các sản phẩm tham gia combo và số lượng yêu cầu
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

/*----------------------------------------------------------------------
  Mã giảm giá (Người dùng tự nhập mã - Áp dụng cho cả đơn hàng)
    - Gắn MÃ cho người dùng (assigned)
    - Mỗi người chỉ dùng 1 lần (per_user_limit = 1)
    - Giới hạn tổng số lần dùng của mã (max_uses)
    - Log lần dùng theo đơn hàng
----------------------------------------------------------------------*/
CREATE TABLE coupons (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  code VARCHAR(64) NOT NULL UNIQUE, 
  name VARCHAR(250),
  discount_type ENUM('Phần trăm','Số tiền') NOT NULL,
  discount_value DECIMAL(12,2) NOT NULL, -- Giá trị giảm
  max_uses INT DEFAULT 0,           -- 0 = không giới hạn
  used_count INT NOT NULL DEFAULT 0, -- Số lần sử dụng
  starts_at DATETIME NOT NULL, -- Ngày bắt đầu áp dụng
  ends_at DATETIME NOT NULL, -- Ngày kết thúc áp dụng
  min_order_value DECIMAL(12,2) DEFAULT 0, -- Giá trị đơn hàng tối thiểu để áp dụng
  is_active BOOLEAN NOT NULL DEFAULT TRUE, -- Đang hoạt động
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  created_by BIGINT NULL,
  updated_by BIGINT NULL,
  CONSTRAINT fk_coupons_created_by FOREIGN KEY(created_by) REFERENCES users(id),
  CONSTRAINT fk_coupons_updated_by FOREIGN KEY(updated_by) REFERENCES users(id),
  CHECK (ends_at > starts_at)
) ENGINE=InnoDB;

-- Bảng liên kết NGƯỜI DÙNG <-> MÃ GIẢM GIÁ
-- Lưu trạng thái sở hữu mã, theo dõi đã dùng chưa cho từng user.
CREATE TABLE user_coupons (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  user_id   BIGINT NOT NULL,
  coupon_id BIGINT NOT NULL,
  status ENUM('Được cấp','Đã sử dụng','Hết hạn') NOT NULL DEFAULT 'Được cấp',
  assigned_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  used_at DATETIME NULL,
  expires_at DATETIME NULL,  -- hạn dùng riêng theo người (tuỳ chọn)
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  created_by BIGINT NULL,
  updated_by BIGINT NULL,
  CONSTRAINT fk_uc_created_by FOREIGN KEY(created_by) REFERENCES users(id),
  CONSTRAINT fk_uc_updated_by FOREIGN KEY(updated_by) REFERENCES users(id),
  CONSTRAINT fk_uc_user   FOREIGN KEY(user_id)   REFERENCES users(id)   ON DELETE CASCADE,
  CONSTRAINT fk_uc_coupon FOREIGN KEY(coupon_id) REFERENCES coupons(id) ON DELETE CASCADE,
  UNIQUE KEY uniq_user_coupon (user_id, coupon_id),   -- 1 mã chỉ xuất hiện 1 lần trên 1 user
  INDEX idx_uc_status (status)
) ENGINE=InnoDB;

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
  UNIQUE KEY uniq_cart_user (user_id) -- mỗi user 1 giỏ mở
) ENGINE=InnoDB;

-- Mặt hàng trong giỏ hàng
CREATE TABLE cart_items (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  cart_id BIGINT NOT NULL, -- Giỏ hàng
  product_id BIGINT NOT NULL, -- Sản phẩm
  qty INT NOT NULL CHECK (qty > 0), -- Số lượng
  price DECIMAL(12,2) NOT NULL CHECK (price >= 0),  -- snapshot giá lúc thêm
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

-- Thanh toán (1 đơn = 1 payment; orders.payment_id -> payments.id)
CREATE TABLE payments (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  amount DECIMAL(12,2) NOT NULL CHECK (amount >= 0),

  -- Phương thức thanh toán
  method ENUM(
    'Tiền mặt','Chuyển khoản','Quẹt thẻ',
    'PayPal','Thanh toán khi nhận hàng (COD)'
  ) NOT NULL,

  txn_ref VARCHAR(250),      -- mã giao dịch từ cổng/nhà cung cấp (nếu có)
  paid_at DATETIME NULL,     -- thời gian thanh toán (nếu có)
  meta JSON,                 -- lưu trữ thông tin bổ sung
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Đơn hàng
CREATE TABLE orders (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  code VARCHAR(32) NOT NULL UNIQUE,                      -- mã đơn hiển thị
  user_id BIGINT NULL,                                   -- NULL với đơn tại quầy (khách vãng lai)
  order_type ENUM('Online','Offline') NOT NULL DEFAULT 'Online',

  -- Trạng thái đơn (rút gọn)
  status ENUM('Chờ xử lý','Đang xử lý','Hoàn tất','Đã hủy') NOT NULL DEFAULT 'Chờ xử lý',

  -- Tiền
  subtotal DECIMAL(12,2) NOT NULL DEFAULT 0,             -- tổng trước giảm
  promotion_discount DECIMAL(12,2) NOT NULL DEFAULT 0,   -- giảm giá từ chương trình khuyến mãi tự động
  discount_total DECIMAL(12,2) NOT NULL DEFAULT 0,       -- tổng giảm
  shipping_fee DECIMAL(12,2) NOT NULL DEFAULT 0,         -- phí vận chuyển
  cod_amount   DECIMAL(12,2) NOT NULL DEFAULT 0,         -- số tiền COD cần thu (0 nếu trả trước)
   grand_total  DECIMAL(12,2) NOT NULL DEFAULT 0,         -- tổng sau giảm + phí + thuế

  coupon_code VARCHAR(64) NULL,                          -- mã giảm giá (nếu có)

  -- Phương thức & trạng thái thanh toán
  payment_method ENUM(
    -- Offline tại quầy:
    'Tiền mặt','Chuyển khoản','Quẹt thẻ',
    -- Online:
    'PayPal','Thanh toán khi nhận hàng (COD)'
  ) NOT NULL,

  payment_status ENUM('Chưa thanh toán','Đã thanh toán','Hoàn tiền')
                NOT NULL DEFAULT 'Chưa thanh toán',

  -- Tham chiếu tới bản ghi thanh toán duy nhất của đơn
  payment_id BIGINT NULL,

  -- Địa chỉ giao (NULL với Offline)
  shipping_address_id BIGINT NULL,
  note VARCHAR(255),
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  created_by BIGINT NULL,
  updated_by BIGINT NULL,
  CONSTRAINT fk_order_created_by FOREIGN KEY(created_by) REFERENCES users(id),
  CONSTRAINT fk_order_updated_by FOREIGN KEY(updated_by) REFERENCES users(id),

  CONSTRAINT fk_order_user     FOREIGN KEY(user_id)            REFERENCES users(id)           ON DELETE SET NULL,
  CONSTRAINT fk_order_shipaddr FOREIGN KEY(shipping_address_id) REFERENCES user_addresses(id) ON DELETE SET NULL,
  CONSTRAINT fk_order_payment    FOREIGN KEY(payment_id)          REFERENCES payments(id)       ON DELETE SET NULL,

  -- RÀNG BUỘC: phương thức thanh toán hợp lệ theo loại đơn
  CONSTRAINT chk_order_payment_method_by_type CHECK (
    (order_type = 'Offline' AND payment_method IN ('Tiền mặt','Chuyển khoản','Quẹt thẻ'))
    OR
    (order_type = 'Online'  AND payment_method IN ('PayPal','Thanh toán khi nhận hàng (COD)'))
  ),

  -- Index hay dùng
  INDEX idx_orders_user      (user_id),
  INDEX idx_orders_status    (status),
  INDEX idx_orders_createdat (created_at),
  INDEX idx_orders_payment   (payment_id)
) ENGINE=InnoDB;

-- Mặt hàng trong đơn hàng
CREATE TABLE order_items (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  order_id BIGINT NOT NULL,
  product_id BIGINT NOT NULL,
  qty INT NOT NULL CHECK (qty > 0),
  unit_price DECIMAL(12,2) NOT NULL CHECK (unit_price >= 0),
  discount   DECIMAL(12,2) NOT NULL DEFAULT 0,
  tax        DECIMAL(12,2) NOT NULL DEFAULT 0,
  line_total DECIMAL(12,2) NOT NULL DEFAULT 0,  -- có thể = (qty*unit_price - discount + tax) ở tầng ứng dụng
  is_gift BOOLEAN NOT NULL DEFAULT FALSE,        -- đánh dấu dòng quà
  promotion_id BIGINT NULL,                      -- khuyến mãi tạo ra quà (nếu có)

  CONSTRAINT fk_oit_order FOREIGN KEY(order_id)  REFERENCES orders(id)   ON DELETE CASCADE,
  CONSTRAINT fk_oit_prod  FOREIGN KEY(product_id) REFERENCES products(id),

  INDEX idx_oit_order   (order_id),
  INDEX idx_oit_prod    (product_id),
  INDEX idx_oit_isgift  (is_gift),
  INDEX idx_oit_promo   (promotion_id)
) ENGINE=InnoDB;

-- Log mỗi lần áp mã vào đơn (để audit/báo cáo, giúp kiểm soát max_uses)
CREATE TABLE coupon_redemptions (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  coupon_id BIGINT NOT NULL,
  user_id   BIGINT NOT NULL,
  order_id  BIGINT NOT NULL,
  discount_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  created_by BIGINT NULL,
  updated_by BIGINT NULL,
  CONSTRAINT fk_cr_created_by FOREIGN KEY(created_by) REFERENCES users(id),
  CONSTRAINT fk_cr_updated_by FOREIGN KEY(updated_by) REFERENCES users(id),
  CONSTRAINT fk_cr_coupon FOREIGN KEY(coupon_id) REFERENCES coupons(id) ON DELETE CASCADE,
  CONSTRAINT fk_cr_user   FOREIGN KEY(user_id)   REFERENCES users(id),
  CONSTRAINT fk_cr_order  FOREIGN KEY(order_id)  REFERENCES orders(id) ON DELETE CASCADE,
  UNIQUE KEY uniq_cr (coupon_id, user_id, order_id),  -- 1 đơn chỉ ghi nhận 1 lần cho cùng coupon-user
  INDEX idx_cr_coupon (coupon_id),
  INDEX idx_cr_user   (user_id),
  INDEX idx_cr_order  (order_id)
) ENGINE=InnoDB;

-- Đánh giá sản phẩm
CREATE TABLE product_reviews (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  order_id BIGINT NULL,           -- chỉ đánh giá sau mua hàng
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

-- Phiếu thu: nhận tiền từ khách (theo đơn hoặc thu rời)
CREATE TABLE receipt_vouchers (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  code VARCHAR(32) NOT NULL UNIQUE,  -- mã phiếu thu hiển thị
  order_id BIGINT NULL,              -- đơn hàng liên quan (nếu có)
  payment_id BIGINT NULL,            -- bản ghi thanh toán (nếu đã tạo payment)
  payer_user_id BIGINT NULL,         -- KH nội bộ (nếu có user)
  payer_name VARCHAR(250) NULL,  
  txn_ref VARCHAR(250) NULL, -- mã giao dịch từ cổng/nhà cung cấp (nếu có)
  bank_time DATETIME NULL,  -- thời gian giao dịch bên ngân hàng (nếu có)
  method ENUM(
    'Tiền mặt','Chuyển khoản','Quẹt thẻ',
    'PayPal','Thanh toán khi nhận hàng (COD)'
  ) NOT NULL,

  amount DECIMAL(12,2) NOT NULL CHECK (amount >= 0),

  received_by BIGINT NULL,           -- nhân viên thu (users.id)
  received_at DATETIME NULL,         -- thời điểm nhận
  note VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  created_by BIGINT NULL,
  CONSTRAINT fk_rv_created_by FOREIGN KEY(created_by) REFERENCES users(id),
  CONSTRAINT fk_rv_order     FOREIGN KEY(order_id)     REFERENCES orders(id)    ON DELETE SET NULL,
  CONSTRAINT fk_rv_payment   FOREIGN KEY(payment_id)   REFERENCES payments(id)  ON DELETE SET NULL,
  CONSTRAINT fk_rv_payer     FOREIGN KEY(payer_user_id) REFERENCES users(id)    ON DELETE SET NULL,
  CONSTRAINT fk_rv_receiver  FOREIGN KEY(received_by)  REFERENCES users(id)     ON DELETE SET NULL,

  INDEX idx_rv_order    (order_id),
  INDEX idx_rv_payment  (payment_id),
  INDEX idx_rv_payer    (payer_user_id),
  INDEX idx_rv_received (received_at)
) ENGINE=InnoDB;

-- =====================================================================
-- 6) WAREHOUSE / INVENTORY / PURCHASE / STOCKTAKE
-- =====================================================================

-- Tồn kho
CREATE TABLE stocks (
  product_id BIGINT PRIMARY KEY,              -- Mã sản phẩm 
  qty INT NOT NULL DEFAULT 0,                 -- tồn hiện tại
  safety_stock INT NOT NULL DEFAULT 10,       -- tồn an toàn (mặc định 10)
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  updated_by BIGINT NULL,
  CONSTRAINT fk_st_updated_by FOREIGN KEY(updated_by) REFERENCES users(id),
  CONSTRAINT fk_st_prod FOREIGN KEY(product_id) REFERENCES products(id)
) ENGINE=InnoDB;

-- Lịch sử thay đổi tồn kho
CREATE TABLE stock_movements (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  product_id BIGINT NOT NULL, -- Mã sản phẩm
  type ENUM('Nhập kho','Xuất kho','Điều chỉnh','Bán hàng','Trả hàng') NOT NULL,  
    -- Nhập kho → Hàng về (từ nhà cung cấp).
    -- Xuất kho → Hàng ra (hủy, hỏng, xuất bán, …).
    -- Điều chỉnh → Điều chỉnh tồn kho khi kiểm kê thấy lệch.
    -- Bán hàng → Giảm tồn kho khi bán cho khách.
    -- Trả hàng → Hàng khách trả lại, nhập lại vào kho.

  ref_type ENUM('Phiếu nhập','Đơn hàng','Kiểm kê','Thủ công') NOT NULL,
    -- Phiếu nhập → Liên quan chứng từ nhập hàng từ nhà cung cấp.
    -- Đơn hàng → Liên quan đơn bán hàng.
    -- Kiểm kê → Liên quan phiếu kiểm kê kho.
    -- Thủ công → Nhân viên kho tự điều chỉnh

  ref_id BIGINT NULL,  -- ID chứng từ tham chiếu (vd: purchase_orders.id / orders.id / stocktakes.id)
  qty INT NOT NULL,    -- số lượng thay đổi (+/-); hoặc quy ước >0 và dùng type xác định chiều
  note VARCHAR(255),
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_sm_prod FOREIGN KEY(product_id) REFERENCES products(id)
) ENGINE=InnoDB;

-- Nhà cung cấp
CREATE TABLE suppliers (
  id BIGINT PRIMARY KEY AUTO_INCREMENT, -- Mã nhà cung cấp
  name VARCHAR(250) NOT NULL, -- Tên nhà cung cấp
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

-- Liên kết Nhà cung cấp <-> Sản phẩm (nhiều-nhiều)
CREATE TABLE supplier_products (
  supplier_id BIGINT NOT NULL,
  product_id  BIGINT NOT NULL,

  supplier_sku VARCHAR(64) NULL,                 -- Mã SP theo NCC
  default_cost DECIMAL(12,2) NULL,               -- Giá nhập mặc định (nếu có)
  moq INT NOT NULL DEFAULT 1,                    -- Số lượng tối thiểu mỗi lần đặt
  lead_time_days INT NOT NULL DEFAULT 0,         -- Số ngày giao dự kiến
  preference_score INT NOT NULL DEFAULT 100,     -- Điểm ưu tiên (nhỏ hơn = ưu tiên hơn)
  is_active BOOLEAN NOT NULL DEFAULT TRUE,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
              ON UPDATE CURRENT_TIMESTAMP,
  created_by BIGINT NULL,

  CONSTRAINT fk_supplier_products_created_by FOREIGN KEY(created_by) REFERENCES users(id),

  PRIMARY KEY (supplier_id, product_id),
  CONSTRAINT fk_supplier_products_supplier FOREIGN KEY(supplier_id) REFERENCES suppliers(id) ON DELETE CASCADE,
  CONSTRAINT fk_supplier_products_product  FOREIGN KEY(product_id)  REFERENCES products(id)  ON DELETE CASCADE,

  INDEX idx_sp_prod_pref (product_id, preference_score),
  INDEX idx_sp_supplier  (supplier_id)
) ENGINE=InnoDB;


-- Phiếu nhập kho
CREATE TABLE purchase_orders (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,                     -- Mã phiếu nhập
  code VARCHAR(32) NOT NULL UNIQUE,                         -- Mã phiếu nhập hiển thị
  supplier_id BIGINT NOT NULL,                              -- Nhà cung cấp
  total_amount DECIMAL(12,2) NOT NULL DEFAULT 0,            -- Tổng tiền phiếu
  paid_amount  DECIMAL(12,2) NOT NULL DEFAULT 0,              -- đã thanh toán
  payment_status ENUM(
    'Chưa đối soát',
    'Đã thanh toán một phần',
    'Đã thanh toán hết')
    NOT NULL DEFAULT 'Chưa đối soát',
  due_date DATE NULL,          -- hạn thanh toán (tự set theo payment_term_days)
  note VARCHAR(255) NULL,      -- Ghi chú (tùy chọn)
  received_at DATETIME NULL,   -- Ngày nhận hàng thực tế (tùy chọn)
  created_by BIGINT NOT NULL,  -- Người tạo phiếu
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  updated_by BIGINT NULL,
  
  CONSTRAINT fk_po_updated_by FOREIGN KEY(updated_by) REFERENCES users(id),
  CONSTRAINT fk_po_sup   FOREIGN KEY(supplier_id) REFERENCES suppliers(id),
  CONSTRAINT fk_po_user  FOREIGN KEY(created_by)  REFERENCES users(id),

  INDEX idx_po_supplier   (supplier_id),
  INDEX idx_po_created_by (created_by),
  INDEX idx_po_code       (code),
  INDEX idx_po_created_at (created_at),
  INDEX idx_po_payment    (payment_status),
  INDEX idx_po_due        (due_date)
) ENGINE=InnoDB;


-- Bảng quản lý lô sản phẩm và hạn sử dụng
CREATE TABLE product_batches (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  product_id BIGINT NOT NULL,              -- FK -> products.id
  batch_code VARCHAR(64) NOT NULL,         -- mã lô (từ NCC hoặc tự tạo)
  mfg_date DATE NULL,                      -- ngày sản xuất (tùy chọn)
  exp_date DATE NOT NULL,                  -- ngày hết hạn
  initial_qty INT NOT NULL CHECK (initial_qty >= 0),  -- SL nhập ban đầu
  current_qty INT NOT NULL DEFAULT 0,                  -- SL còn lại
  purchase_order_id BIGINT NULL,           -- phiếu nhập liên quan
  note VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  created_by BIGINT NULL,
  updated_by BIGINT NULL,

  CONSTRAINT fk_pb_prod FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE CASCADE,
  CONSTRAINT fk_pb_po   FOREIGN KEY(purchase_order_id) REFERENCES purchase_orders(id) ON DELETE SET NULL,
  CONSTRAINT fk_pb_user FOREIGN KEY(created_by) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_pb_user_updated FOREIGN KEY(updated_by) REFERENCES users(id) ON DELETE SET NULL,

  UNIQUE KEY uniq_prod_batch (product_id, batch_code),
  INDEX idx_pb_prod (product_id),
  INDEX idx_pb_exp  (exp_date)
) ENGINE=InnoDB;

-- Chi tiết phiếu nhập kho
CREATE TABLE purchase_order_items (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  purchase_order_id BIGINT NOT NULL, -- Mã phiếu nhập
  product_id BIGINT NOT NULL, -- Mã sản phẩm
  qty INT NOT NULL CHECK (qty > 0), -- Số lượng
  unit_cost DECIMAL(12,2) NOT NULL CHECK (unit_cost >= 0), -- Giá đơn vị
  line_total DECIMAL(12,2) NOT NULL CHECK (line_total >= 0), -- Thành tiền

  CONSTRAINT fk_poi_po   FOREIGN KEY(purchase_order_id) REFERENCES purchase_orders(id) ON DELETE CASCADE,
  CONSTRAINT fk_poi_prod FOREIGN KEY(product_id)        REFERENCES products(id),

  INDEX idx_poi_po     (purchase_order_id),
  INDEX idx_poi_prod   (product_id)
) ENGINE=InnoDB;

-- Phiếu chi: chi tiền cho nhà cung cấp
CREATE TABLE expense_vouchers (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  code VARCHAR(32) NOT NULL UNIQUE,      -- mã phiếu chi hiển thị
  purchase_order_id BIGINT NOT NULL,         -- phiếu nhập liên quan
  supplier_id BIGINT NULL,               -- nhà cung cấp (nếu chi rời/hoặc trùng với phiếu nhập)
  method ENUM('Tiền mặt','Chuyển khoản') NOT NULL,
  txn_ref VARCHAR(250) NULL,                -- mã giao dịch ngân hàng (nếu có)
  amount DECIMAL(12,2) NOT NULL CHECK (amount >= 0),
  paid_by BIGINT NULL,                   -- nhân viên chi (users.id)
  paid_at DATETIME NULL,                 -- thời điểm chi
  bank_time DATETIME NULL,               -- thời điểm ngân hàng xác nhận (nếu có)
  note VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  created_by BIGINT NULL,
  CONSTRAINT fk_ev_created_by FOREIGN KEY(created_by) REFERENCES users(id),

  CONSTRAINT fk_ev_po      FOREIGN KEY(purchase_order_id) REFERENCES purchase_orders(id) ON DELETE RESTRICT,
  CONSTRAINT fk_ev_sup     FOREIGN KEY(supplier_id)       REFERENCES suppliers(id)       ON DELETE SET NULL,
  CONSTRAINT fk_ev_paid_by FOREIGN KEY(paid_by)           REFERENCES users(id)           ON DELETE SET NULL,

  INDEX idx_ev_po    (purchase_order_id),
  INDEX idx_ev_sup   (supplier_id),
  INDEX idx_ev_paid  (paid_at)
) ENGINE=InnoDB;

-- Công nợ nhà cung cấp
CREATE TABLE ap_ledger (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  supplier_id BIGINT NOT NULL,
  ref_type ENUM('Phiếu nhập','Phiếu chi','Điều chỉnh') NOT NULL,
  ref_id BIGINT NULL,
  debit  DECIMAL(14,2) NOT NULL DEFAULT 0,  -- tăng công nợ
  credit DECIMAL(14,2) NOT NULL DEFAULT 0,  -- giảm công nợ
  note VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  created_by BIGINT NULL,
  CONSTRAINT fk_ap_created_by FOREIGN KEY(created_by) REFERENCES users(id),
  CONSTRAINT fk_ap_sup FOREIGN KEY(supplier_id) REFERENCES suppliers(id) ON DELETE CASCADE,
  INDEX idx_ap_sup   (supplier_id),
  INDEX idx_ap_reft  (ref_type),
  INDEX idx_ap_refid (ref_id),
  INDEX idx_ap_time  (created_at),
  CHECK (debit >= 0 AND credit >= 0)
) ENGINE=InnoDB;

-- Kiểm kê kho
CREATE TABLE stocktakes (
  id BIGINT PRIMARY KEY AUTO_INCREMENT, -- Mã kiểm kê
  note VARCHAR(255), -- Ghi chú kiểm kê
  created_by BIGINT NOT NULL, -- Mã người tạo kiểm kê
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_stt_by FOREIGN KEY(created_by) REFERENCES users(id)
) ENGINE=InnoDB;

-- Chi tiết kiểm kê kho
CREATE TABLE stocktake_items (
  id BIGINT PRIMARY KEY AUTO_INCREMENT, -- Mã chi tiết kiểm kê
  stocktake_id BIGINT NOT NULL, -- Mã kiểm kê
  product_id BIGINT NOT NULL, -- Mã sản phẩm
  system_qty INT NOT NULL, -- Tồn kho hiện tại
  counted_qty INT NOT NULL, -- Tồn kho kiểm kê
  difference INT NOT NULL, -- Số lượng khác biệt = system_qty - counted_qty
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  created_by BIGINT NULL,
  CONSTRAINT fk_stti_stt FOREIGN KEY(stocktake_id) REFERENCES stocktakes(id) ON DELETE CASCADE,
  CONSTRAINT fk_stti_prod FOREIGN KEY(product_id)   REFERENCES products(id),
  CONSTRAINT fk_stti_created_by FOREIGN KEY(created_by) REFERENCES users(id)
) ENGINE=InnoDB;

-- Phiếu xuất kho 
CREATE TABLE stock_outs (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  code VARCHAR(32) NOT NULL UNIQUE,      -- Mã phiếu xuất (XK20241015-00001)
  type ENUM('sale','return','damage','other') NOT NULL DEFAULT 'sale',  -- Loại xuất: bán hàng, trả NCC, hư hỏng, khác
  order_id BIGINT NULL,                   -- Đơn hàng liên quan (nếu xuất bán)
  status ENUM('pending','approved','completed','cancelled') NOT NULL DEFAULT 'pending',
  out_date DATETIME NOT NULL,             -- Ngày xuất kho
  total_amount DECIMAL(14,2) NOT NULL DEFAULT 0,  -- Tổng giá trị xuất
  note VARCHAR(500) NULL,                 -- Ghi chú
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  created_by BIGINT NULL,
  updated_by BIGINT NULL,
  CONSTRAINT fk_so_order FOREIGN KEY(order_id) REFERENCES orders(id) ON DELETE SET NULL,
  CONSTRAINT fk_so_created_by FOREIGN KEY(created_by) REFERENCES users(id),
  CONSTRAINT fk_so_updated_by FOREIGN KEY(updated_by) REFERENCES users(id),
  INDEX idx_so_type(type),
  INDEX idx_so_status(status),
  INDEX idx_so_date(out_date),
  INDEX idx_so_order(order_id)
) ENGINE=InnoDB;

-- Chi tiết phiếu xuất kho
CREATE TABLE stock_out_items (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  stock_out_id BIGINT NOT NULL,           -- Mã phiếu xuất
  product_id BIGINT NOT NULL,             -- Mã sản phẩm
  batch_id BIGINT NULL,                   -- Mã lô (nếu có)
  qty INT NOT NULL CHECK (qty > 0),      -- Số lượng xuất
  unit_price DECIMAL(12,2) NOT NULL DEFAULT 0,  -- Đơn giá
  total_price DECIMAL(14,2) NOT NULL DEFAULT 0, -- Thành tiền = qty * unit_price
  note VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_soi_so FOREIGN KEY(stock_out_id) REFERENCES stock_outs(id) ON DELETE CASCADE,
  CONSTRAINT fk_soi_prod FOREIGN KEY(product_id) REFERENCES products(id),
  CONSTRAINT fk_soi_batch FOREIGN KEY(batch_id) REFERENCES product_batches(id) ON DELETE SET NULL,
  INDEX idx_soi_so(stock_out_id),
  INDEX idx_soi_prod(product_id)
) ENGINE=InnoDB;

-- =====================================================================
-- 7) Giao hàng
-- =====================================================================

-- Thông tin đơn hàng
CREATE TABLE shipments (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  order_id BIGINT NOT NULL,
  mode ENUM('third_party') NOT NULL DEFAULT 'third_party',
  carrier VARCHAR(50) NOT NULL,               -- GHN, GHTK, VTP...
  package_no INT NOT NULL DEFAULT 1,          -- số kiện (1 nếu 1 vận đơn)
  service_type_id TINYINT NULL,               -- ví dụ GHN: 1/2
  service_code VARCHAR(30) NULL,
  tracking_code VARCHAR(100) NULL,
  carrier_order_code VARCHAR(100) NULL,
  tracking_url VARCHAR(255) NULL,
   -- Trạng thái để hiển thị & báo cáo
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
  pickup_time DATETIME NULL,                    -- lúc carrier lấy hàng
  handover_at DATETIME NULL,                    -- lúc bàn giao cho carrier (nếu khác pickup)
  last_synced_at DATETIME NULL,                 -- lần cuối đồng bộ webhook/poll

  meta JSON,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  created_by BIGINT NULL,
  CONSTRAINT fk_ship_created_by FOREIGN KEY(created_by) REFERENCES users(id),
  UNIQUE KEY uniq_ship (order_id, package_no),
  UNIQUE KEY uniq_tracking (tracking_code),     -- cho nhanh tra cứu; cho phép NULL
  INDEX idx_ship_order   (order_id),
  INDEX idx_ship_status  (status),
  INDEX idx_ship_created (created_at),
  INDEX idx_ship_updated (updated_at),
  INDEX idx_ship_track   (tracking_code),
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

  detail VARCHAR(255),          -- mô tả ngắn
  location VARCHAR(250) NULL,   -- kiện hàng đang ở đâu (tên + mã)
  hub_code VARCHAR(64) NULL,    -- mã hub

  event_time DATETIME NOT NULL, -- thời điểm sự kiện
  raw_payload JSON,             -- chứng cứ gốc đầy đủ từ carrier để kiểm tra/bắt lỗi/bổ sung mapping sau này

  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, 
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  created_by BIGINT NULL,
  CONSTRAINT fk_sev_created_by FOREIGN KEY(created_by) REFERENCES users(id),

  INDEX idx_sev_shipment (shipment_id),
  INDEX idx_sev_track    (tracking_code),
  INDEX idx_sev_event_at (event_time),
  INDEX idx_sev_mapped   (mapped_status),

  CONSTRAINT fk_sev_shipment FOREIGN KEY (shipment_id) REFERENCES shipments(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================================
-- 8) DỊCH VỤ KHÁCH HÀNG
-- =====================================================================

-- Yêu cầu hậu mãi (gộp khiếu nại / trả hàng / hủy đơn)
CREATE TABLE aftersales_requests (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  order_id BIGINT NOT NULL,                 -- đơn hàng liên quan
  user_id BIGINT NOT NULL,                  -- người gửi yêu cầu
  request_type ENUM('Khiếu nại','Trả hàng','Hủy đơn') NOT NULL,

  -- lý do ngắn (chuẩn hóa) + mô tả tự do
  reason ENUM('Hư hỏng','Hết hạn','Giao nhầm','Thiếu hàng','Khác') NOT NULL,
  description TEXT NULL,

  -- quy trình xử lý rút gọn, áp dụng cho mọi loại
  status ENUM('Mở','Đang xử lý','Chấp thuận','Từ chối','Hoàn tất')
         NOT NULL DEFAULT 'Mở',

  -- các trường dùng cho trường hợp Trả hàng/Hủy đơn (tùy loại mà dùng)
  refund_amount DECIMAL(12,2) NOT NULL DEFAULT 0,   -- tổng tiền hoàn (nếu có)
  approved_by BIGINT NULL,                          -- người duyệt
  resolved_at DATETIME NULL,                        -- thời điểm hoàn tất

  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  created_by BIGINT NULL,
  CONSTRAINT fk_afs_created_by FOREIGN KEY(created_by) REFERENCES users(id),

  CONSTRAINT fk_afs_order     FOREIGN KEY(order_id)  REFERENCES orders(id) ON DELETE CASCADE,
  CONSTRAINT fk_afs_user      FOREIGN KEY(user_id)   REFERENCES users(id),
  CONSTRAINT fk_afs_approved  FOREIGN KEY(approved_by) REFERENCES users(id),

  INDEX idx_afs_order (order_id),
  INDEX idx_afs_user  (user_id),
  INDEX idx_afs_status(status),
  INDEX idx_afs_type  (request_type)
) ENGINE=InnoDB;

-- Chi tiết hậu mãi theo dòng sản phẩm (dùng khi request_type='Trả hàng')
CREATE TABLE aftersales_items (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  request_id BIGINT NOT NULL,            -- FK -> aftersales_requests.id
  order_item_id BIGINT NOT NULL,         -- dòng SP trong đơn gốc
  qty INT NOT NULL CHECK (qty > 0),      -- SL khách trả
  refund_amount DECIMAL(12,2) NOT NULL DEFAULT 0,  -- tiền hoàn cho dòng này (nếu có)

  CONSTRAINT fk_afsi_req  FOREIGN KEY(request_id)   REFERENCES aftersales_requests(id) ON DELETE CASCADE,
  CONSTRAINT fk_afsi_oit  FOREIGN KEY(order_item_id) REFERENCES order_items(id),

  INDEX idx_afsi_req (request_id),
  INDEX idx_afsi_oit (order_item_id)
) ENGINE=InnoDB;


-- =====================================================================
-- 9) LƯU LỊCH SỬ CHỈNH SỬA
-- =====================================================================
CREATE TABLE audit_logs (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  actor_user_id BIGINT NULL, -- người thực hiện hành động (FK -> users.id)
  entity_type VARCHAR(64) NOT NULL, -- loại thực thể bị thay đổi (product, order, warehouse, ...)
  entity_id BIGINT NOT NULL, -- ID của thực thể bị thay đổi (FK -> entity_type.id)
  action VARCHAR(32) NOT NULL, -- hành động thực hiện (create, update, delete, status_change)
  before_data JSON, -- dữ liệu trước khi thay đổi (JSON)
  after_data JSON, -- dữ liệu sau khi thay đổi (JSON)
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_audit_entity (entity_type, entity_id),
  CONSTRAINT fk_audit_user FOREIGN KEY(actor_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =====================================================================
-- 10) ANALYTICS / RECOMMENDATION (MÁY HỌC)
--     (Implicit feedback + bảng kết quả gợi ý)
-- =====================================================================

-- Lịch sử tương tác (view, add, purchase)
CREATE TABLE events (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  user_id BIGINT NULL, -- người thực hiện hành động (FK -> users.id)
  session_id VARCHAR(64) NULL, -- ID phiên (dùng để theo dõi hành động liên tục)
  product_id BIGINT NOT NULL, -- sản phẩm liên quan (FK -> products.id)
  action ENUM('view','add','purchase') NOT NULL, -- hành động thực hiện (view, add, purchase)
  qty INT DEFAULT 1, -- số lượng (dùng cho add/purchase)
  ts DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, -- thời điểm xảy ra
  KEY (user_id), KEY (session_id), KEY (product_id), KEY (ts),
  CONSTRAINT fk_evt_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_evt_prod FOREIGN KEY(product_id) REFERENCES products(id)
) ENGINE=InnoDB;

-- Gợi ý
CREATE TABLE recommendations (
  user_id BIGINT NOT NULL,
  product_id BIGINT NOT NULL, -- sản phẩm gợi ý (FK -> products.id)
  score DOUBLE NOT NULL, -- điểm số (dùng để sắp xếp gợi ý)
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, 
  PRIMARY KEY (user_id, product_id),
  CONSTRAINT fk_rec_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_rec_prod FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Sản phẩm tương tự
CREATE TABLE similar_items (
  product_id BIGINT NOT NULL, -- sản phẩm gốc (FK -> products.id)
  similar_id BIGINT NOT NULL, -- sản phẩm tương tự (FK -> products.id)
  score DOUBLE NOT NULL, -- điểm số (dùng để sắp xếp tương tự)
  PRIMARY KEY (product_id, similar_id), 
  CONSTRAINT fk_sim_prod FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE CASCADE,
  CONSTRAINT fk_sim_sim FOREIGN KEY(similar_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================================
-- 11) CONFIG (CẤU HÌNH PHÍ SHIP, TÍCH HỢP 3RD)
-- =====================================================================

-- Zones (Nội thành, Ngoại thành, Liên tỉnh...)
CREATE TABLE shipping_zones (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL,           -- Nội thành, Ngoại thành, Liên tỉnh...
  base_fee DECIMAL(12,2) NOT NULL DEFAULT 0, -- phí ship cơ bản
  fee_per_km DECIMAL(12,2) NOT NULL DEFAULT 0, -- phí ship theo km
  cod_surcharge DECIMAL(12,2) NOT NULL DEFAULT 0, -- phí ship COD (nếu có)
  is_active BOOLEAN NOT NULL DEFAULT TRUE
) ENGINE=InnoDB;

-- Carrier Configs (Cấu hình carrier)
CREATE TABLE carrier_configs (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  carrier VARCHAR(50) NOT NULL UNIQUE,  -- GHN, GHTK, VTP, Grab...
  api_key VARCHAR(255) NOT NULL, -- API key của carrier
  api_secret VARCHAR(255) NULL, -- API secret của carrier
  sandbox BOOLEAN NOT NULL DEFAULT TRUE, -- môi trường sandbox (true: dev, false: prod)
  webhook_secret VARCHAR(255) NULL, -- secret key để xác thực webhook (nếu có)
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================================
-- 12) INDEXES (THÊM CHỖ QUAN TRỌNG)
-- =====================================================================
CREATE INDEX idx_prod_name ON products(name);
CREATE INDEX idx_prod_cat ON products(category_id);


INSERT INTO users (role_id, username, email, phone, password_hash, full_name, is_active)
VALUES (
    2,                    -- admin (Chủ cửa hàng)
    'admin',              -- ĐĂNG NHẬP BẰNG CÁI NÀY
    'thicamtien2003@gmail.com',
    '0909000000',
    '$2b$10$b0.RGLmD391S.468j6b5FuoaBSv7OZKVT9/hqDR75Qlf2OzR/egxC',
    'Administrator',
    TRUE
);

INSERT INTO staff_profiles (user_id, staff_role, hired_at, note)
SELECT id, 'Admin', CURDATE(), 'Tài khoản admin mặc định'
FROM users WHERE username = 'admin';

ALTER TABLE users ADD COLUMN force_change_password BOOLEAN NOT NULL DEFAULT TRUE AFTER password_hash;

ALTER TABLE users ADD COLUMN is_deleted BOOLEAN NOT NULL DEFAULT FALSE AFTER is_active;

ALTER TABLE users MODIFY email VARCHAR(250) NOT NULL UNIQUE;

ALTER TABLE product_batches ADD COLUMN unit_cost DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER current_qty;

ALTER TABLE stock_movements ADD COLUMN unit_cost DECIMAL(12,2) NULL AFTER qty;

ALTER TABLE order_items ADD COLUMN unit_cost DECIMAL(12,2) NULL AFTER unit_price;
ALTER TABLE order_items ADD COLUMN line_cogs DECIMAL(14,2) NULL AFTER line_total;

ALTER TABLE product_batches
  ADD COLUMN is_active BOOLEAN NOT NULL DEFAULT TRUE AFTER note;

ALTER TABLE purchase_order_items 
  ADD COLUMN batch_code VARCHAR(64) NULL AFTER product_id,
  ADD COLUMN mfg_date DATE NULL AFTER line_total,
  ADD COLUMN exp_date DATE NULL AFTER mfg_date;

  ALTER TABLE promotions
  DROP COLUMN IF EXISTS promo_type,
  DROP COLUMN IF EXISTS value,
  DROP COLUMN IF EXISTS min_order_value,
  DROP COLUMN IF EXISTS max_discount,
  ADD COLUMN description TEXT AFTER name,
  ADD COLUMN discount_type ENUM('percentage','fixed') NOT NULL DEFAULT 'percentage' AFTER description,
  ADD COLUMN discount_value DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER discount_type,
  ADD COLUMN apply_to ENUM('all','category','product') NOT NULL DEFAULT 'all' AFTER discount_value,
  ADD COLUMN priority INT NOT NULL DEFAULT 0 AFTER apply_to;

-- Tạo bảng promotion_categories (cho apply_to = 'category')
CREATE TABLE IF NOT EXISTS promotion_categories (
  promotion_id BIGINT NOT NULL,
  category_id BIGINT NOT NULL,
  PRIMARY KEY (promotion_id, category_id),
  CONSTRAINT fk_pcat_promo FOREIGN KEY(promotion_id) REFERENCES promotions(id) ON DELETE CASCADE,
  CONSTRAINT fk_pcat_cat FOREIGN KEY(category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Cập nhật promotion_products: bỏ created_by, updated_by
ALTER TABLE promotion_products
  DROP FOREIGN KEY IF EXISTS fk_ppromo_created_by,
  DROP FOREIGN KEY IF EXISTS fk_ppromo_updated_by,
  DROP COLUMN IF EXISTS created_at,
  DROP COLUMN IF EXISTS updated_at,
  DROP COLUMN IF EXISTS created_by,
  DROP COLUMN IF EXISTS updated_by;


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

ALTER TABLE expense_vouchers
  ADD COLUMN bank_account_id BIGINT NULL AFTER supplier_id,
  ADD CONSTRAINT fk_ev_bank_account FOREIGN KEY(bank_account_id) 
    REFERENCES supplier_bank_accounts(id) ON DELETE SET NULL;

-- Cập nhật safety_stock mặc định cho các bản ghi hiện tại
UPDATE stocks SET safety_stock = 10 WHERE safety_stock = 0;

-- Bảng thông báo
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

ALTER TABLE coupons 
ADD COLUMN max_discount DECIMAL(12,2) DEFAULT 0 COMMENT 'Giảm tối đa (chỉ áp dụng cho loại Phần trăm)' 
AFTER min_order_value;

-- Bảng stocktake_items: Chi tiết từng sản phẩm trong phiếu kiểm kê
CREATE TABLE IF NOT EXISTS `stocktake_items` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `stocktake_id` INT UNSIGNED NOT NULL COMMENT 'ID phiếu kiểm kê',
  `product_id` INT UNSIGNED NOT NULL COMMENT 'ID sản phẩm',
  `system_quantity` INT NOT NULL DEFAULT 0 COMMENT 'Số lượng tồn kho hệ thống',
  `actual_quantity` INT NOT NULL DEFAULT 0 COMMENT 'Số lượng kiểm kê thực tế',
  `difference` INT NOT NULL DEFAULT 0 COMMENT 'Chênh lệch (actual - system)',
  
  FOREIGN KEY (`stocktake_id`) REFERENCES `stocktakes`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
  
  INDEX `idx_stocktake_id` (`stocktake_id`),
  INDEX `idx_product_id` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
