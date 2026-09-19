<?php
// plants.php - searchable plant catalogue for customers and staff
require_once __DIR__ . '/../db.php';
session_start();

$query = isset($_GET['q']) ? trim($_GET['q']) : '';
$plants = [];

if ($query === '') {
    $stmt = $mysqli->prepare('SELECT id, plant_name, botanical_name, description, care_requirements, price, stock FROM plants ORDER BY plant_name ASC');
} else {
    $like = '%' . $query . '%';
    $stmt = $mysqli->prepare('SELECT id, plant_name, botanical_name, description, care_requirements, price, stock FROM plants WHERE plant_name LIKE ? OR botanical_name LIKE ? OR description LIKE ? OR care_requirements LIKE ? ORDER BY plant_name ASC');
    $stmt->bind_param('ssss', $like, $like, $like, $like);
}

if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $plants[] = $row;
    }
    $result->close();
    $stmt->close();
}

$loggedIn = isset($_SESSION['user_id']);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>EcoSprout - Plant Catalogue</title>
    <link rel="stylesheet" href="/EcoSprout/assets/css/style.css">
</head>
<body>
<?php require_once __DIR__ . '/../includes/app_nav.php'; ?>
<div class="container">
    <h1>Plant Catalogue</h1>

    <form action="plants.php" method="get">
        <label for="q">Search plants</label>
        <input type="text" id="q" name="q" value="<?php echo htmlspecialchars($query); ?>" placeholder="Name, botanical name, or care requirements">
        <button type="submit">Search</button>
    </form>

    <?php if (empty($plants)): ?>
        <div class="notes">No plants matched your search.</div>
    <?php else: ?>
        <div class="paginated-list" data-page-size="4">
        <?php foreach ($plants as $plant): ?>
            <div class="notes paginated-item" style="margin-top:16px; padding:12px; border:1px solid #d7e1d2; border-radius:6px;">
                <h2 style="margin-top:0;"><?php echo htmlspecialchars($plant['plant_name']); ?></h2>
                <p><strong>Botanical name:</strong> <?php echo htmlspecialchars($plant['botanical_name']); ?></p>
                <p><?php echo htmlspecialchars($plant['description']); ?></p>
                <p><strong>Care:</strong> <?php echo htmlspecialchars($plant['care_requirements']); ?></p>
                <p><strong>Price:</strong> $<?php echo number_format((float)$plant['price'], 2); ?> | <strong>Stock:</strong> <?php echo (int)$plant['stock']; ?></p>
                <?php if ($loggedIn): ?>
                    <div class="action-row"><a class="link-button" href="/EcoSprout/catalog/checkout.php?plant_id=<?php echo (int)$plant['id']; ?>">Buy now</a></div>
                <?php else: ?>
                    <div class="action-row"><a class="link-button secondary" href="/EcoSprout/index.php">Login to buy</a></div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($loggedIn): ?>
        <div class="action-row">
            <a class="link-button secondary" href="/EcoSprout/user/submit_query.php">Ask a nursery question</a>
            <a class="link-button secondary" href="/EcoSprout/catalog/workshops.php">Browse workshops and events</a>
        </div>
    <?php else: ?>
        <p><a class="link-button secondary" href="/EcoSprout/index.php">Login</a> to ask a nursery question.</p>
    <?php endif; ?>

    <div class="action-row"><a class="link-button secondary" href="/EcoSprout/index.php">Back to login</a></div>
</div>
<script src="/EcoSprout/assets/js/main.js"></script>
</body>
</html>