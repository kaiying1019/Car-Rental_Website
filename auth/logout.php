<?php
require_once '../includes/config.php';

session_destroy();
session_start();
setMessage('Logged out successfully.', 'success');
redirect('login.php');
?>