<?php
// success.php - role-based dashboard page for logged-in users.
require_once __DIR__ . '/../db.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /EcoSprout/index.php?error=' . urlencode('Please login first'));
    exit;
}

$userId = (int)$_SESSION['user_id'];
$username = htmlspecialchars($_SESSION['username']);
$role = htmlspecialchars($_SESSION['role']);

$plantCount = 0;
$workshopCount = 0;
$openQueryCount = 0;

$plantResult = $mysqli->query('SELECT COUNT(*) AS total FROM plants');
if ($plantResult) {
    $plantRow = $plantResult->fetch_assoc();
    $plantCount = (int)($plantRow['total'] ?? 0);
    $plantResult->close();
}

$workshopResult = $mysqli->query('SELECT COUNT(*) AS total FROM workshops');
if ($workshopResult) {
    $workshopRow = $workshopResult->fetch_assoc();
    $workshopCount = (int)($workshopRow['total'] ?? 0);
    $workshopResult->close();
}

if ($role === 'User') {
    $queryStmt = $mysqli->prepare('SELECT COUNT(*) AS total FROM nursery_queries WHERE user_id = ?');
    $queryStmt->bind_param('i', $userId);
} else {
    $queryStmt = $mysqli->prepare('SELECT COUNT(*) AS total FROM nursery_queries WHERE status = ?');
    $status = 'Open';
    $queryStmt->bind_param('s', $status);
}

if ($queryStmt) {
    $queryStmt->execute();
    $queryResult = $queryStmt->get_result();
    $queryRow = $queryResult ? $queryResult->fetch_assoc() : null;
    $openQueryCount = (int)($queryRow['total'] ?? 0);
    $queryStmt->close();
}

$stats = [
    ['label' => 'Plant catalog', 'value' => (string)$plantCount, 'detail' => 'Active listings'],
    ['label' => 'Workshops', 'value' => (string)$workshopCount, 'detail' => 'Scheduled'],
    ['label' => 'Open queries', 'value' => (string)$openQueryCount, 'detail' => ($role === 'User' ? 'Your questions' : 'Needs attention')],
    ['label' => 'Role', 'value' => $role, 'detail' => 'Access level'],
];

if ($role === 'Admin') {
    $summaryTitle = 'Administrative overview';
    $summaryText = 'You can review operations, staff activity, and nursery performance from the main navigation.';
    $priorityItems = [
        'Audit recent customer enquiries and responses.',
        'Review workshop registrations and booking trends.',
        'Monitor sales data and stock performance.'
    ];
} elseif ($role === 'Manager') {
    $summaryTitle = 'Operations overview';
    $summaryText = 'Focus on plant care, workshop readiness, and customer query responses for this week.';
    $priorityItems = [
        'Confirm stock levels for best-selling plants.',
        'Prepare upcoming workshop materials and staff notes.',
        'Review and respond to open customer enquiries.'
    ];
} else {
    $summaryTitle = 'Customer overview';
    $summaryText = 'Your account is ready for plant browsing, event registration, and submitting questions.';
    $priorityItems = [
        'Explore new plants and seasonal offers.',
        'Book a workshop that matches your interests.',
        'Submit any questions about plant care or orders.'
    ];
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>EcoSprout - Dashboard</title>
    <link rel="stylesheet" href="/EcoSprout/assets/css/style.css">
</head>
<body>
<?php require_once __DIR__ . '/../includes/app_nav.php'; ?>
<div class="container dashboard-page">
    <div class="dashboard-topbar">
        <div>
            <p class="eyebrow">Welcome back</p>
            <h1><?php echo $username; ?></h1>
        </div>
        <div class="role-badge"><?php echo $role; ?></div>
    </div>

    <p class="dashboard-intro">Your nursery workspace is ready. Use the main navigation to move between modules.</p>

    <div class="dashboard-stats">
        <?php foreach ($stats as $stat): ?>
            <div class="stat-card">
                <span class="stat-label"><?php echo htmlspecialchars($stat['label']); ?></span>
                <strong class="stat-value"><?php echo htmlspecialchars($stat['value']); ?></strong>
                <span class="stat-detail"><?php echo htmlspecialchars($stat['detail']); ?></span>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="dashboard-grid">
        <section class="info-card profile-card">
            <div class="card-header">
                <h2>Profile snapshot</h2>
            </div>
            <div class="profile-visual">
                <div class="avatar-placeholder"><?php echo strtoupper(substr($username, 0, 1)); ?></div>
                <div>
                    <p class="profile-name"><?php echo $username; ?></p>
                    <p class="profile-role"><?php echo $role; ?></p>
                </div>
            </div>
            <p class="muted-copy">Status: Active</p>
            <p class="muted-copy">Last login: Today</p>
        </section>

        <section class="info-card activity-card">
            <div class="card-header">
                <h2><?php echo htmlspecialchars($summaryTitle); ?></h2>
            </div>
            <p><?php echo htmlspecialchars($summaryText); ?></p>
            <ul class="check-list">
                <?php foreach ($priorityItems as $item): ?>
                    <li><?php echo htmlspecialchars($item); ?></li>
                <?php endforeach; ?>
            </ul>
        </section>
    </div>
</div>
</body>
</html>