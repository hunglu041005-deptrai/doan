-- Tạo bảng categories cho danh mục sản phẩm
CREATE TABLE IF NOT EXISTS product_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    image VARCHAR(255),
    status TINYINT NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tạo bảng products cho sản phẩm
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    name VARCHAR(200) NOT NULL,
    slug VARCHAR(200) NOT NULL UNIQUE,
    description TEXT,
    short_description TEXT,
    brand VARCHAR(100),
    price DECIMAL(10,0) NOT NULL,
    sale_price DECIMAL(10,0) DEFAULT NULL,
    sku VARCHAR(100) UNIQUE,
    stock_quantity INT NOT NULL DEFAULT 0,
    weight DECIMAL(8,2),
    dimensions VARCHAR(100),
    image VARCHAR(255),
    gallery TEXT, -- JSON array of images
    features TEXT, -- JSON array of features
    specifications TEXT, -- JSON object of specs
    status TINYINT NOT NULL DEFAULT 1,
    featured TINYINT NOT NULL DEFAULT 0,
    rating DECIMAL(3,2) DEFAULT 0,
    review_count INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES product_categories(id) ON DELETE CASCADE,
    INDEX idx_category (category_id),
    INDEX idx_status (status),
    INDEX idx_featured (featured)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tạo bảng product_variants cho các biến thể sản phẩm (size, màu sắc)
CREATE TABLE IF NOT EXISTS product_variants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    name VARCHAR(100) NOT NULL, -- Size S, M, L hoặc Màu đỏ, xanh
    type VARCHAR(50) NOT NULL, -- size, color
    value VARCHAR(100) NOT NULL,
    price_adjustment DECIMAL(10,0) DEFAULT 0,
    stock_quantity INT NOT NULL DEFAULT 0,
    sku VARCHAR(100),
    status TINYINT NOT NULL DEFAULT 1,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_product (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tạo bảng orders cho đơn hàng
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    order_number VARCHAR(50) NOT NULL UNIQUE,
    status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') NOT NULL DEFAULT 'pending',
    payment_status ENUM('pending', 'paid', 'failed', 'refunded') NOT NULL DEFAULT 'pending',
    payment_method VARCHAR(50),
    subtotal DECIMAL(10,0) NOT NULL,
    tax_amount DECIMAL(10,0) DEFAULT 0,
    shipping_amount DECIMAL(10,0) DEFAULT 0,
    discount_amount DECIMAL(10,0) DEFAULT 0,
    total_amount DECIMAL(10,0) NOT NULL,
    currency VARCHAR(3) DEFAULT 'VND',
    customer_name VARCHAR(100) NOT NULL,
    customer_email VARCHAR(150) NOT NULL,
    customer_phone VARCHAR(20),
    shipping_address TEXT NOT NULL,
    billing_address TEXT,
    notes TEXT,
    shipped_at DATETIME NULL,
    delivered_at DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_status (status),
    INDEX idx_order_number (order_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tạo bảng order_items cho chi tiết đơn hàng
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    variant_id INT NULL,
    product_name VARCHAR(200) NOT NULL,
    product_sku VARCHAR(100),
    variant_name VARCHAR(100),
    quantity INT NOT NULL,
    unit_price DECIMAL(10,0) NOT NULL,
    total_price DECIMAL(10,0) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE SET NULL,
    INDEX idx_order (order_id),
    INDEX idx_product (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tạo bảng product_reviews cho đánh giá sản phẩm
CREATE TABLE IF NOT EXISTS product_reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    user_id INT NOT NULL,
    order_id INT NULL,
    rating TINYINT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    title VARCHAR(200),
    comment TEXT,
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    helpful_count INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL,
    INDEX idx_product (product_id),
    INDEX idx_user (user_id),
    INDEX idx_status (status),
    INDEX idx_rating (rating)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tạo bảng shopping_cart cho giỏ hàng
CREATE TABLE IF NOT EXISTS shopping_cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    variant_id INT NULL,
    quantity INT NOT NULL DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE SET NULL,
    UNIQUE KEY unique_cart_item (user_id, product_id, variant_id),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert sample categories
INSERT INTO product_categories (name, slug, description, image) VALUES
('Vợt cầu lông', 'vot-cau-long', 'Vợt cầu lông chính hãng từ các thương hiệu uy tín', 'https://via.placeholder.com/300x200?text=Rackets'),
('Giày thể thao', 'giay-the-thao', 'Giày cầu lông chuyên dụng', 'https://via.placeholder.com/300x200?text=Shoes'),
('Quần áo', 'quan-ao', 'Quần áo thể thao cầu lông', 'https://via.placeholder.com/300x200?text=Apparel'),
('Phụ kiện', 'phu-kien', 'Túi đựng vợt, cầu lông và các phụ kiện khác', 'https://via.placeholder.com/300x200?text=Accessories');

-- Insert sample products
INSERT INTO products (category_id, name, slug, description, brand, price, sale_price, sku, stock_quantity, image, rating, review_count, featured) VALUES
(1, 'Vợt Yonex Arcsaber 11', 'vot-yonex-arcsaber-11', 'Vợt cầu lông Yonex Arcsaber 11 chính hãng, phù hợp cho người chơi trung bình đến cao cấp', 'Yonex', 3200000, 2800000, 'YNX-ARC11', 15, 'https://via.placeholder.com/400x400?text=Yonex+Arcsaber+11', 4.5, 28, 1),
(1, 'Vợt Victor Thruster K9900', 'vot-victor-thruster-k9900', 'Vợt cầu lông Victor Thruster K9900, thiết kế hiện đại, độ bền cao', 'Victor', 2800000, NULL, 'VCT-TK9900', 12, 'https://via.placeholder.com/400x400?text=Victor+K9900', 4.8, 35, 1),
(1, 'Vợt Lining Windstorm 78', 'vot-lining-windstorm-78', 'Vợt cầu lông Lining Windstorm 78, nhẹ và linh hoạt', 'Lining', 2400000, NULL, 'LN-WS78', 20, 'https://via.placeholder.com/400x400?text=Lining+WS78', 4.2, 18, 0),
(2, 'Giày Yonex Power Cushion 65Z3', 'giay-yonex-power-cushion-65z3', 'Giày cầu lông Yonex với công nghệ Power Cushion', 'Yonex', 2200000, NULL, 'YNX-PC65Z3', 25, 'https://via.placeholder.com/400x400?text=Yonex+Shoes', 4.7, 42, 1),
(2, 'Giày Victor A922', 'giay-victor-a922', 'Giày cầu lông Victor A922, thoải mái và bền bỉ', 'Victor', 1800000, NULL, 'VCT-A922', 18, 'https://via.placeholder.com/400x400?text=Victor+A922', 4.3, 22, 0),
(3, 'Áo Yonex 10274EX', 'ao-yonex-10274ex', 'Áo thể thao cầu lông Yonex, chất liệu thoáng mát', 'Yonex', 450000, NULL, 'YNX-10274EX', 50, 'https://via.placeholder.com/400x400?text=Yonex+Shirt', 4.1, 15, 0),
(4, 'Túi đựng vợt Yonex BAG92026EX', 'tui-dung-vot-yonex-bag92026ex', 'Túi đựng vợt Yonex, chứa được 6 cây vợt', 'Yonex', 1200000, NULL, 'YNX-BAG92026EX', 8, 'https://via.placeholder.com/400x400?text=Yonex+Bag', 4.4, 12, 0);

-- Insert sample variants for shoes (sizes)
INSERT INTO product_variants (product_id, name, type, value, stock_quantity) VALUES
(4, 'Size 39', 'size', '39', 5),
(4, 'Size 40', 'size', '40', 8),
(4, 'Size 41', 'size', '41', 6),
(4, 'Size 42', 'size', '42', 4),
(4, 'Size 43', 'size', '43', 2),
(5, 'Size 39', 'size', '39', 3),
(5, 'Size 40', 'size', '40', 5),
(5, 'Size 41', 'size', '41', 4),
(5, 'Size 42', 'size', '42', 3),
(5, 'Size 43', 'size', '43', 3);

-- Insert sample variants for apparel (sizes)
INSERT INTO product_variants (product_id, name, type, value, stock_quantity) VALUES
(6, 'Size S', 'size', 'S', 12),
(6, 'Size M', 'size', 'M', 15),
(6, 'Size L', 'size', 'L', 18),
(6, 'Size XL', 'size', 'XL', 10),
(6, 'Size XXL', 'size', 'XXL', 5);