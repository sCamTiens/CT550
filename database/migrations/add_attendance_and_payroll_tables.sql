-- =====================================================================
-- Bảng CA LÀM VIỆC
-- =====================================================================
CREATE TABLE IF NOT EXISTS work_shifts (
    id TINYINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,                      -- Tên ca: Ca sáng, Ca chiều, Ca tối
    start_time TIME NOT NULL,                       -- Giờ bắt đầu ca
    end_time TIME NOT NULL,                         -- Giờ kết thúc ca
    is_active TINYINT(1) DEFAULT 1,                 -- Trạng thái hoạt động
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by BIGINT,
    updated_by BIGINT,
    CONSTRAINT fk_shifts_created_by FOREIGN KEY(created_by) REFERENCES users(id),
    CONSTRAINT fk_shifts_updated_by FOREIGN KEY(updated_by) REFERENCES users(id),
    INDEX idx_shift_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dữ liệu mẫu cho ca làm việc (không có lương, vì mỗi vị trí lương khác nhau)
INSERT INTO work_shifts (name, start_time, end_time, is_active) VALUES
('Ca sáng', '06:00:00', '14:00:00', 1),
('Ca chiều', '14:00:00', '22:00:00', 1);

-- =====================================================================
-- Bảng LỊCH LÀM VIỆC NHÂN VIÊN (Sắp ca theo ngày)
-- =====================================================================
CREATE TABLE IF NOT EXISTS staff_shift_schedule (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    staff_id BIGINT NOT NULL,                       -- Mã nhân viên
    shift_id TINYINT UNSIGNED NOT NULL,             -- Mã ca làm việc
    work_date DATE NOT NULL,                        -- Ngày làm việc
    status ENUM('Làm việc', 'Nghỉ', 'Có phép', 'Không phép') DEFAULT 'Làm việc', -- Trạng thái
    note VARCHAR(255),                              -- Ghi chú (lý do nghỉ, đổi ca, v.v.)
    created_by BIGINT,                              -- Admin tạo lịch
    updated_by BIGINT,                              -- Admin cập nhật lịch
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- Bảng CHẤM CÔNG
-- =====================================================================
CREATE TABLE IF NOT EXISTS attendances (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL,                        -- Mã nhân viên
    shift_id TINYINT UNSIGNED NOT NULL,             -- Mã ca làm việc
    attendance_date DATE NOT NULL,                  -- Ngày chấm công
    check_in_time DATETIME,                         -- Giờ vào (check-in)
    check_out_time DATETIME,                        -- Giờ ra (check-out)
    check_in_status ENUM('Đúng giờ', 'Muộn', 'Chưa chấm') DEFAULT 'Chưa chấm', -- Trạng thái check-in
    check_out_status ENUM('Đúng giờ', 'Sớm', 'Chưa chấm') DEFAULT 'Chưa chấm', -- Trạng thái check-out
    status ENUM('Có mặt', 'Vắng mặt', 'Đi muộn', 'Về sớm', 'Chưa hoàn thành') DEFAULT 'Chưa hoàn thành', -- Trạng thái tổng thể
    work_hours DECIMAL(5,2) DEFAULT 0,              -- Số giờ làm việc thực tế
    notes TEXT,                                     -- Ghi chú
    is_approved TINYINT(1) DEFAULT 0,               -- Đã duyệt chưa
    approved_by BIGINT,                             -- Người duyệt
    approved_at DATETIME,                           -- Thời gian duyệt
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
    INDEX idx_att_check_out_status (check_out_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- Thêm cột LƯƠNG vào bảng STAFF_PROFILES
-- =====================================================================
ALTER TABLE staff_profiles 
ADD COLUMN base_salary DECIMAL(15,2) DEFAULT 0 COMMENT 'Lương cơ bản tháng',
ADD COLUMN wage_per_shift DECIMAL(15,2) DEFAULT 0 COMMENT 'Lương mỗi ca (nếu tính theo ca)',
ADD COLUMN salary_type ENUM('Theo tháng', 'Theo ca') DEFAULT 'Theo ca' COMMENT 'Loại lương: theo tháng hay theo ca',
ADD COLUMN required_shifts_per_month TINYINT UNSIGNED DEFAULT 28 COMMENT 'Số ca yêu cầu để được full lương';

-- =====================================================================
-- Bảng BẢNG LƯƠNG
-- =====================================================================
CREATE TABLE IF NOT EXISTS payrolls (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL,                        -- Mã nhân viên
    month TINYINT UNSIGNED NOT NULL,                -- Tháng (1-12)
    year SMALLINT UNSIGNED NOT NULL,                -- Năm
    total_shifts_worked SMALLINT UNSIGNED DEFAULT 0, -- Tổng số ca làm
    required_shifts TINYINT UNSIGNED DEFAULT 28,    -- Số ca yêu cầu
    base_salary DECIMAL(15,2) DEFAULT 0,            -- Lương cơ bản
    actual_salary DECIMAL(15,2) DEFAULT 0,          -- Lương thực tế
    bonus DECIMAL(15,2) DEFAULT 0,                  -- Thưởng
    deduction DECIMAL(15,2) DEFAULT 0,              -- Phạt/Khấu trừ
    total_salary DECIMAL(15,2) DEFAULT 0,           -- Tổng lương = actual_salary + bonus - deduction
    status ENUM('Nháp', 'Đã duyệt', 'Đã trả') DEFAULT 'Nháp', -- Trạng thái
    notes TEXT,                                     -- Ghi chú
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- Bảng CHI TIẾT BẢNG LƯƠNG (theo từng ca)
-- =====================================================================
CREATE TABLE IF NOT EXISTS payroll_details (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    payroll_id BIGINT NOT NULL,                     -- Mã bảng lương
    attendance_id BIGINT NOT NULL,                  -- Mã chấm công
    shift_id TINYINT UNSIGNED NOT NULL,             -- Mã ca
    shift_date DATE NOT NULL,                       -- Ngày làm việc
    wage_amount DECIMAL(15,2) DEFAULT 0,            -- Tiền lương ca này (lấy từ wage_per_shift của nhân viên)
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_pd_payroll FOREIGN KEY(payroll_id) REFERENCES payrolls(id) ON DELETE CASCADE,
    CONSTRAINT fk_pd_attendance FOREIGN KEY(attendance_id) REFERENCES attendances(id) ON DELETE CASCADE,
    CONSTRAINT fk_pd_shift FOREIGN KEY(shift_id) REFERENCES work_shifts(id),
    INDEX idx_pd_payroll (payroll_id),
    INDEX idx_pd_date (shift_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =====================================================================
-- Thêm cột IP address vào bảng CHẤM CÔNG
-- =====================================================================

ALTER TABLE attendances 
ADD COLUMN check_in_ip VARCHAR(45) NULL COMMENT 'IP address khi check-in' AFTER check_in_status,
ADD COLUMN check_out_ip VARCHAR(45) NULL COMMENT 'IP address khi check-out' AFTER check_out_status;

-- Thêm index cho IP (để tra cứu nhanh)
ALTER TABLE attendances
ADD INDEX idx_check_in_ip (check_in_ip),
ADD INDEX idx_check_out_ip (check_out_ip);
