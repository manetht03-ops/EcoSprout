<?php
// register.php - show registration form and handle new user creation
// Simple implementation for development use only. No CSRF token and minimal validation.
require_once __DIR__ . '/../db.php';
session_start();

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $role = isset($_POST['role']) ? $_POST['role'] : 'User';

    if ($username === '' || $password === '') {
        $errors[] = 'Username and password are required.';
    }

    // Basic validation: username looks like an email
    if (!filter_var($username, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please provide a valid email address as username.';
    }

    if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    }

    if (empty($errors)) {
        // Check if user exists
        $check = $mysqli->prepare('SELECT id FROM users WHERE username = ?');
        $check->bind_param('s', $username);
        $check->execute();
        $check->store_result();
        if ($check->num_rows > 0) {
            $errors[] = 'User already exists. Please choose a different username or login.';
            $check->close();
        } else {
            $check->close();
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $mysqli->prepare('INSERT INTO users (username, password, role) VALUES (?, ?, ?)');
            if (!$stmt) {
                $errors[] = 'Database error: ' . $mysqli->error;
            } else {
                $stmt->bind_param('sss', $username, $hashed, $role);
                if ($stmt->execute()) {
                    $success = true;
                } else {
                    $errors[] = 'Failed to create user: ' . $stmt->error;
                }
                $stmt->close();
            }
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>EcoSprout - Register</title>
    <link rel="stylesheet" href="/EcoSprout/assets/css/style.css">
</head>
<body>
<?php require_once __DIR__ . '/../includes/app_nav.php'; ?>
<div class="container">
    <h1>Register</h1>

    <?php if ($success): ?>
        <div class="success">Registration successful. You can now <a href="/EcoSprout/index.php">login</a>.</div>
    <?php else: ?>
        <?php if (!empty($errors)): ?>
            <div class="error"><?php echo htmlspecialchars(implode('<br>', $errors)); ?></div>
        <?php endif; ?>

        <form action="register.php" method="post">
            <label for="username">Username (email)</label>
            <input type="email" id="username" name="username" required>

            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>

            <label for="role">Role</label>
            <select id="role" name="role">
                <option value="User">User</option>
                <option value="Manager">Manager</option>
            </select>

            <button type="submit">Register</button>
        </form>
    <?php endif; ?>

    <div class="action-row"><a class="link-button secondary" href="/EcoSprout/index.php">Back to login</a></div>
</div>
</body>
</html>