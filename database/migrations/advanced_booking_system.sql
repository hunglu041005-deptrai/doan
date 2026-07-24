-- Enhanced bookings table
ALTER TABLE bookings 
ADD COLUMN booking_type ENUM('single', 'recurring', 'group') DEFAULT 'single' AFTER payment_status,
ADD COLUMN parent_booking_id INT NULL AFTER booking_type,
ADD COLUMN cancellation_reason TEXT NULL AFTER booking_type,
ADD COLUMN refund_amount DECIMAL(10,2) DEFAULT 0 AFTER cancellation_reason,
ADD COLUMN cancelled_at TIMESTAMP NULL AFTER refund_amount,
ADD COLUMN notes TEXT NULL AFTER cancelled_at;

-- Add foreign key for parent booking
ALTER TABLE bookings 
ADD CONSTRAINT fk_parent_booking 
FOREIGN KEY (parent_booking_id) REFERENCES bookings(id) ON DELETE SET NULL;

-- Create booking_members table for group bookings
CREATE TABLE booking_members (
    id INT PRIMARY KEY AUTO_INCREMENT,
    booking_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(20),
    role ENUM('organizer', 'member') DEFAULT 'member',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
);

-- Create booking_notifications table
CREATE TABLE booking_notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    booking_id INT NOT NULL,
    user_id INT NOT NULL,
    type ENUM('confirmation', 'reminder', 'cancellation', 'update') NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Create booking_reviews table
CREATE TABLE booking_reviews (
    id INT PRIMARY KEY AUTO_INCREMENT,
    booking_id INT NOT NULL,
    user_id INT NOT NULL,
    court_id INT NOT NULL,
    rating TINYINT(1) CHECK (rating >= 1 AND rating <= 5),
    review_text TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (court_id) REFERENCES courts(id) ON DELETE CASCADE,
    UNIQUE KEY unique_booking_review (booking_id, user_id)
);

-- Create waitlist table
CREATE TABLE booking_waitlist (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    court_id INT NOT NULL,
    preferred_date DATE NOT NULL,
    preferred_time_start TIME NOT NULL,
    preferred_time_end TIME NOT NULL,
    max_price DECIMAL(10,2),
    notification_method ENUM('email', 'sms', 'both') DEFAULT 'email',
    status ENUM('active', 'notified', 'expired') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (court_id) REFERENCES courts(id) ON DELETE CASCADE
);

-- Add indexes for better performance
ALTER TABLE bookings ADD INDEX idx_booking_date_time (booking_date, start_time);
ALTER TABLE bookings ADD INDEX idx_court_date (court_id, booking_date);
ALTER TABLE bookings ADD INDEX idx_user_status (user_id, status);
ALTER TABLE bookings ADD INDEX idx_booking_type (booking_type);

ALTER TABLE booking_members ADD INDEX idx_booking_id (booking_id);
ALTER TABLE booking_notifications ADD INDEX idx_user_read (user_id, is_read);
ALTER TABLE booking_reviews ADD INDEX idx_court_rating (court_id, rating);
ALTER TABLE booking_waitlist ADD INDEX idx_court_date_time (court_id, preferred_date, preferred_time_start);