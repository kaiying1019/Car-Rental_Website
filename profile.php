<?php
session_start();
require_once 'includes/config.php';

if (!isLoggedIn()) {
    setMessage('Please login to edit your profile.', 'error');
    redirect('auth/login.php');
}

$user_id = $_SESSION['user_id'];
$conn = getDB();
$errors = [];

$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($username) || empty($email)) {
        $errors[] = 'Username and email are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format.';
    } else {
        $check = $conn->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
        $check->bind_param('ssi', $username, $email, $user_id);
        $check->execute();
        
        if ($check->get_result()->num_rows > 0) {
            $errors[] = 'Username or email already taken.';
        } else {
            if (!empty($new_password)) {
                if ($new_password !== $confirm_password) {
                    $errors[] = 'Passwords do not match.';
                } elseif (strlen($new_password) < 6) {
                    $errors[] = 'Password must be at least 6 characters.';
                } else {
                    $hashed = password_hash($new_password, PASSWORD_DEFAULT);
                    $update = $conn->prepare("UPDATE users SET username = ?, email = ?, password = ? WHERE id = ?");
                    $update->bind_param('sssi', $username, $email, $hashed, $user_id);
                }
            } else {
                $update = $conn->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
                $update->bind_param('ssi', $username, $email, $user_id);
            }

            if (empty($errors) && $update->execute()) {
                $_SESSION['username'] = $username;
                setMessage('Profile updated successfully.', 'success');
                redirect('profile.php');
            } elseif (empty($errors)) {
                $errors[] = 'Something went wrong. Please try again.';
            }
        }
    }
}

include 'includes/header.php';
?>

<div class="container" style="padding: 30px 20px; max-width: 700px;">
  <div class="page-header-row">
    <h2>My Profile</h2>
    <a href="dashboard.php" class="btn btn-secondary btn-inline"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
  </div>

  <?= displayMessage() ?>

  <?php if (!empty($errors)): ?>
    <div class="alert alert-error"><?= implode('<br>', $errors) ?></div>
  <?php endif; ?>

  <div class="form-card">
    <form method="POST">
      <div class="form-row">
        <div>
          <label for="username">Username</label>
          <input type="text" id="username" name="username" value="<?= htmlspecialchars($user['username']) ?>" required>
        </div>
        <div>
          <label for="email">Email</label>
          <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
        </div>
      </div>

      <hr style="margin: 20px 0; border-color: #e2e8f0;">

      <h4 style="color: #0f172a; margin-bottom: 12px;">Change Password (optional)</h4>
      
      <div class="form-row">
        <div>
          <label for="new_password">New Password</label>
          <input type="password" id="new_password" name="new_password" placeholder="Leave blank to keep current">
        </div>
        <div>
          <label for="confirm_password">Confirm New Password</label>
          <input type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter new password">
        </div>
      </div>

      <div style="display: flex; gap: 12px; margin-top: 20px;">
        <button type="submit" class="btn btn-primary">Update Profile</button>
        <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
      </div>
    </form>

    <hr style="margin: 24px 0; border-color: #e2e8f0;">

    <div style="display: flex; justify-content: space-between; align-items: center;">
      <div>
        <p style="color: #64748b; font-size: 0.9rem;">
          <strong>Member since:</strong> <?= date('F d, Y', strtotime($user['created_at'])) ?>
        </p>
        <p style="color: #64748b; font-size: 0.9rem;">
          <strong>Account type:</strong> <?= ucfirst($user['role']) ?>
        </p>
      </div>
      <a href="auth/logout.php" class="btn btn-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
  </div>
</div>

<?php include 'includes/footer.php'; ?>