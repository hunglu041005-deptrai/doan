<?php
require_once __DIR__ . '/db.php';

echo "Đang tạo bảng shop...<br>";

// Tạo bảng product_categories
$sql1 = "CREATE TABLE IF NOT EXISTS product_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    image VARCHAR(255),
    status TINYINT NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($mysqli->query($sql1)) {
    echo "✅ Tạo bảng product_categories thành công<br>";
} else {
    echo "❌ Lỗi tạo bảng product_categories: " . $mysqli->error . "<br>";
}

// Tạo bảng products
$sql2 = "CREATE TABLE IF NOT EXISTS products (
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
    gallery TEXT,
    features TEXT,
    specifications TEXT,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($mysqli->query($sql2)) {
    echo "✅ Tạo bảng products thành công<br>";
} else {
    echo "❌ Lỗi tạo bảng products: " . $mysqli->error . "<br>";
}

// Tạo bảng orders
$sql3 = "CREATE TABLE IF NOT EXISTS orders (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($mysqli->query($sql3)) {
    echo "✅ Tạo bảng orders thành công<br>";
} else {
    echo "❌ Lỗi tạo bảng orders: " . $mysqli->error . "<br>";
}

// Thêm dữ liệu mẫu cho categories
$categories_data = [
    ['Vợt cầu lông', 'vot-cau-long', 'Vợt cầu lông chính hãng từ các thương hiệu uy tín'],
    ['Giày thể thao', 'giay-the-thao', 'Giày cầu lông chuyên dụng'],
    ['Quần áo', 'quan-ao', 'Quần áo thể thao cầu lông'],
    ['Phụ kiện', 'phu-kien', 'Túi đựng vợt, cầu lông và các phụ kiện khác']
];

foreach ($categories_data as $cat) {
    $check = $mysqli->query("SELECT id FROM product_categories WHERE slug = '{$cat[1]}'");
    if ($check->num_rows == 0) {
        $stmt = $mysqli->prepare("INSERT INTO product_categories (name, slug, description) VALUES (?, ?, ?)");
        $stmt->bind_param('sss', $cat[0], $cat[1], $cat[2]);
        if ($stmt->execute()) {
            echo "✅ Thêm danh mục: {$cat[0]}<br>";
        }
        $stmt->close();
    }
}

// Thêm dữ liệu mẫu cho products
$products_data = [
    [1, 'Vợt Yonex Arcsaber 11', 'vot-yonex-arcsaber-11', 'Vợt cầu lông Yonex Arcsaber 11 chính hãng', 'Yonex', 3200000, 2800000, 'YNX-ARC11', 15, 'https://via.placeholder.com/400x400?text=Yonex+Arcsaber+11', 1],
    [1, 'Vợt Victor Thruster K9900', 'vot-victor-thruster-k9900', 'Vợt cầu lông Victor Thruster K9900', 'Victor', 2800000, null, 'VCT-TK9900', 12, 'https://via.placeholder.com/400x400?text=Victor+K9900', 1],
    [2, 'Giày Yonex Power Cushion 65Z3', 'giay-yonex-power-cushion-65z3', 'Giày cầu lông Yonex với công nghệ Power Cushion', 'Yonex', 2200000, null, 'YNX-PC65Z3', 25, 'https://via.placeholder.com/400x400?text=Yonex+Shoes', 1],
    [3, 'Áo Yonex 10274EX', 'ao-yonex-10274ex', 'Áo thể thao cầu lông Yonex', 'Yonex', 450000, null, 'YNX-10274EX', 50, 'https://via.placeholder.com/400x400?text=Yonex+Shirt', 0],
    [4, 'Túi đựng vợt Yonex BAG92026EX', 'tui-dung-vot-yonex-bag92026ex', 'Túi đựng vợt Yonex, chứa được 6 cây vợt', 'Yonex', 1200000, null, 'YNX-BAG92026EX', 8, 'https://via.placeholder.com/400x400?text=Yonex+Bag', 0]
];

foreach ($products_data as $prod) {
    $check = $mysqli->query("SELECT id FROM products WHERE slug = '{$prod[2]}'");
    if ($check->num_rows == 0) {
        $stmt = $mysqli->prepare("INSERT INTO products (category_id, name, slug, description, brand, price, sale_price, sku, stock_quantity, image, featured) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('issssiisisi', $prod[0], $prod[1], $prod[2], $prod[3], $prod[4], $prod[5], $prod[6], $prod[7], $prod[8], $prod[9], $prod[10]);
        if ($stmt->execute()) {
            echo "✅ Thêm sản phẩm: {$prod[1]}<br>";
        }
        $stmt->close();
    }
}

echo "<hr>";
echo "<h3>✅ Hoàn tất thiết lập shop!</h3>";
echo "<p><a href='admin/shop.php'>Vào trang quản lý Shop</a></p>";
echo "<p><a href='equipment.php'>Xem trang Shop khách hàng</a></p>";
?>