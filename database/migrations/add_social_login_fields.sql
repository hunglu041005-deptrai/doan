-- Add social login fields to users table
ALTER TABLE users 
ADD COLUMN social_provider VARCHAR(20) NULL AFTER password,
ADD COLUMN social_id VARCHAR(100) NULL AFTER social_provider,
ADD COLUMN avatar VARCHAR(255) NULL AFTER social_id,
ADD COLUMN email_verified TINYINT(1) DEFAULT 0 AFTER avatar,
ADD COLUMN last_login TIMESTAMP NULL AFTER email_verified;

-- Add indexes for better performance
ALTER TABLE users 
ADD INDEX idx_social_provider_id (social_provider, social_id),
ADD INDEX idx_email_verified (email_verified);

-- Update existing users to have email_verified = 1
UPDATE users SET email_verified = 1 WHERE email IS NOT NULL;