<?php
require_once __DIR__ . '/../includes/config.php';

if (!isLoggedIn()) {
    setMessage("Please login to complete your checkout.", 'error');
    redirect($nav_path . 'auth/login.php');
}

ensureCartLoaded();

if (empty($_SESSION['cart'])) {
    setMessage("Your cart is empty.", 'error');
    redirect('cart.php');
}

$selectedCarIds = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  foreach ((array)($_POST['selected_cars'] ?? []) as $carId) {
    $carId = (int)$carId;
    if ($carId > 0 && isset($_SESSION['cart'][$carId])) {
      $selectedCarIds[] = $carId;
    }
  }

  if (empty($selectedCarIds)) {
    setMessage('Please select at least one car to checkout.', 'error');
    redirect('cart.php');
  }
} else {
  $selectedCarIds = array_keys($_SESSION['cart']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  foreach ($selectedCarIds as $carId) {
    if (isset($_POST['days'][$carId])) {
      $_SESSION['cart'][$carId]['days'] = max(1, min(90, (int)$_POST['days'][$carId]));
    }
  }
}

$selectedItems = [];
foreach ($selectedCarIds as $carId) {
  $selectedItems[$carId] = $_SESSION['cart'][$carId];
}
$_SESSION['checkout_car_ids'] = $selectedCarIds;

$conn = getDB();

$branchesResult = $conn->query("SELECT * FROM branches ORDER BY city, name");
$branches = $branchesResult->fetch_all(MYSQLI_ASSOC);

$errors = [];

$grandTotal = 0;
foreach ($selectedItems as $item) {
    $grandTotal += $item['price_per_day'] * $item['days'];
}
$totalDeposit = count($selectedItems) * 200.00;
$totalPayable = $grandTotal + $totalDeposit;
$selectedPricePerDay = 0;
foreach ($selectedItems as $item) {
  $selectedPricePerDay += $item['price_per_day'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $pickupBranch = (int)($_POST['pickup_branch'] ?? 0);
    $dropoffBranch = (int)($_POST['dropoff_branch'] ?? 0);
    $pickupDatetime = $_POST['pickup_datetime'] ?? '';
    $dropoffDatetime = $_POST['dropoff_datetime'] ?? '';
    $licenceType = sanitize($_POST['licence_type'] ?? '');
    $paymentMethod = sanitize($_POST['payment_method'] ?? '');

    if ($pickupBranch <= 0 || $dropoffBranch <= 0) $errors[] = 'Please select a pick-up and drop-off branch.';
    if (empty($pickupDatetime) || empty($dropoffDatetime)) $errors[] = 'Please select pick-up and drop-off date/time.';
    if (!in_array($licenceType, ['original_idp', 'original_licence', 'malaysia_licence'])) $errors[] = 'Please select a driving licence type.';
    if (!in_array($paymentMethod, ['visa', 'mastercard', 'amex', 'jcb', 'unionpay'])) $errors[] = 'Please select a payment method.';

    if (empty($errors)) {
        try {
        $pickupDate = new DateTime($pickupDatetime);
        $dropoffDate = new DateTime($dropoffDatetime);
        if ($dropoffDate <= $pickupDate) {
          $errors[] = 'Drop-off must be after pick-up.';
        } elseif ($pickupDate < new DateTime()) {
          $errors[] = 'Pick-up time cannot be in the past.';
            }
        } catch (Exception $e) {
        $errors[] = 'Invalid date/time selected.';
        }
    }

    if (empty($errors)) {
      $userId = $_SESSION['user_id'];
      $createdBookingIds = [];
      $stmt = $conn->prepare("INSERT INTO bookings
        (car_id, user_id, pickup_branch_id, dropoff_branch_id, pickup_datetime, dropoff_datetime, licence_type, payment_method, total_days, total_price, deposit_amount, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'confirmed')");

      foreach ($selectedItems as $item) {
        $carId = (int)$item['car_id'];
        $days = (int)$item['days'];
        $itemTotalPrice = $days * (float)$item['price_per_day'];
        $depositAmount = 200.00;

        $stmt->bind_param(
          'iiiissssidd',
          $carId,
          $userId,
          $pickupBranch,
          $dropoffBranch,
          $pickupDatetime,
          $dropoffDatetime,
          $licenceType,
          $paymentMethod,
          $days,
          $itemTotalPrice,
          $depositAmount
        );

        if ($stmt->execute()) {
          $createdBookingIds[] = $stmt->insert_id;
        } else {
          $errors[] = 'Something went wrong while saving the reservation.';
          break;
        }
      }

      if (empty($errors) && !empty($createdBookingIds)) {
        $_SESSION['last_checkout'] = [
          'booking_ids' => $createdBookingIds,
          'items' => $selectedItems,
          'pickup_branch' => $pickupBranch,
          'dropoff_branch' => $dropoffBranch,
          'pickup_datetime' => $pickupDatetime,
          'dropoff_datetime' => $dropoffDatetime,
          'licence_type' => $licenceType,
          'payment_method' => $paymentMethod,
          'total_price' => $totalPayable,
          'total_deposit' => $totalDeposit
        ];

        foreach ($selectedCarIds as $carId) {
          unset($_SESSION['cart'][$carId]);
        }
        unset($_SESSION['checkout_car_ids']);
        saveCartToDatabase();
        redirect('confirmation.php');
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<script>
const BRANCHES = <?= json_encode($branches) ?>;
function updateMap(selectId, mapId) {
  const select = document.getElementById(selectId);
  const branch = BRANCHES.find(function(item) { return item.id == select.value; });
  const frame = document.getElementById(mapId);
  if (branch && frame) {
    frame.src = 'https://www.google.com/maps?q=' + encodeURIComponent(branch.address) + '&output=embed';
  }
}
</script>

<div class="container">
  <div class="page-header-row">
    <h2><i class="fas fa-credit-card"></i> Rental Checkout</h2>
    <a href="cart.php" class="btn btn-secondary btn-inline"><i class="fas fa-arrow-left"></i> Return to Cart</a>
  </div>

  <?= displayMessage() ?>

  <?php if (!empty($errors)): ?>
    <div class="alert alert-error"><?= implode('<br>', $errors) ?></div>
  <?php endif; ?>

  <form method="POST" action="checkout.php">
    <input type="hidden" name="place_order" value="1">
    <?php foreach ($selectedCarIds as $carId): ?>
      <input type="hidden" name="selected_cars[]" value="<?= (int)$carId ?>">
      <input type="hidden" name="days[<?= (int)$carId ?>]" value="<?= (int)$selectedItems[$carId]['days'] ?>">
    <?php endforeach; ?>

    <div class="checkout-grid">
      <div class="form-card" style="margin-top:0;">
      <h4 class="section-title">Pick-up &amp; Drop-off</h4>
      <div class="form-row">
        <div>
          <label for="pickup_branch">Pick-up Branch</label>
          <select id="pickup_branch" name="pickup_branch" onchange="updateMap('pickup_branch','pickup_map')" required>
            <option value="">Select branch</option>
            <?php foreach ($branches as $b): ?>
              <option value="<?= $b['id'] ?>" <?= (isset($_POST['pickup_branch']) && $_POST['pickup_branch'] == $b['id']) ? 'selected' : '' ?>><?= htmlspecialchars($b['name']) ?> &mdash; <?= htmlspecialchars($b['city']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label for="dropoff_branch">Drop-off Branch</label>
          <select id="dropoff_branch" name="dropoff_branch" onchange="updateMap('dropoff_branch','dropoff_map')" required>
            <option value="">Select branch</option>
            <?php foreach ($branches as $b): ?>
              <option value="<?= $b['id'] ?>" <?= (isset($_POST['dropoff_branch']) && $_POST['dropoff_branch'] == $b['id']) ? 'selected' : '' ?>><?= htmlspecialchars($b['name']) ?> &mdash; <?= htmlspecialchars($b['city']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      
      <div class="form-row"> 
        <div class="map-box"><iframe id="pickup_map" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe><span class="map-label">Pick-up location</span></div>
        <div class="map-box"><iframe id="dropoff_map" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe><span class="map-label">Drop-off location</span></div>
      </div>


        <div class="form-row">
        <div>
          <label for="pickup_datetime">Pick-up Date &amp; Time</label>
          <input type="datetime-local" id="pickup_datetime" name="pickup_datetime" value="<?= htmlspecialchars($_POST['pickup_datetime'] ?? '') ?>" required>
        </div>
        <div>
          <label for="dropoff_datetime">Drop-off Date &amp; Time</label>
          <input type="datetime-local" id="dropoff_datetime" name="dropoff_datetime" value="<?= htmlspecialchars($_POST['dropoff_datetime'] ?? '') ?>" required>
        </div>
      </div>
<h4 class="section-title">What to Bring at Pick-up</h4>
      <div class="requirements-box">
        <p><strong>Identification</strong> &mdash; a valid passport or national ID (original, not a photo or scan).</p>
        <p><strong>Driver age</strong> &mdash; main and additional drivers must be between 21 and 70 years old. Drivers under 25 may be charged a young-driver surcharge; check with the branch for current rates.</p>
        <p><strong>Driving licence</strong> &mdash; an original physical licence is required at pick-up. Photos, learner's permits, temporary permits, or region-restricted licences are not accepted. Choose which type of licence you'll bring:</p>

        <label class="radio-row"><input type="radio" name="licence_type" value="original_licence" <?= (($_POST['licence_type'] ?? '') === 'original_licence') ? 'checked' : '' ?> required> <strong>Original driving licence</strong> &mdash; a full, valid licence issued by the main driver's home country, in a category that covers this vehicle.</label>
        <label class="radio-row"><input type="radio" name="licence_type" value="original_idp" <?= (($_POST['licence_type'] ?? '') === 'original_idp') ? 'checked' : '' ?>> <strong>Original licence + International Driving Permit</strong> &mdash; required for drivers whose licence isn't recognised locally (e.g. mainland China, which hasn't ratified the UN Road Traffic Convention). Passport, licence, and IDP must all be issued by the same country. Vehicles with more than 9 seats require a Category D IDP.</label>
        <label class="radio-row"><input type="radio" name="licence_type" value="malaysia_licence" <?= (($_POST['licence_type'] ?? '') === 'malaysia_licence') ? 'checked' : '' ?>> <strong>Malaysian driving licence</strong> &mdash; a locally issued licence, in a category that covers this vehicle's seat count.</label>

        <p style="margin-top:14px;"><strong>Credit card</strong> &mdash; must be a physical card in the main driver's name, matching their passport, with a chip and embossed number and enough available limit to cover the deposit. Debit, virtual, and cashback cards aren't accepted.</p>
        <p><strong>Accepted cards</strong> &mdash; Visa, Mastercard, American Express, JCB, and UnionPay dual-logo cards.</p>
        <p><strong>Deposit</strong> &mdash; approximately RM200.00, pre-authorised on your card at the branch and released roughly 30&ndash;45 days after return (once traffic violations are cleared). No SMS is sent when it's released, so check your card limit directly.</p>
      </div>


        <h4 class="section-title">Payment Method</h4>
      <div class="form-row">
        <div>
          <label for="payment_method">Card Type for Deposit &amp; Payment</label>
          <select id="payment_method" name="payment_method" required>
            <option value="">Select card type</option>
            <option value="visa" <?= (($_POST['payment_method'] ?? '') === 'visa') ? 'selected' : '' ?>>Visa</option>
            <option value="mastercard" <?= (($_POST['payment_method'] ?? '') === 'mastercard') ? 'selected' : '' ?>>Mastercard</option>
            <option value="amex" <?= (($_POST['payment_method'] ?? '') === 'amex') ? 'selected' : '' ?>>American Express</option>
            <option value="jcb" <?= (($_POST['payment_method'] ?? '') === 'jcb') ? 'selected' : '' ?>>JCB</option>
            <option value="unionpay" <?= (($_POST['payment_method'] ?? '') === 'unionpay') ? 'selected' : '' ?>>UnionPay</option>
          </select>
        </div>
      </div>
      </div>

      
      <div class="cart-summary">
        <h3 class="checkout-section-title">Order Summary</h3>
        
        <div>
          <?php foreach ($selectedItems as $item): ?>
            <div class="checkout-item-row">
              <div>
                <strong><?= htmlspecialchars($item['brand'] . ' ' . $item['model']) ?></strong>
                <div class="checkout-item-sub"><?= (int)$item['days'] ?> day(s) @ RM<?= number_format($item['price_per_day'], 2) ?>/day</div>
              </div>
              <strong class="checkout-item-price">RM<?= number_format($item['price_per_day'] * $item['days'], 2) ?></strong>
            </div>
          <?php endforeach; ?>
        </div>

        <div class="summary-row">
          <span>Vehicles</span>
          <strong><?= count($selectedItems) ?></strong>
        </div>
        <div class="summary-row">
          <span>Price / Day</span>
          <strong>RM<?= number_format($selectedPricePerDay, 2) ?></strong>
        </div>
        <div class="summary-row">
          <span>Est. Refundable Deposit</span>
          <span>RM<?= number_format($totalDeposit, 2) ?></span>
        </div>
        <div class="summary-row total">
          <span class="summary-total-label">Total Price<br><small>(Rental + Deposit)</small></span>
          <span>RM<?= number_format($totalPayable, 2) ?></span>
        </div>

        <button type="submit" class="btn btn-success btn-block" style="padding: 14px; margin-top: 20px; font-size: 1.05rem;">
          <i class="fas fa-check-circle"></i> Confirm &amp; Complete Reservation
        </button>
      </div>
    </div>
  </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>