<?php
require_once __DIR__ . '/includes/functions.php';
blockAdminFromPublic();

if (!isLoggedIn()) {
    header('Location: login.php?redirect=checkout.php');
    exit;
}

// ── Auto-migrate orders table ──
$mysqli->query("CREATE TABLE IF NOT EXISTS orders (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT NOT NULL,
    order_number    VARCHAR(30) UNIQUE NOT NULL,
    status          VARCHAR(30) NOT NULL DEFAULT 'pending',
    payment_status  VARCHAR(30) NOT NULL DEFAULT 'pending',
    payment_method  VARCHAR(50) DEFAULT 'cod',
    subtotal        DECIMAL(10,0) NOT NULL DEFAULT 0,
    shipping_amount DECIMAL(10,0) NOT NULL DEFAULT 0,
    discount_amount DECIMAL(10,0) NOT NULL DEFAULT 0,
    total_amount    DECIMAL(10,0) NOT NULL DEFAULT 0,
    customer_name   VARCHAR(100) NOT NULL DEFAULT '',
    customer_email  VARCHAR(150) NOT NULL DEFAULT '',
    customer_phone  VARCHAR(20)  NOT NULL DEFAULT '',
    shipping_province VARCHAR(100),
    shipping_district VARCHAR(100),
    shipping_ward     VARCHAR(100),
    shipping_address  TEXT,
    notes           TEXT,
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user   (user_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$mysqli->query("CREATE TABLE IF NOT EXISTS order_items (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    order_id        INT NOT NULL,
    product_id      INT NOT NULL,
    product_name    VARCHAR(200) NOT NULL,
    product_image   VARCHAR(255),
    product_price   DECIMAL(10,0) NOT NULL DEFAULT 0,
    quantity        INT NOT NULL DEFAULT 1,
    subtotal        DECIMAL(10,0) NOT NULL DEFAULT 0,
    INDEX idx_order   (order_id),
    INDEX idx_product (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// ── Thêm tất cả cột còn thiếu (orders) ──
$orderCols = [
    'payment_method'    => "ALTER TABLE orders ADD COLUMN payment_method VARCHAR(50) DEFAULT 'cod'",
    'customer_name'     => "ALTER TABLE orders ADD COLUMN customer_name VARCHAR(100) NOT NULL DEFAULT ''",
    'customer_email'    => "ALTER TABLE orders ADD COLUMN customer_email VARCHAR(150) NOT NULL DEFAULT ''",
    'customer_phone'    => "ALTER TABLE orders ADD COLUMN customer_phone VARCHAR(20) NOT NULL DEFAULT ''",
    'subtotal'          => "ALTER TABLE orders ADD COLUMN subtotal DECIMAL(10,0) NOT NULL DEFAULT 0",
    'shipping_amount'   => "ALTER TABLE orders ADD COLUMN shipping_amount DECIMAL(10,0) NOT NULL DEFAULT 0",
    'discount_amount'   => "ALTER TABLE orders ADD COLUMN discount_amount DECIMAL(10,0) NOT NULL DEFAULT 0",
    'shipping_province' => "ALTER TABLE orders ADD COLUMN shipping_province VARCHAR(100)",
    'shipping_district' => "ALTER TABLE orders ADD COLUMN shipping_district VARCHAR(100)",
    'shipping_ward'     => "ALTER TABLE orders ADD COLUMN shipping_ward VARCHAR(100)",
    'shipping_address'  => "ALTER TABLE orders ADD COLUMN shipping_address TEXT",
    'notes'             => "ALTER TABLE orders ADD COLUMN notes TEXT",
    'total_amount'      => "ALTER TABLE orders ADD COLUMN total_amount DECIMAL(10,0) NOT NULL DEFAULT 0",
];
foreach ($orderCols as $col => $sql) {
    $r = $mysqli->query("SHOW COLUMNS FROM orders LIKE '$col'");
    if ($r && $r->num_rows === 0) $mysqli->query($sql);
}

// ── Thêm cột còn thiếu (order_items) ──
$itemCols = [
    'product_image' => "ALTER TABLE order_items ADD COLUMN product_image VARCHAR(255)",
    'product_price' => "ALTER TABLE order_items ADD COLUMN product_price DECIMAL(10,0) NOT NULL DEFAULT 0",
];
foreach ($itemCols as $col => $sql) {
    $r = $mysqli->query("SHOW COLUMNS FROM order_items LIKE '$col'");
    if ($r && $r->num_rows === 0) $mysqli->query($sql);
}

$order_success   = false;
$order_number    = '';
$order_id_result = null;
$error_message   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $cart_data = json_decode($_POST['cart_data'] ?? '[]', true);

    $name     = trim($_POST['customer_name']  ?? '');
    $phone    = trim($_POST['customer_phone'] ?? '');
    $province = trim($_POST['province']       ?? '');
    $district = trim($_POST['district']       ?? '');
    $ward     = trim($_POST['ward']           ?? '');
    $addr     = trim($_POST['address_detail'] ?? '');
    $note     = trim($_POST['order_note']     ?? '');
    $method   = trim($_POST['payment_method'] ?? 'cod');
    $email    = $_SESSION['email'] ?? '';

    $full_address = implode(', ', array_filter([$addr, $ward, $district, $province]));

    if (!$name || !$phone || !$province || !$district || !$addr) {
        $error_message = 'Vui lòng điền đầy đủ thông tin giao hàng.';
    } elseif (empty($cart_data)) {
        $error_message = 'Giỏ hàng trống.';
    } else {
        try {
            $subtotal = 0;
            foreach ($cart_data as $item) {
                $subtotal += ($item['price'] ?? 0) * ($item['quantity'] ?? 1);
            }
            $shipping = $subtotal >= 500000 ? 0 : 30000;
            $total    = $subtotal + $shipping;

            // Unique order number
            do {
                $order_number = 'ORD' . date('ymd') . strtoupper(substr(md5(uniqid(rand(),true)), 0, 5));
                $chk = $mysqli->prepare('SELECT id FROM orders WHERE order_number = ?');
                $chk->bind_param('s', $order_number);
                $chk->execute(); $chk->store_result();
                $exists = $chk->num_rows > 0;
                $chk->close();
            } while ($exists);

            $mysqli->begin_transaction();

            $ins = $mysqli->prepare(
                'INSERT INTO orders
                 (user_id, order_number, status, payment_status, payment_method,
                  subtotal, shipping_amount, discount_amount, total_amount,
                  customer_name, customer_email, customer_phone,
                  shipping_province, shipping_district, shipping_ward, shipping_address, notes)
                 VALUES (?,?,?,?,?,?,?,0,?,?,?,?,?,?,?,?,?)'
            );

            $status  = 'pending';
            $pstatus = ($method === 'cod') ? 'unpaid' : 'pending';

            $ins->bind_param(
                'issssiiissssssss',
                $_SESSION['user_id'], $order_number,
                $status, $pstatus, $method,
                $subtotal, $shipping, $total,
                $name, $email, $phone,
                $province, $district, $ward, $full_address, $note
            );

            if ($ins->execute()) {
                $order_id_result = $mysqli->insert_id;

                $item_stmt = $mysqli->prepare(
                    'INSERT INTO order_items (order_id, product_id, product_name, product_image, product_price, quantity, subtotal)
                     VALUES (?,?,?,?,?,?,?)'
                );

                foreach ($cart_data as $item) {
                    $pid      = (int)($item['id']       ?? 0);
                    $pname    = $item['name']    ?? '';
                    $pimage   = $item['image']   ?? '';
                    $pprice   = (int)($item['price']    ?? 0);
                    $qty      = (int)($item['quantity'] ?? 1);
                    $psub     = $pprice * $qty;

                    $item_stmt->bind_param('iissiii', $order_id_result, $pid, $pname, $pimage, $pprice, $qty, $psub);
                    $item_stmt->execute();

                    // Deduct stock with lock
                    $stock = $mysqli->prepare(
                        'UPDATE products SET stock_quantity = stock_quantity - ?
                         WHERE id = ? AND stock_quantity >= ?'
                    );
                    $stock->bind_param('iii', $qty, $pid, $qty);
                    $stock->execute();
                    $stock->close();
                }
                $item_stmt->close();
                $mysqli->commit();
                $order_success = true;

                // Xử lý theo phương thức thanh toán
                if ($method === 'bank' || $method === 'vnpay' || $method === 'momo') {
                    // Chuyển khoản / MoMo: đánh dấu chờ xác minh thủ công
                    $upd = $mysqli->prepare("UPDATE orders SET payment_status='pending_verification' WHERE id=?");
                    $upd->bind_param('i', $order_id_result);
                    $upd->execute(); $upd->close();
                }

            } else {
                $mysqli->rollback();
                $error_message = 'Lỗi tạo đơn hàng: ' . $mysqli->error;
            }
            $ins->close();

        } catch (Exception $e) {
            $mysqli->rollback();
            $error_message = 'Lỗi: ' . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<style>
/* ===== CHECKOUT PAGE ===== */
.co-page { background: #f8fafc; min-height: 100vh; padding: 2rem 0 4rem; }

/* Breadcrumb */
.co-breadcrumb { font-size:.82rem; color:#9ca3af; margin-bottom:2rem; display:flex; align-items:center; gap:.4rem; }
.co-breadcrumb a { color:#6366f1; text-decoration:none; }

/* Steps */
.co-steps { display:flex; align-items:center; background:#fff; border-radius:16px; padding:1rem 1.5rem;
            box-shadow:0 2px 12px rgba(0,0,0,.06); margin-bottom:2rem; }
.co-step { display:flex; align-items:center; gap:.6rem; flex:1; }
.co-step-dot { width:32px; height:32px; border-radius:50%; display:flex; align-items:center;
               justify-content:center; font-weight:800; font-size:.85rem; flex-shrink:0; }
.co-step-dot.done   { background:#10b981; color:#fff; }
.co-step-dot.active { background:linear-gradient(135deg,#6366f1,#8b5cf6); color:#fff;
                      box-shadow:0 4px 12px rgba(99,102,241,.4); }
.co-step-dot.idle   { background:#e5e7eb; color:#9ca3af; }
.co-step-label      { font-size:.8rem; font-weight:700; color:#374151; }
.co-step-line       { flex-grow:1; height:2px; background:#e5e7eb; max-width:60px; }
.co-step-line.done  { background:#10b981; }

/* Cards */
.co-card { background:#fff; border-radius:20px; box-shadow:0 4px 20px rgba(0,0,0,.06);
           border:1px solid #f0f0f0; overflow:hidden; margin-bottom:1.5rem; }
.co-card-header { padding:1.2rem 1.5rem; border-bottom:1px solid #f3f4f6;
                  font-weight:800; color:#111827; display:flex; align-items:center; gap:.6rem; }
.co-card-header i { color:#6366f1; }
.co-card-body { padding:1.5rem; }

/* Form */
.co-label { font-size:.82rem; font-weight:700; color:#374151; margin-bottom:.35rem; display:block; }
.co-label span { color:#ef4444; margin-left:2px; }
.co-input, .co-select, .co-textarea {
    width:100%; background:#f9fafb; border:1.5px solid #e5e7eb; border-radius:12px;
    padding:.7rem 1rem; font-size:.9rem; color:#111827; transition:all .2s;
}
.co-input:focus, .co-select:focus, .co-textarea:focus {
    outline:none; border-color:#6366f1; background:#fff;
    box-shadow:0 0 0 3px rgba(99,102,241,.12);
}
.co-textarea { resize:none; }
.co-select { appearance:none; background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236b7280' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
             background-repeat:no-repeat; background-position:right 1rem center; }

/* Payment methods */
.pm-card {
    border: 2px solid #e5e7eb;
    border-radius: 16px;
    padding: 1rem 1.2rem;
    cursor: pointer;
    transition: all .25s ease;
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: .75rem;
    user-select: none;
    background: #fff;
    position: relative;
    box-shadow: 0 2px 8px rgba(0,0,0,.04);
}
.pm-card:hover {
    border-color: #28a745;
    background: #f6fff8;
    box-shadow: 0 4px 16px rgba(40,167,69,.1);
    transform: translateY(-1px);
}
.pm-card.active {
    border-color: #28a745;
    background: linear-gradient(135deg, #f0fdf4, #e8fdf0);
    box-shadow: 0 4px 20px rgba(40,167,69,.15);
}
.pm-card.active .pm-title { color: #16a34a !important; }
.pm-icon-wrap {
    width: 50px; height: 50px;
    border-radius: 14px;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.pm-text { flex: 1; min-width: 0; }
.pm-title { font-weight: 700; font-size: .93rem; color: #1a1a2e; margin-bottom: 2px; transition: color .2s; }
.pm-sub   { font-size: .78rem; color: #6b7280; }
.pm-check-wrap {
    width: 26px; height: 26px; border-radius: 50%;
    background: #28a745; color: #fff;
    display: flex; align-items: center; justify-content: center;
    font-size: .8rem;
    opacity: 0; transform: scale(.5);
    transition: all .2s ease; flex-shrink: 0;
}
.pm-card.active .pm-check-wrap { opacity: 1; transform: scale(1); }

/* Bank/MoMo transfer detail panels */
#bankDetail, #momoDetail {
    display: none;
    animation: slideDown .2s ease;
}
@keyframes slideDown {
    from { opacity:0; transform:translateY(-8px); }
    to   { opacity:1; transform:translateY(0); }
}
#bankDetail .bank-ref, #momoDetail .bank-ref { font-family:monospace; font-weight:800;
                        font-size:1rem; letter-spacing:1px; }

/* Order summary */
.os-item { display:flex; align-items:center; gap:.8rem; padding:.6rem 0;
           border-bottom:1px solid #f3f4f6; }
.os-item:last-child { border:none; }
.os-item img { width:48px; height:48px; object-fit:cover; border-radius:10px;
               border:1px solid #f0f0f0; flex-shrink:0; }
.os-item-name { font-weight:700; font-size:.85rem; color:#111827; }
.os-item-meta { font-size:.75rem; color:#9ca3af; }
.os-total-row { display:flex; justify-content:space-between; align-items:center;
                padding:.45rem 0; font-size:.88rem; }
.os-total-row.final { font-weight:800; font-size:1rem; color:#111827;
                      border-top:2px solid #f3f4f6; margin-top:.5rem; padding-top:.8rem; }
.os-total-row .val { font-weight:700; }
.os-total-row .free { color:#10b981; font-weight:700; }
.os-total-row.final .val { color:#ef4444; font-size:1.1rem; }

/* CTA */
.btn-place-order {
    background:linear-gradient(135deg,#6366f1,#8b5cf6);
    color:#fff; border:none; border-radius:14px; padding:1rem 2rem;
    font-weight:900; font-size:1rem; width:100%; cursor:pointer;
    box-shadow:0 8px 25px rgba(99,102,241,.35); transition:all .2s;
}
.btn-place-order:hover { transform:translateY(-2px); box-shadow:0 12px 35px rgba(99,102,241,.45); }
.btn-place-order:disabled { opacity:.6; cursor:not-allowed; transform:none; }

/* Policy */
.policy-item { display:flex; align-items:center; gap:.7rem; padding:.4rem 0;
               font-size:.82rem; color:#4b5563; border-bottom:1px solid #f9fafb; }
.policy-item:last-child { border:none; }
.policy-icon { width:32px; height:32px; border-radius:8px; display:flex; align-items:center;
               justify-content:center; flex-shrink:0; }

/* Success */
.success-banner { background:linear-gradient(135deg,#0f0c29,#1e3a5f); border-radius:20px;
                  padding:2.5rem; text-align:center; color:#fff; }
.success-icon { width:80px; height:80px; background:linear-gradient(135deg,#10b981,#059669);
                border-radius:50%; display:flex; align-items:center; justify-content:center;
                margin:0 auto 1.2rem; box-shadow:0 8px 25px rgba(16,185,129,.4); font-size:2rem; color:#fff; }
.order-info-grid { display:grid; grid-template-columns:1fr 1fr; gap:.6rem; margin-top:1.2rem; text-align:left; }
.order-info-item { background:rgba(255,255,255,.08); border-radius:10px; padding:.7rem 1rem; }
.order-info-item .lbl { font-size:.68rem; color:rgba(255,255,255,.5); text-transform:uppercase; }
.order-info-item .val { font-weight:700; font-size:.88rem; }

/* Address section */
.addr-grid { display:grid; grid-template-columns:1fr 1fr 1fr; gap:.8rem; margin-bottom:.8rem; }
@media(max-width:640px) { .addr-grid { grid-template-columns:1fr; } }

/* Empty cart */
.empty-cart-state { text-align:center; padding:2.5rem 1rem; }
.empty-cart-state i { font-size:3rem; color:#d1d5db; margin-bottom:1rem; display:block; }
</style>

<div class="co-page">
<div class="container-lg">

    <!-- Breadcrumb -->
    <div class="co-breadcrumb">
        <a href="index.php"><i class="fas fa-home"></i></a>
        <i class="fas fa-chevron-right" style="font-size:.65rem;"></i>
        <a href="equipment.php">Shop</a>
        <i class="fas fa-chevron-right" style="font-size:.65rem;"></i>
        <span>Thanh toán</span>
    </div>

    <!-- Steps -->
    <div class="co-steps">
        <div class="co-step">
            <div class="co-step-dot done"><i class="fas fa-check"></i></div>
            <div class="co-step-label">Giỏ hàng</div>
        </div>
        <div class="co-step-line done"></div>
        <div class="co-step">
            <div class="co-step-dot <?php echo $order_success ? 'done' : 'active'; ?>">
                <?php echo $order_success ? '<i class="fas fa-check"></i>' : '2'; ?>
            </div>
            <div class="co-step-label">Thanh toán</div>
        </div>
        <div class="co-step-line <?php echo $order_success ? 'done' : ''; ?>"></div>
        <div class="co-step">
            <div class="co-step-dot <?php echo $order_success ? 'done' : 'idle'; ?>">
                <?php echo $order_success ? '<i class="fas fa-check"></i>' : '3'; ?>
            </div>
            <div class="co-step-label <?php echo $order_success ? '' : 'text-muted'; ?>">Xác nhận</div>
        </div>
    </div>

    <?php if ($order_success): ?>
    <!-- ===== SUCCESS STATE ===== -->
    <div class="success-banner mb-4">
        <div class="success-icon"><i class="fas fa-check"></i></div>
        <h3 class="fw-bold mb-1">Đặt hàng thành công!</h3>
        <p style="color:rgba(255,255,255,.65);margin:.3rem 0 1rem;">Cảm ơn bạn đã tin tưởng Hưng Dũng Shop</p>
        <div class="order-info-grid">
            <div class="order-info-item">
                <div class="lbl">Mã đơn hàng</div>
                <div class="val" style="font-family:monospace;color:#67e8f9;font-size:1rem;letter-spacing:1px;">
                    #<?php echo escape($order_number); ?>
                </div>
            </div>
            <div class="order-info-item">
                <div class="lbl">Trạng thái</div>
                <div class="val" id="orderStatusLabel" style="color:#4ade80;">Đang xử lý</div>
            </div>
            <div class="order-info-item">
                <div class="lbl">Thanh toán</div>
                <div class="val">
                    <?php
                    $pm = $_POST['payment_method'] ?? 'cod';
                    $pm_labels = ['cod'=>'Tiền mặt (COD)','bank'=>'MB Bank','momo'=>'Ví MoMo','vnpay'=>'MB Bank'];
                    echo $pm_labels[$pm] ?? 'COD';
                    ?>
                </div>
            </div>
            <div class="order-info-item">
                <div class="lbl">Giao hàng dự kiến</div>
                <div class="val">2–3 ngày làm việc</div>
            </div>
        </div>
    </div>

    <?php if (in_array($_POST['payment_method'] ?? 'cod', ['bank','vnpay','momo'])): ?>
    <?php
    $pm_order  = $_POST['payment_method'] ?? 'bank';
    $is_momo   = ($pm_order === 'momo');
    $order_ref = $order_number; // dùng order_number làm nội dung CK
    // Lấy total từ DB
    $tot_stmt = $mysqli->prepare('SELECT total_amount FROM orders WHERE id=? LIMIT 1');
    $tot_stmt->bind_param('i', $order_id_result);
    $tot_stmt->execute();
    $tot_row = $tot_stmt->get_result()->fetch_assoc();
    $tot_stmt->close();
    $order_total = (int)($tot_row['total_amount'] ?? 0);
    $qr_enc = urlencode($order_ref);
    $qr_url = $is_momo
        ? "https://img.vietqr.io/image/MOMO-0968073500-qr_only.png?amount={$order_total}&addInfo={$qr_enc}&accountName=LU+DANG+HUNG"
        : "https://img.vietqr.io/image/MB-7369786789-qr_only.png?amount={$order_total}&addInfo={$qr_enc}&accountName=LU+DANG+HUNG";
    ?>
    <!-- Status + polling UI -->
    <div id="shopStatusBox" style="margin-bottom:1rem;">
        <div style="background:#f0fdf4;border:1.5px solid #86efac;border-radius:12px;padding:.8rem 1rem;">
            <div style="display:flex;align-items:center;gap:.6rem;color:#166534;font-weight:700;font-size:.88rem;margin-bottom:.4rem;">
                <i class="fas fa-circle-notch fa-spin"></i>
                <span>Đang chờ xác nhận thanh toán tự động...</span>
            </div>
            <div style="height:4px;background:#dcfce7;border-radius:4px;overflow:hidden;">
                <div id="shopProgressBar" style="height:100%;background:#16a34a;width:0%;transition:width 5s linear;"></div>
            </div>
            <div style="display:flex;justify-content:space-between;margin-top:.4rem;font-size:.75rem;color:#15803d;">
                <span id="shopAttemptText">Bắt đầu kiểm tra...</span>
                <span id="shopCountdown">Còn 6:00</span>
            </div>
        </div>
    </div>

    <div style="background:<?php echo $is_momo ? '#fdf2f8' : '#fffbeb'; ?>;border:1px solid <?php echo $is_momo ? '#f9a8d4' : '#fde68a'; ?>;border-radius:16px;padding:1.2rem 1.5rem;margin-bottom:1.5rem;">
        <div class="fw-bold mb-3" style="color:<?php echo $is_momo ? '#be185d' : '#92400e'; ?>;">
            <i class="fas fa-<?php echo $is_momo ? 'wallet' : 'university'; ?> me-2"></i>
            Chuyển khoản để xác nhận đơn hàng – hệ thống tự động xác nhận
        </div>
        <div class="d-flex gap-3 align-items-start">
            <img src="<?php echo $qr_url; ?>" alt="QR"
                 style="width:130px;height:130px;border-radius:10px;border:2px solid <?php echo $is_momo ? '#f9a8d4' : '#fde68a'; ?>;padding:3px;background:#fff;flex-shrink:0;">
            <div style="font-size:.87rem;display:grid;gap:.4rem;">
                <?php if ($is_momo): ?>
                <div><span style="color:#78716c;min-width:120px;display:inline-block;">Số điện thoại</span> <strong style="color:#db2777;font-family:monospace;">0968073500</strong></div>
                <?php else: ?>
                <div><span style="color:#78716c;min-width:120px;display:inline-block;">Ngân hàng</span> <strong>MB Bank</strong></div>
                <div><span style="color:#78716c;min-width:120px;display:inline-block;">Số tài khoản</span> <strong style="font-family:monospace;color:#6366f1;">7369786789</strong></div>
                <?php endif; ?>
                <div><span style="color:#78716c;min-width:120px;display:inline-block;">Chủ tài khoản</span> <strong>LU DANG HUNG</strong></div>
                <div><span style="color:#78716c;min-width:120px;display:inline-block;">Số tiền</span> <strong style="color:<?php echo $is_momo ? '#db2777' : '#6366f1'; ?>"><?php echo number_format($order_total); ?>đ</strong></div>
                <div style="display:flex;align-items:center;gap:.4rem;">
                    <span style="color:#78716c;min-width:120px;display:inline-block;">Nội dung CK</span>
                    <code style="background:#fff;border:1.5px solid <?php echo $is_momo ? '#db2777' : '#6366f1'; ?>;border-radius:6px;padding:2px 8px;font-weight:700;color:<?php echo $is_momo ? '#db2777' : '#6366f1'; ?>;font-size:.88rem;"><?php echo escape($order_ref); ?></code>
                    <button id="btnCopyShopRef" onclick="copyShopRef('<?php echo escape($order_ref); ?>')"
                            style="background:<?php echo $is_momo ? '#db2777' : '#6366f1'; ?>;color:#fff;border:none;border-radius:5px;padding:3px 7px;font-size:.7rem;cursor:pointer;">
                        <i class="fas fa-copy"></i>
                    </button>
                </div>
            </div>
        </div>
        <div style="margin-top:.8rem;background:<?php echo $is_momo ? '#fce7f3' : '#fef9c3'; ?>;border-radius:8px;padding:.5rem .8rem;font-size:.78rem;color:<?php echo $is_momo ? '#9d174d' : '#854d0e'; ?>;">
            <i class="fas fa-magic me-1"></i> Hệ thống <strong>tự động xác nhận</strong> khi nhận được tiền – không cần làm thêm gì.
        </div>
        <!-- Nút kiểm tra thủ công -->
        <div style="margin-top:.8rem;display:flex;gap:.6rem;">
            <button id="btnShopManualCheck" onclick="manualCheckShopOrder()"
                    style="flex:1;background:#f8fafc;border:1.5px solid #e2e8f0;border-radius:10px;padding:.55rem;font-size:.83rem;font-weight:600;color:#374151;cursor:pointer;">
                <i class="fas fa-sync-alt me-1"></i> Kiểm tra ngay
            </button>
        </div>
    </div>

    <script>
    // Polling tự động cho shop order — tự implement không phụ thuộc PaymentPolling
    (function() {
        const ordId  = <?php echo (int)$order_id_result; ?>;
        const ordNum = '<?php echo addslashes($order_number); ?>';
        const isMomo = <?php echo $is_momo ? 'true' : 'false'; ?>;
        const color  = isMomo ? '#db2777' : '#4f46e5';

        let _timer = null, _done = false, attempts = 0;
        const MAX = 72; // 6 phút
        let remaining = 360;

        // Countdown
        const countdown = setInterval(() => {
            remaining--;
            const el = document.getElementById('shopCountdown');
            if (el) {
                const m = Math.floor(remaining/60), s = remaining%60;
                el.textContent = `Còn ${m}:${String(s).padStart(2,'0')}`;
            }
            if (remaining <= 0) clearInterval(countdown);
        }, 1000);

        // Polling
        _timer = setInterval(() => {
            if (_done) { clearInterval(_timer); clearInterval(countdown); return; }
            attempts++;

            const bar = document.getElementById('shopProgressBar');
            if (bar) {
                bar.style.transition = 'none'; bar.style.width = '0%';
                setTimeout(() => { bar.style.transition = 'width 5s linear'; bar.style.width = '100%'; }, 50);
            }
            const at = document.getElementById('shopAttemptText');
            if (at) at.textContent = `Lần kiểm tra ${attempts}/${MAX}`;

            fetch(`api/check-payment-status.php?order_id=${ordId}`)
                .then(r => r.json())
                .then(data => {
                    if (!data.paid) return;
                    _done = true; clearInterval(_timer); clearInterval(countdown);

                    // Update UI
                    const statusEl = document.getElementById('orderStatusLabel');
                    if (statusEl) { statusEl.textContent = '✓ Đã thanh toán'; statusEl.style.color = '#4ade80'; }

                    const statusBox = document.getElementById('shopStatusBox');
                    if (statusBox) statusBox.innerHTML = `
                        <div style="background:#f0fdf4;border:1.5px solid #86efac;border-radius:12px;padding:.9rem 1.1rem;color:#166534;font-weight:700;font-size:.9rem;">
                            <i class="fas fa-check-circle me-2"></i>Đã nhận thanh toán! Đơn hàng đang được xử lý.
                        </div>`;

                    // Toast
                    const toast = document.createElement('div');
                    toast.style.cssText = 'position:fixed;top:20px;right:20px;z-index:9999;background:linear-gradient(135deg,#10b981,#059669);color:#fff;padding:1.2rem 1.5rem;border-radius:16px;box-shadow:0 10px 40px rgba(16,185,129,.3);display:flex;align-items:center;gap:.8rem;min-width:300px;font-weight:600;';
                    toast.innerHTML = `<i class="fas fa-check-circle fa-lg"></i><div><div>Thanh toán thành công! 🎉</div><div style="font-size:.78rem;font-weight:400;opacity:.85;">Mã đơn: ${ordNum}</div></div>`;
                    document.body.appendChild(toast);
                    setTimeout(() => { toast.style.opacity='0'; toast.style.transition='opacity .3s'; setTimeout(()=>{ if(toast.parentNode) toast.parentNode.removeChild(toast); }, 300); }, 4000);
                })
                .catch(() => {});

            if (attempts >= MAX) {
                clearInterval(_timer); clearInterval(countdown); _timer = null;
                const statusBox = document.getElementById('shopStatusBox');
                if (statusBox) statusBox.innerHTML = `
                    <div style="background:#fff7ed;border:1.5px solid #fed7aa;border-radius:12px;padding:.9rem 1.1rem;color:#9a3412;font-size:.85rem;">
                        <i class="fas fa-exclamation-triangle me-1"></i>
                        <strong>Hết thời gian chờ tự động.</strong>
                        Nếu đã chuyển khoản, nhấn <strong>"Kiểm tra ngay"</strong> hoặc liên hệ hỗ trợ với mã <code>${ordNum}</code>
                    </div>`;
            }
        }, 5000);
        setTimeout(() => { const b = document.getElementById('shopProgressBar'); if (b) b.style.width = '100%'; }, 200);

        // Nút kiểm tra thủ công
        window.manualCheckShopOrder = function() {
            const btn = document.getElementById('btnShopManualCheck');
            if (btn) { btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Đang kiểm tra...'; btn.disabled = true; }
            fetch(`api/check-payment-status.php?order_id=${ordId}`)
                .then(r => r.json())
                .then(data => {
                    if (data.paid) {
                        _done = true;
                        clearInterval(_timer); clearInterval(countdown);
                        const statusEl = document.getElementById('orderStatusLabel');
                        if (statusEl) { statusEl.textContent = '✓ Đã thanh toán'; statusEl.style.color = '#4ade80'; }
                        const statusBox = document.getElementById('shopStatusBox');
                        if (statusBox) statusBox.innerHTML = `<div style="background:#f0fdf4;border:1.5px solid #86efac;border-radius:12px;padding:.9rem 1.1rem;color:#166534;font-weight:700;font-size:.9rem;"><i class="fas fa-check-circle me-2"></i>Đã nhận thanh toán! Đơn hàng đang được xử lý.</div>`;
                    } else {
                        if (btn) { btn.innerHTML = '<i class="fas fa-sync-alt me-1"></i> Kiểm tra ngay'; btn.disabled = false; }
                        alert('⚠️ Chưa nhận được thanh toán. Kiểm tra lại nội dung chuyển khoản: ' + ordNum);
                    }
                })
                .catch(() => { if (btn) { btn.innerHTML = '<i class="fas fa-sync-alt me-1"></i> Kiểm tra ngay'; btn.disabled = false; } });
        };

        // Copy order number
        window.copyShopRef = function(ref) {
            navigator.clipboard.writeText(ref).catch(() => {
                const el = document.createElement('textarea'); el.value = ref;
                document.body.appendChild(el); el.select(); document.execCommand('copy'); document.body.removeChild(el);
            });
            const btn = document.getElementById('btnCopyShopRef');
            if (btn) { btn.innerHTML = '<i class="fas fa-check"></i>'; setTimeout(() => btn.innerHTML = '<i class="fas fa-copy"></i>', 1500); }
        };
    })();
    </script>

    <?php elseif (($_POST['payment_method'] ?? '') === 'cod'): ?>
    <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px;padding:.9rem 1.2rem;margin-bottom:1.5rem;font-size:.88rem;color:#166534;">
        <i class="fas fa-info-circle me-2"></i> Thanh toán khi nhận hàng. Shipper sẽ thu tiền khi giao.
    </div>
    <?php endif; ?>

    <div class="d-flex gap-2 justify-content-center">
        <a href="order-history.php" class="btn fw-bold px-4 py-2"
           style="background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff;border:none;border-radius:12px;" onclick="if(window.PaymentPolling) PaymentPolling.stop()">
            <i class="fas fa-history me-2"></i>Xem đơn hàng
        </a>
        <a href="equipment.php" class="btn fw-bold px-4 py-2"
           style="background:#f3f4f6;color:#374151;border:none;border-radius:12px;" onclick="if(window.PaymentPolling) PaymentPolling.stop()">
            <i class="fas fa-shopping-bag me-2"></i>Tiếp tục mua sắm
        </a>
    </div>

    <?php else: ?>
    <!-- ===== CHECKOUT FORM ===== -->
    <?php if ($error_message): ?>
    <div style="background:#fef2f2;border:1px solid #fca5a5;border-radius:12px;padding:.9rem 1.2rem;margin-bottom:1.5rem;font-size:.88rem;color:#dc2626;display:flex;align-items:center;gap:.6rem;">
        <i class="fas fa-exclamation-circle"></i> <?php echo escape($error_message); ?>
    </div>
    <?php endif; ?>

    <form method="POST" id="checkoutForm">
        <input type="hidden" name="place_order" value="1">
        <input type="hidden" name="cart_data" id="cartDataInput">
        <input type="hidden" name="payment_method" id="paymentMethodInput" value="cod">

        <div class="row g-4">
            <!-- ── Cột trái ── -->
            <div class="col-lg-7">

                <!-- Thông tin người nhận -->
                <div class="co-card">
                    <div class="co-card-header">
                        <i class="fas fa-user"></i> Thông tin người nhận
                    </div>
                    <div class="co-card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="co-label">Họ và tên <span>*</span></label>
                                <input type="text" name="customer_name" class="co-input"
                                       value="<?php echo escape($_SESSION['name'] ?? ''); ?>"
                                       placeholder="Nguyễn Văn A" required>
                            </div>
                            <div class="col-md-6">
                                <label class="co-label">Số điện thoại <span>*</span></label>
                                <input type="tel" name="customer_phone" class="co-input"
                                       placeholder="0912 345 678" required
                                       pattern="[0-9]{9,11}">
                            </div>
                            <div class="col-12">
                                <label class="co-label">Email</label>
                                <input type="email" class="co-input" disabled
                                       value="<?php echo escape($_SESSION['email'] ?? ''); ?>"
                                       style="background:#f3f4f6;color:#6b7280;">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Địa chỉ giao hàng -->
                <div class="co-card">
                    <div class="co-card-header">
                        <i class="fas fa-map-marker-alt"></i> Địa chỉ giao hàng
                    </div>
                    <div class="co-card-body">
                        <div class="addr-grid">
                            <div>
                                <label class="co-label">Tỉnh / Thành phố <span>*</span></label>
                                <select name="province" id="selectProvince" class="co-select" required onchange="loadDistricts(this.value)">
                                    <option value="">-- Chọn tỉnh/TP --</option>
                                </select>
                            </div>
                            <div>
                                <label class="co-label">Quận / Huyện <span>*</span></label>
                                <select name="district" id="selectDistrict" class="co-select" required onchange="loadWards(this.value)">
                                    <option value="">-- Chọn quận/huyện --</option>
                                </select>
                            </div>
                            <div>
                                <label class="co-label">Phường / Xã</label>
                                <select name="ward" id="selectWard" class="co-select">
                                    <option value="">-- Chọn phường/xã --</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="co-label">Số nhà, tên đường <span>*</span></label>
                            <input type="text" name="address_detail" class="co-input"
                                   placeholder="VD: 123 Trần Phú" required>
                        </div>
                        <div class="mt-3">
                            <label class="co-label">Ghi chú đơn hàng</label>
                            <textarea name="order_note" class="co-textarea" rows="2"
                                      placeholder="Giao giờ hành chính, để ở bảo vệ,..."></textarea>
                        </div>
                    </div>
                </div>

                <!-- Phương thức thanh toán -->
                <div class="co-card">
                    <div class="co-card-header">
                        <i class="fas fa-credit-card"></i> Phương thức thanh toán
                    </div>
                    <div class="co-card-body">

                        <!-- COD -->
                        <div class="pm-card active" onclick="selectPM('cod', this)">
                            <div class="pm-icon-wrap" style="background:#d1fae5;">
                                <svg width="26" height="26" viewBox="0 0 24 24" fill="none"><rect x="2" y="6" width="20" height="13" rx="3" fill="#16a34a" opacity=".15"/><rect x="2" y="6" width="20" height="13" rx="3" stroke="#16a34a" stroke-width="1.8"/><path d="M6 13h4M6 16h3" stroke="#16a34a" stroke-width="1.8" stroke-linecap="round"/><circle cx="16" cy="13" r="2.5" fill="#16a34a"/></svg>
                            </div>
                            <div class="pm-text">
                                <div class="pm-title">Tiền mặt tại sân</div>
                                <div class="pm-sub">Thanh toán khi đến sân lần đầu</div>
                            </div>
                            <div class="pm-check-wrap">
                                <svg width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M2.5 7l3 3 6-6" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </div>
                        </div>

                        <!-- MoMo -->
                        <div class="pm-card" onclick="selectPM('momo', this)">
                            <div class="pm-icon-wrap" style="background:#fce7f3;">
                                <svg width="26" height="26" viewBox="0 0 24 24" fill="none"><rect x="3" y="5" width="18" height="14" rx="3" fill="#db2777" opacity=".12"/><rect x="3" y="5" width="18" height="14" rx="3" stroke="#db2777" stroke-width="1.8"/><circle cx="8" cy="11" r="2" fill="#db2777"/><circle cx="12" cy="11" r="2" fill="#db2777"/><circle cx="16" cy="11" r="2" fill="#db2777"/></svg>
                            </div>
                            <div class="pm-text">
                                <div class="pm-title">Ví MoMo</div>
                                <div class="pm-sub">Thanh toán qua ví điện tử MoMo</div>
                            </div>
                            <div class="pm-check-wrap">
                                <svg width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M2.5 7l3 3 6-6" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </div>
                        </div>

                        <!-- MB Bank -->
                        <div class="pm-card" onclick="selectPM('vnpay', this)">
                            <div class="pm-icon-wrap" style="background:#dbeafe;">
                                <svg width="26" height="26" viewBox="0 0 24 24" fill="none"><path d="M3 21h18M5 21V10M19 21V10" stroke="#2563eb" stroke-width="1.8" stroke-linecap="round"/><path d="M2 10l10-7 10 7" stroke="#2563eb" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><rect x="9" y="14" width="6" height="7" rx="1" fill="#2563eb" opacity=".15" stroke="#2563eb" stroke-width="1.5"/></svg>
                            </div>
                            <div class="pm-text">
                                <div class="pm-title">MB Bank</div>
                                <div class="pm-sub">Chuyển khoản ngân hàng MB Bank</div>
                            </div>
                            <div class="pm-check-wrap">
                                <svg width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M2.5 7l3 3 6-6" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </div>
                        </div>

                        <!-- Chuyển khoản -->
                        <div class="pm-card" onclick="selectPM('bank', this)">
                            <div class="pm-icon-wrap" style="background:#fef3c7;">
                                <svg width="26" height="26" viewBox="0 0 24 24" fill="none"><path d="M12 3L2 9h20L12 3z" fill="#d97706" opacity=".18" stroke="#d97706" stroke-width="1.8" stroke-linejoin="round"/><path d="M5 9v9M9 9v9M15 9v9M19 9v9M3 18h18" stroke="#d97706" stroke-width="1.8" stroke-linecap="round"/></svg>
                            </div>
                            <div class="pm-text">
                                <div class="pm-title">Chuyển khoản ngân hàng</div>
                                <div class="pm-sub">Xác nhận trong 1–2 giờ làm việc</div>
                            </div>
                            <div class="pm-check-wrap">
                                <svg width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M2.5 7l3 3 6-6" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </div>
                        </div>

                        <!-- MoMo payment panel -->
                        <div id="momoDetail" style="background:#fdf2f8;border:1px solid #f9a8d4;border-radius:14px;padding:1rem 1.2rem;margin-bottom:.7rem;display:none;animation:slideDown .2s ease;">
                            <div style="font-weight:700;font-size:.82rem;color:#be185d;margin-bottom:.7rem;display:flex;align-items:center;gap:.4rem;">
                                <svg width="14" height="14" viewBox="0 0 14 14" fill="none"><circle cx="7" cy="7" r="6.5" stroke="#db2777" stroke-width="1.3"/><path d="M7 4v4M7 9.5v.5" stroke="#db2777" stroke-width="1.4" stroke-linecap="round"/></svg>
                                Thông tin thanh toán MoMo
                            </div>
                            <div style="display:flex;gap:1rem;align-items:flex-start;">
                                <!-- QR MoMo -->
                                <div style="flex-shrink:0;text-align:center;">
                                    <img id="momoQrImg"
                                         src="https://img.vietqr.io/image/MOMO-0968073500-qr_only.png?amount=0&addInfo=DONHANG&accountName=LU+DANG+HUNG"
                                         alt="QR MoMo"
                                         style="width:130px;height:130px;border-radius:10px;border:2px solid #f9a8d4;background:#fff;padding:4px;">
                                    <div style="font-size:.68rem;color:#be185d;margin-top:4px;font-weight:600;">Quét bằng app MoMo</div>
                                </div>
                                <!-- Info -->
                                <div style="flex:1;display:grid;gap:.45rem;font-size:.85rem;">
                                    <div style="display:flex;gap:.5rem;align-items:center;">
                                        <span style="color:#78716c;min-width:105px;">Số điện thoại</span>
                                        <span class="bank-ref" style="color:#db2777;">0968073500</span>
                                    </div>
                                    <div style="display:flex;gap:.5rem;align-items:center;">
                                        <span style="color:#78716c;min-width:105px;">Tên tài khoản</span>
                                        <span style="font-weight:700;">LU DANG HUNG</span>
                                    </div>
                                    <div style="display:flex;gap:.5rem;align-items:center;">
                                        <span style="color:#78716c;min-width:105px;">Số tiền</span>
                                        <span id="momoAmount" style="font-weight:800;color:#db2777;">—</span>
                                    </div>
                                    <div style="display:flex;gap:.5rem;align-items:center;">
                                        <span style="color:#78716c;min-width:105px;">Nội dung CK</span>
                                        <span id="momoRef" class="bank-ref" style="color:#db2777;">Mã đơn hàng</span>
                                    </div>
                                </div>
                            </div>
                            <div style="margin-top:.7rem;background:#fce7f3;border-radius:8px;padding:.5rem .8rem;font-size:.78rem;color:#9d174d;display:flex;align-items:center;gap:.4rem;">
                                <svg width="13" height="13" viewBox="0 0 13 13" fill="none"><path d="M6.5 1L1 11.5h11L6.5 1z" stroke="#db2777" stroke-width="1.2" stroke-linejoin="round"/><path d="M6.5 5v3M6.5 9.2v.3" stroke="#db2777" stroke-width="1.2" stroke-linecap="round"/></svg>
                                Mở app MoMo → Quét QR hoặc Chuyển tiền → Nhập SĐT → Điền đúng nội dung CK
                            </div>
                            <!-- Auto-confirm note thay cho checkbox -->
                            <div style="margin-top:.85rem;padding:.75rem 1rem;background:linear-gradient(135deg,#fdf2f8,#fff0f9);border:1.5px solid #f9a8d4;border-radius:10px;display:flex;align-items:center;gap:.7rem;">
                                <i class="fas fa-magic" style="color:#db2777;font-size:1rem;flex-shrink:0;"></i>
                                <div style="font-size:.83rem;color:#374151;line-height:1.5;">
                                    <strong style="color:#db2777;">Tự động xác nhận</strong> — Nhấn <strong>Đặt hàng</strong>, hệ thống sẽ hiện mã QR và tự động xác nhận khi nhận được tiền.
                                </div>
                            </div>
                        </div>

                        <!-- Bank info panel -->
                        <div id="bankDetail" style="background:#fffdf0;border:1px solid #fde68a;border-radius:14px;padding:1rem 1.2rem;margin-bottom:.7rem;display:none;animation:slideDown .2s ease;">
                            <div style="font-weight:700;font-size:.82rem;color:#92400e;margin-bottom:.7rem;display:flex;align-items:center;gap:.4rem;">
                                <svg width="14" height="14" viewBox="0 0 14 14" fill="none"><circle cx="7" cy="7" r="6.5" stroke="#d97706" stroke-width="1.3"/><path d="M7 4v4M7 9.5v.5" stroke="#d97706" stroke-width="1.4" stroke-linecap="round"/></svg>
                                Thông tin chuyển khoản ngân hàng
                            </div>
                            <div style="display:flex;gap:1rem;align-items:flex-start;">
                                <!-- QR MB Bank (VietQR) -->
                                <div style="flex-shrink:0;text-align:center;">
                                    <img id="bankQrImg"
                                         src="https://img.vietqr.io/image/MB-7369786789-qr_only.png?amount=0&addInfo=DONHANG&accountName=LU+DANG+HUNG"
                                         alt="QR MB Bank"
                                         style="width:130px;height:130px;border-radius:10px;border:2px solid #fde68a;background:#fff;padding:4px;">
                                    <div style="font-size:.68rem;color:#92400e;margin-top:4px;font-weight:600;">Quét bằng app ngân hàng</div>
                                </div>
                                <!-- Info -->
                                <div style="flex:1;display:grid;gap:.45rem;font-size:.85rem;">
                                    <div style="display:flex;gap:.5rem;align-items:center;">
                                        <span style="color:#78716c;min-width:105px;">Ngân hàng</span>
                                        <span style="font-weight:700;">MB Bank</span>
                                    </div>
                                    <div style="display:flex;gap:.5rem;align-items:center;">
                                        <span style="color:#78716c;min-width:105px;">Số tài khoản</span>
                                        <span class="bank-ref" style="color:#6366f1;">7369786789</span>
                                    </div>
                                    <div style="display:flex;gap:.5rem;align-items:center;">
                                        <span style="color:#78716c;min-width:105px;">Chủ tài khoản</span>
                                        <span style="font-weight:700;">LU DANG HUNG</span>
                                    </div>
                                    <div style="display:flex;gap:.5rem;align-items:center;">
                                        <span style="color:#78716c;min-width:105px;">Số tiền</span>
                                        <span id="bankAmount" style="font-weight:800;color:#6366f1;">—</span>
                                    </div>
                                    <div style="display:flex;gap:.5rem;align-items:center;">
                                        <span style="color:#78716c;min-width:105px;">Nội dung CK</span>
                                        <span id="bankRef" class="bank-ref" style="color:#6366f1;">Mã đơn hàng</span>
                                    </div>
                                </div>
                            </div>
                            <div style="margin-top:.7rem;background:#fef9c3;border-radius:8px;padding:.5rem .8rem;font-size:.78rem;color:#854d0e;display:flex;align-items:center;gap:.4rem;">
                                <svg width="13" height="13" viewBox="0 0 13 13" fill="none"><path d="M6.5 1L1 11.5h11L6.5 1z" stroke="#d97706" stroke-width="1.2" stroke-linejoin="round"/><path d="M6.5 5v3M6.5 9.2v.3" stroke="#d97706" stroke-width="1.2" stroke-linecap="round"/></svg>
                                Ghi đúng nội dung để được xác nhận tự động
                            </div>
                            <!-- Auto-confirm note thay cho checkbox -->
                            <div style="margin-top:.85rem;padding:.75rem 1rem;background:linear-gradient(135deg,#fffbeb,#fffdf0);border:1.5px solid #fde68a;border-radius:10px;display:flex;align-items:center;gap:.7rem;">
                                <i class="fas fa-bolt" style="color:#d97706;font-size:1rem;flex-shrink:0;"></i>
                                <div style="font-size:.83rem;color:#374151;line-height:1.5;">
                                    <strong style="color:#d97706;">Tự động xác nhận</strong> — Nhấn <strong>Đặt hàng</strong>, hệ thống sẽ hiện mã QR và <strong>tự động xác nhận</strong> khi nhận được tiền qua SePay.
                                </div>
                            </div>
                        </div>

                        <!-- SSL -->
                        <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px;padding:.75rem 1rem;font-size:.8rem;color:#166534;display:flex;align-items:center;gap:.6rem;">
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M8 1.5L2 4v4c0 3.3 2.7 5.5 6 6 3.3-.5 6-2.7 6-6V4L8 1.5z" fill="#16a34a" opacity=".15" stroke="#16a34a" stroke-width="1.3" stroke-linejoin="round"/><path d="M5 8l2 2 4-4" stroke="#16a34a" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            Giao dịch được bảo mật SSL 256-bit
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── Cột phải ── -->
            <div class="col-lg-5">
                <div style="position:sticky;top:80px;">
                    <!-- Order Summary -->
                    <div class="co-card">
                        <div class="co-card-header">
                            <i class="fas fa-receipt"></i> Đơn hàng của bạn
                            <span id="cartItemCount" style="margin-left:auto;background:#f3f4f6;color:#6b7280;border-radius:8px;padding:2px 8px;font-size:.75rem;font-weight:700;">0 sản phẩm</span>
                        </div>
                        <div class="co-card-body">
                            <div id="orderItemList">
                                <div class="empty-cart-state">
                                    <i class="fas fa-shopping-cart"></i>
                                    <p style="color:#9ca3af;font-size:.9rem;">Giỏ hàng trống</p>
                                    <a href="equipment.php" style="background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff;border:none;border-radius:10px;padding:.5rem 1.2rem;font-weight:700;font-size:.85rem;text-decoration:none;display:inline-block;">
                                        Mua sắm ngay
                                    </a>
                                </div>
                            </div>

                            <div id="orderTotals" style="display:none;margin-top:.8rem;">
                                <div class="os-total-row">
                                    <span style="color:#6b7280;">Tạm tính</span>
                                    <span class="val" id="osSubtotal">0đ</span>
                                </div>
                                <div class="os-total-row">
                                    <span style="color:#6b7280;">Phí vận chuyển</span>
                                    <span class="val" id="osShipping">Miễn phí</span>
                                </div>
                                <div class="os-total-row final">
                                    <span>Tổng cộng</span>
                                    <span class="val" id="osTotal">0đ</span>
                                </div>
                            </div>

                            <button type="submit" class="btn-place-order mt-3" id="placeOrderBtn" disabled>
                                <i class="fas fa-lock me-2"></i>Đặt hàng ngay
                            </button>

                            <p style="text-align:center;font-size:.75rem;color:#9ca3af;margin-top:.6rem;">
                                <i class="fas fa-shield-alt me-1"></i>
                                Thông tin cá nhân được bảo mật tuyệt đối
                            </p>
                        </div>
                    </div>

                    <!-- Policy -->
                    <div class="co-card">
                        <div class="co-card-body" style="padding:1rem 1.2rem;">
                            <div class="policy-item">
                                <div class="pm-icon" style="background:#d1fae5;width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;">
                                    <i class="fas fa-truck" style="color:#16a34a;font-size:.8rem;"></i>
                                </div>
                                <div>
                                    <div class="fw-bold" style="font-size:.82rem;">Miễn phí vận chuyển</div>
                                    <div style="font-size:.75rem;color:#9ca3af;">Cho đơn hàng từ 500.000đ</div>
                                </div>
                            </div>
                            <div class="policy-item">
                                <div class="pm-icon" style="background:#dbeafe;width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;">
                                    <i class="fas fa-undo" style="color:#2563eb;font-size:.8rem;"></i>
                                </div>
                                <div>
                                    <div class="fw-bold" style="font-size:.82rem;">Đổi trả 7 ngày</div>
                                    <div style="font-size:.75rem;color:#9ca3af;">Không cần lý do, miễn phí đổi</div>
                                </div>
                            </div>
                            <div class="policy-item">
                                <div class="pm-icon" style="background:#fef3c7;width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;">
                                    <i class="fas fa-certificate" style="color:#d97706;font-size:.8rem;"></i>
                                </div>
                                <div>
                                    <div class="fw-bold" style="font-size:.82rem;">Chính hãng 100%</div>
                                    <div style="font-size:.75rem;color:#9ca3af;">Bảo hành chính hãng toàn quốc</div>
                                </div>
                            </div>
                            <div class="policy-item">
                                <div class="pm-icon" style="background:#ede9fe;width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;">
                                    <i class="fas fa-headset" style="color:#7c3aed;font-size:.8rem;"></i>
                                </div>
                                <div>
                                    <div class="fw-bold" style="font-size:.82rem;">Hỗ trợ 24/7</div>
                                    <div style="font-size:.75rem;color:#9ca3af;">Hotline: 0968.073.500</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Box hỗ trợ -->
                    <div class="co-card" style="overflow:hidden;">
                        <div style="padding:1.5rem 1.2rem;text-align:center;">
                            <!-- Icon headset -->
                            <div style="width:64px;height:64px;background:linear-gradient(135deg,#28a745,#20c997);
                                        border-radius:18px;display:flex;align-items:center;justify-content:center;
                                        margin:0 auto 1rem;box-shadow:0 6px 20px rgba(40,167,69,.3);">
                                <svg width="32" height="32" viewBox="0 0 32 32" fill="none">
                                    <path d="M6 20v-4a10 10 0 0120 0v4" stroke="#fff" stroke-width="2" stroke-linecap="round"/>
                                    <rect x="4" y="18" width="5" height="8" rx="2.5" fill="#fff" opacity=".9"/>
                                    <rect x="23" y="18" width="5" height="8" rx="2.5" fill="#fff" opacity=".9"/>
                                    <path d="M26 26a4 4 0 01-4 4h-3" stroke="#fff" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                            </div>

                            <div style="font-weight:800;font-size:1.05rem;color:#111827;margin-bottom:.4rem;">Hỗ trợ 24/7</div>
                            <p style="font-size:.82rem;color:#6b7280;margin-bottom:1.2rem;line-height:1.5;">
                                Tư vấn và giải đáp mọi thắc mắc qua nhiều kênh
                            </p>

                            <!-- Gọi điện - filled green -->
                            <a href="tel:0968073500"
                               style="display:flex;align-items:center;justify-content:center;gap:.6rem;
                                      background:linear-gradient(135deg,#28a745,#20c997);
                                      border-radius:12px;padding:.75rem;
                                      font-weight:700;font-size:.92rem;color:#fff;text-decoration:none;
                                      box-shadow:0 4px 15px rgba(40,167,69,.3);
                                      transition:all .2s;margin-bottom:.6rem;"
                               onmouseover="this.style.transform='translateY(-1px)';this.style.boxShadow='0 6px 20px rgba(40,167,69,.4)'"
                               onmouseout="this.style.transform='';this.style.boxShadow='0 4px 15px rgba(40,167,69,.3)'">
                                <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                                    <path d="M3 2h3l1.5 3.5-1.75 1.05a9 9 0 004.7 4.7L11.5 9.5 15 11v3a2 2 0 01-2 2C5.37 16 0 10.63 0 4a2 2 0 012-2h1z" fill="#fff" opacity=".9"/>
                                </svg>
                                Gọi: 0968.073.500
                            </a>

                            <!-- Chat trực tuyến - outline -->
                            <a href="#" onclick="openLiveChat(); return false;"
                               style="display:flex;align-items:center;justify-content:center;gap:.6rem;
                                      border:1.5px solid #28a745;border-radius:12px;padding:.7rem;
                                      font-weight:700;font-size:.9rem;color:#28a745;text-decoration:none;
                                      transition:all .2s;margin-bottom:.6rem;background:#fff;"
                               onmouseover="this.style.background='#f0fdf4'"
                               onmouseout="this.style.background='#fff'">
                                <svg width="17" height="17" viewBox="0 0 17 17" fill="none">
                                    <path d="M1 3a2 2 0 012-2h11a2 2 0 012 2v7a2 2 0 01-2 2H6l-4 4V3z" fill="#28a745" opacity=".12" stroke="#28a745" stroke-width="1.4" stroke-linejoin="round"/>
                                    <circle cx="5.5" cy="6.5" r="1" fill="#28a745"/>
                                    <circle cx="8.5" cy="6.5" r="1" fill="#28a745"/>
                                    <circle cx="11.5" cy="6.5" r="1" fill="#28a745"/>
                                </svg>
                                Chat trực tuyến
                            </a>

                            <!-- Gửi email - outline -->
                            <a href="mailto:support@badmintonpro.vn"
                               style="display:flex;align-items:center;justify-content:center;gap:.6rem;
                                      border:1.5px solid #e5e7eb;border-radius:12px;padding:.7rem;
                                      font-weight:600;font-size:.9rem;color:#4b5563;text-decoration:none;
                                      transition:all .2s;margin-bottom:1rem;background:#fff;"
                               onmouseover="this.style.background='#f9fafb';this.style.borderColor='#d1d5db'"
                               onmouseout="this.style.background='#fff';this.style.borderColor='#e5e7eb'">
                                <svg width="17" height="17" viewBox="0 0 17 17" fill="none">
                                    <rect x="1" y="3" width="15" height="11" rx="2" stroke="#6b7280" stroke-width="1.4"/>
                                    <path d="M1 5l7.5 5.5L16 5" stroke="#6b7280" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                Gửi email
                            </a>

                            <!-- Thời gian phản hồi -->
                            <div style="display:flex;align-items:center;justify-content:center;gap:.4rem;font-size:.78rem;color:#6b7280;">
                                <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
                                    <circle cx="7" cy="7" r="6" fill="#28a745" opacity=".12" stroke="#28a745" stroke-width="1.2"/>
                                    <path d="M7 4v3.5l2 1.5" stroke="#28a745" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                Phản hồi trong vòng 5 phút
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </form>
    <?php endif; ?>

</div>
</div>

<script>
// ── Live Chat ──
function openLiveChat() {
    const existing = document.getElementById('liveChatWidget');
    if (existing) { existing.style.display = 'block'; return; }

    const w = document.createElement('div');
    w.id = 'liveChatWidget';
    w.style.cssText = 'position:fixed;bottom:1.5rem;right:1.5rem;width:340px;z-index:9999;background:#fff;border-radius:20px;box-shadow:0 8px 40px rgba(0,0,0,.2);overflow:hidden;';
    w.innerHTML = `
        <div style="background:linear-gradient(135deg,#28a745,#20c997);padding:1rem 1.2rem;display:flex;align-items:center;justify-content:space-between;">
            <div style="display:flex;align-items:center;gap:.6rem;">
                <div style="width:36px;height:36px;background:rgba(255,255,255,.2);border-radius:50%;display:flex;align-items:center;justify-content:center;">
                    <svg width="18" height="18" viewBox="0 0 32 32" fill="none"><path d="M6 20v-4a10 10 0 0120 0v4" stroke="#fff" stroke-width="2.5" stroke-linecap="round"/><rect x="4" y="18" width="5" height="8" rx="2.5" fill="#fff"/><rect x="23" y="18" width="5" height="8" rx="2.5" fill="#fff"/></svg>
                </div>
                <div>
                    <div style="color:#fff;font-weight:700;font-size:.9rem;">Hỗ trợ BadmintonPro</div>
                    <div style="color:rgba(255,255,255,.85);font-size:.72rem;display:flex;align-items:center;gap:.3rem;">
                        <span style="width:7px;height:7px;background:#a3e635;border-radius:50%;display:inline-block;"></span>
                        Online — Phản hồi ngay
                    </div>
                </div>
            </div>
            <button onclick="document.getElementById('liveChatWidget').style.display='none'"
                    style="background:rgba(255,255,255,.2);border:none;color:#fff;width:30px;height:30px;border-radius:50%;cursor:pointer;font-size:1rem;display:flex;align-items:center;justify-content:center;">✕</button>
        </div>
        <div id="chatMessages" style="height:230px;overflow-y:auto;padding:1rem;background:#f9fafb;display:flex;flex-direction:column;gap:.7rem;">
            <div style="display:flex;gap:.5rem;align-items:flex-end;">
                <div style="width:30px;height:30px;background:linear-gradient(135deg,#28a745,#20c997);border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <svg width="14" height="14" viewBox="0 0 32 32" fill="none"><path d="M6 20v-4a10 10 0 0120 0v4" stroke="#fff" stroke-width="2.5" stroke-linecap="round"/><rect x="4" y="18" width="5" height="8" rx="2.5" fill="#fff"/><rect x="23" y="18" width="5" height="8" rx="2.5" fill="#fff"/></svg>
                </div>
                <div style="background:#fff;border-radius:14px 14px 14px 0;padding:.65rem .9rem;font-size:.83rem;color:#374151;max-width:230px;box-shadow:0 1px 4px rgba(0,0,0,.07);">
                    Xin chào! 👋 Tôi có thể giúp gì cho bạn?
                </div>
            </div>
        </div>
        <div style="padding:.75rem;border-top:1px solid #e5e7eb;display:flex;gap:.5rem;background:#fff;">
            <input id="chatInput" type="text" placeholder="Nhập tin nhắn..."
                   style="flex:1;border:1.5px solid #e5e7eb;border-radius:10px;padding:.5rem .85rem;font-size:.85rem;outline:none;"
                   onfocus="this.style.borderColor='#28a745'" onblur="this.style.borderColor='#e5e7eb'"
                   onkeydown="if(event.key==='Enter')sendChat()">
            <button onclick="sendChat()" style="background:linear-gradient(135deg,#28a745,#20c997);border:none;color:#fff;border-radius:10px;width:38px;height:38px;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <svg width="15" height="15" viewBox="0 0 15 15" fill="none"><path d="M1 7.5h13M8.5 2l6 5.5-6 5.5" stroke="#fff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>
        </div>
    `;
    document.body.appendChild(w);
    w.querySelector('#chatInput').focus();
}

function sendChat() {
    const input = document.getElementById('chatInput');
    const msg   = input.value.trim();
    if (!msg) return;

    const box = document.getElementById('chatMessages');

    // Tin user
    const userBubble = document.createElement('div');
    userBubble.style.cssText = 'display:flex;justify-content:flex-end;';
    userBubble.innerHTML = `<div style="background:linear-gradient(135deg,#28a745,#20c997);border-radius:14px 14px 0 14px;padding:.6rem .8rem;font-size:.82rem;color:#fff;max-width:220px;">${msg}</div>`;
    box.appendChild(userBubble);
    input.value = '';
    box.scrollTop = box.scrollHeight;

    // Auto reply sau 1s
    setTimeout(() => {
        const replies = [
            'Cảm ơn bạn đã liên hệ! Nhân viên sẽ hỗ trợ bạn ngay.',
            'Vui lòng để lại số điện thoại, chúng tôi sẽ gọi lại cho bạn.',
            'Hotline hỗ trợ: 0968.073.500 — hoạt động 6:00–22:00 hàng ngày.',
        ];
        const rep = replies[Math.floor(Math.random() * replies.length)];
        const botBubble = document.createElement('div');
        botBubble.style.cssText = 'display:flex;gap:.5rem;align-items:flex-end;';
        botBubble.innerHTML = `
            <div style="width:28px;height:28px;background:linear-gradient(135deg,#28a745,#20c997);border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <svg width="14" height="14" viewBox="0 0 32 32" fill="none"><path d="M6 20v-4a10 10 0 0120 0v4" stroke="#fff" stroke-width="2.5" stroke-linecap="round"/><rect x="4" y="18" width="5" height="8" rx="2.5" fill="#fff"/><rect x="23" y="18" width="5" height="8" rx="2.5" fill="#fff"/></svg>
            </div>
            <div style="background:#fff;border-radius:14px 14px 14px 0;padding:.6rem .8rem;font-size:.82rem;color:#374151;max-width:220px;box-shadow:0 1px 4px rgba(0,0,0,.06);">${rep}</div>`;
        box.appendChild(botBubble);
        box.scrollTop = box.scrollHeight;
    }, 1000);
}

// ═══════════════════════════════════════════
// CART LOADING
// ═══════════════════════════════════════════
document.addEventListener('DOMContentLoaded', function() {
    const cart = JSON.parse(localStorage.getItem('cart') || '[]');
    const listEl   = document.getElementById('orderItemList');
    const totalsEl = document.getElementById('orderTotals');
    const countEl  = document.getElementById('cartItemCount');
    const submitBtn= document.getElementById('placeOrderBtn');
    const cartInput= document.getElementById('cartDataInput');

    if (!cart.length) return;

    let subtotal = 0;
    let html = '';

    cart.forEach(item => {
        const lineTotal = item.price * item.quantity;
        subtotal += lineTotal;
        html += `
            <div class="os-item">
                <img src="${item.image || 'https://via.placeholder.com/48?text=P'}" alt="${item.name}">
                <div style="flex:1;min-width:0;">
                    <div class="os-item-name">${item.name}</div>
                    <div class="os-item-meta">${item.price.toLocaleString('vi-VN')}đ × ${item.quantity}</div>
                </div>
                <div style="font-weight:800;font-size:.88rem;color:#374151;white-space:nowrap;">
                    ${lineTotal.toLocaleString('vi-VN')}đ
                </div>
            </div>`;
    });

    listEl.innerHTML = html;
    totalsEl.style.display = 'block';

    const totalItems = cart.reduce((s,i) => s + i.quantity, 0);
    countEl.textContent = totalItems + ' sản phẩm';

    const shipping = subtotal >= 500000 ? 0 : 30000;
    const total = subtotal + shipping;

    document.getElementById('osSubtotal').textContent = subtotal.toLocaleString('vi-VN') + 'đ';
    document.getElementById('osShipping').textContent = shipping === 0
        ? '<span class="free">Miễn phí</span>'
        : shipping.toLocaleString('vi-VN') + 'đ';
    document.getElementById('osShipping').innerHTML = shipping === 0
        ? '<span class="free">Miễn phí</span>'
        : shipping.toLocaleString('vi-VN') + 'đ';
    document.getElementById('osTotal').textContent = total.toLocaleString('vi-VN') + 'đ';

    cartInput.value = JSON.stringify(cart);
    // Enable button for COD (default); for momo/bank/vnpay the checkbox controls it
    updatePlaceOrderBtn();
    if (document.getElementById('paymentMethodInput')?.value === 'cod') {
        if (submitBtn) submitBtn.disabled = false;
    }
});

// Clear cart on success
<?php if ($order_success): ?>
localStorage.removeItem('cart');
<?php endif; ?>

// ═══════════════════════════════════════════
// PAYMENT METHOD SELECTION
// ═══════════════════════════════════════════
function selectPM(method, el) {
    document.querySelectorAll('.pm-card').forEach(c => c.classList.remove('active'));
    el.classList.add('active');
    document.getElementById('paymentMethodInput').value = method;

    // Hide all panels
    document.getElementById('bankDetail').style.display = 'none';
    document.getElementById('momoDetail').style.display = 'none';

    const ref = 'ORD-' + Date.now().toString(36).toUpperCase().slice(-6);

    if (method === 'bank' || method === 'vnpay') {
        document.getElementById('bankDetail').style.display = 'block';
        document.getElementById('bankRef').textContent = ref;
        const amountEl  = document.getElementById('osTotal');
        const amountRaw = amountEl ? amountEl.textContent.replace(/[^\d]/g,'') : '0';
        const amountEl2 = document.getElementById('bankAmount');
        if (amountEl2) amountEl2.textContent = amountEl ? amountEl.textContent : '—';
        const qr = document.getElementById('bankQrImg');
        if (qr) qr.src = `https://img.vietqr.io/image/MB-7369786789-qr_only.png?amount=${amountRaw}&addInfo=${encodeURIComponent(ref)}&accountName=LU+DANG+HUNG`;
    } else if (method === 'momo') {
        document.getElementById('momoDetail').style.display = 'block';
        document.getElementById('momoRef').textContent = ref;
        const amountEl  = document.getElementById('osTotal');
        const amountRaw = amountEl ? amountEl.textContent.replace(/[^\d]/g,'') : '0';
        const amountEl2 = document.getElementById('momoAmount');
        if (amountEl2) amountEl2.textContent = amountEl ? amountEl.textContent : '—';
        const qr = document.getElementById('momoQrImg');
        if (qr) qr.src = `https://img.vietqr.io/image/MOMO-0968073500-qr_only.png?amount=${amountRaw}&addInfo=${encodeURIComponent(ref)}&accountName=LU+DANG+HUNG`;
    }

    updatePlaceOrderBtn();
}

function updatePlaceOrderBtn() {
    const btn = document.getElementById('placeOrderBtn');
    if (!btn) return;
    // Nút luôn enabled khi có hàng trong giỏ — không cần checkbox nữa
    const cart = JSON.parse(localStorage.getItem('cart') || '[]');
    btn.disabled = (cart.length === 0);
}

// ═══════════════════════════════════════════
// ADDRESS — VIETNAM API
// ═══════════════════════════════════════════
async function loadProvinces() {
    try {
        const res = await fetch('https://provinces.open-api.vn/api/?depth=1');
        const data = await res.json();
        const sel = document.getElementById('selectProvince');
        data.forEach(p => {
            const opt = document.createElement('option');
            opt.value = p.name;
            opt.dataset.code = p.code;
            opt.textContent = p.name;
            sel.appendChild(opt);
        });
    } catch(e) {
        // Fallback static list
        const provinces = ['Hà Nội','TP. Hồ Chí Minh','Đà Nẵng','Hải Phòng','Cần Thơ','An Giang','Bắc Giang','Bắc Kạn','Bạc Liêu','Bắc Ninh','Bến Tre','Bình Định','Bình Dương','Bình Phước','Bình Thuận','Cà Mau','Cao Bằng','Đắk Lắk','Đắk Nông','Điện Biên','Đồng Nai','Đồng Tháp','Gia Lai','Hà Giang','Hà Nam','Hà Tĩnh','Hải Dương','Hậu Giang','Hòa Bình','Hưng Yên','Khánh Hòa','Kiên Giang','Kon Tum','Lai Châu','Lâm Đồng','Lạng Sơn','Lào Cai','Long An','Nam Định','Nghệ An','Ninh Bình','Ninh Thuận','Phú Thọ','Phú Yên','Quảng Bình','Quảng Nam','Quảng Ngãi','Quảng Ninh','Quảng Trị','Sóc Trăng','Sơn La','Tây Ninh','Thái Bình','Thái Nguyên','Thanh Hóa','Thừa Thiên Huế','Tiền Giang','Trà Vinh','Tuyên Quang','Vĩnh Long','Vĩnh Phúc','Yên Bái'];
        const sel = document.getElementById('selectProvince');
        provinces.forEach(p => {
            const opt = document.createElement('option');
            opt.value = p; opt.textContent = p;
            sel.appendChild(opt);
        });
    }
}

async function loadDistricts(provinceName) {
    const sel = document.getElementById('selectDistrict');
    const wardSel = document.getElementById('selectWard');
    sel.innerHTML = '<option value="">-- Chọn quận/huyện --</option>';
    wardSel.innerHTML = '<option value="">-- Chọn phường/xã --</option>';
    if (!provinceName) return;

    try {
        const provSel = document.getElementById('selectProvince');
        const code = provSel.options[provSel.selectedIndex]?.dataset?.code;
        if (!code) return;

        const res = await fetch(`https://provinces.open-api.vn/api/p/${code}?depth=2`);
        const data = await res.json();
        (data.districts || []).forEach(d => {
            const opt = document.createElement('option');
            opt.value = d.name; opt.dataset.code = d.code; opt.textContent = d.name;
            sel.appendChild(opt);
        });
    } catch(e) {}
}

async function loadWards(districtName) {
    const sel = document.getElementById('selectWard');
    sel.innerHTML = '<option value="">-- Chọn phường/xã --</option>';
    if (!districtName) return;

    try {
        const distSel = document.getElementById('selectDistrict');
        const code = distSel.options[distSel.selectedIndex]?.dataset?.code;
        if (!code) return;

        const res = await fetch(`https://provinces.open-api.vn/api/d/${code}?depth=2`);
        const data = await res.json();
        (data.wards || []).forEach(w => {
            const opt = document.createElement('option');
            opt.value = w.name; opt.textContent = w.name;
            sel.appendChild(opt);
        });
    } catch(e) {}
}

// Form submit validation
document.getElementById('checkoutForm')?.addEventListener('submit', function(e) {
    const cart = JSON.parse(localStorage.getItem('cart') || '[]');
    if (!cart.length) {
        e.preventDefault();
        alert('Giỏ hàng trống!');
        return;
    }
    const btn = document.getElementById('placeOrderBtn');
    if (btn) {
        btn.innerHTML = '<div class="spinner-border spinner-border-sm me-2"></div>Đang xử lý...';
        btn.disabled = true;
    }
});

// Init
loadProvinces();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
<script src="assets/js/payment-polling.js"></script>
