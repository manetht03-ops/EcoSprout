<?php
// admin_users.php - simple Admin UI for managing users (list, create, update role, reset password, delete)
// Usage: Access only as an Admin. This is a simple development-demo implementation.
require_once __DIR__ . '/../db.php';
session_start();

// Ensure the user is logged in and is an Admin
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Admin') {
    header('Location: /EcoSprout/index.php?error=' . urlencode('Admin access required'));
    exit;
}

$errors = [];
$success = false;

// Handle create user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'User';

    if ($username === '' || $password === '') {
        $errors[] = 'Username and password are required for new users.';
    } elseif (!filter_var($username, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please provide a valid email for username.';
    } else {
        // check exists
        $check = $mysqli->prepare('SELECT id FROM users WHERE username = ?');
        $check->bind_param('s', $username);
        $check->execute();
        $check->store_result();
        if ($check->num_rows > 0) {
            $errors[] = 'User already exists.';
            $check->close();
        } else {
            $check->close();
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $ins = $mysqli->prepare('INSERT INTO users (username, password, role) VALUES (?, ?, ?)');
            $ins->bind_param('sss', $username, $hash, $role);
            if (!$ins->execute()) {
                $errors[] = 'Failed to create user: ' . $ins->error;
            } else {
                $success = true;
            }
            $ins->close();
        }
    }
}

// Handle update (role change) or reset password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $uid = (int)($_POST['user_id'] ?? 0);
    $role = $_POST['role'] ?? 'User';
    $newpass = $_POST['new_password'] ?? '';

    if ($uid <= 0) {
        $errors[] = 'Invalid user id.';
    } else {
        if ($newpass !== '') {
            // update password
            $hash = password_hash($newpass, PASSWORD_DEFAULT);
            $up = $mysqli->prepare('UPDATE users SET password = ?, role = ? WHERE id = ?');
            $up->bind_param('ssi', $hash, $role, $uid);
        } else {
            // only role
            $up = $mysqli->prepare('UPDATE users SET role = ? WHERE id = ?');
            $up->bind_param('si', $role, $uid);
        }
        if (!$up->execute()) {
            $errors[] = 'Failed to update user: ' . $up->error;
        } else {
            $success = true;
        }
        $up->close();
    }
}

// Handle delete via GET (simple approach)
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $delId = (int)$_GET['id'];
    if ($delId <= 0) {
        $errors[] = 'Invalid id to delete.';
    } else {
        // prevent deleting self
        if ($delId === (int)$_SESSION['user_id']) {
            $errors[] = 'You cannot delete your own account while logged in.';
        } else {
            $del = $mysqli->prepare('DELETE FROM users WHERE id = ?');
            $del->bind_param('i', $delId);
            if (!$del->execute()) {
                $errors[] = 'Failed to delete user: ' . $del->error;
            } else {
                $success = true;
            }
            $del->close();
        }
    }
}

// Fetch users list
$users = [];
$res = $mysqli->query('SELECT id, username, role, created_at FROM users ORDER BY id ASC');
if ($res) {
    while ($r = $res->fetch_assoc()) {
        $users[] = $r;
    }
    $res->close();
}

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>EcoSprout - Admin Users</title>
    <link rel="stylesheet" href="/EcoSprout/assets/css/style.css">
</head>
<body>
<?php require_once __DIR__ . '/../includes/app_nav.php'; ?>
<div class="container">
    <h1>Admin: User Management</h1>

    <?php if (!empty($errors)): ?>
        <div class="error"><?php echo htmlspecialchars(implode('<br>', $errors)); ?></div>
    <?php endif; ?>
    <?php if (isset($success) && $success): ?>
        <div class="success">Operation completed successfully.</div>
    <?php endif; ?>

    <h2>Create New User</h2>
    <form action="admin_users.php" method="post">
        <input type="hidden" name="action" value="create">
        <label for="username">Username (email)</label>
        <input type="email" id="username" name="username" required>
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required>
        <label for="role">Role</label>
        <select id="role" name="role">
            <option value="User">User</option>
            <option value="Manager">Manager</option>
            <option value="Admin">Admin</option>
        </select>
        <button type="submit">Create</button>
    </form>

    <h2>Existing Users</h2>
    <table class="table">
        <thead><tr><th>ID</th><th>Username</th><th>Role</th><th>Created</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($users as $u): ?>
            <tr>
                <td><?php echo $u['id']; ?></td>
                <td><?php echo htmlspecialchars($u['username']); ?></td>
                <td><?php echo htmlspecialchars($u['role']); ?></td>
                <td><?php echo htmlspecialchars($u['created_at']); ?></td>
                <td>
                    <!-- Simple inline form to change role or reset password -->
                    <form style="display:inline-block;" action="admin_users.php" method="post">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                        <select name="role">
                            <option value="User" <?php echo $u['role']==='User' ? 'selected' : ''; ?>>User</option>
                            <option value="Manager" <?php echo $u['role']==='Manager' ? 'selected' : ''; ?>>Manager</option>
                            <option value="Admin" <?php echo $u['role']==='Admin' ? 'selected' : ''; ?>>Admin</option>
                        </select>
                        <input type="password" name="new_password" placeholder="(leave blank to keep)" style="width:160px">
                        <button type="submit">Update</button>
                    </form>
                    <a href="admin_users.php?action=delete&id=<?php echo $u['id']; ?>" onclick="return confirm('Delete user <?php echo htmlspecialchars($u['username']); ?>?');">Delete</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <div class="action-row"><a class="link-button secondary" href="/EcoSprout/user/success.php">Back to dashboard</a></div>
</div>
</body>
</html>