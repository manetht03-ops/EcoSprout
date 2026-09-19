<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$sessionData = $_SESSION ?? [];
$isLoggedIn = isset($sessionData['user_id']);
$role = isset($sessionData['role']) ? (string)$sessionData['role'] : '';
$currentPath = isset($_SERVER['SCRIPT_NAME']) ? (string)$_SERVER['SCRIPT_NAME'] : '';

$links = [
    ['href' => '/EcoSprout/catalog/plants.php', 'label' => 'Plants'],
];

if ($isLoggedIn) {
    $links[] = ['href' => '/EcoSprout/user/success.php', 'label' => 'Dashboard'];
    $links[] = ['href' => '/EcoSprout/user/profile.php', 'label' => 'Profile'];

    // Workshops and customer queries are customer-facing options only.
    if ($role === 'User') {
        $links[] = ['href' => '/EcoSprout/catalog/workshops.php', 'label' => 'Workshops'];
        $links[] = ['href' => '/EcoSprout/user/submit_query.php', 'label' => 'Queries'];
    }

    if ($role === 'Manager' || $role === 'Admin') {
        $links[] = ['href' => '/EcoSprout/management/manage_plants.php', 'label' => 'Manage Plants'];
        $links[] = ['href' => '/EcoSprout/management/manage_workshops.php', 'label' => 'Manage Workshops'];
        $links[] = ['href' => '/EcoSprout/management/manage_queries.php', 'label' => 'Manage Queries'];
    }

    if ($role === 'Admin') {
        $links[] = ['href' => '/EcoSprout/admin/admin_users.php', 'label' => 'Admin'];
        $links[] = ['href' => '/EcoSprout/admin/sales_report.php', 'label' => 'Reports'];
    }
} else {
    $links[] = ['href' => '/EcoSprout/catalog/workshops.php', 'label' => 'Workshops'];
}
?>
<div class="app-nav">
    <div class="app-nav-inner">
        <a class="brand" href="/EcoSprout/index.php">EcoSprout</a>
        <nav class="app-nav-links" aria-label="Primary">
            <?php foreach ($links as $item): ?>
                <?php $isActive = strpos($currentPath, str_replace('/EcoSprout', '', $item['href'])) !== false; ?>
                <a class="<?php echo $isActive ? 'active' : ''; ?>" href="<?php echo htmlspecialchars($item['href']); ?>"><?php echo htmlspecialchars($item['label']); ?></a>
            <?php endforeach; ?>
        </nav>
        <div class="app-nav-auth">
            <?php if ($isLoggedIn): ?>
                <a class="link-button secondary" href="/EcoSprout/auth/logout.php">Logout</a>
            <?php else: ?>
                <a class="link-button secondary" href="/EcoSprout/auth/register.php">Register</a>
                <a class="link-button" href="/EcoSprout/index.php">Login</a>
            <?php endif; ?>
        </div>
    </div>
</div>
