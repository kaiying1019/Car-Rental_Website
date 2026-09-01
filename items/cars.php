<?php
require_once '../includes/config.php';

$search = sanitize($_GET['search'] ?? '');
$transmission = sanitize($_GET['transmission'] ?? '');
$fuel = sanitize($_GET['fuel'] ?? '');
$seats = (int)($_GET['seats'] ?? 0);
$sort = sanitize($_GET['sort'] ?? '');

$conn = getDB();

ensureCartLoaded();

$action = $_REQUEST['action'] ?? '';

if ($action === 'add') {
  if (!isLoggedIn()) {
    setMessage('Please login to add a car to your cart.', 'error');
    redirect('../auth/login.php');
  }

    $car_id = (int)($_REQUEST['id'] ?? 0);
    $days = max(1, (int)($_REQUEST['days'] ?? 1));

    if ($car_id > 0) {
        $stmt = $conn->prepare("SELECT * FROM cars WHERE id = ?");
        $stmt->bind_param('i', $car_id);
        $stmt->execute();
        $car = $stmt->get_result()->fetch_assoc();

        if ($car) {
            if (isset($_SESSION['cart'][$car_id])) {
                $_SESSION['cart'][$car_id]['days'] += $days;
            } else {
                $_SESSION['cart'][$car_id] = [
                    'car_id'        => $car['id'],
                    'brand'         => $car['brand'],
                    'model'         => $car['model'],
                    'year'          => $car['year'],
                    'price_per_day' => (float)$car['price_per_day'],
                    'image'         => $car['image'],
                    'transmission'  => $car['transmission'],
                    'fuel_type'     => $car['fuel_type'],
                    'seats'         => $car['seats'],
                    'status'        => $car['status'],
                    'days'          => $days
                ];
            }

            saveCartToDatabase();

            setMessage("Added " . htmlspecialchars($car['brand'] . ' ' . $car['model']) . " to your cart.", 'success');
        } else {
            setMessage("Car not found.", 'error');
        }
    }
    redirect('cars.php');
}

$sql = "SELECT cars.*, users.username AS listed_by,
        (SELECT ROUND(AVG(rating),1) FROM reviews WHERE reviews.car_id = cars.id) AS avg_rating,
        (SELECT COUNT(*) FROM reviews WHERE reviews.car_id = cars.id) AS review_count
        FROM cars
        LEFT JOIN users ON cars.added_by = users.id
        WHERE 1=1";
$params = [];
$types = '';

if ($search !== '') {
    $sql .= " AND (cars.brand LIKE ? OR cars.model LIKE ?)";
    $like = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $types .= 'ss';
}

if ($transmission !== '' && in_array($transmission, ['automatic', 'manual'])) {
    $sql .= " AND cars.transmission = ?";
    $params[] = $transmission;
    $types .= 's';
}

if ($fuel !== '' && in_array($fuel, ['petrol', 'diesel', 'hybrid', 'electric'])) {
    $sql .= " AND cars.fuel_type = ?";
    $params[] = $fuel;
    $types .= 's';
}

if ($seats > 0) {
    $sql .= " AND cars.seats >= ?";
    $params[] = $seats;
    $types .= 'i';
}

if ($sort === 'price_asc') {
    $sql .= " ORDER BY cars.price_per_day ASC";
} elseif ($sort === 'price_desc') {
    $sql .= " ORDER BY cars.price_per_day DESC";
} else {
    $sql .= " ORDER BY cars.created_at DESC";
}

$stmt = $conn->prepare($sql);
if ($types !== '') {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$cars = $stmt->get_result();

include '../includes/header.php';
?>

<div class="container" style="padding: 30px 20px;">
  
  <!-- Enhanced Admin Header -->
  <div class="page-header-row">
    <div>
      <h2>Browse Cars</h2>
      <p style="color: #64748b; margin-top: 4px;">Manage your rental fleet inventory</p>
    </div>
    <?php if (isAdmin()): ?>
      <div style="display: flex; gap: 12px;">
        <a href="add-cars.php" class="btn btn-primary" style="background: #0f2b5c; color: white; padding: 12px 24px; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 6px rgba(15, 43, 92, 0.3);">
          <i class="fas fa-plus-circle"></i> Add New Car
        </a>
      </div>
    <?php endif; ?>
  </div>

  <?= displayMessage() ?>

  <form method="GET" class="filter-bar">
    <input type="text" name="search" placeholder="Search brand or model..." value="<?= htmlspecialchars($search) ?>">
    <select name="transmission">
      <option value="">All Transmissions</option>
      <option value="automatic" <?= $transmission === 'automatic' ? 'selected' : '' ?>>Automatic</option>
      <option value="manual" <?= $transmission === 'manual' ? 'selected' : '' ?>>Manual</option>
    </select>
    <select name="fuel">
      <option value="">All Fuel Types</option>
      <option value="petrol" <?= $fuel === 'petrol' ? 'selected' : '' ?>>Petrol</option>
      <option value="diesel" <?= $fuel === 'diesel' ? 'selected' : '' ?>>Diesel</option>
      <option value="hybrid" <?= $fuel === 'hybrid' ? 'selected' : '' ?>>Hybrid</option>
      <option value="electric" <?= $fuel === 'electric' ? 'selected' : '' ?>>Electric</option>
    </select>
    <select name="seats">
      <option value="">Any Seats</option>
      <option value="2" <?= $seats === 2 ? 'selected' : '' ?>>2+ Seats</option>
      <option value="4" <?= $seats === 4 ? 'selected' : '' ?>>4+ Seats</option>
      <option value="5" <?= $seats === 5 ? 'selected' : '' ?>>5+ Seats</option>
      <option value="7" <?= $seats === 7 ? 'selected' : '' ?>>7+ Seats</option>
      <option value="8" <?= $seats === 8 ? 'selected' : '' ?>>8+ Seats</option>
    </select>
    <select name="sort">
      <option value="">Newest</option>
      <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Price: Low to High</option>
      <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Price: High to Low</option>
    </select>
    <button type="submit" class="btn btn-primary btn-inline">Filter</button>
  </form>

  <div class="car-grid">
    <?php if ($cars->num_rows === 0): ?>
      <p class="empty-state">No cars found matching your search.</p>
    <?php else: ?>
      <?php while ($car = $cars->fetch_assoc()): ?>
        <div class="car-card">
          <div class="car-card-img">
            <?php if (!empty($car['image'])): ?>
              <img src="../assets/images/<?= htmlspecialchars($car['image']) ?>" alt="<?= htmlspecialchars($car['brand'] . ' ' . $car['model']) ?>">
            <?php else: ?>
              <div class="car-img-placeholder"><i class="fas fa-car"></i></div>
            <?php endif; ?>
            <span class="badge badge-<?= $car['status'] ?>"><?= ucfirst($car['status']) ?></span>
          </div>
          <div class="car-card-body">
            <h3><?= htmlspecialchars($car['brand'] . ' ' . $car['model']) ?></h3>
            <p class="car-year"><?= (int)$car['year'] ?> &middot; <?= htmlspecialchars(ucfirst($car['transmission'])) ?> &middot; <?= htmlspecialchars(ucfirst($car['fuel_type'])) ?></p>
            <p class="car-price">RM<?= number_format($car['price_per_day'], 2) ?> <span>/ day</span></p>
            <?php if ($car['review_count'] > 0): ?>
              <p class="car-rating"><i class="fas fa-star"></i> <?= $car['avg_rating'] ?> (<?= $car['review_count'] ?> reviews)</p>
            <?php else: ?>
              <p class="car-rating car-rating-empty">No reviews yet</p>
            <?php endif; ?>
            <p class="car-listed-by">Listed by <?= htmlspecialchars($car['listed_by'] ?? 'GoCar Admin') ?></p>
            
            <div class="car-card-actions">
              <a href="car-details.php?id=<?= $car['id'] ?>" class="btn btn-primary btn-block">View Details</a>
              
              <?php if ($car['status'] === 'available'): ?>
                <?php if (isLoggedIn()): ?>
                  <a href="cars.php?action=add&id=<?= $car['id'] ?>" class="btn btn-warning" title="Add to Cart">
                    <i class="fas fa-cart-plus"></i> Add to Cart
                  </a>
                <?php else: ?>
                  <a href="../auth/login.php" class="btn btn-warning" title="Login to add to cart">
                    <i class="fas fa-sign-in-alt"></i> Login to Add
                  </a>
                <?php endif; ?>
              <?php endif; ?>
              
              <!-- Enhanced Admin Action Buttons -->
              <?php if (isAdmin()): ?>
                <div style="display: flex; gap: 10px; margin-top: 12px; width: 100%;">
                  <a href="edit-car.php?id=<?= $car['id'] ?>" class="btn btn-secondary" style="flex: 1; background: #f1f5f9; color: #0f2b5c; border: 1px solid #cbd5e1; padding: 8px 12px;">
                    <i class="fas fa-pen"></i> Edit
                  </a>
                  <a href="delete-car.php?id=<?= $car['id'] ?>" class="btn btn-danger" style="flex: 1; background: #fee2e2; color: #dc2626; border: 1px solid #fca5a5; padding: 8px 12px;" onclick="return confirm('Are you sure you want to permanently delete this car? This cannot be undone.');">
                    <i class="fas fa-trash"></i> Delete
                  </a>
                </div>
              <?php endif; ?>
              
            </div>
          </div>
        </div>
      <?php endwhile; ?>
    <?php endif; ?>
  </div>
</div>

<?php include '../includes/footer.php'; ?>