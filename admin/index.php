<?php
define('SYSTEM_ACCESS', true);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Booking.php';
require_once __DIR__ . '/../classes/Payment.php';

requireAdminAuth();

// Initialize models
$userModel = new User();
$bookingModel = new Booking();
$paymentModel = new Payment();

// Gather statistics
$totalUsers = $userModel->count();
$bookingDb = new Database('bookings', 'booking_id');
$activeGigs = $bookingDb->count(['booking_status' => Booking::STATUS_CONFIRMED]) +
              $bookingDb->count(['booking_status' => Booking::STATUS_IN_PROGRESS]);
$paymentDb = new Database('payments', 'payment_id');
$revenueStmt = $paymentDb->query(
    "SELECT COALESCE(SUM(amount),0) as total FROM payments WHERE payment_status = ?",
    [Payment::STATUS_PAID]
);
$revenue = $revenueStmt ? $revenueStmt->fetch()['total'] : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Dashboard</title>
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
        <li class="nav-item"><a class="nav-link" href="payments.php">Payments</a></li>
      </ul>
    </div>
  </div>
</nav>
<div class="container">
    <h1 class="mb-4">Dashboard</h1>
    <div class="row g-4">
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title">Total Users</h5>
                    <p class="display-6 mb-0"><?= htmlspecialchars($totalUsers); ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title">Active Gigs</h5>
                    <p class="display-6 mb-0"><?= htmlspecialchars($activeGigs); ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title">Revenue</h5>
                    <p class="display-6 mb-0">Rs. <?= number_format($revenue, 2); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>