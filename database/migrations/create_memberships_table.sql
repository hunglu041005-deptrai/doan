-- Tạo bảng memberships đầy đủ (với ticket tracking)
CREATE TABLE IF NOT EXISTS memberships (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    user_id          INT NOT NULL,
    plan_id          INT NOT NULL,
    plan_name        VARCHAR(100),
    plan_detail      VARCHAR(100),
    price            INT,
    months           INT,
    free_tickets     INT DEFAULT 0,
    tickets_used     INT DEFAULT 0,         -- số vé đã dùng
    payment_method   VARCHAR(30),
    payment_status   VARCHAR(30) DEFAULT 'pending',  -- pending | paid
    status           VARCHAR(30) DEFAULT 'active',   -- active | expired | cancelled
    member_code      VARCHAR(30) UNIQUE,
    start_date       DATE,
    end_date         DATE,
    created_at       DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user   (user_id),
    INDEX idx_status (status),
    INDEX idx_end    (end_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Thêm cột tickets_used nếu bảng đã tồn tại
ALTER TABLE memberships ADD COLUMN IF NOT EXISTS tickets_used INT DEFAULT 0;
ALTER TABLE memberships ADD COLUMN IF NOT EXISTS updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Bảng lịch sử sử dụng vé hội viên
CREATE TABLE IF NOT EXISTS membership_ticket_logs (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    membership_id   INT NOT NULL,
    user_id         INT NOT NULL,
    booking_id      INT DEFAULT NULL,    -- liên kết booking nếu có
    action          ENUM('use','refund','bonus') NOT NULL DEFAULT 'use',
    tickets_delta   INT NOT NULL,        -- âm = trừ vé, dương = cộng vé
    note            VARCHAR(200),
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_membership (membership_id),
    INDEX idx_user       (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
