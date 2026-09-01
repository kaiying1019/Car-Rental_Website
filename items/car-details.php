<?php
require_once '../includes/config.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    setMessage('Invalid car selected.', 'error');
    redirect('cars.php');
}

$conn = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    if (!isLoggedIn()) {
        setMessage('You must be logged in to submit a review.', 'error');
        redirect('../auth/login.php');
    }

    $rating = (int)($_POST['rating'] ?? 0);
    $comment = sanitize($_POST['comment'] ?? '');
    $car_id = (int)($_POST['car_id'] ?? 0);

    if ($car_id != $id) {
        setMessage('Invalid car for review.', 'error');
        redirect('car-details.php?id=' . $id);
    }

    if ($rating < 1 || $rating > 5) {
        setMessage('Please select a valid rating between 1 and 5.', 'error');
    } elseif (empty($comment)) {
        setMessage('Please write a comment for your review.', 'error');
    } else {
        $userId = $_SESSION['user_id'];
        $insertReview = $conn->prepare("INSERT INTO reviews (car_id, user_id, rating, comment) VALUES (?, ?, ?, ?)");
        $insertReview->bind_param('iiis', $car_id, $userId, $rating, $comment);

        if ($insertReview->execute()) {
            setMessage('Thank you! Your review has been posted successfully.', 'success');
        } else {
            setMessage('Something went wrong. Please try again later.', 'error');
        }
    }
    redirect('car-details.php?id=' . $id);
}

$stmt = $conn->prepare("SELECT cars.*, users.username AS listed_by
                         FROM cars LEFT JOIN users ON cars.added_by = users.id
                         WHERE cars.id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$car = $stmt->get_result()->fetch_assoc();

if (!$car) {
    setMessage('Car not found.', 'error');
    redirect('cars.php');
}

$reviewStmt = $conn->prepare("SELECT reviews.*, users.username
                               FROM reviews JOIN users ON reviews.user_id = users.id
                               WHERE reviews.car_id = ?
                               ORDER BY reviews.created_at DESC");
$reviewStmt->bind_param('i', $id);
$reviewStmt->execute();
$reviews = $reviewStmt->get_result();

$avgStmt = $conn->prepare("SELECT ROUND(AVG(rating),1) AS avg_rating, COUNT(*) AS total FROM reviews WHERE car_id = ?");
$avgStmt->bind_param('i', $id);
$avgStmt->execute();
$ratingData = $avgStmt->get_result()->fetch_assoc();

$branchesResult = $conn->query("SELECT * FROM branches ORDER BY city, name");
$branches = $branchesResult->fetch_all(MYSQLI_ASSOC);

$bookingErrors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_car'])) {
    if (!isLoggedIn()) {
        setMessage('Please login to book a car.', 'error');
        redirect('../auth/login.php');
    }

    $pickupBranch = (int)($_POST['pickup_branch'] ?? 0);
    $dropoffBranch = (int)($_POST['dropoff_branch'] ?? 0);
    $pickupDatetime = $_POST['pickup_datetime'] ?? '';
    $dropoffDatetime = $_POST['dropoff_datetime'] ?? '';
    $licenceType = sanitize($_POST['licence_type'] ?? '');
    $paymentMethod = sanitize($_POST['payment_method'] ?? '');

    if ($pickupBranch <= 0 || $dropoffBranch <= 0) $bookingErrors[] = 'Please select a pick-up and drop-off branch.';
    if (empty($pickupDatetime) || empty($dropoffDatetime)) $bookingErrors[] = 'Please select pick-up and drop-off date/time.';
    if (!in_array($licenceType, ['original_idp', 'original_licence', 'malaysia_licence'])) $bookingErrors[] = 'Please select a driving licence type.';
    if (!in_array($paymentMethod, ['visa', 'mastercard', 'amex', 'jcb', 'unionpay'])) $bookingErrors[] = 'Please select a payment method.';

    $totalDays = 0;
    $totalPrice = 0;

    if (empty($bookingErrors)) {
        try {
            $pickupDT = new DateTime($pickupDatetime);
            $dropoffDT = new DateTime($dropoffDatetime);

            if ($dropoffDT <= $pickupDT) {
                $bookingErrors[] = 'Drop-off must be after pick-up.';
            } elseif ($pickupDT < new DateTime()) {
                $bookingErrors[] = 'Pick-up time cannot be in the past.';
            } else {
                $totalDays = max(1, $pickupDT->diff($dropoffDT)->days);
                $totalPrice = $totalDays * $car['price_per_day'];
            }
        } catch (Exception $e) {
            $bookingErrors[] = 'Invalid date/time selected.';
        }
    }

    if (empty($bookingErrors)) {
        $depositAmount = 200.00;
        $insert = $conn->prepare("INSERT INTO bookings (car_id, user_id, pickup_branch_id, dropoff_branch_id, pickup_datetime, dropoff_datetime, licence_type, payment_method, total_days, total_price, deposit_amount, status)
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'confirmed')");
        $insert->bind_param('iiiissssidd', $id, $_SESSION['user_id'], $pickupBranch, $dropoffBranch, $pickupDatetime, $dropoffDatetime, $licenceType, $paymentMethod, $totalDays, $totalPrice, $depositAmount);

        if ($insert->execute()) {
          $_SESSION['last_checkout'] = [
            'booking_ids' => [$insert->insert_id],
            'items' => [[
              'car_id' => $car['id'],
              'brand' => $car['brand'],
              'model' => $car['model'],
              'year' => $car['year'],
              'price_per_day' => (float)$car['price_per_day'],
              'transmission' => $car['transmission'],
              'days' => $totalDays
            ]],
            'pickup_branch' => $pickupBranch,
            'dropoff_branch' => $dropoffBranch,
            'pickup_datetime' => $pickupDatetime,
            'dropoff_datetime' => $dropoffDatetime,
            'licence_type' => $licenceType,
            'payment_method' => $paymentMethod,
            'total_price' => $totalPrice + $depositAmount,
            'total_deposit' => $depositAmount
          ];
          redirect('../carts/confirmation.php');
        } else {
            $bookingErrors[] = 'Something went wrong while saving your booking. Please try again.';
        }
    }
}

$contactErrors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_inquiry'])) {
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $message = sanitize($_POST['message'] ?? '');
    $userId = isLoggedIn() ? $_SESSION['user_id'] : null;

    if (empty($name) || empty($email) || empty($message)) {
        $contactErrors[] = 'Name, email, and message are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $contactErrors[] = 'Please enter a valid email address.';
    }

    if (empty($contactErrors)) {
        $insertInquiry = $conn->prepare("INSERT INTO inquiries (car_id, user_id, name, email, phone, message) VALUES (?, ?, ?, ?, ?, ?)");
        $insertInquiry->bind_param('iissss', $id, $userId, $name, $email, $phone, $message);

        if ($insertInquiry->execute()) {
            setMessage('Your message has been sent. Our team will get back to you shortly.', 'success');
            redirect('car-details.php?id=' . $id);
        } else {
            $contactErrors[] = 'Something went wrong sending your message. Please try again.';
        }
    }
}

include '../includes/header.php';
?>

<script>
const BRANCHES = <?= json_encode($branches) ?>;
function updateMap(selectId, mapId) {
  const select = document.getElementById(selectId);
  const branch = BRANCHES.find(b => b.id == select.value);
  const frame = document.getElementById(mapId);
  if (branch) {
    frame.src = 'https://www.google.com/maps?q=' + encodeURIComponent(branch.address) + '&output=embed';
  }
}
</script>

<div class="container" style="padding: 30px 20px;">
  <?= displayMessage() ?>
  <a href="cars.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to all cars</a>

  <div class="car-details-wrap">
    <div class="car-details-img">
      <?php if (!empty($car['image'])): ?>
        <img src="../assets/images/<?= htmlspecialchars($car['image']) ?>" alt="<?= htmlspecialchars($car['brand'] . ' ' . $car['model']) ?>">
      <?php else: ?>
        <div class="car-img-placeholder large"><i class="fas fa-car"></i></div>
      <?php endif; ?>
    </div>

    <div class="car-details-info">
      <span class="badge badge-<?= $car['status'] ?>"><?= ucfirst($car['status']) ?></span>
      <h2><?= htmlspecialchars($car['brand'] . ' ' . $car['model']) ?> (<?= (int)$car['year'] ?>)</h2>
      <p class="car-price large">RM<?= number_format($car['price_per_day'], 2) ?> <span>/ day</span></p>

      <ul class="spec-list">
        <li><i class="fas fa-cog"></i> <?= htmlspecialchars(ucfirst($car['transmission'])) ?></li>
        <li><i class="fas fa-gas-pump"></i> <?= htmlspecialchars(ucfirst($car['fuel_type'])) ?></li>
        <li><i class="fas fa-user-friends"></i> <?= (int)$car['seats'] ?> seats</li>
      </ul>

      <?php if ($ratingData['total'] > 0): ?>
        <p class="car-rating"><i class="fas fa-star"></i> <?= $ratingData['avg_rating'] ?> average (<?= $ratingData['total'] ?> reviews)</p>
      <?php else: ?>
        <p class="car-rating car-rating-empty">No reviews yet</p>
      <?php endif; ?>

      <p class="car-description"><?= nl2br(htmlspecialchars($car['description'])) ?></p>
      <p class="car-listed-by">Listed by <?= htmlspecialchars($car['listed_by'] ?? 'GoCar Admin') ?></p>

      <?php if (isAdmin()): ?>
        <div class="car-card-actions">
          <a href="edit-car.php?id=<?= $car['id'] ?>" class="btn btn-secondary">Edit Car</a>
          <a href="delete-car.php?id=<?= $car['id'] ?>" class="btn btn-danger" onclick="return confirm('Delete this car? This cannot be undone.');">Delete Car</a>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <?php if ($car['status'] === 'available'): ?>
  <div class="booking-section">
    <h3>Book This Car</h3>

    <?php if (!empty($bookingErrors)): ?>
      <div class="alert alert-error"><?= implode('<br>', $bookingErrors) ?></div>
    <?php endif; ?>

    <form method="POST" class="form-card">
      <input type="hidden" name="book_car" value="1">

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

      <button type="submit" class="btn btn-primary">Confirm Booking</button>
    </form>
  </div>
  <?php endif; ?>

  <div class="contact-section">
    <h3>Questions About This Car?</h3>
    <div class="contact-wrap">
      <div class="contact-info-card">
        <h4>Talk to GoCar Rental</h4>
        <p>We're here to help with availability, pricing, or anything else before you book.</p>
        <ul class="contact-methods">
          <li><i class="fas fa-phone"></i> <a href="tel:+60526615774">+60 5-266 15774</a></li>
          <li><i class="fas fa-envelope"></i> <a href="mailto:info@gocarrental.com">info@gocarrental.com</a></li>
          <li><i class="fab fa-whatsapp"></i> <a href="https://wa.me/60526615774" target="_blank" rel="noopener">Chat on WhatsApp</a></li>
          <li><i class="fas fa-clock"></i> Daily, 8:00 AM &ndash; 10:00 PM</li>
        </ul>
      </div>

      <div class="contact-form-card">
        <?php if (!empty($contactErrors)): ?>
          <div class="alert alert-error"><?= implode('<br>', $contactErrors) ?></div>
        <?php endif; ?>
        <form method="POST" class="form-card" style="margin-top:0;">
          <input type="hidden" name="send_inquiry" value="1">
          <div class="form-row">
            <div>
              <label for="name">Your Name</label>
              <input type="text" id="name" name="name" value="<?= htmlspecialchars($_SESSION['username'] ?? ($_POST['name'] ?? '')) ?>" required>
            </div>
            <div>
              <label for="email">Email</label>
              <input type="email" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
            </div>
          </div>
          <label for="phone">Phone (optional)</label>
          <input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
          <label for="message">Message</label>
          <textarea id="message" name="message" rows="4" required><?= htmlspecialchars($_POST['message'] ?? ('Hi, I\'m interested in the ' . $car['brand'] . ' ' . $car['model'] . '. ')) ?></textarea>
          <button type="submit" class="btn btn-primary" style="margin-top:16px;">Send Message</button>
        </form>
      </div>
    </div>
  </div>

  <div class="reviews-section">
    <h3>Reviews</h3>
    <?php if ($reviews->num_rows === 0): ?>
      <p class="empty-state">No reviews yet for this car.</p>
    <?php else: ?>
      <?php while ($review = $reviews->fetch_assoc()): ?>
        <div class="review-card">
          <div class="review-header">
            <strong><?= htmlspecialchars($review['username']) ?></strong>
            <span class="review-stars"><?= str_repeat('★', (int)$review['rating']) . str_repeat('☆', 5 - (int)$review['rating']) ?></span>
          </div>
          <p><?= nl2br(htmlspecialchars($review['comment'])) ?></p>
          <span class="review-date"><?= date('d M Y', strtotime($review['created_at'])) ?></span>
        </div>
      <?php endwhile; ?>
    <?php endif; ?>
  </div>

  <div class="reviews-section" style="margin-top: 40px; border-top: 2px solid #e2e8f0; padding-top: 30px;">
    <h3>Leave a Review</h3>
    
    <?php if (isLoggedIn()): ?>
      <form method="POST" class="form-card" style="padding: 24px; margin-top: 16px;">
        <input type="hidden" name="car_id" value="<?= $id ?>">
        <input type="hidden" name="submit_review" value="1">
        
        <div class="form-row">
          <div>
            <label for="rating">Your Rating</label>
            <select id="rating" name="rating" required style="width: 100%; padding: 10px; border: 1px solid #e2e8f0; border-radius: 8px;">
              <option value="">Select a rating</option>
              <option value="5">⭐ 5 - Excellent</option>
              <option value="4">⭐ 4 - Good</option>
              <option value="3">⭐ 3 - Average</option>
              <option value="2">⭐ 2 - Poor</option>
              <option value="1">⭐ 1 - Terrible</option>
            </select>
          </div>
        </div>

        <label for="review_comment">Your Comment</label>
        <textarea id="review_comment" name="comment" rows="4" placeholder="Share your experience driving this car..." required style="width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 8px; font-family: inherit;"></textarea>

        <button type="submit" class="btn btn-primary" style="margin-top: 16px;">
          <i class="fas fa-paper-plane"></i> Submit Review
        </button>
      </form>
    <?php else: ?>
      <div style="background: #f8fafc; padding: 20px; border-radius: 12px; text-align: center; border: 1px solid #e2e8f0;">
        <p style="margin-bottom: 0; color: #64748b;">
          <a href="../auth/login.php" style="color: #3b82f6; font-weight: 600;">Login</a> to leave a review for this car.
        </p>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php include '../includes/footer.php'; ?>
