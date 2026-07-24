// Homepage search functionality with AJAX
class HomepageSearch {
    constructor() {
        this.searchTimeout = null;
        this.isSearching = false;
        this.init();
    }
    
    init() {
        console.log('Initializing homepage search...');
        this.setupEventListeners();
    }
    
    setupEventListeners() {
        // Search form AJAX handling - prevent default and scrolling
        const searchForm = document.getElementById('searchForm');
        if (searchForm) {
            searchForm.addEventListener('submit', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.performSearch();
                return false;
            });
        }
        
        // Reset button
        const resetBtn = document.getElementById('resetBtn');
        if (resetBtn) {
            resetBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.resetSearch();
            });
        }
        
        // Real-time search on input change with debounce
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.addEventListener('input', () => {
                this.debounceSearch();
            });
        }
        
        const locationInput = document.getElementById('locationInput');
        if (locationInput) {
            locationInput.addEventListener('input', () => {
                this.debounceSearch();
            });
        }
        
        const minPriceInput = document.getElementById('minPriceInput');
        if (minPriceInput) {
            minPriceInput.addEventListener('input', () => {
                this.debounceSearch();
            });
        }
        
        const maxPriceInput = document.getElementById('maxPriceInput');
        if (maxPriceInput) {
            maxPriceInput.addEventListener('input', () => {
                this.debounceSearch();
            });
        }
        
        // Immediate search on select change
        const priceSelect = document.getElementById('priceSelect');
        if (priceSelect) {
            priceSelect.addEventListener('change', () => {
                this.performSearch();
            });
        }
        
        const sortSelect = document.getElementById('sortSelect');
        if (sortSelect) {
            sortSelect.addEventListener('change', () => {
                this.performSearch();
            });
        }
        
        console.log('Homepage search event listeners setup complete');
    }
    
    performSearch() {
        if (this.isSearching) {
            console.log('Search already in progress, skipping...');
            return;
        }
        
        // Save current scroll position
        const currentScrollY = window.scrollY;
        
        // Get form data
        const formData = this.getFormData();
        console.log('Performing search with data:', formData);
        
        // Show loading state
        this.setLoadingState(true);
        this.isSearching = true;
        
        // Create URL with search parameters
        const searchParams = new URLSearchParams();
        Object.keys(formData).forEach(key => {
            if (formData[key]) {
                searchParams.append(key, formData[key]);
            }
        });
        
        // Add AJAX flag
        searchParams.append('ajax', '1');
        
        // Perform AJAX request
        fetch('index.php?' + searchParams.toString(), {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/html'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.text();
        })
        .then(html => {
            // Update results
            const resultsContainer = document.getElementById('courtsResults');
            if (resultsContainer) {
                resultsContainer.innerHTML = html;

                // Scroll xuống phần kết quả
                setTimeout(() => {
                    resultsContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }, 100);
            }
        })
        .catch(error => {
            console.error('Search error:', error);
        })
        .finally(() => {
            this.setLoadingState(false);
            this.isSearching = false;
        });
    }
    
    resetSearch() {
        // Clear all form inputs
        const ids = ['searchInput','locationInput','priceSelect','minPriceInput','maxPriceInput','sortSelect'];
        ids.forEach(id => {
            const el = document.getElementById(id);
            if (el) el.value = '';
        });

        // Perform search
        this.performSearch();
    }
    
    getFormData() {
        return {
            q: document.getElementById('searchInput')?.value || '',
            location: document.getElementById('locationInput')?.value || '',
            price: document.getElementById('priceSelect')?.value || '',
            min_price: document.getElementById('minPriceInput')?.value || '',
            max_price: document.getElementById('maxPriceInput')?.value || '',
            sort: document.getElementById('sortSelect')?.value || ''
        };
    }
    
    setLoadingState(isLoading) {
        const searchBtn = document.getElementById('searchBtn');
        if (searchBtn) {
            if (isLoading) {
                searchBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Đang tìm...';
                searchBtn.disabled = true;
            } else {
                searchBtn.innerHTML = '<i class="fas fa-search me-2"></i>Tìm sân';
                searchBtn.disabled = false;
            }
        }
        
        // Add loading overlay to results
        const resultsContainer = document.getElementById('courtsResults');
        if (resultsContainer) {
            if (isLoading) {
                resultsContainer.style.opacity = '0.6';
                resultsContainer.style.pointerEvents = 'none';
            } else {
                resultsContainer.style.opacity = '1';
                resultsContainer.style.pointerEvents = 'auto';
            }
        }
    }
    
    debounceSearch() {
        // Clear previous timeout
        if (this.searchTimeout) {
            clearTimeout(this.searchTimeout);
        }
        
        // Set new timeout for search
        this.searchTimeout = setTimeout(() => {
            this.performSearch();
        }, 800); // Wait 800ms after user stops typing
    }
    
    showToast(message, type = 'info') {
        // Toast disabled
        console.log(`[Search] ${type}: ${message}`);
    }
    
    getBootstrapAlertClass(type) {
        const classMap = {
            success: 'success',
            error: 'danger',
            warning: 'warning',
            info: 'info'
        };
        return classMap[type] || 'info';
    }
    
    getToastIcon(type) {
        const iconMap = {
            success: 'check-circle',
            error: 'exclamation-triangle',
            warning: 'exclamation-circle',
            info: 'info-circle'
        };
        return iconMap[type] || 'info-circle';
    }
}

// Initialize homepage search when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, initializing homepage search...');
    
    // Check if required elements exist
    const searchForm = document.getElementById('searchForm');
    const courtsResults = document.getElementById('courtsResults');
    
    if (!searchForm) {
        console.error('Search form not found!');
        return;
    }
    
    if (!courtsResults) {
        console.error('Courts results container not found!');
        return;
    }
    
    console.log('Required elements found, creating HomepageSearch instance...');
    window.homepageSearch = new HomepageSearch();
    console.log('HomepageSearch initialized successfully');
});