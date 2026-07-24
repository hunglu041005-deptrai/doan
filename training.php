<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/header.php';
?>
<style>
/* ===== TRAINING PAGE - VIP DESIGN ===== */
.training-page { background: #f8fafc; }

/* Hero */
.training-hero {
    background: linear-gradient(135deg, #0f0c29 0%, #302b63 50%, #24243e 100%);
    padding: 5rem 0 7rem;
    position: relative;
    overflow: hidden;
}
.training-hero::before {
    content: '';
    position: absolute;
    top: -100px; right: -100px;
    width: 500px; height: 500px;
    background: radial-gradient(circle, rgba(255,193,7,.12) 0%, transparent 70%);
    border-radius: 50%;
}
.training-hero::after {
    content: '';
    position: absolute;
    bottom: -80px; left: -80px;
    width: 400px; height: 400px;
    background: radial-gradient(circle, rgba(255,87,34,.08) 0%, transparent 70%);
    border-radius: 50%;
}
.hero-tag {
    display: inline-flex; align-items: center; gap: 8px;
    background: rgba(255,193,7,.15); border: 1px solid rgba(255,193,7,.3);
    color: #fbbf24; padding: 6px 16px; border-radius: 50px;
    font-size: .82rem; font-weight: 700; margin-bottom: 1.5rem;
}
.hero-title {
    font-size: 3rem; font-weight: 900; color: #fff;
    line-height: 1.15; margin-bottom: 1rem;
}
.hero-title .accent {
    background: linear-gradient(135deg, #fbbf24, #f59e0b);
    -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
}
.hero-desc { color: rgba(255,255,255,.6); font-size: 1.05rem; margin-bottom: 2rem; max-width: 520px; }
.hero-stats { display: flex; gap: 2.5rem; flex-wrap: wrap; }
.hero-stat .n { font-size: 2rem; font-weight: 900; color: #fbbf24; }
.hero-stat .l { font-size: .75rem; color: rgba(255,255,255,.5); }

/* Wave */
.wave-sep { margin-top: -2px; line-height: 0; background: #302b63; }
.wave-sep svg { display: block; width: 100%; }
</style>
<style>
/* Course cards */
.section-label { font-size: .75rem; font-weight: 700; color: #6366f1; text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: .5rem; }
.section-title-big { font-size: 1.9rem; font-weight: 900; color: #111827; margin-bottom: .5rem; }
.section-sub { color: #6b7280; font-size: .95rem; }

.course-card {
    background: #fff; border-radius: 24px; overflow: hidden;
    box-shadow: 0 4px 24px rgba(0,0,0,.06);
    border: 2px solid transparent;
    transition: all .35s cubic-bezier(.4,0,.2,1);
    position: relative; height: 100%;
}
.course-card:hover {
    border-color: #fbbf24;
    box-shadow: 0 16px 48px rgba(251,191,36,.18);
    transform: translateY(-6px);
}
.course-card.featured { border-color: #fbbf24; box-shadow: 0 8px 32px rgba(251,191,36,.2); }

.course-img-wrap { position: relative; overflow: hidden; height: 220px; }
.course-img-wrap img { width: 100%; height: 100%; object-fit: cover; transition: transform .4s ease; }
.course-card:hover .course-img-wrap img { transform: scale(1.06); }

.level-chip {
    position: absolute; top: 14px; left: 14px;
    padding: 5px 14px; border-radius: 50px;
    font-size: .75rem; font-weight: 800; text-transform: uppercase; letter-spacing: .5px;
}
.level-basic    { background: #d1fae5; color: #065f46; }
.level-mid      { background: #fef3c7; color: #92400e; }
.level-advanced { background: #fee2e2; color: #991b1b; }

.hot-badge {
    position: absolute; top: 14px; right: 14px;
    background: linear-gradient(135deg, #f59e0b, #ef4444);
    color: #fff; font-size: .7rem; font-weight: 800; padding: 4px 10px; border-radius: 50px;
}
.course-body { padding: 1.8rem; }
.course-name { font-size: 1.15rem; font-weight: 800; color: #111827; margin-bottom: 1rem; }

.course-meta { display: grid; grid-template-columns: 1fr 1fr; gap: .6rem; margin-bottom: 1.2rem; }
.meta-item {
    display: flex; align-items: center; gap: 8px;
    background: #f9fafb; border-radius: 10px; padding: .55rem .8rem;
    font-size: .82rem; color: #374151;
}
.meta-item i { width: 16px; text-align: center; }

.course-features { margin-bottom: 1.4rem; }
.course-features li {
    display: flex; align-items: center; gap: 8px;
    font-size: .85rem; color: #4b5563; padding: .4rem 0;
    border-bottom: 1px solid #f3f4f6;
}
.course-features li:last-child { border: none; }
.course-features li i { color: #10b981; font-size: .9rem; }

.price-row { display: flex; justify-content: space-between; align-items: center; }
.course-price { font-size: 1.4rem; font-weight: 900; color: #111827; }
.course-price small { font-size: .8rem; font-weight: 500; color: #9ca3af; }

.btn-enroll {
    padding: .65rem 1.6rem; border-radius: 12px; font-weight: 700; font-size: .9rem;
    border: none; cursor: pointer; transition: all .2s; text-decoration: none;
    display: inline-flex; align-items: center; gap: 6px;
}
.btn-enroll-green { background: linear-gradient(135deg, #10b981, #059669); color: #fff; box-shadow: 0 4px 15px rgba(16,185,129,.3); }
.btn-enroll-gold  { background: linear-gradient(135deg, #f59e0b, #d97706); color: #fff; box-shadow: 0 4px 15px rgba(245,158,11,.3); }
.btn-enroll-red   { background: linear-gradient(135deg, #ef4444, #dc2626); color: #fff; box-shadow: 0 4px 15px rgba(239,68,68,.3); }
.btn-enroll:hover { transform: translateY(-2px); filter: brightness(1.08); color: #fff; }
</style>
<style>
/* Coach cards */
.coaches-section { background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); padding: 5rem 0; }
.coach-card {
    background: #fff; border-radius: 24px; padding: 2.2rem 1.8rem;
    text-align: center; box-shadow: 0 4px 24px rgba(0,0,0,.06);
    border: 2px solid transparent; transition: all .3s ease; height: 100%;
}
.coach-card:hover { border-color: #fbbf24; box-shadow: 0 12px 40px rgba(251,191,36,.15); transform: translateY(-4px); }
.coach-avatar { position: relative; display: inline-block; margin-bottom: 1.2rem; }
.coach-avatar img { width: 100px; height: 100px; object-fit: cover; border-radius: 50%; border: 4px solid #fef3c7; }
.coach-ring {
    position: absolute; inset: -6px; border-radius: 50%;
    border: 3px dashed #fbbf24; animation: spin-slow 8s linear infinite; opacity: .5;
}
@keyframes spin-slow { to { transform: rotate(360deg); } }
.coach-name { font-size: 1.1rem; font-weight: 800; color: #111827; margin-bottom: .3rem; }
.coach-role { font-size: .82rem; color: #6b7280; margin-bottom: 1rem; }
.coach-tag { display: inline-flex; align-items: center; gap: 5px; background: #f9fafb; border-radius: 8px; padding: 4px 10px; font-size: .78rem; font-weight: 600; color: #374151; margin: 3px; }

/* Registration form */
.reg-section { padding: 5rem 0; background: #fff; }
.reg-card {
    background: linear-gradient(135deg, #0f0c29, #302b63);
    border-radius: 28px; overflow: hidden;
    box-shadow: 0 20px 60px rgba(0,0,0,.2);
}
.reg-header { padding: 2.5rem; border-bottom: 1px solid rgba(255,255,255,.1); }
.reg-header h3 { color: #fff; font-weight: 900; font-size: 1.6rem; margin-bottom: .3rem; }
.reg-header p { color: rgba(255,255,255,.5); font-size: .9rem; }
.reg-body { padding: 2.5rem; }
.form-label-white { color: rgba(255,255,255,.8); font-weight: 600; font-size: .85rem; margin-bottom: .4rem; }
.form-control-dark, .form-select-dark {
    background: rgba(255,255,255,.08); border: 1px solid rgba(255,255,255,.12);
    color: #fff; border-radius: 12px; padding: .75rem 1rem;
    transition: all .2s;
}
.form-control-dark:focus, .form-select-dark:focus {
    background: rgba(255,255,255,.12); border-color: #fbbf24;
    box-shadow: 0 0 0 3px rgba(251,191,36,.15); color: #fff;
}
.form-control-dark::placeholder { color: rgba(255,255,255,.35); }
.form-select-dark option { background: #302b63; color: #fff; }
.btn-register-main {
    background: linear-gradient(135deg, #fbbf24, #f59e0b); color: #111;
    border: none; border-radius: 14px; padding: 1rem 2rem;
    font-weight: 900; font-size: 1rem; width: 100%; cursor: pointer;
    box-shadow: 0 8px 25px rgba(251,191,36,.4); transition: all .2s;
}
.btn-register-main:hover { transform: translateY(-2px); box-shadow: 0 12px 35px rgba(251,191,36,.5); }

/* Success modal */
.modal-success-card { border-radius: 24px !important; overflow: hidden; border: none; }
.modal-success-icon {
    width: 90px; height: 90px; border-radius: 50%;
    background: linear-gradient(135deg, #10b981, #059669);
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 1.5rem; box-shadow: 0 8px 25px rgba(16,185,129,.3);
}
</style>

<div class="training-page">

<!-- ===== HERO ===== -->
<div class="training-hero">
    <div class="container position-relative" style="z-index:2;">
        <div class="row align-items-center">
            <div class="col-lg-7">
                <div class="hero-tag"><i class="fas fa-graduation-cap"></i> Học viện cầu lông BadmintonPro</div>
                <h1 class="hero-title">Nâng tầm kỹ năng<br>cùng <span class="accent">chuyên gia</span></h1>
                <p class="hero-desc">Chương trình đào tạo chuyên nghiệp từ cơ bản đến nâng cao — HLV có chứng chỉ quốc tế BWF, lớp nhỏ 3-6 học viên.</p>
                <div class="hero-stats">
                    <div class="hero-stat"><span class="n">500+</span><span class="l">Học viên</span></div>
                    <div class="hero-stat"><span class="n">3</span><span class="l">Khóa học</span></div>
                    <div class="hero-stat"><span class="n">BWF</span><span class="l">Chứng chỉ</span></div>
                    <div class="hero-stat"><span class="n">15+</span><span class="l">Năm kinh nghiệm</span></div>
                </div>
            </div>
            <div class="col-lg-5 d-none d-lg-flex justify-content-end">
                <div style="width:300px;height:300px;background:rgba(251,191,36,.08);border-radius:50%;display:flex;align-items:center;justify-content:center;border:2px solid rgba(251,191,36,.15);">
                    <div style="width:200px;height:200px;background:rgba(251,191,36,.12);border-radius:50%;display:flex;align-items:center;justify-content:center;">
                        <i class="fas fa-graduation-cap" style="font-size:5rem;color:rgba(251,191,36,.5);"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="wave-sep"><svg viewBox="0 0 1440 60" xmlns="http://www.w3.org/2000/svg"><path d="M0,30 C360,60 1080,0 1440,30 L1440,60 L0,60 Z" fill="#f8fafc"/></svg></div>

<!-- ===== COURSES ===== -->
<section style="padding:5rem 0;background:#f8fafc;">
    <div class="container">
        <div class="text-center mb-5">
            <div class="section-label">Chương trình đào tạo</div>
            <h2 class="section-title-big">Chọn khóa học phù hợp</h2>
            <p class="section-sub">Lộ trình rõ ràng, phù hợp mọi trình độ</p>
        </div>

        <div class="row g-4">
            <!-- Cơ bản -->
            <div class="col-lg-4">
                <div class="course-card">
                    <div class="course-img-wrap">
                        <img src="https://images.unsplash.com/photo-1626224583764-f87db24ac4ea?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" alt="Cơ bản">
                        <span class="level-chip level-basic">Cơ bản</span>
                    </div>
                    <div class="course-body">
                        <div class="course-name">Khóa cầu lông cơ bản</div>
                        <div class="course-meta">
                            <div class="meta-item"><i class="fas fa-clock text-amber-500" style="color:#f59e0b;"></i> 12 buổi</div>
                            <div class="meta-item"><i class="fas fa-users" style="color:#3b82f6;"></i> 4-6 học viên</div>
                            <div class="meta-item"><i class="fas fa-calendar" style="color:#8b5cf6;"></i> 2 buổi/tuần</div>
                            <div class="meta-item"><i class="fas fa-hourglass" style="color:#10b981;"></i> 3 tháng</div>
                        </div>
                        <ul class="list-unstyled course-features">
                            <li><i class="fas fa-check-circle"></i> Kỹ thuật cầm vợt cơ bản</li>
                            <li><i class="fas fa-check-circle"></i> Tư thế & di chuyển</li>
                            <li><i class="fas fa-check-circle"></i> Kỹ thuật đánh cầu</li>
                            <li><i class="fas fa-check-circle"></i> Luật thi đấu cơ bản</li>
                        </ul>
                        <div class="price-row">
                            <div class="course-price">1.800.000đ <small>/khóa</small></div>
                            <button class="btn-enroll btn-enroll-green" onclick="openRegModal('beginner')">
                                <i class="fas fa-user-plus"></i> Đăng ký
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Trung cấp -->
            <div class="col-lg-4">
                <div class="course-card featured">
                    <div class="course-img-wrap">
                        <img src="https://www.malaymail.com/malaymail/uploads/images/2025/11/11/309213.JPG" alt="Trung cấp">
                        <span class="level-chip level-mid">Trung cấp</span>
                        <span class="hot-badge">⭐ Phổ biến</span>
                    </div>
                    <div class="course-body">
                        <div class="course-name">Khóa cầu lông trung cấp</div>
                        <div class="course-meta">
                            <div class="meta-item"><i class="fas fa-clock" style="color:#f59e0b;"></i> 16 buổi</div>
                            <div class="meta-item"><i class="fas fa-users" style="color:#3b82f6;"></i> 4-6 học viên</div>
                            <div class="meta-item"><i class="fas fa-calendar" style="color:#8b5cf6;"></i> 2 buổi/tuần</div>
                            <div class="meta-item"><i class="fas fa-hourglass" style="color:#10b981;"></i> 4 tháng</div>
                        </div>
                        <ul class="list-unstyled course-features">
                            <li><i class="fas fa-check-circle"></i> Kỹ thuật nâng cao</li>
                            <li><i class="fas fa-check-circle"></i> Chiến thuật thi đấu</li>
                            <li><i class="fas fa-check-circle"></i> Thể lực chuyên môn</li>
                            <li><i class="fas fa-check-circle"></i> Thi đấu thực tế</li>
                        </ul>
                        <div class="price-row">
                            <div class="course-price">2.800.000đ <small>/khóa</small></div>
                            <button class="btn-enroll btn-enroll-gold" onclick="openRegModal('intermediate')">
                                <i class="fas fa-star"></i> Đăng ký
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Nâng cao -->
            <div class="col-lg-4">
                <div class="course-card">
                    <div class="course-img-wrap">
                        <img src="https://i.pinimg.com/736x/f1/98/c0/f198c07645f847e714cb44ca60b12ffd.jpg" alt="Nâng cao">
                        <span class="level-chip level-advanced">Nâng cao</span>
                    </div>
                    <div class="course-body">
                        <div class="course-name">Khóa cầu lông nâng cao</div>
                        <div class="course-meta">
                            <div class="meta-item"><i class="fas fa-clock" style="color:#f59e0b;"></i> 20 buổi</div>
                            <div class="meta-item"><i class="fas fa-users" style="color:#3b82f6;"></i> 3-4 học viên</div>
                            <div class="meta-item"><i class="fas fa-calendar" style="color:#8b5cf6;"></i> 3 buổi/tuần</div>
                            <div class="meta-item"><i class="fas fa-hourglass" style="color:#10b981;"></i> 5 tháng</div>
                        </div>
                        <ul class="list-unstyled course-features">
                            <li><i class="fas fa-check-circle"></i> Kỹ thuật chuyên sâu</li>
                            <li><i class="fas fa-check-circle"></i> Chiến thuật cao cấp</li>
                            <li><i class="fas fa-check-circle"></i> Tâm lý thi đấu</li>
                            <li><i class="fas fa-check-circle"></i> Chuẩn bị thi đấu</li>
                        </ul>
                        <div class="price-row">
                            <div class="course-price">4.500.000đ <small>/khóa</small></div>
                            <button class="btn-enroll btn-enroll-red" onclick="openRegModal('advanced')">
                                <i class="fas fa-trophy"></i> Đăng ký
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===== COACHES ===== -->
<section class="coaches-section">
    <div class="container">
        <div class="text-center mb-5">
            <div class="section-label">Đội ngũ</div>
            <h2 class="section-title-big">Huấn luyện viên của chúng tôi</h2>
            <p class="section-sub">Chứng chỉ quốc tế, giàu kinh nghiệm và tận tâm</p>
        </div>
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="coach-card">
                    <div class="coach-avatar">
                        <img src="https://i.pinimg.com/736x/9b/94/08/9b94086c48b773a09d8316398b2bf29e.jpg" alt="HLV A">
                        <div class="coach-ring"></div>
                    </div>
                    <div class="coach-name">HLV Lữ Đăng Hưng</div>
                    <div class="coach-role">Chuyên gia kỹ thuật cơ bản</div>
                    <div>
                        <span class="coach-tag"><i class="fas fa-medal" style="color:#f59e0b;"></i> 15 năm kinh nghiệm</span>
                        <span class="coach-tag"><i class="fas fa-certificate" style="color:#10b981;"></i> BWF Level 2</span>
                        <span class="coach-tag"><i class="fas fa-users" style="color:#3b82f6;"></i> 500+ học viên</span>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="coach-card" style="border-color:#fbbf24;">
                    <div class="coach-avatar">
                        <img src="https://www.welt.de/img/regionales/nrw/mobile160865126/4672500127-ci102l-w1024/urn-newsml-dpa-com-20090101-170104-99-747542-large-4-3-jpg.jpg" alt="HLV B">
                        <div class="coach-ring"></div>
                    </div>
                    <div class="coach-name">HLV Lê Anh Dũng</div>
                    <div class="coach-role">Chuyên gia chiến thuật</div>
                    <div>
                        <span class="coach-tag"><i class="fas fa-medal" style="color:#f59e0b;"></i> 12 năm kinh nghiệm</span>
                        <span class="coach-tag"><i class="fas fa-certificate" style="color:#10b981;"></i> BWF Level 3</span>
                        <span class="coach-tag"><i class="fas fa-trophy" style="color:#ef4444;"></i> Cựu VĐV quốc gia</span>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="coach-card">
                    <div class="coach-avatar">
                        <img src="https://tse3.mm.bing.net/th/id/OIP.eXWUbtvWHob-V_ccaDcMlQHaEa?r=0&cb=thfc1falcon2&rs=1&pid=ImgDetMain&o=7&rm=3" alt="HLV C">
                        <div class="coach-ring"></div>
                    </div>
                    <div class="coach-name">HLV Lê Văn C</div>
                    <div class="coach-role">Chuyên gia thể lực</div>
                    <div>
                        <span class="coach-tag"><i class="fas fa-medal" style="color:#f59e0b;"></i> 10 năm kinh nghiệm</span>
                        <span class="coach-tag"><i class="fas fa-certificate" style="color:#10b981;"></i> Fitness Level 3</span>
                        <span class="coach-tag"><i class="fas fa-dumbbell" style="color:#6366f1;"></i> Chuyên gia thể lực</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===== REGISTRATION ===== -->
<section class="reg-section">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="reg-card">
                    <div class="reg-header">
                        <div style="display:flex;align-items:center;gap:1rem;">
                            <div style="width:48px;height:48px;background:rgba(251,191,36,.2);border-radius:14px;display:flex;align-items:center;justify-content:center;">
                                <i class="fas fa-user-plus" style="color:#fbbf24;font-size:1.2rem;"></i>
                            </div>
                            <div>
                                <h3>Đăng ký khóa học</h3>
                                <p class="mb-0">Điền thông tin để chúng tôi liên hệ xác nhận</p>
                            </div>
                        </div>
                    </div>
                    <div class="reg-body">
                        <form id="trainingForm">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label-white">Họ và tên *</label>
                                    <input type="text" name="student_name" class="form-control form-control-dark" placeholder="Nhập họ và tên" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label-white">Số điện thoại *</label>
                                    <input type="tel" name="phone" class="form-control form-control-dark" placeholder="0123456789" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label-white">Email</label>
                                    <input type="email" name="email" class="form-control form-control-dark" placeholder="email@example.com">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label-white">Độ tuổi *</label>
                                    <select name="age_group" class="form-select form-select-dark" required>
                                        <option value="">Chọn độ tuổi</option>
                                        <option>6-12 tuổi</option>
                                        <option>13-17 tuổi</option>
                                        <option>18-30 tuổi</option>
                                        <option>Trên 30 tuổi</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label-white">Khóa học *</label>
                                    <select name="course" class="form-select form-select-dark" id="courseSelect" required>
                                        <option value="">Chọn khóa học</option>
                                        <option value="beginner">Cơ bản — 1.800.000đ</option>
                                        <option value="intermediate">Trung cấp — 2.800.000đ</option>
                                        <option value="advanced">Nâng cao — 4.500.000đ</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label-white">Thời gian học</label>
                                    <select name="preferred_time" class="form-select form-select-dark">
                                        <option value="">Chọn thời gian</option>
                                        <option>Sáng (6:00–9:00)</option>
                                        <option>Chiều (14:00–17:00)</option>
                                        <option>Tối (18:00–21:00)</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label-white">HLV kèm 1-1</label>
                                    <!-- Hidden input để lưu coach_id được chọn -->
                                    <input type="hidden" name="preferred_coach_id" id="selectedCoachId">
                                    <input type="hidden" name="preferred_coach"    id="selectedCoachName">

                                    <!-- Button trigger picker -->
                                    <div id="coachPickerBtn"
                                         onclick="openCoachPicker()"
                                         style="background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.12);
                                                border-radius:12px;padding:.75rem 1rem;cursor:pointer;
                                                color:rgba(255,255,255,.35);transition:all .2s;display:flex;
                                                align-items:center;gap:.75rem;min-height:52px;">
                                        <i class="fas fa-user-tie" style="font-size:1.1rem;opacity:.5;"></i>
                                        <span id="coachPickerLabel">Hệ thống tự gán HLV phù hợp</span>
                                    </div>
                                    <div id="coachStatus" class="mt-2"></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label-white">Trình độ hiện tại</label>
                                    <select name="current_level" class="form-select form-select-dark">
                                        <option value="">Chọn trình độ</option>
                                        <option>Mới bắt đầu</option>
                                        <option>Biết cơ bản</option>
                                        <option>Trung bình</option>
                                        <option>Khá</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label-white">Mục tiêu học tập</label>
                                    <textarea name="learning_goals" rows="3" class="form-control form-control-dark" placeholder="Chia sẻ mục tiêu của bạn..."></textarea>
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn-register-main">
                                        <i class="fas fa-graduation-cap me-2"></i>Đăng ký khóa học ngay
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
</div>

<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
const courseLabels = {
    'beginner':     'Cơ bản (3 tháng)',
    'intermediate': 'Trung cấp (4 tháng)',
    'advanced':     'Nâng cao (5 tháng)',
};

function openRegModal(course) {
    const select = document.getElementById('courseSelect');
    if (select) select.value = course;
    document.querySelector('.reg-card')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

// ======= COACH PICKER =======
let allCoaches = [];

document.addEventListener('DOMContentLoaded', function() {
    loadCoaches();
});

function loadCoaches() {
    fetch('api/coaches.php')
        .then(r => r.json())
        .then(data => {
            if (!data.success) return;
            allCoaches = data.coaches;
        })
        .catch(() => {});
}

function openCoachPicker() {
    // Tạo modal picker nếu chưa có
    let modal = document.getElementById('coachPickerModal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'coachPickerModal';
        modal.style.cssText = `
            position:fixed;inset:0;z-index:9999;display:flex;align-items:center;justify-content:center;
            background:rgba(0,0,0,.7);backdrop-filter:blur(6px);padding:1rem;
        `;
        modal.innerHTML = `
            <div style="background:linear-gradient(135deg,#0f0c29,#1e1b4b);border-radius:24px;
                        width:100%;max-width:700px;max-height:85vh;overflow:hidden;display:flex;flex-direction:column;
                        box-shadow:0 24px 80px rgba(0,0,0,.5);border:1px solid rgba(255,255,255,.1);">
                <div style="padding:1.5rem 1.8rem;border-bottom:1px solid rgba(255,255,255,.1);
                             display:flex;align-items:center;justify-content:space-between;">
                    <div>
                        <h5 style="color:#fff;font-weight:800;margin:0 0 .2rem;">Chọn Huấn Luyện Viên</h5>
                        <small style="color:rgba(255,255,255,.5);">Click vào HLV bạn muốn đăng ký kèm 1-1</small>
                    </div>
                    <button onclick="closeCoachPicker()" style="background:rgba(255,255,255,.1);border:none;
                            color:#fff;width:36px;height:36px;border-radius:50%;font-size:1.1rem;cursor:pointer;">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <!-- Option: auto assign -->
                <div id="coachAutoOption"
                     onclick="selectCoach(null)"
                     style="margin:1rem 1.5rem .5rem;background:rgba(251,191,36,.1);border:2px solid rgba(251,191,36,.3);
                            border-radius:14px;padding:1rem 1.2rem;cursor:pointer;transition:all .2s;
                            display:flex;align-items:center;gap:1rem;">
                    <div style="width:48px;height:48px;background:rgba(251,191,36,.2);border-radius:50%;
                                display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="fas fa-magic" style="color:#fbbf24;font-size:1.2rem;"></i>
                    </div>
                    <div>
                        <div style="color:#fbbf24;font-weight:700;font-size:.95rem;">Hệ thống tự gán HLV phù hợp</div>
                        <small style="color:rgba(255,255,255,.5);">Hệ thống chọn HLV còn chỗ và phù hợp nhất với bạn</small>
                    </div>
                    <div style="margin-left:auto;"><i class="fas fa-chevron-right" style="color:rgba(255,255,255,.3);"></i></div>
                </div>

                <div style="overflow-y:auto;padding:0 1.5rem 1.5rem;" id="coachPickerList">
                    <div style="color:rgba(255,255,255,.4);text-align:center;padding:2rem;">
                        <div class="spinner-border spinner-border-sm me-2"></div>Đang tải danh sách HLV...
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
        modal.addEventListener('click', e => { if (e.target === modal) closeCoachPicker(); });
    }

    // Render danh sách HLV
    renderCoachList();
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function renderCoachList() {
    const list = document.getElementById('coachPickerList');
    if (!list) return;

    if (!allCoaches.length) {
        // Thử load lại
        fetch('api/coaches.php').then(r=>r.json()).then(d => {
            if (d.success) { allCoaches = d.coaches; renderCoachList(); }
        });
        return;
    }

    let html = '<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:1rem;margin-top:.5rem;">';
    allCoaches.forEach(c => {
        const pct = Math.round((c.current / c.max) * 100);
        const barColor = c.full ? '#ef4444' : c.remaining <= 1 ? '#f59e0b' : '#4ade80';
        const statusText = c.full ? '❌ Đã đầy' : `✅ Còn ${c.remaining} chỗ`;
        const statusColor = c.full ? '#ef4444' : '#4ade80';

        // Avatar: dùng ảnh thật nếu có, fallback icon
        const avatarHtml = c.avatar
            ? `<img src="${c.avatar}" alt="${c.name}" style="width:64px;height:64px;object-fit:cover;border-radius:50%;border:3px solid rgba(251,191,36,.4);">`
            : `<div style="width:64px;height:64px;background:linear-gradient(135deg,#302b63,#6366f1);border-radius:50%;
                           display:flex;align-items:center;justify-content:center;flex-shrink:0;border:3px solid rgba(251,191,36,.3);">
                   <i class="fas fa-chalkboard-teacher" style="color:#fff;font-size:1.4rem;"></i>
               </div>`;

        html += `
            <div class="coach-pick-card ${c.full ? 'is-full' : ''}"
                 onclick="${c.full ? '' : `selectCoach(${JSON.stringify(c).replace(/"/g,'&quot;')})`}"
                 data-coach-id="${c.id}"
                 style="background:rgba(255,255,255,.06);border:2px solid rgba(255,255,255,.1);
                        border-radius:16px;padding:1.2rem;cursor:${c.full ? 'not-allowed' : 'pointer'};
                        transition:all .2s;opacity:${c.full ? '.55' : '1'};">
                <div style="display:flex;align-items:center;gap:.8rem;margin-bottom:1rem;">
                    <div style="flex-shrink:0;">${avatarHtml}</div>
                    <div style="min-width:0;">
                        <div style="color:#fff;font-weight:800;font-size:.95rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${c.name}</div>
                        <div style="color:rgba(255,255,255,.5);font-size:.78rem;margin-top:.1rem;">${c.specialty || 'Huấn luyện viên'}</div>
                        <div style="color:rgba(251,191,36,.8);font-size:.72rem;margin-top:.2rem;">
                            <i class="fas fa-award me-1"></i>${c.experience_years || 0} năm kinh nghiệm
                        </div>
                    </div>
                </div>

                <!-- Thanh slot -->
                <div style="height:5px;background:rgba(255,255,255,.1);border-radius:4px;overflow:hidden;margin-bottom:.5rem;">
                    <div style="height:100%;width:${pct}%;background:${barColor};border-radius:4px;transition:width .4s;"></div>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center;">
                    <small style="color:rgba(255,255,255,.4);font-size:.72rem;">${c.current}/${c.max} học viên tuần này</small>
                    <small style="color:${statusColor};font-weight:700;font-size:.75rem;">${statusText}</small>
                </div>
                ${c.full ? '' : '<div style="text-align:center;margin-top:.7rem;"><span style="background:rgba(251,191,36,.2);color:#fbbf24;border-radius:8px;padding:3px 12px;font-size:.75rem;font-weight:700;">Chọn HLV này</span></div>'}
            </div>
        `;
    });
    html += '</div>';

    list.innerHTML = html;

    // Hover effect
    list.querySelectorAll('.coach-pick-card:not(.is-full)').forEach(card => {
        card.addEventListener('mouseenter', () => {
            card.style.borderColor = 'rgba(251,191,36,.6)';
            card.style.background = 'rgba(255,255,255,.1)';
            card.style.transform = 'translateY(-2px)';
        });
        card.addEventListener('mouseleave', () => {
            const isSelected = card.dataset.coachId == document.getElementById('selectedCoachId').value;
            card.style.borderColor = isSelected ? 'rgba(251,191,36,.8)' : 'rgba(255,255,255,.1)';
            card.style.background = isSelected ? 'rgba(251,191,36,.08)' : 'rgba(255,255,255,.06)';
            card.style.transform = '';
        });
    });
}

function selectCoach(coach) {
    const idInput   = document.getElementById('selectedCoachId');
    const nameInput = document.getElementById('selectedCoachName');
    const label     = document.getElementById('coachPickerLabel');
    const btn       = document.getElementById('coachPickerBtn');

    if (!coach) {
        // Auto assign
        idInput.value   = '';
        nameInput.value = '';
        label.textContent = 'Hệ thống tự gán HLV phù hợp';
        label.style.color = 'rgba(255,255,255,.35)';
        btn.style.borderColor = 'rgba(255,255,255,.12)';
    } else {
        idInput.value   = coach.id;
        nameInput.value = coach.name;

        // Build label với avatar mini
        const avatarEl = coach.avatar
            ? `<img src="${coach.avatar}" style="width:28px;height:28px;border-radius:50%;object-fit:cover;margin-right:8px;">`
            : `<div style="width:28px;height:28px;background:linear-gradient(135deg,#302b63,#6366f1);border-radius:50%;display:inline-flex;align-items:center;justify-content:center;margin-right:8px;flex-shrink:0;"><i class="fas fa-user" style="color:#fff;font-size:.7rem;"></i></div>`;

        btn.style.color = '#fff';
        btn.style.borderColor = 'rgba(251,191,36,.5)';
        label.innerHTML = `${avatarEl}<span style="color:#fff;font-weight:700;">${coach.name}</span>&nbsp;<small style="color:rgba(255,255,255,.5);">— Còn ${coach.remaining} chỗ</small>`;
    }

    document.getElementById('coachStatus')?.remove && (document.getElementById('coachStatus').innerHTML = '');
    closeCoachPicker();
}

function closeCoachPicker() {
    const modal = document.getElementById('coachPickerModal');
    if (modal) modal.style.display = 'none';
    document.body.style.overflow = '';
}

// ======= SUBMIT FORM → REDIRECT CHECKOUT =======
document.getElementById('trainingForm')?.addEventListener('submit', function(e) {
    e.preventDefault();

    const btn = this.querySelector('button[type=submit]');
    btn.innerHTML = '<div class="spinner-border spinner-border-sm me-2"></div>Đang xử lý...';
    btn.disabled = true;

    const formData = new FormData(this);

    fetch('api/training.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                // Redirect sang trang thanh toán
                window.location.href = data.redirect;
            } else {
                alert('Lỗi: ' + data.error);
                btn.innerHTML = '<i class="fas fa-graduation-cap me-2"></i>Đăng ký khóa học ngay';
                btn.disabled = false;
            }
        })
        .catch(err => {
            console.error('Training API error:', err);
            alert('Có lỗi xảy ra. Vui lòng thử lại.');
            btn.innerHTML = '<i class="fas fa-graduation-cap me-2"></i>Đăng ký khóa học ngay';
            btn.disabled = false;
        });
});

function printStudentCard() { window.print(); }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

