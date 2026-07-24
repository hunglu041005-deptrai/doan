// ===== PROFESSIONAL NOTIFICATION SYSTEM =====
class NotificationManager {
    constructor() {
        this.lastTimestamp = null;
        this.pollingInterval = null;
        this.isPolling = false;
        this.notifications = [];
        this.init();
    }

    init() {
        this.injectStyles();
        this.createNotificationUI();
        this.loadInitialNotifications();
        this.startPolling();
        this.bindEvents();
    }

    injectStyles() {
        if (document.getElementById('notif-styles')) return;
        const style = document.createElement('style');
        style.id = 'notif-styles';
        style.textContent = `
            /* ===== NOTIFICATION DROPDOWN ===== */
            .notif-dropdown {
                width: 380px !important;
                padding: 0 !important;
                border: none !important;
                border-radius: 18px !important;
                box-shadow: 0 20px 60px rgba(0,0,0,.18) !important;
                overflow: hidden;
                background: #fff !important;
            }

            .notif-header {
                padding: 1.1rem 1.4rem;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: #fff;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }

            .notif-header-title {
                font-weight: 700;
                font-size: 1rem;
                display: flex;
                align-items: center;
                gap: .5rem;
            }

            .notif-header-title .count-chip {
                background: rgba(255,255,255,.25);
                border-radius: 50px;
                padding: 2px 8px;
                font-size: .72rem;
                font-weight: 800;
            }

            .btn-mark-all {
                background: rgba(255,255,255,.2);
                border: 1px solid rgba(255,255,255,.3);
                color: #fff;
                border-radius: 8px;
                padding: 4px 12px;
                font-size: .75rem;
                font-weight: 600;
                cursor: pointer;
                transition: all .2s;
            }

            .btn-mark-all:hover { background: rgba(255,255,255,.3); }

            .notif-list {
                max-height: 360px;
                overflow-y: auto;
                scrollbar-width: thin;
                scrollbar-color: #e5e7eb #fff;
            }

            .notif-list::-webkit-scrollbar { width: 4px; }
            .notif-list::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 4px; }

            .notif-item {
                display: flex;
                align-items: flex-start;
                gap: .9rem;
                padding: .9rem 1.2rem;
                border-bottom: 1px solid #f3f4f6;
                cursor: pointer;
                transition: background .15s;
                position: relative;
                text-decoration: none !important;
            }

            .notif-item:hover { background: #f9fafb; }

            .notif-item.unread { background: #eff6ff; }
            .notif-item.unread:hover { background: #dbeafe; }

            .notif-icon-wrap {
                width: 40px;
                height: 40px;
                border-radius: 12px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: .95rem;
                flex-shrink: 0;
            }
            .notif-icon-success { background: #d1fae5; color: #059669; }
            .notif-icon-warning { background: #fef3c7; color: #d97706; }
            .notif-icon-danger  { background: #fee2e2; color: #dc2626; }
            .notif-icon-info    { background: #dbeafe; color: #2563eb; }
            .notif-icon-default { background: #f3f4f6; color: #6b7280; }

            .notif-body { flex: 1; min-width: 0; }

            .notif-title {
                font-weight: 700;
                font-size: .85rem;
                color: #111827;
                margin-bottom: 2px;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .notif-msg {
                font-size: .8rem;
                color: #6b7280;
                line-height: 1.4;
                display: -webkit-box;
                -webkit-line-clamp: 2;
                -webkit-box-orient: vertical;
                overflow: hidden;
            }

            .notif-time {
                font-size: .72rem;
                color: #9ca3af;
                margin-top: 4px;
                display: flex;
                align-items: center;
                gap: 4px;
            }

            .notif-unread-dot {
                width: 8px;
                height: 8px;
                background: #3b82f6;
                border-radius: 50%;
                flex-shrink: 0;
                margin-top: 6px;
            }

            .notif-empty {
                text-align: center;
                padding: 2.5rem 1rem;
                color: #9ca3af;
            }

            .notif-empty-icon {
                width: 60px;
                height: 60px;
                background: #f3f4f6;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 1.5rem;
                color: #d1d5db;
                margin: 0 auto .75rem;
            }

            .notif-empty p { font-size: .85rem; margin: 0; }

            .notif-footer {
                padding: .75rem 1.2rem;
                background: #f9fafb;
                border-top: 1px solid #f3f4f6;
                text-align: center;
            }

            .notif-footer a {
                font-size: .82rem;
                font-weight: 600;
                color: #667eea;
                text-decoration: none;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 5px;
                transition: color .2s;
            }

            .notif-footer a:hover { color: #764ba2; }

            .notif-loading {
                display: flex;
                align-items: center;
                justify-content: center;
                gap: .6rem;
                padding: 2rem;
                color: #9ca3af;
                font-size: .85rem;
            }

            .notif-spinner {
                width: 18px;
                height: 18px;
                border: 2px solid #e5e7eb;
                border-top-color: #667eea;
                border-radius: 50%;
                animation: notif-spin .7s linear infinite;
            }

            @keyframes notif-spin { to { transform: rotate(360deg); } }

            /* Bell button */
            .notif-bell-btn {
                position: relative;
                padding: .4rem .6rem !important;
                border-radius: 10px !important;
                transition: background .15s !important;
            }

            .notif-bell-btn:hover { background: rgba(255,255,255,.15) !important; }

            .notif-badge {
                position: absolute;
                top: 4px;
                right: 4px;
                min-width: 16px;
                height: 16px;
                background: linear-gradient(135deg, #ef4444, #dc2626);
                border-radius: 50px;
                font-size: .65rem;
                font-weight: 800;
                color: #fff;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 0 4px;
                border: 2px solid var(--navbar-bg, #fff);
                animation: notif-pulse 2s infinite;
            }

            @keyframes notif-pulse {
                0%, 100% { box-shadow: 0 0 0 0 rgba(239,68,68,.4); }
                50% { box-shadow: 0 0 0 4px rgba(239,68,68,0); }
            }

            /* Toast notifications */
            .notif-toast {
                background: #fff !important;
                border: none !important;
                border-radius: 14px !important;
                box-shadow: 0 10px 40px rgba(0,0,0,.15) !important;
                overflow: hidden;
                min-width: 300px;
            }

            .notif-toast-header {
                background: transparent !important;
                border-bottom: 1px solid #f3f4f6 !important;
                padding: .75rem 1rem !important;
            }

            .notif-toast-body {
                font-size: .85rem;
                color: #374151;
                padding: .75rem 1rem !important;
            }

            .notif-toast-icon {
                width: 28px;
                height: 28px;
                border-radius: 8px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: .8rem;
                margin-right: .5rem;
            }

            /* Group label */
            .notif-group-label {
                padding: .4rem 1.2rem;
                font-size: .7rem;
                font-weight: 700;
                color: #9ca3af;
                text-transform: uppercase;
                letter-spacing: .5px;
                background: #f9fafb;
            }
        `;
        document.head.appendChild(style);
    }

    createNotificationUI() {
        const navbar = document.querySelector('.navbar-nav.ms-auto');
        if (!navbar || document.getElementById('notifDropdown')) return;

        const html = `
            <li class="nav-item dropdown" id="notifNavItem">
                <a class="nav-link notif-bell-btn" href="#"
                   id="notifDropdown" role="button"
                   data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-bell" style="font-size:1.1rem;"></i>
                    <span class="notif-badge" id="notifBadge" style="display:none;">0</span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end notif-dropdown"
                    aria-labelledby="notifDropdown">

                    <li class="notif-header">
                        <div class="notif-header-title">
                            <i class="fas fa-bell"></i>
                            Thông báo
                            <span class="count-chip" id="notifHeaderCount" style="display:none;"></span>
                        </div>
                        <button class="btn-mark-all" id="markAllReadBtn">
                            <i class="fas fa-check-double me-1"></i>Đã đọc hết
                        </button>
                    </li>

                    <div class="notif-list" id="notifList">
                        <div class="notif-loading">
                            <div class="notif-spinner"></div>
                            Đang tải thông báo...
                        </div>
                    </div>

                    <li class="notif-footer">
                        <a href="booking-history.php">
                            <i class="fas fa-list-ul"></i>
                            Xem tất cả thông báo
                        </a>
                    </li>
                </ul>
            </li>
        `;

        const firstNavItem = navbar.querySelector('.nav-item');
        if (firstNavItem) firstNavItem.insertAdjacentHTML('beforebegin', html);
    }

    async loadInitialNotifications() {
        try {
            const res = await fetch('api/notifications.php?action=list&limit=10');
            const data = await res.json();
            if (data.success) {
                this.notifications = data.notifications;
                this.renderNotifications(data.notifications);
                this.updateBadge(data.unread_count);
            }
        } catch (err) {
            this.renderError();
        }
    }

    renderNotifications(list) {
        const container = document.getElementById('notifList');
        if (!container) return;

        if (!list || list.length === 0) {
            container.innerHTML = `
                <div class="notif-empty">
                    <div class="notif-empty-icon"><i class="fas fa-bell-slash"></i></div>
                    <p>Bạn chưa có thông báo nào</p>
                </div>
            `;
            return;
        }

        // Group by time
        const today    = [];
        const earlier  = [];
        const now      = new Date();

        list.forEach(n => {
            const d = new Date(n.sent_at);
            const diff = (now - d) / (1000 * 3600);
            if (diff < 24) today.push(n);
            else earlier.push(n);
        });

        let html = '';

        if (today.length) {
            html += `<div class="notif-group-label">Hôm nay</div>`;
            html += today.map(n => this.renderItem(n)).join('');
        }

        if (earlier.length) {
            html += `<div class="notif-group-label">Trước đó</div>`;
            html += earlier.map(n => this.renderItem(n)).join('');
        }

        container.innerHTML = html;
    }

    renderItem(n) {
        const iconClass = this.getIconClass(n.type);
        const iconColor = this.getIconColor(n.type);
        const unread    = !n.is_read;
        const time      = this.formatTime(n.sent_at);

        return `
            <div class="notif-item ${unread ? 'unread' : ''}"
                 data-id="${n.id}" data-unread="${unread ? '1' : '0'}">
                <div class="notif-icon-wrap notif-icon-${iconColor}">
                    <i class="fas ${iconClass}"></i>
                </div>
                <div class="notif-body">
                    <div class="notif-title">${this.esc(n.title)}</div>
                    <div class="notif-msg">${this.esc(n.message)}</div>
                    <div class="notif-time">
                        <i class="fas fa-clock" style="font-size:.65rem;"></i>
                        ${time}
                    </div>
                </div>
                ${unread ? '<div class="notif-unread-dot"></div>' : ''}
            </div>
        `;
    }

    renderError() {
        const container = document.getElementById('notifList');
        if (container) container.innerHTML = `
            <div class="notif-empty">
                <div class="notif-empty-icon"><i class="fas fa-wifi"></i></div>
                <p>Không thể tải thông báo</p>
            </div>
        `;
    }

    updateBadge(count) {
        const badge       = document.getElementById('notifBadge');
        const headerCount = document.getElementById('notifHeaderCount');

        if (!badge) return;

        if (count > 0) {
            const label = count > 99 ? '99+' : count;
            badge.textContent = label;
            badge.style.display = 'flex';
            if (headerCount) {
                headerCount.textContent = label;
                headerCount.style.display = 'inline-flex';
            }
        } else {
            badge.style.display = 'none';
            if (headerCount) headerCount.style.display = 'none';
        }
    }

    showToastNotification(n) {
        const iconClass = this.getIconClass(n.type);
        const iconColor = this.getIconColor(n.type);

        const colorMap = {
            success: '#059669', warning: '#d97706',
            danger: '#dc2626', info: '#2563eb', default: '#6b7280'
        };
        const bgMap = {
            success: '#d1fae5', warning: '#fef3c7',
            danger: '#fee2e2', info: '#dbeafe', default: '#f3f4f6'
        };
        const color = colorMap[iconColor] || colorMap.default;
        const bg    = bgMap[iconColor]    || bgMap.default;

        const toast = document.createElement('div');
        toast.className = 'toast notif-toast show';
        toast.setAttribute('role', 'alert');
        toast.innerHTML = `
            <div class="notif-toast-header d-flex align-items-center">
                <div class="notif-toast-icon" style="background:${bg};color:${color};">
                    <i class="fas ${iconClass}"></i>
                </div>
                <strong class="me-auto" style="font-size:.85rem;">${this.esc(n.title)}</strong>
                <small class="text-muted me-2" style="font-size:.72rem;">Vừa xong</small>
                <button type="button" class="btn-close" style="font-size:.7rem;"
                        onclick="this.closest('.toast').remove()"></button>
            </div>
            <div class="notif-toast-body">${this.esc(n.message)}</div>
        `;

        let container = document.getElementById('notifToastContainer');
        if (!container) {
            container = document.createElement('div');
            container.id = 'notifToastContainer';
            container.className = 'toast-container position-fixed';
            container.style.cssText = 'bottom:1.5rem;right:1.5rem;z-index:9999;display:flex;flex-direction:column;gap:.5rem;';
            document.body.appendChild(container);
        }

        container.appendChild(toast);
        setTimeout(() => toast.remove(), 5000);
    }

    startPolling() {
        if (this.isPolling) return;
        this.isPolling = true;
        this.pollingInterval = setInterval(() => this.pollForUpdates(), 30000);
    }

    stopPolling() {
        if (this.pollingInterval) clearInterval(this.pollingInterval);
        this.isPolling = false;
    }

    async pollForUpdates() {
        try {
            const ts  = this.lastTimestamp ? '&last_timestamp=' + encodeURIComponent(this.lastTimestamp) : '';
            const res = await fetch(`api/notifications.php?action=realtime${ts}`);
            const data = await res.json();
            if (data.success && data.notifications.length > 0) {
                data.notifications.forEach(n => this.showToastNotification(n));
                this.lastTimestamp = data.timestamp;
                this.loadInitialNotifications();
            }
        } catch (e) {}
    }

    bindEvents() {
        document.addEventListener('click', async e => {
            // Mark all read
            if (e.target.closest('#markAllReadBtn')) {
                e.preventDefault();
                e.stopPropagation();
                try {
                    await fetch('api/notifications.php?action=mark_all_read', { method: 'POST' });
                    document.querySelectorAll('.notif-item.unread').forEach(el => {
                        el.classList.remove('unread');
                        const dot = el.querySelector('.notif-unread-dot');
                        if (dot) dot.remove();
                    });
                    this.updateBadge(0);
                } catch (e) {}
            }

            // Mark single read
            const item = e.target.closest('.notif-item');
            if (item && item.dataset.unread === '1') {
                item.dataset.unread = '0';
                item.classList.remove('unread');
                const dot = item.querySelector('.notif-unread-dot');
                if (dot) dot.remove();

                const id = item.dataset.id;
                try {
                    const fd = new FormData();
                    fd.append('notification_id', id);
                    await fetch('api/notifications.php?action=mark_read', { method: 'POST', body: fd });
                    const badge = document.getElementById('notifBadge');
                    const cur   = parseInt(badge?.textContent) || 0;
                    this.updateBadge(Math.max(0, cur - 1));
                } catch (e) {}
            }
        });

        document.addEventListener('visibilitychange', () => {
            document.hidden ? this.stopPolling() : this.startPolling();
        });
    }

    getIconClass(type) {
        return {
            confirmation: 'fa-check-circle',
            reminder:     'fa-clock',
            cancellation: 'fa-times-circle',
            update:       'fa-info-circle',
            payment:      'fa-credit-card',
            booking:      'fa-calendar-check',
        }[type] || 'fa-bell';
    }

    getIconColor(type) {
        return {
            confirmation: 'success',
            reminder:     'warning',
            cancellation: 'danger',
            update:       'info',
            payment:      'success',
            booking:      'info',
        }[type] || 'default';
    }

    formatTime(ts) {
        const d    = new Date(ts);
        const now  = new Date();
        const diff = Math.floor((now - d) / 60000);
        if (diff < 1)    return 'Vừa xong';
        if (diff < 60)   return `${diff} phút trước`;
        if (diff < 1440) return `${Math.floor(diff / 60)} giờ trước`;
        if (diff < 10080) return `${Math.floor(diff / 1440)} ngày trước`;
        return d.toLocaleDateString('vi-VN');
    }

    esc(str) {
        const d = document.createElement('div');
        d.textContent = str || '';
        return d.innerHTML;
    }
}

document.addEventListener('DOMContentLoaded', function() {
    if (document.querySelector('.navbar-nav.ms-auto')) {
        window.notificationManager = new NotificationManager();
    }
});
