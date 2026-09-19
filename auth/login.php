<?php
// login.php - handle POST from the login form (index.php)
session_start();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /EcoSprout/index.php');
    exit;
}

require_once __DIR__ . '/../db.php'; // provides $mysqli

$username = isset($_POST['username']) ? trim($_POST['username']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';

if ($username === '' || $password === '') {
    header('Location: /EcoSprout/index.php?error=' . urlencode('Please provide username and password'));
    exit;
}

// Fetch user
$stmt = $mysqli->prepare('SELECT id, password, role FROM users WHERE username = ? LIMIT 1');
$id = 0;
$hash = '';
$role = 'User';
$stmt->bind_param('s', $username);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows === 0) {
    // user not found
    header('Location: /EcoSprout/index.php?error=' . urlencode('Invalid credentials'));
    exit;
}
$stmt->bind_result($id, $hash, $role);
$stmt->fetch();

if ($hash === '' || !password_verify($password, $hash)) {
    header('Location: /EcoSprout/index.php?error=' . urlencode('Invalid credentials'));
    exit;
}

// Success: set session and redirect to success page
$_SESSION['user_id'] = $id;
$_SESSION['username'] = $username;
$_SESSION['role'] = $role;

header('Location: /EcoSprout/user/success.php');
exit;
?>