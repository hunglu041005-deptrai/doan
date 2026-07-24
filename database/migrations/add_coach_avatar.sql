-- Thêm cột avatar cho bảng coaches (chạy 1 lần)
ALTER TABLE coaches
    ADD COLUMN IF NOT EXISTS avatar VARCHAR(255) DEFAULT NULL COMMENT 'Đường dẫn ảnh đại diện HLV';
