<?php
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();
$isAdminPage = true;

// Auto-migrate
$mysqli->query("CREATE TABLE IF NOT EXISTS promotions (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    title       VARCHAR(150) NOT NULL,
    description VARCHAR(255),
    color_from  VARCHAR(20) DEFAULT '#f472b6',
    color_to    VARCHAR(20) DEFAULT '#ef4444',
    text_color  VARCHAR(20) DEFAULT '#fff',
    discount_pct TINYINT DEFAULT 0 COMMENT 'Phần trăm giảm giá (0 = không áp dụng tự động)',
    time_start  TIME DEFAULT NULL COMMENT 'Giờ bắt đầu áp dụng',
    time_end    TIME DEFAULT NULL COMMENT 'Giờ kết thúc áp dụng',
    apply_date  DATE DEFAULT NULL COMMENT 'Ngày cụ thể áp dụng (NULL = mọi ngày)',
    apply_weekend TINYINT DEFAULT 0 COMMENT 'Áp dụng cuối tuần',
    apply_newuser TINYINT DEFAULT 0 COMMENT 'Áp dụng cho thành viên mới',
    status      TINYINT DEFAULT 1,
    sort_order  INT DEFAULT 0,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Thêm cột apply_date nếu chưa có
$chk = $mysqli->query("SHOW COLUMNS FROM promotions LIKE 'apply_date'");
if ($chk && $chk->num_rows === 0) {
    $mysqli->query("ALTER TABLE promotions ADD COLUMN apply_date DATE DEFAULT NULL AFTER time_end");
}

$message = '';
$msgType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_id'])) {
        $d = intval($_POST['delete_id']);
        $mysqli->prepare('DELETE FROM promotions WHERE id=?')->bind_param('i',$d) || null;
        $s = $mysqli->prepare('DELETE FROM promotions WHERE id=?');
        $s->bind_param('i', $d); $s->execute(); $s->close();
        $message = 'Đã xóa ưu đãi.';

    } elseif (isset($_POST['toggle_status'])) {
        $tid = intval($_POST['toggle_id']);
        $tv  = intval($_POST['toggle_val']);
        $s = $mysqli->prepare('UPDATE promotions SET status=? WHERE id=?');
        $s->bind_param('ii', $tv, $tid); $s->execute(); $s->close();
        $message = $tv ? 'Đã kích hoạt.' : 'Đã ẩn ưu đãi.';

    } else {
        $title        = trim($_POST['title']        ?? '');
        $desc         = trim($_POST['description']  ?? '');
        $from         = trim($_POST['color_from']   ?? '#f472b6');
        $to           = trim($_POST['color_to']     ?? '#ef4444');
        $text         = trim($_POST['text_color']   ?? '#fff');
        $disc         = min(100, max(0, intval($_POST['discount_pct'] ?? 0)));
        $tstart       = trim($_POST['time_start']   ?? '') ?: null;
        $tend         = trim($_POST['time_end']      ?? '') ?: null;
        $adate        = trim($_POST['apply_date']   ?? '') ?: null;
        $weekend      = isset($_POST['apply_weekend']) ? 1 : 0;
        $newuser      = isset($_POST['apply_newuser']) ? 1 : 0;
        $sort         = intval($_POST['sort_order'] ?? 0);

        if ($title) {
            if (!empty($_POST['edit_id'])) {
                $eid = intval($_POST['edit_id']);
                $s = $mysqli->prepare('UPDATE promotions SET title=?,description=?,color_from=?,color_to=?,text_color=?,discount_pct=?,time_start=?,time_end=?,apply_date=?,apply_weekend=?,apply_newuser=?,sort_order=? WHERE id=?');
                $s->bind_param('sssssiisssiii', $title,$desc,$from,$to,$text,$disc,$tstart,$tend,$adate,$weekend,$newuser,$sort,$eid);
                $s->execute(); $s->close();
                $message = 'Cập nhật ưu đãi thành công.';
            } else {
                $s = $mysqli->prepare('INSERT INTO promotions (title,description,color_from,color_to,text_color,discount_pct,time_start,time_end,apply_date,apply_weekend,apply_newuser,sort_order) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)');
                $s->bind_param('sssssiisssii', $title,$desc,$from,$to,$text,$disc,$tstart,$tend,$adate,$weekend,$newuser,$sort);
                $s->execute(); $s->close();
                $message = 'Thêm ưu đãi thành công.';
            }
        } else {
            $message = 'Vui lòng nhập tiêu đề ưu đãi.';
            $msgType = 'danger';
        }
    }
}

$promos = $mysqli->query('SELECT * FROM promotions ORDER BY sort_order ASC, id DESC')->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid mt-4">

<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow-sm border-0">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="card-title mb-1">🎁 Quản lý Ưu đãi đặc biệt</h2>
                    <p class="text-muted mb-0">Hiển thị trong sidebar trang Nổi bật · Tổng: <strong><?php echo count($promos); ?></strong> ưu đãi</p>
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
    <!-- Form -->
    <div class="col-lg-4">
        <div class="card shadow-sm border-0 sticky-top" style="top:80px;">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0 fw-bold" id="formTitle">
                    <i class="fas fa-plus me-2"></i>Thêm ưu đãi mới
                </h5>
            </div>
            <div class="card-body">
                <form method="post" id="promoForm">
                    <input type="hidden" name="edit_id" id="edit_id">

                    <div class="mb-3">
                        <label class="form-label fw-bold">Tiêu đề <span class="text-danger">*</span></label>
                        <input type="text" name="title" id="promo_title" class="form-control" required placeholder="VD: Giảm 30% Happy Hour">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Mô tả</label>
                        <input type="text" name="description" id="promo_desc" class="form-control" placeholder="VD: 14:00 - 17:00 hàng ngày">
                    </div>

                    <!-- Màu gradient -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">Màu nền gradient</label>
                        <div class="d-flex gap-2 align-items-center">
                            <div>
                                <small class="text-muted d-block">Màu đầu</small>
                                <input type="color" name="color_from" id="color_from" value="#f472b6" class="form-control form-control-color" style="width:50px;height:38px;">
                            </div>
                            <i class="fas fa-arrow-right text-muted mt-3"></i>
                            <div>
                                <small class="text-muted d-block">Màu cuối</small>
                                <input type="color" name="color_to" id="color_to" value="#ef4444" class="form-control form-control-color" style="width:50px;height:38px;">
                            </div>
                            <div>
                                <small class="text-muted d-block">Màu chữ</small>
                                <input type="color" name="text_color" id="text_color" value="#ffffff" class="form-control form-control-color" style="width:50px;height:38px;">
                            </div>
                            <div id="colorPreview" class="rounded p-2 flex-grow-1 mt-3 text-center fw-bold" style="font-size:.8rem;min-height:38px;display:flex;align-items:center;justify-content:center;">Preview</div>
                        </div>
                    </div>

                    <!-- Giảm giá tự động -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">
                            <i class="fas fa-percent text-danger me-1"></i>Giảm giá tự động (%)
                        </label>
                        <input type="number" name="discount_pct" id="discount_pct" class="form-control" min="0" max="100" value="0" placeholder="0 = không áp dụng">
                        <small class="text-muted">Tự động giảm giá khi đặt sân trong khung giờ</small>
                    </div>

                    <!-- Khung giờ -->
                    <div class="mb-3">
                        <label class="form-label fw-bold"><i class="fas fa-clock me-1"></i>Khung giờ áp dụng</label>
                        <div class="d-flex gap-2 align-items-center">
                            <input type="time" name="time_start" id="time_start" class="form-control form-control-sm">
                            <span class="text-muted">–</span>
                            <input type="time" name="time_end" id="time_end" class="form-control form-control-sm">
                        </div>
                        <small class="text-muted">Để trống nếu áp dụng cả ngày</small>
                    </div>
                    <!-- Ngày cụ thể -->
                    <div class="mb-3">
                        <label class="form-label fw-bold"><i class="fas fa-calendar-day me-1 text-danger"></i>Ngày cụ thể áp dụng</label>
                        <input type="date" name="apply_date" id="apply_date" class="form-control">
                        <small class="text-muted">Để trống = áp dụng mọi ngày. Điền ngày = chỉ ngày đó mới giảm giá.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Điều kiện áp dụng</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="apply_weekend" id="apply_weekend">
                            <label class="form-check-label" for="apply_weekend">📅 Cuối tuần & lễ tết</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="apply_newuser" id="apply_newuser">
                            <label class="form-check-label" for="apply_newuser">🆕 Thành viên mới (lần đầu)</label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Thứ tự hiển thị</label>
                        <input type="number" name="sort_order" id="sort_order" class="form-control" value="0" min="0">
                        <small class="text-muted">Số nhỏ = hiển thị trước</small>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-warning fw-bold" id="submitBtn">
                            <i class="fas fa-save me-2"></i>Lưu ưu đãi
                        </button>
                        <button type="button" class="btn btn-outline-secondary" onclick="resetForm()">
                            <i class="fas fa-times me-2"></i>Hủy
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Danh sách -->
    <div class="col-lg-8">
        <!-- Preview -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header"><h6 class="mb-0 fw-bold">👁 Preview (hiển thị trên trang Nổi bật)</h6></div>
            <div class="card-body">
                <?php if (empty($promos)): ?>
                    <p class="text-muted text-center py-3">Chưa có ưu đãi nào. Thêm ưu đãi đầu tiên!</p>
                <?php else: ?>
                    <div class="d-flex flex-column gap-2" style="max-width:320px;">
                        <?php foreach ($promos as $p): if (!$p['status']) continue; ?>
                        <div class="p-3 rounded" style="background:linear-gradient(135deg,<?php echo escape($p['color_from']); ?>,<?php echo escape($p['color_to']); ?>);color:<?php echo escape($p['text_color']); ?>;">
                            <div class="fw-bold"><?php echo escape($p['title']); ?></div>
                            <?php if ($p['description']): ?>
                                <small style="opacity:.85;"><?php echo escape($p['description']); ?></small>
                            <?php endif; ?>
                            <?php if ($p['discount_pct'] > 0): ?>
                                <div class="mt-1"><span style="background:rgba(255,255,255,.25);border-radius:6px;padding:2px 8px;font-size:.72rem;font-weight:700;">-<?php echo $p['discount_pct']; ?>%</span></div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Table -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0 fw-bold">Danh sách ưu đãi (<?php echo count($promos); ?>)</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Preview</th>
                                <th>Tiêu đề</th>
                                <th>Giảm %</th>
                                <th>Khung giờ</th>
                                <th>Điều kiện</th>
                                <th>Thứ tự</th>
                                <th>Trạng thái</th>
                                <th>Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($promos as $p): ?>
                            <tr>
                                <td>
                                    <div class="rounded px-2 py-1" style="background:linear-gradient(135deg,<?php echo escape($p['color_from']); ?>,<?php echo escape($p['color_to']); ?>);color:<?php echo escape($p['text_color']); ?>;font-size:.72rem;font-weight:700;white-space:nowrap;min-width:80px;text-align:center;">
                                        <?php echo escape($p['title']); ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-bold"><?php echo escape($p['title']); ?></div>
                                    <small class="text-muted"><?php echo escape($p['description']); ?></small>
                                </td>
                                <td>
                                    <?php if ($p['discount_pct'] > 0): ?>
                                        <span class="badge bg-danger">-<?php echo $p['discount_pct']; ?>%</span>
                                    <?php else: ?>
                                        <span class="text-muted small">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="small text-muted">
                                    <?php if ($p['time_start'] && $p['time_end']): ?>
                                        <?php echo substr($p['time_start'],0,5); ?> – <?php echo substr($p['time_end'],0,5); ?>
                                    <?php else: ?>
                                        Cả ngày
                                    <?php endif; ?>
                                </td>
                                <td class="small">
                                    <?php if ($p['apply_weekend']): ?><span class="badge bg-info me-1">📅 Cuối tuần</span><?php endif; ?>
                                    <?php if ($p['apply_newuser']): ?><span class="badge bg-success">🆕 Thành viên mới</span><?php endif; ?>
                                    <?php if (!$p['apply_weekend'] && !$p['apply_newuser']): ?><span class="text-muted">Tất cả</span><?php endif; ?>
                                </td>
                                <td class="text-center small"><?php echo $p['sort_order']; ?></td>
                                <td>
                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="toggle_status" value="1">
                                        <input type="hidden" name="toggle_id" value="<?php echo $p['id']; ?>">
                                        <input type="hidden" name="toggle_val" value="<?php echo $p['status'] ? 0 : 1; ?>">
                                        <button type="submit" class="btn btn-sm <?php echo $p['status'] ? 'btn-success' : 'btn-outline-secondary'; ?>">
                                            <?php echo $p['status'] ? '✅ Hiện' : '🚫 Ẩn'; ?>
                                        </button>
                                    </form>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary me-1 editPromoBtn"
                                        data-id="<?php echo $p['id']; ?>"
                                        data-title="<?php echo escape($p['title']); ?>"
                                        data-desc="<?php echo escape($p['description']); ?>"
                                        data-from="<?php echo escape($p['color_from']); ?>"
                                        data-to="<?php echo escape($p['color_to']); ?>"
                                        data-text="<?php echo escape($p['text_color']); ?>"
                                        data-disc="<?php echo $p['discount_pct']; ?>"
                                        data-tstart="<?php echo $p['time_start'] ?? ''; ?>"
                                        data-tend="<?php echo $p['time_end'] ?? ''; ?>"
                                        data-adate="<?php echo $p['apply_date'] ?? ''; ?>"
                                        data-weekend="<?php echo $p['apply_weekend']; ?>"
                                        data-newuser="<?php echo $p['apply_newuser']; ?>"
                                        data-sort="<?php echo $p['sort_order']; ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form method="post" class="d-inline" onsubmit="return confirm('Xóa ưu đãi này?')">
                                        <input type="hidden" name="delete_id" value="<?php echo $p['id']; ?>">
                                        <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
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
// Color preview
function updateColorPreview() {
    const from  = document.getElementById('color_from').value;
    const to    = document.getElementById('color_to').value;
    const text  = document.getElementById('text_color').value;
    const prev  = document.getElementById('colorPreview');
    prev.style.background = `linear-gradient(135deg,${from},${to})`;
    prev.style.color = text;
}
['color_from','color_to','text_color'].forEach(id => {
    document.getElementById(id).addEventListener('input', updateColorPreview);
});
updateColorPreview();

// Edit
document.querySelectorAll('.editPromoBtn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.getElementById('edit_id').value    = btn.dataset.id;
        document.getElementById('promo_title').value = btn.dataset.title;
        document.getElementById('promo_desc').value  = btn.dataset.desc;
        document.getElementById('color_from').value  = btn.dataset.from;
        document.getElementById('color_to').value    = btn.dataset.to;
        document.getElementById('text_color').value  = btn.dataset.text;
        document.getElementById('discount_pct').value= btn.dataset.disc;
        document.getElementById('time_start').value  = btn.dataset.tstart;
        document.getElementById('time_end').value    = btn.dataset.tend;
        document.getElementById('apply_weekend').checked = btn.dataset.weekend === '1';
        document.getElementById('apply_newuser').checked = btn.dataset.newuser === '1';
        document.getElementById('sort_order').value  = btn.dataset.sort;
        updateColorPreview();
        document.getElementById('formTitle').innerHTML = '<i class="fas fa-edit me-2"></i>Sửa ưu đãi';
        document.getElementById('submitBtn').innerHTML = '<i class="fas fa-save me-2"></i>Cập nhật';
        window.scrollTo({top:0,behavior:'smooth'});
    });
});

function resetForm() {
    document.getElementById('promoForm').reset();
    document.getElementById('edit_id').value = '';
    document.getElementById('formTitle').innerHTML = '<i class="fas fa-plus me-2"></i>Thêm ưu đãi mới';
    document.getElementById('submitBtn').innerHTML = '<i class="fas fa-save me-2"></i>Lưu ưu đãi';
    updateColorPreview();
}
</script>
