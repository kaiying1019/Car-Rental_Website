<?php
require_once '../includes/config.php';

if (isLoggedIn()) {
  redirect('../index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = sanitize($_POST['email'] ?? '');
  $password = $_POST['password'] ?? '';

  if (empty($email) || empty($password)) {
    setMessage('Please fill in all fields.', 'error');
  }
  else {
    $conn = getDB();
    $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE email = ?");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
      if (password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        setMessage('Welcome back, ' . $user['username'] . '!', 'success');
        redirect('../index.php');
      }
      else {
        setMessage('Invalid email or password.', 'error');
      }
    }
    else {
      setMessage('Invalid email or password.', 'error');
    }
  }
}

include '../includes/header.php';
?>

<div class="auth-container">
  <h2>Welcome Back</h2>
  <p class="subtitle">Login to your GoCar account</p>
  <?= displayMessage() ?>

  <form method="POST">
    <label for="email">Email Address</label>
    <input type="email" id="email" name="email" placeholder="you@example.com" required>
    <label for="password">Password</label>
    <input type="password" id="password" name="password" placeholder="Enter your password" required>
    <button type="submit" class="btn btn-primary">Login</button>
  </form>

  <p class="auth-link">
    Don't have an account? <a href="register.php">Register here</a>
  </p>
</div>

<?php include '../includes/footer.php'; ?>