<?php
require_once __DIR__ . '/includes/functions.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$userId       = (int) $_SESSION['user_id'];
$userBookings = getUserBookings($userId);
$totalBookings= count($userBookings);

$confirmedCount = 0;
$upcomingCount  = 0;
$totalSpent     = 0;
$today          = date('Y-m-d');

foreach ($userBookings as $b) {
    if ($b['status'] === 'confirmed') {
        $confirmedCount++;
        $totalSpent += $b['total_price'];
        if ($b['booking_date'] >= $today) $upcomingCount++;
    }
}

// Lấy thẻ hội viên (nếu có)
$membership = null;
$stmt = $mysqli->prepare('SELECT * FROM memberships WHERE user_id = ? AND status = "active" ORDER BY created_at DESC LIMIT 1');
if ($stmt) {
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $membership = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// Xử lý cập nhật hồ sơ
$updateMsg   = '';
$updateError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $newName  = trim($_POST['name']  ?? '');
    $newPhone = trim($_POST['phone'] ?? '');
    
    if (!$newName) {
        $updateError = 'Tên không được để trống.';
    } else {
        // Thêm cột phone nếu chưa có
        $mysqli->query('ALTER TABLE users ADD COLUMN IF NOT EXISTS phone VARCHAR(20) DEFAULT NULL');
        $stmt2 = $mysqli->prepare('UPDATE users SET name = ?, phone = ? WHERE id = ?');
        $stmt2->bind_param('ssi', $newName, $newPhone, $userId);
        if ($stmt2->execute()) {
            $_SESSION['name'] = $newName;
            $updateMsg = 'Cập nhật thành công!';
        } else {
            $updateError = 'Có lỗi xảy ra.';
        }
        $stmt2->close();
    }
}

// Đổi mật khẩu
$pwMsg   = '';
$pwError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $curPw  = $_POST['current_password'] ?? '';
    $newPw  = $_POST['new_password']     ?? '';
    $confPw = $_POST['confirm_password'] ?? '';
    
    $stmt3 = $mysqli->prepare('SELECT password FROM users WHERE id = ?');
    $stmt3->bind_param('i', $userId);
    $stmt3->execute();
    $dbPw = $stmt3->get_result()->fetch_assoc()['password'];
    $stmt3->close();
    
    if (!password_verify($curPw, $dbPw)) {
        $pwError = 'Mật khẩu hiện tại không đúng.';
    } elseif (strlen($newPw) < 6) {
        $pwError = 'Mật khẩu mới phải có ít nhất 6 ký tự.';
    } elseif ($newPw !== $confPw) {
        $pwError = 'Xác nhận mật khẩu không khớp.';
    } else {
        $hashed = password_hash($newPw, PASSWORD_DEFAULT);
        $stmt4  = $mysqli->prepare('UPDATE users SET password = ? WHERE id = ?');
        $stmt4->bind_param('si', $hashed, $userId);
        $stmt4->execute();
        $stmt4->close();
        $pwMsg = 'Đổi mật khẩu thành công!';
    }
}

// Lấy phone nếu có
$userPhone = '';
$userAvatar = '';
$stmt5 = $mysqli->prepare("SELECT phone, avatar FROM users WHERE id = ?");
if ($stmt5) {
    $stmt5->bind_param('i', $userId);
    $stmt5->execute();
    $row5 = $stmt5->get_result()->fetch_assoc();
    $userPhone  = $row5['phone']  ?? '';
    $userAvatar = $row5['avatar'] ?? '';
    $stmt5->close();
}

// Xử lý upload avatar
$avatarMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
    $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $finfo   = finfo_open(FILEINFO_MIME_TYPE);
    $mime    = finfo_file($finfo, $_FILES['avatar']['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, $allowed)) {
        $updateError = 'Chỉ chấp nhận file ảnh JPG, PNG, GIF, WEBP.';
    } elseif ($_FILES['avatar']['size'] > 3 * 1024 * 1024) {
        $updateError = 'Ảnh không được vượt quá 3MB.';
    } else {
        $ext      = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
        $filename = 'avatar_' . $userId . '_' . time() . '.' . $ext;
        $dest     = __DIR__ . '/uploads/avatars/' . $filename;

        if (move_uploaded_file($_FILES['avatar']['tmp_name'], $dest)) {
            // Xóa avatar cũ nếu có
            if ($userAvatar && file_exists(__DIR__ . '/uploads/avatars/' . basename($userAvatar))) {
                @unlink(__DIR__ . '/uploads/avatars/' . basename($userAvatar));
            }
            $avatarPath = 'uploads/avatars/' . $filename;
            $stmtAv = $mysqli->prepare('UPDATE users SET avatar = ? WHERE id = ?');
            $stmtAv->bind_param('si', $avatarPath, $userId);
            $stmtAv->execute();
            $stmtAv->close();
            $userAvatar = $avatarPath;
            $_SESSION['avatar'] = $avatarPath;
            $avatarMsg = 'Cập nhật ảnh đại diện thành công!';
        } else {
            $updateError = 'Không thể lưu ảnh. Vui lòng thử lại.';
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<style>
.profile-page { max-width: 1000px; margin: 2rem auto; padding: 0 1rem; }

.profile-hero {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 20px;
    padding: 2rem;
    color: #fff;
    margin-bottom: 1.5rem;
    position: relative;
    overflow: hidden;
}
.profile-hero::before {
    content: '';
    position: absolute;
    top: -40px; right: -40px;
    width: 200px; height: 200px;
    background: rgba(255,255,255,.08);
    border-radius: 50%;
}

.avatar-circle {
    width: 80px; height: 80px;
    background: rgba(255,255,255,.2);
    border: 3px solid rgba(255,255,255,.4);
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 2rem; color: #fff; flex-shrink: 0;
}

.stat-chip {
    background: rgba(255,255,255,.15);
    border: 1px solid rgba(255,255,255,.2);
    border-radius: 12px;
    padding: .6rem 1.2rem;
    text-align: center;
}
.stat-chip .n { font-size: 1.4rem; font-weight: 800; }
.stat-chip .l { font-size: .72rem; opacity: .8; }

.profile-card {
    background: #fff;
    border-radius: 18px;
    padding: 1.8rem;
    box-shadow: 0 4px 20px rgba(0,0,0,.07);
    margin-bottom: 1.2rem;
    border: 1px solid #f0f0f0;
}

.profile-card h5 {
    font-weight: 800;
    color: #111;
    margin-bottom: 1.4rem;
    display: flex;
    align-items: center;
    gap: .5rem;
    padding-bottom: .8rem;
    border-bottom: 2px solid #f3f4f6;
}

.form-label-sm { font-size: .82rem; font-weight: 700; color: #374151; margin-bottom: .3rem; }

.form-control-modern {
    border: 1.5px solid #e5e7eb;
    border-radius: 12px;
    padding: .7rem 1rem;
    font-size: .9rem;
    transition: all .2s;
}
.form-control-modern:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102,126,234,.15);
}

.btn-save {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: #fff; border: none;
    border-radius: 12px;
    padding: .65rem 1.8rem;
    font-weight: 700; font-size: .9rem;
    transition: all .2s;
}
.btn-save:hover { transform: translateY(-1px); color: #fff; opacity: .9; }

.quick-link {
    display: flex; justify-content: space-between; align-items: center;
    padding: .85rem 1rem;
    border-radius: 12px;
    border: 1.5px solid #f3f4f6;
    margin-bottom: .6rem;
    text-decoration: none;
    color: #374151;
    transition: all .2s;
    font-weight: 600;
    font-size: .88rem;
}
.quick-link:hover { border-color: #667eea; background: #f5f3ff; color: #667eea; }
.quick-link .icon-wrap {
    width: 36px; height: 36px;
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: .9rem;
}

.member-badge {
    background: linear-gradient(135deg, #28a745, #20c997);
    color: #fff;
    border-radius: 14px;
    padding: 1rem 1.2rem;
    display: flex; align-items: center; gap: 1rem;
    margin-bottom: 1.2rem;
}

.booking-mini {
    display: flex; justify-content: space-between; align-items: center;
    padding: .7rem 0;
    border-bottom: 1px solid #f3f4f6;
    font-size: .85rem;
}
.booking-mini:last-child { border-bottom: none; }
</style>

<div class="profile-page">

    <!-- Hero -->
    <div class="profile-hero">
        <div class="d-flex align-items-center gap-3 mb-3" style="position:relative;z-index:1;">

            <!-- Avatar có thể click để đổi -->
            <div class="avatar-wrap" style="position:relative;cursor:pointer;" onclick="document.getElementById('avatarInput').click();" title="Nhấn để đổi ảnh đại diện">
                <?php if ($userAvatar && file_exists(__DIR__ . '/' . $userAvatar)): ?>
                    <img src="<?php echo escape($userAvatar); ?>?v=<?php echo time(); ?>"
                         id="avatarPreview"
                         style="width:80px;height:80px;border-radius:50%;object-fit:cover;border:3px solid rgba(255,255,255,.5);"
                         alt="Avatar">
                <?php else: ?>
                    <div class="avatar-circle" id="avatarPreview" style="width:80px;height:80px;">
                        <i class="fas fa-user"></i>
                    </div>
                <?php endif; ?>
                <!-- Overlay khi hover -->
                <div style="
                    position:absolute;inset:0;border-radius:50%;
                    background:rgba(0,0,0,.45);
                    display:flex;align-items:center;justify-content:center;
                    opacity:0;transition:opacity .2s;
                " id="avatarOverlay">
                    <i class="fas fa-camera" style="color:#fff;font-size:1.3rem;"></i>
                </div>
            </div>

            <!-- Form upload ẩn -->
            <form method="POST" enctype="multipart/form-data" id="avatarForm" style="display:none;">
                <input type="file" name="avatar" id="avatarInput" accept="image/*"
                       onchange="previewAndSubmit(this)">
            </form>

            <div>
                <h4 class="fw-bold mb-0"><?php echo escape($_SESSION['name']); ?></h4>
                <div style="opacity:.75;font-size:.85rem;"><?php echo escape($_SESSION['email']); ?></div>
                <div style="margin-top:.3rem;">
                    <span style="background:rgba(255,255,255,.2);border-radius:20px;padding:3px 10px;font-size:.75rem;font-weight:700;">
                        <?php echo isAdmin() ? '👑 Admin' : '👤 Người dùng'; ?>
                    </span>
                </div>
            </div>
        </div>

        <div class="d-flex gap-3 flex-wrap" style="position:relative;z-index:1;">
            <div class="stat-chip">
                <div class="n"><?php echo $totalBookings; ?></div>
                <div class="l">Tổng đặt sân</div>
            </div>
            <div class="stat-chip">
                <div class="n"><?php echo $upcomingCount; ?></div>
                <div class="l">Sắp tới</div>
            </div>
            <div class="stat-chip">
                <div class="n"><?php echo number_format($totalSpent/1000); ?>K</div>
                <div class="l">Đã chi tiêu</div>
            </div>
            <?php if ($membership): ?>
            <div class="stat-chip">
                <div class="n">HV</div>
                <div class="l">Hội viên</div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-7">

            <!-- Thẻ hội viên nếu có -->
            <?php if ($membership): ?>
            <div class="member-badge">
                <div style="width:44px;height:44px;background:rgba(255,255,255,.2);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.3rem;">💎</div>
                <div>
                    <div style="font-weight:800;font-size:.95rem;">Thẻ hội viên đang hoạt động</div>
                    <div style="font-size:.8rem;opacity:.85;">Mã: <?php echo escape($membership['member_code']); ?> · Hết hạn: <?php echo date('d/m/Y', strtotime($membership['end_date'])); ?></div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Cập nhật thông tin -->
            <div class="profile-card">
                <h5><i class="fas fa-user-edit text-primary"></i> Thông tin cá nhân</h5>

                <?php if ($updateMsg): ?>
                    <div class="alert alert-success py-2 rounded-3"><?php echo $updateMsg; ?></div>
                <?php endif; ?>
                <?php if ($updateError): ?>
                    <div class="alert alert-danger py-2 rounded-3"><?php echo $updateError; ?></div>
                <?php endif; ?>

                <form method="POST">
                    <input type="hidden" name="update_profile" value="1">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label-sm">Họ và tên</label>
                            <input type="text" name="name" class="form-control form-control-modern"
                                   value="<?php echo escape($_SESSION['name']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label-sm">Số điện thoại</label>
                            <input type="tel" name="phone" class="form-control form-control-modern"
                                   value="<?php echo escape($userPhone); ?>"
                                   placeholder="0123 456 789">
                        </div>
                        <div class="col-12">
                            <label class="form-label-sm">Email</label>
                            <input type="email" class="form-control form-control-modern"
                                   value="<?php echo escape($_SESSION['email']); ?>" disabled>
                            <div style="font-size:.75rem;color:#9ca3af;margin-top:.3rem;">Email không thể thay đổi</div>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn-save">
                                <i class="fas fa-save me-2"></i>Lưu thay đổi
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Đổi mật khẩu -->
            <div class="profile-card">
                <h5><i class="fas fa-lock text-warning"></i> Đổi mật khẩu</h5>

                <?php if ($pwMsg): ?>
                    <div class="alert alert-success py-2 rounded-3"><?php echo $pwMsg; ?></div>
                <?php endif; ?>
                <?php if ($pwError): ?>
                    <div class="alert alert-danger py-2 rounded-3"><?php echo $pwError; ?></div>
                <?php endif; ?>

                <form method="POST">
                    <input type="hidden" name="change_password" value="1">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label-sm">Mật khẩu hiện tại</label>
                            <input type="password" name="current_password" class="form-control form-control-modern"
                                   placeholder="Nhập mật khẩu hiện tại" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label-sm">Mật khẩu mới</label>
                            <input type="password" name="new_password" class="form-control form-control-modern"
                                   placeholder="Tối thiểu 6 ký tự" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label-sm">Xác nhận mật khẩu mới</label>
                            <input type="password" name="confirm_password" class="form-control form-control-modern"
                                   placeholder="Nhập lại mật khẩu mới" required>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn-save" style="background:linear-gradient(135deg,#f59e0b,#d97706);">
                                <i class="fas fa-key me-2"></i>Đổi mật khẩu
                            </button>
                        </div>
                    </div>
                </form>
            </div>

        </div>

        <div class="col-lg-5">

            <!-- Quick links -->
            <div class="profile-card">
                <h5><i class="fas fa-bolt text-warning"></i> Truy cập nhanh</h5>

                <a href="booking-history.php" class="quick-link">
                    <div class="d-flex align-items-center gap-2">
                        <div class="icon-wrap" style="background:#eff6ff;color:#3b82f6;">
                            <i class="fas fa-history"></i>
                        </div>
                        Lịch sử đặt sân
                    </div>
                    <span class="badge" style="background:#3b82f6;border-radius:8px;"><?php echo $totalBookings; ?></span>
                </a>

                <a href="booking-history.php" class="quick-link">
                    <div class="d-flex align-items-center gap-2">
                        <div class="icon-wrap" style="background:#f0fdf4;color:#22c55e;">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        Đơn sắp tới
                    </div>
                    <span class="badge" style="background:#22c55e;border-radius:8px;"><?php echo $upcomingCount; ?></span>
                </a>

                <a href="membership.php" class="quick-link">
                    <div class="d-flex align-items-center gap-2">
                        <div class="icon-wrap" style="background:#f0fdf4;color:#10b981;">
                            <i class="fas fa-id-card"></i>
                        </div>
                        Gói hội viên
                    </div>
                    <span style="font-size:.75rem;color:#10b981;font-weight:700;"><?php echo $membership ? 'Đang hoạt động' : 'Xem gói'; ?></span>
                </a>

                <a href="training.php" class="quick-link">
                    <div class="d-flex align-items-center gap-2">
                        <div class="icon-wrap" style="background:#fef3c7;color:#f59e0b;">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                        Đăng ký khóa học
                    </div>
                    <i class="fas fa-chevron-right" style="font-size:.75rem;color:#9ca3af;"></i>
                </a>

                <a href="booking-online.php" class="quick-link">
                    <div class="d-flex align-items-center gap-2">
                        <div class="icon-wrap" style="background:#f5f3ff;color:#8b5cf6;">
                            <i class="fas fa-calendar-plus"></i>
                        </div>
                        Đặt sân mới
                    </div>
                    <i class="fas fa-chevron-right" style="font-size:.75rem;color:#9ca3af;"></i>
                </a>

                <a href="logout.php" class="quick-link" style="color:#ef4444;border-color:#fee2e2;">
                    <div class="d-flex align-items-center gap-2">
                        <div class="icon-wrap" style="background:#fee2e2;color:#ef4444;">
                            <i class="fas fa-sign-out-alt"></i>
                        </div>
                        Đăng xuất
                    </div>
                    <i class="fas fa-chevron-right" style="font-size:.75rem;color:#ef4444;"></i>
                </a>
            </div>

            <!-- Booking gần đây -->
            <?php if (!empty($userBookings)): ?>
            <div class="profile-card">
                <h5><i class="fas fa-clock text-info"></i> Đặt sân gần đây</h5>
                <?php foreach (array_slice($userBookings, 0, 4) as $b):
                    $statusColor = $b['status'] === 'confirmed' ? '#22c55e' : ($b['status'] === 'cancelled' ? '#ef4444' : '#f59e0b');
                ?>
                <div class="booking-mini">
                    <div>
                        <div style="font-weight:700;font-size:.85rem;"><?php echo escape($b['court_name']); ?></div>
                        <div style="font-size:.75rem;color:#9ca3af;">
                            <?php echo date('d/m/Y', strtotime($b['booking_date'])); ?>
                            · <?php echo substr($b['start_time'],0,5); ?>–<?php echo substr($b['end_time'],0,5); ?>
                        </div>
                    </div>
                    <span style="background:<?php echo $statusColor; ?>;color:#fff;border-radius:8px;padding:3px 8px;font-size:.72rem;font-weight:700;">
                        <?php echo ucfirst($b['status']); ?>
                    </span>
                </div>
                <?php endforeach; ?>
                <a href="booking-history.php" style="font-size:.82rem;color:#667eea;font-weight:600;text-decoration:none;display:block;text-align:center;margin-top:.8rem;">
                    Xem tất cả →
                </a>
            </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
