// Full-screen map functionality for dedicated map page
class FullScreenCourtMap {
    constructor() {
        this.map = null;
        this.markers = [];
        this.userLocation = null;
        this.courts = [];
        this.selectedCourt = null;
        this.filteredCourts = [];
        this.searchTimeout = null;
        
        // Hanoi center coordinates
        this.defaultCenter = [21.0285, 105.8542];
        this.defaultZoom = 12;
        
        this.init();
    }
    
    async init() {
        try {
            console.log('Initializing full screen map...');
            
            await this.loadLeafletMap();
            console.log('Map loaded');
            
            await this.loadCourtsData();
            console.log('Courts data loaded');
            
            this.setupEventListeners();
            console.log('Event listeners setup');
            
            this.showCourtsOnMap();
            console.log('Courts shown on map');
            
            this.updateCourtsList();
            console.log('Courts list updated');
            
            // Hide any remaining loading indicators
            const loadingElements = document.querySelectorAll('.spinner-border');
            loadingElements.forEach(el => {
                const parent = el.closest('.text-center');
                if (parent) parent.style.display = 'none';
            });
            
            console.log('Map initialization complete');
            
        } catch (error) {
            console.error('Error initializing map:', error);
            this.showMapError();
        }
    }
    
    async loadLeafletMap() {
        // Initialize Leaflet map with full height
        this.map = L.map('map', {
            zoomControl: false // We'll add custom controls
        }).setView(this.defaultCenter, this.defaultZoom);
        
        // Add OpenStreetMap tiles
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 19
        }).addTo(this.map);
        
        // Set map height
        this.setMapHeight();
        
        // Handle window resize
        window.addEventListener('resize', () => {
            this.setMapHeight();
            this.map.invalidateSize();
        });
    }
    
    setMapHeight() {
        const navbar = document.querySelector('.navbar');
        const header = document.querySelector('.bg-primary');
        const searchBar = document.querySelector('.bg-light');
        
        const navbarHeight = navbar ? navbar.offsetHeight : 0;
        const headerHeight = header ? header.offsetHeight : 0;
        const searchBarHeight = searchBar ? searchBar.offsetHeight : 0;
        
        const mapHeight = window.innerHeight - navbarHeight - headerHeight - searchBarHeight;
        document.getElementById('map').style.height = mapHeight + 'px';
        
        // Also set sidebar height
        const sidebar = document.querySelector('.map-sidebar-fullscreen');
        if (sidebar) {
            sidebar.style.height = mapHeight + 'px';
        }
    }
    
    async loadCourtsData() {
        const loadingElement = document.querySelector('.text-center');
        
        try {
            // Show loading
            if (loadingElement) {
                loadingElement.innerHTML = '<div class="spinner-border text-primary me-2"></div>Đang tải dữ liệu sân...';
            }
            
            const response = await fetch('api/courts.php');
            const data = await response.json();
            
            if (data.success && data.courts) {
                this.courts = data.courts;
                this.filteredCourts = [...this.courts];
                
                // Hide loading
                if (loadingElement) {
                    loadingElement.style.display = 'none';
                }
                
                console.log('Loaded courts:', this.courts.length);
            } else {
                throw new Error('API response invalid');
            }
        } catch (error) {
            console.error('Error loading courts data:', error);
            
            // Use demo data as fallback
            this.courts = this.getDemoCourts();
            this.filteredCourts = [...this.courts];
            
            // Hide loading and show demo data message
            if (loadingElement) {
                loadingElement.innerHTML = '<small class="text-muted">Đang sử dụng dữ liệu demo</small>';
                setTimeout(() => {
                    if (loadingElement) loadingElement.style.display = 'none';
                }, 2000);
            }
            
            console.log('Using demo courts:', this.courts.length);
        }
    }
    
    getDemoCourts() {
        const demoLocations = [
            {name: 'Sân Cầu Lông Hoàng Mai Premium', location: 'Hoàng Mai', lat: 20.9815, lng: 105.8468},
            {name: 'Sân Cầu Lông Thanh Xuân Center', location: 'Thanh Xuân', lat: 20.9955, lng: 105.8195},
            {name: 'Sân Cầu Lông Cầu Giấy Sport', location: 'Cầu Giấy', lat: 21.0335, lng: 105.7935},
            {name: 'Sân Cầu Lông Đống Đa Arena', location: 'Đống Đa', lat: 21.0167, lng: 105.8270},
            {name: 'Sân Cầu Lông Ba Đình Elite', location: 'Ba Đình', lat: 21.0333, lng: 105.8167},
            {name: 'Sân Cầu Lông Hai Bà Trưng', location: 'Hai Bà Trưng', lat: 21.0122, lng: 105.8580},
            {name: 'Sân Cầu Lông Long Biên Complex', location: 'Long Biên', lat: 21.0364, lng: 105.8938},
            {name: 'Sân Cầu Lông Tây Hồ Resort', location: 'Tây Hồ', lat: 21.0583, lng: 105.8200},
            {name: 'Sân Cầu Lông Hà Đông Modern', location: 'Hà Đông', lat: 20.9715, lng: 105.7829},
            {name: 'Sân Cầu Lông Nam Từ Liêm', location: 'Nam Từ Liêm', lat: 21.0378, lng: 105.7644}
        ];
        
        return demoLocations.map((loc, index) => ({
            id: index + 1,
            name: loc.name,
            location: loc.location,
            lat: loc.lat + (Math.random() - 0.5) * 0.01,
            lng: loc.lng + (Math.random() - 0.5) * 0.01,
            price_per_hour: 80000 + Math.floor(Math.random() * 70000),
            description: `Sân cầu lông chất lượng cao tại ${loc.location} với đầy đủ tiện nghi hiện đại`,
            cover_image: 'https://via.placeholder.com/300x200?text=Court+' + (index + 1),
            status: ['available', 'limited', 'full'][Math.floor(Math.random() * 3)],
            operating_hours: '06:00 - 22:00',
            facilities: ['Có mái che', 'Sân gỗ', 'Đèn LED', 'Điều hòa', 'Wifi miễn phí']
        }));
    }
    
    showCourtsOnMap() {
        // Save current map view
        const currentCenter = this.map.getCenter();
        const currentZoom = this.map.getZoom();
        
        this.clearMarkers();
        
        this.filteredCourts.forEach(court => {
            const marker = this.createCourtMarker(court);
            this.markers.push(marker);
        });
        
        // Only fit bounds if we have results and it's a significant change
        if (this.markers.length > 0 && this.markers.length < this.courts.length) {
            // Don't auto-fit if user is searching - keep current view
            console.log('Keeping current map view during search');
        } else if (this.markers.length > 0) {
            // Only fit bounds for initial load or show all
            const group = new L.featureGroup(this.markers);
            this.map.fitBounds(group.getBounds().pad(0.1));
        }
    }
    
    createCourtMarker(court) {
        const statusColors = {
            available: '#28a745',
            limited: '#ffc107', 
            full: '#dc3545'
        };
        
        const markerIcon = L.divIcon({
            className: 'custom-marker-fullscreen',
            html: `
                <div class="marker-pin-fullscreen" style="background-color: ${statusColors[court.status]}">
                    <i class="fas fa-badminton-shuttlecock"></i>
                </div>
            `,
            iconSize: [25, 35],
            iconAnchor: [12, 35]
        });
        
        const marker = L.marker([court.lat, court.lng], {icon: markerIcon})
            .addTo(this.map)
            .on('click', () => this.selectCourt(court));
        
        // Gắn courtData để map.php có thể tìm marker theo court.id
        marker.courtData = court;
            
        // Add detailed popup
        const popupContent = `
            <div class="court-popup-detailed">
                <div class="popup-header">
                    <h6 class="fw-bold mb-1">${court.name}</h6>
                    <span class="badge bg-${this.getStatusBadgeClass(court.status)} mb-2">${this.getStatusText(court.status)}</span>
                </div>
                <div class="popup-body">
                    <p class="mb-1"><i class="fas fa-map-marker-alt text-primary me-2"></i>${court.location}</p>
                    <p class="mb-1"><i class="fas fa-clock text-info me-2"></i>${court.operating_hours}</p>
                    <p class="mb-2"><i class="fas fa-money-bill text-success me-2"></i>${this.formatPrice(court.price_per_hour)}/giờ</p>
                    <div class="facilities mb-2">
                        ${court.facilities ? court.facilities.slice(0, 3).map(f => `<span class="badge bg-light text-dark me-1">${f}</span>`).join('') : ''}
                    </div>
                </div>
                <div class="popup-footer d-grid gap-1">
                    <a href="booking-online.php?court_id=${court.id}" class="btn btn-primary btn-sm">
                        <i class="fas fa-calendar-check me-1"></i>Đặt sân ngay
                    </a>
                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="fullScreenMap.centerOnCourt(${court.lat}, ${court.lng})">
                        <i class="fas fa-crosshairs me-1"></i>Định vị
                    </button>
                </div>
            </div>
        `;
        
        marker.bindPopup(popupContent, {
            maxWidth: 280,
            className: 'custom-popup'
        });
        
        return marker;
    }
    
    selectCourt(court) {
        this.selectedCourt = court;
        this.updateSelectedCourtInfo(court);
        this.highlightCourtInList(court.id);
        
        // Animate marker
        const marker = this.markers.find(m => 
            Math.abs(m.getLatLng().lat - court.lat) < 0.0001 && 
            Math.abs(m.getLatLng().lng - court.lng) < 0.0001
        );
        
        if (marker) {
            marker.openPopup();
            this.map.setView([court.lat, court.lng], Math.max(this.map.getZoom(), 15));
        }
    }
    
    updateSelectedCourtInfo(court) {
        const infoContainer = document.getElementById('selectedCourtInfo');
        
        infoContainer.innerHTML = `
            <div class="court-info-detailed">
                <div class="court-header mb-3">
                    <div class="court-name h6 text-primary mb-1">${court.name}</div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="court-status status-${court.status}">${this.getStatusText(court.status)}</span>
                        <span class="court-price text-success fw-bold">${this.formatPrice(court.price_per_hour)}/giờ</span>
                    </div>
                </div>
                
                <div class="court-details mb-3">
                    <div class="detail-item mb-2">
                        <i class="fas fa-map-marker-alt text-primary me-2"></i>
                        <span>${court.location}</span>
                    </div>
                    <div class="detail-item mb-2">
                        <i class="fas fa-clock text-info me-2"></i>
                        <span>${court.operating_hours}</span>
                    </div>
                    ${court.facilities ? `
                    <div class="detail-item mb-2">
                        <i class="fas fa-star text-warning me-2"></i>
                        <span>Tiện nghi: ${court.facilities.slice(0, 2).join(', ')}</span>
                    </div>
                    ` : ''}
                </div>
                
                <div class="court-actions d-grid gap-2">
                    <a href="booking-online.php?court_id=${court.id}" class="btn btn-primary btn-sm">
                        <i class="fas fa-calendar-check me-2"></i>Xem chi tiết & Đặt sân
                    </a>
                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="fullScreenMap.centerOnCourt(${court.lat}, ${court.lng})">
                        <i class="fas fa-crosshairs me-2"></i>Định vị trên bản đồ
                    </button>
                    <button type="button" class="btn btn-outline-info btn-sm" onclick="fullScreenMap.getDirections(${court.lat}, ${court.lng})">
                        <i class="fas fa-route me-2"></i>Chỉ đường
                    </button>
                </div>
            </div>
        `;
    }
    
    updateCourtsList() {
        const listContainer = document.getElementById('courtsList');
        const countBadge = document.getElementById('courtsCount');
        
        countBadge.textContent = `${this.filteredCourts.length} sân`;
        
        if (this.filteredCourts.length === 0) {
            listContainer.innerHTML = `
                <div class="text-center text-muted py-3">
                    <i class="fas fa-search fa-2x mb-2 opacity-50"></i>
                    <p class="mb-0">Không tìm thấy sân phù hợp</p>
                </div>
            `;
            return;
        }
        
        const courtsHTML = this.filteredCourts.map(court => `
            <div class="court-list-item mb-2 p-2 border rounded cursor-pointer" 
                 data-court-id="${court.id}" 
                 onclick="fullScreenMap.selectCourt(${JSON.stringify(court).replace(/"/g, '&quot;')})">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                        <div class="fw-bold text-truncate" style="max-width: 150px;">${court.name}</div>
                        <small class="text-muted">${court.location}</small>
                        <div class="mt-1">
                            <span class="badge bg-${this.getStatusBadgeClass(court.status)} me-1">${this.getStatusText(court.status)}</span>
                            <small class="text-success fw-bold">${this.formatPrice(court.price_per_hour)}/h</small>
                        </div>
                    </div>
                    <button class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>
        `).join('');
        
        listContainer.innerHTML = courtsHTML;
    }
    
    highlightCourtInList(courtId) {
        // Remove previous highlights
        document.querySelectorAll('.court-list-item').forEach(item => {
            item.classList.remove('border-primary', 'bg-light');
        });
        
        // Highlight selected court
        const selectedItem = document.querySelector(`[data-court-id="${courtId}"]`);
        if (selectedItem) {
            selectedItem.classList.add('border-primary', 'bg-light');
            selectedItem.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    }
    
    centerOnCourt(lat, lng) {
        this.map.setView([lat, lng], 16, {animate: true});
    }
    
    getDirections(lat, lng) {
        if (this.userLocation) {
            const url = `https://www.google.com/maps/dir/${this.userLocation[0]},${this.userLocation[1]}/${lat},${lng}`;
            window.open(url, '_blank');
        } else {
            const url = `https://www.google.com/maps/search/${lat},${lng}`;
            window.open(url, '_blank');
        }
    }
    
    setupEventListeners() {
        // Search form AJAX handling - prevent default and scrolling
        document.getElementById('mapSearchForm')?.addEventListener('submit', (e) => {
            e.preventDefault();
            e.stopPropagation();
            this.performSearch();
            return false;
        });
        
        // Reset button
        document.getElementById('resetBtn')?.addEventListener('click', (e) => {
            e.preventDefault();
            this.resetSearch();
        });
        
        // Real-time search on input change
        document.getElementById('locationInput')?.addEventListener('input', () => {
            this.debounceSearch();
        });
        
        document.getElementById('minPriceInput')?.addEventListener('input', () => {
            this.debounceSearch();
        });
        
        document.getElementById('maxPriceInput')?.addEventListener('input', () => {
            this.debounceSearch();
        });
        
        document.getElementById('radiusSelect')?.addEventListener('change', () => {
            this.performSearch();
        });
        
        // Quick action buttons
        document.getElementById('findNearby')?.addEventListener('click', (e) => {
            e.preventDefault();
            this.getCurrentLocation();
        });
        
        document.getElementById('showAvailable')?.addEventListener('click', (e) => {
            e.preventDefault();
            this.filterByStatus('available');
        });
        
        document.getElementById('showAll')?.addEventListener('click', (e) => {
            e.preventDefault();
            this.showAllCourts();
        });
        
        // Map controls
        document.getElementById('zoomIn')?.addEventListener('click', (e) => {
            e.preventDefault();
            this.map.zoomIn();
        });
        
        document.getElementById('zoomOut')?.addEventListener('click', (e) => {
            e.preventDefault();
            this.map.zoomOut();
        });
        
        document.getElementById('centerMap')?.addEventListener('click', (e) => {
            e.preventDefault();
            this.map.setView(this.defaultCenter, this.defaultZoom);
        });
        
        document.getElementById('getCurrentLocation')?.addEventListener('click', (e) => {
            e.preventDefault();
            this.getCurrentLocation();
        });
    }
    
    getCurrentLocation() {
        if (!navigator.geolocation) {
            this.showToast('Trình duyệt không hỗ trợ định vị', 'error');
            return;
        }
        
        const button = document.getElementById('getCurrentLocation');
        const findButton = document.getElementById('findNearby');
        
        [button, findButton].forEach(btn => {
            if (btn) {
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                btn.disabled = true;
            }
        });
        
        navigator.geolocation.getCurrentPosition(
            (position) => {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                
                this.userLocation = [lat, lng];
                this.map.setView([lat, lng], 15);
                
                // Add user location marker
                if (this.userMarker) {
                    this.map.removeLayer(this.userMarker);
                }
                
                this.userMarker = L.marker([lat, lng], {
                    icon: L.divIcon({
                        className: 'user-location-marker',
                        html: '<i class="fas fa-user-circle"></i>',
                        iconSize: [25, 25]
                    })
                }).addTo(this.map);
                
                this.showToast('Đã xác định vị trí của bạn', 'success');
                
                // Reset buttons
                [button, findButton].forEach(btn => {
                    if (btn) {
                        btn.innerHTML = btn.id === 'getCurrentLocation' ? 
                            '<i class="fas fa-location-arrow"></i>' : 
                            '<i class="fas fa-location-arrow me-2"></i>Sân gần tôi';
                        btn.disabled = false;
                    }
                });
            },
            (error) => {
                console.error('Geolocation error:', error);
                this.showToast('Không thể xác định vị trí', 'error');
                
                // Reset buttons
                [button, findButton].forEach(btn => {
                    if (btn) {
                        btn.innerHTML = btn.id === 'getCurrentLocation' ? 
                            '<i class="fas fa-location-arrow"></i>' : 
                            '<i class="fas fa-location-arrow me-2"></i>Sân gần tôi';
                        btn.disabled = false;
                    }
                });
            }
        );
    }
    
    filterByStatus(status) {
        this.filteredCourts = this.courts.filter(court => court.status === status);
        this.showCourtsOnMap();
        this.updateCourtsList();
        this.showToast(`Hiển thị ${this.filteredCourts.length} sân ${this.getStatusText(status).toLowerCase()}`, 'info');
    }
    
    showAllCourts() {
        this.filteredCourts = [...this.courts];
        this.showCourtsOnMap();
        this.updateCourtsList();
        this.showToast(`Hiển thị tất cả ${this.filteredCourts.length} sân`, 'info');
    }
    
    clearMarkers() {
        this.markers.forEach(marker => {
            this.map.removeLayer(marker);
        });
        this.markers = [];
    }
    
    formatPrice(price) {
        return new Intl.NumberFormat('vi-VN').format(price) + 'đ';
    }
    
    getStatusText(status) {
        const statusTexts = {
            available: 'Còn trống',
            limited: 'Ít slot',
            full: 'Đã đầy'
        };
        return statusTexts[status] || 'Không rõ';
    }
    
    getStatusBadgeClass(status) {
        const badgeClasses = {
            available: 'success',
            limited: 'warning',
            full: 'danger'
        };
        return badgeClasses[status] || 'secondary';
    }
    
    showMapError() {
        document.getElementById('map').innerHTML = `
            <div class="d-flex align-items-center justify-content-center h-100 text-danger">
                <div class="text-center">
                    <i class="fas fa-exclamation-triangle fa-3x mb-3"></i>
                    <h5>Không thể tải bản đồ</h5>
                    <p>Vui lòng thử lại sau hoặc kiểm tra kết nối internet</p>
                    <button class="btn btn-primary" onclick="location.reload()">Tải lại trang</button>
                </div>
            </div>
        `;
    }
    
    showToast(message, type = 'info') {
        // Use existing toast system if available
        if (typeof showToast === 'function') {
            showToast(message, type);
        } else {
            console.log(`${type.toUpperCase()}: ${message}`);
        }
    }
    
    // Search functionality
    performSearch() {
        const location = document.getElementById('locationInput')?.value || '';
        const minPrice = document.getElementById('minPriceInput')?.value || '';
        const maxPrice = document.getElementById('maxPriceInput')?.value || '';
        const radius = document.getElementById('radiusSelect')?.value || '3';
        
        console.log('Performing search with filters:', { location, minPrice, maxPrice, radius });
        
        // Save current scroll position
        const currentScrollY = window.scrollY;
        
        // Show loading
        const searchBtn = document.getElementById('searchBtn');
        if (searchBtn) {
            searchBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Đang tìm...';
            searchBtn.disabled = true;
        }
        
        // Filter courts based on criteria
        this.filteredCourts = this.courts.filter(court => {
            let matches = true;
            
            // Location filter
            if (location && !court.location.toLowerCase().includes(location.toLowerCase()) && 
                !court.name.toLowerCase().includes(location.toLowerCase())) {
                matches = false;
            }
            
            // Price filters
            if (minPrice && court.price_per_hour < parseInt(minPrice)) {
                matches = false;
            }
            
            if (maxPrice && court.price_per_hour > parseInt(maxPrice)) {
                matches = false;
            }
            
            return matches;
        });
        
        // Update map and list WITHOUT scrolling
        this.showCourtsOnMap();
        this.updateCourtsList();
        
        // Restore scroll position
        window.scrollTo(0, currentScrollY);
        
        // Reset button
        setTimeout(() => {
            if (searchBtn) {
                searchBtn.innerHTML = '<i class="fas fa-search me-2"></i>Lọc';
                searchBtn.disabled = false;
            }
            // Ensure scroll position is maintained
            window.scrollTo(0, currentScrollY);
        }, 100);
        
        // Show result message
        const resultCount = this.filteredCourts.length;
    }
    
    resetSearch() {
        // Save current scroll position
        const currentScrollY = window.scrollY;
        
        // Clear all inputs
        document.getElementById('locationInput').value = '';
        document.getElementById('minPriceInput').value = '';
        document.getElementById('maxPriceInput').value = '';
        document.getElementById('radiusSelect').value = '3';
        
        // Reset to show all courts
        this.filteredCourts = [...this.courts];
        this.showCourtsOnMap();
        this.updateCourtsList();
        
        // Restore scroll position
        window.scrollTo(0, currentScrollY);
        
        this.showToast('Đã xóa bộ lọc', 'info');
    }
    
    debounceSearch() {
        // Clear previous timeout
        if (this.searchTimeout) {
            clearTimeout(this.searchTimeout);
        }
        
        // Set new timeout for search
        this.searchTimeout = setTimeout(() => {
            this.performSearch();
        }, 500); // Wait 500ms after user stops typing
    }
}

// Initialize map when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, initializing map...');
    
    // Hide loading after 10 seconds as fallback
    setTimeout(() => {
        const loadingElements = document.querySelectorAll('.spinner-border');
        loadingElements.forEach(el => {
            const parent = el.closest('.text-center');
            if (parent && parent.textContent.includes('Đang tải')) {
                parent.innerHTML = '<small class="text-muted">Đã tải xong</small>';
                setTimeout(() => parent.style.display = 'none', 1000);
            }
        });
    }, 10000);
    
    // Load Leaflet CSS and JS
    const leafletCSS = document.createElement('link');
    leafletCSS.rel = 'stylesheet';
    leafletCSS.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
    document.head.appendChild(leafletCSS);
    
    const leafletJS = document.createElement('script');
    leafletJS.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
    leafletJS.onload = function() {
        console.log('Leaflet loaded, creating map instance...');
        // Initialize map after Leaflet is loaded
        window.fullScreenMap = new FullScreenCourtMap();
        window.courtMapInstance = window.fullScreenMap; // alias cho map.php dùng
    };
    leafletJS.onerror = function() {
        console.error('Failed to load Leaflet');
        // Hide loading and show error
        const loadingElements = document.querySelectorAll('.spinner-border');
        loadingElements.forEach(el => {
            const parent = el.closest('.text-center');
            if (parent) {
                parent.innerHTML = '<div class="text-danger"><i class="fas fa-exclamation-triangle"></i> Lỗi tải bản đồ</div>';
            }
        });
    };
    document.head.appendChild(leafletJS);
});