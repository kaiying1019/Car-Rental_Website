<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/CarRentalWebsite/includes/config.php'; ?>
<?php $cart_count = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0; ?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GoCar Rental</title>
  <link rel="stylesheet" href="<?= $nav_path ?>assets/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>

<body>
  <header class="header">
    <div class="container header-container">
      <a href="<?= $nav_path ?>index.php" class="logo">
        <i class="fas fa-car"></i>
        <span>GoCar</span>
      </a>

      <button class="hamburger" id="hamburger" aria-label="Toggle menu">
        <span></span>
        <span></span>
        <span></span>
      </button>

      <nav class="nav" id="nav">
        <ul class="nav-list">
          <li><a href="<?= $nav_path ?>index.php">Home</a></li>
          <li><a href="<?= $nav_path ?>items/cars.php">Cars</a></li>

          <li>
            <a href="<?= $nav_path ?>carts/cart.php" class="cart-link">
              <i class="fas fa-shopping-cart"></i> Cart
              <?php if ($cart_count > 0): ?>
                <span class="cart-badge"><?= $cart_count ?></span>
              <?php endif; ?>
            </a>
          </li>

          <li><a href="<?= $nav_path ?>contact.php">Contact</a></li>

          <?php if (isLoggedIn()): ?>
            <li><a href="<?= $nav_path ?>dashboard.php">Dashboard</a></li>
            <li><a href="<?= $nav_path ?>auth/logout.php" class="btn-logout">Logout</a></li>
          <?php else: ?>
            <li><a href="<?= $nav_path ?>auth/login.php" class="btn-login">Login</a></li>
            <li><a href="<?= $nav_path ?>auth/register.php" class="btn-register">Register</a></li>
          <?php endif; ?>
        </ul>
      </nav>
    </div>
  </header>