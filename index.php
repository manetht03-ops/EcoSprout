<?php
// EcoSprout - Simple login page
// AI assistant: Copilot CLI runtime in VS Code
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>EcoSprout - Login</title>
    <link rel="stylesheet" href="/EcoSprout/assets/css/style.css">
</head>
<body>
<?php require_once __DIR__ . '/includes/app_nav.php'; ?>
<div class="auth-shell">
    <div class="auth-hero">
        <div class="hero-badge">EcoSprout Nursery</div>
        <h1>Grow smarter with a greener customer experience.</h1>
        <p class="hero-copy">Manage plants, workshops, customer enquiries, and daily nursery operations from one simple dashboard.</p>

        <ul class="feature-list">
            <li>Browse the nursery catalogue</li>
            <li>Book workshop sessions</li>
            <li>Track orders and staff tasks</li>
        </ul>
    </div>

    <div class="auth-panel">
        <div class="panel-header">
            <span class="panel-kicker">Welcome back</span>
            <h2>Login to continue</h2>
        </div>

        <?php if (isset($_GET['error'])): ?>
            <div class="error"><?php echo htmlspecialchars($_GET['error']); ?></div>
        <?php endif; ?>

        <form id="loginForm" action="/EcoSprout/auth/login.php" method="post">
            <label for="username">Username (email)</label>
            <input type="text" id="username" name="username" placeholder="e.g. admin@ecosprout" required>

            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>

            <button type="submit">Login</button>
        </form>

        <div class="auth-links">
            <a href="/EcoSprout/auth/register.php">Create account</a>
            <a href="/EcoSprout/auth/reset_request.php">Forgot password?</a>
        </div>

        <div class="notes compact">
            <p><a href="/EcoSprout/catalog/plants.php">Browse plant catalogue</a> | <a href="/EcoSprout/catalog/workshops.php">Browse workshops</a></p>
        </div>
    </div>
</div>
<script src="/EcoSprout/assets/js/main.js"></script>
</body>
</html>