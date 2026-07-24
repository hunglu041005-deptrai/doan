/**
 * payment-polling.js
 * Thanh toán tự động qua SePay webhook + polling
 * Dùng chung cho: Đặt sân, Shop, Khóa học, Hội viên
 */

window.PaymentPolling = (function () {

    let _timer     = null;
    let _done      = false;
    let _attempts  = 0;
    const MAX_ATTEMPTS = 72; // 6 phút (72 × 5s)

    // ── Hiển thị panel QR + thông tin CK ──────────────────────────────────
    function showQRPanel(opts) {
        // opts: { method, amount, ref, panelId, qrId, refId, amountId }
        const { method, amount, ref, panelId, qrId, refId, amountId } = opts;
        const enc = encodeURIComponent(ref);

        const panel = document.getElementById(panelId);
        if (panel) panel.style.display = 'block';

        const qrEl = document.getElementById(qrId);
        if (qrEl) {
            if (method === 'momo') {
                qrEl.src = `https://img.vietqr.io/image/MOMO-0968073500-qr_only.png?amount=${amount}&addInfo=${enc}&accountName=LU+DANG+HUNG`;
            } else {
                qrEl.src = `https://img.vietqr.io/image/MB-7369786789-qr_only.png?amount=${amount}&addInfo=${enc}&accountName=LU+DANG+HUNG`;
            }
        }
        if (refId)    { const el = document.getElementById(refId);    if (el) el.textContent = ref; }
        if (amountId) { const el = document.getElementById(amountId); if (el) el.textContent = parseInt(amount).toLocaleString('vi-VN') + 'đ'; }
    }

    // ── Hiện hộp chờ tự động ──────────────────────────────────────────────
    function showWaitingBox(containerId, ref) {
        const box = document.getElementById(containerId);
        if (!box) return;
        box.innerHTML = `
            <div style="background:#f0fdf4;border:1.5px solid #86efac;border-radius:12px;padding:1rem 1.2rem;margin-top:1rem;">
                <div style="font-weight:700;color:#166534;margin-bottom:.4rem;">
                    <i class="fas fa-circle-notch fa-spin me-1"></i>Đang chờ thanh toán tự động
                </div>
                <div style="font-size:.84rem;color:#15803d;margin-bottom:.35rem;">
                    Chuyển khoản nội dung: <strong style="font-family:monospace;background:#dcfce7;padding:2px 7px;border-radius:4px;">${ref}</strong>
                </div>
                <div style="font-size:.78rem;color:#166534;">
                    <i class="fas fa-magic me-1"></i>Hệ thống <strong>tự động xác nhận</strong> khi nhận được tiền.
                </div>
                <div style="margin-top:.6rem;height:4px;background:#dcfce7;border-radius:4px;overflow:hidden;">
                    <div id="pp_progressBar" style="height:100%;background:#16a34a;width:0%;transition:width 5s linear;"></div>
                </div>
            </div>`;
        box.style.display = 'block';
        setTimeout(() => {
            const b = document.getElementById('pp_progressBar');
            if (b) b.style.width = '100%';
        }, 100);
    }

    // ── Bắt đầu polling ───────────────────────────────────────────────────
    function start(apiParam, onSuccess, waitingBoxId) {
        if (_timer) clearInterval(_timer);
        _done = false;
        _attempts = 0;

        _timer = setInterval(() => {
            if (_done) { clearInterval(_timer); return; }
            _attempts++;

            // Reset progress bar
            const bar = document.getElementById('pp_progressBar');
            if (bar) {
                bar.style.transition = 'none';
                bar.style.width = '0%';
                setTimeout(() => { bar.style.transition = 'width 5s linear'; bar.style.width = '100%'; }, 50);
            }

            // Gọi API check
            fetch('api/check-payment-status.php?' + apiParam)
                .then(r => r.json())
                .then(data => {
                    if (!data.paid) return;
                    _done = true;
                    clearInterval(_timer);
                    _timer = null;
                    if (waitingBoxId) {
                        const box = document.getElementById(waitingBoxId);
                        if (box) box.style.display = 'none';
                    }
                    if (onSuccess) onSuccess(data);
                })
                .catch(() => {});

            // Hết giờ
            if (_attempts >= MAX_ATTEMPTS) {
                clearInterval(_timer);
                _timer = null;
                const box = document.getElementById(waitingBoxId);
                if (box) {
                    box.innerHTML = `<div style="background:#fff7ed;border:1.5px solid #fed7aa;border-radius:12px;padding:1rem;color:#9a3412;font-size:.85rem;margin-top:1rem;">
                        <i class="fas fa-exclamation-triangle me-1"></i> Hết thời gian chờ. Nếu đã chuyển khoản, vui lòng liên hệ hỗ trợ.
                    </div>`;
                }
            }
        }, 5000);
    }

    function stop() {
        if (_timer) { clearInterval(_timer); _timer = null; }
        _done = true;
    }

    // ── Hiện modal / thông báo thành công chung ───────────────────────────
    function showSuccessToast(message, redirectUrl, delay) {
        // Toast thành công
        const toast = document.createElement('div');
        toast.style.cssText = `
            position:fixed;top:20px;right:20px;z-index:9999;
            background:#10b981;color:#fff;padding:1rem 1.5rem;
            border-radius:14px;box-shadow:0 8px 30px rgba(0,0,0,.15);
            display:flex;align-items:center;gap:.8rem;min-width:280px;
            animation:slideIn .3s ease;font-weight:600;`;
        toast.innerHTML = `
            <i class="fas fa-check-circle fa-lg"></i>
            <div>
                <div>${message}</div>
                <div style="font-size:.78rem;font-weight:400;opacity:.85;margin-top:.2rem;">
                    Tự động chuyển trang sau ${Math.round((delay||2000)/1000)} giây...
                </div>
            </div>`;
        document.body.appendChild(toast);

        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transition = 'opacity .3s';
            setTimeout(() => {
                if (toast.parentNode) toast.parentNode.removeChild(toast);
                if (redirectUrl) window.location.href = redirectUrl;
            }, 300);
        }, delay || 2000);
    }

    return { start, stop, showQRPanel, showWaitingBox, showSuccessToast };
})();
