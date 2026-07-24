 
 <?php
require_once __DIR__ . '/includes/functions.php';

// Chặn admin truy cập trang web thường
blockAdminFromPublic();

// Get product ID
$product_id = intval($_GET['id'] ?? 0);
if (!$product_id) {
    header('Location: equipment.php');
    exit;
}

// Get product details
$stmt = $mysqli->prepare('
    SELECT p.*, c.name as category_name, c.slug as category_slug
    FROM products p 
    LEFT JOIN product_categories c ON p.category_id = c.id 
    WHERE p.id = ? AND p.status = 1
');
$stmt->bind_param('i', $product_id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();
$stmt->close();

if (!$product) {
    header('Location: equipment.php');
    exit;
}

// Get product reviews
$reviews = [];
$rating_stats = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];

try {
    $reviews_stmt = $mysqli->prepare('
        SELECT r.*, u.name as user_name, u.email as user_email
        FROM product_reviews r
        JOIN users u ON r.user_id = u.id
        WHERE r.product_id = ? AND r.status = "approved"
        ORDER BY r.created_at DESC
    ');
    $reviews_stmt->bind_param('i', $product_id);
    $reviews_stmt->execute();
    $reviews_result = $reviews_stmt->get_result();
    $reviews = $reviews_result->fetch_all(MYSQLI_ASSOC);
    $reviews_stmt->close();

    // Get rating statistics
    for ($i = 1; $i <= 5; $i++) {
        $stmt = $mysqli->prepare('SELECT COUNT(*) as count FROM product_reviews WHERE product_id = ? AND rating = ? AND status = "approved"');
        $stmt->bind_param('ii', $product_id, $i);
        $stmt->execute();
        $result = $stmt->get_result();
        $rating_stats[$i] = $result->fetch_assoc()['count'];
        $stmt->close();
    }
} catch (Exception $e) {
    // Bảng chưa tồn tại, sử dụng giá trị mặc định
    $reviews = [];
    $rating_stats = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
}

// Check if user can review (must be logged in and have purchased)
$can_review = false;
$user_review = null;
if (isLoggedIn()) {
    try {
        // Check if reviews table exists first
        $table_check = $mysqli->query("SHOW TABLES LIKE 'product_reviews'");
        if (!$table_check || $table_check->num_rows == 0) {
            // Table doesn't exist, but don't redirect yet - let user try to review first
            $can_review = true;
            $user_review = null;
        } else {
            // Check if user already reviewed
            $stmt = $mysqli->prepare('SELECT * FROM product_reviews WHERE product_id = ? AND user_id = ?');
            $stmt->bind_param('ii', $product_id, $_SESSION['user_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            $user_review = $result->fetch_assoc();
            $stmt->close();
            
            // For demo purposes, allow all logged users to review
            $can_review = !$user_review;
        }
    } catch (Exception $e) {
        // Table doesn't exist or other error, allow review attempt
        $can_review = true;
        $user_review = null;
    }
}

// Handle AJAX review submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review']) && isLoggedIn()) {
    $rating = intval($_POST['rating'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $comment = trim($_POST['comment'] ?? '');
    
    // Check if this is an AJAX request
    $is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    
    if ($rating >= 1 && $rating <= 5 && !empty($comment) && !$user_review) {
        try {
            // Check if tables exist first
            $table_check = $mysqli->query("SHOW TABLES LIKE 'product_reviews'");
            if (!$table_check || $table_check->num_rows == 0) {
                if ($is_ajax) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'message' => 'Hệ thống đánh giá chưa được thiết lập. Vui lòng liên hệ admin.',
                        'redirect' => 'fix-reviews-setup.php'
                    ]);
                    exit;
                } else {
                    header("Location: fix-reviews-setup.php");
                    exit;
                }
            }
            
            $stmt = $mysqli->prepare('INSERT INTO product_reviews (product_id, user_id, rating, title, comment, status) VALUES (?, ?, ?, ?, ?, "approved")');
            if (!$stmt) {
                throw new Exception('Prepare failed: ' . $mysqli->error);
            }
            
            $stmt->bind_param('iiiss', $product_id, $_SESSION['user_id'], $rating, $title, $comment);
            
            if ($stmt->execute()) {
                $review_id = $mysqli->insert_id;
                
                // Cập nhật rating cho sản phẩm
                $update_rating = $mysqli->prepare('
                    UPDATE products 
                    SET average_rating = (
                        SELECT AVG(rating) FROM product_reviews 
                        WHERE product_id = ? AND status = "approved"
                    ),
                    review_count = (
                        SELECT COUNT(*) FROM product_reviews 
                        WHERE product_id = ? AND status = "approved"
                    )
                    WHERE id = ?
                ');
                
                if ($update_rating) {
                    $update_rating->bind_param('iii', $product_id, $product_id, $product_id);
                    $update_rating->execute();
                    $update_rating->close();
                }
                
                if ($is_ajax) {
                    // Get updated product info
                    $product_stmt = $mysqli->prepare('SELECT average_rating, review_count FROM products WHERE id = ?');
                    $product_stmt->bind_param('i', $product_id);
                    $product_stmt->execute();
                    $updated_product = $product_stmt->get_result()->fetch_assoc();
                    $product_stmt->close();
                    
                    // Get user info for the new review
                    $user_stmt = $mysqli->prepare('SELECT name FROM users WHERE id = ?');
                    $user_stmt->bind_param('i', $_SESSION['user_id']);
                    $user_stmt->execute();
                    $user_info = $user_stmt->get_result()->fetch_assoc();
                    $user_stmt->close();
                    
                    // Return JSON response
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => true,
                        'message' => 'Đánh giá của bạn đã được gửi thành công!',
                        'review' => [
                            'id' => $review_id,
                            'rating' => $rating,
                            'title' => $title,
                            'comment' => $comment,
                            'user_name' => $user_info['name'],
                            'created_at' => date('Y-m-d H:i:s'),
                            'helpful_count' => 0
                        ],
                        'product' => [
                            'average_rating' => floatval($updated_product['average_rating']),
                            'review_count' => intval($updated_product['review_count'])
                        ]
                    ]);
                    exit;
                } else {
                    header("Location: product-detail.php?id=$product_id&review_success=1");
                    exit;
                }
            } else {
                throw new Exception('Execute failed: ' . $stmt->error);
            }
            $stmt->close();
        } catch (Exception $e) {
            // Log error for debugging
            error_log("Review submission error: " . $e->getMessage());
            
            if ($is_ajax) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Có lỗi xảy ra khi lưu đánh giá. Vui lòng thử lại sau.',
                    'error' => $e->getMessage() // For debugging
                ]);
                exit;
            } else {
                header("Location: fix-reviews-setup.php");
                exit;
            }
        }
    } else {
        if ($is_ajax) {
            $error_msg = '';
            if ($rating < 1 || $rating > 5) {
                $error_msg = 'Vui lòng chọn số sao đánh giá.';
            } elseif (empty($comment)) {
                $error_msg = 'Vui lòng nhập nhận xét.';
            } elseif ($user_review) {
                $error_msg = 'Bạn đã đánh giá sản phẩm này rồi.';
            } else {
                $error_msg = 'Vui lòng điền đầy đủ thông tin đánh giá.';
            }
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => $error_msg
            ]);
            exit;
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- Product Detail Header -->
<section class="bg-primary text-white py-3">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="index.php" class="text-white-50">Trang chủ</a></li>
                <li class="breadcrumb-item"><a href="equipment.php" class="text-white-50">Shop</a></li>
                <li class="breadcrumb-item"><a href="equipment.php#<?php echo $product['category_slug']; ?>" class="text-white-50"><?php echo htmlspecialchars($product['category_name']); ?></a></li>
                <li class="breadcrumb-item active text-white" aria-current="page"><?php echo htmlspecialchars($product['name']); ?></li>
            </ol>
        </nav>
    </div>
</section>

<!-- Product Detail -->
<section class="py-4">
    <div class="container">
        <?php if (isset($_GET['review_success'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle me-2"></i>Cảm ơn bạn đã đánh giá sản phẩm!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row g-3">
            <!-- Product Images -->
            <div class="col-lg-5">
                <div class="product-image-wrapper position-relative">
                    <img src="<?php echo htmlspecialchars($product['image']); ?>" 
                         alt="<?php echo htmlspecialchars($product['name']); ?>" 
                         class="img-fluid rounded-3 shadow-sm main-product-image w-100">
                    
                    <!-- Badges -->
                    <?php if ($product['featured'] || $product['sale_price']): ?>
                        <div class="product-badges position-absolute top-0 start-0 m-2">
                            <?php if ($product['featured']): ?>
                                <span class="badge bg-danger mb-1 d-block">Bán chạy</span>
                            <?php endif; ?>
                            <?php if ($product['sale_price']): ?>
                                <span class="badge bg-success d-block">Giảm giá</span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Product Info -->
            <div class="col-lg-7">
                <div class="product-info">
                    <!-- Brand & Title -->
                    <div class="mb-2">
                        <?php if ($product['brand']): ?>
                            <span class="badge bg-primary me-2"><?php echo htmlspecialchars($product['brand']); ?></span>
                        <?php endif; ?>
                        <h1 class="h4 fw-bold d-inline"><?php echo htmlspecialchars($product['name']); ?></h1>
                    </div>

                    <!-- Rating & Reviews -->
                    <div class="d-flex align-items-center mb-2">
                        <div class="stars me-2">
                            <?php 
                            $rating = isset($product['average_rating']) ? floatval($product['average_rating']) : 0;
                            for ($i = 1; $i <= 5; $i++): 
                            ?>
                                <i class="fas fa-star <?php echo $i <= $rating ? 'text-warning' : 'text-muted'; ?>"></i>
                            <?php endfor; ?>
                        </div>
                        <small class="text-muted">
                            <?php echo number_format($rating, 1); ?> (<?php echo $product['review_count'] ?? 0; ?> đánh giá)
                        </small>
                    </div>

                    <!-- Price -->
                    <div class="price-section p-2 bg-light rounded mb-2">
                        <?php if ($product['sale_price']): ?>
                            <span class="h5 text-danger fw-bold me-2"><?php echo number_format($product['sale_price']); ?>đ</span>
                            <span class="text-muted text-decoration-line-through small"><?php echo number_format($product['price']); ?>đ</span>
                            <span class="badge bg-danger ms-2 small">-<?php echo round((($product['price'] - $product['sale_price']) / $product['price']) * 100); ?>%</span>
                        <?php else: ?>
                            <span class="h5 text-danger fw-bold"><?php echo number_format($product['price']); ?>đ</span>
                        <?php endif; ?>
                    </div>

                    <!-- Stock & Features -->
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <?php if ($product['stock_quantity'] > 0): ?>
                                <?php if ($product['stock_quantity'] <= 5): ?>
                                    <small class="text-warning">
                                        <i class="fas fa-exclamation-triangle me-1"></i>
                                        Còn <?php echo $product['stock_quantity']; ?>
                                    </small>
                                <?php else: ?>
                                    <small class="text-success">
                                        <i class="fas fa-check-circle me-1"></i>Còn hàng
                                    </small>
                                <?php endif; ?>
                            <?php else: ?>
                                <small class="text-danger">
                                    <i class="fas fa-times-circle me-1"></i>Hết hàng
                                </small>
                            <?php endif; ?>
                        </div>
                        <div class="col-6">
                            <small class="text-muted">
                                <i class="fas fa-shield-alt text-success me-1"></i>Bảo hành 12 tháng
                            </small>
                        </div>
                        <div class="col-6">
                            <small class="text-muted">
                                <i class="fas fa-shipping-fast text-info me-1"></i>Miễn phí ship
                            </small>
                        </div>
                        <div class="col-6">
                            <small class="text-muted">
                                <i class="fas fa-undo text-warning me-1"></i>Đổi trả 7 ngày
                            </small>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="d-flex gap-2">
                        <?php if ($product['stock_quantity'] > 0): ?>
                            <button class="btn btn-danger flex-grow-1 add-to-cart" data-product-id="<?php echo $product['id']; ?>">
                                <i class="fas fa-shopping-cart me-2"></i>Thêm vào giỏ
                            </button>
                        <?php else: ?>
                            <button class="btn btn-secondary flex-grow-1" disabled>
                                <i class="fas fa-times me-2"></i>Hết hàng
                            </button>
                        <?php endif; ?>
                        <button class="btn btn-outline-danger">
                            <i class="fas fa-heart"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Description & Reviews -->
        <div class="row mt-3">
            <div class="col-12">
                <ul class="nav nav-tabs" id="productTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="description-tab" data-bs-toggle="tab" data-bs-target="#description" type="button" role="tab">
                            <i class="fas fa-info-circle me-2"></i>Mô tả
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="reviews-tab" data-bs-toggle="tab" data-bs-target="#reviews" type="button" role="tab">
                            <i class="fas fa-star me-2"></i>Đánh giá (<?php echo count($reviews); ?>)
                        </button>
                    </li>
                </ul>
                
                <div class="tab-content border border-top-0 rounded-bottom p-3" id="productTabsContent">
                    <!-- Description Tab -->
                    <div class="tab-pane fade show active" id="description" role="tabpanel">
                        <p class="text-muted mb-0"><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                    </div>
                    
                    <!-- Reviews Tab -->
                    <div class="tab-pane fade" id="reviews" role="tabpanel">
                        <!-- Rating Summary -->
                        <div class="row mb-3">
                            <div class="col-md-4 text-center" id="ratingSummary">
                                <div class="h3 fw-bold text-warning"><?php echo isset($product['average_rating']) ? number_format($product['average_rating'], 1) : '0.0'; ?></div>
                                <div class="stars mb-1" id="averageStars">
                                    <?php 
                                    $avg_rating = isset($product['average_rating']) ? floatval($product['average_rating']) : 0;
                                    for ($i = 1; $i <= 5; $i++): 
                                    ?>
                                        <i class="fas fa-star <?php echo $i <= $avg_rating ? 'text-warning' : 'text-muted'; ?>"></i>
                                    <?php endfor; ?>
                                </div>
                                <small class="text-muted" id="reviewCount"><?php echo $product['review_count'] ?? 0; ?> đánh giá</small>
                            </div>
                            <div class="col-md-8">
                                <?php 
                                $total_reviews = $product['review_count'] ?? 0;
                                for ($i = 5; $i >= 1; $i--): 
                                ?>
                                    <div class="d-flex align-items-center mb-1">
                                        <span class="me-2 small"><?php echo $i; ?>★</span>
                                        <div class="progress flex-grow-1 me-2" style="height: 4px;">
                                            <?php 
                                            $percentage = $total_reviews > 0 ? ($rating_stats[$i] / $total_reviews) * 100 : 0;
                                            ?>
                                            <div class="progress-bar bg-warning" style="width: <?php echo $percentage; ?>%"></div>
                                        </div>
                                        <small class="text-muted"><?php echo $rating_stats[$i]; ?></small>
                                    </div>
                                <?php endfor; ?>
                            </div>
                        </div>

                        <!-- Write Review Button -->
                        <?php if (isLoggedIn() && $can_review): ?>
                            <div class="text-center mb-3">
                                <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#reviewModal">
                                    <i class="fas fa-edit me-2"></i>Viết đánh giá
                                </button>
                            </div>
                        <?php elseif (!isLoggedIn()): ?>
                            <div class="text-center mb-3">
                                <a href="login.php" class="btn btn-outline-warning btn-sm">
                                    <i class="fas fa-sign-in-alt me-2"></i>Đăng nhập để đánh giá
                                </a>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Setup Reviews Button (for debugging) -->
                        <?php if (isAdmin()): ?>
                            <div class="text-center mb-3">
                                <a href="fix-reviews-setup.php" class="btn btn-outline-secondary btn-sm">
                                    <i class="fas fa-wrench me-2"></i>Setup Reviews (Admin)
                                </a>
                            </div>
                        <?php endif; ?>

                        <!-- Reviews List -->
                        <?php if (empty($reviews)): ?>
                            <div class="text-center py-3" id="emptyReviews">
                                <i class="fas fa-comments fa-2x text-muted mb-2"></i>
                                <p class="text-muted small">Chưa có đánh giá nào cho sản phẩm này.</p>
                            </div>
                        <?php else: ?>
                            <div class="reviews-list" id="reviewsList">
                                <?php foreach ($reviews as $review): ?>
                                    <div class="review-item border-bottom py-2">
                                        <div class="d-flex justify-content-between mb-1">
                                            <div>
                                                <strong class="small"><?php echo htmlspecialchars($review['user_name']); ?></strong>
                                                <div class="stars small">
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <i class="fas fa-star <?php echo $i <= $review['rating'] ? 'text-warning' : 'text-muted'; ?>"></i>
                                                    <?php endfor; ?>
                                                </div>
                                            </div>
                                            <small class="text-muted">
                                                <?php echo date('d/m/Y', strtotime($review['created_at'])); ?>
                                            </small>
                                        </div>
                                        
                                        <?php if ($review['title']): ?>
                                            <h6 class="fw-bold small"><?php echo htmlspecialchars($review['title']); ?></h6>
                                        <?php endif; ?>
                                        
                                        <p class="mb-1 small"><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                                        
                                        <button class="btn btn-sm btn-outline-secondary btn-xs">
                                            <i class="fas fa-thumbs-up me-1"></i>Hữu ích (<?php echo $review['helpful_count']; ?>)
                                        </button>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Review Modal -->
<?php if (isLoggedIn() && $can_review): ?>
<div class="modal fade" id="reviewModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title">
                    <i class="fas fa-star text-warning me-2"></i>Đánh giá sản phẩm
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
                            <form method="post" id="reviewForm">
                                <div class="modal-body">
                                    <!-- Product Info -->
                                    <div class="d-flex align-items-center mb-3 p-2 bg-light rounded">
                                        <img src="<?php echo htmlspecialchars($product['image']); ?>" 
                                             alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                             class="rounded me-2" style="width: 40px; height: 40px; object-fit: cover;">
                                        <div>
                                            <small class="fw-bold"><?php echo htmlspecialchars($product['name']); ?></small>
                                        </div>
                                    </div>
                                    
                                    <!-- Rating Input -->
                                    <div class="mb-3">
                                        <label class="form-label small">Đánh giá *</label>
                                        <div class="rating-input text-center">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <input type="radio" name="rating" value="<?php echo $i; ?>" id="star<?php echo $i; ?>" required>
                                                <label for="star<?php echo $i; ?>" class="star-label">
                                                    <i class="fas fa-star"></i>
                                                </label>
                                            <?php endfor; ?>
                                        </div>
                                        <div class="text-center">
                                            <small class="text-muted rating-text">Chọn số sao</small>
                                        </div>
                                    </div>
                                    
                                    <!-- Title Input -->
                                    <div class="mb-3">
                                        <label class="form-label small">Tiêu đề</label>
                                        <input type="text" name="title" class="form-control form-control-sm" placeholder="Tóm tắt đánh giá...">
                                    </div>
                                    
                                    <!-- Comment Input -->
                                    <div class="mb-3">
                                        <label class="form-label small">Nhận xét *</label>
                                        <textarea name="comment" class="form-control form-control-sm" rows="3" 
                                                  placeholder="Chia sẻ trải nghiệm của bạn..." required></textarea>
                                    </div>
                                </div>
                                <div class="modal-footer border-0 pt-0">
                                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Hủy</button>
                                    <button type="submit" name="submit_review" class="btn btn-warning btn-sm">
                                        <i class="fas fa-paper-plane me-2"></i>Gửi đánh giá
                                    </button>
                                </div>
                            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<script src="assets/js/equipment.js"></script>

<script>
// Enhanced rating input interaction
document.addEventListener('DOMContentLoaded', function() {
    const ratingInputs = document.querySelectorAll('.rating-input input[type="radio"]');
    const ratingText = document.querySelector('.rating-text small');
    
    const ratingTexts = {
        1: 'Rất không hài lòng 😞',
        2: 'Không hài lòng 😐', 
        3: 'Bình thường 🙂',
        4: 'Hài lòng 😊',
        5: 'Rất hài lòng 🤩'
    };
    
    ratingInputs.forEach(input => {
        input.addEventListener('change', function() {
            if (ratingText) {
                ratingText.textContent = ratingTexts[this.value];
                ratingText.style.color = '#28a745';
                ratingText.style.fontWeight = 'bold';
            }
        });
    });
    
    // Add to cart with loading animation
    const addToCartBtns = document.querySelectorAll('.add-to-cart');
    addToCartBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            // Add loading state
            this.classList.add('loading');
            this.disabled = true;
            
            // Simulate API call
            setTimeout(() => {
                this.classList.remove('loading');
                this.disabled = false;
                this.innerHTML = '<i class="fas fa-check me-2"></i>Đã thêm vào giỏ';
                this.classList.remove('btn-danger');
                this.classList.add('btn-success');
                
                // Reset after 2 seconds
                setTimeout(() => {
                    this.innerHTML = '<i class="fas fa-shopping-cart me-2"></i>Thêm vào giỏ hàng';
                    this.classList.remove('btn-success');
                    this.classList.add('btn-danger');
                }, 2000);
            }, 1000);
        });
    });
    
    // Smooth scroll to reviews
    const reviewLinks = document.querySelectorAll('a[href*="#reviews"]');
    reviewLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            document.querySelector('.reviews-list').scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        });
    });
    
    // Image zoom effect
    const productImage = document.querySelector('.main-product-image');
    if (productImage) {
        productImage.addEventListener('click', function() {
            // Create modal for image zoom
            const modal = document.createElement('div');
            modal.className = 'modal fade';
            modal.innerHTML = `
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content border-0">
                        <div class="modal-body p-0">
                            <img src="${this.src}" class="img-fluid w-100" alt="${this.alt}">
                        </div>
                        <button type="button" class="btn-close position-absolute top-0 end-0 m-3" data-bs-dismiss="modal" style="z-index: 1050;"></button>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
            
            const bsModal = new bootstrap.Modal(modal);
            bsModal.show();
            
            modal.addEventListener('hidden.bs.modal', function() {
                document.body.removeChild(modal);
            });
        });
        
        // Add cursor pointer
        productImage.style.cursor = 'pointer';
        productImage.title = 'Click để phóng to';
    }
    
    // Helpful button interaction
    const helpfulBtns = document.querySelectorAll('.review-actions button');
    helpfulBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            if (this.innerHTML.includes('thumbs-up')) {
                const currentCount = parseInt(this.textContent.match(/\d+/)[0]);
                this.innerHTML = `<i class="fas fa-thumbs-up me-1"></i>Hữu ích (${currentCount + 1})`;
                this.classList.remove('btn-outline-primary');
                this.classList.add('btn-primary');
                this.disabled = true;
            }
        });
    });
    
    // Form validation enhancement
    const reviewForm = document.querySelector('#reviewForm');
    if (reviewForm) {
        reviewForm.addEventListener('submit', function(e) {
            e.preventDefault(); // Prevent default form submission
            
            const comment = this.querySelector('textarea[name="comment"]').value.trim();
            const rating = this.querySelector('input[name="rating"]:checked');
            
            if (!rating) {
                alert('Vui lòng chọn số sao đánh giá!');
                return;
            }
            
            if (comment.length < 10) {
                alert('Nhận xét phải có ít nhất 10 ký tự!');
                return;
            }
            
            // Add loading to submit button
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<div class="spinner-border spinner-border-sm me-2"></div>Đang gửi...';
            submitBtn.disabled = true;
            
            // Prepare form data
            const formData = new FormData(this);
            formData.append('submit_review', '1');
            
            // Send AJAX request
            fetch(window.location.href, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                // Check if response is ok
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                // Check if response is JSON
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    // If not JSON, it might be a redirect or HTML error page
                    return response.text().then(text => {
                        console.log('Non-JSON response:', text);
                        throw new Error('Server returned non-JSON response');
                    });
                }
                
                return response.json();
            })
            .then(data => {
                console.log('Response data:', data); // Debug log
                
                if (data.success) {
                    // Show success message
                    showSuccessMessage(data.message);
                    
                    // Add new review to the list
                    addNewReviewToList(data.review);
                    
                    // Update rating summary
                    updateRatingSummary(data.product);
                    
                    // Close modal and reset form
                    const modal = bootstrap.Modal.getInstance(document.getElementById('reviewModal'));
                    modal.hide();
                    this.reset();
                    
                    // Switch to reviews tab to show the new review
                    const reviewsTab = document.getElementById('reviews-tab');
                    if (reviewsTab) {
                        const tab = new bootstrap.Tab(reviewsTab);
                        tab.show();
                    }
                    
                    // Reset rating text
                    const ratingText = document.querySelector('.rating-text');
                    if (ratingText) {
                        ratingText.textContent = 'Chọn số sao';
                        ratingText.style.color = '';
                        ratingText.style.fontWeight = '';
                    }
                    
                    // Hide write review button (user can only review once)
                    const writeReviewBtn = document.querySelector('[data-bs-target="#reviewModal"]');
                    if (writeReviewBtn) {
                        writeReviewBtn.style.display = 'none';
                    }
                    
                } else {
                    // Check if there's a redirect URL for setup
                    if (data.redirect) {
                        if (confirm(data.message + '\n\nBạn có muốn chuyển đến trang thiết lập không?')) {
                            window.location.href = data.redirect;
                        }
                    } else {
                        alert(data.message || 'Có lỗi xảy ra. Vui lòng thử lại.');
                    }
                }
            })
            .catch(error => {
                console.error('Error details:', error);
                alert('Có lỗi xảy ra khi gửi đánh giá. Vui lòng thử lại hoặc tải lại trang.');
            })
            .finally(() => {
                // Reset button
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        });
    }
    
    // Function to show success message
    function showSuccessMessage(message) {
        // Remove existing alerts
        const existingAlerts = document.querySelectorAll('.alert-success');
        existingAlerts.forEach(alert => alert.remove());
        
        // Create new alert
        const alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-success alert-dismissible fade show';
        alertDiv.innerHTML = `
            <i class="fas fa-check-circle me-2"></i>${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        // Insert at the top of the container
        const container = document.querySelector('.container');
        const firstChild = container.firstElementChild;
        container.insertBefore(alertDiv, firstChild.nextSibling);
        
        // Auto-hide after 5 seconds
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 5000);
    }
    
    // Function to add new review to the list
    function addNewReviewToList(review) {
        const reviewsList = document.getElementById('reviewsList');
        const emptyReviews = document.getElementById('emptyReviews');
        
        // Hide empty message if it exists
        if (emptyReviews) {
            emptyReviews.style.display = 'none';
        }
        
        // Create new review element
        const reviewElement = document.createElement('div');
        reviewElement.className = 'review-item border-bottom py-2';
        reviewElement.style.opacity = '0';
        reviewElement.style.transform = 'translateY(-20px)';
        
        // Generate stars HTML
        let starsHtml = '';
        for (let i = 1; i <= 5; i++) {
            starsHtml += `<i class="fas fa-star ${i <= review.rating ? 'text-warning' : 'text-muted'}"></i>`;
        }
        
        // Format date
        const date = new Date(review.created_at);
        const formattedDate = date.toLocaleDateString('vi-VN');
        
        reviewElement.innerHTML = `
            <div class="d-flex justify-content-between mb-1">
                <div>
                    <strong class="small">${escapeHtml(review.user_name)}</strong>
                    <div class="stars small">
                        ${starsHtml}
                    </div>
                </div>
                <small class="text-muted">${formattedDate}</small>
            </div>
            ${review.title ? `<h6 class="fw-bold small">${escapeHtml(review.title)}</h6>` : ''}
            <p class="mb-1 small">${escapeHtml(review.comment).replace(/\n/g, '<br>')}</p>
            <button class="btn btn-sm btn-outline-secondary btn-xs">
                <i class="fas fa-thumbs-up me-1"></i>Hữu ích (0)
            </button>
        `;
        
        // Add to list
        if (reviewsList) {
            reviewsList.insertBefore(reviewElement, reviewsList.firstChild);
        } else {
            // Create reviews list if it doesn't exist
            const reviewsContainer = emptyReviews.parentNode;
            const newReviewsList = document.createElement('div');
            newReviewsList.className = 'reviews-list';
            newReviewsList.id = 'reviewsList';
            newReviewsList.appendChild(reviewElement);
            reviewsContainer.appendChild(newReviewsList);
        }
        
        // Animate in
        setTimeout(() => {
            reviewElement.style.transition = 'all 0.5s ease';
            reviewElement.style.opacity = '1';
            reviewElement.style.transform = 'translateY(0)';
        }, 100);
        
        // Highlight new review temporarily
        setTimeout(() => {
            reviewElement.classList.add('new-review');
            setTimeout(() => {
                reviewElement.classList.remove('new-review');
            }, 3000);
        }, 600);
    }
    
    // Function to update rating summary
    function updateRatingSummary(productData) {
        const ratingSummary = document.getElementById('ratingSummary');
        if (ratingSummary) {
            // Add animation class
            ratingSummary.classList.add('rating-update');
            setTimeout(() => {
                ratingSummary.classList.remove('rating-update');
            }, 600);
            
            const ratingValue = ratingSummary.querySelector('.h3');
            const averageStars = document.getElementById('averageStars');
            const reviewCount = document.getElementById('reviewCount');
            
            if (ratingValue) {
                ratingValue.textContent = parseFloat(productData.average_rating).toFixed(1);
            }
            
            if (averageStars) {
                let starsHtml = '';
                const avgRating = parseFloat(productData.average_rating);
                for (let i = 1; i <= 5; i++) {
                    starsHtml += `<i class="fas fa-star ${i <= avgRating ? 'text-warning' : 'text-muted'}"></i>`;
                }
                averageStars.innerHTML = starsHtml;
            }
            
            if (reviewCount) {
                reviewCount.textContent = `${productData.review_count} đánh giá`;
            }
        }
        
        // Update tab title with animation
        const reviewsTab = document.getElementById('reviews-tab');
        if (reviewsTab) {
            reviewsTab.style.transform = 'scale(1.05)';
            reviewsTab.innerHTML = `<i class="fas fa-star me-2"></i>Đánh giá (${productData.review_count})`;
            setTimeout(() => {
                reviewsTab.style.transform = 'scale(1)';
            }, 200);
        }
    }
    
    // Helper function to escape HTML
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // Auto-resize textarea
    const textareas = document.querySelectorAll('textarea');
    textareas.forEach(textarea => {
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = this.scrollHeight + 'px';
        });
    });
});

// Intersection Observer for animations
const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
};

const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.style.opacity = '1';
            entry.target.style.transform = 'translateY(0)';
        }
    });
}, observerOptions);

// Observe elements for animation
document.addEventListener('DOMContentLoaded', function() {
    const animateElements = document.querySelectorAll('.card, .alert, .product-info > *');
    animateElements.forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(20px)';
        el.style.transition = 'all 0.6s ease';
        observer.observe(el);
    });
});
</script>

<style>
/* Clean and compact styles */
.main-product-image {
    height: 350px;
    object-fit: cover;
    cursor: pointer;
    transition: transform 0.3s ease;
}

.main-product-image:hover {
    transform: scale(1.02);
}

.product-image-wrapper {
    position: relative;
}

.product-badges .badge {
    font-size: 0.75rem;
}

.product-info {
    padding: 0.5rem 0;
}

.price-section {
    border-left: 3px solid #dc3545;
}

.rating-input {
    display: flex;
    justify-content: center;
    gap: 0.25rem;
}

.rating-input input[type="radio"] {
    display: none;
}

.star-label {
    font-size: 1.25rem;
    color: #ddd;
    cursor: pointer;
    transition: all 0.2s ease;
}

.rating-input input[type="radio"]:checked ~ .star-label,
.rating-input .star-label:hover,
.rating-input .star-label:hover ~ .star-label {
    color: #ffc107;
}

.review-item:last-child {
    border-bottom: none !important;
}

.stars {
    font-size: 0.85rem;
}

.btn-xs {
    padding: 0.125rem 0.5rem;
    font-size: 0.75rem;
}

.nav-tabs .nav-link {
    border-radius: 0.375rem 0.375rem 0 0;
    font-size: 0.9rem;
    padding: 0.5rem 1rem;
}

.tab-content {
    min-height: 200px;
}

/* Compact spacing */
.py-2 {
    padding-top: 0.5rem !important;
    padding-bottom: 0.5rem !important;
}

.mb-1 {
    margin-bottom: 0.25rem !important;
}

.mb-2 {
    margin-bottom: 0.5rem !important;
}

.mb-3 {
    margin-bottom: 1rem !important;
}

/* Progress bars */
.progress {
    border-radius: 10px;
    background-color: #f8f9fa;
}

.progress-bar {
    border-radius: 10px;
}

/* Modal improvements */
.modal-content {
    border-radius: 10px;
    border: none;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
}

.modal-header {
    background: linear-gradient(135deg, #ffc107 0%, #ff8f00 100%);
    color: white;
    border-radius: 10px 10px 0 0;
}

.modal-header .btn-close {
    filter: invert(1);
}

/* Hover effects */
.btn:hover {
    transform: translateY(-1px);
    transition: all 0.2s ease;
}

.card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transition: box-shadow 0.3s ease;
}

/* Mobile responsive */
@media (max-width: 768px) {
    .main-product-image {
        height: 250px;
    }
    
    .h4 {
        font-size: 1.1rem;
    }
    
    .h5 {
        font-size: 1rem;
    }
    
    .product-info {
        padding: 0.25rem 0;
    }
    
    .nav-tabs .nav-link {
        font-size: 0.8rem;
        padding: 0.375rem 0.75rem;
    }
    
    .tab-content {
        min-height: 150px;
    }
}

/* Animation for elements */
.fade-in {
    opacity: 0;
    transform: translateY(20px);
    transition: all 0.6s ease;
}

.fade-in.show {
    opacity: 1;
    transform: translateY(0);
}

/* Animation for new reviews */
.review-item {
    transition: all 0.3s ease;
}

.review-item.new-review {
    background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
    border-left: 3px solid #ffc107;
    animation: slideInFromTop 0.5s ease-out;
}

@keyframes slideInFromTop {
    0% {
        opacity: 0;
        transform: translateY(-20px);
    }
    100% {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Success message animation */
.alert-success {
    animation: slideInFromTop 0.5s ease-out;
}

/* Rating summary update animation */
.rating-update {
    animation: pulse 0.6s ease-in-out;
}

@keyframes pulse {
    0% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.05);
    }
    100% {
        transform: scale(1);
    }
}

/* Modal improvements */
.modal-content {
    border-radius: 10px;
    border: none;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
}

.modal-header {
    background: linear-gradient(135deg, #ffc107 0%, #ff8f00 100%);
    color: white;
    border-radius: 10px 10px 0 0;
}

.modal-header .btn-close {
    filter: invert(1);
}

/* Loading state for form */
.form-loading {
    position: relative;
    pointer-events: none;
}

.form-loading::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255,255,255,0.8);
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
}

/* Enhanced button states */
.btn:disabled {
    opacity: 0.7;
    cursor: not-allowed;
}

.btn.loading {
    position: relative;
    color: transparent;
}

.btn.loading::after {
    content: '';
    position: absolute;
    width: 16px;
    height: 16px;
    top: 50%;
    left: 50%;
    margin-left: -8px;
    margin-top: -8px;
    border: 2px solid transparent;
    border-top-color: currentColor;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>