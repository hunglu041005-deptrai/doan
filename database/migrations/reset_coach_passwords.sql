-- Cập nhật mật khẩu các HLV thành 123456
-- Hash bcrypt của "123456"
UPDATE users 
SET password = '$2y$10$OmZfsNso2OCrd9BY1QNoK.xM3t9efqjfu09LNg0nyrpHXGKDNX5N'
WHERE role = 'coach';

-- Xác nhận
SELECT id, name, email, role FROM users WHERE role = 'coach';
