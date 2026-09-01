<?php
require_once __DIR__ . '/../includes/config.php';

if (!isLoggedIn()) {
    redirect($nav_path . 'auth/login.php');
}

$checkout = $_SESSION['last_checkout'] ?? null;

if (!$checkout) {
    setMessage("No recent booking found.", 'error');
    redirect('cars.php');
}

$conn = getDB();

$pickupBranchName = 'Branch';
$dropoffBranchName = 'Branch';

if (!empty($checkout['pickup_branch'])) {
    $bStmt = $conn->prepare("SELECT name, city FROM branches WHERE id = ?");
    $bStmt->bind_param('i', $checkout['pickup_branch']);
    $bStmt->execute();
    if ($b = $bStmt->get_result()->fetch_assoc()) {
        $pickupBranchName = $b['name'] . ' (' . $b['city'] . ')';
    }
}

if (!empty($checkout['dropoff_branch'])) {
    $bStmt = $conn->prepare("SELECT name, city FROM branches WHERE id = ?");
    $bStmt->bind_param('i', $checkout['dropoff_branch']);
    $bStmt->execute();
    if ($b = $bStmt->get_result()->fetch_assoc()) {
        $dropoffBranchName = $b['name'] . ' (' . $b['city'] . ')';
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="confirmation-container">
  
  <div class="form-card confirmation-card">
    <div class="confirmation-icon">
      <i class="fas fa-check-circle"></i>
    </div>
    <h2 class="confirmation-title">Reservation Confirmed!</h2>
    <p class="confirmation-subtitle">Thank you, <?= htmlspecialchars($_SESSION['username'] ?? 'User') ?>. Your car rental booking has been successfully placed.</p>
  </div>

  <div class="form-card" style="margin-top: 24px;">
    <h3 class="checkout-section-title">
      <i class="fas fa-receipt"></i> Booking Details
    </h3>

    <div class="details-grid">
      <div>
        <span class="details-label">Booking Reference IDs</span>
        <strong>#<?= implode(', #', $checkout['booking_ids']) ?></strong>
      </div>
      <div>
        <span class="details-label">Payment Method</span>
        <strong><?= strtoupper(htmlspecialchars($checkout['payment_method'])) ?></strong>
      </div>
      <div>
        <span class="details-label">Pick-up Branch</span>
        <strong><?= htmlspecialchars($pickupBranchName) ?></strong>
      </div>
      <div>
        <span class="details-label">Drop-off Branch</span>
        <strong><?= htmlspecialchars($dropoffBranchName) ?></strong>
      </div>
      <div>
        <span class="details-label">Pick-up Date &amp; Time</span>
        <strong><?= date('d M Y, h:i A', strtotime($checkout['pickup_datetime'])) ?></strong>
      </div>
      <div>
        <span class="details-label">Drop-off Date &amp; Time</span>
        <strong><?= date('d M Y, h:i A', strtotime($checkout['dropoff_datetime'])) ?></strong>
      </div>
    </div>

    <h4 style="margin: 20px 0 12px 0; color: #0f172a;">Reserved Vehicles</h4>
    <div class="reserved-vehicles-box">
      <?php foreach ($checkout['items'] as $item): ?>
        <div class="checkout-item-row">
          <div>
            <strong><?= htmlspecialchars($item['brand'] . ' ' . $item['model']) ?> (<?= (int)$item['year'] ?>)</strong>
            <div class="checkout-item-sub"><?= (int)$item['days'] ?> day(s) &middot; <?= ucfirst(htmlspecialchars($item['transmission'])) ?></div>
          </div>
          <strong class="checkout-item-price">RM<?= number_format($item['price_per_day'] * $item['days'], 2) ?></strong>
        </div>
      <?php endforeach; ?>

      <div class="summary-row total">
        <span>Total Paid / Reserved</span>
        <span>RM<?= number_format($checkout['total_price'], 2) ?></span>
      </div>
    </div>

    <div class="info-notice-box">
      <h4 class="info-notice-title"><i class="fas fa-info-circle"></i> What to Bring at Pick-Up</h4>
      <ul class="info-notice-list">
        <li>Original Identification (Passport or MyKad)</li>
        <li>Original Physical Driving Licence (matching selected licence type)</li>
        <li>Physical Credit Card for the RM<?= number_format($checkout['total_deposit'], 2) ?> refundable deposit</li>
      </ul>
    </div>

    <div class="action-row">
      <a href="../items/cars.php" class="btn btn-primary"><i class="fas fa-car"></i> Browse More Cars</a>
      <a href="<?= $nav_path ?>index.php" class="btn btn-secondary"><i class="fas fa-home"></i> Return to Home</a>
    </div>

  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>