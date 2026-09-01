<?php
require_once '../includes/config.php';

if (!isAdmin()) {
    setMessage('You do not have permission to access this page.', 'error');
    redirect('cars.php');
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    setMessage('Invalid car selected.', 'error');
    redirect('cars.php');
}

$conn = getDB();

$stmt = $conn->prepare("SELECT image FROM cars WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$car = $stmt->get_result()->fetch_assoc();

if (!$car) {
    setMessage('Car not found.', 'error');
    redirect('cars.php');
}

// Reviews for this car are removed automatically via ON DELETE CASCADE on reviews.car_id
$delete = $conn->prepare("DELETE FROM cars WHERE id = ?");
$delete->bind_param('i', $id);

if ($delete->execute()) {
    if (!empty($car['image']) && file_exists($car['image'])) {
        unlink($car['image']);
    }
    setMessage('Car deleted successfully.', 'success');
} else {
    setMessage('Something went wrong. Please try again.', 'error');
}

redirect('cars.php');
