<?php
require_once __DIR__ . '/../includes/functions.php';

// Auto-add category column
$chk = $mysqli->query("SHOW COLUMNS FROM courts LIKE 'category'");
if ($chk && $chk->num_rows === 0) {
    $mysqli->query("ALTER TABLE courts ADD COLUMN category VARCHAR(100) DEFAULT '' COMMENT 'Comma-separated: premium,popular,new,promo'");
}

requireAdmin();
$isAdminPage = true;
$message = '';
$msgType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Xóa sân
    if (isset($_POST['delete_id'])) {
        $del = intval($_POST['delete_id']);
        $stmt = $mysqli->prepare('DELETE FROM courts WHERE id = ?');
        $stmt->bind_param('i', $del);
        $stmt->execute();
        $message = 'Đã xóa sân.';

    // Toggle nổi bật nhanh
    } elseif (isset($_POST['toggle_featured'])) {
        $tid = intval($_POST['toggle_id']);
        $tval = intval($_POST['toggle_val']);
        $stmt = $mysqli->prepare('UPDATE courts SET featured = ? WHERE id = ?');
        $stmt->bind_param('ii', $tval, $tid);
        $stmt->execute();
        $message = $tval ? 'Đã đánh dấu nổi bật.' : 'Đã bỏ đánh dấu nổi bật.';

    // Thêm / Sửa sân
    } else {
        $name        = trim($_POST['name']        ?? '');
        $location    = trim($_POST['location']    ?? '');
        $price       = intval($_POST['price']     ?? 0);
        $image       = trim($_POST['image']       ?? '');
        $description = trim($_POST['description'] ?? '');
        $featured    = isset($_POST['featured']) ? 1 : 0;
        $categories  = $_POST['categories'] ?? [];
        $category    = implode(',', array_filter($categories, fn($c) => in_array($c, ['premium','popular','new','promo'])));

        if ($name && $location && $price) {
            if (!empty($_POST['edit_id'])) {
                $edit_id = intval($_POST['edit_id']);
                $stmt = $mysqli->prepare('UPDATE courts SET name=?, location=?, price_per_hour=?, cover_image=?, description=?, featured=?, category=? WHERE id=?');
                $stmt->bind_param('ssissisi', $name, $location, $price, $image, $description, $featured, $category, $edit_id);
                $stmt->execute();
                $message = 'Cập nhật sân thành công.';
            } else {
                $stmt = $mysqli->prepare('INSERT INTO courts (name, location, price_per_hour, cover_image, description, featured, category) VALUES (?,?,?,?,?,?,?)');
                $stmt->bind_param('ssissis', $name, $location, $price, $image, $description, $featured, $category);
                $stmt->execute();
                $message = 'Thêm sân mới thành công.';
            }
        } else {
            $message = 'Vui lòng điền đầy đủ thông tin bắt buộc.';
            $msgType = 'danger';
        }
    }
}

$courts = getAllCourts();
$featuredCount = count(array_filter($courts, fn($c) => !empty($c['featured'])));

require_once __DIR__ . '/../includes/header.php';
?>

<style>
.featured-star { color: #ffc107; font-size: 1.1rem; }
.badge-featured { background: linear-gradient(135deg, #ffc107, #fd7e14); color: #fff; font-size: .72rem; padding: 3px 8px; border-radius: 20px; font-weight: 700; }
.toggle-featured-btn { cursor: pointer; border: none; background: none; padding: 0; }
.court-img-thumb { width: 48px; height: 36px; object-fit: cover; border-radius: 6px; }
</style>

<!-- Header -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow-sm border-0">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="card-title mb-1">Quản lý sân</h2>
                    <p class="text-muted mb-0">
                        Tổng: <strong><?php echo count($courts); ?></strong> sân &nbsp;·&nbsp;
                        <i class="fas fa-star text-warning me-1"></i>Nổi bật: <strong><?php echo $featuredCount; ?></strong> sân
                    </p>
                </div>
                <a href="dashboard.php" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-arrow-left me-1"></i>Dashboard
                </a>
            </div>
        </div>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $msgType; ?> alert-dismissible fade show">
        <?php echo escape($message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row gy-4">
    <!-- Form thêm/sửa -->
    <div class="col-lg-4">
        <div class="card shadow-sm border-0 sticky-top" style="top: 80px;">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0 fw-bold" id="formTitle">
                    <i class="fas fa-plus me-2"></i>Thêm sân mới
                </h5>
            </div>
            <div class="card-body">
                <form method="post" id="courtForm">
                    <input type="hidden" name="edit_id" id="edit_id" value="">

                    <div class="mb-3">
                        <label class="form-label fw-bold">Tên sân <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="court_name" class="form-control" required placeholder="VD: Sân cầu lông ABC">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Phường / Xã <span class="text-danger">*</span></label>
                        <input list="hanoiWards" type="text" name="location" id="court_location" class="form-control" placeholder="VD: Trung Hòa, Cầu Giấy" required>
                        <datalist id="hanoiWards">
                            <?php foreach (getLocations() as $loc): ?>
                                <option value="<?php echo escape($loc); ?>"></option>
                            <?php endforeach; ?>
                        </datalist>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Giá mỗi giờ (VNĐ) <span class="text-danger">*</span></label>
                        <input type="number" name="price" id="court_price" class="form-control" min="0" step="1000" required placeholder="VD: 120000">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">URL ảnh bìa</label>
                        <input type="text" name="image" id="court_image" class="form-control" placeholder="https://...">
                        <div id="imagePreview" class="mt-2 d-none">
                            <img id="previewImg" src="" alt="Preview" style="width:100%;height:120px;object-fit:cover;border-radius:8px;">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Mô tả</label>
                        <textarea name="description" id="court_description" class="form-control" rows="3" placeholder="Mô tả về sân..."></textarea>
                    </div>

                    <!-- CHECKBOX NỔI BẬT -->
                    <div class="mb-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="featured" id="court_featured" style="width:2.5rem;height:1.3rem;">
                            <label class="form-check-label fw-bold ms-2" for="court_featured">
                                <i class="fas fa-star text-warning me-1"></i>Đánh dấu là sân nổi bật
                            </label>
                        </div>
                        <small class="text-muted">Sân nổi bật sẽ hiển thị trong trang "Nổi bật"</small>
                    </div>

                    <!-- DANH MỤC -->
                    <div class="mb-4">
                        <label class="form-label fw-bold">
                            <i class="fas fa-tags text-primary me-1"></i>Danh mục nổi bật
                        </label>
                        <div class="row g-2">
                            <div class="col-6">
                                <div class="form-check border rounded p-2">
                                    <input class="form-check-input" type="checkbox" name="categories[]" value="premium" id="cat_premium">
                                    <label class="form-check-label small fw-bold" for="cat_premium">
                                        👑 Sân cao cấp
                                    </label>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-check border rounded p-2">
                                    <input class="form-check-input" type="checkbox" name="categories[]" value="popular" id="cat_popular">
                                    <label class="form-check-label small fw-bold" for="cat_popular">
                                        🔥 Phổ biến nhất
                                    </label>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-check border rounded p-2">
                                    <input class="form-check-input" type="checkbox" name="categories[]" value="new" id="cat_new">
                                    <label class="form-check-label small fw-bold" for="cat_new">
                                        ✨ Mới nhất
                                    </label>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-check border rounded p-2">
                                    <input class="form-check-input" type="checkbox" name="categories[]" value="promo" id="cat_promo">
                                    <label class="form-check-label small fw-bold" for="cat_promo">
                                        % Khuyến mãi
                                    </label>
                                </div>
                            </div>
                        </div>
                        <small class="text-muted">Có thể chọn nhiều danh mục</small>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            <i class="fas fa-save me-2"></i>Lưu sân
                        </button>
                        <button type="button" class="btn btn-outline-secondary" onclick="resetForm()">
                            <i class="fas fa-times me-2"></i>Hủy / Tạo mới
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Danh sách sân -->
    <div class="col-lg-8">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold">Danh sách sân (<?php echo count($courts); ?>)</h5>
                <div class="d-flex gap-2">
                    <input type="text" id="tableSearch" class="form-control form-control-sm" placeholder="Tìm nhanh..." style="width:180px;">
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="courtsTable">
                        <thead class="table-light">
                            <tr>
                                <th style="width:50px;">#</th>
                                <th style="width:60px;">Ảnh</th>
                                <th>Tên sân</th>
                                <th>Khu vực</th>
                                <th>Giá/h</th>
                                <th style="width:90px;">Nổi bật</th>
                                <th>Danh mục</th>
                                <th>Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($courts as $court):
                                $isFeatured = !empty($court['featured']);
                            ?>
                                <tr id="row-<?php echo $court['id']; ?>">
                                    <td class="text-muted small"><?php echo $court['id']; ?></td>
                                    <td>
                                        <?php if ($court['cover_image']): ?>
                                            <img src="<?php echo escape($court['cover_image']); ?>" class="court-img-thumb" alt="">
                                        <?php else: ?>
                                            <div class="court-img-thumb bg-light d-flex align-items-center justify-content-center">
                                                <i class="fas fa-image text-muted" style="font-size:.8rem;"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="fw-bold"><?php echo escape($court['name']); ?></div>
                                        <?php if ($isFeatured): ?>
                                            <span class="badge-featured"><i class="fas fa-star me-1"></i>Nổi bật</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-muted small"><?php echo escape($court['location']); ?></td>
                                    <td class="text-success fw-bold small"><?php echo number_format($court['price_per_hour']); ?>đ</td>
                                    <td class="text-center">
                                        <!-- Toggle nổi bật nhanh -->
                                        <form method="post" class="d-inline">
                                            <input type="hidden" name="toggle_featured" value="1">
                                            <input type="hidden" name="toggle_id" value="<?php echo $court['id']; ?>">
                                            <input type="hidden" name="toggle_val" value="<?php echo $isFeatured ? 0 : 1; ?>">
                                            <button type="submit" class="toggle-featured-btn" title="<?php echo $isFeatured ? 'Bỏ nổi bật' : 'Đặt nổi bật'; ?>">
                                                <i class="fas fa-star <?php echo $isFeatured ? 'featured-star' : 'text-muted'; ?> fa-lg"></i>
                                            </button>
                                        </form>
                                    </td>
                                    <td>
                                        <?php
                                        $cats = array_filter(explode(',', $court['category'] ?? ''));
                                        $catBadges = ['premium'=>['bg-warning text-dark','👑'], 'popular'=>['bg-danger text-white','🔥'], 'new'=>['bg-info text-white','✨'], 'promo'=>['bg-success text-white','%']];
                                        foreach ($cats as $c):
                                            if (isset($catBadges[$c])):
                                        ?>
                                            <span class="badge <?php echo $catBadges[$c][0]; ?> me-1"><?php echo $catBadges[$c][1]; ?></span>
                                        <?php endif; endforeach; ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary me-1 editCourtBtn"
                                            data-id="<?php echo $court['id']; ?>"
                                            data-name="<?php echo escape($court['name']); ?>"
                                            data-location="<?php echo escape($court['location']); ?>"
                                            data-price="<?php echo $court['price_per_hour']; ?>"
                                            data-image="<?php echo escape($court['cover_image']); ?>"
                                            data-description="<?php echo escape($court['description']); ?>"
                                            data-featured="<?php echo $isFeatured ? '1' : '0'; ?>"
                                            data-category="<?php echo escape($court['category'] ?? ''); ?>">
                                            <i class="fas fa-edit"></i> Sửa
                                        </button>
                                        <form method="post" class="d-inline" onsubmit="return confirm('Xóa sân này?');">
                                            <input type="hidden" name="delete_id" value="<?php echo $court['id']; ?>">
                                            <button class="btn btn-sm btn-outline-danger">
                                                <i class="fas fa-trash"></i> Xóa
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<script>
// Điền form khi click Sửa
document.querySelectorAll('.editCourtBtn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.getElementById('edit_id').value           = btn.dataset.id;
        document.getElementById('court_name').value        = btn.dataset.name;
        document.getElementById('court_location').value    = btn.dataset.location;
        document.getElementById('court_price').value       = btn.dataset.price;
        document.getElementById('court_image').value       = btn.dataset.image;
        document.getElementById('court_description').value = btn.dataset.description;
        document.getElementById('court_featured').checked  = btn.dataset.featured === '1';

        // Populate category checkboxes
        const cats = (btn.dataset.category || '').split(',');
        ['premium','popular','new','promo'].forEach(c => {
            const el = document.getElementById('cat_' + c);
            if (el) el.checked = cats.includes(c);
        });

        document.getElementById('formTitle').innerHTML = '<i class="fas fa-edit me-2"></i>Sửa sân: ' + btn.dataset.name;
        document.getElementById('submitBtn').innerHTML  = '<i class="fas fa-save me-2"></i>Cập nhật sân';

        // Preview ảnh
        updatePreview(btn.dataset.image);
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
});

// Reset form
function resetForm() {
    document.getElementById('courtForm').reset();
    document.getElementById('edit_id').value = '';
    document.getElementById('formTitle').innerHTML = '<i class="fas fa-plus me-2"></i>Thêm sân mới';
    document.getElementById('submitBtn').innerHTML  = '<i class="fas fa-save me-2"></i>Lưu sân';
    document.getElementById('imagePreview').classList.add('d-none');
    ['premium','popular','new','promo'].forEach(c => {
        const el = document.getElementById('cat_' + c);
        if (el) el.checked = false;
    });
}

// Preview ảnh
document.getElementById('court_image').addEventListener('input', function() {
    updatePreview(this.value);
});

function updatePreview(url) {
    const preview = document.getElementById('imagePreview');
    const img     = document.getElementById('previewImg');
    if (url && url.startsWith('http')) {
        img.src = url;
        preview.classList.remove('d-none');
        img.onerror = () => preview.classList.add('d-none');
    } else {
        preview.classList.add('d-none');
    }
}

// Tìm nhanh trong bảng
document.getElementById('tableSearch').addEventListener('input', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('#courtsTable tbody tr').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
});
</script>
