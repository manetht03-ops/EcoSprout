<?php
// sales_report.php - simple admin sales report for completed and pending activity
require_once __DIR__ . '/../db.php';
session_start();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Admin') {
    header('Location: /EcoSprout/index.php?error=' . urlencode('Admin access required'));
    exit;
}

$stats = [
    'orders_total' => 0,
    'orders_revenue' => 0,
    'confirmed_orders' => 0,
    'workshop_registrations' => 0,
    'workshop_fees' => 0,
    'customer_queries' => 0,
];

$orderSummary = $mysqli->query('SELECT COUNT(*) AS orders_total, COALESCE(SUM(total_amount), 0) AS orders_revenue, SUM(CASE WHEN status IN ("Confirmed", "Completed") THEN 1 ELSE 0 END) AS confirmed_orders FROM plant_orders');
if ($orderSummary) {
    $stats = array_merge($stats, $orderSummary->fetch_assoc() ?: []);
    $orderSummary->close();
}

$workshopSummary = $mysqli->query('SELECT COUNT(*) AS workshop_registrations, COALESCE(SUM(w.fee), 0) AS workshop_fees FROM workshop_registrations r INNER JOIN workshops w ON r.workshop_id = w.id WHERE r.status IN ("Registered", "Confirmed", "Attended")');
if ($workshopSummary) {
    $stats = array_merge($stats, $workshopSummary->fetch_assoc() ?: []);
    $workshopSummary->close();
}

$querySummary = $mysqli->query('SELECT COUNT(*) AS customer_queries FROM nursery_queries');
if ($querySummary) {
    $stats = array_merge($stats, $querySummary->fetch_assoc() ?: []);
    $querySummary->close();
}

$recentOrders = [];
$orderResult = $mysqli->query('SELECT o.created_at, u.username, p.plant_name, o.quantity, o.total_amount, o.status FROM plant_orders o INNER JOIN users u ON o.user_id = u.id INNER JOIN plants p ON o.plant_id = p.id ORDER BY o.created_at DESC LIMIT 10');
if ($orderResult) {
    while ($row = $orderResult->fetch_assoc()) {
        $recentOrders[] = $row;
    }
    $orderResult->close();
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>EcoSprout - Sales Report</title>
    <link rel="stylesheet" href="/EcoSprout/assets/css/style.css">
</head>
<body>
<?php require_once __DIR__ . '/../includes/app_nav.php'; ?>
<div class="container" style="max-width:900px;">
    <h1>Sales Report</h1>

    <div class="notes" style="margin-top:16px; padding:14px; border:1px solid #d7e1d2; border-radius:6px;">
        <p><strong>Plant orders placed:</strong> <?php echo (int)$stats['orders_total']; ?></p>
        <p><strong>Confirmed / completed orders:</strong> <?php echo (int)$stats['confirmed_orders']; ?></p>
        <p><strong>Total plant order value:</strong> $<?php echo number_format((float)$stats['orders_revenue'], 2); ?></p>
        <p><strong>Workshop registrations:</strong> <?php echo (int)$stats['workshop_registrations']; ?></p>
        <p><strong>Workshop fee value:</strong> $<?php echo number_format((float)$stats['workshop_fees'], 2); ?></p>
        <p><strong>Customer queries submitted:</strong> <?php echo (int)$stats['customer_queries']; ?></p>
    </div>

    <h2>Recent Orders</h2>
    <?php if (empty($recentOrders)): ?>
        <p>No recent orders to display.</p>
    <?php else: ?>
        <table class="table">
            <thead>
                <tr><th>Date</th><th>Customer</th><th>Plant</th><th>Qty</th><th>Total</th><th>Status</th></tr>
            </thead>
            <tbody>
                <?php foreach ($recentOrders as $order): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($order['created_at']); ?></td>
                        <td><?php echo htmlspecialchars($order['username']); ?></td>
                        <td><?php echo htmlspecialchars($order['plant_name']); ?></td>
                        <td><?php echo (int)$order['quantity']; ?></td>
                        <td>$<?php echo number_format((float)$order['total_amount'], 2); ?></td>
                        <td><?php echo htmlspecialchars($order['status']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <p>This report only uses aggregated records and order metadata, not passwords or other sensitive user secrets.</p>
    <div class="action-row"><a class="link-button secondary" href="/EcoSprout/user/success.php">Back to dashboard</a></div>
</div>
</body>
</html>