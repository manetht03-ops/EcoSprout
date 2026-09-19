<?php
// profile.php - simple profile page where user can view username/role and change password
require_once __DIR__ . '/../db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: /EcoSprout/index.php?error=' . urlencode('Please login first'));
    exit;
}

$uid = (int)$_SESSION['user_id'];
$username = htmlspecialchars($_SESSION['username']);
$role = htmlspecialchars($_SESSION['role']);

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current = isset($_POST['current_password']) ? $_POST['current_password'] : '';
    $new = isset($_POST['new_password']) ? $_POST['new_password'] : '';
    $confirm = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

    if ($new === '' || $confirm === '') {
        $errors[] = 'Please provide the new password and confirmation.';
    }
    if ($new !== $confirm) {
        $errors[] = 'New password and confirmation do not match.';
    }
    if (strlen($new) < 6) {
        $errors[] = 'New password must be at least 6 characters.';
    }

    if (empty($errors)) {
        // Verify current password
        $stmt = $mysqli->prepare('SELECT password FROM users WHERE id = ? LIMIT 1');
        $stmt->bind_param('i', $uid);
        $stmt->execute();
        $stmt->bind_result($hash);
        $stmt->fetch();
        $stmt->close();

        if (!password_verify($current, $hash)) {
            $errors[] = 'Current password is incorrect.';
        } else {
            $newHash = password_hash($new, PASSWORD_DEFAULT);
            $up = $mysqli->prepare('UPDATE users SET password = ? WHERE id = ?');
            $up->bind_param('si', $newHash, $uid);
            if ($up->execute()) {
                $success = true;
            } else {
                $errors[] = 'Failed to update password: ' . $up->error;
            }
            $up->close();
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>EcoSprout - Profile</title>
    <link rel="stylesheet" href="/EcoSprout/assets/css/style.css">
</head>
<body>
<?php require_once __DIR__ . '/../includes/app_nav.php'; ?>
<div class="container">
    <h1>Profile</h1>
    <p>Username: <strong><?php echo $username; ?></strong></p>
    <p>Role: <strong><?php echo $role; ?></strong></p>

    <?php if ($success): ?>
        <div class="success">Password updated successfully.</div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="error"><?php echo htmlspecialchars(implode('<br>', $errors)); ?></div>
    <?php endif; ?>

    <h2>Change Password</h2>
    <form action="profile.php" method="post">
        <label for="current_password">Current Password</label>
        <input type="password" id="current_password" name="current_password" required>

        <label for="new_password">New Password</label>
        <input type="password" id="new_password" name="new_password" required>

        <label for="confirm_password">Confirm New Password</label>
        <input type="password" id="confirm_password" name="confirm_password" required>

        <button type="submit">Update Password</button>
    </form>

    <div class="action-row"><a class="link-button secondary" href="/EcoSprout/user/success.php">Back to dashboard</a></div>
</div>
</body>
</html>