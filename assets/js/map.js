// Map functionality for badminton court booking system
class CourtMap {
    constructor() {
        this.map = null;
        this.markers = [];
        this.userLocation = null;
        this.courts = [];
        this.selectedCourt = null;
        
        // Hanoi center coordinates
        this.defaultCenter = [21.0285, 105.8542];
        this.defaultZoom = 12;
        
        this.init();
    }
    
    async init() {
        try {
            await this.loadLeafletMap();
            await this.loadCourtsData();
            this.setupEventListeners();
            this.showCourtsOnMap();
        } catch (error) {
            console.error('Error initializing map:', error);
            this.showMapError();
        }
    }
    
    async loadLeafletMap() {
        // Initialize Leaflet map
        this.map = L.map('map').setView(this.defaultCenter, this.defaultZoom);
        
        // Add OpenStreetMap tiles
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 19
        }).addTo(this.map);
        
        // Add custom controls
        this.addCustomControls();
    }
    
    addCustomControls() {
        // Remove default zoom controls
        this.map.zoomControl.remove();
        
        // Add custom zoom controls
        const customZoomControl = L.control({position: 'topright'});
        customZoomControl.onAdd = () => {
            const div = L.DomUtil.create('div', 'leaflet-bar leaflet-control leaflet-control-custom');
            div.innerHTML = `
                <a class="leaflet-control-zoom-in" href="#" title="Zoom in" role="button" aria-label="Zoom in">+</a>
                <a class="leaflet-control-zoom-out" href="#" title="Zoom out" role="button" aria-label="Zoom out">−</a>
            `;
            return div;
        };
        customZoomControl.addTo(this.map);
    }
    
    async loadCourtsData() {
        try {
            const response = await fetch('api/courts.php');
            const data = await response.json();
            
            if (data.success) {
                this.courts = data.courts.map(court => ({
                    ...court,
                    // Add random coordinates for demo (in real app, these should come from database)
                    lat: 21.0285 + (Math.random() - 0.5) * 0.1,
                    lng: 105.8542 + (Math.random() - 0.5) * 0.1,
                    status: this.getCourtStatus(court)
                }));
            }
        } catch (error) {
            console.error('Error loading courts data:', error);
            // Fallback to demo data
            this.courts = this.getDemoCourts();
        }
    }
    
    getDemoCourts() {
        const demoLocations = [
            {name: 'Sân Cầu Lông Hoàng Mai', location: 'Hoàng Mai', lat: 20.9815, lng: 105.8468},
            {name: 'Sân Cầu Lông Thanh Xuân', location: 'Thanh Xuân', lat: 20.9955, lng: 105.8195},
            {name: 'Sân Cầu Lông Cầu Giấy', location: 'Cầu Giấy', lat: 21.0335, lng: 105.7935},
            {name: 'Sân Cầu Lông Đống Đa', location: 'Đống Đa', lat: 21.0167, lng: 105.8270},
            {name: 'Sân Cầu Lông Ba Đình', location: 'Ba Đình', lat: 21.0333, lng: 105.8167},
            {name: 'Sân Cầu Lông Hai Bà Trưng', location: 'Hai Bà Trưng', lat: 21.0122, lng: 105.8580},
            {name: 'Sân Cầu Lông Long Biên', location: 'Long Biên', lat: 21.0364, lng: 105.8938},
            {name: 'Sân Cầu Lông Tây Hồ', location: 'Tây Hồ', lat: 21.0583, lng: 105.8200}
        ];
        
        return demoLocations.map((loc, index) => ({
            id: index + 1,
            name: loc.name,
            location: loc.location,
            lat: loc.lat,
            lng: loc.lng,
            price_per_hour: 80000 + Math.floor(Math.random() * 70000),
            description: `Sân cầu lông chất lượng cao tại ${loc.location}`,
            cover_image: 'https://via.placeholder.com/300x200?text=Court+' + (index + 1),
            status: ['available', 'limited', 'full'][Math.floor(Math.random() * 3)]
        }));
    }
    
    getCourtStatus(court) {
        // In real app, this would check actual booking data
        const statuses = ['available', 'limited', 'full'];
        return statuses[Math.floor(Math.random() * statuses.length)];
    }
    
    showCourtsOnMap() {
        // Clear existing markers
        this.clearMarkers();
        
        this.courts.forEach(court => {
            const marker = this.createCourtMarker(court);
            this.markers.push(marker);
        });
    }
    
    createCourtMarker(court) {
        const statusColors = {
            available: '#28a745',
            limited: '#ffc107', 
            full: '#dc3545'
        };
        
        const markerIcon = L.divIcon({
            className: 'custom-marker',
            html: `
                <div class="marker-pin" style="background-color: ${statusColors[court.status]}">
                    <i class="fas fa-map-marker-alt"></i>
                </div>
                <div class="marker-label">${court.name}</div>
            `,
            iconSize: [30, 40],
            iconAnchor: [15, 40]
        });
        
        const marker = L.marker([court.lat, court.lng], {icon: markerIcon})
            .addTo(this.map)
            .on('click', () => this.selectCourt(court));
            
        // Add popup
        const popupContent = `
            <div class="court-popup">
                <h6 class="fw-bold mb-2">${court.name}</h6>
                <p class="mb-1"><strong>Khu vực:</strong> ${court.location}</p>
                <p class="mb-1"><strong>Giá:</strong> ${this.formatPrice(court.price_per_hour)}/giờ</p>
                <p class="mb-2"><strong>Trạng thái:</strong> <span class="badge bg-${this.getStatusBadgeClass(court.status)}">${this.getStatusText(court.status)}</span></p>
                <a href="booking-online.php?court_id=${court.id}" class="btn btn-primary btn-sm">
                    <i class="fas fa-calendar-check me-1"></i>Đặt sân ngay
                </a>
            </div>
        `;
        
        marker.bindPopup(popupContent);
        
        return marker;
    }
    
    selectCourt(court) {
        this.selectedCourt = court;
        this.updateSelectedCourtInfo(court);
        
        // Animate marker
        const marker = this.markers.find(m => 
            m.getLatLng().lat === court.lat && m.getLatLng().lng === court.lng
        );
        
        if (marker) {
            marker.getElement().classList.add('map-marker-bounce');
            setTimeout(() => {
                marker.getElement().classList.remove('map-marker-bounce');
            }, 1000);
        }
    }
    
    updateSelectedCourtInfo(court) {
        const infoContainer = document.getElementById('selectedCourtInfo');
        
        infoContainer.innerHTML = `
            <div class="court-info">
                <div class="court-name">${court.name}</div>
                <div class="court-details mb-2">
                    <div><i class="fas fa-map-marker-alt me-2"></i>${court.location}</div>
                    <div><i class="fas fa-clock me-2"></i>Mở cửa 6:00 - 22:00</div>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="court-price">${this.formatPrice(court.price_per_hour)}/giờ</div>
                    <span class="court-status status-${court.status}">${this.getStatusText(court.status)}</span>
                </div>
                <div class="d-grid gap-2">
                    <a href="booking-online.php?court_id=${court.id}" class="btn btn-primary btn-sm">
                        <i class="fas fa-calendar-check me-2"></i>Xem chi tiết & Đặt sân
                    </a>
                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="courtMap.centerOnCourt(${court.lat}, ${court.lng})">
                        <i class="fas fa-crosshairs me-2"></i>Định vị trên bản đồ
                    </button>
                </div>
            </div>
        `;
    }
    
    centerOnCourt(lat, lng) {
        this.map.setView([lat, lng], 16, {animate: true});
    }
    
    setupEventListeners() {
        // Get current location button
        document.getElementById('getCurrentLocation').addEventListener('click', () => {
            this.getCurrentLocation();
        });
        
        // Map filter button
        document.getElementById('applyMapFilter').addEventListener('click', () => {
            this.applyFilters();
        });
        
        // Custom zoom controls
        document.getElementById('zoomIn').addEventListener('click', () => {
            this.map.zoomIn();
        });
        
        document.getElementById('zoomOut').addEventListener('click', () => {
            this.map.zoomOut();
        });
        
        document.getElementById('centerMap').addEventListener('click', () => {
            this.map.setView(this.defaultCenter, this.defaultZoom);
        });
    }
    
    getCurrentLocation() {
        if (!navigator.geolocation) {
            this.showToast('Trình duyệt không hỗ trợ định vị', 'error');
            return;
        }
        
        const button = document.getElementById('getCurrentLocation');
        const originalText = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Đang định vị...';
        button.disabled = true;
        
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
                        iconSize: [20, 20]
                    })
                }).addTo(this.map);
                
                this.showToast('Đã xác định vị trí của bạn', 'success');
                button.innerHTML = originalText;
                button.disabled = false;
            },
            (error) => {
                console.error('Geolocation error:', error);
                this.showToast('Không thể xác định vị trí', 'error');
                button.innerHTML = originalText;
                button.disabled = false;
            }
        );
    }
    
    applyFilters() {
        const radius = parseFloat(document.getElementById('searchRadius').value);
        const minPrice = parseFloat(document.getElementById('mapMinPrice').value) || 0;
        const maxPrice = parseFloat(document.getElementById('mapMaxPrice').value) || Infinity;
        
        let filteredCourts = this.courts;
        
        // Filter by price
        filteredCourts = filteredCourts.filter(court => 
            court.price_per_hour >= minPrice && court.price_per_hour <= maxPrice
        );
        
        // Filter by distance if user location is available
        if (this.userLocation && radius) {
            filteredCourts = filteredCourts.filter(court => {
                const distance = this.calculateDistance(
                    this.userLocation[0], this.userLocation[1],
                    court.lat, court.lng
                );
                return distance <= radius;
            });
        }
        
        // Update map with filtered courts
        this.clearMarkers();
        filteredCourts.forEach(court => {
            const marker = this.createCourtMarker(court);
            this.markers.push(marker);
        });
    }
    
    calculateDistance(lat1, lng1, lat2, lng2) {
        const R = 6371; // Earth's radius in km
        const dLat = (lat2 - lat1) * Math.PI / 180;
        const dLng = (lng2 - lng1) * Math.PI / 180;
        const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                Math.sin(dLng/2) * Math.sin(dLng/2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
        return R * c;
    }
    
    clearMarkers() {
        this.markers.forEach(marker => {
            this.map.removeLayer(marker);
        });
        this.markers = [];
    }
    
    formatPrice(price) {
        return new Intl.NumberFormat('vi-VN', {
            style: 'currency',
            currency: 'VND'
        }).format(price);
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
}

// Initialize map when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Load Leaflet CSS and JS
    const leafletCSS = document.createElement('link');
    leafletCSS.rel = 'stylesheet';
    leafletCSS.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
    document.head.appendChild(leafletCSS);
    
    const leafletJS = document.createElement('script');
    leafletJS.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
    leafletJS.onload = function() {
        // Initialize map after Leaflet is loaded
        window.courtMap = new CourtMap();
    };
    document.head.appendChild(leafletJS);
});