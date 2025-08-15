<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';
requireMusicianAuth();

require_once __DIR__ . '/../classes/Musician.php';
require_once __DIR__ . '/../classes/FileUpload.php';

$musician = new Musician();
$userId = getCurrentUserId();
$profile = $musician->getMusicianProfile($userId);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCSRF();

    $updateData = [
        'genres' => array_filter(array_map('trim', explode(',', $_POST['genre'] ?? ''))),
        'base_price_per_event' => $_POST['price_range'] ?? '',
        'availability_status' => $_POST['availability'] ?? ''
    ];

    if (!empty($_FILES['profile_image']['name'])) {
        $uploader = new FileUpload();
        $upload = $uploader->upload($_FILES['profile_image'], 'profiles');
        if ($upload['success']) {
            $updateData['profile_image'] = $upload['filename'];
        } else {
            $error = $upload['message'];
        }
    }

    $result = $musician->updateMusicianProfile($userId, $updateData);
    if ($result['success']) {
        $profile = $musician->getMusicianProfile($userId);
        $success = $result['message'];
    } else {
        $error = $result['message'];
    }
}

$csrfToken = generateCSRFToken();

include __DIR__ . '/../includes/header.php';
?>

<div class="container mt-4">
    <h2>Edit Profile</h2>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken); ?>">

        <div class="mb-3">
            <label for="genre" class="form-label">Genre</label>
            <input type="text" class="form-control" id="genre" name="genre" value="<?= htmlspecialchars(implode(', ', json_decode($profile['genres'] ?? '[]', true))); ?>">
            <div class="form-text">Comma separated list</div>
        </div>

        <div class="mb-3">
            <label for="price_range" class="form-label">Price Range (per event)</label>
            <input type="number" step="0.01" class="form-control" id="price_range" name="price_range" value="<?= htmlspecialchars($profile['base_price_per_event'] ?? ''); ?>">
        </div>

        <div class="mb-3">
            <label for="availability" class="form-label">Availability</label>
            <select class="form-select" id="availability" name="availability">
                <option value="available" <?= (isset($profile['availability_status']) && $profile['availability_status'] === 'available') ? 'selected' : ''; ?>>Available</option>
                <option value="unavailable" <?= (isset($profile['availability_status']) && $profile['availability_status'] === 'unavailable') ? 'selected' : ''; ?>>Unavailable</option>
            </select>
        </div>

        <div class="mb-3">
            <label for="profile_image" class="form-label">Profile Image</label>
            <input class="form-control" type="file" id="profile_image" name="profile_image">
        </div>

        <button type="submit" class="btn btn-primary">Save Changes</button>
    </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>