<?php
// admin_bookings.php - admin overview for orders and workshop bookings
require_once __DIR__ . '/../db.php';
session_start();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Admin') {
    header('Location: /EcoSprout/index.php?error=' . urlencode('Admin access required'));
    exit;
}

$errors = [];
$success = false;
$allowedOrderStatuses = ['Pending Payment', 'Confirmed', 'Completed', 'Cancelled'];
$allowedRegistrationStatuses = ['Registered', 'Confirmed', 'Cancelled', 'Attended'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_order') {
        $orderId = (int)($_POST['order_id'] ?? 0);
        $status = trim($_POST['status'] ?? 'Pending Payment');
        if ($orderId <= 0) {
            $errors[] = 'Invalid order id.';
        } elseif (!in_array($status, $allowedOrderStatuses, true)) {
            $errors[] = 'Invalid order status.';
        } else {
            $stmt = $mysqli->prepare('UPDATE plant_orders SET status = ? WHERE id = ?');
            if (!$stmt) {
                $errors[] = 'Database error: ' . $mysqli->error;
            } else {
                $stmt->bind_param('si', $status, $orderId);
                if ($stmt->execute()) {
                    $success = true;
                } else {
                    $errors[] = 'Failed to update order: ' . $stmt->error;
                }
                $stmt->close();
            }
        }
    }

    if ($action === 'update_registration') {
        $registrationId = (int)($_POST['registration_id'] ?? 0);
        $status = trim($_POST['registration_status'] ?? 'Registered');
        if ($registrationId <= 0) {
            $errors[] = 'Invalid registration id.';
        } elseif (!in_array($status, $allowedRegistrationStatuses, true)) {
            $errors[] = 'Invalid registration status.';
        } else {
            $stmt = $mysqli->prepare('UPDATE workshop_registrations SET status = ? WHERE id = ?');
            if (!$stmt) {
                $errors[] = 'Database error: ' . $mysqli->error;
            } else {
                $stmt->bind_param('si', $status, $registrationId);
                if ($stmt->execute()) {
                    $success = true;
                } else {
                    $errors[] = 'Failed to update workshop registration: ' . $stmt->error;
                }
                $stmt->close();
            }
        }
    }
}

$orders = [];
$orderResult = $mysqli->query('SELECT o.id, o.quantity, o.unit_price, o.total_amount, o.status, o.created_at, u.username, p.plant_name FROM plant_orders o INNER JOIN users u ON o.user_id = u.id INNER JOIN plants p ON o.plant_id = p.id ORDER BY o.created_at DESC');
if ($orderResult) {
    while ($row = $orderResult->fetch_assoc()) {
        $orders[] = $row;
    }
    $orderResult->close();
}

$registrations = [];
$registrationResult = $mysqli->query('SELECT r.id, r.participant_name, r.participant_email, r.status, r.created_at, w.title AS workshop_title FROM workshop_registrations r INNER JOIN workshops w ON r.workshop_id = w.id ORDER BY r.created_at DESC');
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
    <title>EcoSprout - Orders and Bookings</title>
    <link rel="stylesheet" href="/EcoSprout/assets/css/style.css">
</head>
<body>
<?php require_once __DIR__ . '/../includes/app_nav.php'; ?>
<div class="container" style="max-width:1000px;">
    <h1>Orders and Bookings Overview</h1>

    <?php if ($success): ?>
        <div class="success">Record updated successfully.</div>
    <?php endif; ?>
    <?php if (!empty($errors)): ?>
        <div class="error"><?php echo htmlspecialchars(implode('<br>', $errors)); ?></div>
    <?php endif; ?>

    <h2>Plant Orders</h2>
    <?php if (empty($orders)): ?>
        <p>No plant orders have been placed.</p>
    <?php else: ?>
        <?php foreach ($orders as $order): ?>
            <div class="notes" style="margin-top:16px; padding:14px; border:1px solid #d7e1d2; border-radius:6px;">
                <p><strong>Customer:</strong> <?php echo htmlspecialchars($order['username']); ?></p>
                <p><strong>Plant:</strong> <?php echo htmlspecialchars($order['plant_name']); ?></p>
                <p><strong>Quantity:</strong> <?php echo (int)$order['quantity']; ?> | <strong>Unit price:</strong> $<?php echo number_format((float)$order['unit_price'], 2); ?> | <strong>Total:</strong> $<?php echo number_format((float)$order['total_amount'], 2); ?></p>
                <p><strong>Created:</strong> <?php echo htmlspecialchars($order['created_at']); ?></p>

                <form action="admin_bookings.php" method="post">
                    <input type="hidden" name="action" value="update_order">
                    <input type="hidden" name="order_id" value="<?php echo (int)$order['id']; ?>">
                    <label for="order_status_<?php echo (int)$order['id']; ?>">Status</label>
                    <select id="order_status_<?php echo (int)$order['id']; ?>" name="status">
                        <?php foreach ($allowedOrderStatuses as $status): ?>
                            <option value="<?php echo htmlspecialchars($status); ?>" <?php echo $order['status'] === $status ? 'selected' : ''; ?>><?php echo htmlspecialchars($status); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit">Update Order</button>
                </form>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <h2>Workshop Registrations</h2>
    <?php if (empty($registrations)): ?>
        <p>No workshop registrations have been submitted.</p>
    <?php else: ?>
        <?php foreach ($registrations as $registration): ?>
            <div class="notes" style="margin-top:16px; padding:14px; border:1px solid #d7e1d2; border-radius:6px;">
                <p><strong>Workshop:</strong> <?php echo htmlspecialchars($registration['workshop_title']); ?></p>
                <p><strong>Participant:</strong> <?php echo htmlspecialchars($registration['participant_name']); ?> | <?php echo htmlspecialchars($registration['participant_email']); ?></p>
                <p><strong>Created:</strong> <?php echo htmlspecialchars($registration['created_at']); ?></p>

                <form action="admin_bookings.php" method="post">
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