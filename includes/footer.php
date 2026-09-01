<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/CarRentalWebsite/includes/config.php'; ?>

<footer class="footer">
    <div class="container footer-container">
      <div class="footer-col">
        <h3><i class="fas fa-car"></i> GoCar Rental</h3>
        <p>Most trusted car rental website in Malaysia</p>
      </div>

      <div class="footer-col">
        <h4>Quick Links</h4>
        <ul>
          <li><a href="<?= $nav_path ?>index.php">Home</a></li>
          <li><a href="<?= $nav_path ?>items/cars.php">Browse Cars</a></li>
          <li><a href="<?= $nav_path ?>contact.php">Contact</a></li>
          <?php if (!isLoggedIn()): ?>
            <li><a href="<?= $nav_path ?>auth/register.php">Register</a></li>
            <li><a href="<?= $nav_path ?>auth/login.php">Login</a></li>
          <?php endif; ?>
        </ul>
      </div>

      <div class="footer-col">
        <h4>Contact</h4>
        <ul class="contact-info">
          <li><i class="fas fa-map-marker-alt"></i> Jalan Bukit Bintang, 55100 Kuala Lumpur</li>
          <li><i class="fas fa-phone"></i> +60 5-266 15774</li>
          <li><i class="fas fa-envelope"></i> info@gocarrental.com</li>
        </ul>
      </div>
    </div>

    <div class="footer-bottom">
      <p>&copy; <?= date('Y') ?> GoCar Rental. All rights reserved.</p>
    </div>
  </footer>

  <script src="<?= $nav_path ?>assets/script.js?v=<?= filemtime(__DIR__ . '/../assets/script.js') ?>"></script>
</body>
</html>