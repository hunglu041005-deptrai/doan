-- Tạo bảng training_registrations nếu chưa có (với đầy đủ các cột)
CREATE TABLE IF NOT EXISTS training_registrations (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    student_code     VARCHAR(20)  UNIQUE NOT NULL,
    student_name     VARCHAR(100) NOT NULL,
    phone            VARCHAR(20)  NOT NULL,
    email            VARCHAR(100) DEFAULT NULL,
    course           ENUM('beginner','intermediate','advanced') NOT NULL,
    age_group        VARCHAR(30)  DEFAULT NULL,
    preferred_time   VARCHAR(60)  DEFAULT NULL,
    preferred_coach  VARCHAR(100) DEFAULT NULL,
    current_level    VARCHAR(50)  DEFAULT NULL,
    learning_goals   TEXT         DEFAULT NULL,
    coach_id         INT          DEFAULT NULL,
    schedule_days    VARCHAR(100) DEFAULT NULL,
    schedule_time    VARCHAR(60)  DEFAULT NULL,
    week_start       DATE         DEFAULT NULL,
    qr_code          VARCHAR(50)  DEFAULT NULL,
    status           VARCHAR(30)  NOT NULL DEFAULT 'pending_payment',
    payment_method   VARCHAR(30)  DEFAULT NULL,
    payment_at       DATETIME     DEFAULT NULL,
    created_at       DATETIME     DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_coach_week (coach_id, week_start),
    INDEX idx_status     (status),
    INDEX idx_code       (student_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Nếu bảng đã tồn tại, thêm cột còn thiếu (chạy từng lệnh)
ALTER TABLE training_registrations
    ADD COLUMN IF NOT EXISTS payment_method VARCHAR(30) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS payment_at     DATETIME    DEFAULT NULL;

-- Sửa cột status thành VARCHAR (để dùng pending_payment thay enum cũ)
-- Chạy nếu status là ENUM cũ chỉ có 'active'/'inactive':
-- ALTER TABLE training_registrations MODIFY COLUMN status VARCHAR(30) NOT NULL DEFAULT 'pending_payment';
