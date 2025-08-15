<?php
require_once '../config/config.php';
require_once '../config/constants.php';
require_once '../classes/Booking.php';
require_once '../classes/Gig.php';
require_once '../classes/Payment.php';

$musicianId = $_SESSION['user_id'] ?? 0;

$booking = new Booking();
$gig = new Gig();
$payment = new Payment();

$upcomingBookings = $booking->getUpcomingBookings($musicianId, 5);
$bookingStats = $booking->getMusicianBookingStats($musicianId);
$paymentStats = $payment->getMusicianPaymentStats($musicianId);
$paymentOverview = $paymentStats['data']['overview'] ?? [];
$upcomingGigs = $gig->getUpcomingGigs($musicianId, 5);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Musician Dashboard</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>
<?php include '../includes/navigation.php'; ?>
<div class="container mt-4">
    <h1 class="mb-4">Musician Dashboard</h1>
    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">Upcoming Bookings</div>
                <ul class="list-group list-group-flush">
                    <?php if (!empty($upcomingBookings)): ?>
                        <?php foreach ($upcomingBookings as $b): ?>
                            <li class="list-group-item">
                                <strong><?= htmlspecialchars($b['event_title']); ?></strong><br>
                                <?= htmlspecialchars($b['event_date']); ?> at <?= htmlspecialchars($b['venue_name']); ?>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li class="list-group-item">No upcoming bookings.</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">Upcoming Gigs</div>
                <ul class="list-group list-group-flush">
                    <?php if (!empty($upcomingGigs)): ?>
                        <?php foreach ($upcomingGigs as $g): ?>
                            <li class="list-group-item">
                                <strong><?= htmlspecialchars($g['title']); ?></strong><br>
                                <?= htmlspecialchars($g['gig_date']); ?> at <?= htmlspecialchars($g['venue_name']); ?>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li class="list-group-item">No upcoming gigs.</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">Booking Statuses</div>
                <ul class="list-group list-group-flush">
                    <?php if (!empty($bookingStats['by_status'])): ?>
                        <?php foreach ($bookingStats['by_status'] as $status => $count): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <?= ucfirst(str_replace('_', ' ', $status)); ?>
                                <span class="badge bg-primary rounded-pill"><?= $count; ?></span>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li class="list-group-item">No booking data.</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">Earnings Summary</div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item">Total Earned: $<?= number_format($paymentOverview['total_earned'] ?? 0, 2); ?></li>
                    <li class="list-group-item">Pending Amount: $<?= number_format($paymentOverview['pending_amount'] ?? 0, 2); ?></li>
                </ul>
            </div>
        </div>
    </div>
</div>
</body>
</html>