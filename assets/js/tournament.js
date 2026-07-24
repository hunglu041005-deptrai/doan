// Tournament JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Package selection
    document.querySelectorAll('[data-package]').forEach(btn => {
        btn.addEventListener('click', function() {
            const packageType = this.dataset.package;
            const packageSelect = document.getElementById('packageSelect');
            
            // Set the package in the form
            packageSelect.value = packageType;
            
            // Scroll to form
            document.querySelector('.tournament-registration').scrollIntoView({
                behavior: 'smooth'
            });
            
            // Highlight the form
            const card = document.querySelector('.tournament-registration .card');
            card.style.boxShadow = '0 0 20px rgba(40, 167, 69, 0.3)';
            setTimeout(() => {
                card.style.boxShadow = '';
            }, 2000);
        });
    });

    // Form submission
    document.getElementById('tournamentForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const submitBtn = this.querySelector('button[type="submit"]');
        
        // Show loading
        submitBtn.innerHTML = '<div class="spinner-border spinner-border-sm me-2"></div>Đang gửi...';
        submitBtn.disabled = true;
        
        // Simulate form submission
        setTimeout(() => {
            // Show success modal
            const successModal = new bootstrap.Modal(document.getElementById('tournamentSuccessModal'));
            successModal.show();
            
            // Reset form
            this.reset();
            
            // Reset button
            submitBtn.innerHTML = '<i class="fas fa-paper-plane me-2"></i>Gửi đăng ký';
            submitBtn.disabled = false;
        }, 2000);
    });

    // Package price update based on participants
    document.querySelector('select[name="participants"]').addEventListener('change', function() {
        const participants = this.value;
        const packageSelect = document.getElementById('packageSelect');
        
        // Update package options based on participants
        const options = packageSelect.querySelectorAll('option');
        options.forEach(option => {
            if (option.value) {
                option.style.display = 'block';
                
                // Hide inappropriate packages
                if (participants === '16-32' && option.value === 'vip') {
                    option.style.display = 'none';
                } else if (participants === '64+' && option.value === 'basic') {
                    option.style.display = 'none';
                }
            }
        });
    });

    // Service card animations
    document.querySelectorAll('.service-card').forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
            this.style.boxShadow = '0 10px 30px rgba(0,0,0,0.15)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
            this.style.boxShadow = '';
        });
    });

    // Package card animations
    document.querySelectorAll('.package-card').forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'scale(1.02)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'scale(1)';
        });
    });

    // Form validation
    const form = document.getElementById('tournamentForm');
    const inputs = form.querySelectorAll('input[required], select[required]');
    
    inputs.forEach(input => {
        input.addEventListener('blur', function() {
            validateField(this);
        });
        
        input.addEventListener('input', function() {
            if (this.classList.contains('is-invalid')) {
                validateField(this);
            }
        });
    });

    function validateField(field) {
        const value = field.value.trim();
        let isValid = true;
        let message = '';

        // Required field check
        if (field.hasAttribute('required') && !value) {
            isValid = false;
            message = 'Trường này là bắt buộc';
        }

        // Email validation
        if (field.type === 'email' && value) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(value)) {
                isValid = false;
                message = 'Email không hợp lệ';
            }
        }

        // Phone validation
        if (field.type === 'tel' && value) {
            const phoneRegex = /^[0-9]{10,11}$/;
            if (!phoneRegex.test(value.replace(/\s/g, ''))) {
                isValid = false;
                message = 'Số điện thoại không hợp lệ';
            }
        }

        // Date validation
        if (field.type === 'date' && value) {
            const selectedDate = new Date(value);
            const minDate = new Date();
            minDate.setDate(minDate.getDate() + 7); // Minimum 7 days from now
            
            if (selectedDate < minDate) {
                isValid = false;
                message = 'Ngày tổ chức phải cách ít nhất 7 ngày';
            }
        }

        // Update field appearance
        if (isValid) {
            field.classList.remove('is-invalid');
            field.classList.add('is-valid');
        } else {
            field.classList.remove('is-valid');
            field.classList.add('is-invalid');
        }

        // Show/hide error message
        let feedback = field.parentNode.querySelector('.invalid-feedback');
        if (!feedback) {
            feedback = document.createElement('div');
            feedback.className = 'invalid-feedback';
            field.parentNode.appendChild(feedback);
        }
        feedback.textContent = message;
    }

    // Auto-fill tournament name based on organizer
    document.querySelector('input[name="organizer_name"]').addEventListener('input', function() {
        const tournamentNameField = document.querySelector('input[name="tournament_name"]');
        if (!tournamentNameField.value && this.value) {
            const currentYear = new Date().getFullYear();
            tournamentNameField.value = `Giải cầu lông ${this.value} ${currentYear}`;
        }
    });

    // Package comparison tooltip
    document.querySelectorAll('.package-card').forEach(card => {
        card.addEventListener('click', function() {
            const packageType = this.querySelector('[data-package]').dataset.package;
            showPackageDetails(packageType);
        });
    });

    function showPackageDetails(packageType) {
        const details = {
            basic: {
                title: 'Gói Cơ bản',
                features: [
                    'Lập kế hoạch giải đấu chi tiết',
                    '2 trọng tài chính thức có chứng chỉ',
                    'Bảng thi đấu in ấn chuyên nghiệp',
                    'Giải thưởng cơ bản (cup, huy chương)',
                    'Hỗ trợ tổ chức trong 1 ngày',
                    'Báo cáo kết quả chi tiết'
                ]
            },
            premium: {
                title: 'Gói Cao cấp',
                features: [
                    'Tất cả dịch vụ gói cơ bản',
                    '4 trọng tài chuyên nghiệp',
                    'Bảng thi đấu điện tử real-time',
                    'Giải thưởng cao cấp và tiền mặt',
                    'Livestream HD chất lượng cao',
                    'Quay phim highlight chuyên nghiệp',
                    'Hỗ trợ tổ chức 2-3 ngày',
                    'Dịch vụ MC chuyên nghiệp'
                ]
            },
            vip: {
                title: 'Gói VIP',
                features: [
                    'Tất cả dịch vụ gói cao cấp',
                    '6+ trọng tài quốc tế',
                    'Hệ thống điện tử hoàn chỉnh',
                    'Giải thưởng VIP và quà tặng',
                    'Multi-camera livestream',
                    'Dịch vụ catering cao cấp',
                    'Hỗ trợ tổ chức nhiều ngày',
                    'Dịch vụ khách sạn cho VIP',
                    'Xe đưa đón cho khách mời'
                ]
            }
        };

        const detail = details[packageType];
        if (detail) {
            // Create and show modal with package details
            console.log(`Showing details for ${detail.title}:`, detail.features);
        }
    }
});

// Add custom CSS
const style = document.createElement('style');
style.textContent = `
    .service-card {
        transition: all 0.3s ease;
        cursor: pointer;
    }
    
    .service-icon {
        width: 60px;
        height: 60px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .package-card {
        transition: all 0.3s ease;
        cursor: pointer;
        position: relative;
    }
    
    .popular-badge {
        position: absolute;
        top: -10px;
        right: 20px;
        z-index: 10;
    }
    
    .package-header {
        position: relative;
    }
    
    .package-price {
        margin: 10px 0;
    }
    
    .is-valid {
        border-color: #28a745;
    }
    
    .is-invalid {
        border-color: #dc3545;
    }
    
    .invalid-feedback {
        display: block;
        width: 100%;
        margin-top: 0.25rem;
        font-size: 0.875em;
        color: #dc3545;
    }
    
    .tournament-registration .card {
        transition: box-shadow 0.3s ease;
    }
    
    @media (max-width: 768px) {
        .package-card {
            margin-bottom: 20px;
        }
        
        .service-card {
            margin-bottom: 20px;
        }
    }
`;
document.head.appendChild(style);