<?php
// setup.php - run this once to create the database and initial users
// Adjust DB credentials below if your XAMPP uses a password for root
$DB_HOST = '127.0.0.1';
$DB_USER = 'root'; 
$DB_PASS = 'mysql';
$DB_NAME = 'ecosprout_db';

// Connect to MySQL server
$mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS);
if ($mysqli->connect_error) {
    die('Connection error: ' . $mysqli->connect_error);
}

// Create database
if ($mysqli->query("CREATE DATABASE IF NOT EXISTS `$DB_NAME` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci") === TRUE) {
    echo "Database `$DB_NAME` is ready.<br>";
} else {
    die('Database creation failed: ' . $mysqli->error);
}

// Select database
$mysqli->select_db($DB_NAME);

// Create users table
$createTableSql = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($mysqli->query($createTableSql) === TRUE) {
    echo "Table `users` is ready.<br>";
} else {
    die('Table creation failed: ' . $mysqli->error);
}

// Create password_resets table to support password reset flow (tokens stored, expires)
$createResetSql = "CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(255) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($mysqli->query($createResetSql) === TRUE) {
    echo "Table `password_resets` is ready.<br>";
} else {
    die('password_resets table creation failed: ' . $mysqli->error);
}

// Create plants table to support catalogue search and inventory management
$createPlantsSql = "CREATE TABLE IF NOT EXISTS plants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    plant_name VARCHAR(255) NOT NULL,
    botanical_name VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    care_requirements TEXT NOT NULL,
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    stock INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($mysqli->query($createPlantsSql) === TRUE) {
    echo "Table `plants` is ready.<br>";
} else {
    die('plants table creation failed: ' . $mysqli->error);
}

// Create customer inquiry table so logged-in users can submit queries to nursery staff
$createQueriesSql = "CREATE TABLE IF NOT EXISTS nursery_queries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'Open',
    admin_response TEXT DEFAULT NULL,
    responded_by INT DEFAULT NULL,
    responded_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (responded_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($mysqli->query($createQueriesSql) === TRUE) {
    echo "Table `nursery_queries` is ready.<br>";
} else {
    die('nursery_queries table creation failed: ' . $mysqli->error);
}

// Create plant orders table to support the checkout flow
$createOrdersSql = "CREATE TABLE IF NOT EXISTS plant_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    plant_id INT NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'Pending Payment',
    payment_reference VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (plant_id) REFERENCES plants(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($mysqli->query($createOrdersSql) === TRUE) {
    echo "Table `plant_orders` is ready.<br>";
} else {
    die('plant_orders table creation failed: ' . $mysqli->error);
}

// Create workshops table for educational events
$createWorkshopsSql = "CREATE TABLE IF NOT EXISTS workshops (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    workshop_date DATE NOT NULL,
    start_time TIME NOT NULL,
    location VARCHAR(255) NOT NULL,
    capacity INT NOT NULL DEFAULT 0,
    fee DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    status VARCHAR(50) NOT NULL DEFAULT 'Scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($mysqli->query($createWorkshopsSql) === TRUE) {
    echo "Table `workshops` is ready.<br>";
} else {
    die('workshops table creation failed: ' . $mysqli->error);
}

// Create workshop registrations table
$createWorkshopRegsSql = "CREATE TABLE IF NOT EXISTS workshop_registrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    workshop_id INT NOT NULL,
    user_id INT NOT NULL,
    participant_name VARCHAR(255) NOT NULL,
    participant_email VARCHAR(255) NOT NULL,
    notes TEXT DEFAULT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'Registered',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (workshop_id) REFERENCES workshops(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_workshop_user (workshop_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($mysqli->query($createWorkshopRegsSql) === TRUE) {
    echo "Table `workshop_registrations` is ready.<br>";
} else {
    die('workshop_registrations table creation failed: ' . $mysqli->error);
}

// Seed a small workshop list for the events page
$workshops = [
    [
        'title' => 'Indoor Plant Care Basics',
        'description' => 'Learn how to water, light, and repot common indoor plants.',
        'workshop_date' => '2026-09-05',
        'start_time' => '10:00:00',
        'location' => 'EcoSprout Training Room',
        'capacity' => 20,
        'fee' => 15.00,
        'status' => 'Scheduled',
    ],
    [
        'title' => 'Seasonal Garden Planning',
        'description' => 'Plan your garden layout and planting calendar for the next season.',
        'workshop_date' => '2026-09-12',
        'start_time' => '14:00:00',
        'location' => 'Greenhouse Hall',
        'capacity' => 25,
        'fee' => 20.00,
        'status' => 'Scheduled',
    ],
    [
        'title' => 'DIY Composting for Beginners',
        'description' => 'A practical session on home composting and soil improvement.',
        'workshop_date' => '2026-09-19',
        'start_time' => '09:30:00',
        'location' => 'Workshop Yard',
        'capacity' => 18,
        'fee' => 10.00,
        'status' => 'Scheduled',
    ],
];

$workshopCheck = $mysqli->prepare('SELECT id FROM workshops WHERE title = ? AND workshop_date = ? LIMIT 1');
$workshopInsert = $mysqli->prepare('INSERT INTO workshops (title, description, workshop_date, start_time, location, capacity, fee, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
if (!$workshopCheck || !$workshopInsert) {
    die('Workshop setup failed: ' . $mysqli->error);
}

foreach ($workshops as $workshop) {
    $workshopCheck->bind_param('ss', $workshop['title'], $workshop['workshop_date']);
    $workshopCheck->execute();
    $workshopCheck->store_result();
    if ($workshopCheck->num_rows === 0) {
        $workshopInsert->bind_param(
            'sssssids',
            $workshop['title'],
            $workshop['description'],
            $workshop['workshop_date'],
            $workshop['start_time'],
            $workshop['location'],
            $workshop['capacity'],
            $workshop['fee'],
            $workshop['status']
        );
        if ($workshopInsert->execute()) {
            echo "Inserted workshop: {$workshop['title']}<br>";
        } else {
            echo "Failed to insert {$workshop['title']}: " . $workshopInsert->error . '<br>';
        }
    } else {
        echo "Workshop {$workshop['title']} already exists, skipping.<br>";
    }
}

$workshopCheck->close();
$workshopInsert->close();

// Seed a small plant catalogue used by the search page
$plants = [
    [
        'plant_name' => 'Monstera Deliciosa',
        'botanical_name' => 'Monstera deliciosa',
        'description' => 'A popular tropical indoor plant with striking split leaves.',
        'care_requirements' => 'Bright indirect light, moderate watering, and well-draining soil.',
        'price' => 24.99,
        'stock' => 18,
    ],
    [
        'plant_name' => 'Snake Plant',
        'botanical_name' => 'Dracaena trifasciata',
        'description' => 'Low-maintenance plant that tolerates low light and infrequent watering.',
        'care_requirements' => 'Low to bright indirect light, water when the soil is completely dry.',
        'price' => 14.50,
        'stock' => 32,
    ],
    [
        'plant_name' => 'Peace Lily',
        'botanical_name' => 'Spathiphyllum',
        'description' => 'An elegant flowering plant that also helps improve indoor air quality.',
        'care_requirements' => 'Moderate indirect light, keep soil lightly moist, avoid direct sun.',
        'price' => 19.75,
        'stock' => 21,
    ],
    [
        'plant_name' => 'Aloe Vera',
        'botanical_name' => 'Aloe vera',
        'description' => 'Succulent plant valued for its decorative leaves and practical uses.',
        'care_requirements' => 'Bright light, water sparingly, and use sandy well-draining soil.',
        'price' => 11.00,
        'stock' => 27,
    ],
];

$plantCheck = $mysqli->prepare('SELECT id FROM plants WHERE plant_name = ? LIMIT 1');
$plantInsert = $mysqli->prepare('INSERT INTO plants (plant_name, botanical_name, description, care_requirements, price, stock) VALUES (?, ?, ?, ?, ?, ?)');
if (!$plantCheck || !$plantInsert) {
    die('Plant catalogue setup failed: ' . $mysqli->error);
}

foreach ($plants as $plant) {
    $plantCheck->bind_param('s', $plant['plant_name']);
    $plantCheck->execute();
    $plantCheck->store_result();
    if ($plantCheck->num_rows === 0) {
        $plantInsert->bind_param(
            'ssssdi',
            $plant['plant_name'],
            $plant['botanical_name'],
            $plant['description'],
            $plant['care_requirements'],
            $plant['price'],
            $plant['stock']
        );
        if ($plantInsert->execute()) {
            echo "Inserted plant: {$plant['plant_name']}<br>";
        } else {
            echo "Failed to insert {$plant['plant_name']}: " . $plantInsert->error . '<br>';
        }
    } else {
        echo "Plant {$plant['plant_name']} already exists, skipping.<br>";
    }
}

$plantCheck->close();
$plantInsert->close();

// Insert initial users (passwords will be hashed)
$users = [
    ['username' => 'admin@ecosprout', 'password' => 'Admin@123', 'role' => 'Admin'],
    ['username' => 'manager@ecosprout', 'password' => 'Manager@123', 'role' => 'Manager'],
    ['username' => 'user@ecosprout', 'password' => 'User@123', 'role' => 'User']
];

$insertStmt = $mysqli->prepare('INSERT INTO users (username, password, role) VALUES (?, ?, ?)');
if (!$insertStmt) die('Prepare failed: ' . $mysqli->error);

foreach ($users as $u) {
    // Hash the password using PHP's password_hash
    $hashed = password_hash($u['password'], PASSWORD_DEFAULT);
    // Use INSERT IGNORE-like behavior by checking existence first
    $check = $mysqli->prepare('SELECT id FROM users WHERE username = ?');
    $check->bind_param('s', $u['username']);
    $check->execute();
    $check->store_result();
    if ($check->num_rows === 0) {
        $insertStmt->bind_param('sss', $u['username'], $hashed, $u['role']);
        if ($insertStmt->execute()) {
            echo "Inserted user: {$u['username']} ({$u['role']})<br>";
        } else {
            echo "Failed to insert {$u['username']}: " . $insertStmt->error . '<br>';
        }
    } else {
        echo "User {$u['username']} already exists, skipping.<br>";
    }
    $check->close();
}

$insertStmt->close();
$mysqli->close();

echo '<hr><strong>Initial credentials:</strong><br>';
echo 'Admin: admin@ecosprout / Admin@123<br>';
echo 'Manager: manager@ecosprout / Manager@123<br>';
echo 'User: user@ecosprout / User@123<br>';

echo '<p>After running this script (open in browser or run via CLI), remove or restrict access to setup.php to avoid accidental re-run.</p>';
?>