<?php
// manage_plants.php - staff/admin screen to add and update plant inventory
require_once __DIR__ . '/../db.php';
session_start();

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['Admin', 'Manager'], true)) {
    header('Location: /EcoSprout/index.php?error=' . urlencode('Staff access required'));
    exit;
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $plantName = trim($_POST['plant_name'] ?? '');
        $botanicalName = trim($_POST['botanical_name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $careRequirements = trim($_POST['care_requirements'] ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $stock = (int)($_POST['stock'] ?? 0);

        if ($plantName === '' || $botanicalName === '' || $description === '' || $careRequirements === '') {
            $errors[] = 'All plant fields are required.';
        } elseif ($price < 0) {
            $errors[] = 'Price cannot be negative.';
        } elseif ($stock < 0) {
            $errors[] = 'Stock cannot be negative.';
        } else {
            $check = $mysqli->prepare('SELECT id FROM plants WHERE plant_name = ? LIMIT 1');
            if (!$check) {
                $errors[] = 'Database error: ' . $mysqli->error;
            } else {
                $check->bind_param('s', $plantName);
                $check->execute();
                $check->store_result();
                if ($check->num_rows > 0) {
                    $errors[] = 'A plant with that name already exists.';
                } else {
                    $stmt = $mysqli->prepare('INSERT INTO plants (plant_name, botanical_name, description, care_requirements, price, stock) VALUES (?, ?, ?, ?, ?, ?)');
                    if (!$stmt) {
                        $errors[] = 'Database error: ' . $mysqli->error;
                    } else {
                        $stmt->bind_param('ssssdi', $plantName, $botanicalName, $description, $careRequirements, $price, $stock);
                        if ($stmt->execute()) {
                            $success = true;
                        } else {
                            $errors[] = 'Failed to add plant: ' . $stmt->error;
                        }
                        $stmt->close();
                    }
                }
                $check->close();
            }
        }
    }

    if ($action === 'update') {
        $plantId = (int)($_POST['plant_id'] ?? 0);
        $description = trim($_POST['description'] ?? '');
        $careRequirements = trim($_POST['care_requirements'] ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $stock = (int)($_POST['stock'] ?? 0);

        if ($plantId <= 0) {
            $errors[] = 'Invalid plant id.';
        } elseif ($description === '' || $careRequirements === '') {
            $errors[] = 'Description and care requirements are required.';
        } elseif ($price < 0 || $stock < 0) {
            $errors[] = 'Price and stock must be zero or greater.';
        } else {
            $stmt = $mysqli->prepare('UPDATE plants SET description = ?, care_requirements = ?, price = ?, stock = ? WHERE id = ?');
            if (!$stmt) {
                $errors[] = 'Database error: ' . $mysqli->error;
            } else {
                $stmt->bind_param('ssdii', $description, $careRequirements, $price, $stock, $plantId);
                if ($stmt->execute()) {
                    $success = true;
                } else {
                    $errors[] = 'Failed to update plant: ' . $stmt->error;
                }
                $stmt->close();
            }
        }
    }
}

$plants = [];
$result = $mysqli->query('SELECT id, plant_name, botanical_name, description, care_requirements, price, stock, created_at FROM plants ORDER BY plant_name ASC');
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $plants[] = $row;
    }
    $result->close();
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>EcoSprout - Plant Inventory</title>
    <link rel="stylesheet" href="/EcoSprout/assets/css/style.css">
</head>
<body>
<?php require_once __DIR__ . '/../includes/app_nav.php'; ?>
<div class="container" style="max-width:900px;">
    <h1>Plant Inventory</h1>

    <?php if ($success): ?>
        <div class="success">Plant inventory updated successfully.</div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="error"><?php echo htmlspecialchars(implode('<br>', $errors)); ?></div>
    <?php endif; ?>

    <h2>Add New Plant</h2>
    <form action="manage_plants.php" method="post">
        <input type="hidden" name="action" value="create">

        <label for="plant_name">Plant Name</label>
        <input type="text" id="plant_name" name="plant_name" required>

        <label for="botanical_name">Botanical Name</label>
        <input type="text" id="botanical_name" name="botanical_name" required>

        <label for="description">Description</label>
        <textarea id="description" name="description" rows="4" style="width:100%;padding:8px;margin-top:6px;border:1px solid #cbd5c2;border-radius:4px;resize:vertical" required></textarea>

        <label for="care_requirements">Care Requirements</label>
        <textarea id="care_requirements" name="care_requirements" rows="4" style="width:100%;padding:8px;margin-top:6px;border:1px solid #cbd5c2;border-radius:4px;resize:vertical" required></textarea>

        <label for="price">Price</label>
        <input type="number" id="price" name="price" min="0" step="0.01" required>

        <label for="stock">Stock</label>
        <input type="number" id="stock" name="stock" min="0" step="1" required>

        <button type="submit">Add Plant</button>
    </form>

    <h2>Existing Plants</h2>
    <?php if (empty($plants)): ?>
        <p>No plants are available yet.</p>
    <?php else: ?>
        <?php foreach ($plants as $plant): ?>
            <div class="notes" style="margin-top:16px; padding:14px; border:1px solid #d7e1d2; border-radius:6px;">
                <p><strong><?php echo htmlspecialchars($plant['plant_name']); ?></strong></p>
                <p><strong>Botanical:</strong> <?php echo htmlspecialchars($plant['botanical_name']); ?></p>
                <p><strong>Created:</strong> <?php echo htmlspecialchars($plant['created_at']); ?></p>

                <form action="manage_plants.php" method="post">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="plant_id" value="<?php echo (int)$plant['id']; ?>">

                    <label for="description_<?php echo (int)$plant['id']; ?>">Description</label>
                    <textarea id="description_<?php echo (int)$plant['id']; ?>" name="description" rows="4" style="width:100%;padding:8px;margin-top:6px;border:1px solid #cbd5c2;border-radius:4px;resize:vertical" required><?php echo htmlspecialchars($plant['description']); ?></textarea>

                    <label for="care_<?php echo (int)$plant['id']; ?>">Care Requirements</label>
                    <textarea id="care_<?php echo (int)$plant['id']; ?>" name="care_requirements" rows="4" style="width:100%;padding:8px;margin-top:6px;border:1px solid #cbd5c2;border-radius:4px;resize:vertical" required><?php echo htmlspecialchars($plant['care_requirements']); ?></textarea>

                    <label for="price_<?php echo (int)$plant['id']; ?>">Price</label>
                    <input type="number" id="price_<?php echo (int)$plant['id']; ?>" name="price" min="0" step="0.01" value="<?php echo htmlspecialchars($plant['price']); ?>" required>

                    <label for="stock_<?php echo (int)$plant['id']; ?>">Stock</label>
                    <input type="number" id="stock_<?php echo (int)$plant['id']; ?>" name="stock" min="0" step="1" value="<?php echo (int)$plant['stock']; ?>" required>

                    <button type="submit">Update Plant</button>
                </form>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <div class="action-row"><a class="link-button secondary" href="/EcoSprout/user/success.php">Back to dashboard</a></div>
</div>
</body>
</html>