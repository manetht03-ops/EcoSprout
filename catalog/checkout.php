<?php
// checkout.php - local demo payment checkout that confirms a plant order and reduces stock.
// The actual payment logic is delegated to a mock gateway for cleaner validation and safer data handling.
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/mock_payment_gateway.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: /EcoSprout/index.php?error=' . urlencode('Please login first'));
    exit;
}

$errors = [];
$success = false;
$order = null;
$plant = null;

$plantId = (int)($_GET['plant_id'] ?? $_POST['plant_id'] ?? 0);
$quantity = max(1, (int)($_POST['quantity'] ?? $_GET['quantity'] ?? 1));
$paymentMethod = trim((string)($_POST['payment_method'] ?? 'Visa'));
$cardholderName = trim((string)($_POST['cardholder_name'] ?? ''));
$cardNumber = (string)($_POST['card_number'] ?? '');
$expiryMonth = trim((string)($_POST['expiry_month'] ?? ''));
$expiryYear = trim((string)($_POST['expiry_year'] ?? ''));
$cvv = (string)($_POST['cvv'] ?? '');
$gateway = new MockPaymentGateway();
$paymentMethods = $gateway->getSupportedMethods();

if (isset($_GET['order_id'])) {
    $orderId = (int)$_GET['order_id'];
    $stmt = $mysqli->prepare('SELECT o.id, o.quantity, o.unit_price, o.total_amount, o.status, o.payment_reference, o.created_at, p.plant_name, p.botanical_name FROM plant_orders o INNER JOIN plants p ON o.plant_id = p.id WHERE o.id = ? AND o.user_id = ? LIMIT 1');
    if ($stmt) {
        $userId = (int)$_SESSION['user_id'];
        $stmt->bind_param('ii', $orderId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $order = $result->fetch_assoc();
        $result->close();
        $stmt->close();
        if (!$order) {
            $errors[] = 'Order not found.';
        }
    } else {
        $errors[] = 'Database error: ' . $mysqli->error;
    }
} elseif ($plantId > 0) {
    $stmt = $mysqli->prepare('SELECT id, plant_name, botanical_name, price, stock FROM plants WHERE id = ? LIMIT 1');
    if ($stmt) {
        $stmt->bind_param('i', $plantId);
        $stmt->execute();
        $result = $stmt->get_result();
        $plant = $result->fetch_assoc();
        $result->close();
        $stmt->close();
        if (!$plant) {
            $errors[] = 'Plant not found.';
        }
    } else {
        $errors[] = 'Database error: ' . $mysqli->error;
    }
} else {
    $errors[] = 'Please select a plant to purchase.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($errors) && $plant) {
    $cleanCardNumber = preg_replace('/\D+/', '', $cardNumber);
    $validationErrors = $gateway->validatePaymentInput($paymentMethod, $cardholderName, $cardNumber, $expiryMonth, $expiryYear, $cvv);
    if (!empty($validationErrors)) {
        $errors = array_merge($errors, $validationErrors);
    } else {
        $userId = (int)$_SESSION['user_id'];
        $unitPrice = (float)$plant['price'];
        $total = $unitPrice * $quantity;

        try {
            $paymentResult = $gateway->processPayment($total, $paymentMethod, $cleanCardNumber);
            $paymentReference = $paymentResult['reference'];
            $maskedCard = $paymentResult['masked_card'];

            $mysqli->begin_transaction();
            try {
                $lockStmt = $mysqli->prepare('SELECT stock FROM plants WHERE id = ? FOR UPDATE');
                if (!$lockStmt) {
                    throw new RuntimeException('Database error: ' . $mysqli->error);
                }
                $lockStmt->bind_param('i', $plantId);
                $lockStmt->execute();
                $stockResult = $lockStmt->get_result();
                $stockRow = $stockResult->fetch_assoc();
                $stockResult->close();
                $lockStmt->close();

                if (!$stockRow) {
                    throw new RuntimeException('Plant not found during checkout.');
                }

                if ($quantity > (int)$stockRow['stock']) {
                    throw new RuntimeException('Requested quantity is larger than the current stock.');
                }

                $updateStock = $mysqli->prepare('UPDATE plants SET stock = stock - ? WHERE id = ?');
                if (!$updateStock) {
                    throw new RuntimeException('Database error: ' . $mysqli->error);
                }
                $updateStock->bind_param('ii', $quantity, $plantId);
                if (!$updateStock->execute()) {
                    throw new RuntimeException('Failed to update stock: ' . $updateStock->error);
                }
                $updateStock->close();

                $orderStmt = $mysqli->prepare('INSERT INTO plant_orders (user_id, plant_id, quantity, unit_price, total_amount, status, payment_reference) VALUES (?, ?, ?, ?, ?, "Confirmed", ?)');
                if (!$orderStmt) {
                    throw new RuntimeException('Database error: ' . $mysqli->error);
                }
                $orderStmt->bind_param('iiidds', $userId, $plantId, $quantity, $unitPrice, $total, $paymentReference);
                if (!$orderStmt->execute()) {
                    throw new RuntimeException('Failed to create order: ' . $orderStmt->error);
                }
                $orderId = $orderStmt->insert_id;
                $orderStmt->close();

                $mysqli->commit();
                $_SESSION['last_payment_reference'] = $paymentReference;
                $_SESSION['last_masked_card'] = $maskedCard;
                header('Location: /EcoSprout/catalog/checkout.php?order_id=' . $orderId);
                exit;
            } catch (Throwable $throwable) {
                $mysqli->rollback();
                $errors[] = $throwable->getMessage();
            }
        } catch (Throwable $throwable) {
            $errors[] = $throwable->getMessage();
        }
    }
}

if ($plantId > 0 && !$plant && empty($order)) {
    // keep checkout page usable even if the plant lookup failed earlier
    $stmt = $mysqli->prepare('SELECT id, plant_name, botanical_name, price, stock FROM plants WHERE id = ? LIMIT 1');
    if ($stmt) {
        $stmt->bind_param('i', $plantId);
        $stmt->execute();
        $result = $stmt->get_result();
        $plant = $result->fetch_assoc();
        $result->close();
        $stmt->close();
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>EcoSprout - Checkout</title>
    <link rel="stylesheet" href="/EcoSprout/assets/css/style.css">
</head>
<body>
<?php require_once __DIR__ . '/../includes/app_nav.php'; ?>
<div class="container">
    <h1>Checkout</h1>

    <?php if (!empty($errors)): ?>
        <div class="error"><?php echo htmlspecialchars(implode('<br>', $errors)); ?></div>
    <?php endif; ?>

    <?php if ($order): ?>
        <p><strong>Order ID:</strong> <?php echo (int)$order['id']; ?></p>
        <p><strong>Plant:</strong> <?php echo htmlspecialchars($order['plant_name']); ?></p>
        <p><strong>Quantity:</strong> <?php echo (int)$order['quantity']; ?></p>
        <p><strong>Total:</strong> $<?php echo number_format((float)$order['total_amount'], 2); ?></p>
        <p><strong>Status:</strong> <?php echo htmlspecialchars($order['status']); ?></p>
        <p><strong>Payment reference:</strong> <?php echo htmlspecialchars($order['payment_reference'] ?? 'N/A'); ?></p>
        <?php if (!empty($_SESSION['last_masked_card'])): ?>
            <p><strong>Card:</strong> <?php echo htmlspecialchars($_SESSION['last_masked_card']); ?></p>
        <?php endif; ?>
        <p>Card data was validated and was not stored. Only the last 4 digits and payment reference are kept for reference.</p>
        <div class="action-row"><a class="link-button" href="/EcoSprout/catalog/plants.php">Continue shopping</a></div>
    <?php elseif ($plant): ?>
        <p><strong>Plant:</strong> <?php echo htmlspecialchars($plant['plant_name']); ?></p>
        <p><strong>Botanical name:</strong> <?php echo htmlspecialchars($plant['botanical_name']); ?></p>
        <p><strong>Unit price:</strong> $<?php echo number_format((float)$plant['price'], 2); ?></p>
        <p><strong>Available stock:</strong> <?php echo (int)$plant['stock']; ?></p>

        <form action="checkout.php" method="post">
            <input type="hidden" name="plant_id" value="<?php echo (int)$plant['id']; ?>">

            <label for="quantity">Quantity</label>
            <input type="number" id="quantity" name="quantity" min="1" max="<?php echo (int)$plant['stock']; ?>" value="<?php echo (int)$quantity; ?>" required>

            <label for="payment_method">Payment Method</label>
            <select id="payment_method" name="payment_method" required>
                <?php foreach ($paymentMethods as $method): ?>
                    <option value="<?php echo htmlspecialchars($method); ?>" <?php echo $paymentMethod === $method ? 'selected' : ''; ?>><?php echo htmlspecialchars($method); ?></option>
                <?php endforeach; ?>
            </select>

            <label for="cardholder_name">Cardholder Name</label>
            <input type="text" id="cardholder_name" name="cardholder_name" value="<?php echo htmlspecialchars($cardholderName); ?>" required>

            <label for="card_number">Card Number</label>
            <input type="text" id="card_number" name="card_number" inputmode="numeric" autocomplete="off" maxlength="19" placeholder="1234 5678 9012 3456" required>

            <label for="expiry_month">Expiry Month</label>
            <input type="text" id="expiry_month" name="expiry_month" inputmode="numeric" maxlength="2" placeholder="MM" required>

            <label for="expiry_year">Expiry Year</label>
            <input type="text" id="expiry_year" name="expiry_year" inputmode="numeric" maxlength="2" placeholder="YY" required>

            <label for="cvv">CVV</label>
            <input type="password" id="cvv" name="cvv" inputmode="numeric" maxlength="4" autocomplete="off" required>

            <button type="submit">Pay and Confirm Order</button>
        </form>
        <p>Payment is simulated for this assignment. No real card details are stored.</p>
        <div class="action-row"><a class="link-button secondary" href="/EcoSprout/catalog/plants.php">Back to plant catalogue</a></div>
    <?php endif; ?>

    <div class="action-row"><a class="link-button secondary" href="/EcoSprout/user/success.php">Back to dashboard</a></div>
</div>
</body>
</html>