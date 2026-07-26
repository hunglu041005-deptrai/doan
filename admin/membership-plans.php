<?php
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

// ── Auto-migrate bảng membership_plans ──────────────────────────────────────
$mysqli->query("CREATE TABLE IF NOT EXISTS membership_plans (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    court_id     INT DEFAULT NULL COMMENT 'NULL = áp dụng tất cả sân',
    name         VARCHAR(150) NOT NULL COMMENT 'VD: COMBO CHIỀU 14H-17H',
    detail       VARCHAR(150) NOT NULL COMMENT 'VD: 10 VÉ TẶNG 1 VÉ',
    price_per    INT NOT NULL DEFAULT 80000 COMMENT 'Giá mỗi vé (VD: 80000)',
    total_price  INT NOT NULL DEFAULT 720000 COMMENT 'Tổng tiền gói',
    months       INT NOT NULL DEFAULT 3 COMMENT 'Thời hạn tháng',
    free_tickets INT NOT NULL DEFAULT 11 COMMENT 'Tổng vé (mua + tặng)',
    time_range   VARCHAR(30) DEFAULT '14H-17H' COMMENT 'Khung giờ áp dụng',
    sale_start   DATE DEFAULT NULL COMMENT 'Ngày mở bán',
    sale_end     DATE DEFAULT NULL COMMENT 'Ngày kết thúc bán',
    status       TINYINT DEFAULT 1,
    sort_order   INT DEFAULT 0,
    created_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_court  (court_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// ── Xử lý POST ──────────────────────────────────────────────────────────────
$msg = ''; $msgType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['act'] ?? 'save';

    if ($act === 'delete') {
        $id = intval($_POST['id']);
        $s  = $mysqli->prepare('DELETE FROM membership_plans WHERE id=?');
        $s->bind_param('i', $id); $s->execute(); $s->close();
        $msg = 'Đã xóa gói.';

    } elseif ($act === 'toggle') {
        $id  = intval($_POST['id']);
        $val = intval($_POST['val']);
        $s   = $mysqli->prepare('UPDATE membership_plans SET status=? WHERE id=?');
        $s->bind_param('ii', $val, $id); $s->execute(); $s->close();
        $msg = $val ? 'Đã kích hoạt.' : 'Đã ẩn.';

    } else {
        $id          = intval($_POST['edit_id'] ?? 0);
        $court_id    = intval($_POST['court_id'] ?? 0) ?: null;
        $name        = trim($_POST['name'] ?? '');
        $detail      = trim($_POST['detail'] ?? '');
        $price_per   = intval($_POST['price_per'] ?? 80000);
        $total_price = intval($_POST['total_price'] ?? 0);
        $months      = intval($_POST['months'] ?? 3);
        $free_tickets= intval($_POST['free_tickets'] ?? 0);
        $time_range  = trim($_POST['time_range'] ?? '');
        $sale_start  = trim($_POST['sale_start'] ?? '') ?: null;
        $sale_end    = trim($_POST['sale_end'] ?? '')   ?: null;
        $sort        = intval($_POST['sort_order'] ?? 0);

        if (!$name || !$detail) { $msg = 'Vui lòng điền đầy đủ thông tin.'; $msgType = 'danger'; }
        else {
            if ($id) {
                $s = $mysqli->prepare('UPDATE membership_plans SET court_id=?,name=?,detail=?,price_per=?,total_price=?,months=?,free_tickets=?,time_range=?,sale_start=?,sale_end=?,sort_order=? WHERE id=?');
                $s->bind_param('ississsssii', $court_id,$name,$detail,$price_per,$total_price,$months,$free_tickets,$time_range,$sale_start,$sale_end,$sort,$id);
                $s->execute(); $s->close();
                $msg = 'Cập nhật gói thành công.';
            } else {
                $s = $mysqli->prepare('INSERT INTO membership_plans (court_id,name,detail,price_per,total_price,months,free_tickets,time_range,sale_start,sale_end,sort_order) VALUES (?,?,?,?,?,?,?,?,?,?,?)');
                $s->bind_param('ississsssii', $court_id,$name,$detail,$price_per,$total_price,$months,$free_tickets,$time_range,$sale_start,$sale_end,$sort);
                $s->execute(); $s->close();
                $msg = 'Thêm gói thành công.';
            }
        }
    }
}

// ── Lấy dữ liệu ──────────────────────────────────────────────────────────────
$plans = $mysqli->query(
    "SELECT mp.*, c.name AS court_name
     FROM membership_plans mp
     LEFT JOIN courts c ON c.id = mp.court_id
     ORDER BY mp.court_id IS NULL DESC, mp.court_id, mp.sort_order, mp.id"
)->fetch_all(MYSQLI_ASSOC);

$courts = $mysqli->query("SELECT id, name FROM courts WHERE status=1 ORDER BY name")->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/../includes/header.php';
?>

<style>
.plan-row-global { background: #f0fdf4; }
.plan-row-court  { background: #fffbeb; }
</style>

<div class="container-fluid mt-4 pb-5">

<!-- Header -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="fw-bold mb-1"><i class="fas fa-tags text-success me-2"></i>Quản lý gói hội viên</h2>
                    <p class="text-muted mb-0">Tạo gói combo riêng cho từng sân hoặc áp dụng toàn bộ</p>
                </div>
                <a href="dashboard.php" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-arrow-left me-1"></i>Dashboard
                </a>
            </div>
        </div>
    </div>
</div>

<?php if ($msg): ?>
<div class="alert alert-<?php echo $msgType; ?> alert-dismissible fade show">
    <i class="fas fa-check-circle me-2"></i><?php echo escape($msg); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="row g-4">
    <!-- Form thêm/sửa -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm sticky-top" style="top:80px;">
            <div class="card-header fw-bold" style="background:linear-gradient(135deg,#16a34a,#15803d);color:#fff;">
                <i class="fas fa-plus me-2"></i><span id="formTitle">Thêm gói mới</span>
            </div>
            <div class="card-body">
                <form method="post" id="planForm">
                    <input type="hidden" name="act" value="save">
                    <input type="hidden" name="edit_id" id="edit_id">

                    <!-- Sân áp dụng -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">Sân áp dụng</label>
                        <select name="court_id" id="court_id" class="form-select">
                            <option value="">🌐 Tất cả sân (Global)</option>
                            <?php foreach ($courts as $c): ?>
                            <option value="<?php echo $c['id']; ?>"><?php echo escape($c['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Để trống = gói hiển thị ở tất cả sân</small>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-12">
                            <label class="form-label fw-bold">Tên gói <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="f_name" class="form-control" required
                                   placeholder="VD: COMBO CHIỀU 14H–17H">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Chi tiết <span class="text-danger">*</span></label>
                            <input type="text" name="detail" id="f_detail" class="form-control" required
                                   placeholder="VD: 10 VÉ TẶNG 1 VÉ">
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-bold">Giá/vé (đ)</label>
                            <input type="number" name="price_per" id="f_price_per" class="form-control" value="80000" min="0" oninput="calcTotal()">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold">Tổng tiền (đ)</label>
                            <input type="number" name="total_price" id="f_total_price" class="form-control" value="720000" min="0">
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-4">
                            <label class="form-label fw-bold">Thời hạn</label>
                            <div class="input-group input-group-sm">
                                <input type="number" name="months" id="f_months" class="form-control" value="3" min="1" max="24">
                                <span class="input-group-text">tháng</span>
                            </div>
                        </div>
                        <div class="col-4">
                            <label class="form-label fw-bold">Tổng vé</label>
                            <input type="number" name="free_tickets" id="f_free" class="form-control" value="11" min="1">
                            <small class="text-muted">Mua + tặng</small>
                        </div>
                        <div class="col-4">
                            <label class="form-label fw-bold">Khung giờ</label>
                            <input type="text" name="time_range" id="f_time" class="form-control" value="14H-17H" placeholder="14H-17H">
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-bold">Ngày mở bán</label>
                            <input type="date" name="sale_start" id="f_sale_start" class="form-control">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold">Ngày kết thúc</label>
                            <input type="date" name="sale_end" id="f_sale_end" class="form-control">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Thứ tự hiển thị</label>
                        <input type="number" name="sort_order" id="f_sort" class="form-control" value="0" min="0">
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-success fw-bold">
                            <i class="fas fa-save me-2"></i><span id="submitLabel">Lưu gói</span>
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="resetForm()">
                            <i class="fas fa-times me-1"></i>Hủy
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Danh sách -->
    <div class="col-lg-8">
        <!-- Preview -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header fw-bold bg-white border-bottom">
                <i class="fas fa-eye me-2 text-primary"></i>Preview giao diện người dùng
            </div>
            <div class="card-body" style="background:#f8fafc;">
                <p class="text-muted small mb-3">Đây là giao diện gói combo hiển thị trên trang sân:</p>
                <?php $previewCount = 0; foreach ($plans as $p): if (!$p['status'] || $previewCount >= 3) continue; $previewCount++; ?>
                <div style="background:#fff;border:2px solid #e5e7eb;border-radius:14px;padding:1rem 1.2rem;margin-bottom:.8rem;position:relative;overflow:hidden;">
                    <div style="position:absolute;left:0;top:0;bottom:0;width:4px;background:#16a34a;"></div>
                    <div style="display:flex;align-items:center;gap:.6rem;margin-bottom:.5rem;">
                        <span style="background:linear-gradient(135deg,#16a34a,#15803d);color:#fff;border-radius:20px;padding:2px 12px;font-size:.75rem;font-weight:700;">
                            <?php echo $p['court_id'] ? '🏸 '.escape($p['court_name']) : '🌐 Tất cả sân'; ?>
                        </span>
                    </div>
                    <div style="font-size:.72rem;color:#16a34a;font-weight:700;text-transform:uppercase;letter-spacing:.5px;">
                        GIÁ <?php echo number_format($p['price_per']); ?>đ/VÉ · <?php echo escape($p['time_range']); ?>
                    </div>
                    <div style="font-weight:800;font-size:.95rem;color:#111;margin:.2rem 0;">
                        <?php echo escape($p['name']); ?> : <?php echo escape($p['detail']); ?>
                    </div>
                    <div style="display:flex;gap:1.5rem;font-size:.8rem;color:#6b7280;margin:.5rem 0;">
                        <span>⏱ <strong style="color:#16a34a;"><?php echo $p['months']; ?> Tháng</strong></span>
                        <span>✅ Miễn phí <strong style="color:#16a34a;"><?php echo $p['free_tickets']; ?> vé</strong></span>
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-top:.5rem;">
                        <span style="font-size:1.1rem;font-weight:800;color:#111;"><?php echo number_format($p['total_price']); ?> đ</span>
                        <span style="background:#16a34a;color:#fff;border-radius:8px;padding:4px 14px;font-size:.82rem;font-weight:700;">Đăng ký ›</span>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if (empty($plans)): ?>
                <p class="text-muted text-center py-3">Chưa có gói nào. Thêm gói đầu tiên!</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Bảng danh sách -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                <span class="fw-bold">Danh sách gói (<?php echo count($plans); ?>)</span>
                <div>
                    <span class="badge bg-success me-1">🌐 Global</span>
                    <span class="badge" style="background:#f59e0b;">🏸 Theo sân</span>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size:.85rem;">
                    <thead class="table-light">
                        <tr>
                            <th>Sân</th>
                            <th>Tên gói</th>
                            <th>Giá/vé</th>
                            <th>Tổng tiền</th>
                            <th>Tháng</th>
                            <th>Vé</th>
                            <th>Giờ</th>
                            <th>Trạng thái</th>
                            <th style="width:120px;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($plans)): ?>
                    <tr><td colspan="9" class="text-center py-4 text-muted">Chưa có gói nào</td></tr>
                    <?php endif; ?>
                    <?php foreach ($plans as $p):
                        $rowClass = $p['court_id'] ? 'plan-row-court' : 'plan-row-global';
                    ?>
                    <tr class="<?php echo $rowClass; ?>">
                        <td>
                            <?php if ($p['court_id']): ?>
                                <span class="badge" style="background:#f59e0b;font-size:.72rem;"><?php echo escape($p['court_name']); ?></span>
                            <?php else: ?>
                                <span class="badge bg-success" style="font-size:.72rem;">Tất cả sân</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="fw-bold"><?php echo escape($p['name']); ?></div>
                            <div class="text-muted" style="font-size:.78rem;"><?php echo escape($p['detail']); ?></div>
                        </td>
                        <td><?php echo number_format($p['price_per']); ?>đ</td>
                        <td class="fw-bold"><?php echo number_format($p['total_price']); ?>đ</td>
                        <td><?php echo $p['months']; ?>T</td>
                        <td><?php echo $p['free_tickets']; ?></td>
                        <td><span class="badge bg-light text-dark"><?php echo escape($p['time_range']); ?></span></td>
                        <td>
                            <form method="post" class="d-inline">
                                <input type="hidden" name="act" value="toggle">
                                <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                                <input type="hidden" name="val" value="<?php echo $p['status'] ? 0 : 1; ?>">
                                <button type="submit" class="btn btn-sm <?php echo $p['status'] ? 'btn-success' : 'btn-outline-secondary'; ?>" style="font-size:.72rem;padding:2px 8px;">
                                    <?php echo $p['status'] ? '✅ Hiện' : '🚫 Ẩn'; ?>
                                </button>
                            </form>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary me-1 editBtn" style="font-size:.72rem;"
                                    data-id="<?php echo $p['id']; ?>"
                                    data-court="<?php echo $p['court_id'] ?? ''; ?>"
                                    data-name="<?php echo escape($p['name']); ?>"
                                    data-detail="<?php echo escape($p['detail']); ?>"
                                    data-pp="<?php echo $p['price_per']; ?>"
                                    data-tp="<?php echo $p['total_price']; ?>"
                                    data-months="<?php echo $p['months']; ?>"
                                    data-free="<?php echo $p['free_tickets']; ?>"
                                    data-time="<?php echo escape($p['time_range']); ?>"
                                    data-ss="<?php echo $p['sale_start'] ?? ''; ?>"
                                    data-se="<?php echo $p['sale_end'] ?? ''; ?>"
                                    data-sort="<?php echo $p['sort_order']; ?>">
                                <i class="fas fa-edit"></i>
                            </button>
                            <form method="post" class="d-inline" onsubmit="return confirm('Xóa gói này?')">
                                <input type="hidden" name="act" value="delete">
                                <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger" style="font-size:.72rem;">
                                    <i class="fas fa-trash"></i>
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

<script>
function calcTotal() {
    const pp = parseInt(document.getElementById('f_price_per').value) || 0;
    const fr = parseInt(document.getElementById('f_free').value) || 0;
    document.getElementById('f_total_price').value = pp * fr;
}

document.querySelectorAll('.editBtn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.getElementById('edit_id').value   = btn.dataset.id;
        document.getElementById('court_id').value  = btn.dataset.court;
        document.getElementById('f_name').value    = btn.dataset.name;
        document.getElementById('f_detail').value  = btn.dataset.detail;
        document.getElementById('f_price_per').value  = btn.dataset.pp;
        document.getElementById('f_total_price').value= btn.dataset.tp;
        document.getElementById('f_months').value  = btn.dataset.months;
        document.getElementById('f_free').value    = btn.dataset.free;
        document.getElementById('f_time').value    = btn.dataset.time;
        document.getElementById('f_sale_start').value = btn.dataset.ss;
        document.getElementById('f_sale_end').value   = btn.dataset.se;
        document.getElementById('f_sort').value    = btn.dataset.sort;
        document.getElementById('formTitle').textContent = 'Sửa gói';
        document.getElementById('submitLabel').textContent = 'Cập nhật';
        window.scrollTo({top:0, behavior:'smooth'});
    });
});

function resetForm() {
    document.getElementById('planForm').reset();
    document.getElementById('edit_id').value = '';
    document.getElementById('formTitle').textContent = 'Thêm gói mới';
    document.getElementById('submitLabel').textContent = 'Lưu gói';
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
