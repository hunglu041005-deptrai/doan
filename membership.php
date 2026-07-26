<?php
require_once __DIR__ . '/includes/functions.php';

// Thêm cột tickets_used nếu chưa có (kiểm tra trước để tránh lỗi duplicate)
$col_check = $mysqli->query("SHOW COLUMNS FROM memberships LIKE 'tickets_used'");
if ($col_check && $col_check->num_rows === 0) {
    $mysqli->query("ALTER TABLE memberships ADD COLUMN tickets_used INT NOT NULL DEFAULT 0");
}

// Auto-expire và lấy membership hiện tại
$activeMembership = isLoggedIn() ? getActiveMembership((int)$_SESSION['user_id']) : null;
$ticketsRemaining = $activeMembership ? getMembershipTicketsRemaining($activeMembership) : 0;
$allMemberships   = isLoggedIn() ? getUserMemberships((int)$_SESSION['user_id']) : [];

// Lọc sân được chọn
$selectedCourtId = isset($_GET['court_id']) ? (int)$_GET['court_id'] : 0;

// Lấy tất cả sân để hiển thị dropdown
$allCourts = $mysqli->query("SELECT id, name, location, cover_image FROM courts WHERE status=1 ORDER BY name")->fetch_all(MYSQLI_ASSOC);

// Lấy gói từ DB — tạo bảng trước nếu chưa có
$mysqli->query("CREATE TABLE IF NOT EXISTS membership_plans (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    court_id     INT DEFAULT NULL,
    name         VARCHAR(150) NOT NULL,
    detail       VARCHAR(150) NOT NULL,
    price_per    INT NOT NULL DEFAULT 80000,
    total_price  INT NOT NULL DEFAULT 720000,
    months       INT NOT NULL DEFAULT 3,
    free_tickets INT NOT NULL DEFAULT 11,
    time_range   VARCHAR(30) DEFAULT '14H-17H',
    sale_start   DATE DEFAULT NULL,
    sale_end     DATE DEFAULT NULL,
    status       TINYINT DEFAULT 1,
    sort_order   INT DEFAULT 0,
    created_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_court (court_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$today = date('Y-m-d');
$plansQuery = $selectedCourtId
    ? $mysqli->prepare("SELECT mp.*, c.name AS court_name FROM membership_plans mp LEFT JOIN courts c ON c.id=mp.court_id WHERE mp.status=1 AND (mp.court_id=? OR mp.court_id IS NULL) AND (mp.sale_start IS NULL OR mp.sale_start<=?) AND (mp.sale_end IS NULL OR mp.sale_end>=?) ORDER BY mp.court_id IS NOT NULL DESC, mp.sort_order, mp.id")
    : $mysqli->prepare("SELECT mp.*, c.name AS court_name FROM membership_plans mp LEFT JOIN courts c ON c.id=mp.court_id WHERE mp.status=1 AND (mp.sale_start IS NULL OR mp.sale_start<=?) AND (mp.sale_end IS NULL OR mp.sale_end>=?) ORDER BY mp.court_id IS NULL DESC, mp.sort_order, mp.id");

if ($selectedCourtId) { $plansQuery->bind_param('iss', $selectedCourtId, $today, $today); }
else                  { $plansQuery->bind_param('ss', $today, $today); }
$plansQuery->execute();
$dbPlans = $plansQuery->get_result()->fetch_all(MYSQLI_ASSOC);
$plansQuery->close();

// Fallback nếu DB chưa có
if (empty($dbPlans)) {
    $dbPlans = [
        ['id'=>1,'court_id'=>null,'court_name'=>null,'name'=>'COMBO CHIỀU 14H–17H','detail'=>'5 VÉ TẶNG 0 VÉ', 'price_per'=>80000,'total_price'=>400000, 'months'=>1, 'free_tickets'=>5, 'time_range'=>'14H–17H','sale_start'=>null,'sale_end'=>null],
        ['id'=>2,'court_id'=>null,'court_name'=>null,'name'=>'COMBO CHIỀU 14H–17H','detail'=>'10 VÉ TẶNG 1 VÉ','price_per'=>80000,'total_price'=>720000, 'months'=>3, 'free_tickets'=>11,'time_range'=>'14H–17H','sale_start'=>null,'sale_end'=>null],
        ['id'=>3,'court_id'=>null,'court_name'=>null,'name'=>'COMBO CHIỀU 14H–17H','detail'=>'20 VÉ TẶNG 2 VÉ','price_per'=>80000,'total_price'=>1440000,'months'=>6, 'free_tickets'=>22,'time_range'=>'14H–17H','sale_start'=>null,'sale_end'=>null],
        ['id'=>4,'court_id'=>null,'court_name'=>null,'name'=>'COMBO TỐI 20H–22H',  'detail'=>'20 VÉ TẶNG 2 VÉ','price_per'=>80000,'total_price'=>1440000,'months'=>9, 'free_tickets'=>22,'time_range'=>'20H–22H','sale_start'=>null,'sale_end'=>null],
        ['id'=>5,'court_id'=>null,'court_name'=>null,'name'=>'COMBO TỐI 20H–22H',  'detail'=>'30 VÉ TẶNG 3 VÉ','price_per'=>80000,'total_price'=>2160000,'months'=>12,'free_tickets'=>33,'time_range'=>'20H–22H','sale_start'=>null,'sale_end'=>null],
        ['id'=>6,'court_id'=>null,'court_name'=>null,'name'=>'COMBO CHIỀU 15H–18H','detail'=>'10 VÉ TẶNG 1 VÉ','price_per'=>80000,'total_price'=>720000, 'months'=>3, 'free_tickets'=>11,'time_range'=>'15H–18H','sale_start'=>null,'sale_end'=>null],
        ['id'=>7,'court_id'=>null,'court_name'=>null,'name'=>'COMBO CHIỀU 15H–18H','detail'=>'20 VÉ TẶNG 2 VÉ','price_per'=>80000,'total_price'=>1440000,'months'=>6, 'free_tickets'=>22,'time_range'=>'15H–18H','sale_start'=>null,'sale_end'=>null],
    ];
}

// Tên sân đang chọn
$selectedCourtName = '';
if ($selectedCourtId) {
    foreach ($allCourts as $c) { if ($c['id'] == $selectedCourtId) { $selectedCourtName = $c['name']; break; } }
}

// plans array cho JS (legacy format)
$plans = [];
foreach ($dbPlans as $p) {
    $plans[] = ['id'=>$p['id'],'label'=>$p['name'],'sub'=>$p['detail'],'price'=>(int)$p['total_price'],'months'=>(int)$p['months'],'free'=>(int)$p['free_tickets'],'time'=>$p['time_range'],'price_per'=>(int)$p['price_per'],'popular'=>false,'court_id'=>$p['court_id'],'court_name'=>$p['court_name'],'sale_start'=>$p['sale_start'],'sale_end'=>$p['sale_end']];
}

require_once __DIR__ . '/includes/header.php';
?>

<style>
/* ===== MEMBERSHIP PAGE ===== */
.membership-page {
    background: #f0f4f8;
    min-height: 100vh;
    padding-bottom: 3rem;
}

/* Hero Banner */
.membership-hero {
    background: linear-gradient(135deg, #1a1a2e 0%, #16213e 40%, #0f3460 100%);
    padding: 4rem 0 6rem;
    position: relative;
    overflow: hidden;
}

.membership-hero::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -10%;
    width: 600px;
    height: 600px;
    background: radial-gradient(circle, rgba(40,167,69,.15) 0%, transparent 70%);
    border-radius: 50%;
}

.membership-hero::after {
    content: '';
    position: absolute;
    bottom: -30%;
    left: -5%;
    width: 400px;
    height: 400px;
    background: radial-gradient(circle, rgba(102,126,234,.1) 0%, transparent 70%);
    border-radius: 50%;
}

.hero-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(40,167,69,.2);
    border: 1px solid rgba(40,167,69,.4);
    color: #4ade80;
    padding: 6px 16px;
    border-radius: 50px;
    font-size: .82rem;
    font-weight: 600;
    margin-bottom: 1.5rem;
}

.hero-title {
    font-size: 2.8rem;
    font-weight: 800;
    color: #fff;
    line-height: 1.2;
    margin-bottom: 1rem;
}

.hero-title span {
    background: linear-gradient(135deg, #4ade80, #22d3ee);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.hero-subtitle {
    color: rgba(255,255,255,.65);
    font-size: 1.05rem;
    margin-bottom: 2rem;
    max-width: 500px;
}

.hero-stats {
    display: flex;
    gap: 2rem;
    flex-wrap: wrap;
}

.hero-stat {
    text-align: center;
}

.hero-stat .num {
    font-size: 1.8rem;
    font-weight: 800;
    color: #4ade80;
    display: block;
}

.hero-stat .lbl {
    font-size: .78rem;
    color: rgba(255,255,255,.5);
}

/* Wave divider */
.wave-divider {
    margin-top: -2px;
    line-height: 0;
}

.wave-divider svg {
    display: block;
    width: 100%;
}

/* Plans section */
.plans-section {
    padding: 3rem 0;
}

.section-header {
    text-align: center;
    margin-bottom: 2.5rem;
}

.section-header h2 {
    font-size: 1.8rem;
    font-weight: 800;
    color: #1a1a2e;
    margin-bottom: .5rem;
}

.section-header p {
    color: #6b7280;
    font-size: .95rem;
}

/* Plan card */
.plan-card {
    background: #fff;
    border-radius: 20px;
    padding: 1.8rem;
    margin-bottom: 1.2rem;
    border: 2px solid #e5e7eb;
    transition: all .3s cubic-bezier(.4,0,.2,1);
    position: relative;
    overflow: hidden;
}

.plan-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #28a745, #20c997);
    opacity: 0;
    transition: opacity .3s;
}

.plan-card:hover {
    border-color: #28a745;
    box-shadow: 0 12px 40px rgba(40,167,69,.12);
    transform: translateY(-3px);
}

.plan-card:hover::before {
    opacity: 1;
}

.plan-card.popular {
    border-color: #28a745;
    box-shadow: 0 8px 30px rgba(40,167,69,.15);
}

.plan-card.popular::before {
    opacity: 1;
}

.popular-badge {
    position: absolute;
    top: 1.2rem;
    right: 1.2rem;
    background: linear-gradient(135deg, #f59e0b, #ef4444);
    color: #fff;
    font-size: .72rem;
    font-weight: 700;
    padding: 4px 12px;
    border-radius: 50px;
    text-transform: uppercase;
    letter-spacing: .5px;
}

.plan-num-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: linear-gradient(135deg, #28a745, #20c997);
    color: #fff;
    border-radius: 10px;
    padding: 5px 14px;
    font-size: .8rem;
    font-weight: 700;
    margin-bottom: .8rem;
}

.plan-price-tag {
    font-size: .72rem;
    font-weight: 700;
    color: #9ca3af;
    text-transform: uppercase;
    letter-spacing: .8px;
    margin-bottom: .3rem;
}

.plan-name {
    font-size: 1rem;
    font-weight: 800;
    color: #111827;
    margin-bottom: .6rem;
    line-height: 1.4;
}

.plan-date-row {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: .8rem;
    color: #9ca3af;
    margin-bottom: .8rem;
}

.plan-meta-row {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
    margin-bottom: 1.2rem;
}

.meta-chip {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 4px 10px;
    font-size: .8rem;
    font-weight: 600;
    color: #374151;
}

.meta-chip .chip-val {
    color: #f59e0b;
    font-weight: 800;
}

.meta-chip i {
    color: #f59e0b;
}

.plan-bottom {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 1rem;
    border-top: 1px solid #f3f4f6;
}

.plan-price-big {
    font-size: 1.5rem;
    font-weight: 800;
    color: #111827;
}

.plan-price-big span {
    font-size: .85rem;
    font-weight: 500;
    color: #9ca3af;
}

.btn-register {
    background: linear-gradient(135deg, #28a745, #20c997);
    color: #fff;
    border: none;
    border-radius: 12px;
    padding: .6rem 1.6rem;
    font-weight: 700;
    font-size: .9rem;
    cursor: pointer;
    transition: all .2s;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    box-shadow: 0 4px 15px rgba(40,167,69,.3);
}

.btn-register:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(40,167,69,.4);
    color: #fff;
}

/* Benefits section */
.benefits-section {
    background: #fff;
    border-radius: 20px;
    padding: 2rem;
    margin-bottom: 1.5rem;
    border: 1px solid #e5e7eb;
}

.benefit-item {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    padding: .8rem 0;
    border-bottom: 1px solid #f3f4f6;
}

.benefit-item:last-child { border-bottom: none; }

.benefit-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
}

.benefit-icon.green { background: rgba(40,167,69,.1); color: #28a745; }
.benefit-icon.blue  { background: rgba(59,130,246,.1); color: #3b82f6; }
.benefit-icon.orange{ background: rgba(245,158,11,.1); color: #f59e0b; }
.benefit-icon.purple{ background: rgba(139,92,246,.1); color: #8b5cf6; }

.benefit-text h6 { font-weight: 700; font-size: .9rem; margin-bottom: .2rem; color: #111827; }
.benefit-text p  { font-size: .8rem; color: #6b7280; margin: 0; }

/* CTA section */
.cta-section {
    background: linear-gradient(135deg, #1a1a2e, #0f3460);
    border-radius: 20px;
    padding: 2.5rem;
    text-align: center;
    color: #fff;
    margin-bottom: 1.5rem;
}

.cta-section h3 { font-weight: 800; margin-bottom: .5rem; }
.cta-section p  { color: rgba(255,255,255,.65); margin-bottom: 1.5rem; }

.btn-cta {
    background: linear-gradient(135deg, #28a745, #20c997);
    color: #fff;
    border: none;
    border-radius: 14px;
    padding: .85rem 2.5rem;
    font-weight: 700;
    font-size: 1rem;
    cursor: pointer;
    transition: all .2s;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    box-shadow: 0 8px 25px rgba(40,167,69,.4);
}

.btn-cta:hover { transform: translateY(-2px); color: #fff; }

/* Animations */
@keyframes fadeUp {
    from { opacity: 0; transform: translateY(20px); }
    to   { opacity: 1; transform: translateY(0); }
}

.plan-card { animation: fadeUp .4s ease both; }
.plan-card:nth-child(1) { animation-delay: .05s; }
.plan-card:nth-child(2) { animation-delay: .1s; }
.plan-card:nth-child(3) { animation-delay: .15s; }
.plan-card:nth-child(4) { animation-delay: .2s; }
.plan-card:nth-child(5) { animation-delay: .25s; }
.plan-card:nth-child(6) { animation-delay: .3s; }

/* ── Active Membership Card ── */
.active-member-card {
    background: linear-gradient(135deg, #1a1a2e 0%, #0f3460 100%);
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 8px 32px rgba(15,52,96,.3);
    border: 1px solid rgba(74,222,128,.2);
}
.amc-header {
    padding: 1.2rem 1.4rem 1rem;
    background: rgba(0,0,0,.2);
    border-bottom: 1px solid rgba(255,255,255,.08);
    color: #fff;
}
.amc-body { padding: .8rem 1.2rem 1.2rem; }
.amc-section {
    background: rgba(255,255,255,.06);
    border-radius: 10px;
    padding: .7rem .9rem;
    margin-bottom: .6rem;
    color: #fff;
}
.badge-active {
    background: rgba(74,222,128,.25);
    color: #4ade80;
    border: 1px solid rgba(74,222,128,.4);
    border-radius: 6px;
    padding: 2px 8px;
    font-size: .68rem;
    font-weight: 800;
    letter-spacing: .5px;
}
</style>

<div class="membership-page">

    <!-- Hero -->
    <div class="membership-hero">
        <div class="container position-relative" style="z-index:2;">
            <div class="row align-items-center">
                <div class="col-lg-7">
                    <div class="hero-badge">
                        <i class="fas fa-id-card"></i> Gói hội viên ưu đãi
                    </div>
                    <h1 class="hero-title">
                        Chơi thả ga với<br>
                        <span>gói hội viên</span>
                    </h1>
                    <p class="hero-subtitle">
                        Mua combo vé, nhận vé miễn phí — tiết kiệm đến 10% so với giá lẻ. Thời hạn linh hoạt từ 3 đến 12 tháng.
                    </p>
                    <div class="hero-stats">
                        <div class="hero-stat">
                            <span class="num">6+</span>
                            <span class="lbl">Gói hội viên</span>
                        </div>
                        <div class="hero-stat">
                            <span class="num">10%</span>
                            <span class="lbl">Tiết kiệm tối đa</span>
                        </div>
                        <div class="hero-stat">
                            <span class="num">80K</span>
                            <span class="lbl">Giá/vé ưu đãi</span>
                        </div>
                        <div class="hero-stat">
                            <span class="num">12</span>
                            <span class="lbl">Tháng tối đa</span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-5 d-none d-lg-flex justify-content-end">
                    <div style="width:280px;height:280px;background:rgba(40,167,69,.1);border-radius:50%;display:flex;align-items:center;justify-content:center;border:2px solid rgba(40,167,69,.2);">
                        <div style="width:200px;height:200px;background:rgba(40,167,69,.15);border-radius:50%;display:flex;align-items:center;justify-content:center;">
                            <i class="fas fa-id-card" style="font-size:5rem;color:rgba(74,222,128,.6);"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Wave -->
    <div class="wave-divider" style="background:#1a1a2e;">
        <svg viewBox="0 0 1440 60" xmlns="http://www.w3.org/2000/svg">
            <path d="M0,30 C360,60 1080,0 1440,30 L1440,60 L0,60 Z" fill="#f0f4f8"/>
        </svg>
    </div>

    <!-- Main content -->
    <div class="plans-section">
        <div class="container">
            <div class="row">

                <!-- Plans list -->
                <div class="col-lg-8">
                    <div class="section-header text-start mb-4">
                        <h2 style="font-size:1.5rem;">Danh sách gói hội viên</h2>
                        <p>Chọn sân trước, hệ thống hiện các gói combo phù hợp</p>
                    </div>

                    <!-- ── Bước 1: Chọn sân ── -->
                    <div style="margin-bottom:1.5rem;">
                        <div style="font-size:.82rem;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.5px;margin-bottom:.7rem;">
                            <i class="fas fa-map-marker-alt me-1 text-success"></i> Bước 1 — Chọn sân bạn muốn đăng ký gói
                        </div>
                        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:.7rem;" id="courtPickerGrid">
                            <!-- Tất cả sân -->
                            <a href="membership.php#step2" style="text-decoration:none;">
                                <?php $isAllSelected = ($selectedCourtId === 0); ?>
                                <div style="border:2px solid <?php echo $isAllSelected?'#16a34a':'#e5e7eb'; ?>;border-radius:12px;padding:.7rem .9rem;background:<?php echo $isAllSelected?'#f0fdf4':'#fff'; ?>;cursor:pointer;transition:all .2s;display:flex;align-items:center;gap:.6rem;">
                                    <div style="width:36px;height:36px;border-radius:8px;background:#d1fae5;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                        <i class="fas fa-globe" style="color:#16a34a;"></i>
                                    </div>
                                    <div>
                                        <div style="font-weight:700;font-size:.82rem;color:#111;">Tất cả sân</div>
                                        <div style="font-size:.72rem;color:#9ca3af;">Gói chung</div>
                                    </div>
                                    <?php if ($isAllSelected): ?><i class="fas fa-check-circle ms-auto" style="color:#16a34a;"></i><?php endif; ?>
                                </div>
                            </a>
                            <?php foreach ($allCourts as $c):
                                $isSelected = ((int)$c['id'] === $selectedCourtId);
                            ?>
                            <a href="membership.php?court_id=<?php echo (int)$c['id']; ?>#step2" style="text-decoration:none;">
                                <div style="border:2px solid <?php echo $isSelected?'#16a34a':'#e5e7eb'; ?>;border-radius:12px;padding:.7rem .9rem;background:<?php echo $isSelected?'#f0fdf4':'#fff'; ?>;cursor:pointer;transition:all .2s;display:flex;align-items:center;gap:.6rem;">
                                    <?php if (!empty($c['cover_image'])): ?>
                                    <img src="<?php echo escape($c['cover_image']); ?>" style="width:36px;height:36px;border-radius:8px;object-fit:cover;flex-shrink:0;" alt="">
                                    <?php else: ?>
                                    <div style="width:36px;height:36px;border-radius:8px;background:#dbeafe;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                        <i class="fas fa-table-tennis" style="color:#3b82f6;"></i>
                                    </div>
                                    <?php endif; ?>
                                    <div style="min-width:0;">
                                        <div style="font-weight:700;font-size:.82rem;color:#111;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?php echo escape($c['name']); ?></div>
                                        <div style="font-size:.72rem;color:#9ca3af;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?php echo escape($c['location']); ?></div>
                                    </div>
                                    <?php if ($isSelected): ?><i class="fas fa-check-circle ms-auto flex-shrink-0" style="color:#16a34a;"></i><?php endif; ?>
                                </div>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- ── Bước 2: Gói combo ── -->
                    <div id="step2" style="font-size:.82rem;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.5px;margin-bottom:.7rem;scroll-margin-top:80px;">
                        <i class="fas fa-tags me-1 text-success"></i> Bước 2 — Gói combo
                        <?php if ($selectedCourtId && $selectedCourtName): ?>
                        <span style="background:#d1fae5;color:#166534;border-radius:6px;padding:2px 8px;font-size:.72rem;font-weight:700;margin-left:.4rem;text-transform:none;">
                            📍 <?php echo escape($selectedCourtName); ?>
                        </span>
                        <?php endif; ?>
                    </div>

                    <?php if (empty($plans)): ?>
                    <div style="text-align:center;padding:2.5rem 1rem;background:#f9fafb;border-radius:16px;border:2px dashed #e5e7eb;">
                        <i class="fas fa-tags fa-2x" style="color:#d1d5db;margin-bottom:.8rem;display:block;"></i>
                        <div style="font-weight:700;color:#6b7280;margin-bottom:.3rem;">Chưa có gói combo cho sân này</div>
                        <div style="font-size:.82rem;color:#9ca3af;">Thử chọn sân khác hoặc xem gói chung</div>
                    </div>
                    <?php endif; ?>

                    <?php foreach ($plans as $p): ?>
                    <div class="plan-card <?php echo $p['popular'] ? 'popular' : ''; ?>">
                        <?php if ($p['popular']): ?>
                            <div class="popular-badge">⭐ Phổ biến nhất</div>
                        <?php endif; ?>

                        <div class="plan-num-badge">
                            <?php echo $p['id']; ?> Thẻ hội viên
                        </div>

                        <div class="plan-price-tag">GIÁ <?php echo number_format($p['price_per']); ?>đ/VÉ</div>
                        <div class="plan-name"><?php echo escape($p['label']); ?> : <?php echo escape($p['sub']); ?></div>

                        <?php if ($p['sale_start'] || $p['sale_end']): ?>
                        <div class="plan-date-row">
                            <i class="fas fa-calendar-alt text-success"></i>
                            Mở bán:
                            <?php echo $p['sale_start'] ? date('d/m/Y', strtotime($p['sale_start'])) : '...'; ?>
                            –
                            <?php echo $p['sale_end']   ? date('d/m/Y', strtotime($p['sale_end']))   : '...'; ?>
                        </div>
                        <?php endif; ?>

                        <div class="plan-meta-row">
                            <div class="meta-chip">
                                <i class="fas fa-clock"></i>
                                Thời hạn <span class="chip-val"><?php echo $p['months']; ?> Tháng</span>
                            </div>
                            <div class="meta-chip">
                                <i class="fas fa-gift"></i>
                                Miễn phí <span class="chip-val"><?php echo $p['free']; ?> vé</span>
                            </div>
                            <?php if (!empty($p['time'])): ?>
                            <div class="meta-chip">
                                <i class="fas fa-clock"></i>
                                <span class="chip-val"><?php echo escape($p['time']); ?></span>
                            </div>
                            <?php endif; ?>
                            <?php if ($p['court_name']): ?>
                            <div class="meta-chip" style="background:#dbeafe;color:#1d4ed8;">
                                <i class="fas fa-map-marker-alt"></i>
                                <span><?php echo escape($p['court_name']); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>

                        <div class="plan-bottom">
                            <div class="plan-price-big">
                                <?php echo number_format($p['price']); ?> đ
                                <span>/ <?php echo $p['months']; ?> tháng</span>
                            </div>
                            <?php if (isLoggedIn()): ?>
                                <a href="#" class="btn-register" onclick="registerPlan(<?php echo $p['id']; ?>); return false;">
                                    Đăng ký <i class="fas fa-chevron-right"></i>
                                </a>
                            <?php else: ?>
                                <a href="login.php?redirect=membership.php<?php echo $selectedCourtId ? '?court_id='.$selectedCourtId : ''; ?>" class="btn-register">
                                    Đăng ký <i class="fas fa-chevron-right"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Sidebar -->
                <div class="col-lg-4">

                    <!-- Active Membership Card (nếu có) -->
                    <?php if ($activeMembership): ?>
                    <div class="active-member-card mb-3">
                        <div class="amc-header">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <i class="fas fa-id-card fa-lg" style="color:#4ade80;"></i>
                                <span style="font-weight:800;font-size:1rem;">Thẻ hội viên của bạn</span>
                                <?php if($activeMembership['status']==='active'): ?>
                                    <span class="badge-active">ACTIVE</span>
                                <?php endif; ?>
                            </div>
                            <div style="font-family:monospace;font-size:1.3rem;font-weight:900;letter-spacing:3px;color:#fbbf24;">
                                <?php echo escape($activeMembership['member_code']); ?>
                            </div>
                        </div>
                        <div class="amc-body">
                            <!-- Ticket progress -->
                            <div class="amc-section">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span style="font-size:.82rem;color:rgba(255,255,255,.7);">
                                        <i class="fas fa-ticket-alt me-1"></i>Vé còn lại
                                    </span>
                                    <span style="font-weight:800;font-size:1.1rem;color:#4ade80;">
                                        <?php echo $ticketsRemaining; ?> / <?php echo $activeMembership['free_tickets']; ?>
                                    </span>
                                </div>
                                <?php
                                $pct = $activeMembership['free_tickets'] > 0
                                    ? round($ticketsRemaining / $activeMembership['free_tickets'] * 100)
                                    : 0;
                                $barColor = $pct > 50 ? '#4ade80' : ($pct > 20 ? '#fbbf24' : '#ef4444');
                                ?>
                                <div style="height:8px;background:rgba(255,255,255,.15);border-radius:4px;overflow:hidden;">
                                    <div style="height:100%;width:<?php echo $pct; ?>%;background:<?php echo $barColor; ?>;border-radius:4px;transition:width .5s;"></div>
                                </div>
                                <div style="font-size:.72rem;color:rgba(255,255,255,.5);margin-top:.3rem;">
                                    Đã dùng <?php echo $activeMembership['tickets_used'] ?? 0; ?> vé
                                </div>
                            </div>

                            <!-- Plan info -->
                            <div class="amc-section">
                                <div style="font-size:.78rem;color:rgba(255,255,255,.5);">Gói đang dùng</div>
                                <div style="font-weight:700;font-size:.88rem;">
                                    <?php echo escape($activeMembership['plan_name']); ?>
                                </div>
                                <div style="font-size:.78rem;color:#a5b4fc;">
                                    <?php echo escape($activeMembership['plan_detail']); ?>
                                </div>
                            </div>

                            <!-- Expiry -->
                            <?php
                            $daysLeft = (int)ceil((strtotime($activeMembership['end_date']) - time()) / 86400);
                            $expiryColor = $daysLeft <= 7 ? '#ef4444' : ($daysLeft <= 30 ? '#fbbf24' : '#4ade80');
                            ?>
                            <div class="amc-section">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div style="font-size:.72rem;color:rgba(255,255,255,.5);">Hiệu lực</div>
                                        <div style="font-size:.82rem;font-weight:700;">
                                            <?php echo date('d/m/Y', strtotime($activeMembership['start_date'])); ?>
                                            →
                                            <?php echo date('d/m/Y', strtotime($activeMembership['end_date'])); ?>
                                        </div>
                                    </div>
                                    <div style="text-align:right;">
                                        <div style="font-size:.72rem;color:rgba(255,255,255,.5);">Còn lại</div>
                                        <div style="font-weight:800;color:<?php echo $expiryColor; ?>;">
                                            <?php echo $daysLeft; ?> ngày
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Giá ưu đãi -->
                            <div class="amc-section" style="background:rgba(74,222,128,.08);border:1px solid rgba(74,222,128,.2);">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="fas fa-tag" style="color:#4ade80;"></i>
                                    <div>
                                        <div style="font-size:.78rem;color:rgba(255,255,255,.7);">Giá ưu đãi hội viên</div>
                                        <div style="font-weight:800;color:#4ade80;font-size:1rem;">80,000đ / giờ</div>
                                        <div style="font-size:.68rem;color:rgba(255,255,255,.4);">Giá cố định, không tăng giờ cao điểm</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Ticket log button -->
                            <button onclick="showTicketLog(<?php echo $activeMembership['id']; ?>)"
                                    class="btn w-100 mt-2 py-2"
                                    style="background:rgba(255,255,255,.1);color:#fff;border:1px solid rgba(255,255,255,.2);border-radius:10px;font-size:.82rem;font-weight:600;">
                                <i class="fas fa-history me-2"></i>Lịch sử dùng vé
                            </button>

                            <!-- Nút tạo lịch tự động -->
                            <button onclick="createMemberBookings(<?php echo $activeMembership['id']; ?>)"
                                    id="btnCreateBookings"
                                    class="btn w-100 mt-2 py-2"
                                    style="background:rgba(74,222,128,.15);color:#4ade80;border:1px solid rgba(74,222,128,.3);border-radius:10px;font-size:.82rem;font-weight:600;">
                                <i class="fas fa-calendar-plus me-2"></i>Tạo lịch đặt sân tự động
                            </button>

                            <?php if($ticketsRemaining <= 0 || $daysLeft <= 0): ?>
                            <div style="margin-top:.8rem;background:rgba(251,191,36,.1);border:1px solid rgba(251,191,36,.3);border-radius:8px;padding:.6rem;font-size:.78rem;color:#fbbf24;text-align:center;">
                                <i class="fas fa-exclamation-triangle me-1"></i>
                                <?php echo $ticketsRemaining <= 0 ? 'Đã hết vé!' : 'Gói sắp hết hạn!'; ?>
                                Gia hạn hoặc mua gói mới bên dưới.
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php else: ?>
                    <!-- Benefits (chưa có gói) -->
                    <div class="benefits-section">
                        <h6 class="fw-bold mb-3" style="color:#111827;">
                            <i class="fas fa-star text-warning me-2"></i>Quyền lợi hội viên
                        </h6>
                        <div class="benefit-item">
                            <div class="benefit-icon green"><i class="fas fa-ticket-alt"></i></div>
                            <div class="benefit-text">
                                <h6>Vé ưu đãi 80K cố định</h6>
                                <p>Đặt sân với giá 80,000đ/giờ — không tăng giờ cao điểm</p>
                            </div>
                        </div>
                        <div class="benefit-item">
                            <div class="benefit-icon blue"><i class="fas fa-gift"></i></div>
                            <div class="benefit-text">
                                <h6>Tặng vé miễn phí</h6>
                                <p>Mua 10 tặng 1 vé · Mua 20 tặng 2 vé · Mua 30 tặng 3 vé</p>
                            </div>
                        </div>
                        <div class="benefit-item">
                            <div class="benefit-icon orange"><i class="fas fa-clock"></i></div>
                            <div class="benefit-text">
                                <h6>Thời hạn linh hoạt</h6>
                                <p>Sử dụng vé trong 3, 6, 9 hoặc 12 tháng kể từ ngày kích hoạt</p>
                            </div>
                        </div>
                        <div class="benefit-item">
                            <div class="benefit-icon purple"><i class="fas fa-headset"></i></div>
                            <div class="benefit-text">
                                <h6>Hỗ trợ ưu tiên</h6>
                                <p>Đặt sân nhanh — hội viên được ưu tiên giữ slot</p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Lịch sử gói (nếu có từ 2+ gói) -->
                    <?php if (count($allMemberships) > 1): ?>
                    <div class="benefits-section mb-3">
                        <h6 class="fw-bold mb-3" style="color:#111827;font-size:.88rem;">
                            <i class="fas fa-history text-primary me-2"></i>Lịch sử gói hội viên
                        </h6>
                        <?php foreach (array_slice($allMemberships, 0, 3) as $m): ?>
                        <div style="display:flex;justify-content:space-between;align-items:center;padding:.5rem 0;border-bottom:1px solid #f3f4f6;font-size:.8rem;">
                            <div>
                                <div style="font-weight:700;font-family:monospace;color:#374151;"><?php echo escape($m['member_code']); ?></div>
                                <div style="color:#9ca3af;"><?php echo escape($m['plan_name']); ?></div>
                            </div>
                            <div style="text-align:right;">
                                <?php
                                $sColor = ['active'=>'#16a34a','expired'=>'#9ca3af','cancelled'=>'#ef4444'][$m['status']] ?? '#9ca3af';
                                $sLabel = ['active'=>'Active','expired'=>'Hết hạn','cancelled'=>'Đã huỷ'][$m['status']] ?? $m['status'];
                                ?>
                                <div style="font-weight:700;color:<?php echo $sColor; ?>;"><?php echo $sLabel; ?></div>
                                <div style="color:#9ca3af;"><?php echo date('d/m/Y', strtotime($m['end_date'])); ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <!-- CTA -->
                    <div class="cta-section">
                        <h3>Cần tư vấn?</h3>
                        <p>Liên hệ ngay để được hỗ trợ chọn gói phù hợp nhất</p>
                        <a href="tel:0968073500" class="btn-cta">
                            <i class="fas fa-phone"></i> 0968.073.500
                        </a>
                    </div>

                    <!-- FAQ -->
                    <div class="benefits-section">
                        <h6 class="fw-bold mb-3" style="color:#111827;">
                            <i class="fas fa-question-circle text-primary me-2"></i>Câu hỏi thường gặp
                        </h6>
                        <div class="accordion accordion-flush" id="faqAccordion">
                            <div class="accordion-item border-0 border-bottom">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed px-0 py-3" style="font-size:.85rem;font-weight:600;" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                        Vé có hết hạn không?
                                    </button>
                                </h2>
                                <div id="faq1" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body px-0 pt-0 pb-3" style="font-size:.82rem;color:#6b7280;">
                                        Vé có thời hạn theo gói bạn chọn (3, 6, 9 hoặc 12 tháng kể từ ngày kích hoạt).
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item border-0 border-bottom">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed px-0 py-3" style="font-size:.85rem;font-weight:600;" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                        Có thể chuyển nhượng vé không?
                                    </button>
                                </h2>
                                <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body px-0 pt-0 pb-3" style="font-size:.82rem;color:#6b7280;">
                                        Vé hội viên được sử dụng cho tài khoản đăng ký, không chuyển nhượng.
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item border-0">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed px-0 py-3" style="font-size:.85rem;font-weight:600;" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                        Thanh toán bằng gì?
                                    </button>
                                </h2>
                                <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body px-0 pt-0 pb-3" style="font-size:.82rem;color:#6b7280;">
                                        Hỗ trợ MoMo, MB Bank, chuyển khoản ngân hàng và tiền mặt tại sân.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<!-- ===== MEMBERSHIP WAITING CONTAINER (hiện khi chờ chuyển khoản) ===== -->
<div id="membershipWaitingContainer" style="display:none;position:fixed;bottom:0;left:0;right:0;z-index:1050;padding:1rem;background:rgba(0,0,0,.6);backdrop-filter:blur(4px);"></div>

<!-- ===== MODAL CHỌN SÂN TẠO LỊCH ===== -->
<div class="modal fade" id="selectCourtModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow-lg" style="border-radius:16px;overflow:hidden;">
            <div style="background:linear-gradient(135deg,#16a34a,#15803d);padding:1.2rem 1.5rem;color:#fff;">
                <h6 class="fw-bold mb-0"><i class="fas fa-map-marker-alt me-2"></i>Chọn sân tạo lịch tự động</h6>
                <small style="opacity:.7;">Hệ thống sẽ đặt slot 14H-17H mỗi ngày</small>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" id="selectCourtMembershipId">
                <label class="form-label fw-bold">Chọn sân</label>
                <select id="selectCourtDropdown" class="form-select">
                    <option value="">-- Chọn sân --</option>
                    <?php foreach ($allCourts as $c): ?>
                    <option value="<?php echo (int)$c['id']; ?>"><?php echo escape($c['name']); ?> — <?php echo escape($c['location']); ?></option>
                    <?php endforeach; ?>
                </select>
                <small class="text-muted mt-1 d-block">Sân này sẽ được giữ slot theo gói combo của bạn</small>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-success btn-sm fw-bold" onclick="confirmSelectCourt()">
                    <i class="fas fa-calendar-plus me-1"></i>Tạo lịch
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ===== PAYMENT MODAL ===== -->
<div class="modal fade" id="paymentModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius:20px;overflow:hidden;">

            <!-- Header -->
            <div style="background:linear-gradient(135deg,#1a1a2e,#0f3460);padding:1.5rem 2rem;color:#fff;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="fw-bold mb-1"><i class="fas fa-credit-card me-2"></i>Thanh toán gói hội viên</h5>
                        <small style="color:rgba(255,255,255,.6);">Hoàn tất đăng ký để nhận thẻ hội viên</small>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
            </div>

            <div class="modal-body p-0">
                <div class="row g-0">

                    <!-- Left: Order summary -->
                    <div class="col-md-5" style="background:#f8fafc;padding:1.8rem;border-right:1px solid #e5e7eb;">
                        <h6 class="fw-bold mb-3" style="color:#374151;">Thông tin gói</h6>

                        <div id="pm-plan-info" style="background:#fff;border-radius:14px;padding:1.2rem;border:1px solid #e5e7eb;margin-bottom:1rem;">
                            <div id="pm-plan-badge" class="mb-2" style="display:inline-flex;align-items:center;gap:6px;background:linear-gradient(135deg,#28a745,#20c997);color:#fff;border-radius:8px;padding:4px 12px;font-size:.8rem;font-weight:700;"></div>
                            <div id="pm-plan-label" style="font-size:.72rem;font-weight:700;color:#9ca3af;text-transform:uppercase;margin-bottom:.2rem;"></div>
                            <div id="pm-plan-name" style="font-weight:800;font-size:.95rem;color:#111;margin-bottom:.8rem;"></div>
                            <div style="display:flex;gap:.8rem;flex-wrap:wrap;font-size:.8rem;">
                                <span id="pm-months" style="background:#fef3c7;color:#d97706;border-radius:6px;padding:3px 8px;font-weight:700;"></span>
                                <span id="pm-free" style="background:#d1fae5;color:#059669;border-radius:6px;padding:3px 8px;font-weight:700;"></span>
                            </div>
                        </div>

                        <div style="background:#fff;border-radius:14px;padding:1.2rem;border:1px solid #e5e7eb;">
                            <div class="d-flex justify-content-between mb-2" style="font-size:.85rem;">
                                <span style="color:#6b7280;">Giá gói</span>
                                <span id="pm-price-sub" class="fw-bold"></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2" style="font-size:.85rem;">
                                <span style="color:#6b7280;">Vé tặng thêm</span>
                                <span id="pm-free-val" style="color:#28a745;font-weight:700;"></span>
                            </div>
                            <hr style="margin:.8rem 0;">
                            <div class="d-flex justify-content-between">
                                <span class="fw-bold">Tổng thanh toán</span>
                                <span id="pm-total" style="font-size:1.2rem;font-weight:800;color:#111;"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Payment methods -->
                    <div class="col-md-7" style="padding:1.8rem;">
                        <h6 class="fw-bold mb-3" style="color:#374151;">Phương thức thanh toán</h6>

                        <div class="pm-methods">
                            <!-- Cash -->
                            <label class="pm-method-card selected" data-method="cash">
                                <input type="radio" name="pm_method" value="cash" checked style="display:none;">
                                <div class="d-flex align-items-center gap-3">
                                    <div style="width:44px;height:44px;background:#d1fae5;border-radius:12px;display:flex;align-items:center;justify-content:center;">
                                        <i class="fas fa-money-bill-wave" style="color:#28a745;font-size:1.1rem;"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="fw-bold" style="font-size:.9rem;">Tiền mặt tại sân</div>
                                        <div style="font-size:.78rem;color:#9ca3af;">Thanh toán khi đến sân lần đầu</div>
                                    </div>
                                    <div class="pm-check"><i class="fas fa-check-circle text-success"></i></div>
                                </div>
                            </label>

                            <!-- MoMo -->
                            <label class="pm-method-card" data-method="momo">
                                <input type="radio" name="pm_method" value="momo" style="display:none;">
                                <div class="d-flex align-items-center gap-3">
                                    <div style="width:44px;height:44px;background:#fce7f3;border-radius:12px;display:flex;align-items:center;justify-content:center;">
                                        <i class="fas fa-wallet" style="color:#ec4899;font-size:1.1rem;"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="fw-bold" style="font-size:.9rem;">Ví MoMo</div>
                                        <div style="font-size:.78rem;color:#9ca3af;">Thanh toán qua ví điện tử MoMo</div>
                                    </div>
                                    <div class="pm-check" style="opacity:0;"><i class="fas fa-check-circle text-success"></i></div>
                                </div>
                            </label>

                            <!-- MB Bank -->
                            <label class="pm-method-card" data-method="vnpay">
                                <input type="radio" name="pm_method" value="vnpay" style="display:none;">
                                <div class="d-flex align-items-center gap-3">
                                    <div style="width:44px;height:44px;background:#dbeafe;border-radius:12px;display:flex;align-items:center;justify-content:center;">
                                        <i class="fas fa-university" style="color:#3b82f6;font-size:1.1rem;"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                    <div class="fw-bold" style="font-size:.9rem;">MB Bank</div>
                                        <div style="font-size:.78rem;color:#9ca3af;">Thanh toán qua ngân hàng</div>
                                    </div>
                                    <div class="pm-check" style="opacity:0;"><i class="fas fa-check-circle text-success"></i></div>
                                </div>
                            </label>
                        </div>

                        <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px;padding:.8rem 1rem;margin-top:1rem;font-size:.8rem;color:#166534;">
                            <i class="fas fa-shield-alt me-2"></i>
                            Giao dịch được bảo mật SSL 256-bit
                        </div>

                        <button id="btn-confirm-payment" class="btn w-100 mt-3 py-3 fw-bold" style="background:linear-gradient(135deg,#28a745,#20c997);color:#fff;border:none;border-radius:14px;font-size:1rem;box-shadow:0 8px 25px rgba(40,167,69,.3);transition:all .2s;">
                            <i class="fas fa-lock me-2"></i>Xác nhận đăng ký
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ===== MEMBER CARD MODAL ===== -->
<div class="modal fade" id="memberCardModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius:20px;overflow:hidden;">

            <div style="background:linear-gradient(135deg,#28a745,#20c997);padding:1.5rem 2rem;color:#fff;text-align:center;">
                <i class="fas fa-check-circle fa-2x mb-2 d-block"></i>
                <h5 class="fw-bold mb-1">Đăng ký thành công!</h5>
                <small style="opacity:.8;">Thẻ hội viên của bạn đã được kích hoạt</small>
            </div>

            <div class="modal-body p-0">
                <!-- Printable member card -->
                <div id="member-card-print" style="padding:1.5rem;">

                    <!-- Card design -->
                    <div id="member-card" style="background:linear-gradient(135deg,#1a1a2e 0%,#0f3460 100%);border-radius:18px;padding:1.8rem;color:#fff;position:relative;overflow:hidden;margin-bottom:1rem;">
                        <!-- Background decoration -->
                        <div style="position:absolute;top:-30px;right:-30px;width:150px;height:150px;background:rgba(40,167,69,.15);border-radius:50%;"></div>
                        <div style="position:absolute;bottom:-40px;left:-20px;width:120px;height:120px;background:rgba(102,126,234,.1);border-radius:50%;"></div>

                        <!-- Header -->
                        <div class="d-flex justify-content-between align-items-start mb-3" style="position:relative;z-index:1;">
                            <div>
                                <div style="font-size:.7rem;color:rgba(255,255,255,.5);text-transform:uppercase;letter-spacing:1px;">BadmintonPro</div>
                                <div style="font-size:1.1rem;font-weight:800;color:#4ade80;">THẺ HỘI VIÊN</div>
                            </div>
                            <div style="background:rgba(74,222,128,.2);border:1px solid rgba(74,222,128,.4);border-radius:8px;padding:4px 10px;font-size:.72rem;font-weight:700;color:#4ade80;" id="mc-status">ACTIVE</div>
                        </div>

                        <!-- Member code big -->
                        <div style="text-align:center;margin:1rem 0;position:relative;z-index:1;">
                            <div style="font-size:.7rem;color:rgba(255,255,255,.5);margin-bottom:.3rem;">MÃ THẺ HỘI VIÊN</div>
                            <div id="mc-code" style="font-size:1.8rem;font-weight:900;letter-spacing:4px;color:#fff;font-family:monospace;"></div>
                        </div>

                        <!-- QR Code -->
                        <div style="text-align:center;margin:1rem 0;position:relative;z-index:1;">
                            <div id="mc-qr" style="display:inline-block;background:#fff;padding:10px;border-radius:12px;"></div>
                            <div style="font-size:.7rem;color:rgba(255,255,255,.5);margin-top:.5rem;">Quét mã để xác nhận hội viên</div>
                        </div>

                        <!-- Info grid -->
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:.8rem;position:relative;z-index:1;">
                            <div style="background:rgba(255,255,255,.08);border-radius:10px;padding:.7rem;">
                                <div style="font-size:.65rem;color:rgba(255,255,255,.5);text-transform:uppercase;">Họ tên</div>
                                <div id="mc-name" style="font-weight:700;font-size:.85rem;"></div>
                            </div>
                            <div style="background:rgba(255,255,255,.08);border-radius:10px;padding:.7rem;">
                                <div style="font-size:.65rem;color:rgba(255,255,255,.5);text-transform:uppercase;">Gói</div>
                                <div id="mc-plan" style="font-weight:700;font-size:.85rem;color:#4ade80;"></div>
                            </div>
                            <div style="background:rgba(255,255,255,.08);border-radius:10px;padding:.7rem;">
                                <div style="font-size:.65rem;color:rgba(255,255,255,.5);text-transform:uppercase;">Hiệu lực từ</div>
                                <div id="mc-start" style="font-weight:700;font-size:.85rem;"></div>
                            </div>
                            <div style="background:rgba(255,255,255,.08);border-radius:10px;padding:.7rem;">
                                <div style="font-size:.65rem;color:rgba(255,255,255,.5);text-transform:uppercase;">Hết hạn</div>
                                <div id="mc-end" style="font-weight:700;font-size:.85rem;color:#fbbf24;"></div>
                            </div>
                        </div>

                        <!-- Tickets info -->
                        <div style="margin-top:.8rem;background:rgba(74,222,128,.1);border:1px solid rgba(74,222,128,.2);border-radius:10px;padding:.7rem;text-align:center;position:relative;z-index:1;">
                            <span id="mc-tickets" style="font-weight:700;color:#4ade80;font-size:.9rem;"></span>
                        </div>
                    </div>

                    <!-- Print note -->
                    <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px;padding:1rem;font-size:.82rem;color:#166534;text-align:center;">
                        <i class="fas fa-info-circle me-2"></i>
                        Xuất trình mã thẻ hoặc QR code cho nhân viên khi đến sân
                    </div>

                    <!-- Cash payment note -->
                    <div id="mc-cash-note" style="display:none;margin-top:.8rem;background:#fffbeb;border:1px solid #fde68a;border-radius:12px;padding:1rem;font-size:.82rem;color:#92400e;">
                        <div class="fw-bold mb-1"><i class="fas fa-info-circle me-2"></i>Thanh toán tiền mặt</div>
                        Vui lòng mang thẻ này đến sân và thanh toán tại quầy khi đến sân lần đầu tiên.
                        Thẻ sẽ được kích hoạt ngay sau khi nhận tiền.
                    </div>
                </div>
            </div>

            <div style="padding:1rem 1.5rem;border-top:1px solid #f3f4f6;display:flex;gap:.8rem;">
                <button onclick="printMemberCard()" class="btn flex-grow-1 py-2 fw-bold" style="background:linear-gradient(135deg,#1a1a2e,#0f3460);color:#fff;border:none;border-radius:12px;">
                    <i class="fas fa-print me-2"></i>In thẻ hội viên
                </button>
                <button class="btn py-2 fw-bold" style="background:#f3f4f6;color:#374151;border:none;border-radius:12px;padding:0 1.5rem;" data-bs-dismiss="modal">
                    Đóng
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.pm-method-card {
    display: block;
    background: #fff;
    border: 2px solid #e5e7eb;
    border-radius: 14px;
    padding: 1rem 1.2rem;
    margin-bottom: .8rem;
    cursor: pointer;
    transition: all .2s;
}
.pm-method-card:hover { border-color: #28a745; }
.pm-method-card.selected {
    border-color: #28a745;
    background: #f0fdf4;
}
.pm-method-card.selected .pm-check { opacity: 1 !important; }

@media print {
    body * { visibility: hidden; }
    #member-card-print, #member-card-print * { visibility: visible; }
    #member-card-print { position: fixed; top: 0; left: 0; width: 100%; }
    .modal-footer, button { display: none !important; }
}
</style>

<!-- ===== TICKET LOG MODAL ===== -->
<div class="modal fade" id="ticketLogModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content border-0 shadow-lg" style="border-radius:20px;overflow:hidden;">
            <div style="background:linear-gradient(135deg,#1a1a2e,#0f3460);padding:1.2rem 1.5rem;color:#fff;display:flex;align-items:center;justify-content:space-between;">
                <div>
                    <h6 class="fw-bold mb-0"><i class="fas fa-history me-2"></i>Lịch sử dùng vé</h6>
                    <small style="color:rgba(255,255,255,.5);">Các lần sử dụng vé hội viên</small>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0" style="max-height:420px;overflow-y:auto;">
                <div id="ticketLogBody" style="padding:1rem 1.4rem;"></div>
            </div>
            <div style="padding:.8rem 1.4rem;border-top:1px solid #f3f4f6;text-align:center;">
                <button class="btn btn-sm py-1 px-3 fw-bold" style="background:#f3f4f6;color:#374151;border:none;border-radius:8px;" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
// plansData được generate từ PHP — có đầy đủ court_id, time_range
const plansData = <?php
$jsPlans = [];
foreach ($plans as $p) {
    $jsPlans[$p['id']] = [
        'badge'      => $p['id'] . ' Thẻ hội viên',
        'label'      => 'GIÁ ' . number_format($p['price_per']) . 'đ/VÉ',
        'name'       => $p['label'] . ' : ' . $p['sub'],
        'price'      => (int)$p['price'],
        'months'     => (int)$p['months'],
        'free'       => (int)$p['free'],
        'court_id'   => $p['court_id'] ? (int)$p['court_id'] : null,
        'court_name' => $p['court_name'] ?? null,
        'time_range' => $p['time'] ?? '',
    ];
}
echo json_encode($jsPlans, JSON_UNESCAPED_UNICODE);
?>;

let currentPlanId = null;

function registerPlan(planId) {
    currentPlanId = planId;
    const p = plansData[planId];

    // Nếu gói global (không có court_id) và URL không có court_id → hỏi chọn sân
    const urlCourtId = new URLSearchParams(window.location.search).get('court_id');
    if (!p.court_id && !urlCourtId) {
        // Redirect về trang chọn sân
        if (confirm('Gói này có thể áp dụng cho tất cả sân.\nBạn muốn chọn sân cụ thể để đặt lịch tự động?\n\nBấm OK → chọn sân\nBấm Hủy → mua gói không gắn sân')) {
            window.location.href = 'membership.php#courtPickerGrid';
            return;
        }
    }

    document.getElementById('pm-plan-badge').textContent = p.badge;
    document.getElementById('pm-plan-label').textContent = p.label;
    document.getElementById('pm-plan-name').textContent  = p.name;
    document.getElementById('pm-months').textContent     = `⏱ ${p.months} Tháng`;
    document.getElementById('pm-free').textContent       = `🎁 Miễn phí ${p.free} vé`;
    document.getElementById('pm-price-sub').textContent  = p.price.toLocaleString('vi-VN') + 'đ';
    document.getElementById('pm-free-val').textContent   = `+${p.free} vé miễn phí`;
    document.getElementById('pm-total').textContent      = p.price.toLocaleString('vi-VN') + 'đ';

    new bootstrap.Modal(document.getElementById('paymentModal')).show();
}

// Payment method selection
document.querySelectorAll('.pm-method-card').forEach(card => {
    card.addEventListener('click', function() {
        document.querySelectorAll('.pm-method-card').forEach(c => {
            c.classList.remove('selected');
            c.querySelector('.pm-check').style.opacity = '0';
        });
        this.classList.add('selected');
        this.querySelector('.pm-check').style.opacity = '1';
        this.querySelector('input[type=radio]').checked = true;
    });
});

// Confirm payment
document.getElementById('btn-confirm-payment').addEventListener('click', function() {
    const method = document.querySelector('input[name=pm_method]:checked').value;
    const btn    = this;

    btn.innerHTML = '<div class="spinner-border spinner-border-sm me-2"></div>Đang xử lý...';
    btn.disabled  = true;

    const formData = new FormData();
    formData.append('plan_id', currentPlanId);
    formData.append('payment_method', method);
    formData.append('action', 'register');

    // Truyền court_id: ưu tiên court_id của gói, fallback sang court_id từ URL (trang đang xem)
    const planCourtId = plansData[currentPlanId]?.court_id
        || (new URLSearchParams(window.location.search).get('court_id') ? parseInt(new URLSearchParams(window.location.search).get('court_id')) : null);
    if (planCourtId) formData.append('court_id', planCourtId);

    fetch('api/membership.php', { method: 'POST', body: formData })
        .then(r => {
            if (!r.ok) throw new Error('HTTP ' + r.status + ': ' + r.statusText);
            return r.json();
        })
        .then(data => {
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('paymentModal')).hide();
                if (method === 'cash') {
                    // Hiện thông báo nếu đã tạo bookings định kỳ
                    if (data.booking_created > 0) {
                        const p = plansData[currentPlanId];
                        const courtName = p?.court_name || '';
                        const note = document.createElement('div');
                        note.style.cssText = 'position:fixed;top:20px;left:50%;transform:translateX(-50%);z-index:9999;background:linear-gradient(135deg,#1d4ed8,#3b82f6);color:#fff;padding:.9rem 1.5rem;border-radius:14px;box-shadow:0 8px 30px rgba(29,78,216,.3);font-weight:600;font-size:.88rem;text-align:center;min-width:320px;';
                        note.innerHTML = `<i class="fas fa-calendar-check me-2"></i>Đã tự động đặt <strong>${data.booking_created} buổi</strong> khung giờ ${p?.time_range || ''} cho <strong>${courtName}</strong>`;
                        document.body.appendChild(note);
                        setTimeout(() => { note.style.opacity='0'; note.style.transition='opacity .5s'; setTimeout(()=>note.remove(),500); }, 4000);
                    }
                    showMemberCard(data);
                } else {
                    showTransferWaiting(data);
                }
            } else if (data.existing) {
                alert('⚠️ ' + data.error);
            } else {
                alert('Lỗi: ' + (data.error || 'Không rõ lỗi. Vui lòng thử lại.'));
            }
        })
        .catch(err => {
            console.error('Membership register error:', err);
            alert('Lỗi kết nối: ' + err.message + '\nVui lòng kiểm tra lại kết nối mạng và thử lại.');
        })
        .finally(() => {
            btn.innerHTML = '<i class="fas fa-lock me-2"></i>Xác nhận đăng ký';
            btn.disabled  = false;
        });
});

// ── Biến polling toàn cục ──────────────────────────────────────────────────
let _memberPollingTimer = null;
let _memberPollingDone  = false;

// ── Hiển thị màn hình chờ chuyển khoản + tự động polling ─────────────────
function showTransferWaiting(data) {
    const mid    = data.membership_id;
    const code   = data.member_code;
    const amount = data.price;
    const method = data.payment_method;
    const enc    = encodeURIComponent(code);

    const isMomo = method === 'momo';
    const qrUrl  = isMomo
        ? `https://img.vietqr.io/image/MOMO-0968073500-qr_only.png?amount=${amount}&addInfo=${enc}&accountName=LU+DANG+HUNG`
        : `https://img.vietqr.io/image/MB-7369786789-qr_only.png?amount=${amount}&addInfo=${enc}&accountName=LU+DANG+HUNG`;

    const color  = isMomo ? '#db2777' : '#4f46e5';
    const bg     = isMomo ? '#fdf2f8' : '#f0f9ff';
    const border = isMomo ? '#f9a8d4' : '#bae6fd';

    const container = document.getElementById('membershipWaitingContainer');
    if (!container) return;

    _memberPollingDone = false;
    container.style.display = 'block';
    container.innerHTML = `
        <div style="background:#fff;border-radius:20px;padding:1.5rem;max-width:520px;margin:0 auto;box-shadow:0 20px 60px rgba(0,0,0,.3);">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;">
                <div style="font-weight:800;color:${color};font-size:1rem;">
                    <i class="fas fa-${isMomo ? 'wallet' : 'university'} me-2"></i>
                    ${isMomo ? 'Thanh toán MoMo' : 'Chuyển khoản MB Bank'}
                </div>
                <button onclick="cancelMembershipWaiting()" style="background:none;border:none;cursor:pointer;color:#9ca3af;font-size:1.2rem;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div style="display:flex;gap:1.2rem;align-items:flex-start;flex-wrap:wrap;">
                <div style="flex-shrink:0;text-align:center;">
                    <img src="${qrUrl}" alt="QR" style="width:140px;height:140px;border-radius:12px;border:2px solid ${border};padding:4px;background:#fff;">
                    <div style="font-size:.72rem;color:#9ca3af;margin-top:.4rem;">Quét QR để thanh toán</div>
                </div>
                <div style="flex:1;min-width:180px;">
                    <div style="background:${bg};border:1px solid ${border};border-radius:12px;padding:1rem;font-size:.85rem;display:grid;gap:.5rem;">
                        ${isMomo
                            ? `<div style="display:flex;justify-content:space-between;"><span style="color:#78716c;">Số MoMo</span><strong style="color:${color};font-family:monospace;">0968073500</strong></div>`
                            : `<div style="display:flex;justify-content:space-between;"><span style="color:#78716c;">Ngân hàng</span><strong>MB Bank</strong></div>
                               <div style="display:flex;justify-content:space-between;"><span style="color:#78716c;">Số TK</span><strong style="font-family:monospace;color:${color};">7369786789</strong></div>`
                        }
                        <div style="display:flex;justify-content:space-between;"><span style="color:#78716c;">Tên TK</span><strong>LU DANG HUNG</strong></div>
                        <div style="display:flex;justify-content:space-between;"><span style="color:#78716c;">Số tiền</span><strong style="color:${color};">${parseInt(amount).toLocaleString('vi-VN')}đ</strong></div>
                        <hr style="margin:.3rem 0;border-color:${border};">
                        <div>
                            <span style="color:#78716c;display:block;font-size:.78rem;margin-bottom:.2rem;">📌 Nội dung CK (bắt buộc):</span>
                            <div style="display:flex;align-items:center;gap:.4rem;">
                                <code style="background:#fff;border:1.5px solid ${color};border-radius:8px;padding:4px 10px;font-size:.9rem;font-weight:700;color:${color};letter-spacing:1px;">${code}</code>
                                <button onclick="copyMemberCode('${code}')" id="btnCopyCode" style="background:${color};color:#fff;border:none;border-radius:6px;padding:4px 8px;font-size:.72rem;cursor:pointer;">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div id="memberPaymentStatus" style="margin-top:1rem;">
                <div style="background:#f0fdf4;border:1.5px solid #86efac;border-radius:12px;padding:.8rem 1rem;">
                    <div style="display:flex;align-items:center;gap:.6rem;color:#166534;font-weight:700;font-size:.88rem;margin-bottom:.4rem;">
                        <i class="fas fa-circle-notch fa-spin"></i>
                        <span id="memberStatusText">Đang chờ xác nhận thanh toán tự động...</span>
                    </div>
                    <div style="height:4px;background:#dcfce7;border-radius:4px;overflow:hidden;">
                        <div id="memberProgressBar" style="height:100%;background:#16a34a;width:0%;transition:width 5s linear;"></div>
                    </div>
                    <div style="display:flex;justify-content:space-between;margin-top:.4rem;font-size:.75rem;color:#15803d;">
                        <span id="memberAttemptText">Bắt đầu kiểm tra...</span>
                        <span id="memberCountdown">Còn 6:00</span>
                    </div>
                </div>
            </div>
            <div style="margin-top:.8rem;display:flex;gap:.6rem;">
                <button id="btnManualCheck" onclick="manualCheckMembership(${mid}, data)"
                        style="flex:1;background:#f8fafc;border:1.5px solid #e2e8f0;border-radius:10px;padding:.6rem;font-size:.83rem;font-weight:600;color:#374151;cursor:pointer;">
                    <i class="fas fa-sync-alt me-1"></i> Kiểm tra ngay
                </button>
                <button onclick="cancelMembershipWaiting()"
                        style="background:#fef2f2;border:1.5px solid #fecaca;border-radius:10px;padding:.6rem 1rem;font-size:.83rem;font-weight:600;color:#dc2626;cursor:pointer;">
                    Huỷ
                </button>
            </div>
            <div style="font-size:.73rem;color:#9ca3af;margin-top:.5rem;text-align:center;">
                Hệ thống tự động xác nhận khi nhận tiền · Không cần làm gì thêm
            </div>
        </div>`;

    // Lưu data vào closure để dùng sau
    window._memberWaitingData = data;

    startMemberPolling(mid, data);
    setTimeout(() => {
        const b = document.getElementById('memberProgressBar');
        if (b) b.style.width = '100%';
    }, 100);
}

// ── Polling tự động ────────────────────────────────────────────────────────
function startMemberPolling(mid, data) {
    if (_memberPollingTimer) clearInterval(_memberPollingTimer);
    _memberPollingDone = false;

    let attempts  = 0;
    const MAX     = 72; // 6 phút (72 × 5s)
    let remaining = 360;

    const countdown = setInterval(() => {
        remaining--;
        const el = document.getElementById('memberCountdown');
        if (el) {
            const m = Math.floor(remaining / 60);
            const s = remaining % 60;
            el.textContent = `Còn ${m}:${String(s).padStart(2,'0')}`;
        }
        if (remaining <= 0) clearInterval(countdown);
    }, 1000);

    _memberPollingTimer = setInterval(() => {
        if (_memberPollingDone) { clearInterval(_memberPollingTimer); clearInterval(countdown); return; }
        attempts++;

        const bar = document.getElementById('memberProgressBar');
        if (bar) {
            bar.style.transition = 'none'; bar.style.width = '0%';
            setTimeout(() => { bar.style.transition = 'width 5s linear'; bar.style.width = '100%'; }, 50);
        }
        const at = document.getElementById('memberAttemptText');
        if (at) at.textContent = `Lần kiểm tra ${attempts}/${MAX}`;

        fetch(`api/check-payment-status.php?membership_id=${mid}`)
            .then(r => r.json())
            .then(result => {
                if (!result.paid) return;
                clearInterval(_memberPollingTimer); clearInterval(countdown);
                _memberPollingTimer = null;
                onMembershipPaid(data);
            })
            .catch(() => {});

        if (attempts >= MAX) {
            clearInterval(_memberPollingTimer); clearInterval(countdown);
            _memberPollingTimer = null;
            const statusEl = document.getElementById('memberPaymentStatus');
            if (statusEl) {
                statusEl.innerHTML = `
                    <div style="background:#fff7ed;border:1.5px solid #fed7aa;border-radius:12px;padding:.8rem 1rem;color:#9a3412;font-size:.85rem;">
                        <i class="fas fa-exclamation-triangle me-1"></i>
                        <strong>Hết thời gian chờ tự động.</strong><br>
                        Nếu đã chuyển khoản, nhấn <strong>"Kiểm tra ngay"</strong> hoặc liên hệ hỗ trợ với mã <code>${data.member_code}</code>
                    </div>`;
            }
        }
    }, 5000);
}

// ── Kiểm tra thủ công ─────────────────────────────────────────────────────
function manualCheckMembership(mid, data) {
    const btn = document.getElementById('btnManualCheck');
    if (btn) { btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Đang kiểm tra...'; btn.disabled = true; }

    fetch(`api/check-payment-status.php?membership_id=${mid}`)
        .then(r => r.json())
        .then(result => {
            if (result.paid) {
                // Nếu có data từ server, dùng luôn; không thì reload
                if (data && data.member_code) {
                    onMembershipPaid(data);
                } else {
                    window.location.reload();
                }
            } else {
                if (btn) { btn.innerHTML = '<i class="fas fa-sync-alt me-1"></i> Kiểm tra ngay'; btn.disabled = false; }
                const st = document.getElementById('memberStatusText');
                if (st) {
                    st.textContent = '⚠️ Chưa nhận được thanh toán. Kiểm tra lại nội dung chuyển khoản.';
                    setTimeout(() => { if (st) st.textContent = 'Đang chờ xác nhận thanh toán tự động...'; }, 3000);
                }
            }
        })
        .catch(() => {
            if (btn) { btn.innerHTML = '<i class="fas fa-sync-alt me-1"></i> Kiểm tra ngay'; btn.disabled = false; }
            alert('Lỗi kết nối. Vui lòng thử lại.');
        });
}

// ── Xử lý khi thanh toán xác nhận thành công ─────────────────────────────
function onMembershipPaid(data) {
    if (_memberPollingDone) return;
    _memberPollingDone = true;
    if (_memberPollingTimer) { clearInterval(_memberPollingTimer); _memberPollingTimer = null; }

    const container = document.getElementById('membershipWaitingContainer');
    if (container) container.style.display = 'none';

    // Toast thành công
    const toast = document.createElement('div');
    toast.style.cssText = 'position:fixed;top:20px;right:20px;z-index:9999;background:linear-gradient(135deg,#10b981,#059669);color:#fff;padding:1.2rem 1.5rem;border-radius:16px;box-shadow:0 10px 40px rgba(16,185,129,.3);display:flex;align-items:center;gap:.8rem;min-width:300px;font-weight:600;';
    toast.innerHTML = `<i class="fas fa-check-circle fa-lg"></i><div><div>Hội viên đã được kích hoạt! 🎉</div><div style="font-size:.78rem;font-weight:400;opacity:.85;">Mã thẻ: ${data.member_code}</div></div>`;
    document.body.appendChild(toast);

    // Hiện member card
    setTimeout(() => showMemberCard(data), 600);

    // Reload trang sau 5s để sidebar cập nhật
    setTimeout(() => {
        if (toast.parentNode) toast.parentNode.removeChild(toast);
        window.location.reload();
    }, 5000);
}

function cancelMembershipWaiting() {
    if (_memberPollingTimer) { clearInterval(_memberPollingTimer); _memberPollingTimer = null; }
    _memberPollingDone = true;
    const c = document.getElementById('membershipWaitingContainer');
    if (c) c.style.display = 'none';
}

function copyMemberCode(code) {
    navigator.clipboard.writeText(code).then(() => {
        const btn = document.getElementById('btnCopyCode');
        if (btn) { btn.innerHTML = '<i class="fas fa-check"></i>'; setTimeout(() => btn.innerHTML = '<i class="fas fa-copy"></i>', 1500); }
    }).catch(() => {
        const el = document.createElement('textarea');
        el.value = code; document.body.appendChild(el); el.select(); document.execCommand('copy'); document.body.removeChild(el);
    });
}

function showMemberCard(data) {
    document.getElementById('mc-code').textContent  = data.member_code;
    document.getElementById('mc-name').textContent  = data.user_name || '';
    document.getElementById('mc-plan').textContent  = `${data.months} tháng`;
    document.getElementById('mc-start').textContent = formatDate(data.start_date);
    document.getElementById('mc-end').textContent   = formatDate(data.end_date);

    document.getElementById('mc-tickets').innerHTML = `
        <i class="fas fa-ticket-alt me-2"></i>
        ${data.plan_name} : ${data.plan_detail}
        — <strong>${data.free_tickets} vé miễn phí</strong>
        &nbsp;·&nbsp; Giá ưu đãi <strong style="color:#fbbf24;">${(data.member_price||80000).toLocaleString('vi-VN')}đ/giờ</strong>
    `;

    const statusEl = document.getElementById('mc-status');
    if (statusEl) {
        if (data.payment_status === 'pending') {
            statusEl.style.cssText = 'background:rgba(251,191,36,.2);border:1px solid rgba(251,191,36,.4);border-radius:8px;padding:4px 10px;font-size:.72rem;font-weight:700;color:#fbbf24;';
            statusEl.textContent = 'PENDING';
        } else {
            statusEl.style.cssText = 'background:rgba(74,222,128,.2);border:1px solid rgba(74,222,128,.4);border-radius:8px;padding:4px 10px;font-size:.72rem;font-weight:700;color:#4ade80;';
            statusEl.textContent = 'ACTIVE';
        }
    }

    const qrEl = document.getElementById('mc-qr');
    qrEl.innerHTML = '';
    if (typeof QRCode !== 'undefined') {
        new QRCode(qrEl, {
            text: `BADMINTONPRO-MEMBER|${data.member_code}|${data.end_date}`,
            width: 120, height: 120,
            colorDark: '#1a1a2e', colorLight: '#ffffff',
            correctLevel: QRCode.CorrectLevel.H
        });
    }

    const cashNote = document.getElementById('mc-cash-note');
    if (cashNote) cashNote.style.display = data.payment_method === 'cash' ? 'block' : 'none';

    setTimeout(() => new bootstrap.Modal(document.getElementById('memberCardModal')).show(), 400);
}

function formatDate(dateStr) {
    if (!dateStr) return '—';
    const d = new Date(dateStr);
    return `${String(d.getDate()).padStart(2,'0')}/${String(d.getMonth()+1).padStart(2,'0')}/${d.getFullYear()}`;
}

function printMemberCard() { window.print(); }

// ── Tạo lịch đặt sân tự động từ gói hội viên ──
function createMemberBookings(membershipId) {
    // Lấy court_id từ URL
    const urlCourtId = new URLSearchParams(window.location.search).get('court_id');
    if (urlCourtId) {
        doCreateBookings(membershipId, parseInt(urlCourtId));
        return;
    }
    // Không có court_id trong URL → hiện modal chọn sân
    document.getElementById('selectCourtMembershipId').value = membershipId;
    new bootstrap.Modal(document.getElementById('selectCourtModal')).show();
}

function doCreateBookings(membershipId, courtId) {
    const btn = document.getElementById('btnCreateBookings');
    if (btn) { btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Đang tạo lịch...'; btn.disabled = true; }

    const fd = new FormData();
    fd.append('action', 'create_bookings');
    fd.append('membership_id', membershipId);
    fd.append('court_id', courtId);

    fetch('api/membership.php', { method: 'POST', body: fd })
        .then(r => {
            if (!r.ok) throw new Error('HTTP ' + r.status);
            return r.json();
        })
        .then(data => {
            if (btn) { btn.innerHTML = '<i class="fas fa-calendar-plus me-2"></i>Tạo lịch đặt sân tự động'; btn.disabled = false; }
            if (data.success) {
                const msg = data.created > 0
                    ? `✅ Đã tạo ${data.created} buổi cho khung giờ ${data.time_range}!${data.skipped > 0 ? '\n(' + data.skipped + ' buổi bị trùng lịch đã bỏ qua)' : ''}`
                    : `⚠️ Không tạo được buổi nào (khung giờ ${data.time_range} đã bị đặt hết hoặc không hợp lệ)`;
                alert(msg);
                if (data.created > 0) window.location.reload();
            } else {
                alert('Lỗi: ' + (data.error || 'Không xác định'));
            }
        })
        .catch(err => {
            if (btn) { btn.innerHTML = '<i class="fas fa-calendar-plus me-2"></i>Tạo lịch đặt sân tự động'; btn.disabled = false; }
            alert('Lỗi: ' + err.message);
        });
}

function confirmSelectCourt() {
    const sel = document.getElementById('selectCourtDropdown');
    const mid = document.getElementById('selectCourtMembershipId').value;
    if (!sel.value) { alert('Vui lòng chọn sân'); return; }
    bootstrap.Modal.getInstance(document.getElementById('selectCourtModal')).hide();
    doCreateBookings(parseInt(mid), parseInt(sel.value));
}

// ── Lịch sử dùng vé ──
function showTicketLog(membershipId) {
    const modal = new bootstrap.Modal(document.getElementById('ticketLogModal'));
    const body  = document.getElementById('ticketLogBody');
    body.innerHTML = '<div class="text-center py-3"><div class="spinner-border text-primary"></div></div>';
    modal.show();

    fetch('api/membership.php?action=ticket_logs')
        .then(r => r.json())
        .then(data => {
            if (!data.logs || data.logs.length === 0) {
                body.innerHTML = `
                    <div style="text-align:center;padding:2rem;color:#9ca3af;">
                        <i class="fas fa-ticket-alt fa-2x mb-2 d-block" style="opacity:.3;"></i>
                        Chưa có lịch sử dùng vé
                    </div>`;
                return;
            }
            let html = '';
            data.logs.forEach(log => {
                const isUse    = log.action === 'use';
                const isRefund = log.action === 'refund';
                const icon     = isRefund ? 'fa-undo' : (isUse ? 'fa-minus-circle' : 'fa-plus-circle');
                const color    = isRefund ? '#10b981' : (isUse ? '#ef4444' : '#3b82f6');
                const delta    = log.tickets_delta > 0 ? `+${log.tickets_delta}` : log.tickets_delta;

                html += `
                    <div style="display:flex;align-items:center;gap:.8rem;padding:.7rem 0;border-bottom:1px solid #f3f4f6;">
                        <div style="width:36px;height:36px;background:${color}20;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="fas ${icon}" style="color:${color};"></i>
                        </div>
                        <div style="flex:1;">
                            <div style="font-weight:600;font-size:.85rem;">${log.note || (isUse ? 'Dùng vé' : 'Hoàn vé')}</div>
                            ${log.court_name ? `<div style="font-size:.75rem;color:#6b7280;"><i class="fas fa-map-marker-alt me-1"></i>${log.court_name}</div>` : ''}
                            ${log.booking_date ? `<div style="font-size:.75rem;color:#9ca3af;">${log.booking_date} · ${log.start_time||''} – ${log.end_time||''}</div>` : ''}
                            <div style="font-size:.72rem;color:#9ca3af;">${new Date(log.created_at).toLocaleString('vi-VN')}</div>
                        </div>
                        <div style="font-weight:800;font-size:1rem;color:${color};">${delta} vé</div>
                    </div>
                `;
            });
            body.innerHTML = html;
        })
        .catch(() => { body.innerHTML = '<div class="alert alert-danger">Lỗi tải dữ liệu</div>'; });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
