<?php
require_once __DIR__ . '/includes/functions.php';

// Chặn admin truy cập trang web thường
blockAdminFromPublic();

// Get categories and products from database
$categories_result = $mysqli->query('SELECT * FROM product_categories WHERE status = 1 ORDER BY sort_order, name');
$categories = $categories_result ? $categories_result->fetch_all(MYSQLI_ASSOC) : [];

$products_result = $mysqli->query('
    SELECT p.*, c.name as category_name, c.slug as category_slug
    FROM products p 
    LEFT JOIN product_categories c ON p.category_id = c.id 
    WHERE p.status = 1 
    ORDER BY p.featured DESC, p.created_at DESC
');
$products = $products_result ? $products_result->fetch_all(MYSQLI_ASSOC) : [];

// Group products by category
$products_by_category = [];
foreach ($products as $product) {
    $products_by_category[$product['category_slug']][] = $product;
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- Equipment Page Header -->
<section class="bg-danger text-white py-3">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="h4 mb-1">
                    <i class="fas fa-shopping-cart me-2"></i>Thiết bị & Phụ kiện
                </h1>
                <p class="mb-0 opacity-75 small">Chính hãng & Uy tín - Đầy đủ vợt, giày, quần áo</p>
            </div>
            <div class="col-md-4 text-md-end">
                <a href="discover.php" class="btn btn-light btn-sm">
                    <i class="fas fa-arrow-left me-2"></i>Quay lại
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Equipment Categories -->
<section class="equipment-categories py-5">
    <div class="container">
        <div class="row mb-5">
            <div class="col-12 text-center">
                <h2 class="fw-bold mb-3">Danh mục sản phẩm</h2>
                <p class="text-muted">Thiết bị cầu lông chính hãng từ các thương hiệu uy tín</p>
            </div>
        </div>
        
        <!-- Category Tabs -->
        <div class="category-tabs mb-4">
            <ul class="nav nav-pills justify-content-center" id="categoryTabs" role="tablist">
                <?php foreach ($categories as $index => $category): ?>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?php echo $index === 0 ? 'active' : ''; ?>" 
                                id="<?php echo $category['slug']; ?>-tab" 
                                data-bs-toggle="pill" 
                                data-bs-target="#<?php echo $category['slug']; ?>" 
                                type="button" role="tab">
                            <i class="fas fa-<?php 
                                echo $category['slug'] === 'vot-cau-long' ? 'table-tennis' : 
                                    ($category['slug'] === 'giay-the-thao' ? 'shoe-prints' : 
                                    ($category['slug'] === 'quan-ao' ? 'tshirt' : 'briefcase')); 
                            ?> me-2"></i><?php echo htmlspecialchars($category['name']); ?>
                        </button>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        
        <!-- Category Content -->
        <div class="tab-content" id="categoryTabsContent">
            <?php foreach ($categories as $index => $category): ?>
                <div class="tab-pane fade <?php echo $index === 0 ? 'show active' : ''; ?>" 
                     id="<?php echo $category['slug']; ?>" role="tabpanel">
                    <div class="row g-4">
                        <?php 
                        $category_products = $products_by_category[$category['slug']] ?? [];
                        if (empty($category_products)): 
                        ?>
                            <div class="col-12 text-center py-5">
                                <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">Chưa có sản phẩm trong danh mục này</h5>
                                <p class="text-muted">Sản phẩm sẽ được cập nhật sớm</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($category_products as $product): ?>
                                <div class="col-lg-3 col-md-6">
                                    <div class="product-card bg-white rounded-4 shadow-sm h-100">
                                        <div class="product-image position-relative">
                                            <img src="<?php echo htmlspecialchars($product['image']); ?>" 
                                                 class="card-img-top" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                            <?php if ($product['featured']): ?>
                                                <div class="product-badge">
                                                    <span class="badge bg-success">Bán chạy</span>
                                                </div>
                                            <?php endif; ?>
                                            <?php if ($product['sale_price']): ?>
                                                <div class="product-badge">
                                                    <span class="badge bg-warning text-dark">Giảm giá</span>
                                                </div>
                                            <?php endif; ?>
                                            <div class="product-actions">
                                                <button class="btn btn-light btn-sm" title="Yêu thích">
                                                    <i class="fas fa-heart"></i>
                                                </button>
                                                <a href="product-detail.php?id=<?php echo $product['id']; ?>" class="btn btn-light btn-sm" title="Xem chi tiết">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </div>
                                        </div>
                                        <div class="card-body p-3">
                                            <?php if ($product['brand']): ?>
                                                <div class="product-brand mb-1">
                                                    <span class="badge bg-primary"><?php echo htmlspecialchars($product['brand']); ?></span>
                                                </div>
                                            <?php endif; ?>
                                            <h6 class="product-name fw-bold mb-2">
                                                <a href="product-detail.php?id=<?php echo $product['id']; ?>" class="text-decoration-none text-dark">
                                                    <?php echo htmlspecialchars($product['name']); ?>
                                                </a>
                                            </h6>
                                            <?php if (isset($product['average_rating']) && $product['average_rating'] > 0): ?>
                                                <div class="product-rating mb-2">
                                                    <?php 
                                                    $rating = floatval($product['average_rating']);
                                                    for ($i = 1; $i <= 5; $i++): 
                                                    ?>
                                                        <i class="fas fa-star <?php echo $i <= $rating ? 'text-warning' : 'text-muted'; ?>"></i>
                                                    <?php endfor; ?>
                                                    <span class="small text-muted ms-1">(<?php echo $product['review_count'] ?? 0; ?>)</span>
                                                </div>
                                            <?php endif; ?>
                                            <div class="product-price mb-3">
                                                <?php if ($product['sale_price']): ?>
                                                    <span class="h6 text-danger fw-bold"><?php echo number_format($product['sale_price']); ?>đ</span>
                                                    <span class="small text-muted text-decoration-line-through ms-2"><?php echo number_format($product['price']); ?>đ</span>
                                                <?php else: ?>
                                                    <span class="h6 text-danger fw-bold"><?php echo number_format($product['price']); ?>đ</span>
                                                <?php endif; ?>
                                            </div>
                                            <?php if ($product['short_description']): ?>
                                                <div class="product-features mb-3">
                                                    <small class="text-muted">
                                                        <?php echo htmlspecialchars($product['short_description']); ?>
                                                    </small>
                                                </div>
                                            <?php elseif ($product['description']): ?>
                                                <div class="product-features mb-3">
                                                    <small class="text-muted">
                                                        <?php echo htmlspecialchars(substr($product['description'], 0, 80)) . (strlen($product['description']) > 80 ? '...' : ''); ?>
                                                    </small>
                                                </div>
                                            <?php endif; ?>
                                            <?php if ($product['stock_quantity'] > 0): ?>
                                                <div class="stock-info mb-2">
                                                    <?php if ($product['stock_quantity'] <= 5): ?>
                                                        <small class="text-warning">
                                                            <i class="fas fa-exclamation-triangle me-1"></i>
                                                            Chỉ còn <?php echo $product['stock_quantity']; ?> sản phẩm
                                                        </small>
                                                    <?php else: ?>
                                                        <small class="text-success">
                                                            <i class="fas fa-check-circle me-1"></i>
                                                            Còn hàng (<?php echo $product['stock_quantity']; ?>)
                                                        </small>
                                                    <?php endif; ?>
                                                </div>

                                                <?php
                                                // Danh mục cần chọn size
                                                $needSize   = in_array($product['category_slug'] ?? '', ['giay-the-thao','quan-ao','phu-kien']);
                                                $sizeOptions = [];
                                                if (in_array($product['category_slug'] ?? '', ['giay-the-thao'])) {
                                                    $sizeOptions = ['38','39','40','41','42','43','44'];
                                                } elseif (in_array($product['category_slug'] ?? '', ['quan-ao'])) {
                                                    $sizeOptions = ['S','M','L','XL','XXL'];
                                                } elseif (in_array($product['category_slug'] ?? '', ['phu-kien'])) {
                                                    $sizeOptions = ['S/M','M/L','L/XL','One Size'];
                                                }
                                                ?>

                                                <?php if ($needSize && !empty($sizeOptions)): ?>
                                                <div class="size-selector mb-2" id="size-wrap-<?php echo $product['id']; ?>">
                                                    <div style="font-size:.75rem;font-weight:700;color:#374151;margin-bottom:.35rem;">
                                                        <i class="fas fa-ruler-horizontal me-1 text-muted"></i>Chọn size:
                                                    </div>
                                                    <div class="d-flex flex-wrap gap-1">
                                                        <?php foreach ($sizeOptions as $s): ?>
                                                        <button type="button" class="size-chip"
                                                                data-product-id="<?php echo $product['id']; ?>"
                                                                data-size="<?php echo $s; ?>"
                                                                onclick="selectSize(this)">
                                                            <?php echo $s; ?>
                                                        </button>
                                                        <?php endforeach; ?>
                                                    </div>
                                                    <input type="hidden" id="selected-size-<?php echo $product['id']; ?>" value="">
                                                </div>
                                                <?php endif; ?>

                                                <!-- Hai nút hành động -->
                                                <div class="d-flex gap-2 mt-2">
                                                    <button class="btn btn-danger flex-grow-1 add-to-cart"
                                                            data-product-id="<?php echo $product['id']; ?>"
                                                            <?php if ($needSize): ?>data-needs-size="1"<?php endif; ?>>
                                                        <i class="fas fa-shopping-cart me-1"></i>Giỏ hàng
                                                    </button>
                                                    <button class="btn btn-warning flex-grow-1 fw-bold buy-now-btn"
                                                            data-product-id="<?php echo $product['id']; ?>"
                                                            data-product-name="<?php echo htmlspecialchars($product['name']); ?>"
                                                            data-product-price="<?php echo $product['sale_price'] ?: $product['price']; ?>"
                                                            data-product-image="<?php echo htmlspecialchars($product['image'] ?? ''); ?>"
                                                            <?php if ($needSize): ?>data-needs-size="1"<?php endif; ?>>
                                                        <i class="fas fa-bolt me-1"></i>Đặt ngay
                                                    </button>
                                                </div>
                                            <?php else: ?>
                                                <div class="stock-info mb-2">
                                                    <small class="text-danger">
                                                        <i class="fas fa-times-circle me-1"></i>
                                                        Hết hàng
                                                    </small>
                                                </div>
                                                <button class="btn btn-secondary w-100" disabled>
                                                    <i class="fas fa-times me-2"></i>Hết hàng
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Shopping Cart Sidebar -->
<div class="cart-sidebar" id="cartSidebar">
    <div class="cart-header">
        <h5 class="fw-bold mb-0">
            <i class="fas fa-shopping-cart me-2"></i>Giỏ hàng
        </h5>
        <button class="btn-close" id="closeCart"></button>
    </div>
    <div class="cart-body">
        <div id="cartItems">
            <div class="empty-cart text-center py-4">
                <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                <p class="text-muted">Giỏ hàng trống</p>
            </div>
        </div>
    </div>
    <div class="cart-footer">
        <div class="cart-total mb-3">
            <div class="d-flex justify-content-between">
                <span class="fw-bold">Tổng cộng:</span>
                <span class="fw-bold text-danger" id="cartTotal">0đ</span>
            </div>
        </div>
        <div class="d-grid gap-2">
            <button class="btn btn-danger" id="checkoutBtn" disabled>
                <i class="fas fa-credit-card me-2"></i>Thanh toán
            </button>
            <button class="btn btn-outline-secondary" id="clearCart">
                <i class="fas fa-trash me-2"></i>Xóa tất cả
            </button>
        </div>
    </div>
</div>

<!-- Cart Toggle Button -->
<div class="cart-toggle" id="cartToggle">
    <i class="fas fa-shopping-cart"></i>
    <span class="cart-count" id="cartCount">0</span>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<style>
/* Size chips */
.size-chip {
    background: #f3f4f6; border: 1.5px solid #e5e7eb;
    border-radius: 8px; padding: 3px 10px;
    font-size: .75rem; font-weight: 700; color: #374151;
    cursor: pointer; transition: all .15s;
}
.size-chip:hover   { border-color: #ef4444; color: #ef4444; background: #fff0f0; }
.size-chip.active  { background: #ef4444; border-color: #ef4444; color: #fff; }
.size-chip.sold-out{ opacity:.45; cursor:not-allowed; text-decoration:line-through; }
</style>

<script>
// Chọn size
function selectSize(btn) {
    const pid  = btn.dataset.productId;
    document.querySelectorAll(`.size-chip[data-product-id="${pid}"]`).forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById('selected-size-' + pid).value = btn.dataset.size;
}

// Đặt ngay — thêm vào giỏ rồi redirect checkout
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.buy-now-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const pid       = parseInt(this.dataset.productId);
            const name      = this.dataset.productName;
            const price     = parseInt(this.dataset.productPrice);
            const image     = this.dataset.productImage;
            const needsSize = this.dataset.needsSize === '1';

            if (needsSize) {
                const sizeVal = document.getElementById('selected-size-' + pid)?.value;
                if (!sizeVal) {
                    // Highlight size selector
                    const wrap = document.getElementById('size-wrap-' + pid);
                    if (wrap) {
                        wrap.style.animation = 'none';
                        wrap.style.border = '1.5px solid #ef4444';
                        wrap.style.borderRadius = '8px';
                        wrap.style.padding = '4px';
                        setTimeout(() => { wrap.style.border = ''; wrap.style.padding = ''; }, 1800);
                    }
                    alert('Vui lòng chọn size trước khi đặt hàng!');
                    return;
                }
                // Thêm size vào tên
                const cartItem = { id: pid, name: name + ' (Size ' + sizeVal + ')', price: price, quantity: 1, image: image };
                addToCartAndCheckout(cartItem);
            } else {
                const cartItem = { id: pid, name: name, price: price, quantity: 1, image: image };
                addToCartAndCheckout(cartItem);
            }
        });
    });

    // Override add-to-cart để kiểm tra size
    document.querySelectorAll('.add-to-cart[data-needs-size="1"]').forEach(btn => {
        btn.addEventListener('click', function(e) {
            const pid    = parseInt(this.dataset.productId);
            const sizeVal = document.getElementById('selected-size-' + pid)?.value;
            if (!sizeVal) {
                e.stopImmediatePropagation();
                const wrap = document.getElementById('size-wrap-' + pid);
                if (wrap) {
                    wrap.style.border = '1.5px solid #ef4444';
                    wrap.style.borderRadius = '8px';
                    wrap.style.padding = '4px';
                    setTimeout(() => { wrap.style.border = ''; wrap.style.padding = ''; }, 1800);
                }
                alert('Vui lòng chọn size trước!');
            }
        }, true); // capture phase — runs before equipment.js handler
    });
});

function addToCartAndCheckout(item) {
    // Lấy cart hiện tại
    let cart = JSON.parse(localStorage.getItem('cart') || '[]');
    const existing = cart.find(c => c.id === item.id && c.name === item.name);
    if (existing) {
        existing.quantity += 1;
    } else {
        cart.push(item);
    }
    localStorage.setItem('cart', JSON.stringify(cart));
    window.location.href = 'checkout.php';
}
</script>

<script src="assets/js/equipment.js"></script>