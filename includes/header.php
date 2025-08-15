<?php
// Header template
// Provides opening HTML structure and loads CSS assets
// Usage: set $pageTitle before including for custom title
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/functions.php';
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
     <link rel="preconnect" href="https://fonts.googleapis.com">
     <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
     <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/css/custom.css">
     <link rel="stylesheet" href="/assets/css/responsive.css">
</head>
<body>
<?php include __DIR__ . '/navigation.php'; ?>
