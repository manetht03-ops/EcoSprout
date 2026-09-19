<?php
// workshops.php - browse workshops and register for a session
require_once __DIR__ . '/../db.php';
session_start();

$loggedIn = isset($_SESSION['user_id']);
$message = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$loggedIn) {
        $errors[] = 'Please login to register for a workshop.';
    } else {
        $workshopId = (int)($_POST['workshop_id'] ?? 0);
        $participantName = trim($_POST['participant_name'] ?? ($_SESSION['username'] ?? ''));
        $participantEmail = trim($_POST['participant_email'] ?? ($_SESSION['username'] ?? ''));
        $notes = trim($_POST['notes'] ?? '');

        if ($workshopId <= 0) {
            $errors[] = 'Invalid workshop selection.';
        } elseif ($participantName === '' || $participantEmail === '') {
            $errors[] = 'Participant name and email are required.';
        } else {
            $workshopStmt = $mysqli->prepare('SELECT id, title, capacity, status FROM workshops WHERE id = ? LIMIT 1');
            $workshopStmt->bind_param('i', $workshopId);
            $workshopStmt->execute();
            $workshopResult = $workshopStmt->get_result();
            $workshop = $workshopResult->fetch_assoc();
            $workshopResult->close();
            $workshopStmt->close();

            if (!$workshop) {
                $errors[] = 'Workshop not found.';
            } elseif ($workshop['status'] !== 'Scheduled') {
                $errors[] = 'This workshop is not currently open for registration.';
            } else {
                $countStmt = $mysqli->prepare('SELECT COUNT(*) AS total FROM workshop_registrations WHERE workshop_id = ? AND status IN ("Registered", "Confirmed")');
                $countStmt->bind_param('i', $workshopId);
                $countStmt->execute();
                $countResult = $countStmt->get_result();
                $countRow = $countResult->fetch_assoc();
                $countResult->close();
                $countStmt->close();

                if ((int)$countRow['total'] >= (int)$workshop['capacity']) {
                    $errors[] = 'This workshop is already full.';
                } else {
                    $userId = (int)$_SESSION['user_id'];
                    $insert = $mysqli->prepare('INSERT INTO workshop_registrations (workshop_id, user_id, participant_name, participant_email, notes, status) VALUES (?, ?, ?, ?, ?, "Registered")');
                    if (!$insert) {
                        $errors[] = 'Database error: ' . $mysqli->error;
                    } else {
                        $insert->bind_param('iisss', $workshopId, $userId, $participantName, $participantEmail, $notes);
                        if ($insert->execute()) {
                            $message = 'Workshop registration completed successfully.';
                        } else {
                            $errors[] = 'Failed to register: ' . $insert->error;
                        }
                        $insert->close();
                    }
                }
            }
        }
    }
}

$workshops = [];
$result = $mysqli->query('SELECT w.id, w.title, w.description, w.workshop_date, w.start_time, w.location, w.capacity, w.fee, w.status, COUNT(r.id) AS registered_count FROM workshops w LEFT JOIN workshop_registrations r ON w.id = r.workshop_id AND r.status IN ("Registered", "Confirmed") GROUP BY w.id ORDER BY w.workshop_date ASC, w.start_time ASC');
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $workshops[] = $row;
    }
    $result->close();
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>EcoSprout - Workshops and Events</title>
    <link rel="stylesheet" href="/EcoSprout/assets/css/style.css">
</head>
<body>
<?php require_once __DIR__ . '/../includes/app_nav.php'; ?>
<div class="container" style="max-width:900px;">
    <h1>Workshops and Events</h1>

    <?php if ($message !== ''): ?>
        <div class="success"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    <?php if (!empty($errors)): ?>
        <div class="error"><?php echo htmlspecialchars(implode('<br>', $errors)); ?></div>
    <?php endif; ?>

    <?php if (empty($workshops)): ?>
        <p>No workshops are available right now.</p>
    <?php else: ?>
        <div class="paginated-list" data-page-size="3">
        <?php foreach ($workshops as $workshop): ?>
            <div class="notes paginated-item" style="margin-top:16px; padding:14px; border:1px solid #d7e1d2; border-radius:6px;">
                <h2 style="margin-top:0;"><?php echo htmlspecialchars($workshop['title']); ?></h2>
                <p><?php echo htmlspecialchars($workshop['description']); ?></p>
                <p><strong>Date:</strong> <?php echo htmlspecialchars($workshop['workshop_date']); ?> | <strong>Time:</strong> <?php echo htmlspecialchars(substr($workshop['start_time'], 0, 5)); ?></p>
                <p><strong>Location:</strong> <?php echo htmlspecialchars($workshop['location']); ?></p>
                <p><strong>Fee:</strong> $<?php echo number_format((float)$workshop['fee'], 2); ?> | <strong>Capacity:</strong> <?php echo (int)$workshop['registered_count']; ?>/<?php echo (int)$workshop['capacity']; ?></p>

                <?php if ($loggedIn && $workshop['status'] === 'Scheduled'): ?>
                    <form action="workshops.php" method="post">
                        <input type="hidden" name="workshop_id" value="<?php echo (int)$workshop['id']; ?>">

                        <label for="participant_name_<?php echo (int)$workshop['id']; ?>">Participant Name</label>
                        <input type="text" id="participant_name_<?php echo (int)$workshop['id']; ?>" name="participant_name" value="<?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?>" required>

                        <label for="participant_email_<?php echo (int)$workshop['id']; ?>">Participant Email</label>
                        <input type="email" id="participant_email_<?php echo (int)$workshop['id']; ?>" name="participant_email" value="<?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?>" required>

                        <label for="notes_<?php echo (int)$workshop['id']; ?>">Notes</label>
                        <textarea id="notes_<?php echo (int)$workshop['id']; ?>" name="notes" rows="3" style="width:100%;padding:8px;margin-top:6px;border:1px solid #cbd5c2;border-radius:4px;resize:vertical"></textarea>

                        <button type="submit">Register</button>
                    </form>
                <?php elseif (!$loggedIn): ?>
                    <div class="action-row"><a class="link-button secondary" href="/EcoSprout/index.php">Login to register</a></div>
                <?php else: ?>
                    <p>This event is not currently open for registration.</p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="action-row"><a class="link-button secondary" href="/EcoSprout/user/success.php">Back to dashboard</a></div>
</div>
<script src="/EcoSprout/assets/js/main.js"></script>
</body>
</html>