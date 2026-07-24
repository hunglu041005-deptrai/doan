<?php
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();
$isAdminPage = true;

// Tạo bảng nếu chưa có
$mysqli->query("CREATE TABLE IF NOT EXISTS support_tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    role VARCHAR(20) DEFAULT 'coach',
    subject VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    status VARCHAR(20) DEFAULT 'open',
    admin_reply TEXT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user (user_id), INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$message = '';

// Xử lý phản hồi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ticket_id'])) {
    $tid   = intval($_POST['ticket_id']);
    $reply = trim($_POST['admin_reply'] ?? '');
    $status = $_POST['new_status'] ?? 'answered';

    $upd = $mysqli->prepare('UPDATE support_tickets SET admin_reply=?, status=?, updated_at=NOW() WHERE id=?');
    $upd->bind_param('ssi', $reply, $status, $tid);
    $upd->execute(); $upd->close();
    $message = 'Đã gửi phản hồi thành công.';
}

// Lấy tickets + tên người gửi
$tickets = $mysqli->query("
    SELECT st.*, u.name AS user_name, u.email AS user_email
    FROM support_tickets st
    LEFT JOIN users u ON u.id = st.user_id
    ORDER BY FIELD(st.status,'open','answered','closed'), st.created_at DESC
")->fetch_all(MYSQLI_ASSOC);

$openCount     = count(array_filter($tickets, fn($t) => $t['status'] === 'open'));
$answeredCount = count(array_filter($tickets, fn($t) => $t['status'] === 'answered'));

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid mt-4">

<!-- Header -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-lg" style="background:linear-gradient(135deg,#7c3aed 0%,#4f46e5 100%);">
            <div class="card-body text-white py-4">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1 class="fw-bold mb-1"><i class="fas fa-headset me-3"></i>Yêu cầu hỗ trợ</h1>
                        <p class="mb-0 opacity-75">Xem và phản hồi yêu cầu từ Huấn luyện viên.</p>
                    </div>
                    <div class="col-md-4 text-end d-none d-md-block">
                        <i class="fas fa-ticket-alt" style="font-size:4rem;opacity:.2;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($message): ?>
<div class="alert alert-success alert-dismissible fade show">
    <i class="fas fa-check-circle me-2"></i><?php echo escape($message); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Stats -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center">
                <div class="bg-warning bg-opacity-10 rounded-3 p-3 me-3"><i class="fas fa-envelope-open text-warning fa-2x"></i></div>
                <div><div class="text-muted small fw-bold text-uppercase">Chờ phản hồi</div>
                <div class="h2 fw-bold mb-0 text-warning"><?php echo $openCount; ?></div></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center">
                <div class="bg-success bg-opacity-10 rounded-3 p-3 me-3"><i class="fas fa-check-circle text-success fa-2x"></i></div>
                <div><div class="text-muted small fw-bold text-uppercase">Đã phản hồi</div>
                <div class="h2 fw-bold mb-0 text-success"><?php echo $answeredCount; ?></div></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center">
                <div class="bg-primary bg-opacity-10 rounded-3 p-3 me-3"><i class="fas fa-ticket-alt text-primary fa-2x"></i></div>
                <div><div class="text-muted small fw-bold text-uppercase">Tổng tickets</div>
                <div class="h2 fw-bold mb-0 text-primary"><?php echo count($tickets); ?></div></div>
            </div>
        </div>
    </div>
</div>

<!-- Danh sách tickets -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom py-3">
        <h6 class="mb-0 fw-bold"><i class="fas fa-list text-primary me-2"></i>Tất cả yêu cầu</h6>
    </div>
    <div class="card-body p-0">
        <?php if (empty($tickets)): ?>
        <div class="text-center py-5 text-muted">
            <i class="fas fa-inbox fa-3x mb-3 d-block opacity-25"></i>
            Chưa có yêu cầu hỗ trợ nào.
        </div>
        <?php else: ?>
        <div class="accordion accordion-flush" id="ticketsAccordion">
        <?php foreach ($tickets as $i => $t):
            $sColor = ['open'=>'warning','answered'=>'success','closed'=>'secondary'][$t['status']] ?? 'secondary';
            $sLabel = ['open'=>'Chờ phản hồi','answered'=>'Đã phản hồi','closed'=>'Đã đóng'][$t['status']] ?? $t['status'];
        ?>
        <div class="accordion-item border-0 border-bottom">
            <h2 class="accordion-header">
                <button class="accordion-button <?php echo $i>0?'collapsed':''; ?> py-3" type="button"
                        data-bs-toggle="collapse" data-bs-target="#ticket-<?php echo $t['id']; ?>">
                    <div class="d-flex align-items-center gap-3 w-100 me-3">
                        <span class="badge bg-<?php echo $sColor; ?> rounded-pill"><?php echo $sLabel; ?></span>
                        <span class="fw-bold"><?php echo escape($t['subject']); ?></span>
                        <span class="ms-auto text-muted small">
                            <?php echo escape($t['user_name'] ?? ''); ?> · <?php echo date('d/m/Y H:i', strtotime($t['created_at'])); ?>
                        </span>
                    </div>
                </button>
            </h2>
            <div id="ticket-<?php echo $t['id']; ?>" class="accordion-collapse collapse <?php echo $i===0?'show':''; ?>">
                <div class="accordion-body pt-0">
                    <!-- Nội dung yêu cầu -->
                    <div class="p-3 rounded-3 mb-3" style="background:#f8fafc;border:1px solid #e5e7eb;">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <div class="bg-primary bg-opacity-10 rounded-circle p-2">
                                <i class="fas fa-user text-primary small"></i>
                            </div>
                            <div>
                                <div class="fw-bold small"><?php echo escape($t['user_name'] ?? 'HLV'); ?></div>
                                <div class="text-muted" style="font-size:.72rem;"><?php echo escape($t['user_email'] ?? ''); ?></div>
                            </div>
                        </div>
                        <p class="mb-0" style="font-size:.88rem;"><?php echo nl2br(escape($t['message'])); ?></p>
                    </div>

                    <!-- Phản hồi cũ nếu có -->
                    <?php if ($t['admin_reply']): ?>
                    <div class="p-3 rounded-3 mb-3" style="background:#f0fdf4;border:1px solid #bbf7d0;">
                        <div class="fw-bold small text-success mb-1"><i class="fas fa-reply me-1"></i>Phản hồi của Admin:</div>
                        <p class="mb-0 small"><?php echo nl2br(escape($t['admin_reply'])); ?></p>
                    </div>
                    <?php endif; ?>

                    <!-- Form phản hồi -->
                    <?php if ($t['status'] !== 'closed'): ?>
                    <form method="POST">
                        <input type="hidden" name="ticket_id" value="<?php echo $t['id']; ?>">
                        <div class="mb-2">
                            <textarea name="admin_reply" class="form-control" rows="3"
                                      placeholder="Nhập phản hồi của bạn..."
                                      required><?php echo escape($t['admin_reply'] ?? ''); ?></textarea>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" name="new_status" value="answered" class="btn btn-success btn-sm fw-bold">
                                <i class="fas fa-paper-plane me-1"></i>Gửi phản hồi
                            </button>
                            <button type="submit" name="new_status" value="closed" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-times me-1"></i>Đóng ticket
                            </button>
                        </div>
                    </form>
                    <?php else: ?>
                    <div class="text-muted small"><i class="fas fa-lock me-1"></i>Ticket đã đóng.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
