 
 
<?php
define('SYSTEM_ACCESS', true);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Payment.php';

requireAdminAuth();

$paymentDb = new Database('payments', 'payment_id');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['payment_id'], $_POST['action'])) {
    $paymentId = (int)$_POST['payment_id'];
    if ($_POST['action'] === 'mark_paid') {
        $paymentDb->update($paymentId, ['payment_status' => Payment::STATUS_PAID]);
    }
    header('Location: payments.php');
    exit;
}

$payments = $paymentDb->read([], 'payment_date DESC');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Payments</title>
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
        <li class="nav-item"><a class="nav-link" href="users.php">Users</a></li>
        <li class="nav-item"><a class="nav-link" href="bookings.php">Bookings</a></li>
        <li class="nav-item"><a class="nav-link active" href="payments.php">Payments</a></li>
      </ul>
    </div>
  </div>
</nav>
<div class="container">
    <h1 class="mb-4">Payments</h1>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Booking</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Date</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($payments as $payment): ?>
            <tr>
                <td><?= htmlspecialchars($payment['payment_id']); ?></td>
                <td><?= htmlspecialchars($payment['booking_id']); ?></td>
                <td><?= htmlspecialchars($payment['amount']); ?></td>
                <td><?= htmlspecialchars($payment['payment_status']); ?></td>
                <td><?= htmlspecialchars($payment['payment_date']); ?></td>
                <td>
                    <?php if ($payment['payment_status'] !== Payment::STATUS_PAID): ?>
                    <form method="post" class="d-inline">
                        <input type="hidden" name="payment_id" value="<?= $payment['payment_id']; ?>">
                        <button name="action" value="mark_paid" class="btn btn-sm btn-success">Mark Paid</button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>