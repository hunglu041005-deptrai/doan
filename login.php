<?php
require_once __DIR__ . '/includes/functions.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = $_POST['password']      ?? '';

    if (!$email || !$password) {
        $error = 'Vui lòng điền email và mật khẩu.';
    } else {
        $stmt = $mysqli->prepare('SELECT id, name, email, password, role, status FROM users WHERE email = ? LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$user) {
            $error = 'Email hoặc mật khẩu không đúng.';
        } elseif ($user['status'] != 1) {
            $error = 'Tài khoản đang bị khóa. Liên hệ hỗ trợ.';
        } else {
            // Kiểm tra mật khẩu: hỗ trợ cả bcrypt lẫn plain text
            $pwOk = false;
            if (password_verify($password, $user['password'])) {
                $pwOk = true;
            } elseif ($user['password'] === $password) {
                // Plain text — tự động hash lại
                $newHash = password_hash($password, PASSWORD_DEFAULT);
                $upd = $mysqli->prepare('UPDATE users SET password=? WHERE id=?');
                $upd->bind_param('si', $newHash, $user['id']);
                $upd->execute();
                $upd->close();
                $pwOk = true;
            }

            if (!$pwOk) {
                $error = 'Email hoặc mật khẩu không đúng.';
            } else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name']    = $user['name'];
                $_SESSION['email']   = $user['email'];
                $_SESSION['role']    = $user['role'];

                if ($user['role'] === 'admin') {
                    header('Location: admin/dashboard.php'); exit;
                } elseif ($user['role'] === 'coach') {
                    header('Location: coach/dashboard.php'); exit;
                } else {
                    $redirect = $_GET['redirect'] ?? 'index.php';
                    header('Location: ' . (strpos($redirect, 'http') === false ? $redirect : 'index.php'));
                    exit;
                }
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<style>
/* ===== LOGIN PAGE ===== */
.login-page {
    min-height: calc(100vh - 60px);
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    padding: 2rem 0;
}

.login-card {
    background: #fff;
    border-radius: 24px;
    overflow: hidden;
    box-shadow: 0 25px 60px rgba(0,0,0,.2);
    display: flex;
    max-width: 860px;
    width: 100%;
    margin: 0 auto;
}

/* ── Left panel ── */
.login-left {
    background: linear-gradient(160deg, #0f0c29 0%, #302b63 60%, #24243e 100%);
    padding: 3rem 2.5rem;
    color: #fff;
    display: flex;
    flex-direction: column;
    justify-content: center;
    min-width: 300px;
    position: relative;
    overflow: hidden;
}
.login-left::before {
    content: '';
    position: absolute; top: -80px; right: -80px;
    width: 260px; height: 260px;
    background: rgba(251,191,36,.12); border-radius: 50%;
}
.login-left::after {
    content: '';
    position: absolute; bottom: -60px; left: -40px;
    width: 200px; height: 200px;
    background: rgba(99,102,241,.12); border-radius: 50%;
}
.ll-content { position: relative; z-index: 1; }
.ll-brand { display: flex; align-items: center; gap: .6rem; margin-bottom: 2.5rem; }
.ll-brand-name { font-size: 1.4rem; font-weight: 900; color: #fff; }
.ll-brand-name span { color: #ff6b35; }
.ll-feature { display: flex; align-items: center; gap: .9rem; padding: .7rem 0; }
.ll-icon {
    width: 40px; height: 40px; border-radius: 12px;
    background: rgba(255,255,255,.12);
    display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.ll-feature .title { font-weight: 700; font-size: .88rem; }
.ll-feature .sub   { font-size: .74rem; color: rgba(255,255,255,.5); margin-top: 1px; }
.ll-divider { height: 1px; background: rgba(255,255,255,.1); margin: 1.5rem 0; }
.ll-reg-link { font-size: .85rem; color: rgba(255,255,255,.6); text-align: center; margin-top: 1.5rem; }
.ll-reg-link a { color: #fbbf24; font-weight: 700; text-decoration: none; }

/* ── Right panel ── */
.login-right { flex: 1; padding: 3rem 2.5rem; display: flex; flex-direction: column; justify-content: center; }
.login-title    { font-size: 1.6rem; font-weight: 900; color: #111827; margin-bottom: .3rem; }
.login-subtitle { color: #6b7280; font-size: .9rem; margin-bottom: 2rem; }

/* Fields */
.lf-field { margin-bottom: 1.1rem; }
.lf-label {
    font-size: .82rem; font-weight: 700; color: #374151;
    margin-bottom: .35rem; display: flex; align-items: center; gap: .4rem;
}
.lf-label i { color: #6366f1; font-size: .78rem; }
.lf-wrap { position: relative; }
.lf-input {
    width: 100%; background: #f9fafb;
    border: 1.5px solid #e5e7eb; border-radius: 12px;
    padding: .72rem 1rem .72rem 2.6rem;
    font-size: .9rem; color: #111827; transition: all .2s;
}
.lf-input:focus {
    outline: none; border-color: #6366f1; background: #fff;
    box-shadow: 0 0 0 3px rgba(99,102,241,.12);
}
.lf-icon {
    position: absolute; left: .9rem; top: 50%; transform: translateY(-50%);
    color: #9ca3af; font-size: .85rem; pointer-events: none;
}
.lf-toggle {
    position: absolute; right: .9rem; top: 50%; transform: translateY(-50%);
    background: none; border: none; color: #9ca3af; cursor: pointer; padding: 0;
}
.lf-toggle:hover { color: #6366f1; }

/* Submit */
.login-submit {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: #fff; border: none; border-radius: 12px;
    padding: .85rem; font-weight: 800; font-size: .95rem;
    width: 100%; cursor: pointer; transition: all .2s;
    box-shadow: 0 6px 20px rgba(102,126,234,.35);
    margin-top: .3rem;
}
.login-submit:hover { transform: translateY(-2px); box-shadow: 0 10px 28px rgba(102,126,234,.45); }
.login-submit:disabled { opacity: .7; cursor: not-allowed; transform: none; }

/* Alert */
.lf-alert {
    border-radius: 12px; padding: .85rem 1rem;
    font-size: .85rem; margin-bottom: 1.2rem;
    display: flex; align-items: center; gap: .6rem;
}
.lf-alert.danger  { background: #fef2f2; border: 1px solid #fca5a5; color: #dc2626; }
.lf-alert.success { background: #f0fdf4; border: 1px solid #86efac; color: #16a34a; }

/* Social divider */
.lf-or {
    display: flex; align-items: center; gap: .8rem;
    color: #9ca3af; font-size: .8rem; margin: 1.2rem 0;
}
.lf-or::before, .lf-or::after {
    content: ''; flex: 1; height: 1px; background: #e5e7eb;
}

/* Demo buttons */
.demo-btn {
    display: flex; align-items: center; justify-content: center; gap: .5rem;
    border: 1.5px solid #e5e7eb; border-radius: 10px;
    padding: .55rem; font-size: .8rem; font-weight: 600;
    background: #fff; cursor: pointer; transition: all .2s; color: #374151;
}
.demo-btn:hover { border-color: #6366f1; background: #f8f7ff; color: #6366f1; }

@media (max-width: 768px) {
    .login-card { flex-direction: column; border-radius: 16px; }
    .login-left { min-width: unset; padding: 2rem 1.5rem; }
    .login-right { padding: 2rem 1.5rem; }
}
</style>

<div class="login-page">
<div class="container px-3">
<div class="login-card">

    <!-- ── Left panel ── -->
    <div class="login-left">
        <div class="ll-content">
            <div class="ll-brand">
                <i class="fas fa-badminton" style="font-size:1.6rem;color:#ff6b35;"></i>
                <span class="ll-brand-name">Badminton<span>Pro</span></span>
            </div>

            <h2 style="font-size:1.4rem;font-weight:800;margin-bottom:.5rem;">Chào mừng trở lại!</h2>
            <p style="color:rgba(255,255,255,.55);font-size:.85rem;margin-bottom:1.8rem;">
                Đăng nhập để tiếp tục đặt sân yêu thích
            </p>

            <div class="ll-feature">
                <div class="ll-icon"><i class="fas fa-shield-alt" style="color:#4ade80;"></i></div>
                <div>
                    <div class="title">Bảo mật tuyệt đối</div>
                    <div class="sub">Dữ liệu mã hóa SSL 256-bit</div>
                </div>
            </div>
            <div class="ll-feature">
                <div class="ll-icon"><i class="fas fa-history" style="color:#fbbf24;"></i></div>
                <div>
                    <div class="title">Lịch sử đặt sân</div>
                    <div class="sub">Xem và quản lý booking dễ dàng</div>
                </div>
            </div>
            <div class="ll-feature">
                <div class="ll-icon"><i class="fas fa-bell" style="color:#a78bfa;"></i></div>
                <div>
                    <div class="title">Thông báo realtime</div>
                    <div class="sub">Nhận xác nhận ngay lập tức</div>
                </div>
            </div>
            <div class="ll-feature">
                <div class="ll-icon"><i class="fas fa-gift" style="color:#fb923c;"></i></div>
                <div>
                    <div class="title">Ưu đãi thành viên</div>
                    <div class="sub">Giá cố định 80K/giờ khi mua gói</div>
                </div>
            </div>

            <div class="ll-divider"></div>
            <div class="ll-reg-link">
                Chưa có tài khoản?
                <a href="register.php"><i class="fas fa-user-plus me-1"></i>Đăng ký miễn phí</a>
            </div>
        </div>
    </div>

    <!-- ── Right panel ── -->
    <div class="login-right">
        <div class="login-title">Đăng nhập</div>
        <div class="login-subtitle">Nhập thông tin tài khoản của bạn</div>

        <?php if (isset($_GET['message']) && $_GET['message'] === 'logout_success'): ?>
        <div class="lf-alert success">
            <i class="fas fa-check-circle"></i> Đăng xuất thành công!
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="lf-alert danger">
            <i class="fas fa-exclamation-circle"></i> <?php echo escape($error); ?>
        </div>
        <?php endif; ?>

        <form method="POST" id="loginForm">

            <!-- Email -->
            <div class="lf-field">
                <label class="lf-label"><i class="fas fa-envelope"></i> Email <span style="color:#ef4444;">*</span></label>
                <div class="lf-wrap">
                    <i class="fas fa-envelope lf-icon"></i>
                    <input type="email" name="email" class="lf-input" id="inp_email"
                           value="<?php echo escape($_POST['email'] ?? ''); ?>"
                           placeholder="email@example.com" required>
                </div>
            </div>

            <!-- Mật khẩu -->
            <div class="lf-field">
                <label class="lf-label"><i class="fas fa-lock"></i> Mật khẩu <span style="color:#ef4444;">*</span></label>
                <div class="lf-wrap">
                    <i class="fas fa-lock lf-icon"></i>
                    <input type="password" name="password" class="lf-input" id="inp_password"
                           placeholder="Nhập mật khẩu" required style="padding-right:2.8rem;">
                    <button type="button" class="lf-toggle" onclick="togglePw()">
                        <i class="fas fa-eye" id="pw_ico"></i>
                    </button>
                </div>
            </div>

            <!-- Remember + Forgot -->
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.2rem;font-size:.82rem;">
                <label style="display:flex;align-items:center;gap:.4rem;color:#6b7280;cursor:pointer;">
                    <input type="checkbox" id="rememberMe" style="accent-color:#6366f1;width:15px;height:15px;">
                    Ghi nhớ đăng nhập
                </label>
                <a href="#" style="color:#6366f1;font-weight:700;text-decoration:none;"
                   data-bs-toggle="modal" data-bs-target="#forgotModal">Quên mật khẩu?</a>
            </div>

            <button type="submit" class="login-submit" id="loginSubmit">
                <i class="fas fa-sign-in-alt me-2"></i>Đăng nhập
            </button>
        </form>

        <!-- Demo accounts -->
        <div class="lf-or">Tài khoản demo</div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:.6rem;">
            <button class="demo-btn" onclick="fillDemo('admin@badminton.local','admin123')">
                <i class="fas fa-user-shield" style="color:#6366f1;"></i> Admin
            </button>
            <button class="demo-btn" onclick="fillDemo('user@example.com','password123')">
                <i class="fas fa-user" style="color:#10b981;"></i> User
            </button>
        </div>

        <p style="text-align:center;margin-top:1.5rem;font-size:.82rem;color:#9ca3af;">
            Chưa có tài khoản?
            <a href="register.php" style="color:#6366f1;font-weight:700;text-decoration:none;">
                Đăng ký ngay <i class="fas fa-arrow-right ms-1"></i>
            </a>
        </p>
    </div>

</div>
</div>
</div>

<!-- Modal quên mật khẩu -->
<div class="modal fade" id="forgotModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:420px;">
        <div class="modal-content border-0 shadow-lg" style="border-radius:20px;overflow:hidden;">
            <div style="background:linear-gradient(135deg,#667eea,#764ba2);padding:1.3rem 1.5rem;color:#fff;">
                <div style="display:flex;align-items:center;justify-content:space-between;">
                    <h5 style="margin:0;font-weight:800;"><i class="fas fa-key me-2"></i>Quên mật khẩu</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <small style="opacity:.75;">Nhập email để nhận liên kết đặt lại mật khẩu</small>
            </div>
            <div style="padding:1.5rem;">
                <div style="position:relative;margin-bottom:1rem;">
                    <i class="fas fa-envelope" style="position:absolute;left:.9rem;top:50%;transform:translateY(-50%);color:#9ca3af;"></i>
                    <input type="email" id="forgotEmail" class="lf-input" placeholder="email@example.com">
                </div>
                <button onclick="sendForgot()" class="login-submit" style="margin:0;">
                    <i class="fas fa-paper-plane me-2"></i>Gửi liên kết
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function togglePw() {
    const inp = document.getElementById('inp_password');
    const ico = document.getElementById('pw_ico');
    const show = inp.type === 'password';
    inp.type = show ? 'text' : 'password';
    ico.className = show ? 'fas fa-eye-slash' : 'fas fa-eye';
}

function fillDemo(email, pw) {
    document.getElementById('inp_email').value    = email;
    document.getElementById('inp_password').value = pw;
    // Flash green
    ['inp_email','inp_password'].forEach(id => {
        const el = document.getElementById(id);
        el.style.borderColor = '#10b981';
        setTimeout(() => el.style.borderColor = '', 1200);
    });
}

function sendForgot() {
    const email = document.getElementById('forgotEmail').value.trim();
    if (!email) return;
    alert('Liên kết đặt lại mật khẩu đã được gửi đến ' + email);
    bootstrap.Modal.getInstance(document.getElementById('forgotModal')).hide();
}

document.getElementById('loginForm').addEventListener('submit', function() {
    const btn = document.getElementById('loginSubmit');
    btn.innerHTML = '<div class="spinner-border spinner-border-sm me-2"></div>Đang đăng nhập...';
    btn.disabled = true;
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
