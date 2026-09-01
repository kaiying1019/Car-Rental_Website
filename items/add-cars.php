<?php
require_once '../includes/config.php';

if (!isAdmin()) {
    setMessage('You do not have permission to access this page.', 'error');
    redirect('cars.php');
}

$errors = [];

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

    $imagePath = null;
    if (!empty($_FILES['image']['name'])) {
        $upload = uploadCarImage($_FILES['image']);
        if ($upload['success']) {
            $imagePath = $upload['filename'];
        } else {
            $errors[] = $upload['error'];
        }
    }

    if (empty($errors)) {
        $conn = getDB();
        $stmt = $conn->prepare("INSERT INTO cars (brand, model, year, price_per_day, transmission, fuel_type, seats, image, description, status, added_by)
                                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('ssidssisssi', $brand, $model, $year, $price, $transmission, $fuel, $seats, $imagePath, $description, $status, $_SESSION['user_id']);

        if ($stmt->execute()) {
            setMessage('Car added successfully.', 'success');
            redirect('cars.php');
        } else {
            $errors[] = 'Something went wrong. Please try again.';
        }
    }
}

include '../includes/header.php';
?>

<div class="container" style="padding: 30px 20px; max-width: 700px;">
  <h2>Add New Car</h2>
  <?= displayMessage() ?>
  <?php if (!empty($errors)): ?>
    <div class="alert alert-error"><?= implode('<br>', $errors) ?></div>
  <?php endif; ?>

  <form method="POST" enctype="multipart/form-data" class="form-card">
    <div class="form-row">
      <div>
        <label for="brand">Brand</label>
        <input type="text" id="brand" name="brand" value="<?= htmlspecialchars($_POST['brand'] ?? '') ?>" required>
      </div>
      <div>
        <label for="model">Model</label>
        <input type="text" id="model" name="model" value="<?= htmlspecialchars($_POST['model'] ?? '') ?>" required>
      </div>
    </div>

    <div class="form-row">
      <div>
        <label for="year">Year</label>
        <input type="number" id="year" name="year" value="<?= htmlspecialchars($_POST['year'] ?? date('Y')) ?>" required>
      </div>
      <div>
        <label for="price_per_day">Price per Day (RM)</label>
        <input type="number" step="0.01" id="price_per_day" name="price_per_day" value="<?= htmlspecialchars($_POST['price_per_day'] ?? '') ?>" required>
      </div>
    </div>

    <div class="form-row">
      <div>
        <label for="transmission">Transmission</label>
        <select id="transmission" name="transmission" required>
          <option value="automatic">Automatic</option>
          <option value="manual">Manual</option>
        </select>
      </div>
      <div>
        <label for="fuel_type">Fuel Type</label>
        <select id="fuel_type" name="fuel_type" required>
          <option value="petrol">Petrol</option>
          <option value="diesel">Diesel</option>
          <option value="hybrid">Hybrid</option>
          <option value="electric">Electric</option>
        </select>
      </div>
    </div>

    <div class="form-row">
      <div>
        <label for="seats">Seats</label>
        <input type="number" id="seats" name="seats" value="<?= htmlspecialchars($_POST['seats'] ?? 5) ?>" required>
      </div>
      <div>
        <label for="status">Status</label>
        <select id="status" name="status">
          <option value="available">Available</option>
          <option value="rented">Rented</option>
          <option value="maintenance">Maintenance</option>
        </select>
      </div>
    </div>

    <label for="image">Car Image</label>
    <input type="file" id="image" name="image" accept="image/png, image/jpeg, image/webp">

    <label for="description">Description</label>
    <textarea id="description" name="description" rows="4"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>

    <button type="submit" class="btn btn-primary">Add Car</button>
  </form>
</div>

<?php include '../includes/footer.php'; ?>
