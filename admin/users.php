<?php
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();
$isAdminPage = true;
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'], $_POST['status'])) {
    $user_id = intval($_POST['user_id']);
    $status = intval($_POST['status']);
    $stmt = $mysqli->prepare('UPDATE users SET status = ? WHERE id = ?');
    $stmt->bind_param('ii', $status, $user_id);
    $stmt->execute();
    $message = 'Cập nhật trạng thái tài khoản thành công.';
}
$users = getAllUsers();
require_once __DIR__ . '/../includes/header.php';
?>
<div class="row">
    <div class="col-12">
        <div class="card shadow-sm mb-4">
            <div class="card-body d-flex justify-content-between align-items-center flex-column flex-md-row gap-3">
                <div>
                    <h2 class="card-title">Quản lý người dùng</h2>
                    <p class="text-muted mb-0">Khóa hoặc mở tài khoản user.</p>
                </div>
                <a href="dashboard.php" class="btn btn-outline-secondary">Dashboard</a>
            </div>
        </div>
    </div>
</div>
<?php if ($message): ?>
    <div class="alert alert-success"><?php echo escape($message); ?></div>
<?php endif; ?>
<div class="card shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped align-middle">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Họ tên</th>
                        <th>Email</th>
                        <th>Vai trò</th>
                        <th>Trạng thái</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo escape($user['id']); ?></td>
                            <td><?php echo escape($user['name']); ?></td>
                            <td><?php echo escape($user['email']); ?></td>
                            <td><?php echo ucfirst(escape($user['role'])); ?></td>
                            <td><?php echo $user['status'] ? 'Hoạt động' : 'Khóa'; ?></td>
                            <td>
                                <form method="post" class="d-flex gap-2">
                                    <input type="hidden" name="user_id" value="<?php echo escape($user['id']); ?>">
                                    <select name="status" class="form-select form-select-sm">
                                        <option value="1" <?php echo $user['status'] ? 'selected' : ''; ?>>Mở</option>
                                        <option value="0" <?php echo !$user['status'] ? 'selected' : ''; ?>>Khóa</option>
                                    </select>
                                    <button type="submit" class="btn btn-sm btn-primary">Lưu</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
