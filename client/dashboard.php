<?php
require_once '../config/config.php';
require_once '../config/constants.php';
require_once '../classes/Booking.php';
require_once '../classes/Gig.php';
require_once '../classes/Payment.php';

$clientId = $_SESSION['user_id'] ?? 0;

$booking = new Booking();
$gig = new Gig();
$payment = new Payment();

$upcomingResult = $booking->getClientBookings($clientId, ['status' => 'confirmed', 'date_from' => date('Y-m-d')], 1, 5);
$upcomingBookings = $upcomingResult['bookings'] ?? [];

$bookingStats = $booking->getClientBookingStats($clientId);

$paymentSummaryStmt = $payment->query(
    "SELECT SUM(CASE WHEN p.payment_status='paid' THEN p.amount ELSE 0 END) AS paid,
            SUM(CASE WHEN p.payment_status='pending' THEN p.amount ELSE 0 END) AS pending
     FROM payments p
     JOIN bookings b ON p.booking_id = b.booking_id
     WHERE b.client_id = ?",
    [$clientId]
);
$paymentSummary = $paymentSummaryStmt ? $paymentSummaryStmt->fetch() : ['paid'=>0,'pending'=>0];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Client Dashboard</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>
<?php include '../includes/navigation.php'; ?>
<div class="container mt-4">
    <h1 class="mb-4">Client Dashboard</h1>
    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">Upcoming Bookings</div>
                <ul class="list-group list-group-flush">
                    <?php if (!empty($upcomingBookings)): ?>
                        <?php foreach ($upcomingBookings as $b): ?>
                            <li class="list-group-item">
                                <strong><?= htmlspecialchars($b['event_title']); ?></strong><br>
                                <?= htmlspecialchars($b['event_date']); ?> with <?= htmlspecialchars($b['client_username'] ?? ''); ?>
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
    </div>
    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">Spending Summary</div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item">Total Paid: $<?= number_format($paymentSummary['paid'] ?? 0, 2); ?></li>
                    <li class="list-group-item">
                        Pending Payments: $<?= number_format($paymentSummary['pending'] ?? 0, 2); ?>
                    </li>
                </ul>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">Related Gigs</div>
                <ul class="list-group list-group-flush">
                    <?php
                    $relatedGigs = [];
                    if (!empty($upcomingBookings)) {
                        $musicianIds = array_unique(array_column($upcomingBookings, 'musician_id'));
                        foreach ($musicianIds as $mId) {
                            $relatedGigs = array_merge($relatedGigs, $gig->getUpcomingGigs($mId, 1));
                        }
                    }
                    ?>
                    <?php if (!empty($relatedGigs)): ?>
                        <?php foreach ($relatedGigs as $g): ?>
                            <li class="list-group-item">
                                <strong><?= htmlspecialchars($g['title']); ?></strong><br>
                                <?= htmlspecialchars($g['gig_date']); ?> at <?= htmlspecialchars($g['venue_name']); ?>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li class="list-group-item">No related gigs.</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</div>
</body>
</html>