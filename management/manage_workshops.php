<?php
// manage_workshops.php - manager/admin screen to update workshop schedules and participant registrations
require_once __DIR__ . '/../db.php';
session_start();

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['Admin', 'Manager'], true)) {
    header('Location: /EcoSprout/index.php?error=' . urlencode('Staff access required'));
    exit;
}

$errors = [];
$success = false;
$allowedStatuses = ['Scheduled', 'Postponed', 'Cancelled', 'Completed'];
$allowedRegistrationStatuses = ['Registered', 'Confirmed', 'Cancelled', 'Attended'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create_workshop') {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $workshopDate = trim($_POST['workshop_date'] ?? '');
        $startTime = trim($_POST['start_time'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $capacity = (int)($_POST['capacity'] ?? 0);
        $fee = (float)($_POST['fee'] ?? 0);
        $status = trim($_POST['status'] ?? 'Scheduled');

        if ($title === '' || $description === '' || $workshopDate === '' || $startTime === '' || $location === '') {
            $errors[] = 'All workshop fields are required.';
        } elseif ($capacity < 1) {
            $errors[] = 'Capacity must be at least 1.';
        } elseif (!in_array($status, $allowedStatuses, true)) {
            $errors[] = 'Invalid workshop status.';
        } else {
            $stmt = $mysqli->prepare('INSERT INTO workshops (title, description, workshop_date, start_time, location, capacity, fee, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
            if (!$stmt) {
                $errors[] = 'Database error: ' . $mysqli->error;
            } else {
                $stmt->bind_param('sssssids', $title, $description, $workshopDate, $startTime, $location, $capacity, $fee, $status);
                if ($stmt->execute()) {
                    $success = true;
                } else {
                    $errors[] = 'Failed to create workshop: ' . $stmt->error;
                }
                $stmt->close();
            }
        }
    }

    if ($action === 'update_workshop') {
        $workshopId = (int)($_POST['workshop_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $workshopDate = trim($_POST['workshop_date'] ?? '');
        $startTime = trim($_POST['start_time'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $capacity = (int)($_POST['capacity'] ?? 0);
        $fee = (float)($_POST['fee'] ?? 0);
        $status = trim($_POST['status'] ?? 'Scheduled');

        if ($workshopId <= 0) {
            $errors[] = 'Invalid workshop id.';
        } elseif ($title === '' || $description === '' || $workshopDate === '' || $startTime === '' || $location === '') {
            $errors[] = 'All workshop fields are required.';
        } elseif ($capacity < 1) {
            $errors[] = 'Capacity must be at least 1.';
        } elseif (!in_array($status, $allowedStatuses, true)) {
            $errors[] = 'Invalid workshop status.';
        } else {
            $stmt = $mysqli->prepare('UPDATE workshops SET title = ?, description = ?, workshop_date = ?, start_time = ?, location = ?, capacity = ?, fee = ?, status = ? WHERE id = ?');
            if (!$stmt) {
                $errors[] = 'Database error: ' . $mysqli->error;
            } else {
                $stmt->bind_param('sssssidii', $title, $description, $workshopDate, $startTime, $location, $capacity, $fee, $status, $workshopId);
                if ($stmt->execute()) {
                    $success = true;
                } else {
                    $errors[] = 'Failed to update workshop: ' . $stmt->error;
                }
                $stmt->close();
            }
        }
    }

    if ($action === 'update_registration') {
        $registrationId = (int)($_POST['registration_id'] ?? 0);
        $registrationStatus = trim($_POST['registration_status'] ?? 'Registered');

        if ($registrationId <= 0) {
            $errors[] = 'Invalid registration id.';
        } elseif (!in_array($registrationStatus, $allowedRegistrationStatuses, true)) {
            $errors[] = 'Invalid registration status.';
        } else {
            $stmt = $mysqli->prepare('UPDATE workshop_registrations SET status = ? WHERE id = ?');
            if (!$stmt) {
                $errors[] = 'Database error: ' . $mysqli->error;
            } else {
                $stmt->bind_param('si', $registrationStatus, $registrationId);
                if ($stmt->execute()) {
                    $success = true;
                } else {
                    $errors[] = 'Failed to update registration: ' . $stmt->error;
                }
                $stmt->close();
            }
        }
    }
}

$workshops = [];
$workshopResult = $mysqli->query('SELECT id, title, description, workshop_date, start_time, location, capacity, fee, status FROM workshops ORDER BY workshop_date ASC, start_time ASC');
if ($workshopResult) {
    while ($row = $workshopResult->fetch_assoc()) {
        $workshops[] = $row;
    }
    $workshopResult->close();
}

$registrations = [];
$registrationResult = $mysqli->query('SELECT r.id, r.participant_name, r.participant_email, r.notes, r.status, r.created_at, w.title AS workshop_title, w.workshop_date FROM workshop_registrations r INNER JOIN workshops w ON r.workshop_id = w.id ORDER BY r.created_at DESC');
if ($registrationResult) {
    while ($row = $registrationResult->fetch_assoc()) {
        $registrations[] = $row;
    }
    $registrationResult->close();
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>EcoSprout - Workshop Management</title>
    <link rel="stylesheet" href="/EcoSprout/assets/css/style.css">
</head>
<body>
<?php require_once __DIR__ . '/../includes/app_nav.php'; ?>
<div class="container" style="max-width:1000px;">
    <h1>Workshop Management</h1>

    <?php if ($success): ?>
        <div class="success">Workshop data updated successfully.</div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="error"><?php echo htmlspecialchars(implode('<br>', $errors)); ?></div>
    <?php endif; ?>

    <h2>Create Workshop</h2>
    <form action="manage_workshops.php" method="post">
        <input type="hidden" name="action" value="create_workshop">
        <label for="title">Title</label>
        <input type="text" id="title" name="title" required>

        <label for="description">Description</label>
        <textarea id="description" name="description" rows="4" style="width:100%;padding:8px;margin-top:6px;border:1px solid #cbd5c2;border-radius:4px;resize:vertical" required></textarea>

        <label for="workshop_date">Date</label>
        <input type="date" id="workshop_date" name="workshop_date" required>

        <label for="start_time">Start Time</label>
        <input type="time" id="start_time" name="start_time" required>

        <label for="location">Location</label>
        <input type="text" id="location" name="location" required>

        <label for="capacity">Capacity</label>
        <input type="number" id="capacity" name="capacity" min="1" step="1" required>

        <label for="fee">Fee</label>
        <input type="number" id="fee" name="fee" min="0" step="0.01" required>

        <label for="status">Status</label>
        <select id="status" name="status">
            <?php foreach ($allowedStatuses as $status): ?>
                <option value="<?php echo htmlspecialchars($status); ?>"><?php echo htmlspecialchars($status); ?></option>
            <?php endforeach; ?>
        </select>

        <button type="submit">Create Workshop</button>
    </form>

    <h2>Existing Workshops</h2>
    <?php if (empty($workshops)): ?>
        <p>No workshops are available.</p>
    <?php else: ?>
        <?php foreach ($workshops as $workshop): ?>
            <div class="notes" style="margin-top:16px; padding:14px; border:1px solid #d7e1d2; border-radius:6px;">
                <form action="manage_workshops.php" method="post">
                    <input type="hidden" name="action" value="update_workshop">
                    <input type="hidden" name="workshop_id" value="<?php echo (int)$workshop['id']; ?>">

                    <label for="title_<?php echo (int)$workshop['id']; ?>">Title</label>
                    <input type="text" id="title_<?php echo (int)$workshop['id']; ?>" name="title" value="<?php echo htmlspecialchars($workshop['title']); ?>" required>

                    <label for="description_<?php echo (int)$workshop['id']; ?>">Description</label>
                    <textarea id="description_<?php echo (int)$workshop['id']; ?>" name="description" rows="4" style="width:100%;padding:8px;margin-top:6px;border:1px solid #cbd5c2;border-radius:4px;resize:vertical" required><?php echo htmlspecialchars($workshop['description']); ?></textarea>

                    <label for="workshop_date_<?php echo (int)$workshop['id']; ?>">Date</label>
                    <input type="date" id="workshop_date_<?php echo (int)$workshop['id']; ?>" name="workshop_date" value="<?php echo htmlspecialchars($workshop['workshop_date']); ?>" required>

                    <label for="start_time_<?php echo (int)$workshop['id']; ?>">Start Time</label>
                    <input type="time" id="start_time_<?php echo (int)$workshop['id']; ?>" name="start_time" value="<?php echo htmlspecialchars(substr($workshop['start_time'], 0, 5)); ?>" required>

                    <label for="location_<?php echo (int)$workshop['id']; ?>">Location</label>
                    <input type="text" id="location_<?php echo (int)$workshop['id']; ?>" name="location" value="<?php echo htmlspecialchars($workshop['location']); ?>" required>

                    <label for="capacity_<?php echo (int)$workshop['id']; ?>">Capacity</label>
                    <input type="number" id="capacity_<?php echo (int)$workshop['id']; ?>" name="capacity" min="1" step="1" value="<?php echo (int)$workshop['capacity']; ?>" required>

                    <label for="fee_<?php echo (int)$workshop['id']; ?>">Fee</label>
                    <input type="number" id="fee_<?php echo (int)$workshop['id']; ?>" name="fee" min="0" step="0.01" value="<?php echo htmlspecialchars($workshop['fee']); ?>" required>

                    <label for="status_<?php echo (int)$workshop['id']; ?>">Status</label>
                    <select id="status_<?php echo (int)$workshop['id']; ?>" name="status">
                        <?php foreach ($allowedStatuses as $status): ?>
                            <option value="<?php echo htmlspecialchars($status); ?>" <?php echo $workshop['status'] === $status ? 'selected' : ''; ?>><?php echo htmlspecialchars($status); ?></option>
                        <?php endforeach; ?>
                    </select>

                    <button type="submit">Update Workshop</button>
                </form>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <h2>Participant Registrations</h2>
    <?php if (empty($registrations)): ?>
        <p>No registrations yet.</p>
    <?php else: ?>
        <?php foreach ($registrations as $registration): ?>
            <div class="notes" style="margin-top:16px; padding:14px; border:1px solid #d7e1d2; border-radius:6px;">
                <p><strong>Workshop:</strong> <?php echo htmlspecialchars($registration['workshop_title']); ?> (<?php echo htmlspecialchars($registration['workshop_date']); ?>)</p>
                <p><strong>Participant:</strong> <?php echo htmlspecialchars($registration['participant_name']); ?> | <?php echo htmlspecialchars($registration['participant_email']); ?></p>
                <p><strong>Notes:</strong> <?php echo htmlspecialchars($registration['notes'] ?? ''); ?></p>
                <p><strong>Created:</strong> <?php echo htmlspecialchars($registration['created_at']); ?></p>

                <form action="manage_workshops.php" method="post">
                    <input type="hidden" name="action" value="update_registration">
                    <input type="hidden" name="registration_id" value="<?php echo (int)$registration['id']; ?>">

                    <label for="registration_status_<?php echo (int)$registration['id']; ?>">Status</label>
                    <select id="registration_status_<?php echo (int)$registration['id']; ?>" name="registration_status">
                        <?php foreach ($allowedRegistrationStatuses as $status): ?>
                            <option value="<?php echo htmlspecialchars($status); ?>" <?php echo $registration['status'] === $status ? 'selected' : ''; ?>><?php echo htmlspecialchars($status); ?></option>
                        <?php endforeach; ?>
                    </select>

                    <button type="submit">Update Registration</button>
                </form>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <div class="action-row"><a class="link-button secondary" href="/EcoSprout/user/success.php">Back to dashboard</a></div>
</div>
</body>
</html>