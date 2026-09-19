<?php
// reset_request.php - request a password reset; prints a reset link (no email sending in this simple demo)
require_once __DIR__ . '/../db.php';

$errors = [];
success:;
$resetLink = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    if ($username === '') {
        $errors[] = 'Please enter your username (email).';
    }

    if (empty($errors)) {
        // Find user
        $stmt = $mysqli->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows === 0) {
            $errors[] = 'No user found with that username.';
            $stmt->close();
        } else {
            $stmt->bind_result($uid);
            $stmt->fetch();
            $stmt->close();

            // Create token and store
            $token = bin2hex(random_bytes(16));
            $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour

            $ins = $mysqli->prepare('INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)');
            if (!$ins) {
                $errors[] = 'DB error: ' . $mysqli->error;
            } else {
                $ins->bind_param('iss', $uid, $token, $expires);
                if ($ins->execute()) {
                    // In production, send email with link; for dev, show link
                    $resetLink = sprintf('%s/reset_password.php?token=%s', rtrim(dirname($_SERVER['REQUEST_URI']), '/'), $token);
                } else {
                    $errors[] = 'Failed to create reset token: ' . $ins->error;
                }
                $ins->close();
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
    <title>EcoSprout - Password Reset</title>
    <link rel="stylesheet" href="/EcoSprout/assets/css/style.css">
</head>
<body>
<?php require_once __DIR__ . '/../includes/app_nav.php'; ?>
<div class="container">
    <h1>Password Reset</h1>
    <?php if (!empty($errors)): ?>
        <div class="error"><?php echo htmlspecialchars(implode('<br>', $errors)); ?></div>
    <?php endif; ?>

    <?php if ($resetLink !== ''): ?>
        <div class="success">A reset link has been generated (for development). Click to reset your password:<br>
            <a href="<?php echo htmlspecialchars($resetLink); ?>"><?php echo htmlspecialchars($resetLink); ?></a>
        </div>
    <?php else: ?>
        <form action="reset_request.php" method="post">
            <label for="username">Username (email)</label>
            <input type="email" id="username" name="username" required>
            <button type="submit">Request Reset</button>
        </form>
    <?php endif; ?>

    <div class="action-row"><a class="link-button secondary" href="/EcoSprout/index.php">Back to login</a></div>
</div>
</body>
</html>