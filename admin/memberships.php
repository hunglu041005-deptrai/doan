<?php
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

// ── Xử lý actions ──────────────────────────────────────────────────────────
$msg = ''; $msgType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['act'] ?? '';
    $mid = intval($_POST['mid'] ?? 0);

    if ($act === 'activate' && $mid) {
        $upd = $mysqli->prepare("UPDATE memberships SET payment_status='paid', status='active', start_date=CURDATE(), end_date=DATE_ADD(CURDATE(), INTERVAL months MONTH) WHERE id=?");
        $upd->bind_param('i', $mid); $upd->execute();
        $msg = 'Đã kích hoạt hội viên #' . $mid;
    } elseif ($act === 'cancel' && $mid) {
        $upd = $mysqli->prepare("UPDATE memberships SET status='cancelled' WHERE id=?");
        $upd->bind_param('i', $mid); $upd->execute();
        $msg = 'Đã huỷ hội viên #' . $mid; $msgType = 'warning';
    } elseif ($act === 'refund' && $mid) {
        $upd = $mysqli->prepare("UPDATE memberships SET status='refunded', payment_status='refunded' WHERE id=?");
        $upd->bind_param('i', $mid); $upd->execute();
        $msg = 'Đã hoàn tiền hội viên #' . $mid; $msgType = 'info';
    } elseif ($act === 'add_tickets' && $mid) {
        $qty = intval($_POST['tickets'] ?? 0);
        if ($qty > 0) {
            $upd = $mysqli->prepare("UPDATE memberships SET free_tickets = free_tickets + ? WHERE id=?");
            $upd->bind_param('ii', $qty, $mid); $upd->execute();
            $msg = "Đã thêm $qty vé cho hội viên #$mid";
        }
    }
}

// ── Lấy filters ────────────────────────────────────────────────────────────
$fStatus = $_GET['status'] ?? '';
$fSearch = trim($_GET['q'] ?? '');
$page    = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;

// ── Query ──────────────────────────────────────────────────────────────────
$where = ['1=1'];
$params = []; $types = '';

if ($fStatus !== '') {
    $where[] = 'm.status = ?'; $types .= 's'; $params[] = $fStatus;
}
if ($fSearch !== '') {
    $where[] = '(u.name LIKE ? OR u.email LIKE ? OR m.member_code LIKE ?)';
    $types .= 'sss';
    $like = "%$fSearch%";
    $params[] = $like; $params[] = $like; $params[] = $like;
}

$whereStr = implode(' AND ', $where);

// Count
$cntSql = "SELECT COUNT(*) FROM memberships m LEFT JOIN users u ON u.id=m.user_id WHERE $whereStr";
$cntStmt = $mysqli->prepare($cntSql);
if ($types) $cntStmt->bind_param($types, ...$params);
$cntStmt->execute();
$total = $cntStmt->get_result()->fetch_row()[0];
$cntStmt->close();

$totalPages = max(1, ceil($total / $perPage));
$offset     = ($page - 1) * $perPage;

// Data
$sql = "SELECT m.*, u.name AS user_name, u.email AS user_email, u.phone AS user_phone
        FROM memberships m
        LEFT JOIN users u ON u.id = m.user_id
        WHERE $whereStr
        ORDER BY m.created_at DESC
        LIMIT ? OFFSET ?";
$stmt = $mysqli->prepare($sql);
$allTypes  = $types . 'ii';
$allParams = array_merge($params, [$perPage, $offset]);
$stmt->bind_param($allTypes, ...$allParams);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// ── Stats ──────────────────────────────────────────────────────────────────
$stats = [];
foreach (['active','pending_payment','expired','cancelled'] as $s) {
    $r = $mysqli->query("SELECT COUNT(*) FROM memberships WHERE status='$s'");
    $stats[$s] = $r->fetch_row()[0];
}
$statsRevenue = $mysqli->query("SELECT SUM(price) FROM memberships WHERE payment_status='paid'")->fetch_row()[0] ?? 0;

require_once __DIR__ . '/../includes/header.php';
?>

<style>
.mem-page { background:#f8fafc; min-height:100vh; padding:2rem 0 3rem; }
.stat-card { background:#fff; border-radius:16px; padding:1.2rem 1.5rem; border:1px solid #f0f0f0;
             box-shadow:0 2px 12px rgba(0,0,0,.05); transition:all .2s; }
.stat-card:hover { transform:translateY(-2px); box-shadow:0 6px 20px rgba(0,0,0,.08); }
.mem-table th { background:#f8fafc; font-size:.78rem; font-weight:700; color:#6b7280;
                text-transform:uppercase; letter-spacing:.5px; border:none; padding:.75rem 1rem; }
.mem-table td { vertical-align:middle; border-color:#f3f4f6; padding:.75rem 1rem; font-size:.87rem; }
.badge-status { border-radius:8px; padding:3px 10px; font-size:.72rem; font-weight:700; }
.badge-active       { background:#d1fae5; color:#065f46; }
.badge-pending      { background:#fef9c3; color:#854d0e; }
.badge-pending_payment { background:#fef9c3; color:#854d0e; }
.badge-expired      { background:#f3f4f6; color:#6b7280; }
.badge-cancelled    { background:#fee2e2; color:#991b1b; }
.badge-refunded     { background:#dbeafe; color:#1e40af; }
.ticket-bar { height:6px; border-radius:3px; background:#e5e7eb; overflow:hidden; margin-top:3px; }
.ticket-bar-fill { height:100%; border-radius:3px; transition:width .4s; }
</style>

<div class="mem-page">
<div class="container-xl">

    <!-- Header -->
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h3 class="fw-bold mb-1"><i class="fas fa-id-card text-success me-2"></i>Quản lý hội viên</h3>
            <p class="text-muted mb-0" style="font-size:.85rem;">Xem và quản lý tất cả gói hội viên</p>
        </div>
        <a href="dashboard.php" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Dashboard
        </a>
    </div>

    <?php if ($msg): ?>
    <div class="alert alert-<?php echo $msgType; ?> alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i><?php echo escape($msg); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3 col-xl">
            <div class="stat-card">
                <div style="font-size:.75rem;color:#9ca3af;font-weight:700;text-transform:uppercase;">Active</div>
                <div style="font-size:1.8rem;font-weight:800;color:#10b981;"><?php echo $stats['active']; ?></div>
            </div>
        </div>
        <div class="col-6 col-md-3 col-xl">
            <div class="stat-card">
                <div style="font-size:.75rem;color:#9ca3af;font-weight:700;text-transform:uppercase;">Chờ TT</div>
                <div style="font-size:1.8rem;font-weight:800;color:#f59e0b;"><?php echo $stats['pending_payment']; ?></div>
            </div>
        </div>
        <div class="col-6 col-md-3 col-xl">
            <div class="stat-card">
                <div style="font-size:.75rem;color:#9ca3af;font-weight:700;text-transform:uppercase;">Hết hạn</div>
                <div style="font-size:1.8rem;font-weight:800;color:#6b7280;"><?php echo $stats['expired']; ?></div>
            </div>
        </div>
        <div class="col-6 col-md-3 col-xl">
            <div class="stat-card">
                <div style="font-size:.75rem;color:#9ca3af;font-weight:700;text-transform:uppercase;">Đã huỷ</div>
                <div style="font-size:1.8rem;font-weight:800;color:#ef4444;"><?php echo $stats['cancelled']; ?></div>
            </div>
        </div>
        <div class="col-6 col-md-3 col-xl">
            <div class="stat-card">
                <div style="font-size:.75rem;color:#9ca3af;font-weight:700;text-transform:uppercase;">Doanh thu</div>
                <div style="font-size:1.3rem;font-weight:800;color:#6366f1;"><?php echo number_format($statsRevenue); ?>đ</div>
            </div>
        </div>
    </div>

    <!-- Filter -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-3">
            <form class="row g-2 align-items-end" method="get">
                <div class="col-md-5">
                    <input type="text" name="q" class="form-control form-control-sm"
                           placeholder="Tìm tên, email, mã thẻ..." value="<?php echo escape($fSearch); ?>">
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select form-select-sm">
                        <option value="">Tất cả trạng thái</option>
                        <?php foreach (['active'=>'Active','pending_payment'=>'Chờ thanh toán','expired'=>'Hết hạn','cancelled'=>'Đã huỷ','refunded'=>'Đã hoàn'] as $v=>$l): ?>
                        <option value="<?php echo $v; ?>" <?php echo $fStatus===$v?'selected':''; ?>><?php echo $l; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="fas fa-search me-1"></i>Lọc
                    </button>
                    <a href="memberships.php" class="btn btn-sm btn-outline-secondary ms-1">Reset</a>
                </div>
                <div class="col-auto ms-auto text-muted" style="font-size:.82rem;">
                    Tổng: <strong><?php echo $total; ?></strong> gói
                </div>
            </form>
        </div>
    </div>

    <!-- Table -->
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table mem-table mb-0">
                <thead>
                    <tr>
                        <th>Hội viên</th>
                        <th>Mã thẻ</th>
                        <th>Gói</th>
                        <th>Vé</th>
                        <th>Hiệu lực</th>
                        <th>Thanh toán</th>
                        <th>Trạng thái</th>
                        <th style="width:130px;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($rows)): ?>
                <tr><td colspan="8" class="text-center py-5 text-muted">Không có dữ liệu</td></tr>
                <?php endif; ?>
                <?php foreach ($rows as $r):
                    $tickets_used  = $r['tickets_used'] ?? 0;
                    $tickets_total = $r['free_tickets'] ?? 0;
                    $tickets_left  = max(0, $tickets_total - $tickets_used);
                    $pct = $tickets_total > 0 ? round($tickets_left / $tickets_total * 100) : 0;
                    $barColor = $pct > 50 ? '#10b981' : ($pct > 20 ? '#f59e0b' : '#ef4444');
                    $daysLeft = $r['end_date'] ? (int)ceil((strtotime($r['end_date']) - time()) / 86400) : 0;
                    $expiryColor = $daysLeft > 30 ? '#10b981' : ($daysLeft > 7 ? '#f59e0b' : '#ef4444');
                    $statusBadge = [
                        'active'          => '<span class="badge-status badge-active">Active</span>',
                        'pending_payment' => '<span class="badge-status badge-pending">Chờ TT</span>',
                        'expired'         => '<span class="badge-status badge-expired">Hết hạn</span>',
                        'cancelled'       => '<span class="badge-status badge-cancelled">Đã huỷ</span>',
                        'refunded'        => '<span class="badge-status badge-refunded">Đã hoàn</span>',
                    ][$r['status']] ?? '<span class="badge-status badge-expired">'.$r['status'].'</span>';
                ?>
                <tr>
                    <td>
                        <div style="font-weight:700;"><?php echo escape($r['user_name'] ?? '—'); ?></div>
                        <div style="font-size:.75rem;color:#9ca3af;"><?php echo escape($r['user_email'] ?? ''); ?></div>
                    </td>
                    <td>
                        <code style="font-size:.82rem;color:#6366f1;font-weight:700;"><?php echo escape($r['member_code']); ?></code>
                    </td>
                    <td>
                        <div style="font-weight:600;font-size:.82rem;"><?php echo escape($r['plan_name']); ?></div>
                        <div style="font-size:.75rem;color:#9ca3af;"><?php echo escape($r['plan_detail']); ?></div>
                        <div style="font-size:.72rem;color:#6366f1;"><?php echo $r['months']; ?> tháng · <?php echo number_format($r['price']); ?>đ</div>
                    </td>
                    <td style="min-width:90px;">
                        <div style="font-weight:700;font-size:.88rem;"><?php echo $tickets_left; ?> / <?php echo $tickets_total; ?></div>
                        <div class="ticket-bar">
                            <div class="ticket-bar-fill" style="width:<?php echo $pct; ?>%;background:<?php echo $barColor; ?>;"></div>
                        </div>
                        <div style="font-size:.7rem;color:#9ca3af;">Đã dùng <?php echo $tickets_used; ?></div>
                    </td>
                    <td style="min-width:120px;">
                        <?php if ($r['start_date']): ?>
                        <div style="font-size:.8rem;"><?php echo date('d/m/Y', strtotime($r['start_date'])); ?> →</div>
                        <div style="font-size:.8rem;"><?php echo date('d/m/Y', strtotime($r['end_date'])); ?></div>
                        <div style="font-size:.72rem;font-weight:700;color:<?php echo $expiryColor; ?>;">
                            <?php echo $r['status']==='active' ? "Còn $daysLeft ngày" : ''; ?>
                        </div>
                        <?php else: ?>
                        <span style="font-size:.75rem;color:#9ca3af;">Chưa kích hoạt</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div style="font-size:.8rem;">
                            <?php
                            $pmLabel = ['cash'=>'Tiền mặt','bank_transfer'=>'Chuyển khoản','vnpay'=>'MB Bank','momo'=>'MoMo'][$r['payment_method']] ?? $r['payment_method'];
                            $psColor = $r['payment_status']==='paid' ? '#10b981' : '#f59e0b';
                            $psLabel = $r['payment_status']==='paid' ? 'Đã TT' : 'Chưa TT';
                            echo escape($pmLabel);
                            ?>
                        </div>
                        <div style="font-size:.72rem;font-weight:700;color:<?php echo $psColor; ?>;"><?php echo $psLabel; ?></div>
                        <div style="font-size:.7rem;color:#9ca3af;"><?php echo date('d/m/y', strtotime($r['created_at'])); ?></div>
                    </td>
                    <td><?php echo $statusBadge; ?></td>
                    <td>
                        <div class="d-flex gap-1 flex-wrap">
                        <?php if ($r['status'] === 'pending_payment'): ?>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="mid" value="<?php echo $r['id']; ?>">
                                <input type="hidden" name="act" value="activate">
                                <button type="submit" class="btn btn-xs btn-success py-0 px-2" style="font-size:.72rem;"
                                        onclick="return confirm('Kích hoạt hội viên này?')" title="Kích hoạt">
                                    <i class="fas fa-check"></i> Kích hoạt
                                </button>
                            </form>
                        <?php endif; ?>
                        <?php if (in_array($r['status'], ['active','pending_payment'])): ?>
                            <!-- Thêm vé -->
                            <button class="btn btn-xs btn-outline-primary py-0 px-2" style="font-size:.72rem;"
                                    onclick="showAddTickets(<?php echo $r['id']; ?>, '<?php echo escape($r['member_code']); ?>')"
                                    title="Thêm vé">
                                <i class="fas fa-ticket-alt"></i>
                            </button>
                            <!-- Huỷ -->
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="mid" value="<?php echo $r['id']; ?>">
                                <input type="hidden" name="act" value="cancel">
                                <button type="submit" class="btn btn-xs btn-outline-danger py-0 px-2" style="font-size:.72rem;"
                                        onclick="return confirm('Huỷ gói hội viên này?')" title="Huỷ">
                                    <i class="fas fa-times"></i>
                                </button>
                            </form>
                        <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="card-footer bg-white border-0 py-3">
            <nav>
                <ul class="pagination pagination-sm justify-content-center mb-0">
                    <?php for ($i=1; $i<=$totalPages; $i++): ?>
                    <li class="page-item <?php echo $i===$page?'active':''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo urlencode($fStatus); ?>&q=<?php echo urlencode($fSearch); ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
    </div>

</div>
</div>

<!-- Modal thêm vé -->
<div class="modal fade" id="addTicketsModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius:16px;">
            <div class="modal-header border-0" style="background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff;border-radius:16px 16px 0 0;">
                <h6 class="modal-title fw-bold"><i class="fas fa-ticket-alt me-2"></i>Thêm vé hội viên</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="post">
                <div class="modal-body p-4">
                    <input type="hidden" name="act" value="add_tickets">
                    <input type="hidden" name="mid" id="addTicketsMid">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Mã thẻ</label>
                        <div id="addTicketsCode" class="fw-bold text-primary" style="font-family:monospace;"></div>
                    </div>
                    <div>
                        <label class="form-label fw-bold">Số vé cần thêm</label>
                        <input type="number" name="tickets" class="form-control" min="1" max="50" value="1" required>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Huỷ</button>
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus me-1"></i>Thêm vé
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function showAddTickets(mid, code) {
    document.getElementById('addTicketsMid').value  = mid;
    document.getElementById('addTicketsCode').textContent = code;
    new bootstrap.Modal(document.getElementById('addTicketsModal')).show();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
