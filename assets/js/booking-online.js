// Enhanced Booking Online JavaScript with Beautiful UI
document.addEventListener('DOMContentLoaded', function() {
    console.log('=== Enhanced Booking page loaded ===');
    
    // Booking state
    let bookingState = {
        selectedCourt: null,
        selectedDate: null,
        selectedTime: null,
        selectedEndTime: null,
        selectedDuration: 1,
        totalPrice: 0,
        availableSlots: []
    };

    // Step navigation
    const steps = document.querySelectorAll('.booking-step');
    const stepIndicators = document.querySelectorAll('.step-item');
    let currentStep = 1;

    // Court selection with enhanced animation
    document.querySelectorAll('.select-court-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            console.log('Court selected');
            const courtId = this.dataset.courtId;
            const courtName = this.dataset.courtName;
            const courtPrice = parseInt(this.dataset.courtPrice);

            // Add selection animation
            document.querySelectorAll('.court-booking-card').forEach(card => {
                card.classList.remove('selected-court');
            });
            this.closest('.court-booking-card').classList.add('selected-court');

            bookingState.selectedCourt = {
                id: courtId,
                name: courtName,
                price: courtPrice
            };

            // Update UI with animation
            document.getElementById('selectedCourtName').textContent = courtName;
            document.getElementById('selectedCourtPrice').textContent = `Giá: ${courtPrice.toLocaleString()}đ/giờ`;

            // Smooth transition to step 2
            setTimeout(() => {
                showStep(2);
                loadTimeSlots();
            }, 300);
        });
    });

    // Date selection with validation
    document.getElementById('bookingDate').addEventListener('change', function() {
        const selectedDate = new Date(this.value);
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        
        if (selectedDate < today) {
            alert('Không thể chọn ngày trong quá khứ');
            this.value = today.toISOString().split('T')[0];
            return;
        }
        
        bookingState.selectedDate = this.value;
        loadTimeSlots();
    });

    // Enhanced Time slots loader with real API
    function loadTimeSlots() {
        console.log('Loading enhanced time slots from API...');
        const timeSlotsGrid = document.getElementById('timeSlotsGrid');
        
        if (!timeSlotsGrid) {
            console.error('Time slots grid not found');
            return;
        }

        if (!bookingState.selectedCourt || !bookingState.selectedDate) {
            console.error('Missing court or date selection');
            return;
        }

        // Show loading animation
        timeSlotsGrid.innerHTML = `
            <div class="col-12 text-center py-5">
                <div class="loading-container">
                    <div class="spinner-border text-primary mb-3" role="status">
                        <span class="visually-hidden">Đang tải...</span>
                    </div>
                    <p class="text-muted">Đang kiểm tra lịch trống...</p>
                    <small class="text-muted">Kết nối với hệ thống đặt sân</small>
                </div>
            </div>
        `;

        // Call real API
        const apiUrl = `api/time-slots.php?court_id=${bookingState.selectedCourt.id}&date=${bookingState.selectedDate}`;
        
        fetch(apiUrl)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    renderTimeSlots(data.data);
                } else {
                    throw new Error(data.error || 'API returned error');
                }
            })
            .catch(error => {
                console.error('Error loading time slots:', error);
                timeSlotsGrid.innerHTML = `
                    <div class="col-12 text-center py-4">
                        <i class="fas fa-exclamation-circle fa-2x text-warning mb-2 d-block"></i>
                        <p class="text-muted">Không thể tải khung giờ. Vui lòng nhấn <strong>Tải lại</strong>.</p>
                    </div>`;
            });
    }

    // Render time slots from API data
    function renderTimeSlots(data) {
        const timeSlotsGrid = document.getElementById('timeSlotsGrid');
        const { slots, statistics, court, dateFormatted } = data;
        
        bookingState.availableSlots = slots.filter(slot => slot.available);
        
        let slotsHTML = '';
        
        // Add summary first
        const summaryHTML = `
            <div class="col-12 mb-3">
                <div class="slots-summary alert alert-info">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Có ${statistics.available} khung giờ trống</strong> trong ngày ${dateFormatted}
                            <div class="mt-1">
                                <small class="text-muted">
                                    Tỷ lệ trống: ${statistics.availabilityRate}% | 
                                    Đã đặt: ${statistics.booked} | 
                                    Đã qua: ${statistics.passed}
                                </small>
                            </div>
                        </div>
                        <div class="col-md-4 text-md-end">
                            <small class="text-muted">
                                <i class="fas fa-clock me-1"></i>Cập nhật: ${new Date().toLocaleTimeString('vi-VN')}
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Generate slot HTML
        slots.forEach(slot => {
            const isAvailable = slot.available;
            const statusClass = slot.statusClass;
            
            slotsHTML += `
                <div class="time-slot-enhanced ${slot.status}" 
                     data-time="${slot.time}" 
                     data-end-time="${slot.endTime}"
                     data-price="${slot.price}"
                     data-hour="${slot.hour}"
                     onclick="${isAvailable ? 'selectTimeSlot(this)' : ''}"
                     style="cursor: ${isAvailable ? 'pointer' : 'not-allowed'};">
                    
                    <div class="slot-header">
                        <div class="slot-time">${slot.time} - ${slot.endTime}</div>
                        ${slot.isPeakHour ? '<span class="peak-badge">Giờ cao điểm</span>' : ''}
                        ${slot.isDiscountHour ? '<span class="discount-badge">Giảm giá</span>' : ''}
                    </div>
                    
                    <div class="slot-price">
                        ${slot.price.toLocaleString()}đ
                        ${slot.price !== slot.basePrice ? 
                            `<div class="price-note">(${slot.priceMultiplier > 1 ? '+' : ''}${Math.round((slot.priceMultiplier - 1) * 100)}%)</div>` : 
                            ''
                        }
                    </div>
                    
                    <div class="slot-status">
                        <span class="badge bg-${statusClass}">${slot.statusText}</span>
                    </div>
                    
                    ${isAvailable ? '<div class="slot-hover-effect"><i class="fas fa-check"></i> Chọn</div>' : ''}
                </div>
            `;
        });

        timeSlotsGrid.innerHTML = summaryHTML + slotsHTML;
        
        // Add pricing info
        const pricingInfoHTML = `
            <div class="col-12 mt-3">
                <div class="pricing-info alert alert-light">
                    <h6 class="fw-bold mb-2">
                        <i class="fas fa-money-bill-wave text-success me-2"></i>
                        Thông tin giá
                    </h6>
                    <div class="row">
                        <div class="col-md-4">
                            <small><strong>Giá cơ bản:</strong> ${court.basePrice.toLocaleString()}đ/giờ</small>
                        </div>
                        <div class="col-md-4">
                            <small><strong>Giờ cao điểm:</strong> +20% (${data.pricing.peakHours})</small>
                        </div>
                        <div class="col-md-4">
                            <small><strong>Giờ sáng:</strong> -10% (${data.pricing.discountHours})</small>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        timeSlotsGrid.insertAdjacentHTML('beforeend', pricingInfoHTML);
        
        console.log(`API time slots loaded: ${statistics.available} available out of ${statistics.total}`);
    }

    // Enhanced time slot selection with multi-hour booking
    window.selectTimeSlot = function(element) {
        if (element.classList.contains('booked') || element.classList.contains('passed')) {
            return;
        }

        // Remove previous selection
        document.querySelectorAll('.time-slot-enhanced').forEach(slot => {
            slot.classList.remove('selected');
        });

        // Select this slot
        element.classList.add('selected');

        const selectedTime = element.dataset.time;
        const selectedEndTime = element.dataset.endTime;
        const selectedPrice = parseInt(element.dataset.price);
        const selectedHour = parseInt(element.dataset.hour);

        bookingState.selectedTime = selectedTime;
        bookingState.selectedEndTime = selectedEndTime;
        bookingState.totalPrice = selectedPrice;

        // Show duration selector
        showDurationSelector(selectedHour, selectedPrice);

        // Enable proceed button
        const proceedBtn = document.getElementById('proceedToPayment');
        if (proceedBtn) {
            proceedBtn.disabled = false;
            proceedBtn.classList.remove('btn-secondary');
            proceedBtn.classList.add('btn-primary');
        }

        console.log('Selected time:', selectedTime, 'Price:', selectedPrice);
    };

    // Duration selector for multi-hour booking
    function showDurationSelector(startHour, basePrice) {
        const existingSelector = document.querySelector('.duration-selector');
        if (existingSelector) {
            existingSelector.remove();
        }

        // Find how many consecutive hours are available
        let maxDuration = 1;
        for (let i = 1; i <= 4; i++) { // Max 4 hours
            const nextHour = startHour + i;
            if (nextHour > 21) break; // Court closes at 22:00
            
            const nextSlot = bookingState.availableSlots.find(slot => slot.hour === nextHour);
            if (!nextSlot) break;
            
            maxDuration = i + 1;
        }

        if (maxDuration > 1) {
            const durationHTML = `
                <div class="duration-selector mt-3 p-3 bg-light rounded">
                    <h6 class="fw-bold mb-3">
                        <i class="fas fa-clock text-primary me-2"></i>
                        Chọn thời lượng (tối đa ${maxDuration} giờ)
                    </h6>
                    <div class="duration-options">
                        ${Array.from({length: maxDuration}, (_, i) => {
                            const duration = i + 1;
                            const totalPrice = calculateTotalPrice(startHour, duration);
                            return `
                                <div class="duration-option ${duration === 1 ? 'selected' : ''}" 
                                     data-duration="${duration}" 
                                     onclick="selectDuration(${duration}, ${startHour})">
                                    <div class="duration-time">${duration} giờ</div>
                                    <div class="duration-price">${totalPrice.toLocaleString()}đ</div>
                                    ${duration > 1 ? `<div class="duration-save">Tiết kiệm ${Math.round((basePrice * duration - totalPrice) / 1000)}k</div>` : ''}
                                </div>
                            `;
                        }).join('')}
                    </div>
                </div>
            `;
            
            document.querySelector('.time-slots-container').insertAdjacentHTML('beforeend', durationHTML);
        }
    }

    // Calculate total price with discounts for longer bookings
    function calculateTotalPrice(startHour, duration) {
        let total = 0;
        for (let i = 0; i < duration; i++) {
            const hour = startHour + i;
            const slot = bookingState.availableSlots.find(s => s.hour === hour);
            if (slot) {
                total += slot.price;
            }
        }
        
        // Apply discount for longer bookings
        if (duration >= 3) {
            total = Math.round(total * 0.95); // 5% discount for 3+ hours
        }
        if (duration >= 4) {
            total = Math.round(total * 0.9); // 10% discount for 4+ hours
        }
        
        return total;
    }

    // Duration selection
    window.selectDuration = function(duration, startHour) {
        document.querySelectorAll('.duration-option').forEach(opt => {
            opt.classList.remove('selected');
        });
        event.target.closest('.duration-option').classList.add('selected');

        bookingState.selectedDuration = duration;
        bookingState.selectedEndTime = `${(startHour + duration).toString().padStart(2, '0')}:00`;
        bookingState.totalPrice = calculateTotalPrice(startHour, duration);

        console.log(`Selected duration: ${duration} hours, Total: ${bookingState.totalPrice}`);
    };

    // Step navigation functions with animations
    function showStep(stepNumber) {
        console.log('Showing step:', stepNumber);
        
        // Add fade out animation
        const currentStepEl = document.querySelector('.booking-step:not(.d-none)');
        if (currentStepEl) {
            currentStepEl.style.opacity = '0';
            currentStepEl.style.transform = 'translateX(-20px)';
        }
        
        setTimeout(() => {
            // Hide all steps
            steps.forEach(step => {
                step.classList.add('d-none');
                step.style.opacity = '';
                step.style.transform = '';
            });
            
            // Show target step with animation
            const targetStep = document.getElementById(`step${stepNumber}`);
            if (targetStep) {
                targetStep.classList.remove('d-none');
                targetStep.style.opacity = '0';
                targetStep.style.transform = 'translateX(20px)';
                
                setTimeout(() => {
                    targetStep.style.transition = 'all 0.3s ease';
                    targetStep.style.opacity = '1';
                    targetStep.style.transform = 'translateX(0)';
                }, 50);
            }
            
            // Update step indicators with animation
            stepIndicators.forEach((indicator, index) => {
                indicator.classList.remove('active', 'completed');
                if (index + 1 < stepNumber) {
                    indicator.classList.add('completed');
                } else if (index + 1 === stepNumber) {
                    indicator.classList.add('active');
                }
            });
            
            currentStep = stepNumber;

            // Set default date when entering step 2
            if (stepNumber === 2) {
                const dateInput = document.getElementById('bookingDate');
                if (dateInput && !bookingState.selectedDate) {
                    const today = new Date();
                    const dateStr = today.toISOString().split('T')[0];
                    dateInput.value = dateStr;
                    bookingState.selectedDate = dateStr;
                }
            }

            // Update summary if on step 3
            if (stepNumber === 3) {
                updateBookingSummary();
            }
        }, 150);
    }

    // Enhanced booking summary
    function updateBookingSummary() {
        if (bookingState.selectedCourt && bookingState.selectedDate && bookingState.selectedTime) {
            document.getElementById('summaryCourtName').textContent = bookingState.selectedCourt.name;
            document.getElementById('summaryDate').textContent = new Date(bookingState.selectedDate).toLocaleDateString('vi-VN');
            document.getElementById('summaryTime').textContent = `${bookingState.selectedTime} - ${bookingState.selectedEndTime}`;
            document.getElementById('summaryDuration').textContent = `${bookingState.selectedDuration} giờ`;
            document.getElementById('summaryPricePerHour').textContent = `${Math.round(bookingState.totalPrice / bookingState.selectedDuration).toLocaleString()}đ (trung bình)`;
            document.getElementById('summaryTotal').textContent = `${bookingState.totalPrice.toLocaleString()}đ`;
        }
    }

    // Navigation buttons
    document.getElementById('changeCourtBtn').addEventListener('click', () => showStep(1));
    document.getElementById('backToStep1').addEventListener('click', () => showStep(1));
    document.getElementById('proceedToPayment').addEventListener('click', () => showStep(3));
    document.getElementById('backToStep2').addEventListener('click', () => showStep(2));

    // Enhanced payment method selection
    document.querySelectorAll('.payment-option').forEach(option => {
        option.addEventListener('click', function() {
            const method = this.dataset.method;
            const radio = this.querySelector('input[type="radio"]');
            
            // Remove previous selection
            document.querySelectorAll('.payment-option').forEach(opt => {
                opt.classList.remove('selected');
            });
            
            // Select this option
            this.classList.add('selected');
            radio.checked = true;
            
            // Add has-selection class to container
            document.querySelector('.payment-methods').classList.add('has-selection');
            
            // Add selection animation
            this.style.animation = 'none';
            setTimeout(() => {
                this.style.animation = 'pulse 0.6s ease';
            }, 10);
            
            console.log('Payment method selected:', method);
        });
    });

    // Initialize payment method selection
    const defaultPayment = document.querySelector('input[name="paymentMethod"]:checked');
    if (defaultPayment) {
        const defaultOption = defaultPayment.closest('.payment-option');
        defaultOption.classList.add('selected');
        document.querySelector('.payment-methods').classList.add('has-selection');
    }

    // Enhanced confirm booking with validation
    document.getElementById('confirmBooking').addEventListener('click', function() {
        const paymentMethod = document.querySelector('input[name="paymentMethod"]:checked')?.value;
        
        if (!paymentMethod) {
            showAlert('Vui lòng chọn phương thức thanh toán', 'warning');
            return;
        }

        // Validate booking data
        if (!bookingState.selectedCourt || !bookingState.selectedDate || !bookingState.selectedTime) {
            showAlert('Vui lòng chọn đầy đủ thông tin đặt sân', 'warning');
            return;
        }

        // Nếu đang ở trạng thái chờ thanh toán chuyển khoản → không cho nhấn lại
        if (window._pendingBookingId && (paymentMethod === 'momo' || paymentMethod === 'vnpay')) {
            showAlert('Đơn đã tạo. Vui lòng chuyển khoản và chờ hệ thống xác nhận tự động.', 'info');
            return;
        }

        // Tiền mặt → submit thẳng
        if (paymentMethod === 'cash') {
            submitBooking(paymentMethod, this);
            return;
        }

        // MoMo / MB Bank → tạo booking pending rồi auto-polling
        submitPendingBooking(paymentMethod, this);
    });

    // Show the payment confirmation modal with correct info
    function showPaymentConfirmModal(paymentMethod) {
        const modal = document.getElementById('paymentConfirmModal');
        if (!modal) {
            // Fallback: just submit if modal not found
            const paymentMethod2 = document.querySelector('input[name="paymentMethod"]:checked')?.value;
            submitBooking(paymentMethod2, document.getElementById('confirmBooking'));
            return;
        }

        // Generate a booking reference for display
        const bookingRef = 'BK-' + Date.now().toString(36).toUpperCase().slice(-6);

        // Reset checkbox and button state
        const cb    = document.getElementById('confirmTransferCheck');
        const pmBtn = document.getElementById('pmConfirmBtn');
        if (cb)    { cb.checked = false; }
        if (pmBtn) { pmBtn.disabled = true; pmBtn.style.opacity = '0.5'; }

        const momoPanel = document.getElementById('pmMomoPanel');
        const bankPanel = document.getElementById('pmBankPanel');
        const icon      = document.getElementById('pmModalIcon');
        const title     = document.getElementById('pmModalTitle');
        const header    = document.getElementById('paymentConfirmModalHeader');

        if (paymentMethod === 'momo') {
            if (momoPanel) momoPanel.style.display = 'block';
            if (bankPanel) bankPanel.style.display = 'none';
            if (icon)   icon.className = 'fas fa-wallet me-2';
            if (title)  title.textContent = 'Thanh toán qua MoMo';
            if (header) header.style.background = '#fdf2f8';
            const momoRef = document.getElementById('pmMomoRef');
            if (momoRef) momoRef.textContent = bookingRef;
        } else {
            if (momoPanel) momoPanel.style.display = 'none';
            if (bankPanel) bankPanel.style.display = 'block';
            if (icon)   icon.className = 'fas fa-university me-2';
            if (title)  title.textContent = 'Chuyển khoản ngân hàng (VNPay)';
            if (header) header.style.background = '#fffdf0';
            const bankRef = document.getElementById('pmBankRef');
            if (bankRef) bankRef.textContent = bookingRef;
        }

        // Store paymentMethod for use after confirmation
        window._pendingPaymentMethod = paymentMethod;

        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
    }

    // Called when user confirms payment in modal
    window.proceedBookingAfterPayment = function() {
        // Hide the modal
        const modal = document.getElementById('paymentConfirmModal');
        if (modal) {
            const bsModal = bootstrap.Modal.getInstance(modal);
            if (bsModal) bsModal.hide();
        }
        const pm  = window._pendingPaymentMethod || 'momo';
        const btn = document.getElementById('confirmBooking');
        submitBooking(pm, btn);
    };

    // ── Tạo booking pending (chuyển khoản) ────────────────────────────────
    // -- Tao booking pending va auto-polling ---------------------------------
    function submitPendingBooking(paymentMethod, btn) {
        // Reset flag khi bắt đầu đặt mới
        window._paymentDone = false;
        if (btn) {
            btn.innerHTML = '<div class="spinner-border spinner-border-sm me-2"></div>Dang tao don...';
            btn.disabled = true;
        }
        const bookingData = {
            court_id:       bookingState.selectedCourt.id,
            booking_date:   bookingState.selectedDate,
            start_time:     bookingState.selectedTime,
            duration:       bookingState.selectedDuration,
            payment_method: paymentMethod,
            notes:          document.getElementById('bookingNotes')?.value || ''
        };
        fetch('book.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
            body: new URLSearchParams(bookingData)
        })
        .then(r => r.json())
        .then(data => {
            if (!data.success) throw new Error(data.error || 'Loi tao don');
            window._pendingBookingId   = data.booking_id;
            window._pendingTransferRef = data.transfer_ref;
            window._pendingAmount      = data.total_price;
            const ref = data.transfer_ref, amount = data.total_price, enc = encodeURIComponent(ref);
            if (paymentMethod === 'momo') {
                const qr = document.getElementById('bookingMomoQr');
                if (qr) qr.src = `https://img.vietqr.io/image/MOMO-0968073500-qr_only.png?amount=${amount}&addInfo=${enc}&accountName=LU+DANG+HUNG`;
                const re = document.getElementById('bookingMomoRef');   if (re) re.textContent = ref;
                const ae = document.getElementById('bookingMomoAmount'); if (ae) ae.textContent = amount.toLocaleString('vi-VN') + 'd';
            } else {
                const qr = document.getElementById('bookingBankQr');
                if (qr) qr.src = `https://img.vietqr.io/image/MB-7369786789-qr_only.png?amount=${amount}&addInfo=${enc}&accountName=LU+DANG+HUNG`;
                const re = document.getElementById('bookingBankRef');   if (re) re.textContent = ref;
                const ae = document.getElementById('bookingBankAmount'); if (ae) ae.textContent = amount.toLocaleString('vi-VN') + 'd';
            }
            if (btn) {
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" style="width:14px;height:14px;border-width:2px;"></span>Dang cho thanh toan...';
                btn.disabled  = true;
                btn.classList.remove('btn-success');
                btn.classList.add('btn-primary');
            }
            const noteBox = document.getElementById('transferWaitingNote');
            if (noteBox) {
                noteBox.innerHTML = `
                <div style="background:#f0fdf4;border:1.5px solid #86efac;border-radius:12px;padding:1rem 1.2rem;margin-top:1rem;">
                    <div style="font-weight:700;color:#166534;margin-bottom:.4rem;">
                        <i class="fas fa-circle-notch fa-spin me-1"></i>Đang chờ thanh toán tự động
                    </div>
                    <div style="font-size:.84rem;color:#15803d;margin-bottom:.35rem;">
                        Chuyển khoản nội dung: <strong style="font-family:monospace;background:#dcfce7;padding:2px 7px;border-radius:4px;">${ref}</strong>
                        <button onclick="navigator.clipboard.writeText('${ref}').then(()=>{this.textContent='✓';setTimeout(()=>this.textContent='Copy',1500)})" 
                                style="background:#16a34a;color:#fff;border:none;border-radius:5px;padding:1px 7px;font-size:.72rem;cursor:pointer;margin-left:4px;">Copy</button>
                    </div>
                    <div style="font-size:.78rem;color:#166534;margin-bottom:.6rem;">
                        <i class="fas fa-magic me-1"></i>Hệ thống <strong>tự động xác nhận</strong> khi nhận được tiền.
                    </div>
                    <div style="height:4px;background:#dcfce7;border-radius:4px;overflow:hidden;margin-bottom:.8rem;">
                        <div id="pollingProgressBar" style="height:100%;background:#16a34a;width:0%;transition:width 5s linear;"></div>
                    </div>
                    <div style="display:flex;gap:.5rem;align-items:center;justify-content:space-between;font-size:.72rem;color:#15803d;margin-bottom:.8rem;">
                        <span id="pollingAttemptText">Đang kiểm tra...</span>
                        <span id="pollingCountdown">Còn 6:00</span>
                    </div>
                    <button id="btnManualCheckBooking" onclick="window._manualCheckBooking()" 
                            style="width:100%;background:#fff;border:1.5px solid #16a34a;border-radius:9px;padding:.5rem;font-size:.83rem;font-weight:700;color:#16a34a;cursor:pointer;">
                        <i class="fas fa-sync-alt me-1"></i> Kiểm tra ngay (nếu đã chuyển khoản)
                    </button>
                </div>`;
                noteBox.style.display = 'block';
                setTimeout(() => { const b = document.getElementById('pollingProgressBar'); if (b) b.style.width = '100%'; }, 100);

                // Countdown timer
                let _cRemaining = 360;
                window._countdownTimer = setInterval(() => {
                    _cRemaining--;
                    const el = document.getElementById('pollingCountdown');
                    if (el) { const m=Math.floor(_cRemaining/60),s=_cRemaining%60; el.textContent=`Còn ${m}:${String(s).padStart(2,'0')}`; }
                    if (_cRemaining<=0) clearInterval(window._countdownTimer);
                }, 1000);
            }

            // Manual check function
            window._manualCheckBooking = function() {
                const bookingId = window._pendingBookingId;
                if (!bookingId) return;
                const btn = document.getElementById('btnManualCheckBooking');
                if (btn) { btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Đang kiểm tra...'; btn.disabled = true; }
                fetch(`api/check-payment-status.php?booking_id=${bookingId}`)
                    .then(r => r.json())
                    .then(d => {
                        if (d.paid) {
                            checkPaymentStatus(bookingId); // trigger success flow
                        } else {
                            if (btn) { btn.innerHTML = '<i class="fas fa-sync-alt me-1"></i> Kiểm tra ngay'; btn.disabled = false; }
                            const at = document.getElementById('pollingAttemptText');
                            if (at) { at.textContent = '⚠️ Chưa nhận được tiền. Kiểm tra lại nội dung CK.'; setTimeout(()=>{ if(at) at.textContent='Đang kiểm tra...'; }, 3000); }
                        }
                    })
                    .catch(() => { if (btn) { btn.innerHTML = '<i class="fas fa-sync-alt me-1"></i> Kiểm tra ngay'; btn.disabled = false; } });
            };

            startAutoPolling(data.booking_id, btn);
        })
        .catch(err => {
            console.error('Pending booking error:', err);
            showAlert('Loi tao don: ' + err.message, 'error');
            if (btn) { btn.innerHTML = '<i class="fas fa-credit-card me-2"></i>Thanh toan'; btn.disabled = false; }
        });
    }

    let _pollingTimer = null;

    function startAutoPolling(bookingId, btn) {
        if (_pollingTimer) clearInterval(_pollingTimer);
        let attempts = 0, maxAttempts = 72;
        _pollingTimer = setInterval(() => {
            attempts++;
            const bar = document.getElementById('pollingProgressBar');
            if (bar) { bar.style.transition = 'none'; bar.style.width = '0%'; setTimeout(() => { bar.style.transition = 'width 5s linear'; bar.style.width = '100%'; }, 50); }
            const at = document.getElementById('pollingAttemptText');
            if (at) at.textContent = `Lần kiểm tra ${attempts}/${maxAttempts}`;
            checkPaymentStatus(bookingId);
            if (attempts >= maxAttempts) {
                clearInterval(_pollingTimer); _pollingTimer = null;
                if (window._countdownTimer) clearInterval(window._countdownTimer);
                if (btn) { btn.innerHTML = '<i class="fas fa-redo me-2"></i>Thử lại'; btn.disabled = false; window._pendingBookingId = null; }
                const nb = document.getElementById('transferWaitingNote');
                if (nb) nb.innerHTML = `<div style="background:#fff7ed;border:1.5px solid #fed7aa;border-radius:12px;padding:1rem;color:#9a3412;font-size:.85rem;">
                    <i class="fas fa-exclamation-triangle me-1"></i> Hết thời gian chờ tự động.<br>
                    Nếu đã chuyển khoản, nhấn <strong>"Kiểm tra ngay"</strong> hoặc liên hệ hỗ trợ với mã: <strong>${window._pendingTransferRef}</strong>.
                    <br><br>
                    <button onclick="window._manualCheckBooking && window._manualCheckBooking()" id="btnManualCheckBooking"
                            style="background:#d97706;color:#fff;border:none;border-radius:8px;padding:.5rem 1rem;font-size:.83rem;font-weight:700;cursor:pointer;width:100%;">
                        <i class="fas fa-sync-alt me-1"></i> Kiểm tra thanh toán ngay
                    </button>
                </div>`;
            }
        }, 5000);
    }

    function checkPaymentStatus(bookingId) {
        // Nếu đã xử lý xong thì bỏ qua
        if (window._paymentDone) return;

        fetch(`api/check-payment-status.php?booking_id=${bookingId}`)
        .then(r => r.json())
        .then(data => {
            if (!data.paid) return;

            // Đánh dấu đã xong - tránh xử lý lại
            window._paymentDone = true;

            // Dừng polling NGAY LẬP TỨC
            if (_pollingTimer) { clearInterval(_pollingTimer); _pollingTimer = null; }

            // Ẩn panels
            ['bookingBankPanel','bookingMomoPanel','transferWaitingNote'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.style.display = 'none';
            });

            // Điền thông tin modal
            const codeEl = document.getElementById('bookingCode');
            if (codeEl) codeEl.textContent = 'BK' + bookingId;
            const nameEl = document.getElementById('finalCourtName');
            if (nameEl) nameEl.textContent = bookingState.selectedCourt?.name || '';
            const dtEl = document.getElementById('finalDateTime');
            if (dtEl) dtEl.textContent =
                (bookingState.selectedDate ? new Date(bookingState.selectedDate).toLocaleDateString('vi-VN') : '') +
                (bookingState.selectedTime ? ' - ' + bookingState.selectedTime + ' den ' + (bookingState.selectedEndTime || '') : '');
            const totalEl = document.getElementById('finalTotal');
            if (totalEl) totalEl.textContent = (bookingState.totalPrice || window._pendingAmount || 0).toLocaleString('vi-VN') + 'd';

            const ne = document.getElementById('bookingSuccessNote');
            if (ne) {
                ne.innerHTML = '<i class="fas fa-check-circle text-success me-1"></i> Da nhan thanh toan - dat san thanh cong!';
                ne.style.display = 'block';
            }

            // Hiện modal
            const modalEl = document.getElementById('bookingSuccessModal');
            if (modalEl) {
                new bootstrap.Modal(modalEl).show();
            }

            window._pendingBookingId   = null;
            window._pendingTransferRef = null;

            // Countdown hiển thị
            const countEl = document.getElementById('redirectCountdown');
            let countdown = 2;
            const countTimer = setInterval(() => {
                countdown--;
                if (countEl) countEl.textContent = countdown;
                if (countdown <= 0) clearInterval(countTimer);
            }, 1000);

            // Redirect sau 2 giây
            window._redirectTimer = setTimeout(() => {
                window.location.href = 'booking-history.php';
            }, 2000);
        })
        .catch(() => {});
    }
    // Core booking submission function (cash only)
    function submitBooking(paymentMethod, btn) {
        // Show loading
        if (btn) {
            btn.innerHTML = '<div class="spinner-border spinner-border-sm me-2"></div>Đang xử lý...';
            btn.disabled = true;
        }

        // Prepare booking data
        const bookingData = {
            court_id: bookingState.selectedCourt.id,
            booking_date: bookingState.selectedDate,
            start_time: bookingState.selectedTime,
            duration: bookingState.selectedDuration,
            payment_method: paymentMethod,
            notes: document.getElementById('bookingNotes')?.value || ''
        };

        console.log('Sending booking data:', bookingData);

        // Send AJAX request to book.php
        fetch('book.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: new URLSearchParams(bookingData)
        })
        .then(response => {
            if (response.redirected) {
                // Payment gateway redirect (VNPay/MoMo)
                window.location.href = response.url;
                return null;
            }
            return response.json();
        })
        .then(data => {
            if (!data) return; // đã redirect

            console.log('Booking response:', data);

            if (data.success) {
                const bookingId  = data.booking_id;
                const bookingCode = 'BK' + bookingId;

                // Cập nhật modal
                document.getElementById('bookingCode').textContent    = bookingCode;
                document.getElementById('finalCourtName').textContent = bookingState.selectedCourt.name;
                document.getElementById('finalDateTime').textContent  =
                    `${new Date(bookingState.selectedDate).toLocaleDateString('vi-VN')} - ${bookingState.selectedTime} đến ${bookingState.selectedEndTime}`;
                document.getElementById('finalTotal').textContent     = `${bookingState.totalPrice.toLocaleString()}đ`;

                // Thêm ghi chú nếu thanh toán chuyển khoản (đang chờ xác nhận)
                const pm = data.payment_method || 'cash';
                const noteEl = document.getElementById('bookingSuccessNote');
                if (noteEl) {
                    if (pm === 'momo') {
                        noteEl.innerHTML = '<i class="fas fa-clock text-warning me-1"></i> Đơn đặt sân đang chờ xác nhận thanh toán MoMo.';
                        noteEl.style.display = 'block';
                    } else if (pm === 'vnpay') {
                        noteEl.innerHTML = '<i class="fas fa-clock text-warning me-1"></i> Đơn đặt sân đang chờ xác nhận chuyển khoản MB Bank.';
                        noteEl.style.display = 'block';
                    } else {
                        noteEl.style.display = 'none';
                    }
                }

                // Hiện modal thành công
                const successModal = new bootstrap.Modal(document.getElementById('bookingSuccessModal'));
                successModal.show();

                // Reset nút
                if (btn) {
                    btn.innerHTML = '<i class="fas fa-check me-2"></i>Xác nhận đặt sân';
                    btn.disabled  = false;
                }

                // Redirect sang lịch sử sau 3 giây
                setTimeout(() => { window.location.href = 'booking-history.php'; }, 2000);

            } else {
                throw new Error(data.error || 'Có lỗi xảy ra khi đặt sân');
            }
        })
        .catch(error => {
            console.error('Booking error:', error);
            showAlert('Có lỗi xảy ra khi đặt sân. Vui lòng thử lại.', 'error');
            
            // Reset button
            if (btn) {
                btn.innerHTML = '<i class="fas fa-check me-2"></i>Xác nhận đặt sân';
                btn.disabled = false;
            }
        });
    }

    // Alert helper function
    function showAlert(message, type = 'info') {
        const alertHTML = `
            <div class="alert alert-${type} alert-dismissible fade show position-fixed" 
                 style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
                <i class="fas fa-${type === 'warning' ? 'exclamation-triangle' : 'info-circle'} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', alertHTML);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            const alert = document.querySelector('.alert');
            if (alert) alert.remove();
        }, 5000);
    }

    // Initialize
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('bookingDate').value = today;
    bookingState.selectedDate = today;

    // Auto-select preselected court if coming from URL ?court_id=xxx
    if (window._preselectedCourt) {
        const pc = window._preselectedCourt;
        const courtId = pc.id;
        const selectBtn = document.querySelector(`.select-court-btn[data-court-id="${courtId}"]`);

        if (selectBtn) {
            // Set bookingState immediately (no wait needed)
            bookingState.selectedCourt = {
                id:    courtId,
                name:  pc.name,
                price: pc.price
            };

            // Highlight card
            const card = selectBtn.closest('.court-booking-card');
            if (card) {
                card.classList.add('selected-court');
                card.style.border = '2px solid #007bff';
                card.style.backgroundColor = '#f8f9ff';
                setTimeout(() => {
                    card.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }, 200);
            }

            // Update step 2 info
            document.getElementById('selectedCourtName').textContent = pc.name;
            document.getElementById('selectedCourtPrice').textContent = `Giá: ${pc.price.toLocaleString()}đ/giờ`;

            // Auto-proceed to step 2 after short delay
            setTimeout(() => {
                showStep(2);
                loadTimeSlots();
            }, 800);
        }
    }

    console.log('Enhanced booking system initialized');
});

// ============================================================
// RECURRING BOOKING — Đặt sân định kỳ
// ============================================================

// State: các ngày trong tuần đã chọn
window._recurringDays = [];
window._recurringPreviewData = null;

// Toggle recurring panel
document.addEventListener('DOMContentLoaded', function() {
    const toggle = document.getElementById('recurringToggle');
    if (toggle) {
        toggle.addEventListener('change', function() {
            const opts = document.getElementById('recurringOptions');
            if (opts) opts.style.display = this.checked ? 'block' : 'none';
            if (!this.checked) {
                // Reset khi tắt
                window._recurringDays = [];
                window._recurringPreviewData = null;
                document.querySelectorAll('.dow-badge').forEach(b => {
                    b.style.background = '#fff';
                    b.style.color = '#6b7280';
                    b.style.borderColor = '#e2e8f0';
                });
                const pr = document.getElementById('recurringPreviewResult');
                if (pr) pr.style.display = 'none';
            }
        });
    }
});

// Toggle ngày trong tuần
function toggleDow(val) {
    const idx = window._recurringDays.indexOf(val);
    const badge = document.querySelector(`.dow-badge[data-val="${val}"]`);
    if (idx === -1) {
        window._recurringDays.push(val);
        if (badge) {
            badge.style.background = '#3b82f6';
            badge.style.color = '#fff';
            badge.style.borderColor = '#3b82f6';
        }
    } else {
        window._recurringDays.splice(idx, 1);
        if (badge) {
            badge.style.background = '#fff';
            badge.style.color = '#6b7280';
            badge.style.borderColor = '#e2e8f0';
        }
    }
}

// Xem trước lịch đặt định kỳ
function previewRecurring() {
    const btn      = document.getElementById('btnRecurringPreview');
    const result   = document.getElementById('recurringPreviewResult');
    const endDate  = document.getElementById('recurringEndDate')?.value;

    // Lấy bookingState từ scope cha (DOMContentLoaded)
    const court    = window._bookingState?.selectedCourt;
    const date     = window._bookingState?.selectedDate;
    const time     = window._bookingState?.selectedTime;
    const duration = window._bookingState?.selectedDuration || 1;

    if (!court || !date || !time) {
        alert('Vui lòng chọn sân và khung giờ trước.');
        return;
    }
    if (window._recurringDays.length === 0) {
        alert('Vui lòng chọn ít nhất 1 ngày trong tuần.');
        return;
    }
    if (!endDate) {
        alert('Vui lòng chọn ngày kết thúc.');
        return;
    }
    if (endDate <= date) {
        alert('Ngày kết thúc phải sau ngày bắt đầu.');
        return;
    }

    if (btn) { btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Đang tính...'; btn.disabled = true; }

    const fd = new FormData();
    fd.append('court_id',       court.id);
    fd.append('start_time',     time);
    fd.append('duration',       duration);
    fd.append('start_date',     date);
    fd.append('end_date',       endDate);
    fd.append('payment_method', 'cash');
    fd.append('preview',        '1');
    window._recurringDays.forEach(d => fd.append('days_of_week[]', d));

    fetch('api/recurring-booking.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (btn) { btn.innerHTML = '<i class="fas fa-eye me-1"></i> Xem trước lịch đặt'; btn.disabled = false; }
            if (!data.success) { alert('Lỗi: ' + data.error); return; }

            window._recurringPreviewData = data;
            renderRecurringPreview(data);
        })
        .catch(err => {
            if (btn) { btn.innerHTML = '<i class="fas fa-eye me-1"></i> Xem trước lịch đặt'; btn.disabled = false; }
            alert('Lỗi kết nối: ' + err.message);
        });
}

// Hiển thị kết quả preview
function renderRecurringPreview(data) {
    const result = document.getElementById('recurringPreviewResult');
    if (!result) return;

    const dowMap = {1:'T2',2:'T3',3:'T4',4:'T5',5:'T6',6:'T7',7:'CN'};
    const fmtDate = s => {
        const d = new Date(s + 'T00:00:00');
        return `${String(d.getDate()).padStart(2,'0')}/${String(d.getMonth()+1).padStart(2,'0')}/${d.getFullYear()}`;
    };

    let datesHtml = '';
    data.dates_available.forEach((d, i) => {
        const dow = new Date(d + 'T00:00:00').getDay(); // 0=CN,1=T2...
        const dowLabel = {0:'CN',1:'T2',2:'T3',3:'T4',4:'T5',5:'T6',6:'T7'}[dow];
        datesHtml += `<span style="display:inline-block;background:#d1fae5;color:#065f46;border-radius:6px;padding:2px 8px;font-size:.75rem;font-weight:600;margin:2px;">${dowLabel} ${fmtDate(d)}</span>`;
    });

    let conflictHtml = '';
    if (data.dates_conflict.length > 0) {
        conflictHtml = `<div style="margin-top:.6rem;font-size:.78rem;color:#dc2626;">
            <i class="fas fa-exclamation-triangle me-1"></i>
            <strong>${data.dates_conflict.length} ngày bị trùng lịch</strong> (sẽ bỏ qua):
            ${data.dates_conflict.map(d => `<span style="display:inline-block;background:#fee2e2;color:#991b1b;border-radius:6px;padding:2px 6px;font-size:.72rem;margin:1px;">${fmtDate(d)}</span>`).join('')}
        </div>`;
    }

    result.style.display = 'block';
    result.innerHTML = `
        <div style="background:#f0fdf4;border:1.5px solid #86efac;border-radius:12px;padding:1rem;">
            <!-- Summary -->
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:.6rem;margin-bottom:.8rem;">
                <div style="background:#fff;border-radius:8px;padding:.6rem;text-align:center;">
                    <div style="font-size:1.4rem;font-weight:800;color:#16a34a;">${data.total_sessions}</div>
                    <div style="font-size:.72rem;color:#6b7280;">Buổi đặt</div>
                </div>
                <div style="background:#fff;border-radius:8px;padding:.6rem;text-align:center;">
                    <div style="font-size:1.4rem;font-weight:800;color:#6366f1;">${parseInt(data.price_per_session).toLocaleString('vi-VN')}đ</div>
                    <div style="font-size:.72rem;color:#6b7280;">Mỗi buổi</div>
                </div>
                <div style="background:#fff;border-radius:8px;padding:.6rem;text-align:center;">
                    <div style="font-size:1.4rem;font-weight:800;color:#ef4444;">${parseInt(data.total_price).toLocaleString('vi-VN')}đ</div>
                    <div style="font-size:.72rem;color:#6b7280;">Tổng tiền</div>
                </div>
            </div>

            <div style="font-size:.82rem;color:#166534;margin-bottom:.6rem;">
                <i class="fas fa-calendar-week me-1"></i>
                <strong>${data.court_name}</strong> · ${data.start_time}–${data.end_time} · ${data.days_label}
            </div>

            <!-- Danh sách ngày -->
            <div style="margin-bottom:.6rem;font-size:.78rem;font-weight:700;color:#166534;">
                Các buổi sẽ được đặt:
            </div>
            <div style="max-height:140px;overflow-y:auto;margin-bottom:.4rem;">${datesHtml}</div>
            ${conflictHtml}

            <!-- Nút xác nhận -->
            <div style="margin-top:1rem;display:flex;gap:.6rem;">
                <select id="recurringPayMethod" style="flex:1;border:1.5px solid #86efac;border-radius:8px;padding:.45rem .7rem;font-size:.83rem;font-weight:600;color:#374151;">
                    <option value="cash">💵 Tiền mặt tại sân</option>
                    <option value="vnpay">🏦 MB Bank (chuyển khoản)</option>
                    <option value="momo">💳 Ví MoMo</option>
                </select>
                <button onclick="confirmRecurring()" id="btnConfirmRecurring"
                        style="background:linear-gradient(135deg,#16a34a,#15803d);color:#fff;border:none;border-radius:8px;padding:.45rem 1.2rem;font-size:.85rem;font-weight:700;cursor:pointer;white-space:nowrap;">
                    <i class="fas fa-check me-1"></i> Xác nhận đặt ${data.total_sessions} buổi
                </button>
            </div>
        </div>`;
}

// Xác nhận đặt định kỳ
function confirmRecurring() {
    const data     = window._recurringPreviewData;
    const btn      = document.getElementById('btnConfirmRecurring');
    const payMethod = document.getElementById('recurringPayMethod')?.value || 'cash';

    if (!data) { alert('Vui lòng xem trước lịch đặt trước.'); return; }

    const court    = window._bookingState?.selectedCourt;
    const date     = window._bookingState?.selectedDate;
    const time     = window._bookingState?.selectedTime;
    const duration = window._bookingState?.selectedDuration || 1;
    const endDate  = document.getElementById('recurringEndDate')?.value;
    const notes    = document.getElementById('bookingNotes')?.value || '';

    if (btn) { btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Đang đặt...'; btn.disabled = true; }

    const fd = new FormData();
    fd.append('court_id',       court.id);
    fd.append('start_time',     time);
    fd.append('duration',       duration);
    fd.append('start_date',     date);
    fd.append('end_date',       endDate);
    fd.append('payment_method', payMethod);
    fd.append('notes',          notes);
    fd.append('preview',        '0');
    window._recurringDays.forEach(d => fd.append('days_of_week[]', d));

    fetch('api/recurring-booking.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            if (!res.success) {
                if (btn) { btn.innerHTML = '<i class="fas fa-check me-1"></i> Xác nhận đặt'; btn.disabled = false; }
                alert('Lỗi: ' + res.error);
                return;
            }

            // Ẩn recurring panel
            const container = document.getElementById('recurringPreviewResult');
            if (container) container.style.display = 'none';

            if (payMethod === 'cash') {
                // Hiện toast thành công rồi redirect
                showRecurringSuccess(res);
            } else {
                // Hiện QR để chuyển khoản
                showRecurringPayment(res, payMethod);
            }
        })
        .catch(err => {
            if (btn) { btn.innerHTML = '<i class="fas fa-check me-1"></i> Xác nhận đặt'; btn.disabled = false; }
            alert('Lỗi kết nối: ' + err.message);
        });
}

// Hiển thị thành công (cash)
function showRecurringSuccess(res) {
    const toast = document.createElement('div');
    toast.style.cssText = 'position:fixed;top:20px;right:20px;z-index:9999;background:linear-gradient(135deg,#10b981,#059669);color:#fff;padding:1.2rem 1.5rem;border-radius:16px;box-shadow:0 10px 40px rgba(16,185,129,.3);min-width:300px;font-weight:600;';
    toast.innerHTML = `
        <div style="font-size:1rem;margin-bottom:.3rem;">🎉 Đặt sân định kỳ thành công!</div>
        <div style="font-size:.82rem;opacity:.9;">Đã tạo <strong>${res.total_sessions}</strong> buổi · Tổng: <strong>${parseInt(res.total_price).toLocaleString('vi-VN')}đ</strong></div>
        <div style="font-size:.75rem;opacity:.75;margin-top:.2rem;">Chuyển sang lịch sử đặt sân...</div>`;
    document.body.appendChild(toast);
    setTimeout(() => { window.location.href = 'booking-history.php'; }, 2500);
}

// Hiển thị QR thanh toán (bank/momo)
function showRecurringPayment(res, payMethod) {
    const isMomo = payMethod === 'momo';
    const ref    = res.transfer_ref;
    const amount = res.total_price;
    const enc    = encodeURIComponent(ref);
    const qrUrl  = isMomo
        ? `https://img.vietqr.io/image/MOMO-0968073500-qr_only.png?amount=${amount}&addInfo=${enc}&accountName=LU+DANG+HUNG`
        : `https://img.vietqr.io/image/MB-7369786789-qr_only.png?amount=${amount}&addInfo=${enc}&accountName=LU+DANG+HUNG`;
    const color  = isMomo ? '#db2777' : '#4f46e5';

    // Tạo overlay
    const overlay = document.createElement('div');
    overlay.id = 'recurringPayOverlay';
    overlay.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:2000;display:flex;align-items:center;justify-content:center;padding:1rem;';
    overlay.innerHTML = `
        <div style="background:#fff;border-radius:20px;padding:1.5rem;max-width:420px;width:100%;box-shadow:0 20px 60px rgba(0,0,0,.3);">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;">
                <div style="font-weight:800;color:${color};font-size:1rem;">
                    <i class="fas fa-${isMomo ? 'wallet' : 'university'} me-2"></i>
                    ${isMomo ? 'Thanh toán MoMo' : 'Chuyển khoản MB Bank'}
                </div>
                <button onclick="document.getElementById('recurringPayOverlay').remove();window.location.href='booking-history.php'"
                        style="background:none;border:none;font-size:1.3rem;cursor:pointer;color:#6b7280;">×</button>
            </div>

            <div style="text-align:center;margin-bottom:1rem;">
                <img src="${qrUrl}" alt="QR" style="width:160px;height:160px;border-radius:12px;border:2px solid #e5e7eb;padding:4px;background:#fff;">
            </div>

            <div style="font-size:.85rem;display:grid;gap:.4rem;margin-bottom:1rem;background:#f8fafc;border-radius:10px;padding:.8rem;">
                ${isMomo
                    ? `<div style="display:flex;justify-content:space-between;"><span style="color:#78716c;">Số MoMo</span><strong style="color:${color};">0968073500</strong></div>`
                    : `<div style="display:flex;justify-content:space-between;"><span style="color:#78716c;">Ngân hàng</span><strong>MB Bank · 7369786789</strong></div>`
                }
                <div style="display:flex;justify-content:space-between;"><span style="color:#78716c;">Số tiền</span><strong style="color:${color};">${parseInt(amount).toLocaleString('vi-VN')}đ</strong></div>
                <div>
                    <span style="color:#78716c;display:block;font-size:.75rem;margin-bottom:3px;">📌 Nội dung CK:</span>
                    <div style="display:flex;align-items:center;gap:.4rem;">
                        <code style="background:#fff;border:1.5px solid ${color};border-radius:6px;padding:3px 10px;font-weight:700;color:${color};">${ref}</code>
                        <button onclick="navigator.clipboard.writeText('${ref}').then(()=>{this.textContent='✓';setTimeout(()=>this.textContent='📋',1500)})"
                                style="background:${color};color:#fff;border:none;border-radius:5px;padding:3px 8px;cursor:pointer;">📋</button>
                    </div>
                </div>
            </div>

            <div style="background:#f0fdf4;border:1.5px solid #86efac;border-radius:10px;padding:.8rem;font-size:.8rem;color:#166534;text-align:center;">
                <i class="fas fa-magic me-1"></i> Hệ thống tự động xác nhận · ${res.total_sessions} buổi · Không cần làm thêm gì
            </div>

            <div style="margin-top:.8rem;display:flex;gap:.5rem;">
                <button onclick="window.location.href='booking-history.php'"
                        style="flex:1;background:#f8fafc;border:1.5px solid #e2e8f0;border-radius:10px;padding:.6rem;font-size:.83rem;font-weight:600;color:#374151;cursor:pointer;">
                    Xem lịch sử đặt sân
                </button>
            </div>
        </div>`;
    document.body.appendChild(overlay);
}

// Expose bookingState để recurring functions có thể đọc
document.addEventListener('DOMContentLoaded', function() {
    // Hook vào bookingState sau khi DOMContentLoaded chạy xong
    setTimeout(() => {
        // bookingState được khai báo trong DOMContentLoaded scope,
        // dùng MutationObserver để lắng nghe thay đổi selectedCourtName
        const courtNameEl = document.getElementById('selectedCourtName');
        const dateEl      = document.getElementById('bookingDate');
        if (courtNameEl) {
            const obs = new MutationObserver(() => {
                // Sync from DOM elements vào window._bookingState
                syncBookingState();
            });
            obs.observe(courtNameEl, { childList: true, characterData: true, subtree: true });
        }
        if (dateEl) {
            dateEl.addEventListener('change', syncBookingState);
        }
    }, 500);
});

function syncBookingState() {
    // Đọc lại từ các element hiện tại
    const courtId   = document.querySelector('.court-booking-card.selected-court')?.dataset?.courtId;
    const courtName = document.getElementById('selectedCourtName')?.textContent;
    const courtPrice = parseInt((document.getElementById('selectedCourtPrice')?.textContent || '0').replace(/[^\d]/g, '')) || 0;
    const date      = document.getElementById('bookingDate')?.value;
    const selectedSlot = document.querySelector('.time-slot-enhanced.selected');
    const time      = selectedSlot?.dataset?.time;
    const endTime   = selectedSlot?.dataset?.endTime;
    const duration  = parseInt(document.querySelector('.duration-option.selected')?.dataset?.duration || '1');

    window._bookingState = {
        selectedCourt: courtId ? { id: courtId, name: courtName, price: courtPrice } : null,
        selectedDate:  date || null,
        selectedTime:  time || null,
        selectedEndTime: endTime || null,
        selectedDuration: duration,
    };
}

// Patch selectTimeSlot để sync state
const _origSelectTimeSlot = window.selectTimeSlot;
window.selectTimeSlot = function(element) {
    if (_origSelectTimeSlot) _origSelectTimeSlot(element);
    setTimeout(syncBookingState, 100);
};

// Enhanced CSS for beautiful booking interface
enhancedStyle.textContent = `
    /* Enhanced Time Slots Styling */
    .time-slot-enhanced {
        position: relative;
        background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
        border: 2px solid #e9ecef;
        border-radius: 12px;
        padding: 16px;
        margin: 8px;
        text-align: center;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        cursor: pointer;
        overflow: hidden;
        min-width: 160px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }

    .time-slot-enhanced.available {
        border-color: #28a745;
        background: linear-gradient(135deg, #f8fff8 0%, #e8f5e8 100%);
    }

    .time-slot-enhanced.available:hover {
        transform: translateY(-4px) scale(1.02);
        box-shadow: 0 8px 25px rgba(40, 167, 69, 0.15);
        border-color: #20c997;
    }

    .time-slot-enhanced.booked {
        border-color: #dc3545;
        background: linear-gradient(135deg, #fff8f8 0%, #f8e8e8 100%);
        opacity: 0.7;
    }

    .time-slot-enhanced.passed {
        border-color: #6c757d;
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        opacity: 0.6;
    }

    .time-slot-enhanced.selected {
        border-color: #007bff !important;
        background: linear-gradient(135deg, #e7f3ff 0%, #cce7ff 100%) !important;
        transform: translateY(-2px) scale(1.05);
        box-shadow: 0 8px 25px rgba(0, 123, 255, 0.2);
    }

    .slot-header {
        margin-bottom: 8px;
        position: relative;
    }

    .slot-time {
        font-weight: 700;
        font-size: 1.1em;
        color: #2c3e50;
        margin-bottom: 4px;
    }

    .peak-badge {
        position: absolute;
        top: -8px;
        right: -8px;
        background: linear-gradient(45deg, #ff6b6b, #ee5a24);
        color: white;
        font-size: 0.7em;
        padding: 2px 6px;
        border-radius: 8px;
        font-weight: 600;
        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }

    .discount-badge {
        position: absolute;
        top: -8px;
        right: -8px;
        background: linear-gradient(45deg, #00b894, #00a085);
        color: white;
        font-size: 0.7em;
        padding: 2px 6px;
        border-radius: 8px;
        font-weight: 600;
        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }

    .slot-price {
        font-size: 1.2em;
        font-weight: 700;
        color: #28a745;
        margin: 8px 0;
        text-shadow: 0 1px 2px rgba(0,0,0,0.1);
    }

    .price-note {
        font-size: 0.8em;
        color: #6c757d;
        font-weight: 500;
        margin-top: 2px;
    }

    .pricing-info {
        border-left: 4px solid #28a745;
        background: linear-gradient(135deg, #f8fff8 0%, #ffffff 100%);
    }

    .loading-container {
        padding: 40px;
        background: rgba(255, 255, 255, 0.9);
        border-radius: 15px;
        backdrop-filter: blur(10px);
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
    }

    .slot-status .badge {
        font-size: 0.8em;
        padding: 4px 8px;
        border-radius: 6px;
    }

    .slot-hover-effect {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 123, 255, 0.9);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        opacity: 0;
        transition: opacity 0.3s ease;
        border-radius: 10px;
    }

    .time-slot-enhanced.available:hover .slot-hover-effect {
        opacity: 1;
    }

    .payment-option {
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .payment-card {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        border: 2px solid #e9ecef !important;
        background: #ffffff;
        position: relative;
        overflow: hidden;
    }

    .payment-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
        transition: left 0.5s;
    }

    .payment-option:hover .payment-card {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        border-color: #007bff !important;
    }

    .payment-option:hover .payment-card::before {
        left: 100%;
    }

    .payment-option.selected .payment-card {
        border-color: #28a745 !important;
        background: linear-gradient(135deg, #f8fff8 0%, #e8f5e8 100%);
        transform: scale(1.02);
        box-shadow: 0 8px 25px rgba(40, 167, 69, 0.2);
        font-weight: 600;
    }

    .payment-option.selected .payment-card .fw-bold {
        color: #28a745;
        font-weight: 700;
    }

    .payment-option.selected .payment-card::after {
        content: '✓';
        position: absolute;
        top: 10px;
        right: 15px;
        background: #28a745;
        color: white;
        width: 24px;
        height: 24px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: bold;
        animation: checkmark 0.3s ease;
    }

    @keyframes checkmark {
        0% {
            transform: scale(0) rotate(180deg);
            opacity: 0;
        }
        100% {
            transform: scale(1) rotate(0deg);
            opacity: 1;
        }
    }

    @keyframes pulse {
        0% {
            transform: scale(1.02);
        }
        50% {
            transform: scale(1.05);
        }
        100% {
            transform: scale(1.02);
        }
    }

    /* Dim unselected options */
    .payment-methods.has-selection .payment-option:not(.selected) {
        opacity: 0.6;
        transform: scale(0.98);
    }

    .payment-methods.has-selection .payment-option:not(.selected) .payment-card {
        background: #f8f9fa;
        border-color: #dee2e6 !important;
    }

    /* Radio button styling */
    .payment-option input[type="radio"] {
        width: 20px;
        height: 20px;
        accent-color: #28a745;
    }

    .payment-option.selected input[type="radio"] {
        transform: scale(1.2);
    }

    .duration-options {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
        gap: 12px;
        margin-top: 12px;
    }

    .duration-option {
        background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
        border: 2px solid #e9ecef;
        border-radius: 10px;
        padding: 12px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .duration-option:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0,0,0,0.1);
        border-color: #007bff;
    }

    .duration-option.selected {
        border-color: #007bff;
        background: linear-gradient(135deg, #e7f3ff 0%, #cce7ff 100%);
        transform: scale(1.05);
    }

    .duration-time {
        font-weight: 700;
        font-size: 1.1em;
        color: #2c3e50;
        margin-bottom: 4px;
    }

    .duration-price {
        font-size: 1.1em;
        font-weight: 600;
        color: #28a745;
        margin-bottom: 4px;
    }

    .duration-save {
        font-size: 0.8em;
        color: #e74c3c;
        font-weight: 600;
        background: rgba(231, 76, 60, 0.1);
        padding: 2px 6px;
        border-radius: 4px;
        display: inline-block;
    }

    /* Court Selection Enhancement */
    .court-booking-card {
        transition: all 0.3s ease;
        border-radius: 12px;
        overflow: hidden;
        margin-bottom: 20px;
        border: 2px solid transparent;
        background: white;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }

    .court-booking-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        border-color: #007bff;
    }

    .court-booking-card.selected-court {
        border-color: #28a745 !important;
        background: linear-gradient(135deg, #f8fff8 0%, #ffffff 100%);
        transform: scale(1.02);
        box-shadow: 0 8px 25px rgba(40, 167, 69, 0.2);
    }

    .court-image {
        width: 100%;
        height: 200px;
        object-fit: cover;
        transition: transform 0.3s ease;
    }

    .court-booking-card:hover .court-image {
        transform: scale(1.05);
    }

    /* Step Indicators Enhancement */
    .booking-steps {
        position: relative;
        padding: 20px 0;
    }

    .step-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        position: relative;
        z-index: 2;
    }

    .step-circle {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: #e9ecef;
        color: #6c757d;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 1.2em;
        transition: all 0.3s ease;
        border: 3px solid #e9ecef;
    }

    .step-item.active .step-circle {
        background: linear-gradient(135deg, #007bff, #0056b3);
        color: white;
        border-color: #007bff;
        transform: scale(1.1);
        box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
    }

    .step-item.completed .step-circle {
        background: linear-gradient(135deg, #28a745, #20c997);
        color: white;
        border-color: #28a745;
    }

    .step-label {
        margin-top: 8px;
        font-weight: 600;
        color: #6c757d;
        transition: color 0.3s ease;
    }

    .step-item.active .step-label,
    .step-item.completed .step-label {
        color: #2c3e50;
    }

    .step-connector {
        flex: 1;
        height: 3px;
        background: #e9ecef;
        margin: 0 20px;
        position: relative;
        top: -25px;
        z-index: 1;
    }

    /* Slots Summary Enhancement */
    .slots-summary {
        border-left: 4px solid #17a2b8;
        background: linear-gradient(135deg, #e7f9fc 0%, #ffffff 100%);
        border-radius: 8px;
        animation: slideInDown 0.5s ease;
    }

    /* Animations */
    @keyframes slideInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes slideInDown {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .time-slot-enhanced {
            min-width: 140px;
            margin: 4px;
            padding: 12px;
        }
        
        .duration-options {
            grid-template-columns: repeat(2, 1fr);
        }
        
        .step-connector {
            margin: 0 10px;
        }
    }

    /* Payment Methods Enhancement */
    .payment-option {
        transition: all 0.3s ease;
    }

    .payment-card {
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .payment-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0,0,0,0.1);
        border-color: #007bff !important;
    }

    .payment-option input[type="radio"]:checked + .payment-card {
        border-color: #28a745 !important;
        background: linear-gradient(135deg, #f8fff8 0%, #ffffff 100%);
    }

    /* Loading States */
    .loading-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(255, 255, 255, 0.9);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 10;
        border-radius: 12px;
    }
`;
document.head.appendChild(enhancedStyle);
