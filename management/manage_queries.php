<?php
// manage_queries.php - staff/admin screen to review and respond to customer queries
require_once __DIR__ . '/../db.php';
session_start();

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['Admin', 'Manager'], true)) {
    header('Location: /EcoSprout/index.php?error=' . urlencode('Staff access required'));
    exit;
}

$errors = [];
$success = false;
$allowedStatus = ['Open', 'In Progress', 'Resolved', 'Closed'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $queryId = (int)($_POST['query_id'] ?? 0);
    $status = trim($_POST['status'] ?? 'Open');
    $response = trim($_POST['response'] ?? '');

    if ($queryId <= 0) {
        $errors[] = 'Invalid query id.';
    } elseif (!in_array($status, $allowedStatus, true)) {
        $errors[] = 'Invalid status selection.';
    } else {
        if ($response === '') {
            $stmt = $mysqli->prepare('UPDATE nursery_queries SET status = ? WHERE id = ?');
            if (!$stmt) {
                $errors[] = 'Database error: ' . $mysqli->error;
            } else {
                $stmt->bind_param('si', $status, $queryId);
                if ($stmt->execute()) {
                    $success = true;
                } else {
                    $errors[] = 'Failed to update query: ' . $stmt->error;
                }
                $stmt->close();
            }
        } else {
            $responderId = (int)$_SESSION['user_id'];
            $stmt = $mysqli->prepare('UPDATE nursery_queries SET status = ?, admin_response = ?, responded_by = ?, responded_at = NOW() WHERE id = ?');
            if (!$stmt) {
                $errors[] = 'Database error: ' . $mysqli->error;
            } else {
                $stmt->bind_param('ssii', $status, $response, $responderId, $queryId);
                if ($stmt->execute()) {
                    $success = true;
                } else {
                    $errors[] = 'Failed to update query: ' . $stmt->error;
                }
                $stmt->close();
            }
        }
    }
}

$queries = [];
$sql = 'SELECT q.id, q.subject, q.message, q.status, q.admin_response, q.created_at, u.username AS customer_name, r.username AS responder_name FROM nursery_queries q INNER JOIN users u ON q.user_id = u.id LEFT JOIN users r ON q.responded_by = r.id ORDER BY q.created_at DESC';
$result = $mysqli->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $queries[] = $row;
    }
    $result->close();
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>EcoSprout - Customer Queries</title>
    <link rel="stylesheet" href="/EcoSprout/assets/css/style.css">
</head>
<body>
<?php require_once __DIR__ . '/../includes/app_nav.php'; ?>
<div class="container" style="max-width:900px;">
    <h1>Customer Queries</h1>

    <?php if ($success): ?>
        <div class="success">Query updated successfully.</div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="error"><?php echo htmlspecialchars(implode('<br>', $errors)); ?></div>
    <?php endif; ?>

    <?php if (empty($queries)): ?>
        <p>No customer queries have been submitted yet.</p>
    <?php else: ?>
        <?php foreach ($queries as $queryRow): ?>
            <div class="notes" style="margin-top:16px; padding:14px; border:1px solid #d7e1d2; border-radius:6px;">
                <p><strong>Customer:</strong> <?php echo htmlspecialchars($queryRow['customer_name']); ?></p>
                <p><strong>Subject:</strong> <?php echo htmlspecialchars($queryRow['subject']); ?></p>
                <p><strong>Status:</strong> <?php echo htmlspecialchars($queryRow['status']); ?></p>
                <p><strong>Message:</strong><br><?php echo nl2br(htmlspecialchars($queryRow['message'])); ?></p>
                <?php if (!empty($queryRow['admin_response'])): ?>
                    <p><strong>Response:</strong><br><?php echo nl2br(htmlspecialchars($queryRow['admin_response'])); ?></p>
                    <p><strong>Responded by:</strong> <?php echo htmlspecialchars($queryRow['responder_name'] ?? 'N/A'); ?></p>
                <?php endif; ?>

                <form action="manage_queries.php" method="post">
                    <input type="hidden" name="query_id" value="<?php echo (int)$queryRow['id']; ?>">
                    <label for="status_<?php echo (int)$queryRow['id']; ?>">Update status</label>
                    <select id="status_<?php echo (int)$queryRow['id']; ?>" name="status">
                        <?php foreach ($allowedStatus as $statusOption): ?>
                            <option value="<?php echo htmlspecialchars($statusOption); ?>" <?php echo $queryRow['status'] === $statusOption ? 'selected' : ''; ?>><?php echo htmlspecialchars($statusOption); ?></option>
                        <?php endforeach; ?>
                    </select>

                    <label for="response_<?php echo (int)$queryRow['id']; ?>">Response</label>
                    <textarea id="response_<?php echo (int)$queryRow['id']; ?>" name="response" rows="4" style="width:100%;padding:8px;margin-top:6px;border:1px solid #cbd5c2;border-radius:4px;resize:vertical"><?php echo htmlspecialchars($queryRow['admin_response'] ?? ''); ?></textarea>

                    <button type="submit">Save update</button>
                </form>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <div class="action-row"><a class="link-button secondary" href="/EcoSprout/user/success.php">Back to dashboard</a></div>
</div>
</body>
</html>