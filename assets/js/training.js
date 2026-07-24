// Training JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Course selection
    document.querySelectorAll('[data-course]').forEach(btn => {
        btn.addEventListener('click', function() {
            const courseType = this.dataset.course;
            const courseSelect = document.getElementById('courseSelect');
            
            // Set the course in the form
            courseSelect.value = courseType;
            
            // Scroll to form
            document.querySelector('.training-registration').scrollIntoView({
                behavior: 'smooth'
            });
            
            // Highlight the form
            const card = document.querySelector('.training-registration .card');
            card.style.boxShadow = '0 0 20px rgba(255, 193, 7, 0.3)';
            setTimeout(() => {
                card.style.boxShadow = '';
            }, 2000);
        });
    });

    // Form submission
    document.getElementById('trainingForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const submitBtn = this.querySelector('button[type="submit"]');
        
        // Show loading
        submitBtn.innerHTML = '<div class="spinner-border spinner-border-sm me-2"></div>Đang đăng ký...';
        submitBtn.disabled = true;
        
        // Simulate form submission
        setTimeout(() => {
            // Show success modal
            const successModal = new bootstrap.Modal(document.getElementById('trainingSuccessModal'));
            successModal.show();
            
            // Reset form
            this.reset();
            
            // Reset button
            submitBtn.innerHTML = '<i class="fas fa-graduation-cap me-2"></i>Đăng ký khóa học';
            submitBtn.disabled = false;
        }, 2000);
    });

    // Course recommendation based on current level
    document.querySelector('select[name="current_level"]').addEventListener('change', function() {
        const currentLevel = this.value;
        const courseSelect = document.getElementById('courseSelect');
        const recommendationDiv = document.getElementById('courseRecommendation') || createRecommendationDiv();
        
        let recommendedCourse = '';
        let message = '';
        
        switch(currentLevel) {
            case 'beginner':
                recommendedCourse = 'beginner';
                message = 'Khuyến nghị: Khóa cơ bản phù hợp với bạn';
                break;
            case 'basic':
                recommendedCourse = 'intermediate';
                message = 'Khuyến nghị: Khóa trung cấp sẽ giúp bạn tiến bộ';
                break;
            case 'intermediate':
                recommendedCourse = 'intermediate';
                message = 'Khuyến nghị: Khóa trung cấp để hoàn thiện kỹ năng';
                break;
            case 'advanced':
                recommendedCourse = 'advanced';
                message = 'Khuyến nghị: Khóa nâng cao cho trình độ của bạn';
                break;
        }
        
        if (recommendedCourse) {
            courseSelect.value = recommendedCourse;
            recommendationDiv.innerHTML = `
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    <i class="fas fa-lightbulb me-2"></i>${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
        }
    });

    function createRecommendationDiv() {
        const div = document.createElement('div');
        div.id = 'courseRecommendation';
        div.className = 'mt-2';
        
        const courseSelect = document.getElementById('courseSelect');
        courseSelect.parentNode.appendChild(div);
        
        return div;
    }

    // Age group validation
    document.querySelector('select[name="age_group"]').addEventListener('change', function() {
        const ageGroup = this.value;
        const courseSelect = document.getElementById('courseSelect');
        const options = courseSelect.querySelectorAll('option');
        
        // Show/hide courses based on age
        options.forEach(option => {
            if (option.value) {
                option.style.display = 'block';
                
                // Advanced course only for 18+
                if (ageGroup === '6-12' && option.value === 'advanced') {
                    option.style.display = 'none';
                }
            }
        });
    });

    // Training card animations
    document.querySelectorAll('.training-card').forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
            this.style.boxShadow = '0 10px 30px rgba(0,0,0,0.15)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
            this.style.boxShadow = '';
        });
    });

    // Coach card animations
    document.querySelectorAll('.coach-card').forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'scale(1.02)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'scale(1)';
        });
    });

    // Form validation
    const form = document.getElementById('trainingForm');
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

        // Name validation
        if (field.name === 'student_name' && value) {
            if (value.length < 2) {
                isValid = false;
                message = 'Tên phải có ít nhất 2 ký tự';
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

    // Course comparison
    document.querySelectorAll('.training-card').forEach(card => {
        card.addEventListener('click', function(e) {
            if (!e.target.closest('button')) {
                showCourseDetails(this);
            }
        });
    });

    function showCourseDetails(card) {
        const courseName = card.querySelector('h5').textContent;
        const courseInfo = card.querySelector('.training-info').innerHTML;
        const courseContent = card.querySelector('.training-content').innerHTML;
        
        // Create modal content
        const modalContent = `
            <div class="modal fade" id="courseDetailModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">${courseName}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Thông tin khóa học:</h6>
                                    <div class="training-info">${courseInfo}</div>
                                </div>
                                <div class="col-md-6">
                                    ${courseContent}
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                            <button type="button" class="btn btn-warning" onclick="document.querySelector('[data-course]').click()">Đăng ký ngay</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Remove existing modal
        const existingModal = document.getElementById('courseDetailModal');
        if (existingModal) {
            existingModal.remove();
        }
        
        // Add new modal
        document.body.insertAdjacentHTML('beforeend', modalContent);
        
        // Show modal
        const modal = new bootstrap.Modal(document.getElementById('courseDetailModal'));
        modal.show();
    }

    // Schedule suggestion based on age and preferred time
    function suggestSchedule() {
        const ageGroup = document.querySelector('select[name="age_group"]').value;
        const preferredTime = document.querySelector('select[name="preferred_time"]').value;
        
        if (ageGroup && preferredTime) {
            let suggestion = '';
            
            if (ageGroup === '6-12') {
                suggestion = 'Khuyến nghị: Lịch học cuối tuần phù hợp với trẻ em';
            } else if (ageGroup === '13-17') {
                suggestion = 'Khuyến nghị: Lịch học chiều và cuối tuần';
            } else if (preferredTime === 'morning') {
                suggestion = 'Khuyến nghị: Buổi sáng ít đông, tập trung tốt hơn';
            } else if (preferredTime === 'evening') {
                suggestion = 'Khuyến nghị: Buổi tối có nhiều lựa chọn lịch học';
            }
            
            if (suggestion) {
                showScheduleSuggestion(suggestion);
            }
        }
    }

    function showScheduleSuggestion(message) {
        const suggestionDiv = document.getElementById('scheduleSuggestion') || createScheduleSuggestionDiv();
        suggestionDiv.innerHTML = `
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-calendar-alt me-2"></i>${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
    }

    function createScheduleSuggestionDiv() {
        const div = document.createElement('div');
        div.id = 'scheduleSuggestion';
        div.className = 'mt-2';
        
        const preferredTimeSelect = document.querySelector('select[name="preferred_time"]');
        preferredTimeSelect.parentNode.appendChild(div);
        
        return div;
    }

    // Add event listeners for schedule suggestion
    document.querySelector('select[name="age_group"]').addEventListener('change', suggestSchedule);
    document.querySelector('select[name="preferred_time"]').addEventListener('change', suggestSchedule);
});

// Add custom CSS
const style = document.createElement('style');
style.textContent = `
    .training-card {
        transition: all 0.3s ease;
        cursor: pointer;
    }
    
    .training-image {
        position: relative;
        overflow: hidden;
    }
    
    .training-level {
        position: absolute;
        top: 10px;
        left: 10px;
    }
    
    .popular-badge {
        position: absolute;
        top: -10px;
        right: 20px;
        z-index: 10;
    }
    
    .coach-card {
        transition: all 0.3s ease;
        cursor: pointer;
    }
    
    .coach-avatar img {
        width: 120px;
        height: 120px;
        object-fit: cover;
    }
    
    .info-badge {
        display: flex;
        align-items: center;
        font-size: 0.9em;
    }
    
    .training-info .info-item {
        display: flex;
        align-items: center;
        font-size: 0.9em;
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
    
    .training-registration .card {
        transition: box-shadow 0.3s ease;
    }
    
    @media (max-width: 768px) {
        .training-card {
            margin-bottom: 20px;
        }
        
        .coach-card {
            margin-bottom: 20px;
        }
        
        .coach-avatar img {
            width: 100px;
            height: 100px;
        }
    }
`;
document.head.appendChild(style);