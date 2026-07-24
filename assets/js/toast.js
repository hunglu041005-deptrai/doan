/**
 * Toast Notification System
 * Professional toast notifications for user feedback
 */

class Toast {
    constructor(type = 'info', message = '', duration = 4000) {
        this.type = type; // success, error, warning, info
        this.message = message;
        this.duration = duration;
        this.create();
    }

    create() {
        const container = document.getElementById('notification-container');
        if (!container) {
            console.error('Notification container not found');
            return;
        }

        const toast = document.createElement('div');
        toast.className = `toast-notification ${this.type}`;
        
        const icons = {
            success: '<i class="fas fa-check-circle" style="color: #198754; font-size: 1.5rem;"></i>',
            error: '<i class="fas fa-exclamation-circle" style="color: #dc3545; font-size: 1.5rem;"></i>',
            warning: '<i class="fas fa-exclamation-triangle" style="color: #ffc107; font-size: 1.5rem;"></i>',
            info: '<i class="fas fa-info-circle" style="color: #0dcaf0; font-size: 1.5rem;"></i>'
        };

        const closeBtn = `<button class="btn-close ms-auto" style="border: none; background: none; cursor: pointer; opacity: 0.5;">
            <i class="fas fa-times"></i>
        </button>`;

        toast.innerHTML = `
            ${icons[this.type] || icons.info}
            <div style="flex: 1; margin-left: 1rem;">
                <strong>${this.getTitle()}</strong>
                <p style="margin: 0; font-size: 0.9rem; opacity: 0.8;">${this.message}</p>
            </div>
            ${closeBtn}
        `;

        container.appendChild(toast);

        // Close button handler
        const closeBtn_el = toast.querySelector('.btn-close');
        closeBtn_el.addEventListener('click', () => this.remove(toast));

        // Auto dismiss
        if (this.duration) {
            setTimeout(() => this.remove(toast), this.duration);
        }

        return toast;
    }

    getTitle() {
        const titles = {
            success: '✓ Thành công',
            error: '✗ Lỗi',
            warning: '⚠ Cảnh báo',
            info: 'ℹ Thông tin'
        };
        return titles[this.type] || 'Thông báo';
    }

    remove(element) {
        element.classList.add('hide');
        setTimeout(() => {
            element.remove();
        }, 300);
    }
}

// Static methods for easy usage
Toast.success = (message, duration = 3000) => new Toast('success', message, duration);
Toast.error = (message, duration = 4000) => new Toast('error', message, duration);
Toast.warning = (message, duration = 3500) => new Toast('warning', message, duration);
Toast.info = (message, duration = 3000) => new Toast('info', message, duration);

// Legacy alert replacement
window.showNotification = (message, type = 'info') => {
    Toast[type](message);
};
