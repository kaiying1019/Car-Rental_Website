<?php
require_once __DIR__ . '/../includes/config.php';

ensureCartLoaded();

$conn = getDB();
$action = $_REQUEST['action'] ?? '';

// Handle Cart Actions
if ($action === 'remove') {
    $car_id = (int)($_GET['id'] ?? 0);
    if ($car_id > 0 && isset($_SESSION['cart'][$car_id])) {
        $carName = $_SESSION['cart'][$car_id]['brand'] . ' ' . $_SESSION['cart'][$car_id]['model'];
        unset($_SESSION['cart'][$car_id]);
        saveCartToDatabase();
        setMessage("Removed " . htmlspecialchars($carName) . " from your cart.", 'success');
    }
    redirect('cart.php');
}

if ($action === 'clear') {
    $_SESSION['cart'] = [];
    saveCartToDatabase();
    setMessage("Your cart has been cleared.", 'success');
    redirect('cart.php');
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container">
  <div class="page-header-row">
    <h2><i class="fas fa-shopping-cart"></i> Your Rental Cart</h2>
    <a href="../items/cars.php" class="btn btn-secondary btn-inline"><i class="fas fa-arrow-left"></i> Browse More Cars</a>
  </div>

  <?= displayMessage() ?>

  <?php if (empty($_SESSION['cart'])): ?>
    <div class="empty-state form-card cart-empty-box">
      <i class="fas fa-shopping-cart"></i>
      <h3>Your cart is currently empty</h3>
      <p class="cart-empty-desc">Select a car from our rental fleet to add it to your cart.</p>
      <a href="../items/cars.php" class="btn btn-primary"><i class="fas fa-car"></i> View Cars</a>
    </div>
  <?php else: ?>
    <form method="POST" action="cart.php">
      <div class="cart-grid-layout">
        
        <div>
          <div class="cart-table-wrap">
            <table class="cart-table">
              <thead>
                <tr>
                  <th>Select</th>
                  <th>Car Vehicle</th>
                  <th>Rate / Day</th>
                  <th class="cart-table-header-days">Rental Days</th>
                  <th class="cart-table-right">Subtotal</th>
                  <th class="cart-table-header-remove">Remove</th>
                </tr>
              </thead>
              <tbody>
                <?php 
                $grandTotal = 0;
                $totalDaysSum = 0;
                foreach ($_SESSION['cart'] as $carId => $item): 
                  $subtotal = $item['price_per_day'] * $item['days'];
                  $grandTotal += $subtotal;
                  $totalDaysSum += $item['days'];
                ?>
                  <tr>
                    <td class="cart-table-center">
                      <input type="checkbox" name="selected_cars[]" value="<?= (int)$carId ?>" aria-label="Select <?= htmlspecialchars($item['brand'] . ' ' . $item['model']) ?> for checkout">
                    </td>
                    <td>
                      <div class="cart-item-info">
                        <?php 
                        $serverImagePath = __DIR__ . '/../assets/images/' . $item['image'];
                        $webImagePath = '../assets/images/' . $item['image'];
                        if (!empty($item['image']) && file_exists($serverImagePath)): ?>
                          <img src="<?= htmlspecialchars($webImagePath) ?>" alt="<?= htmlspecialchars($item['brand'] . ' ' . $item['model']) ?>" class="cart-item-img">
                        <?php else: ?>
                          <div class="cart-item-img car-img-placeholder"><i class="fas fa-car"></i></div>
                        <?php endif; ?>
                        <div>
                          <a href="car-details.php?id=<?= $carId ?>" class="cart-item-title"><?= htmlspecialchars($item['brand'] . ' ' . $item['model']) ?> (<?= (int)$item['year'] ?>)</a>
                          <div class="cart-item-sub">
                            <?= ucfirst(htmlspecialchars($item['transmission'])) ?> &middot; <?= ucfirst(htmlspecialchars($item['fuel_type'])) ?> &middot; <?= (int)$item['seats'] ?> seats
                          </div>
                        </div>
                      </div>
                    </td>
                    <td >
                      RM<?= number_format($item['price_per_day'], 2) ?>
                    </td>
                    <td class="cart-table-center">
                      <input type="number" name="days[<?= $carId ?>]" value="<?= (int)$item['days'] ?>" min="1" max="90" class="days-input" required>
                    </td>
                    <td class="cart-table-subtotal" data-subtotal data-price-per-day="<?= htmlspecialchars((string)$item['price_per_day']) ?>">
                      RM<?= number_format($subtotal, 2) ?>
                    </td>
                    <td class="cart-table-center">
                      <a href="cart.php?action=remove&id=<?= $carId ?>" class="btn btn-danger btn-sm" title="Remove Item" onclick="return confirm('Remove this car from your cart?');">
                        <i class="fas fa-trash"></i>
                      </a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>

          <div class="cart-actions-bar">
            <a href="cart.php?action=clear" class="btn btn-danger btn-inline" onclick="return confirm('Are you sure you want to clear your entire cart?');">
              <i class="fas fa-trash-alt"></i> Clear All Cart
            </a>
          </div>
        </div>

        <!-- Summary Column -->
        <div class="cart-summary">
          <h3 class="cart-summary-title">Cart Summary</h3>
          
          <div class="summary-row">
            <span>Vehicles Selected</span>
            <strong id="selected-vehicle-count">0</strong>
          </div>
          <div class="summary-row">
            <span>Total Rental Days</span>
            <strong id="selected-days-total">0 day(s)</strong>
          </div>
          <div class="summary-row">
            <span>Price / Day</span>
            <strong id="selected-price-per-day">RM0.00</strong>
          </div>
          <div class="summary-row">
            <span>Refundable Deposit</span>
            <span id="selected-deposit">RM0.00</span>
          </div>

          <div class="summary-row total">
            <span class="summary-total-label">Total Price<br><small>(Rental + Deposit)</small></span>
            <span id="selected-grand-total">RM0.00</span>
          </div>

          <button type="submit" formaction="checkout.php" formmethod="POST" class="btn btn-success btn-block cart-checkout-btn" id="checkout-button" disabled>
            <i class="fas fa-check-circle"></i> Proceed to Checkout
          </button>
          
          <p class="cart-lock-note">
            <i class="fas fa-lock"></i> Free cancellation &amp; easy modification
          </p>
        </div>

      </div>
    </form>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>