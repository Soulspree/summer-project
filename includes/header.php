<?php
// Header template
// Provides opening HTML structure and loads CSS assets
// Usage: set $pageTitle before including for custom title

// Default title if not provided
if (!isset($pageTitle)) {
    $pageTitle = 'Musician Booking';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="stylesheet" href="/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/css/custom.css">
</head>
<body>
<?php include __DIR__ . '/navigation.php'; ?>
