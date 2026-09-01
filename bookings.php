<?php
require_once 'includes/config.php';
include 'includes/header.php';

if (!isLoggedIn()) {
    setMessage("Please login to view your bookings.", "error");
    redirect("auth/login.php");
}

$conn = getDB();
$user_id = $_SESSION['user_id'];

$bookingsQuery = "SELECT b.*, c.brand, c.model, c.image 
                  FROM bookings b 
                  JOIN cars c ON b.car_id = c.id 
                  WHERE b.user_id = $user_id 
                  AND b.status = 'confirmed'
                  ORDER BY b.created_at DESC";
$bookingsResult = $conn->query($bookingsQuery);
$bookings = $bookingsResult->fetch_all(MYSQLI_ASSOC);

$totalActive = count($bookings);

$totalQuery = "SELECT COUNT(*) AS total FROM bookings WHERE user_id = $user_id";
$totalBookings = $conn->query($totalQuery)->fetch_assoc()['total'] ?? 0;

$completedQuery = "SELECT COUNT(*) AS completed FROM bookings WHERE user_id = $user_id AND status = 'completed'";
$completedBookings = $conn->query($completedQuery)->fetch_assoc()['completed'] ?? 0;
?>

<div class="container" style="padding: 30px 0;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
        <h1 style="color: #0f2b5c;">My Bookings</h1>
        <a href="dashboard.php" class="btn btn-secondary" style="background: #64748b; color: #fff; padding: 10px 20px; border-radius: 8px; text-decoration: none;">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>

    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 30px;">
        <div style="background: #fff; padding: 20px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.06); text-align: center;">
            <span style="display: block; font-size: 2rem; font-weight: 800; color: #0f2b5c;"><?php echo $totalActive; ?></span>
            <span style="color: #64748b;">Active Bookings</span>
        </div>
        <div style="background: #fff; padding: 20px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.06); text-align: center;">
            <span style="display: block; font-size: 2rem; font-weight: 800; color: #10b981;"><?php echo $completedBookings; ?></span>
            <span style="color: #64748b;">Completed</span>
        </div>
        <div style="background: #fff; padding: 20px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.06); text-align: center;">
            <span style="display: block; font-size: 2rem; font-weight: 800; color: #64748b;"><?php echo $totalBookings; ?></span>
            <span style="color: #64748b;">Total Bookings</span>
        </div>
    </div>

    <?php if (empty($bookings)): ?>
        <div style="text-align: center; padding: 60px 20px; background: #fff; border-radius: 16px; box-shadow: 0 2px 10px rgba(0,0,0,0.06);">
            <i class="fas fa-calendar-alt" style="font-size: 4rem; color: #cbd5e1; margin-bottom: 16px;"></i>
            <h3 style="color: #1e293b;">No Active Bookings</h3>
            <p style="color: #64748b; max-width: 400px; margin: 10px auto;">You have no confirmed bookings. Browse cars and book your next ride!</p>
            <a href="items/cars.php" class="btn btn-primary" style="display: inline-block; padding: 12px 30px; background: #3b82f6; color: #fff; border-radius: 50px; text-decoration: none; margin-top: 16px;">
                Browse Cars
            </a>
        </div>
    <?php else: ?>
        <div style="background: #fff; border-radius: 16px; box-shadow: 0 2px 10px rgba(0,0,0,0.06); overflow: hidden;">
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; min-width: 650px;">
                    <thead>
                        <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                            <th style="padding: 16px 20px; text-align: left; font-weight: 600; color: #0f172a;">Booking</th>
                            <th style="padding: 16px 20px; text-align: left; font-weight: 600; color: #0f172a;">Car</th>
                            <th style="padding: 16px 20px; text-align: left; font-weight: 600; color: #0f172a;">Dates</th>
                            <th style="padding: 16px 20px; text-align: left; font-weight: 600; color: #0f172a;">Total</th>
                            <th style="padding: 16px 20px; text-align: left; font-weight: 600; color: #0f172a;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bookings as $booking): ?>
                            <tr style="border-bottom: 1px solid #e2e8f0;">
                                <td style="padding: 16px 20px;">
                                    <span style="font-weight: 600;">#<?php echo $booking['id']; ?></span>
                                    <br>
                                    <small style="color: #94a3b8;"><?php echo date('d M Y', strtotime($booking['created_at'])); ?></small>
                                </td>
                                <td style="padding: 16px 20px;">
                                    <div style="display: flex; align-items: center; gap: 12px;">
                                        <img src="assets/images/<?php echo $booking['image'] ?: 'default-car.jpg'; ?>" alt="<?php echo $booking['brand'] . ' ' . $booking['model']; ?>" style="width: 60px; height: 45px; object-fit: cover; border-radius: 6px;">
                                        <div>
                                            <strong><?php echo $booking['brand'] . ' ' . $booking['model']; ?></strong>
                                            <br>
                                            <small style="color: #64748b;"><?php echo $booking['brand']; ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td style="padding: 16px 20px;">
                                    <span style="font-size: 0.9rem;">
                                        <i class="fas fa-calendar-check" style="color: #3b82f6; width: 16px;"></i> <?php echo date('d M Y', strtotime($booking['pickup_datetime'])); ?>
                                        <br>
                                        <i class="fas fa-calendar-times" style="color: #ef4444; width: 16px;"></i> <?php echo date('d M Y', strtotime($booking['dropoff_datetime'])); ?>
                                    </span>
                                </td>
                                <td style="padding: 16px 20px;">
                                    <strong style="color: #d97706;">RM <?php echo number_format($booking['total_price'], 2); ?></strong>
                                    <br>
                                    <small style="color: #94a3b8;"><?php echo $booking['total_days']; ?> days</small>
                                </td>
                                <td style="padding: 16px 20px;">
                                    <?php
                                    $statusColors = [
                                        'confirmed' => '#3b82f6',
                                        'completed' => '#10b981',
                                        'cancelled' => '#ef4444'
                                    ];
                                    $color = $statusColors[$booking['status']] ?? '#64748b';
                                    ?>
                                    <span style="display: inline-block; padding: 4px 14px; border-radius: 50px; font-size: 0.8rem; font-weight: 600; background: <?php echo $color . '20'; ?>; color: <?php echo $color; ?>;">
                                        <?php echo ucfirst($booking['status']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>