// Equipment JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Shopping cart state
    let cart = {
        items: [],
        total: 0
    };

    // Get product data from page (populated by PHP from database)
    const products = {};
    
    // Extract product data from DOM
    document.querySelectorAll('.add-to-cart').forEach(btn => {
        const productCard = btn.closest('.product-card');
        const productId = parseInt(btn.dataset.productId);
        
        if (productCard && productId) {
            const name = productCard.querySelector('.product-name')?.textContent?.trim();
            const priceElement = productCard.querySelector('.product-price .h6');
            const price = priceElement ? parseInt(priceElement.textContent.replace(/[^\d]/g, '')) : 0;
            const image = productCard.querySelector('img')?.src;
            const brandElement = productCard.querySelector('.product-brand .badge');
            const brand = brandElement ? brandElement.textContent.trim() : '';
            
            if (name && price) {
                products[productId] = {
                    id: productId,
                    name: name,
                    price: price,
                    image: image || 'https://via.placeholder.com/300x300?text=Product',
                    brand: brand
                };
            }
        }
    });

    // Add to cart functionality
    document.querySelectorAll('.add-to-cart').forEach(btn => {
        btn.addEventListener('click', function() {
            const productId = parseInt(this.dataset.productId);
            const product = products[productId];
            
            if (product) {
                addToCart(product);
                
                // Visual feedback
                this.innerHTML = '<i class="fas fa-check me-2"></i>Đã thêm';
                this.classList.add('btn-success');
                this.classList.remove('btn-danger');
                
                setTimeout(() => {
                    this.innerHTML = '<i class="fas fa-shopping-cart me-2"></i>Thêm vào giỏ';
                    this.classList.remove('btn-success');
                    this.classList.add('btn-danger');
                }, 1500);
            }
        });
    });

    // Add to cart function
    function addToCart(product) {
        const existingItem = cart.items.find(item => item.id === product.id);
        
        if (existingItem) {
            existingItem.quantity += 1;
        } else {
            cart.items.push({
                ...product,
                quantity: 1
            });
        }
        
        updateCartUI();
        showCartSidebar();
    }

    // Update cart UI
    function updateCartUI() {
        const cartCount = cart.items.reduce((sum, item) => sum + item.quantity, 0);
        const cartTotal = cart.items.reduce((sum, item) => sum + (item.price * item.quantity), 0);
        
        cart.total = cartTotal;
        
        // Update cart count
        document.getElementById('cartCount').textContent = cartCount;
        
        // Update cart total
        document.getElementById('cartTotal').textContent = cartTotal.toLocaleString() + 'đ';
        
        // Update cart items
        const cartItemsContainer = document.getElementById('cartItems');
        
        if (cart.items.length === 0) {
            cartItemsContainer.innerHTML = `
                <div class="empty-cart text-center py-4">
                    <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                    <p class="text-muted">Giỏ hàng trống</p>
                </div>
            `;
            document.getElementById('checkoutBtn').disabled = true;
        } else {
            cartItemsContainer.innerHTML = cart.items.map(item => `
                <div class="cart-item" data-product-id="${item.id}">
                    <div class="row g-2 align-items-center">
                        <div class="col-3">
                            <img src="${item.image}" class="cart-item-image" alt="${item.name}">
                        </div>
                        <div class="col-6">
                            <h6 class="cart-item-name">${item.name}</h6>
                            <p class="cart-item-price text-danger mb-0">${item.price.toLocaleString()}đ</p>
                        </div>
                        <div class="col-3">
                            <div class="quantity-controls">
                                <button class="btn btn-sm btn-outline-secondary decrease-qty" data-product-id="${item.id}">-</button>
                                <span class="quantity">${item.quantity}</span>
                                <button class="btn btn-sm btn-outline-secondary increase-qty" data-product-id="${item.id}">+</button>
                            </div>
                            <button class="btn btn-sm btn-outline-danger remove-item mt-1" data-product-id="${item.id}">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `).join('');
            
            document.getElementById('checkoutBtn').disabled = false;
            
            // Add event listeners for quantity controls
            addQuantityControlListeners();
        }
    }

    // Add quantity control listeners
    function addQuantityControlListeners() {
        document.querySelectorAll('.increase-qty').forEach(btn => {
            btn.addEventListener('click', function() {
                const productId = parseInt(this.dataset.productId);
                changeQuantity(productId, 1);
            });
        });
        
        document.querySelectorAll('.decrease-qty').forEach(btn => {
            btn.addEventListener('click', function() {
                const productId = parseInt(this.dataset.productId);
                changeQuantity(productId, -1);
            });
        });
        
        document.querySelectorAll('.remove-item').forEach(btn => {
            btn.addEventListener('click', function() {
                const productId = parseInt(this.dataset.productId);
                removeFromCart(productId);
            });
        });
    }

    // Change quantity
    function changeQuantity(productId, change) {
        const item = cart.items.find(item => item.id === productId);
        if (item) {
            item.quantity += change;
            if (item.quantity <= 0) {
                removeFromCart(productId);
            } else {
                updateCartUI();
            }
        }
    }

    // Remove from cart
    function removeFromCart(productId) {
        cart.items = cart.items.filter(item => item.id !== productId);
        updateCartUI();
    }

    // Cart sidebar controls
    document.getElementById('cartToggle').addEventListener('click', function() {
        showCartSidebar();
    });

    document.getElementById('closeCart').addEventListener('click', function() {
        hideCartSidebar();
    });

    document.getElementById('clearCart').addEventListener('click', function() {
        if (confirm('Bạn có chắc muốn xóa tất cả sản phẩm?')) {
            cart.items = [];
            updateCartUI();
        }
    });

    // Show/hide cart sidebar
    function showCartSidebar() {
        document.getElementById('cartSidebar').classList.add('show');
    }

    function hideCartSidebar() {
        document.getElementById('cartSidebar').classList.remove('show');
    }

    // Checkout
    document.getElementById('checkoutBtn').addEventListener('click', function() {
        if (cart.items.length === 0) return;
        
        // Save cart to localStorage for checkout page
        localStorage.setItem('cart', JSON.stringify(cart.items));
        
        // Redirect to checkout
        window.location.href = 'checkout.php';
    });

    // Product card animations
    document.querySelectorAll('.product-card').forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
            this.style.boxShadow = '0 10px 30px rgba(0,0,0,0.15)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
            this.style.boxShadow = '';
        });
    });

    // Product actions (wishlist, quick view)
    document.querySelectorAll('.product-actions button').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            
            const action = this.title;
            if (action === 'Yêu thích') {
                this.classList.toggle('active');
                this.innerHTML = this.classList.contains('active') ? 
                    '<i class="fas fa-heart text-danger"></i>' : 
                    '<i class="fas fa-heart"></i>';
            } else if (action === 'Xem nhanh') {
                // Show quick view modal
                showQuickView(this.closest('.product-card'));
            }
        });
    });

    // Quick view functionality
    function showQuickView(productCard) {
        const productName = productCard.querySelector('.product-name').textContent;
        const productPrice = productCard.querySelector('.product-price .h6').textContent;
        const productImage = productCard.querySelector('img').src;
        const productBrand = productCard.querySelector('.product-brand .badge').textContent;
        
        const modalContent = `
            <div class="modal fade" id="quickViewModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">${productName}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <img src="${productImage}" class="img-fluid rounded" alt="${productName}">
                                </div>
                                <div class="col-md-6">
                                    <div class="product-brand mb-2">
                                        <span class="badge bg-primary">${productBrand}</span>
                                    </div>
                                    <h4>${productName}</h4>
                                    <div class="product-price mb-3">
                                        <span class="h4 text-danger">${productPrice}</span>
                                    </div>
                                    <div class="product-description mb-3">
                                        <p>Sản phẩm chính hãng với chất lượng cao, phù hợp cho người chơi ở mọi trình độ.</p>
                                    </div>
                                    <div class="product-features mb-3">
                                        <h6>Đặc điểm:</h6>
                                        <ul>
                                            <li>Chính hãng 100%</li>
                                            <li>Bảo hành 12 tháng</li>
                                            <li>Miễn phí vận chuyển</li>
                                            <li>Đổi trả trong 7 ngày</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                            <button type="button" class="btn btn-danger" onclick="document.querySelector('[data-product-id]').click()">
                                <i class="fas fa-shopping-cart me-2"></i>Thêm vào giỏ
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Remove existing modal
        const existingModal = document.getElementById('quickViewModal');
        if (existingModal) {
            existingModal.remove();
        }
        
        // Add new modal
        document.body.insertAdjacentHTML('beforeend', modalContent);
        
        // Show modal
        const modal = new bootstrap.Modal(document.getElementById('quickViewModal'));
        modal.show();
    }

    // Category tab switching
    document.querySelectorAll('#categoryTabs button').forEach(tab => {
        tab.addEventListener('shown.bs.tab', function() {
            // Animate product cards when tab is shown
            const targetPane = document.querySelector(this.dataset.bsTarget);
            const productCards = targetPane.querySelectorAll('.product-card');
            
            productCards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    card.style.transition = 'all 0.3s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
    });

    // Initialize cart UI
    updateCartUI();
});

// Add custom CSS
const style = document.createElement('style');
style.textContent = `
    .product-card {
        transition: all 0.3s ease;
        cursor: pointer;
    }
    
    .product-image {
        position: relative;
        overflow: hidden;
    }
    
    .product-badge {
        position: absolute;
        top: 10px;
        left: 10px;
        z-index: 2;
    }
    
    .product-actions {
        position: absolute;
        top: 10px;
        right: 10px;
        display: flex;
        flex-direction: column;
        gap: 5px;
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    
    .product-card:hover .product-actions {
        opacity: 1;
    }
    
    .product-actions button {
        width: 35px;
        height: 35px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .product-actions button.active {
        background-color: #fff;
        border-color: #dc3545;
    }
    
    .cart-sidebar {
        position: fixed;
        top: 0;
        right: -400px;
        width: 400px;
        height: 100vh;
        background: white;
        box-shadow: -2px 0 10px rgba(0,0,0,0.1);
        z-index: 1050;
        transition: right 0.3s ease;
        display: flex;
        flex-direction: column;
    }
    
    .cart-sidebar.show {
        right: 0;
    }
    
    .cart-header {
        padding: 20px;
        border-bottom: 1px solid #e9ecef;
        display: flex;
        justify-content: between;
        align-items: center;
    }
    
    .cart-body {
        flex: 1;
        padding: 20px;
        overflow-y: auto;
    }
    
    .cart-footer {
        padding: 20px;
        border-top: 1px solid #e9ecef;
    }
    
    .cart-item {
        padding: 15px 0;
        border-bottom: 1px solid #f8f9fa;
    }
    
    .cart-item:last-child {
        border-bottom: none;
    }
    
    .cart-item-image {
        width: 60px;
        height: 60px;
        object-fit: cover;
        border-radius: 8px;
    }
    
    .cart-item-name {
        font-size: 0.9em;
        margin-bottom: 5px;
        line-height: 1.2;
    }
    
    .cart-item-price {
        font-size: 0.9em;
        font-weight: 600;
    }
    
    .quantity-controls {
        display: flex;
        align-items: center;
        gap: 5px;
        margin-bottom: 5px;
    }
    
    .quantity-controls button {
        width: 25px;
        height: 25px;
        padding: 0;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .quantity {
        min-width: 20px;
        text-align: center;
        font-weight: 600;
    }
    
    .cart-toggle {
        position: fixed;
        bottom: 20px;
        right: 20px;
        width: 60px;
        height: 60px;
        background: #dc3545;
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
        z-index: 1000;
        transition: all 0.3s ease;
    }
    
    .cart-toggle:hover {
        transform: scale(1.1);
        box-shadow: 0 6px 20px rgba(220, 53, 69, 0.4);
    }
    
    .cart-count {
        position: absolute;
        top: -5px;
        right: -5px;
        background: #ffc107;
        color: #000;
        border-radius: 50%;
        width: 24px;
        height: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.8em;
        font-weight: bold;
    }
    
    @media (max-width: 768px) {
        .cart-sidebar {
            width: 100%;
            right: -100%;
        }
        
        .product-actions {
            opacity: 1;
        }
    }
`;
document.head.appendChild(style);