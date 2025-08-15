<?php
define('SYSTEM_ACCESS', true);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../classes/User.php';

requireAdminAuth();

$userModel = new User();

// Handle status changes
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'], $_POST['action'])) {
    $userId = (int)$_POST['user_id'];
    if ($_POST['action'] === 'activate') {
        $userModel->update($userId, ['account_status' => ACCOUNT_STATUS_ACTIVE]);
    } elseif ($_POST['action'] === 'suspend') {
        $userModel->update($userId, ['account_status' => ACCOUNT_STATUS_SUSPENDED]);
    }
    header('Location: users.php');
    exit;
}

$users = $userModel->read([], 'created_at DESC');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manage Users</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
  <div class="container-fluid">
    <a class="navbar-brand" href="index.php">Admin</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav" aria-controls="adminNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="adminNav">
      <ul class="navbar-nav me-auto">
        <li class="nav-item"><a class="nav-link active" href="users.php">Users</a></li>
        <li class="nav-item"><a class="nav-link" href="bookings.php">Bookings</a></li>
        <li class="nav-item"><a class="nav-link" href="payments.php">Payments</a></li>
      </ul>
    </div>
  </div>
</nav>
<div class="container">
    <h1 class="mb-4">Users</h1>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Type</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($users as $user): ?>
            <tr>
                <td><?= htmlspecialchars($user['user_id']); ?></td>
                <td><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                <td><?= htmlspecialchars($user['email']); ?></td>
                <td><?= htmlspecialchars($user['user_type']); ?></td>
                <td><?= htmlspecialchars($user['account_status']); ?></td>
                <td>
                    <form method="post" class="d-inline">
                        <input type="hidden" name="user_id" value="<?= $user['user_id']; ?>">
                        <?php if ($user['account_status'] === ACCOUNT_STATUS_ACTIVE): ?>
                            <button name="action" value="suspend" class="btn btn-sm btn-warning">Suspend</button>
                        <?php else: ?>
                            <button name="action" value="activate" class="btn btn-sm btn-success">Activate</button>
                        <?php endif; ?>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>