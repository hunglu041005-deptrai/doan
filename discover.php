<?php
require_once __DIR__ . '/includes/functions.php';

// Chặn admin truy cập trang web thường
blockAdminFromPublic();

// Get filter parameters
$filters = [
    'service' => $_GET['service'] ?? '',
    'area' => $_GET['area'] ?? '',
    'type' => $_GET['type'] ?? ''
];

require_once __DIR__ . '/includes/header.php';
?>

<!-- Discover Page Header -->
<section class="bg-info text-white py-4">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="h3 mb-2">🔍 Khám phá dịch vụ</h1>
                <p class="mb-0 opacity-75">Tìm hiểu thêm về các dịch vụ và tiện ích của chúng tôi</p>
            </div>
            <div class="col-md-4 text-md-end">
                <a href="index.php" class="btn btn-light">
                    <i class="fas fa-arrow-left me-2"></i>Về trang chủ
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Service Filter Bar -->
<section class="bg-light py-3 border-bottom">
    <div class="container">
        <form class="row g-2 align-items-end" method="get" action="discover.php">
            <div class="col-sm-6 col-md-4">
                <label class="form-label fw-bold">Loại dịch vụ</label>
                <select name="service" class="form-select">
                    <option value="">Tất cả dịch vụ</option>
                    <option value="booking" <?php echo $filters['service'] === 'booking' ? 'selected' : ''; ?>>Đặt sân</option>
                    <option value="membership" <?php echo $filters['service'] === 'membership' ? 'selected' : ''; ?>>Gói hội viên</option>
                    <option value="training" <?php echo $filters['service'] === 'training' ? 'selected' : ''; ?>>Đào tạo</option>
                    <option value="equipment" <?php echo $filters['service'] === 'equipment' ? 'selected' : ''; ?>>Thiết bị</option>
                </select>
            </div>
            <div class="col-sm-6 col-md-3">
                <label class="form-label fw-bold">Khu vực</label>
                <select name="area" class="form-select">
                    <option value="">Tất cả khu vực</option>
                    <option value="center" <?php echo $filters['area'] === 'center' ? 'selected' : ''; ?>>Trung tâm</option>
                    <option value="north" <?php echo $filters['area'] === 'north' ? 'selected' : ''; ?>>Phía Bắc</option>
                    <option value="south" <?php echo $filters['area'] === 'south' ? 'selected' : ''; ?>>Phía Nam</option>
                    <option value="east" <?php echo $filters['area'] === 'east' ? 'selected' : ''; ?>>Phía Đông</option>
                    <option value="west" <?php echo $filters['area'] === 'west' ? 'selected' : ''; ?>>Phía Tây</option>
                </select>
            </div>
            <div class="col-sm-6 col-md-2">
                <label class="form-label fw-bold">Đối tượng</label>
                <select name="type" class="form-select">
                    <option value="">Tất cả</option>
                    <option value="individual" <?php echo $filters['type'] === 'individual' ? 'selected' : ''; ?>>Cá nhân</option>
                    <option value="group" <?php echo $filters['type'] === 'group' ? 'selected' : ''; ?>>Nhóm</option>
                    <option value="corporate" <?php echo $filters['type'] === 'corporate' ? 'selected' : ''; ?>>Doanh nghiệp</option>
                </select>
            </div>
            <div class="col-sm-6 col-md-2">
                <label class="form-label opacity-0">Tìm</label>
                <button type="submit" class="btn btn-info w-100">
                    <i class="fas fa-search me-2"></i>Tìm kiếm
                </button>
            </div>
            <div class="col-sm-6 col-md-1">
                <label class="form-label opacity-0">Reset</label>
                <a href="discover.php" class="btn btn-outline-secondary w-100" title="Xóa bộ lọc">
                    <i class="fas fa-times"></i>
                </a>
            </div>
        </form>
    </div>
</section>

<!-- Main Discover Section -->
<section class="discover-page-section">
    <div class="container-fluid">
        <div class="row g-0">
            <!-- Main Content -->
            <div class="col-lg-9">
                <div class="discover-content p-4">
                    <!-- Service Categories -->
                    <div class="row g-4 mb-5">
                        <div class="col-12">
                            <h3 class="fw-bold mb-4">🎯 Dịch vụ chính</h3>
                        </div>
                        
                        <!-- Booking Service -->
                        <div class="col-md-6">
                            <div class="service-card h-100 p-4 bg-white rounded-4 shadow-sm border">
                                <div class="service-header d-flex align-items-center mb-3">
                                    <div class="service-icon bg-primary text-white rounded-circle me-3">
                                        <i class="fas fa-calendar-check fa-2x"></i>
                                    </div>
                                    <div>
                                        <h5 class="fw-bold mb-1">Đặt sân trực tuyến</h5>
                                        <small class="text-muted">Nhanh chóng & Tiện lợi</small>
                                    </div>
                                </div>
                                
                                <p class="text-muted mb-3">Hệ thống đặt sân hiện đại với giao diện thân thiện, thanh toán đa dạng và quản lý lịch thông minh.</p>
                                
                                <div class="features-list mb-4">
                                    <div class="feature-item d-flex align-items-center mb-2">
                                        <i class="fas fa-check-circle text-success me-2"></i>
                                        <span>Đặt sân 24/7 online</span>
                                    </div>
                                    <div class="feature-item d-flex align-items-center mb-2">
                                        <i class="fas fa-check-circle text-success me-2"></i>
                                        <span>Thanh toán MoMo, MB Bank</span>
                                    </div>
                                    <div class="feature-item d-flex align-items-center mb-2">
                                        <i class="fas fa-check-circle text-success me-2"></i>
                                        <span>Xác nhận tức thì</span>
                                    </div>
                                    <div class="feature-item d-flex align-items-center">
                                        <i class="fas fa-check-circle text-success me-2"></i>
                                        <span>Hủy miễn phí trước 2h</span>
                                    </div>
                                </div>
                                
                                <div class="d-flex gap-2">
                                    <a href="booking-online.php" class="btn btn-primary flex-fill">
                                        <i class="fas fa-play me-2"></i>Đặt ngay
                                    </a>
                                    <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#bookingGuideModal">
                                        <i class="fas fa-question-circle"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Membership Plans -->
                        <div class="col-md-6">
                            <div class="service-card p-4 bg-white rounded-4 shadow-sm border">
                                <div class="service-header d-flex align-items-center mb-3">
                                    <div class="service-icon bg-success text-white rounded-circle me-3">
                                        <i class="fas fa-id-card fa-2x"></i>
                                    </div>
                                    <div>
                                        <h5 class="fw-bold mb-1">Gói hội viên</h5>
                                        <small class="text-muted">Ưu đãi & Tiết kiệm</small>
                                    </div>
                                </div>

                                <p class="text-muted mb-3">Chơi thả ga với gói hội viên ưu đãi — mua combo vé, tặng vé miễn phí, thời hạn linh hoạt.</p>

                                <div class="features-list mb-4">
                                    <div class="feature-item d-flex align-items-center mb-2">
                                        <i class="fas fa-check-circle text-success me-2"></i>
                                        <span>Combo vé ưu đãi, tặng vé miễn phí</span>
                                    </div>
                                    <div class="feature-item d-flex align-items-center mb-2">
                                        <i class="fas fa-check-circle text-success me-2"></i>
                                        <span>Thời hạn linh hoạt 3 – 12 tháng</span>
                                    </div>
                                    <div class="feature-item d-flex align-items-center mb-2">
                                        <i class="fas fa-check-circle text-success me-2"></i>
                                        <span>Nhiều khung giờ: sáng, chiều, tối</span>
                                    </div>
                                    <div class="feature-item d-flex align-items-center">
                                        <i class="fas fa-check-circle text-success me-2"></i>
                                        <span>Tiết kiệm đến 10% so với giá lẻ</span>
                                    </div>
                                </div>

                                <div class="d-flex gap-2">
                                    <a href="membership.php" class="btn btn-success flex-fill" style="background: linear-gradient(135deg,#28a745,#20c997); border:none; border-radius:12px; font-weight:600;">
                                        <i class="fas fa-play me-2"></i>Xem gói hội viên
                                    </a>
                                    <button class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#membershipModal" style="border-radius:12px;">
                                        <i class="fas fa-question-circle"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Training Service -->
                        <div class="col-md-6">
                            <div class="service-card h-100 p-4 bg-white rounded-4 shadow-sm border">
                                <div class="service-header d-flex align-items-center mb-3">
                                    <div class="service-icon bg-warning text-dark rounded-circle me-3">
                                        <i class="fas fa-graduation-cap fa-2x"></i>
                                    </div>
                                    <div>
                                        <h5 class="fw-bold mb-1">Đào tạo cầu lông</h5>
                                        <small class="text-muted">Từ cơ bản đến nâng cao</small>
                                    </div>
                                </div>
                                
                                <p class="text-muted mb-3">Khóa học cầu lông với huấn luyện viên giàu kinh nghiệm, phương pháp hiện đại và lộ trình rõ ràng.</p>
                                
                                <div class="features-list mb-4">
                                    <div class="feature-item d-flex align-items-center mb-2">
                                        <i class="fas fa-check-circle text-success me-2"></i>
                                        <span>HLV chuyên nghiệp</span>
                                    </div>
                                    <div class="feature-item d-flex align-items-center mb-2">
                                        <i class="fas fa-check-circle text-success me-2"></i>
                                        <span>Lớp nhỏ 4-6 người</span>
                                    </div>
                                    <div class="feature-item d-flex align-items-center mb-2">
                                        <i class="fas fa-check-circle text-success me-2"></i>
                                        <span>Thiết bị đầy đủ</span>
                                    </div>
                                    <div class="feature-item d-flex align-items-center">
                                        <i class="fas fa-check-circle text-success me-2"></i>
                                        <span>Chứng chỉ hoàn thành</span>
                                    </div>
                                </div>
                                
                                <div class="d-flex gap-2">
                                    <a href="training.php" class="btn btn-warning flex-fill">
                                        <i class="fas fa-user-plus me-2"></i>Đăng ký
                                    </a>
                                    <button class="btn btn-outline-warning" data-bs-toggle="modal" data-bs-target="#trainingModal">
                                        <i class="fas fa-calendar-alt"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Equipment Service -->
                        <div class="col-md-6">
                            <div class="service-card h-100 p-4 bg-white rounded-4 shadow-sm border">
                                <div class="service-header d-flex align-items-center mb-3">
                                    <div class="service-icon bg-danger text-white rounded-circle me-3">
                                        <i class="fas fa-shopping-cart fa-2x"></i>
                                    </div>
                                    <div>
                                        <h5 class="fw-bold mb-1">Thiết bị & Phụ kiện</h5>
                                        <small class="text-muted">Chính hãng & Uy tín</small>
                                    </div>
                                </div>
                                
                                <p class="text-muted mb-3">Cửa hàng thiết bị cầu lông với đầy đủ vợt, giày, quần áo và phụ kiện từ các thương hiệu nổi tiếng.</p>
                                
                                <div class="features-list mb-4">
                                    <div class="feature-item d-flex align-items-center mb-2">
                                        <i class="fas fa-check-circle text-success me-2"></i>
                                        <span>Vợt Yonex, Victor, Lining</span>
                                    </div>
                                    <div class="feature-item d-flex align-items-center mb-2">
                                        <i class="fas fa-check-circle text-success me-2"></i>
                                        <span>Giày chuyên dụng</span>
                                    </div>
                                    <div class="feature-item d-flex align-items-center mb-2">
                                        <i class="fas fa-check-circle text-success me-2"></i>
                                        <span>Quần áo thể thao</span>
                                    </div>
                                    <div class="feature-item d-flex align-items-center">
                                        <i class="fas fa-check-circle text-success me-2"></i>
                                        <span>Bảo hành chính hãng</span>
                                    </div>
                                </div>
                                
                                <div class="d-flex gap-2">
                                    <a href="equipment.php" class="btn btn-danger flex-fill">
                                        <i class="fas fa-store me-2"></i>Xem shop
                                    </a>
                                    <button class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#equipmentModal">
                                        <i class="fas fa-tags"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Additional Services -->
                    <div class="row g-4 mb-5">
                        <div class="col-12">
                            <h3 class="fw-bold mb-4">🌟 Dịch vụ bổ sung</h3>
                        </div>
                        
                        <div class="col-md-4">
                            <!-- Ứng dụng di động -->
                            <div class="additional-service-card text-center p-4 bg-white rounded-4 border shadow-sm h-100">
                                <div class="service-icon-wrap mb-3">
                                    <div style="width:64px;height:64px;background:linear-gradient(135deg,#667eea,#764ba2);border-radius:18px;display:flex;align-items:center;justify-content:center;margin:0 auto;">
                                        <i class="fas fa-mobile-alt fa-2x text-white"></i>
                                    </div>
                                </div>
                                <h6 class="fw-bold mb-2">Ứng dụng di động</h6>
                                <p class="text-muted small mb-3">Đặt sân mọi lúc mọi nơi, nhận thông báo tức thì</p>
                                <div class="d-flex gap-2 justify-content-center">
                                    <a href="#" class="btn btn-dark btn-sm d-flex align-items-center gap-1" style="border-radius:8px;">
                                        <i class="fab fa-apple"></i> iOS
                                    </a>
                                    <a href="#" class="btn btn-success btn-sm d-flex align-items-center gap-1" style="border-radius:8px;">
                                        <i class="fab fa-google-play"></i> Android
                                    </a>
                                </div>
                                <div class="mt-2">
                                    <small class="text-muted"><i class="fas fa-star text-warning me-1"></i>4.8 · 10K+ lượt tải</small>
                                </div>
                            </div>
                        </div>

                        <!-- Hỗ trợ 24/7 -->
                        <div class="col-md-4">
                            <div class="additional-service-card text-center p-4 bg-white rounded-4 border shadow-sm h-100">
                                <div class="service-icon-wrap mb-3">
                                    <div style="width:64px;height:64px;background:linear-gradient(135deg,#28a745,#20c997);border-radius:18px;display:flex;align-items:center;justify-content:center;margin:0 auto;">
                                        <i class="fas fa-headset fa-2x text-white"></i>
                                    </div>
                                </div>
                                <h6 class="fw-bold mb-2">Hỗ trợ 24/7</h6>
                                <p class="text-muted small mb-3">Tư vấn và giải đáp mọi thắc mắc qua nhiều kênh</p>
                                <div class="d-grid gap-2">
                                    <a href="tel:0968073500" class="btn btn-success btn-sm" style="border-radius:8px;">
                                        <i class="fas fa-phone me-2"></i>Gọi: 0968.073.500
                                    </a>
                                    <button class="btn btn-outline-success btn-sm" style="border-radius:8px;"
                                            onclick="openLiveChat()">
                                        <i class="fas fa-comments me-2"></i>Chat trực tuyến
                                    </button>
                                    <a href="mailto:support@badmintonpro.vn" class="btn btn-outline-secondary btn-sm" style="border-radius:8px;">
                                        <i class="fas fa-envelope me-2"></i>Gửi email
                                    </a>
                                </div>
                                <div class="mt-2">
                                    <small class="text-muted"><i class="fas fa-clock text-success me-1"></i>Phản hồi trong vòng 5 phút</small>
                                </div>
                            </div>
                        </div>

                        <!-- Chương trình VIP -->
                        <div class="col-md-4">
                            <div class="additional-service-card text-center p-4 bg-white rounded-4 border shadow-sm h-100" style="border-color:#fbbf24 !important;background:linear-gradient(135deg,#fffbeb,#fff) !important;">
                                <div class="service-icon-wrap mb-3">
                                    <div style="width:64px;height:64px;background:linear-gradient(135deg,#f59e0b,#d97706);border-radius:18px;display:flex;align-items:center;justify-content:center;margin:0 auto;">
                                        <i class="fas fa-crown fa-2x text-white"></i>
                                    </div>
                                </div>
                                <h6 class="fw-bold mb-2">Gói hội viên VIP</h6>
                                <p class="text-muted small mb-3">Combo vé ưu đãi, tặng vé miễn phí, tiết kiệm đến 10%</p>
                                <ul class="list-unstyled text-start mb-3" style="font-size:.82rem;">
                                    <li class="mb-1"><i class="fas fa-check text-success me-2"></i>10 vé tặng 1 vé miễn phí</li>
                                    <li class="mb-1"><i class="fas fa-check text-success me-2"></i>Thời hạn 3 – 12 tháng</li>
                                    <li><i class="fas fa-check text-success me-2"></i>Giá chỉ từ 720.000đ</li>
                                </ul>
                                <a href="membership.php" class="btn btn-warning btn-sm w-100 fw-bold" style="border-radius:8px;">
                                    <i class="fas fa-id-card me-2"></i>Xem các gói ngay
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Sidebar -->
            <div class="col-lg-3">
                <div class="discover-sidebar bg-light h-100 p-4">
                    <!-- Quick Links -->
                    <div class="sidebar-section mb-4">
                        <h6 class="fw-bold mb-3">🚀 Liên kết nhanh</h6>
                        <div class="d-grid gap-2">
                            <a href="booking-online.php" class="btn btn-outline-info btn-sm text-start">
                                <i class="fas fa-search me-2"></i>Tìm sân ngay
                            </a>
                            <a href="map.php" class="btn btn-outline-info btn-sm text-start">
                                <i class="fas fa-map me-2"></i>Xem bản đồ
                            </a>
                            <a href="featured.php" class="btn btn-outline-info btn-sm text-start">
                                <i class="fas fa-star me-2"></i>Sân nổi bật
                            </a>
                            <a href="<?php echo isLoggedIn() ? 'profile.php' : 'register.php'; ?>" class="btn btn-outline-info btn-sm text-start">
                                <i class="fas fa-user me-2"></i>Tài khoản
                            </a>
                        </div>
                    </div>
                    
                    <!-- Contact Info -->
                    <div class="sidebar-section mb-4">
                        <h6 class="fw-bold mb-3">📞 Liên hệ</h6>
                        <div class="contact-info">
                            <div class="contact-item mb-2">
                                <i class="fas fa-phone text-primary me-2"></i>
                                <span>0968.073.500</span>
                            </div>
                            <div class="contact-item mb-2">
                                <i class="fas fa-envelope text-primary me-2"></i>
                                <span>hung@admin.vn </span>
                            </div>
                            <div class="contact-item mb-2">
                                <i class="fas fa-map-marker-alt text-primary me-2"></i>
                                <span>Hà Nội, Việt Nam</span>
                            </div>
                            <div class="contact-item">
                                <i class="fas fa-clock text-primary me-2"></i>
                                <span>6:00 - 22:00</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- FAQ -->
                    <div class="sidebar-section">
                        <h6 class="fw-bold mb-3">❓ Câu hỏi thường gặp</h6>
                        <div class="accordion accordion-flush" id="faqAccordion">
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                        Làm sao để đặt sân?
                                    </button>
                                </h2>
                                <div id="faq1" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body small">
                                        Bạn có thể đặt sân trực tuyến qua website hoặc app, chọn sân và khung giờ phù hợp.
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                        Có thể hủy đặt sân không?
                                    </button>
                                </h2>
                                <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body small">
                                        Có thể hủy miễn phí trước 2 giờ. Sau thời gian này sẽ tính phí hủy.
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                        Có cho thuê vợt không?
                                    </button>
                                </h2>
                                <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body small">
                                        Có, chúng tôi có dịch vụ cho thuê vợt và cầu với giá ưu đãi.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<!-- Modals -->
<!-- Booking Guide Modal -->
<div class="modal fade" id="bookingGuideModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Hướng dẫn đặt sân</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="step mb-3">
                    <h6><span class="badge bg-primary me-2">1</span>Chọn sân</h6>
                    <p class="small text-muted">Tìm kiếm và chọn sân phù hợp với vị trí và giá cả.</p>
                </div>
                <div class="step mb-3">
                    <h6><span class="badge bg-primary me-2">2</span>Chọn giờ</h6>
                    <p class="small text-muted">Chọn khung giờ trống phù hợp với lịch trình.</p>
                </div>
                <div class="step mb-3">
                    <h6><span class="badge bg-primary me-2">3</span>Thanh toán</h6>
                    <p class="small text-muted">Thanh toán online hoặc trả tiền mặt tại sân.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Discover page specific scripts -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Service card hover effects
    document.querySelectorAll('.service-card').forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
            this.style.boxShadow = '0 10px 30px rgba(0,0,0,0.1)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
            this.style.boxShadow = '';
        });
    });
    
    // Additional service card animations
    document.querySelectorAll('.additional-service-card').forEach(card => {
        card.addEventListener('mouseenter', function() {
            const icon = this.querySelector('i');
            icon.style.transform = 'scale(1.1) rotate(5deg)';
        });
        
        card.addEventListener('mouseleave', function() {
            const icon = this.querySelector('i');
            icon.style.transform = 'scale(1) rotate(0deg)';
        });
    });
});

// Live Chat function
function openLiveChat() {
    const modal = document.getElementById('liveChatModal');
    new bootstrap.Modal(modal).show();
    setTimeout(() => {
        document.getElementById('chatInput')?.focus();
    }, 400);
}
</script>

<!-- Live Chat Modal -->
<div class="modal fade" id="liveChatModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:400px;">
        <div class="modal-content border-0 shadow-lg" style="border-radius:20px;overflow:hidden;">
            <!-- Header -->
            <div style="background:linear-gradient(135deg,#28a745,#20c997);padding:1.2rem 1.5rem;display:flex;align-items:center;gap:.8rem;">
                <div style="width:44px;height:44px;background:rgba(255,255,255,.2);border-radius:50%;display:flex;align-items:center;justify-content:center;">
                    <i class="fas fa-headset text-white fa-lg"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="text-white fw-bold">Hỗ trợ BadmintonPro</div>
                    <div style="color:rgba(255,255,255,.8);font-size:.75rem;">
                        <span style="display:inline-block;width:8px;height:8px;background:#90ee90;border-radius:50%;margin-right:4px;"></span>
                        Đang trực tuyến · Phản hồi trong 5 phút
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <!-- Chat area -->
            <div id="chatMessages" style="height:280px;overflow-y:auto;padding:1rem;background:#f8fafc;display:flex;flex-direction:column;gap:.7rem;">
                <!-- Bot welcome -->
                <div style="display:flex;align-items:flex-end;gap:.5rem;">
                    <div style="width:32px;height:32px;background:#28a745;border-radius:50%;flex-shrink:0;display:flex;align-items:center;justify-content:center;">
                        <i class="fas fa-robot text-white" style="font-size:.7rem;"></i>
                    </div>
                    <div style="background:#fff;border-radius:14px 14px 14px 0;padding:.7rem 1rem;max-width:78%;box-shadow:0 2px 8px rgba(0,0,0,.08);font-size:.85rem;">
                        Xin chào! 👋 Tôi là trợ lý BadmintonPro. Tôi có thể giúp gì cho bạn?
                    </div>
                </div>
                <!-- Quick options -->
                <div style="display:flex;flex-wrap:wrap;gap:.5rem;margin-left:2.5rem;">
                    <button class="quick-chat-btn" onclick="sendQuick('Tôi muốn đặt sân')">🏸 Đặt sân</button>
                    <button class="quick-chat-btn" onclick="sendQuick('Hỏi về gói hội viên')">💎 Hội viên</button>
                    <button class="quick-chat-btn" onclick="sendQuick('Đăng ký khóa học')">📚 Khóa học</button>
                    <button class="quick-chat-btn" onclick="sendQuick('Hỏi về giá cả')">💰 Giá cả</button>
                </div>
            </div>

            <!-- Input -->
            <div style="padding:.8rem 1rem;border-top:1px solid #f0f0f0;display:flex;gap:.5rem;">
                <input type="text" id="chatInput"
                       placeholder="Nhập tin nhắn..."
                       style="flex:1;border:1.5px solid #e5e7eb;border-radius:50px;padding:.5rem 1rem;font-size:.85rem;outline:none;"
                       onkeydown="if(event.key==='Enter') sendChat()">
                <button onclick="sendChat()" style="width:40px;height:40px;background:linear-gradient(135deg,#28a745,#20c997);border:none;border-radius:50%;color:#fff;cursor:pointer;flex-shrink:0;">
                    <i class="fas fa-paper-plane" style="font-size:.85rem;"></i>
                </button>
            </div>

            <!-- Footer -->
            <div style="padding:.5rem 1rem;background:#f9fafb;text-align:center;font-size:.73rem;color:#9ca3af;border-top:1px solid #f0f0f0;">
                Hoặc gọi trực tiếp: <a href="tel:0123456789" style="color:#28a745;font-weight:700;">0968073500</a>
            </div>
        </div>
    </div>
</div>

<style>
.quick-chat-btn {
    background: #fff;
    border: 1.5px solid #e5e7eb;
    border-radius: 50px;
    padding: 4px 10px;
    font-size: .75rem;
    cursor: pointer;
    transition: all .15s;
    font-weight: 600;
    color: #374151;
}
.quick-chat-btn:hover { border-color: #28a745; color: #28a745; background: #f0fdf4; }
</style>

<script>
const botReplies = {
    'Tôi muốn đặt sân': 'Tuyệt! Bạn có thể đặt sân trực tiếp tại <a href="booking-online.php" style="color:#28a745;font-weight:700;">trang đặt sân</a>. Chọn sân → chọn giờ → thanh toán là xong! 🏸',
    'Hỏi về gói hội viên': 'Chúng tôi có nhiều gói hội viên từ 720.000đ/3 tháng. Xem chi tiết tại <a href="membership.php" style="color:#28a745;font-weight:700;">Gói hội viên</a>. Mua combo 10 vé tặng 1 vé! 💎',
    'Đăng ký khóa học': 'Các khóa học cầu lông từ cơ bản đến nâng cao. Xem và đăng ký tại <a href="training.php" style="color:#28a745;font-weight:700;">Đào tạo</a>. HLV chuyên nghiệp, lớp 4-6 người! 📚',
    'Hỏi về giá cả': 'Giá sân từ 80.000đ - 200.000đ/giờ tùy khu vực và giờ chơi. Giờ cao điểm (18-21h) tăng 20%. Xem thêm tại <a href="index.php" style="color:#28a745;font-weight:700;">Trang chủ</a>. 💰',
};

function sendQuick(msg) {
    addMsg(msg, 'user');
    setTimeout(() => {
        const reply = botReplies[msg] || 'Cảm ơn bạn! Nhân viên hỗ trợ sẽ liên hệ sớm. Hoặc gọi <strong>0123.456.789</strong> để được hỗ trợ ngay.';
        addMsg(reply, 'bot');
    }, 600);
}

function sendChat() {
    const input = document.getElementById('chatInput');
    const msg = input.value.trim();
    if (!msg) return;
    input.value = '';
    addMsg(msg, 'user');
    setTimeout(() => {
        addMsg('Cảm ơn câu hỏi của bạn! 😊 Nhân viên sẽ phản hồi trong vài phút. Trong lúc đó bạn có thể xem <a href="discover.php" style="color:#28a745;font-weight:700;">trang Khám phá</a> để biết thêm dịch vụ.', 'bot');
    }, 700);
}

function addMsg(text, from) {
    const container = document.getElementById('chatMessages');
    const div = document.createElement('div');
    div.style.cssText = `display:flex;align-items:flex-end;gap:.5rem;${from === 'user' ? 'flex-direction:row-reverse;' : ''}`;

    const icon = document.createElement('div');
    icon.style.cssText = `width:32px;height:32px;border-radius:50%;flex-shrink:0;display:flex;align-items:center;justify-content:center;background:${from === 'user' ? '#667eea' : '#28a745'};`;
    icon.innerHTML = `<i class="fas fa-${from === 'user' ? 'user' : 'robot'} text-white" style="font-size:.7rem;"></i>`;

    const bubble = document.createElement('div');
    bubble.style.cssText = `background:${from === 'user' ? 'linear-gradient(135deg,#667eea,#764ba2)' : '#fff'};color:${from === 'user' ? '#fff' : '#374151'};border-radius:${from === 'user' ? '14px 14px 0 14px' : '14px 14px 14px 0'};padding:.7rem 1rem;max-width:78%;box-shadow:0 2px 8px rgba(0,0,0,.08);font-size:.85rem;`;
    bubble.innerHTML = text;

    div.appendChild(icon);
    div.appendChild(bubble);
    container.appendChild(div);
    container.scrollTop = container.scrollHeight;
}
</script>