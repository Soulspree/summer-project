<?php
require_once '../config/config.php';
require_once '../includes/functions.php';
require_once '../classes/Booking.php';
require_once '../classes/Musician.php';

$musician_id = isset($_GET['musician_id']) ? (int)$_GET['musician_id'] : 0;
$client_id   = $_SESSION['user_id'] ?? 0;

$musicianObj = new Musician();
$musician    = $musicianObj->getPublicMusicianProfile($musician_id);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bookingData = [
        'event_title'          => $_POST['event_title'] ?? '',
        'event_date'           => $_POST['event_date'] ?? '',
        'start_time'           => $_POST['start_time'] ?? '',
        'end_time'             => $_POST['end_time'] ?? '',
        'venue_name'           => $_POST['venue_name'] ?? '',
        'venue_address'        => $_POST['venue_address'] ?? '',
        'event_type'           => $_POST['event_type'] ?? '',
        'audience_size'        => $_POST['audience_size'] ?? '',
        'music_genres_requested'=> $_POST['music_genres_requested'] ?? '',
        'special_requests'     => $_POST['special_requests'] ?? '',
        'equipment_provided'   => $_POST['equipment_provided'] ?? ''
    ];

    $booking = new Booking();
    if (method_exists($booking, 'createRequest')) {
        $result = $booking->createRequest($client_id, $musician_id, $bookingData);
    } else {
        $result = $booking->createBookingRequest($client_id, $musician_id, $bookingData);
    }

    if ($result) {
        setFlashMessage('success', 'Booking request sent successfully.');
        header('Location: book-musician.php?musician_id=' . $musician_id);
        exit;
    } else {
        setFlashMessage('error', 'Failed to send booking request.');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Musician</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
    <h1 class="mb-4">Book Musician</h1>
    <?php echo displayFlashMessages(); ?>
    <?php if (!$musician): ?>
        <p>Musician not found.</p>
    <?php else: ?>
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title"><?php echo htmlspecialchars($musician['stage_name'] ?? ($musician['first_name'] . ' ' . $musician['last_name'])); ?></h5>
                <p class="card-text">Location: <?php echo htmlspecialchars($musician['city'] ?? $musician['location'] ?? ''); ?></p>
            </div>
        </div>

        <form method="post" class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Event Title</label>
                <input type="text" name="event_title" class="form-control" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Event Date</label>
                <input type="date" name="event_date" class="form-control" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Event Type</label>
                <input type="text" name="event_type" class="form-control" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Start Time</label>
                <input type="time" name="start_time" class="form-control" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">End Time</label>
                <input type="time" name="end_time" class="form-control">
            </div>
            <div class="col-md-6">
                <label class="form-label">Venue Name</label>
                <input type="text" name="venue_name" class="form-control" required>
            </div>
            <div class="col-12">
                <label class="form-label">Venue Address</label>
                <input type="text" name="venue_address" class="form-control">
            </div>
            <div class="col-md-4">
                <label class="form-label">Audience Size</label>
                <input type="number" name="audience_size" class="form-control">
            </div>
            <div class="col-md-4">
                <label class="form-label">Genres Requested</label>
                <input type="text" name="music_genres_requested" class="form-control">
            </div>
            <div class="col-md-4">
                <label class="form-label">Equipment Provided</label>
                <input type="text" name="equipment_provided" class="form-control">
            </div>
            <div class="col-12">
                <label class="form-label">Special Requests</label>
                <textarea name="special_requests" class="form-control" rows="3"></textarea>
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-primary">Submit Booking Request</button>
            </div>
        </form>
    <?php endif; ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
client/search-musicians.php
+71-1
 
<?php
require_once '../config/config.php';
require_once '../includes/functions.php';
require_once '../classes/Musician.php';

// Collect filters from query string
$filters = [
    'genre'     => $_GET['genre'] ?? null,
    'location'  => $_GET['location'] ?? null,
    'min_price' => $_GET['min_price'] ?? null,
    'max_price' => $_GET['max_price'] ?? null,
];

$musicianObj = new Musician();
$results = $musicianObj->searchMusicians($filters);
$musicians = $results['musicians'] ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Musicians</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
    <h1 class="mb-4">Search Musicians</h1>
    <?php echo displayFlashMessages(); ?>
    <form method="get" class="row g-3 mb-4">
        <div class="col-md-3">
            <input type="text" name="genre" class="form-control" placeholder="Genre" value="<?php echo htmlspecialchars($filters['genre'] ?? ''); ?>">
        </div>
        <div class="col-md-3">
            <input type="text" name="location" class="form-control" placeholder="Location" value="<?php echo htmlspecialchars($filters['location'] ?? ''); ?>">
        </div>
        <div class="col-md-2">
            <input type="number" step="0.01" name="min_price" class="form-control" placeholder="Min Price" value="<?php echo htmlspecialchars($filters['min_price'] ?? ''); ?>">
        </div>
        <div class="col-md-2">
            <input type="number" step="0.01" name="max_price" class="form-control" placeholder="Max Price" value="<?php echo htmlspecialchars($filters['max_price'] ?? ''); ?>">
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-primary w-100">Search</button>
        </div>
    </form>

    <div class="row">
        <?php if (empty($musicians)): ?>
            <p>No musicians found.</p>
        <?php else: ?>
            <?php foreach ($musicians as $musician): ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($musician['stage_name'] ?? ($musician['first_name'] . ' ' . $musician['last_name'])); ?></h5>
                            <p class="card-text">
                                <?php echo htmlspecialchars($musician['city'] ?? $musician['location'] ?? ''); ?><br>
                                <?php echo htmlspecialchars($musician['base_price_per_hour']); ?> per hour
                            </p>
                            <a href="book-musician.php?musician_id=<?php echo $musician['user_id']; ?>" class="btn btn-sm btn-success">Book</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>