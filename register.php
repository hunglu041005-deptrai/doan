<?php
require_once __DIR__ . '/includes/functions.php';

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name']     ?? '');
    $email    = trim($_POST['email']    ?? '');
    $phone    = trim($_POST['phone']    ?? '');
    $password = $_POST['password']      ?? '';
    $confirm  = $_POST['confirm']       ?? '';

    if (!$name || !$email || !$password || !$confirm) {
        $error = 'Vui lòng điền đầy đủ thông tin bắt buộc.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Địa chỉ email không hợp lệ.';
    } elseif (strlen($password) < 6) {
        $error = 'Mật khẩu phải có ít nhất 6 ký tự.';
    } elseif ($password !== $confirm) {
        $error = 'Mật khẩu xác nhận không khớp.';
    } else {
        $stmt = $mysqli->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $error = 'Email này đã được đăng ký. Vui lòng đăng nhập.';
        } else {
            $hash   = password_hash($password, PASSWORD_DEFAULT);
            $insert = $mysqli->prepare('INSERT INTO users (name, email, password, phone) VALUES (?,?,?,?)');
            $insert->bind_param('ssss', $name, $email, $hash, $phone);
            if ($insert->execute()) {
                $_SESSION['user_id'] = $insert->insert_id;
                $_SESSION['name']    = $name;
                $_SESSION['email']   = $email;
                $_SESSION['role']    = 'user';
                redirect('index.php');
            } else {
                $error = 'Đăng ký thất bại. Vui lòng thử lại.';
            }
            $insert->close();
        }
        $stmt->close();
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<style>
/* ===== REGISTER PAGE ===== */
.reg-page {
    min-height: calc(100vh - 60px);
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    padding: 2rem 0;
}

.reg-card {
    background: #fff;
    border-radius: 24px;
    overflow: hidden;
    box-shadow: 0 25px 60px rgba(0,0,0,.2);
    display: flex;
    max-width: 900px;
    width: 100%;
    margin: 0 auto;
}

/* Left panel */
.reg-left {
    background: linear-gradient(160deg, #0f0c29 0%, #302b63 60%, #24243e 100%);
    padding: 3rem 2.5rem;
    color: #fff;
    display: flex;
    flex-direction: column;
    justify-content: center;
    min-width: 320px;
    position: relative;
    overflow: hidden;
}
.reg-left::before {
    content: '';
    position: absolute; top: -80px; right: -80px;
    width: 260px; height: 260px;
    background: rgba(251,191,36,.12); border-radius: 50%;
}
.reg-left::after {
    content: '';
    position: absolute; bottom: -60px; left: -40px;
    width: 200px; height: 200px;
    background: rgba(99,102,241,.12); border-radius: 50%;
}
.reg-left-content { position: relative; z-index: 1; }
.reg-brand { display: flex; align-items: center; gap: .6rem; margin-bottom: 2.5rem; }
.reg-brand-name { font-size: 1.4rem; font-weight: 900; color: #fff; }
.reg-brand-name span { color: #ff6b35; }
.reg-feature { display: flex; align-items: center; gap: .9rem; padding: .75rem 0; }
.reg-feature-icon {
    width: 40px; height: 40px; border-radius: 12px;
    background: rgba(255,255,255,.12);
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.reg-feature-text .title { font-weight: 700; font-size: .9rem; }
.reg-feature-text .sub   { font-size: .75rem; color: rgba(255,255,255,.55); margin-top: 1px; }
.reg-divider { height: 1px; background: rgba(255,255,255,.1); margin: 1.5rem 0; }
.reg-login-link { font-size: .85rem; color: rgba(255,255,255,.6); text-align: center; margin-top: 2rem; }
.reg-login-link a { color: #fbbf24; font-weight: 700; text-decoration: none; }

/* Right panel */
.reg-right { flex: 1; padding: 3rem 2.5rem; }
.reg-title { font-size: 1.6rem; font-weight: 900; color: #111827; margin-bottom: .3rem; }
.reg-subtitle { color: #6b7280; font-size: .9rem; margin-bottom: 2rem; }

/* Input groups */
.reg-field { margin-bottom: 1.1rem; }
.reg-label {
    font-size: .82rem; font-weight: 700; color: #374151;
    margin-bottom: .35rem; display: flex; align-items: center; gap: .4rem;
}
.reg-label i { color: #6366f1; font-size: .78rem; }
.reg-input-wrap { position: relative; }
.reg-input {
    width: 100%; background: #f9fafb;
    border: 1.5px solid #e5e7eb; border-radius: 12px;
    padding: .72rem 1rem .72rem 2.6rem;
    font-size: .9rem; color: #111827; transition: all .2s;
}
.reg-input:focus {
    outline: none; border-color: #6366f1; background: #fff;
    box-shadow: 0 0 0 3px rgba(99,102,241,.12);
}
.reg-input.error  { border-color: #ef4444; }
.reg-input.success { border-color: #10b981; }
.reg-icon {
    position: absolute; left: .9rem; top: 50%; transform: translateY(-50%);
    color: #9ca3af; font-size: .85rem; pointer-events: none;
}
.reg-toggle {
    position: absolute; right: .9rem; top: 50%; transform: translateY(-50%);
    color: #9ca3af; background: none; border: none; cursor: pointer;
    font-size: .85rem; padding: 0;
}
.reg-toggle:hover { color: #6366f1; }

/* Password strength */
.pw-strength { margin-top: .4rem; }
.pw-bars { display: flex; gap: 4px; margin-bottom: .25rem; }
.pw-bar {
    height: 4px; flex: 1; border-radius: 2px;
    background: #e5e7eb; transition: background .3s;
}
.pw-bar.weak   { background: #ef4444; }
.pw-bar.fair   { background: #f59e0b; }
.pw-bar.good   { background: #3b82f6; }
.pw-bar.strong { background: #10b981; }
.pw-label { font-size: .72rem; color: #9ca3af; }

/* Submit */
.reg-submit {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: #fff; border: none; border-radius: 12px;
    padding: .85rem; font-weight: 800; font-size: .95rem;
    width: 100%; cursor: pointer; transition: all .2s;
    box-shadow: 0 6px 20px rgba(102,126,234,.35);
    margin-top: .5rem;
}
.reg-submit:hover { transform: translateY(-2px); box-shadow: 0 10px 28px rgba(102,126,234,.45); }
.reg-submit:disabled { opacity: .7; cursor: not-allowed; transform: none; }

/* Alert */
.reg-alert {
    border-radius: 12px; padding: .85rem 1rem;
    font-size: .85rem; margin-bottom: 1.2rem;
    display: flex; align-items: center; gap: .6rem;
}
.reg-alert.danger  { background: #fef2f2; border: 1px solid #fca5a5; color: #dc2626; }
.reg-alert.success { background: #f0fdf4; border: 1px solid #86efac; color: #16a34a; }

/* Terms */
.reg-terms { font-size: .78rem; color: #9ca3af; text-align: center; margin-top: .8rem; }
.reg-terms a { color: #6366f1; }

@media (max-width: 768px) {
    .reg-card { flex-direction: column; border-radius: 16px; }
    .reg-left { min-width: unset; padding: 2rem 1.5rem; }
    .reg-right { padding: 2rem 1.5rem; }
}
</style>

<div class="reg-page">
<div class="container px-3">
<div class="reg-card">

    <!-- ── Left panel ── -->
    <div class="reg-left">
        <div class="reg-left-content">
            <div class="reg-brand">
                <i class="fas fa-badminton" style="font-size:1.6rem;color:#ff6b35;"></i>
                <span class="reg-brand-name">Badminton<span>Pro</span></span>
            </div>

            <h2 style="font-size:1.4rem;font-weight:800;margin-bottom:.5rem;">Tham gia ngay!</h2>
            <p style="color:rgba(255,255,255,.55);font-size:.85rem;margin-bottom:1.8rem;">
                Đặt sân cầu lông trực tuyến — nhanh, dễ, tiết kiệm
            </p>

            <div class="reg-feature">
                <div class="reg-feature-icon"><i class="fas fa-calendar-check" style="color:#fbbf24;"></i></div>
                <div class="reg-feature-text">
                    <div class="title">Đặt sân 24/7</div>
                    <div class="sub">Mọi lúc, mọi nơi, không cần gọi điện</div>
                </div>
            </div>
            <div class="reg-feature">
                <div class="reg-feature-icon"><i class="fas fa-tags" style="color:#4ade80;"></i></div>
                <div class="reg-feature-text">
                    <div class="title">Giá ưu đãi thành viên</div>
                    <div class="sub">Hội viên được giá cố định 80K/giờ</div>
                </div>
            </div>
            <div class="reg-feature">
                <div class="reg-feature-icon"><i class="fas fa-bolt" style="color:#a78bfa;"></i></div>
                <div class="reg-feature-text">
                    <div class="title">Xác nhận tức thì</div>
                    <div class="sub">Nhận thông báo ngay khi đặt thành công</div>
                </div>
            </div>
            <div class="reg-feature">
                <div class="reg-feature-icon"><i class="fas fa-star" style="color:#fb923c;"></i></div>
                <div class="reg-feature-text">
                    <div class="title">Đánh giá & Tích điểm</div>
                    <div class="sub">Nhận ưu đãi từ mỗi lần đặt sân</div>
                </div>
            </div>

            <div class="reg-divider"></div>
            <div class="reg-login-link">
                Đã có tài khoản?
                <a href="login.php"><i class="fas fa-sign-in-alt me-1"></i>Đăng nhập ngay</a>
            </div>
        </div>
    </div>

    <!-- ── Right panel ── -->
    <div class="reg-right">
        <div class="reg-title">Tạo tài khoản</div>
        <div class="reg-subtitle">Điền thông tin bên dưới để bắt đầu</div>

        <?php if ($error): ?>
        <div class="reg-alert danger">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo escape($error); ?>
        </div>
        <?php endif; ?>

        <form method="POST" id="regForm" novalidate>

            <!-- Họ tên -->
            <div class="reg-field">
                <label class="reg-label"><i class="fas fa-user"></i> Họ và tên <span style="color:#ef4444;">*</span></label>
                <div class="reg-input-wrap">
                    <i class="fas fa-user reg-icon"></i>
                    <input type="text" name="name" class="reg-input" id="inp_name"
                           value="<?php echo escape($_POST['name'] ?? ''); ?>"
                           placeholder="Nguyễn Văn A" required>
                </div>
            </div>

            <!-- Email -->
            <div class="reg-field">
                <label class="reg-label"><i class="fas fa-envelope"></i> Email <span style="color:#ef4444;">*</span></label>
                <div class="reg-input-wrap">
                    <i class="fas fa-envelope reg-icon"></i>
                    <input type="email" name="email" class="reg-input" id="inp_email"
                           value="<?php echo escape($_POST['email'] ?? ''); ?>"
                           placeholder="email@example.com" required>
                </div>
            </div>

            <!-- SĐT -->
            <div class="reg-field">
                <label class="reg-label"><i class="fas fa-phone"></i> Số điện thoại</label>
                <div class="reg-input-wrap">
                    <i class="fas fa-phone reg-icon"></i>
                    <input type="tel" name="phone" class="reg-input" id="inp_phone"
                           value="<?php echo escape($_POST['phone'] ?? ''); ?>"
                           placeholder="0912 345 678">
                </div>
            </div>

            <!-- Mật khẩu -->
            <div class="reg-field">
                <label class="reg-label"><i class="fas fa-lock"></i> Mật khẩu <span style="color:#ef4444;">*</span></label>
                <div class="reg-input-wrap">
                    <i class="fas fa-lock reg-icon"></i>
                    <input type="password" name="password" class="reg-input" id="inp_password"
                           placeholder="Ít nhất 6 ký tự" required>
                    <button type="button" class="reg-toggle" onclick="togglePw('inp_password','ico_pw1')">
                        <i class="fas fa-eye" id="ico_pw1"></i>
                    </button>
                </div>
                <!-- Strength indicator -->
                <div class="pw-strength" id="pwStrength" style="display:none;">
                    <div class="pw-bars">
                        <div class="pw-bar" id="bar1"></div>
                        <div class="pw-bar" id="bar2"></div>
                        <div class="pw-bar" id="bar3"></div>
                        <div class="pw-bar" id="bar4"></div>
                    </div>
                    <div class="pw-label" id="pwLabel">Độ mạnh mật khẩu</div>
                </div>
            </div>

            <!-- Xác nhận mật khẩu -->
            <div class="reg-field">
                <label class="reg-label"><i class="fas fa-shield-alt"></i> Xác nhận mật khẩu <span style="color:#ef4444;">*</span></label>
                <div class="reg-input-wrap">
                    <i class="fas fa-shield-alt reg-icon"></i>
                    <input type="password" name="confirm" class="reg-input" id="inp_confirm"
                           placeholder="Nhập lại mật khẩu" required>
                    <button type="button" class="reg-toggle" onclick="togglePw('inp_confirm','ico_pw2')">
                        <i class="fas fa-eye" id="ico_pw2"></i>
                    </button>
                </div>
                <div id="confirmMsg" style="font-size:.72rem;margin-top:.25rem;display:none;"></div>
            </div>

            <button type="submit" class="reg-submit" id="regSubmit">
                <i class="fas fa-user-plus me-2"></i>Tạo tài khoản
            </button>

            <p class="reg-terms">
                Bằng cách đăng ký, bạn đồng ý với
                <a href="#">Điều khoản dịch vụ</a> và <a href="#">Chính sách bảo mật</a>
            </p>
        </form>
    </div>

</div>
</div>
</div>

<script>
// Toggle show/hide password
function togglePw(inputId, iconId) {
    const inp  = document.getElementById(inputId);
    const ico  = document.getElementById(iconId);
    const show = inp.type === 'password';
    inp.type = show ? 'text' : 'password';
    ico.className = show ? 'fas fa-eye-slash' : 'fas fa-eye';
}

// Password strength
document.getElementById('inp_password').addEventListener('input', function() {
    const val = this.value;
    const st  = document.getElementById('pwStrength');
    if (!val) { st.style.display = 'none'; return; }
    st.style.display = 'block';

    let score = 0;
    if (val.length >= 6)  score++;
    if (val.length >= 10) score++;
    if (/[A-Z]/.test(val) && /[a-z]/.test(val)) score++;
    if (/[0-9]/.test(val) || /[^A-Za-z0-9]/.test(val)) score++;

    const levels = ['','weak','fair','good','strong'];
    const labels = ['','Yếu','Trung bình','Khá','Mạnh'];
    const colors = ['','#ef4444','#f59e0b','#3b82f6','#10b981'];

    for (let i = 1; i <= 4; i++) {
        const bar = document.getElementById('bar' + i);
        bar.className = 'pw-bar' + (i <= score ? ' ' + levels[score] : '');
    }
    document.getElementById('pwLabel').textContent = labels[score] || '';
    document.getElementById('pwLabel').style.color = colors[score];
});

// Confirm password match
document.getElementById('inp_confirm').addEventListener('input', function() {
    const pw  = document.getElementById('inp_password').value;
    const msg = document.getElementById('confirmMsg');
    msg.style.display = 'block';
    if (this.value === pw && this.value) {
        msg.textContent = '✅ Mật khẩu khớp';
        msg.style.color = '#10b981';
        this.classList.remove('error'); this.classList.add('success');
    } else {
        msg.textContent = '❌ Mật khẩu không khớp';
        msg.style.color = '#ef4444';
        this.classList.remove('success'); this.classList.add('error');
    }
});

// Submit loading state
document.getElementById('regForm').addEventListener('submit', function() {
    const btn = document.getElementById('regSubmit');
    btn.innerHTML = '<div class="spinner-border spinner-border-sm me-2"></div>Đang xử lý...';
    btn.disabled = true;
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
