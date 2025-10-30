-- Bảng lưu lịch sử nhập file
CREATE TABLE IF NOT EXISTS import_history (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Lịch sử nhập file Excel';
