<?php
// submit_query.php - logged-in customers can send a question to nursery management
require_once __DIR__ . '/../db.php';
session_start();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'User') {
    header('Location: /EcoSprout/index.php?error=' . urlencode('Only customers can submit queries'));
    exit;
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($subject === '' || strlen($subject) < 3) {
        $errors[] = 'Please enter a subject of at least 3 characters.';
    }
    if ($message === '' || strlen($message) < 10) {
        $errors[] = 'Please enter a message of at least 10 characters.';
    }

    if (empty($errors)) {
        $stmt = $mysqli->prepare('INSERT INTO nursery_queries (user_id, subject, message, status) VALUES (?, ?, ?, "Open")');
        if (!$stmt) {
            $errors[] = 'Database error: ' . $mysqli->error;
        } else {
            $userId = (int)$_SESSION['user_id'];
            $stmt->bind_param('iss', $userId, $subject, $message);
            if ($stmt->execute()) {
                $success = true;
            } else {
                $errors[] = 'Failed to submit query: ' . $stmt->error;
            }
            $stmt->close();
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>EcoSprout - Submit Query</title>
    <link rel="stylesheet" href="/EcoSprout/assets/css/style.css">
</head>
<body>
<?php require_once __DIR__ . '/../includes/app_nav.php'; ?>
<div class="container">
    <h1>Submit a Query</h1>

    <?php if ($success): ?>
        <div class="success">Your query has been submitted. Nursery staff will review it shortly.</div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="error"><?php echo htmlspecialchars(implode('<br>', $errors)); ?></div>
    <?php endif; ?>

    <?php if (!$success): ?>
        <form action="submit_query.php" method="post">
            <label for="subject">Subject</label>
            <input type="text" id="subject" name="subject" required>

            <label for="message">Message</label>
            <textarea id="message" name="message" rows="6" style="width:100%;padding:8px;margin-top:6px;border:1px solid #cbd5c2;border-radius:4px;resize:vertical" required></textarea>

            <button type="submit">Send Query</button>
        </form>
    <?php endif; ?>

    <div class="action-row"><a class="link-button secondary" href="/EcoSprout/user/success.php">Back to dashboard</a></div>
</div>
</body>
</html>