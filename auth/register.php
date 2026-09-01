<?php
require_once '../includes/config.php';

if (isLoggedIn()) {
  redirect('../index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = sanitize($_POST['username'] ?? '');
  $email = sanitize($_POST['email'] ?? '');
  $password = $_POST['password'] ?? '';
  $confirm = $_POST['confirm_password'] ?? '';

  if (empty($username) || empty($email) || empty($password) || empty($confirm)) {
    setMessage('Please fill in all fields.', 'error');
  } 
  elseif (strlen($password) < 6) {
    setMessage('Password must be at least 6 characters.', 'error');
  } 
  elseif ($password !== $confirm) {
    setMessage('Passwords do not match.', 'error');
  } 
  else {
    $conn = getDB();
    $check = $conn->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
    $check->bind_param('ss', $email, $username);
    $check->execute();

    if ($check->get_result()->num_rows > 0) {
      setMessage('Email or username already exists.', 'error');
    }
    else {
      $hashed = password_hash($password, PASSWORD_DEFAULT);
      $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
      $stmt->bind_param('sss', $username, $email, $hashed);

      if ($stmt->execute()) {
        setMessage('Registration successful! Please login.', 'success');
        redirect('login.php');
      }
      else {
        setMessage('Something went wrong. Please try again.', 'error');
      }
    }
  }
}

include '../includes/header.php';
?>

<div class="auth-container">
  <h2>Create Account</h2>
  <p class="subtitle">Join GoCar and start renting today</p>
  <?= displayMessage() ?>

  <form method="POST">
    <label for="username">Username</label>
    <input type="text" id="username" name="username" placeholder="johndoe" required>
    <label for="email">Email Address</label>
    <input type="email" id="email" name="email" placeholder="you@example.com" required>
    <label for="password">Password</label>
    <input type="password" id="password" name="password" placeholder="Min 6 characters" required>
    <label for="confirm_password">Confirm Password</label>
    <input type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter password" required>
    <button type="submit" class="btn btn-primary">Register</button>
  </form>

  <p class="auth-link">
    Already have an account? <a href="login.php">Login here</a>
  </p>
</div>

<?php include '../includes/footer.php'; ?>