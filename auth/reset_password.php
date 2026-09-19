<?php
// reset_password.php - handle reset token and allow user to set a new password
require_once __DIR__ . '/../db.php';

$token = isset($_GET['token']) ? $_GET['token'] : (isset($_POST['token']) ? $_POST['token'] : '');
$errors = [];
$success = false;
$pr_id = 0;
$user_id = 0;
$expires_at = '';
$username = '';

if ($token === '') {
    $errors[] = 'Invalid or missing token.';
}

// Verify token
if (empty($errors)) {
    $stmt = $mysqli->prepare('SELECT pr.id, pr.user_id, pr.expires_at, u.username FROM password_resets pr JOIN users u ON pr.user_id = u.id WHERE pr.token = ? LIMIT 1');
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows === 0) {
        $errors[] = 'Invalid token.';
        $stmt->close();
    } else {
        $stmt->bind_result($pr_id, $user_id, $expires_at, $username);
        $stmt->fetch();
        $stmt->close();
        if ($expires_at === '' || strtotime($expires_at) < time()) {
            $errors[] = 'Token has expired.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($errors)) {
    $new = isset($_POST['new_password']) ? $_POST['new_password'] : '';
    $confirm = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

    if ($new === '' || $confirm === '') {
        $errors[] = 'Please enter and confirm your new password.';
    }
    if ($new !== $confirm) {
        $errors[] = 'Password and confirmation do not match.';
    }
    if (strlen($new) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    }

    if (empty($errors)) {
        $newHash = password_hash($new, PASSWORD_DEFAULT);
        $up = $mysqli->prepare('UPDATE users SET password = ? WHERE id = ?');
        $up->bind_param('si', $newHash, $user_id);
        if ($up->execute()) {
            // delete the token
            $del = $mysqli->prepare('DELETE FROM password_resets WHERE id = ?');
            $del->bind_param('i', $pr_id);
            $del->execute();
            $del->close();
            $success = true;
        } else {
            $errors[] = 'Failed to update password: ' . $up->error;
        }
        $up->close();
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>EcoSprout - Reset Password</title>
    <link rel="stylesheet" href="/EcoSprout/assets/css/style.css">
</head>
<body>
<?php require_once __DIR__ . '/../includes/app_nav.php'; ?>
<div class="container">
    <h1>Reset Password</h1>

    <?php if (!empty($errors)): ?>
        <div class="error"><?php echo htmlspecialchars(implode('<br>', $errors)); ?></div>
    <?php endif; ?>

    <?php if (isset($success) && $success): ?>
        <div class="success">Password updated. You can now <a href="/EcoSprout/index.php">login</a>.</div>
    <?php elseif (empty($errors)): ?>
        <p>Resetting password for: <strong><?php echo htmlspecialchars((string)$username); ?></strong></p>
        <form action="reset_password.php" method="post">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
            <label for="new_password">New Password</label>
            <input type="password" id="new_password" name="new_password" required>

            <label for="confirm_password">Confirm Password</label>
            <input type="password" id="confirm_password" name="confirm_password" required>

            <button type="submit">Set New Password</button>
        </form>
    <?php endif; ?>

    <div class="action-row"><a class="link-button secondary" href="/EcoSprout/index.php">Back to login</a></div>
</div>
</body>
</html>