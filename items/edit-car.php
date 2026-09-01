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
$errors = [];

$stmt = $conn->prepare("SELECT * FROM cars WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$car = $stmt->get_result()->fetch_assoc();

if (!$car) {
    setMessage('Car not found.', 'error');
    redirect('cars.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $brand = sanitize($_POST['brand'] ?? '');
    $model = sanitize($_POST['model'] ?? '');
    $year = (int)($_POST['year'] ?? 0);
    $price = (float)($_POST['price_per_day'] ?? 0);
    $transmission = sanitize($_POST['transmission'] ?? '');
    $fuel = sanitize($_POST['fuel_type'] ?? '');
    $seats = (int)($_POST['seats'] ?? 0);
    $description = sanitize($_POST['description'] ?? '');
    $status = sanitize($_POST['status'] ?? 'available');

    if (empty($brand) || empty($model)) $errors[] = 'Brand and model are required.';
    if ($year < 1990 || $year > (int)date('Y') + 1) $errors[] = 'Please enter a valid year.';
    if ($price <= 0) $errors[] = 'Price per day must be greater than 0.';
    if (!in_array($transmission, ['automatic', 'manual'])) $errors[] = 'Please select a valid transmission.';
    if (!in_array($fuel, ['petrol', 'diesel', 'hybrid', 'electric'])) $errors[] = 'Please select a valid fuel type.';
    if ($seats < 1 || $seats > 15) $errors[] = 'Seats must be between 1 and 15.';
    if (!in_array($status, ['available', 'rented', 'maintenance'])) $errors[] = 'Please select a valid status.';

    $imagePath = $car['image'];
    if (!empty($_FILES['image']['name'])) {
        $upload = uploadCarImage($_FILES['image']);
        if ($upload['success']) {
            $imagePath = $upload['filename'];
        } else {
            $errors[] = $upload['error'];
        }
    }

    if (empty($errors)) {
        $update = $conn->prepare("UPDATE cars SET brand=?, model=?, year=?, price_per_day=?, transmission=?, fuel_type=?, seats=?, image=?, description=?, status=? WHERE id=?");
        $update->bind_param('ssidssisssi', $brand, $model, $year, $price, $transmission, $fuel, $seats, $imagePath, $description, $status, $id);

        if ($update->execute()) {
            setMessage('Car updated successfully.', 'success');
            redirect('car-details.php?id=' . $id);
        } else {
            $errors[] = 'Something went wrong. Please try again.';
        }
    }

    // Keep the submitted values visible on the form if validation failed
    $car = array_merge($car, $_POST);
}

include '../includes/header.php';
?>

<div class="container" style="padding: 30px 20px; max-width: 700px;">
  <h2>Edit Car</h2>
  <?php if (!empty($errors)): ?>
    <div class="alert alert-error"><?= implode('<br>', $errors) ?></div>
  <?php endif; ?>

  <form method="POST" enctype="multipart/form-data" class="form-card">
    <div class="form-row">
      <div>
        <label for="brand">Brand</label>
        <input type="text" id="brand" name="brand" value="<?= htmlspecialchars($car['brand']) ?>" required>
      </div>
      <div>
        <label for="model">Model</label>
        <input type="text" id="model" name="model" value="<?= htmlspecialchars($car['model']) ?>" required>
      </div>
    </div>

    <div class="form-row">
      <div>
        <label for="year">Year</label>
        <input type="number" id="year" name="year" value="<?= htmlspecialchars($car['year']) ?>" required>
      </div>
      <div>
        <label for="price_per_day">Price per Day (RM)</label>
        <input type="number" step="0.01" id="price_per_day" name="price_per_day" value="<?= htmlspecialchars($car['price_per_day']) ?>" required>
      </div>
    </div>

    <div class="form-row">
      <div>
        <label for="transmission">Transmission</label>
        <select id="transmission" name="transmission" required>
          <option value="automatic" <?= $car['transmission'] === 'automatic' ? 'selected' : '' ?>>Automatic</option>
          <option value="manual" <?= $car['transmission'] === 'manual' ? 'selected' : '' ?>>Manual</option>
        </select>
      </div>
      <div>
        <label for="fuel_type">Fuel Type</label>
        <select id="fuel_type" name="fuel_type" required>
          <?php foreach (['petrol', 'diesel', 'hybrid', 'electric'] as $f): ?>
            <option value="<?= $f ?>" <?= $car['fuel_type'] === $f ? 'selected' : '' ?>><?= ucfirst($f) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="form-row">
      <div>
        <label for="seats">Seats</label>
        <input type="number" id="seats" name="seats" value="<?= htmlspecialchars($car['seats']) ?>" required>
      </div>
      <div>
        <label for="status">Status</label>
        <select id="status" name="status">
          <?php foreach (['available', 'rented', 'maintenance'] as $s): ?>
            <option value="<?= $s ?>" <?= $car['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <?php if (!empty($car['image'])): ?>
      <p>Current image: <?= htmlspecialchars($car['image']) ?></p>
    <?php endif; ?>
    <label for="image">Replace Car Image (optional)</label>
    <input type="file" id="image" name="image" accept="image/png, image/jpeg, image/webp">

    <label for="description">Description</label>
    <textarea id="description" name="description" rows="4"><?= htmlspecialchars($car['description']) ?></textarea>

    <button type="submit" class="btn btn-primary">Save Changes</button>
  </form>
</div>

<?php include '../includes/footer.php'; ?>
