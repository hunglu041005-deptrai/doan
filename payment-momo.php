<?php
/**
 * MoMo Payment Handler - Intermediate page
 * Handles MoMo payment request and redirects to MoMo gateway
 */

require_once __DIR__ . '/includes/functions.php';

$booking_id = intval($_GET['booking_id'] ?? 0);
if (!$booking_id || !isset($_SESSION['momo_data'])) {
    redirect('index.php');
}

$momo_data = $_SESSION['momo_data'];
unset($_SESSION['momo_data']);

// In production, POST to MoMo API
// For development, show payment form
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh toán MoMo - BadmintonPro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #0d6efd 0%, #6610f2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .payment-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            max-width: 500px;
            width: 100%;
            padding: 40px;
        }
        .momo-logo {
            text-align: center;
            margin-bottom: 30px;
        }
        .momo-logo img {
            max-width: 150px;
            height: auto;
        }
        .payment-info {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .payment-info p {
            margin: 10px 0;
            font-size: 14px;
        }
        .amount {
            font-size: 28px;
            font-weight: bold;
            color: #0d6efd;
            text-align: center;
            padding: 15px 0;
        }
        .btn-momo {
            background: #a62db5;
            border: none;
            width: 100%;
            padding: 12px;
            font-size: 16px;
            margin-top: 20px;
        }
        .btn-momo:hover {
            background: #9026ad;
            color: white;
        }
        .note {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            border-radius: 4px;
            margin-top: 20px;
            font-size: 13px;
            color: #856404;
        }
    </style>
</head>
<body>
    <div class="payment-container">
        <!-- MoMo Logo -->
        <div class="momo-logo">
            <svg width="120" height="120" viewBox="0 0 120 120" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="60" cy="60" r="55" fill="#a62db5" opacity="0.1"/>
                <circle cx="60" cy="60" r="50" fill="#a62db5"/>
                <text x="60" y="75" font-size="48" font-weight="bold" fill="white" text-anchor="middle">M</text>
            </svg>
        </div>

        <h3 class="text-center mb-4">Thanh toán qua MoMo</h3>

        <div class="payment-info">
            <p><strong>Đơn đặt sân:</strong> #{$booking_id}</p>
            <p><strong>Phương thức:</strong> <img src="https://api.momo.vn/favicon.ico" width="16"> MoMo</p>
        </div>

        <div class="amount">
            <?php echo number_format($momo_data['amount']) ?> VND
        </div>

        <p class="text-muted text-center" style="font-size: 14px; margin: 15px 0;">
            Bạn sẽ được chuyển hướng đến trang thanh toán MoMo
        </p>

        <!-- In production, this would POST to MoMo API endpoint -->
        <button class="btn btn-momo btn-lg rounded-3" onclick="submitPayment()">
            <i class="fas fa-mobile-alt"></i> Xác nhận thanh toán
        </button>

        <div class="note">
            <strong>ℹ️ Ghi chú:</strong> Bạn sẽ được chuyển đến ứng dụng MoMo để hoàn tất thanh toán. 
            Hãy xác nhận giao dịch trong ứng dụng MoMo của bạn.
        </div>

        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee;">
            <a href="booking-history.php" class="text-decoration-none text-muted">← Quay lại lịch sử đặt sân</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function submitPayment() {
            // In production, submit to MoMo API
            // For now, show a simulation
            alert('✓ Gửi yêu cầu thanh toán đến MoMo...');
            
            // Create form for MoMo API submission (in production)
            // const form = new FormData();
            // form.append('partnerCode', 'MOMOXXXXXX');
            // form.append('orderId', '<?php echo $momo_data['orderId']; ?>');
            // form.append('amount', '<?php echo $momo_data['amount']; ?>');
            // etc...
            
            // For development, redirect to callback after delay
            setTimeout(() => {
                window.location.href = 'payment-momo-callback.php?booking_id=<?php echo $booking_id; ?>&status=success';
            }, 1500);
        }
    </script>
</body>
</html>
