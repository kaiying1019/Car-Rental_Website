<?php
session_start();
require_once 'includes/config.php';

if (!isLoggedIn()) {
    setMessage('Please login to access dashboard.', 'error');
    redirect('auth/login.php');
}

$user_id = $_SESSION['user_id'];
$conn = getDB();

$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM bookings WHERE user_id = ?");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$total_bookings = $stmt->get_result()->fetch_assoc()['total'];

$stmt = $conn->prepare("SELECT COUNT(*) as active FROM bookings WHERE user_id = ? AND status IN ('pending', 'confirmed')");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$active_bookings = $stmt->get_result()->fetch_assoc()['active'];

include 'includes/header.php';
?>

<div class="container" style="padding: 30px 20px;">
  <?= displayMessage() ?>
  
  <div class="page-header-row">
    <h2>Dashboard</h2>
    <a href="profile.php" class="btn btn-secondary btn-inline"><i class="fas fa-user-edit"></i> Edit Profile</a>
  </div>

  <div class="dashboard-grid">
    <div class="dashboard-card">
      <div class="dashboard-card-header" style="background:#0f2b5c;color:#fff;">
        <i class="fas fa-user-circle"></i> Welcome, <?= htmlspecialchars($user['username']) ?>
      </div>
      <div class="dashboard-card-body">
        <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
        <p><strong>Role:</strong> <?= ucfirst($user['role']) ?></p>
        <p><strong>Member since:</strong> <?= date('F d, Y', strtotime($user['created_at'])) ?></p>
      </div>
    </div>

    <div class="dashboard-card">
      <div class="dashboard-card-header" style="background:#10b981;color:#fff;">
        <i class="fas fa-calendar-check"></i> My Bookings
      </div>
      <div class="dashboard-card-body text-center" style="padding:30px 20px;">
        <div class="stats-number"><?= $total_bookings ?></div>
        <p style="color:#64748b;">Total Bookings</p>
        <hr>
        <p><strong><?= $active_bookings ?></strong> Active Booking(s)</p>
        <a href="bookings.php" class="btn btn-primary" style="margin-top:14px;">View All Bookings</a>
      </div>
    </div>

    <div class="dashboard-card">
      <div class="dashboard-card-header" style="background:#3b82f6;color:#fff;">
        <i class="fas fa-bolt"></i> Quick Actions
      </div>
      <div class="dashboard-card-body">
        <a href="items/cars.php" class="btn btn-primary btn-block" style="margin-bottom:10px;"><i class="fas fa-car"></i> Browse Cars</a>
        <a href="carts/cart.php" class="btn btn-success btn-block" style="margin-bottom:10px;"><i class="fas fa-shopping-cart"></i> My Cart</a>
        <a href="auth/logout.php" class="btn btn-danger btn-block"><i class="fas fa-sign-out-alt"></i> Logout</a>
      </div>
    </div>
  </div>
</div>

<?php include 'includes/footer.php'; ?>