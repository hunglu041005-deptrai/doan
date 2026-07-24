<?php
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();
$isAdminPage = true;

$message = '';
$error = '';

// Handle review actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $review_id = intval($_POST['review_id'] ?? 0);
        
        switch ($_POST['action']) {
            case 'approve':
                $stmt = $mysqli->prepare('UPDATE product_reviews SET status = "approved" WHERE id = ?');
                $stmt->bind_param('i', $review_id);
                if ($stmt->execute()) {
                    $message = 'Đã duyệt đánh giá thành công!';
                }
                $stmt->close();
                break;
                
            case 'reject':
                $stmt = $mysqli->prepare('UPDATE product_reviews SET status = "rejected" WHERE id = ?');
                $stmt->bind_param('i', $review_id);
                if ($stmt->execute()) {
                    $message = 'Đã từ chối đánh giá!';
                }
                $stmt->close();
                break;
                
            case 'delete':
                $stmt = $mysqli->prepare('DELETE FROM product_reviews WHERE id = ?');
                $stmt->bind_param('i', $review_id);
                if ($stmt->execute()) {
                    $message = 'Đã xóa đánh giá!';
                }
                $stmt->close();
                break;
        }
    }
}

// Get reviews with product and user info
$reviews_query = '
    SELECT r.*, p.name as product_name, u.name as user_name, u.email as user_email
    FROM product_reviews r
    JOIN products p ON r.product_id = p.id
    JOIN users u ON r.user_id = u.id
    ORDER BY r.created_at DESC
';
$reviews_result = $mysqli->query($reviews_query);
$reviews = $reviews_result ? $reviews_result->fetch_all(MYSQLI_ASSOC) : [];

// Get statistics
$stats = [];
$stats['total'] = $mysqli->query('SELECT COUNT(*) as count FROM product_reviews')->fetch_assoc()['count'];
$stats['pending'] = $mysqli->query('SELECT COUNT(*) as count FROM product_reviews WHERE status = "pending"')->fetch_assoc()['count'];
$stats['approved'] = $mysqli->query('SELECT COUNT(*) as count FROM product_reviews WHERE status = "approved"')->fetch_assoc()['count'];
$stats['rejected'] = $mysqli->query('SELECT COUNT(*) as count FROM product_reviews WHERE status = "rejected"')->fetch_assoc()['count'];

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid mt-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-lg" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <div class="card-body text-white py-4">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h1 class="h3 fw-bold mb-2">
                                <i class="fas fa-star me-3"></i>Quản lý đánh giá
                            </h1>
                            <p class="mb-0 opacity-90">Duyệt và quản lý đánh giá sản phẩm từ khách hàng</p>
                        </div>
                        <div class="col-md-4 text-end">
                            <a href="dashboard.php" class="btn btn-light">
                                <i class="fas fa-arrow-left me-2"></i>Dashboard
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Statistics -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="bg-primary bg-opacity-10 rounded-circle p-3 d-inline-flex mb-2">
                        <i class="fas fa-comments text-primary fa-2x"></i>
                    </div>
                    <h4 class="fw-bold"><?php echo $stats['total']; ?></h4>
                    <p class="text-muted mb-0">Tổng đánh giá</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="bg-warning bg-opacity-10 rounded-circle p-3 d-inline-flex mb-2">
                        <i class="fas fa-clock text-warning fa-2x"></i>
                    </div>
                    <h4 class="fw-bold"><?php echo $stats['pending']; ?></h4>
                    <p class="text-muted mb-0">Chờ duyệt</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="bg-success bg-opacity-10 rounded-circle p-3 d-inline-flex mb-2">
                        <i class="fas fa-check text-success fa-2x"></i>
                    </div>
                    <h4 class="fw-bold"><?php echo $stats['approved']; ?></h4>
                    <p class="text-muted mb-0">Đã duyệt</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="bg-danger bg-opacity-10 rounded-circle p-3 d-inline-flex mb-2">
                        <i class="fas fa-times text-danger fa-2x"></i>
                    </div>
                    <h4 class="fw-bold"><?php echo $stats['rejected']; ?></h4>
                    <p class="text-muted mb-0">Từ chối</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Reviews List -->
    <div class="card shadow-sm">
        <div class="card-header">
            <h5 class="mb-0">Danh sách đánh giá</h5>
        </div>
        <div class="card-body">
            <?php if (empty($reviews)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-comments fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">Chưa có đánh giá nào</h5>
                    <p class="text-muted">Đánh giá từ khách hàng sẽ hiển thị ở đây</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Sản phẩm</th>
                                <th>Khách hàng</th>
                                <th>Đánh giá</th>
                                <th>Nội dung</th>
                                <th>Trạng thái</th>
                                <th>Ngày tạo</th>
                                <th>Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reviews as $review): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($review['product_name']); ?></strong>
                                    </td>
                                    <td>
                                        <div>
                                            <strong><?php echo htmlspecialchars($review['user_name']); ?></strong><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($review['user_email']); ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="stars me-2">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <i class="fas fa-star <?php echo $i <= $review['rating'] ? 'text-warning' : 'text-muted'; ?>"></i>
                                                <?php endfor; ?>
                                            </div>
                                            <span class="fw-bold"><?php echo $review['rating']; ?>/5</span>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($review['title']): ?>
                                            <strong><?php echo htmlspecialchars($review['title']); ?></strong><br>
                                        <?php endif; ?>
                                        <small><?php echo htmlspecialchars(substr($review['comment'], 0, 100)); ?>
                                        <?php echo strlen($review['comment']) > 100 ? '...' : ''; ?></small>
                                    </td>
                                    <td>
                                        <?php
                                        $status_class = [
                                            'pending' => 'warning',
                                            'approved' => 'success', 
                                            'rejected' => 'danger'
                                        ];
                                        $status_text = [
                                            'pending' => 'Chờ duyệt',
                                            'approved' => 'Đã duyệt',
                                            'rejected' => 'Từ chối'
                                        ];
                                        ?>
                                        <span class="badge bg-<?php echo $status_class[$review['status']]; ?>">
                                            <?php echo $status_text[$review['status']]; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small><?php echo date('d/m/Y H:i', strtotime($review['created_at'])); ?></small>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <?php if ($review['status'] === 'pending'): ?>
                                                <form method="post" class="d-inline">
                                                    <input type="hidden" name="action" value="approve">
                                                    <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                                    <button type="submit" class="btn btn-success" title="Duyệt">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                </form>
                                                <form method="post" class="d-inline">
                                                    <input type="hidden" name="action" value="reject">
                                                    <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                                    <button type="submit" class="btn btn-warning" title="Từ chối">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            
                                            <button class="btn btn-info" title="Xem chi tiết" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#reviewModal<?php echo $review['id']; ?>">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            
                                            <form method="post" class="d-inline" 
                                                  onsubmit="return confirm('Bạn có chắc muốn xóa đánh giá này?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                                <button type="submit" class="btn btn-danger" title="Xóa">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Review Detail Modals -->
<?php foreach ($reviews as $review): ?>
<div class="modal fade" id="reviewModal<?php echo $review['id']; ?>" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Chi tiết đánh giá</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Thông tin sản phẩm:</h6>
                        <p><strong><?php echo htmlspecialchars($review['product_name']); ?></strong></p>
                        
                        <h6>Khách hàng:</h6>
                        <p>
                            <strong><?php echo htmlspecialchars($review['user_name']); ?></strong><br>
                            <small class="text-muted"><?php echo htmlspecialchars($review['user_email']); ?></small>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <h6>Đánh giá:</h6>
                        <div class="d-flex align-items-center mb-2">
                            <div class="stars me-2">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="fas fa-star <?php echo $i <= $review['rating'] ? 'text-warning' : 'text-muted'; ?> fa-lg"></i>
                                <?php endfor; ?>
                            </div>
                            <span class="h5 mb-0"><?php echo $review['rating']; ?>/5</span>
                        </div>
                        
                        <h6>Trạng thái:</h6>
                        <span class="badge bg-<?php echo $status_class[$review['status']]; ?> fs-6">
                            <?php echo $status_text[$review['status']]; ?>
                        </span>
                    </div>
                </div>
                
                <hr>
                
                <?php if ($review['title']): ?>
                    <h6>Tiêu đề:</h6>
                    <p><strong><?php echo htmlspecialchars($review['title']); ?></strong></p>
                <?php endif; ?>
                
                <h6>Nội dung đánh giá:</h6>
                <p><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                
                <hr>
                
                <div class="row">
                    <div class="col-md-6">
                        <small class="text-muted">
                            <strong>Ngày tạo:</strong> <?php echo date('d/m/Y H:i:s', strtotime($review['created_at'])); ?>
                        </small>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted">
                            <strong>Hữu ích:</strong> <?php echo $review['helpful_count']; ?> lượt
                        </small>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <?php if ($review['status'] === 'pending'): ?>
                    <form method="post" class="d-inline">
                        <input type="hidden" name="action" value="approve">
                        <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-check me-2"></i>Duyệt đánh giá
                        </button>
                    </form>
                    <form method="post" class="d-inline">
                        <input type="hidden" name="action" value="reject">
                        <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-times me-2"></i>Từ chối
                        </button>
                    </form>
                <?php endif; ?>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<style>
.stars {
    font-size: 0.9rem;
}

.table td {
    vertical-align: middle;
}

.btn-group-sm .btn {
    padding: 0.25rem 0.5rem;
}
</style>