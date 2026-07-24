<?php
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();
$isAdminPage = true;
$message = '';
$msgType = 'success';

// ── Auto-add cột còn thiếu ──
foreach ([
    "ALTER TABLE coaches ADD COLUMN IF NOT EXISTS phone VARCHAR(20) DEFAULT NULL",
    "ALTER TABLE coaches ADD COLUMN IF NOT EXISTS bio TEXT DEFAULT NULL",
    "ALTER TABLE coaches ADD COLUMN IF NOT EXISTS avatar VARCHAR(255) DEFAULT NULL",
] as $sql) { $mysqli->query($sql); }

// ── Xử lý POST ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Xóa HLV
    if (isset($_POST['delete_id'])) {
        $did = intval($_POST['delete_id']);
        $mysqli->prepare('DELETE FROM coaches WHERE id=?')->execute() || null;
        $s = $mysqli->prepare('DELETE FROM coaches WHERE id=?');
        $s->bind_param('i', $did); $s->execute(); $s->close();
        // Xóa user_id liên kết nếu muốn (giữ account user, chỉ xóa coach record)
        $message = 'Đã xóa huấn luyện viên.';

    // Toggle trạng thái
    } elseif (isset($_POST['toggle_status'])) {
        $tid = intval($_POST['toggle_id']);
        $tv  = intval($_POST['toggle_val']);
        $s = $mysqli->prepare('UPDATE coaches SET status=? WHERE id=?');
        $s->bind_param('ii', $tv, $tid); $s->execute(); $s->close();
        $message = $tv ? 'Đã kích hoạt HLV.' : 'Đã ẩn HLV.';

    // Thêm / Sửa
    } else {
        $name       = trim($_POST['name']        ?? '');
        $specialty  = trim($_POST['specialty']   ?? '');
        $exp        = intval($_POST['experience_years']     ?? 0);
        $max        = intval($_POST['max_students_per_week'] ?? 3);
        $phone      = trim($_POST['phone']       ?? '');
        $bio        = trim($_POST['bio']         ?? '');
        $status_val = intval($_POST['status']    ?? 1);
        $user_id    = intval($_POST['user_id']   ?? 0) ?: null;

        if (!$name) {
            $message = 'Vui lòng nhập tên HLV.'; $msgType = 'danger';
        } else {
            if (!empty($_POST['edit_id'])) {
                $eid = intval($_POST['edit_id']);
                $s = $mysqli->prepare('UPDATE coaches SET name=?,specialty=?,experience_years=?,max_students_per_week=?,phone=?,bio=?,status=?,user_id=? WHERE id=?');
                $s->bind_param('ssiissiis', $name,$specialty,$exp,$max,$phone,$bio,$status_val,$user_id,$eid);
                $s->execute(); $s->close();
                $message = 'Cập nhật HLV thành công.';
            } else {
                $s = $mysqli->prepare('INSERT INTO coaches (name,specialty,experience_years,max_students_per_week,phone,bio,status,user_id) VALUES (?,?,?,?,?,?,?,?)');
                $s->bind_param('ssiiisii', $name,$specialty,$exp,$max,$phone,$bio,$status_val,$user_id);
                $s->execute(); $s->close();
                $message = 'Thêm HLV thành công.';
            }
        }
    }
}

// ── Lấy danh sách HLV ──
$coaches = $mysqli->query("
    SELECT c.*, u.email AS user_email,
           (SELECT COUNT(*) FROM training_registrations tr WHERE tr.coach_id=c.id AND tr.status='active') AS active_students
    FROM coaches c
    LEFT JOIN users u ON u.id = c.user_id
    ORDER BY c.status DESC, c.name ASC
")->fetch_all(MYSQLI_ASSOC);

// Danh sách user role=coach để gán
$coachUsers = $mysqli->query("SELECT id, name, email FROM users WHERE role='coach' ORDER BY name")->fetch_all(MYSQLI_ASSOC);

// Thống kê
$totalCoaches  = count($coaches);
$activeCoaches = count(array_filter($coaches, fn($c) => $c['status'] == 1));
$totalStudents = array_sum(array_column($coaches, 'active_students'));

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid mt-4">

<!-- Header -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-lg" style="background:linear-gradient(135deg,#302b63 0%,#24243e 100%);">
            <div class="card-body text-white py-4">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1 class="fw-bold mb-1"><i class="fas fa-chalkboard-teacher me-3"></i>Quản lý Huấn luyện viên</h1>
                        <p class="mb-0 opacity-75">Thêm mới, chỉnh sửa thông tin và quản lý lịch làm việc của HLV.</p>
                    </div>
                    <div class="col-md-4 text-end d-none d-md-block">
                        <i class="fas fa-user-tie" style="font-size:4rem;opacity:.2;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($message): ?>
<div class="alert alert-<?php echo $msgType; ?> alert-dismissible fade show shadow-sm">
    <i class="fas fa-<?php echo $msgType==='success'?'check-circle':'exclamation-circle'; ?> me-2"></i><?php echo escape($message); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Thống kê -->
<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100 hover-lift">
            <div class="card-body d-flex align-items-center">
                <div class="bg-primary bg-opacity-10 rounded-3 p-3 me-3"><i class="fas fa-users text-primary fa-2x"></i></div>
                <div><div class="text-muted small fw-bold text-uppercase">Tổng HLV</div>
                <div class="h2 fw-bold mb-0 text-primary"><?php echo $totalCoaches; ?></div></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100 hover-lift">
            <div class="card-body d-flex align-items-center">
                <div class="bg-success bg-opacity-10 rounded-3 p-3 me-3"><i class="fas fa-check-circle text-success fa-2x"></i></div>
                <div><div class="text-muted small fw-bold text-uppercase">Đang hoạt động</div>
                <div class="h2 fw-bold mb-0 text-success"><?php echo $activeCoaches; ?></div></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100 hover-lift">
            <div class="card-body d-flex align-items-center">
                <div class="bg-warning bg-opacity-10 rounded-3 p-3 me-3"><i class="fas fa-graduation-cap text-warning fa-2x"></i></div>
                <div><div class="text-muted small fw-bold text-uppercase">Học viên đang học</div>
                <div class="h2 fw-bold mb-0 text-warning"><?php echo $totalStudents; ?></div></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
<!-- ── Form thêm/sửa ── -->
<div class="col-lg-4">
<div class="card border-0 shadow-sm sticky-top" style="top:80px;">
    <div class="card-header py-3 text-white fw-bold" id="formHeader"
         style="background:linear-gradient(135deg,#302b63,#24243e);border-radius:12px 12px 0 0;">
        <i class="fas fa-plus me-2"></i><span id="formTitle">Thêm HLV mới</span>
    </div>
    <div class="card-body">
        <form method="POST" id="coachForm">
            <input type="hidden" name="edit_id" id="edit_id">

            <div class="mb-3">
                <label class="form-label fw-semibold">Họ tên HLV <span class="text-danger">*</span></label>
                <input type="text" name="name" id="f_name" class="form-control" required placeholder="Nguyễn Văn A">
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Chuyên môn</label>
                <input type="text" name="specialty" id="f_specialty" class="form-control" placeholder="Kỹ thuật cơ bản, tấn công...">
            </div>
            <div class="row g-3 mb-3">
                <div class="col-6">
                    <label class="form-label fw-semibold">Năm kinh nghiệm</label>
                    <input type="number" name="experience_years" id="f_exp" class="form-control" min="0" max="50" value="0">
                </div>
                <div class="col-6">
                    <label class="form-label fw-semibold">Max HV/tuần</label>
                    <input type="number" name="max_students_per_week" id="f_max" class="form-control" min="1" max="20" value="3">
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Số điện thoại</label>
                <input type="tel" name="phone" id="f_phone" class="form-control" placeholder="0912 345 678">
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Giới thiệu bản thân</label>
                <textarea name="bio" id="f_bio" class="form-control" rows="3" placeholder="Mô tả ngắn về HLV..."></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Tài khoản đăng nhập</label>
                <select name="user_id" id="f_user" class="form-select">
                    <option value="">-- Chưa liên kết --</option>
                    <?php foreach ($coachUsers as $u): ?>
                    <option value="<?php echo $u['id']; ?>"><?php echo escape($u['name']); ?> (<?php echo escape($u['email']); ?>)</option>
                    <?php endforeach; ?>
                </select>
                <div class="form-text">Tài khoản để HLV đăng nhập vào dashboard.</div>
            </div>
            <div class="mb-4">
                <label class="form-label fw-semibold">Trạng thái</label>
                <select name="status" id="f_status" class="form-select">
                    <option value="1">✅ Đang hoạt động</option>
                    <option value="0">🚫 Tạm nghỉ</option>
                </select>
            </div>
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary fw-bold py-2" id="submitBtn">
                    <i class="fas fa-save me-2"></i>Lưu HLV
                </button>
                <button type="button" class="btn btn-outline-secondary" onclick="resetForm()">
                    <i class="fas fa-times me-2"></i>Hủy / Tạo mới
                </button>
            </div>
        </form>
    </div>
</div>
</div>

<!-- ── Danh sách HLV ── -->
<div class="col-lg-8">
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom py-3">
        <div class="d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold"><i class="fas fa-list text-primary me-2"></i>Danh sách huấn luyện viên
                <span class="badge bg-primary ms-2"><?php echo $totalCoaches; ?></span>
            </h6>
            <input type="text" id="coachSearch" class="form-control form-control-sm" style="width:200px;"
                   placeholder="Tìm kiếm HLV..." oninput="filterCoaches(this.value)">
        </div>
    </div>

    <?php if (empty($coaches)): ?>
    <div class="card-body text-center py-5">
        <i class="fas fa-user-tie fa-3x text-muted mb-3 d-block"></i>
        <h6 class="text-muted">Chưa có HLV nào</h6>
        <p class="text-muted small">Thêm HLV đầu tiên bằng form bên trái.</p>
    </div>
    <?php else: ?>

    <!-- Grid cards HLV -->
    <div class="card-body">
        <div class="row g-3" id="coachesGrid">
        <?php foreach ($coaches as $c): ?>
        <div class="col-md-6 coach-item">
            <div class="card border h-100 coach-card <?php echo $c['status'] ? '' : 'opacity-65'; ?>"
                 style="border-radius:14px;transition:all .2s;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-start gap-3">
                        <!-- Avatar -->
                        <div style="flex-shrink:0;">
                            <?php if (!empty($c['avatar']) && file_exists(__DIR__ . '/../' . $c['avatar'])): ?>
                                <img src="../<?php echo escape($c['avatar']); ?>"
                                     style="width:56px;height:56px;border-radius:50%;object-fit:cover;border:2px solid #e5e7eb;">
                            <?php else: ?>
                                <div style="width:56px;height:56px;border-radius:50%;background:linear-gradient(135deg,#302b63,#6366f1);display:flex;align-items:center;justify-content:center;">
                                    <i class="fas fa-user-tie text-white fa-lg"></i>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Info -->
                        <div style="flex:1;min-width:0;">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <div class="fw-bold"><?php echo escape($c['name']); ?></div>
                                <span class="badge <?php echo $c['status'] ? 'bg-success' : 'bg-secondary'; ?> rounded-pill" style="font-size:.65rem;">
                                    <?php echo $c['status'] ? 'Hoạt động' : 'Tạm nghỉ'; ?>
                                </span>
                            </div>
                            <div class="text-muted small mb-1">
                                <i class="fas fa-star text-warning me-1"></i><?php echo escape($c['specialty'] ?? 'Chưa cập nhật'); ?>
                            </div>
                            <?php if ($c['phone']): ?>
                            <div class="text-muted small"><i class="fas fa-phone me-1"></i><?php echo escape($c['phone']); ?></div>
                            <?php endif; ?>
                            <?php if ($c['user_email']): ?>
                            <div class="text-muted small"><i class="fas fa-envelope me-1"></i><?php echo escape($c['user_email']); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Stats bar -->
                    <div class="row g-2 mt-2">
                        <div class="col-4 text-center">
                            <div class="fw-bold text-primary"><?php echo $c['experience_years'] ?? 0; ?></div>
                            <div style="font-size:.68rem;color:#9ca3af;">Năm KN</div>
                        </div>
                        <div class="col-4 text-center">
                            <div class="fw-bold text-success"><?php echo $c['active_students']; ?></div>
                            <div style="font-size:.68rem;color:#9ca3af;">HV đang học</div>
                        </div>
                        <div class="col-4 text-center">
                            <div class="fw-bold text-info"><?php echo $c['max_students_per_week'] ?? 3; ?></div>
                            <div style="font-size:.68rem;color:#9ca3af;">Max HV/tuần</div>
                        </div>
                    </div>

                    <?php if ($c['bio']): ?>
                    <div class="mt-2 text-muted" style="font-size:.78rem;line-height:1.4;border-top:1px solid #f3f4f6;padding-top:.5rem;">
                        <?php echo escape(mb_substr($c['bio'], 0, 100)) . (mb_strlen($c['bio']) > 100 ? '...' : ''); ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Card footer: actions -->
                <div class="card-footer bg-white border-top-0 pt-0 pb-3 px-3">
                    <div class="d-flex gap-2 mt-2">
                        <!-- Sửa -->
                        <button class="btn btn-sm btn-outline-primary flex-grow-1 editCoachBtn"
                            data-id="<?php echo $c['id']; ?>"
                            data-name="<?php echo escape($c['name']); ?>"
                            data-specialty="<?php echo escape($c['specialty'] ?? ''); ?>"
                            data-exp="<?php echo $c['experience_years'] ?? 0; ?>"
                            data-max="<?php echo $c['max_students_per_week'] ?? 3; ?>"
                            data-phone="<?php echo escape($c['phone'] ?? ''); ?>"
                            data-bio="<?php echo escape($c['bio'] ?? ''); ?>"
                            data-status="<?php echo $c['status']; ?>"
                            data-user="<?php echo $c['user_id'] ?? ''; ?>">
                            <i class="fas fa-edit me-1"></i>Sửa
                        </button>

                        <!-- Xem dashboard -->
                        <?php if ($c['user_id']): ?>
                        <a href="<?php echo '../coach/dashboard.php?coach_id=' . $c['id']; ?>"
                           class="btn btn-sm btn-outline-success flex-grow-1" target="_blank" title="Xem dashboard">
                            <i class="fas fa-external-link-alt me-1"></i>Dashboard
                        </a>
                        <?php endif; ?>

                        <!-- Toggle trạng thái -->
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="toggle_status" value="1">
                            <input type="hidden" name="toggle_id" value="<?php echo $c['id']; ?>">
                            <input type="hidden" name="toggle_val" value="<?php echo $c['status'] ? 0 : 1; ?>">
                            <button type="submit" class="btn btn-sm <?php echo $c['status'] ? 'btn-outline-warning' : 'btn-outline-success'; ?>"
                                    title="<?php echo $c['status'] ? 'Tạm nghỉ' : 'Kích hoạt'; ?>">
                                <i class="fas <?php echo $c['status'] ? 'fa-pause' : 'fa-play'; ?>"></i>
                            </button>
                        </form>

                        <!-- Xóa -->
                        <form method="POST" class="d-inline" onsubmit="return confirm('Xóa HLV <?php echo escape($c['name']); ?>?');">
                            <input type="hidden" name="delete_id" value="<?php echo $c['id']; ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Xóa">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>
</div>
</div><!-- /row -->
</div><!-- /container -->

<style>
.hover-lift { transition: transform .2s, box-shadow .2s; }
.hover-lift:hover { transform: translateY(-3px); box-shadow: 0 .5rem 1.5rem rgba(0,0,0,.12) !important; }
.coach-card:hover { border-color: #6366f1 !important; box-shadow: 0 4px 20px rgba(99,102,241,.15); }
.opacity-65 { opacity: .65; }
</style>

<script>
// Điền form khi click Sửa
document.querySelectorAll('.editCoachBtn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.getElementById('edit_id').value    = btn.dataset.id;
        document.getElementById('f_name').value     = btn.dataset.name;
        document.getElementById('f_specialty').value= btn.dataset.specialty;
        document.getElementById('f_exp').value      = btn.dataset.exp;
        document.getElementById('f_max').value      = btn.dataset.max;
        document.getElementById('f_phone').value    = btn.dataset.phone;
        document.getElementById('f_bio').value      = btn.dataset.bio;
        document.getElementById('f_status').value   = btn.dataset.status;

        const uSel = document.getElementById('f_user');
        if (uSel) uSel.value = btn.dataset.user || '';

        document.getElementById('formTitle').textContent  = 'Sửa: ' + btn.dataset.name;
        document.getElementById('submitBtn').innerHTML    = '<i class="fas fa-save me-2"></i>Cập nhật HLV';

        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
});

function resetForm() {
    document.getElementById('coachForm').reset();
    document.getElementById('edit_id').value = '';
    document.getElementById('formTitle').textContent  = 'Thêm HLV mới';
    document.getElementById('submitBtn').innerHTML    = '<i class="fas fa-save me-2"></i>Lưu HLV';
}

function filterCoaches(q) {
    document.querySelectorAll('.coach-item').forEach(el => {
        el.style.display = el.textContent.toLowerCase().includes(q.toLowerCase()) ? '' : 'none';
    });
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
