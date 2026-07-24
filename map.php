<?php
require_once __DIR__ . '/includes/functions.php';

// Chặn admin truy cập trang web thường
blockAdminFromPublic();

// Get filter parameters
$filters = [
    'location' => $_GET['location'] ?? '',
    'price' => $_GET['price'] ?? '',
    'min_price' => $_GET['min_price'] ?? '',
    'max_price' => $_GET['max_price'] ?? '',
    'radius' => $_GET['radius'] ?? '3'
];

// Params từ "Chỉ đường" trong featured.php
$target_court_id   = intval($_GET['court_id'] ?? 0);
$target_court_name = $_GET['name'] ?? '';
$target_lat        = floatval($_GET['lat'] ?? 0);
$target_lng        = floatval($_GET['lng'] ?? 0);

$locations = getLocations();
require_once __DIR__ . '/includes/header.php';
?>

<!-- Map Page Header -->
<section class="bg-primary text-white py-4">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="h3 mb-2">🗺️ Bản đồ sân cầu lông Hà Nội</h1>
                <p class="mb-0 opacity-75">Khám phá và tìm kiếm sân cầu lông gần bạn trên bản đồ tương tác</p>
            </div>
            <div class="col-md-4 text-md-end">
                <a href="index.php" class="btn btn-light">
                    <i class="fas fa-arrow-left me-2"></i>Về trang chủ
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Advanced Search Bar -->
<section class="bg-light py-3 border-bottom">
    <div class="container">
        <form class="row g-2 align-items-end" id="mapSearchForm">
            <div class="col-sm-6 col-md-3">
                <label class="form-label fw-bold">Phường / Xã</label>
                <input list="locationList" type="text" name="location" class="form-control" id="locationInput"
                       value="<?php echo escape($filters['location']); ?>" placeholder="Chọn khu vực...">
                <datalist id="locationList">
                    <?php foreach ($locations as $location): ?>
                        <option value="<?php echo escape($location); ?>"></option>
                    <?php endforeach; ?>
                </datalist>
            </div>
            <div class="col-sm-6 col-md-2">
                <label class="form-label fw-bold">Bán kính</label>
                <select name="radius" class="form-select" id="radiusSelect">
                    <option value="1" <?php echo $filters['radius'] === '1' ? 'selected' : ''; ?>>1km</option>
                    <option value="3" <?php echo $filters['radius'] === '3' ? 'selected' : ''; ?>>3km</option>
                    <option value="5" <?php echo $filters['radius'] === '5' ? 'selected' : ''; ?>>5km</option>
                    <option value="10" <?php echo $filters['radius'] === '10' ? 'selected' : ''; ?>>10km</option>
                </select>
            </div>
            <div class="col-sm-6 col-md-2">
                <label class="form-label fw-bold">Giá từ</label>
                <input type="number" name="min_price" class="form-control" id="minPriceInput"
                       value="<?php echo escape($filters['min_price']); ?>" placeholder="VND">
            </div>
            <div class="col-sm-6 col-md-2">
                <label class="form-label fw-bold">Giá đến</label>
                <input type="number" name="max_price" class="form-control" id="maxPriceInput"
                       value="<?php echo escape($filters['max_price']); ?>" placeholder="VND">
            </div>
            <div class="col-sm-6 col-md-2">
                <label class="form-label opacity-0">Tìm</label>
                <button type="submit" class="btn btn-primary w-100" id="searchBtn">
                    <i class="fas fa-search me-2"></i>Lọc
                </button>
            </div>
            <div class="col-sm-6 col-md-1">
                <label class="form-label opacity-0">Reset</label>
                <button type="button" class="btn btn-outline-secondary w-100" id="resetBtn" title="Xóa bộ lọc">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </form>
    </div>
</section>

<!-- Main Map Section -->
<section class="map-section py-0">
    <div class="container-fluid p-0">
        <div class="row g-0">
            <!-- Map Container -->
            <div class="col-lg-9">
                <div class="map-container-fullscreen position-relative">
                    <div id="map" class="w-100 h-100">
                        <!-- Map will be loaded here -->
                    </div>
                    
                    <!-- Map Controls -->
                    <div class="map-controls position-absolute top-0 end-0 m-3">
                        <div class="btn-group-vertical shadow-sm" role="group">
                            <button type="button" class="btn btn-light btn-sm" id="zoomIn" title="Phóng to">
                                <i class="fas fa-plus"></i>
                            </button>
                            <button type="button" class="btn btn-light btn-sm" id="zoomOut" title="Thu nhỏ">
                                <i class="fas fa-minus"></i>
                            </button>
                            <button type="button" class="btn btn-light btn-sm" id="centerMap" title="Về trung tâm">
                                <i class="fas fa-crosshairs"></i>
                            </button>
                            <button type="button" class="btn btn-light btn-sm" id="getCurrentLocation" title="Vị trí của tôi">
                                <i class="fas fa-location-arrow"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Map Legend -->
                    <div class="map-legend position-absolute bottom-0 start-0 m-3">
                        <div class="card shadow-sm">
                            <div class="card-body p-2">
                                <div class="d-flex flex-wrap gap-2">
                                    <div class="d-flex align-items-center">
                                        <div class="legend-marker bg-success me-1"></div>
                                        <small>Còn trống</small>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <div class="legend-marker bg-warning me-1"></div>
                                        <small>Ít slot</small>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <div class="legend-marker bg-danger me-1"></div>
                                        <small>Đã đầy</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Sidebar -->
            <div class="col-lg-3">
                <div class="map-sidebar-fullscreen h-100 bg-white border-start">
                    <!-- Quick Actions -->
                    <div class="p-3 border-bottom bg-light">
                        <h6 class="fw-bold mb-3">🎯 Tìm kiếm nhanh</h6>
                        <div class="d-grid gap-2">
                            <button type="button" class="btn btn-outline-primary btn-sm" id="findNearby">
                                <i class="fas fa-location-arrow me-2"></i>Sân gần tôi
                            </button>
                            <button type="button" class="btn btn-outline-success btn-sm" id="showAvailable">
                                <i class="fas fa-check-circle me-2"></i>Chỉ sân còn trống
                            </button>
                            <button type="button" class="btn btn-outline-info btn-sm" id="showAll">
                                <i class="fas fa-eye me-2"></i>Hiện tất cả
                            </button>
                        </div>
                    </div>
                    
                    <!-- Selected Court Info -->
                    <div class="p-3 border-bottom">
                        <h6 class="fw-bold mb-3">📍 Thông tin sân</h6>
                        <div id="selectedCourtInfo">
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-mouse-pointer fa-2x mb-2 opacity-50"></i>
                                <p class="mb-0">Nhấp vào marker trên bản đồ để xem thông tin sân</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Courts List -->
                    <div class="flex-grow-1 overflow-auto">
                        <div class="p-3">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="fw-bold mb-0">📋 Danh sách sân</h6>
                                <span class="badge bg-primary" id="courtsCount">0 sân</span>
                            </div>
                            <div id="courtsList">
                                <div class="text-center text-muted py-3">
                                    <div class="spinner-border spinner-border-sm mb-2" role="status"></div>
                                    <p class="mb-0">Đang tải danh sách...</p>
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

<!-- Map functionality script -->
<script src="assets/js/map-page.js"></script>

<?php if ($target_court_id && $target_court_name): ?>
<!-- Auto-highlight sân khi đến từ "Chỉ đường" -->
<script>
// Truyền thông tin sân mục tiêu vào window để map-page.js sử dụng
window.targetCourt = {
    id:   <?php echo $target_court_id; ?>,
    name: <?php echo json_encode($target_court_name); ?>,
    lat:  <?php echo $target_lat ?: 'null'; ?>,
    lng:  <?php echo $target_lng ?: 'null'; ?>
};

document.addEventListener('DOMContentLoaded', function() {
    const t = window.targetCourt;

    function doHighlight() {
        // 1. Zoom map đến sân nếu có tọa độ
        if (t.lat && t.lng && window.courtMapInstance?.map) {
            window.courtMapInstance.map.setView([t.lat, t.lng], 16);
            // Tìm marker và mở popup
            window.courtMapInstance.markers.forEach(m => {
                if (m.courtData?.id === t.id || m.courtData?.name === t.name) {
                    m.openPopup();
                }
            });
        }

        // 2. Highlight card trong sidebar
        document.querySelectorAll('[data-court-id]').forEach(card => {
            if (parseInt(card.dataset.courtId) === t.id) {
                card.scrollIntoView({ behavior: 'smooth', block: 'center' });
                card.style.outline = '3px solid #0d6efd';
                card.style.borderRadius = '12px';
                setTimeout(() => card.style.outline = '', 4000);
            }
        });

        // 3. Banner thông báo
        const banner = document.createElement('div');
        banner.id = 'direction-banner';
        banner.innerHTML = `
            <i class="fas fa-location-arrow me-2"></i>
            Đang chỉ đường đến: <strong>${t.name}</strong>
            <a href="https://www.google.com/maps/dir/?api=1&destination=${t.lat},${t.lng}"
               target="_blank"
               style="color:#fff;margin-left:.8rem;font-weight:700;text-decoration:underline;">
               Mở Google Maps <i class="fas fa-external-link-alt ms-1"></i>
            </a>
            <button onclick="document.getElementById('direction-banner').remove()"
                    style="background:transparent;border:none;color:#fff;margin-left:.8rem;font-size:1.1rem;cursor:pointer;">✕</button>
        `;
        Object.assign(banner.style, {
            position:    'fixed',
            top:         '72px',
            left:        '50%',
            transform:   'translateX(-50%)',
            background:  'linear-gradient(135deg,#0d6efd,#0dcaf0)',
            color:       '#fff',
            padding:     '.7rem 1.5rem',
            borderRadius:'50px',
            fontWeight:  '600',
            fontSize:    '.88rem',
            zIndex:      '9999',
            boxShadow:   '0 8px 25px rgba(13,110,253,.35)',
            display:     'flex',
            alignItems:  'center',
            gap:         '.3rem',
            whiteSpace:  'nowrap',
        });
        document.body.appendChild(banner);
        setTimeout(() => banner?.remove(), 8000);
    }

    // Chờ map load xong (map-page.js init async)
    let tries = 0;
    const wait = setInterval(() => {
        tries++;
        if (window.courtMapInstance || tries > 20) {
            clearInterval(wait);
            doHighlight();
        }
    }, 300);
});
</script>
<?php endif; ?>

<style>
.map-container-fullscreen {
    height: calc(100vh - 200px);
}

.map-sidebar-fullscreen {
    height: calc(100vh - 200px);
    overflow-y: auto;
}

.legend-marker {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    display: inline-block;
}

.map-controls .btn {
    border-radius: 0;
    border-color: rgba(0,0,0,0.125);
}

.map-controls .btn:first-child {
    border-top-left-radius: 0.375rem;
    border-top-right-radius: 0.375rem;
}

.map-controls .btn:last-child {
    border-bottom-left-radius: 0.375rem;
    border-bottom-right-radius: 0.375rem;
}
</style>