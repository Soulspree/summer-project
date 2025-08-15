<?php
define('SYSTEM_ACCESS', true);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Booking.php';
require_once __DIR__ . '/../classes/User.php';

requireAdminAuth();

$bookingModel = new Booking();
$bookingDb = new Database('bookings', 'booking_id');
$userModel = new User();

// Handle booking approvals
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['booking_id'], $_POST['action'])) {
    $bookingId = (int)$_POST['booking_id'];
    $booking = $bookingModel->getBookingById($bookingId);
    if ($booking) {
        if ($_POST['action'] === 'approve') {
            $bookingModel->updateBookingStatus($bookingId, Booking::STATUS_CONFIRMED, $booking['musician_id']);
        } elseif ($_POST['action'] === 'reject') {
            $bookingModel->updateBookingStatus($bookingId, Booking::STATUS_REJECTED, $booking['musician_id']);
        }
    }
    header('Location: bookings.php');
    exit;
}

$pendingBookings = $bookingDb->read(['booking_status' => Booking::STATUS_PENDING], 'created_at DESC');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Booking Approvals</title>
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
        <li class="nav-item"><a class="nav-link active" href="bookings.php">Bookings</a></li>
        <li class="nav-item"><a class="nav-link" href="payments.php">Payments</a></li>
      </ul>
    </div>
  </div>
</nav>
<div class="container">
    <h1 class="mb-4">Pending Bookings</h1>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Event</th>
                <th>Date</th>
                <th>Client</th>
                <th>Musician</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($pendingBookings as $booking): ?>
            <?php $client = $userModel->find($booking['client_id']); $musician = $userModel->find($booking['musician_id']); ?>
            <tr>
                <td><?= htmlspecialchars($booking['booking_id']); ?></td>
                <td><?= htmlspecialchars($booking['event_title']); ?></td>
                <td><?= htmlspecialchars($booking['event_date']); ?></td>
                <td><?= htmlspecialchars($client['first_name'] . ' ' . $client['last_name']); ?></td>
                <td><?= htmlspecialchars($musician['first_name'] . ' ' . $musician['last_name']); ?></td>
                <td>
                    <form method="post" class="d-inline">
                        <input type="hidden" name="booking_id" value="<?= $booking['booking_id']; ?>">
                        <button name="action" value="approve" class="btn btn-sm btn-success">Approve</button>
                        <button name="action" value="reject" class="btn btn-sm btn-danger">Reject</button>
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