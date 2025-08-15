 
<?php
define('SYSTEM_ACCESS', true);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Musician.php';

header('Content-Type: application/json');

$query = trim($_GET['q'] ?? '');
$filters = [];
if ($query !== '') {
    $filters['search'] = $query;
}

try {
    $musician = new Musician();
    $result = $musician->searchMusicians($filters, 1, 10);
    echo json_encode($result);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['musicians' => [], 'error' => 'Search error']);
}