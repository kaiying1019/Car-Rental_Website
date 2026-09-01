<?php
define('DB_HOST', 'localhost');
define('DB_PORT', 3306);
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'carrentalwebsite');

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

function getDB() {
  static $conn = null;
  
  if ($conn === null) {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
      
    if ($conn->connect_error) {
      die("DB connection failed: " . $conn->connect_error);
    }
  }
  
  return $conn;
}

function isLoggedIn() {
  return isset($_SESSION['user_id']);
}

function isAdmin() {
  return isLoggedIn() && ($_SESSION['role'] ?? '') === 'admin';
}

function ensureCartLoaded() {
  if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
  }

  if (!isLoggedIn()) {
    return $_SESSION['cart'];
  }

  $userId = (int)($_SESSION['user_id'] ?? 0);
  if ($userId <= 0) {
    return $_SESSION['cart'];
  }

  if (isset($_SESSION['cart_loaded_user']) && $_SESSION['cart_loaded_user'] === $userId) {
    return $_SESSION['cart'];
  }

  $conn = getDB();
  $stmt = $conn->prepare("SELECT ci.car_id, ci.days, c.brand, c.model, c.year, c.price_per_day, c.image, c.transmission, c.fuel_type, c.seats, c.status FROM cart_items ci JOIN cars c ON c.id = ci.car_id WHERE ci.user_id = ?");
  $stmt->bind_param('i', $userId);
  $stmt->execute();
  $result = $stmt->get_result();

  $cart = [];
  while ($row = $result->fetch_assoc()) {
    $cart[(int)$row['car_id']] = [
      'car_id'        => (int)$row['car_id'],
      'brand'         => $row['brand'],
      'model'         => $row['model'],
      'year'          => (int)$row['year'],
      'price_per_day' => (float)$row['price_per_day'],
      'image'         => $row['image'],
      'transmission'  => $row['transmission'],
      'fuel_type'     => $row['fuel_type'],
      'seats'         => (int)$row['seats'],
      'status'        => $row['status'],
      'days'          => (int)$row['days']
    ];
  }

  $_SESSION['cart'] = $cart;
  $_SESSION['cart_loaded_user'] = $userId;
  return $_SESSION['cart'];
}

function saveCartToDatabase() {
  if (!isLoggedIn()) {
    return;
  }

  $userId = (int)($_SESSION['user_id'] ?? 0);
  if ($userId <= 0) {
    return;
  }

  $conn = getDB();
  $conn->query("DELETE FROM cart_items WHERE user_id = " . $userId);

  if (empty($_SESSION['cart'])) {
    return;
  }

  $stmt = $conn->prepare("INSERT INTO cart_items (user_id, car_id, days) VALUES (?, ?, ?)");

  foreach ($_SESSION['cart'] as $item) {
    $carId = (int)($item['car_id'] ?? 0);
    $days = max(1, (int)($item['days'] ?? 1));

    if ($carId > 0) {
      $stmt->bind_param('iii', $userId, $carId, $days);
      $stmt->execute();
    }
  }
}

function redirect($url) {
  header("Location: $url");
  exit;
}

function sanitize($s) {
  return htmlspecialchars(strip_tags(trim($s)));
}

function setMessage($text, $type = 'success') {
  $_SESSION['message'] = ['text' => $text, 'type' => $type];
}

function displayMessage() {
  if (isset($_SESSION['message'])) {
    $m = $_SESSION['message'];
    unset($_SESSION['message']);
    return "<div class='alert alert-{$m['type']}'>{$m['text']}</div>";
  }
  return '';
}

function uploadCarImage($file) {
    $targetDir = "uploads/";
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    $fileName = time() . '_' . basename($file['name']);
    $targetFile = $targetDir . $fileName;
    $fileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
    $allowedTypes = ['jpg', 'jpeg', 'png', 'webp'];
    if (!in_array($fileType, $allowedTypes)) {
        return ['success' => false, 'error' => 'Only JPG, JPEG, PNG, & WEBP files are allowed.'];
    }
    if ($file['size'] > 5000000) {
        return ['success' => false, 'error' => 'File is too large (max 5MB).'];
    }
    if (move_uploaded_file($file['tmp_name'], $targetFile)) {
        return ['success' => true, 'filename' => $targetFile];
    } else {
        return ['success' => false, 'error' => 'Failed to upload image.'];
    }
}

$script_dir = realpath(dirname($_SERVER['SCRIPT_FILENAME'] ?? __DIR__));
$project_root = realpath(dirname(__DIR__));

$nav_path = '';
if ($script_dir && $project_root && $script_dir !== $project_root) {
  $relative = str_replace('\\', '/', substr($script_dir, strlen($project_root)));
  $depth = substr_count(trim($relative, '/'), '/');
  $nav_path = str_repeat('../', $depth + 1);
}

ensureCartLoaded();
?>