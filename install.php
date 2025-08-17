<?php declare(strict_types=1);
/**
 * LookingGlass Web Installer
 * 
 * Handles initial installation and database setup
 */

session_start();

// Check if already installed
if (file_exists(__DIR__ . '/db_config.php')) {
    require_once __DIR__ . '/db_config.php';
    try {
        $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
        $stmt = $pdo->query("SELECT is_installed FROM lg_installation WHERE id = 1");
        if ($stmt && $stmt->fetchColumn()) {
            header('Location: index.php');
            exit;
        }
    } catch (Exception $e) {
        // Continue with installation if database connection fails
    }
}

$step = $_GET['step'] ?? 1;
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($step == 1) {
        // Database configuration step
        $db_host = $_POST['db_host'] ?? '';
        $db_name = $_POST['db_name'] ?? '';
        $db_user = $_POST['db_user'] ?? '';
        $db_pass = $_POST['db_pass'] ?? '';
        
        if (empty($db_host) || empty($db_name) || empty($db_user)) {
            $error = 'Please fill in all required database fields.';
        } else {
            try {
                // Test database connection
                $pdo = new PDO("mysql:host=$db_host", $db_user, $db_pass);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                // Create database if it doesn't exist
                $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                $pdo->exec("USE `$db_name`");
                
                // Import SQL schema
                $sql = file_get_contents(__DIR__ . '/lookingglass.sql');
                
                // Remove comments and split by semicolon
                $sql = preg_replace('/--.*$/m', '', $sql); // Remove single-line comments
                $sql = preg_replace('/\/\*.*?\*\//s', '', $sql); // Remove multi-line comments
                $statements = explode(';', $sql);
                
                foreach ($statements as $statement) {
                    $statement = trim($statement);
                    if (!empty($statement)) {
                        try {
                            $pdo->exec($statement);
                        } catch (PDOException $e) {
                            // Skip errors for statements that might already exist
                            if (strpos($e->getMessage(), 'already exists') === false) {
                                throw $e;
                            }
                        }
                    }
                }
                
                // Save database configuration
                $config_content = "<?php\n";
                $config_content .= "\$db_host = '" . addslashes($db_host) . "';\n";
                $config_content .= "\$db_name = '" . addslashes($db_name) . "';\n";
                $config_content .= "\$db_user = '" . addslashes($db_user) . "';\n";
                $config_content .= "\$db_pass = '" . addslashes($db_pass) . "';\n";
                
                file_put_contents(__DIR__ . '/db_config.php', $config_content);
                
                $_SESSION['db_configured'] = true;
                header('Location: install.php?step=2');
                exit;
                
            } catch (Exception $e) {
                $error = 'Database connection failed: ' . $e->getMessage();
            }
        }
    } elseif ($step == 2) {
        // Admin account creation step
        if (!isset($_SESSION['db_configured'])) {
            header('Location: install.php?step=1');
            exit;
        }
        
        $admin_username = $_POST['admin_username'] ?? '';
        $admin_password = $_POST['admin_password'] ?? '';
        $admin_email = $_POST['admin_email'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (empty($admin_username) || empty($admin_password) || empty($admin_email)) {
            $error = 'Please fill in all fields.';
        } elseif ($admin_password !== $confirm_password) {
            $error = 'Passwords do not match.';
        } elseif (strlen($admin_password) < 6) {
            $error = 'Password must be at least 6 characters long.';
        } else {
            try {
                require_once __DIR__ . '/db_config.php';
                $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                // Create admin account
                $hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO lg_admins (username, password, email) VALUES (?, ?, ?)");
                $stmt->execute([$admin_username, $hashed_password, $admin_email]);
                
                // Mark as installed
                $stmt = $pdo->prepare("INSERT INTO lg_installation (is_installed) VALUES (1)");
                $stmt->execute();
                
                $success = 'Installation completed successfully! You can now login to the admin panel.';
                $_SESSION['installation_complete'] = true;
                
            } catch (Exception $e) {
                $error = 'Failed to create admin account: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LookingGlass Installation</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #933bff 0%, #0d091c 100%);
            margin: 0;
            padding: 20px;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container {
            background: white;
            border-radius: 10px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            max-width: 500px;
            width: 100%;
        }
        h1 {
            color: #933bff;
            text-align: center;
            margin-bottom: 30px;
        }
        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
        }
        .step {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #ddd;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 10px;
            font-weight: bold;
        }
        .step.active {
            background: #933bff;
            color: white;
        }
        .step.completed {
            background: #28a745;
            color: white;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #333;
        }
        input[type="text"], input[type="password"], input[type="email"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            box-sizing: border-box;
        }
        input[type="text"]:focus, input[type="password"]:focus, input[type="email"]:focus {
            border-color: #933bff;
            outline: none;
        }
        .btn {
            background: #933bff;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            width: 100%;
        }
        .btn:hover {
            background: #7a2fd9;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .success {
            background: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .complete {
            text-align: center;
        }
        .complete a {
            color: #933bff;
            text-decoration: none;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>LookingGlass Installation</h1>
        
        <div class="step-indicator">
            <div class="step <?= $step >= 1 ? 'active' : '' ?> <?= $step > 1 ? 'completed' : '' ?>">1</div>
            <div class="step <?= $step >= 2 ? 'active' : '' ?> <?= isset($_SESSION['installation_complete']) ? 'completed' : '' ?>">2</div>
        </div>
        
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['installation_complete'])): ?>
            <div class="complete">
                <h2>Installation Complete!</h2>
                <p>Your LookingGlass has been successfully installed.</p>
                <p><a href="admin.php">Go to Admin Panel</a> | <a href="index.php">View LookingGlass</a></p>
            </div>
        <?php elseif ($step == 1): ?>
            <h2>Step 1: Database Configuration</h2>
            <form method="POST">
                <div class="form-group">
                    <label for="db_host">Database Host:</label>
                    <input type="text" id="db_host" name="db_host" value="<?= htmlspecialchars($_POST['db_host'] ?? 'localhost') ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="db_name">Database Name:</label>
                    <input type="text" id="db_name" name="db_name" value="<?= htmlspecialchars($_POST['db_name'] ?? 'lookingglass') ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="db_user">Database Username:</label>
                    <input type="text" id="db_user" name="db_user" value="<?= htmlspecialchars($_POST['db_user'] ?? '') ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="db_pass">Database Password:</label>
                    <input type="password" id="db_pass" name="db_pass" value="<?= htmlspecialchars($_POST['db_pass'] ?? '') ?>">
                </div>
                
                <button type="submit" class="btn">Configure Database</button>
            </form>
        <?php elseif ($step == 2): ?>
            <h2>Step 2: Create Admin Account</h2>
            <form method="POST">
                <div class="form-group">
                    <label for="admin_username">Admin Username:</label>
                    <input type="text" id="admin_username" name="admin_username" value="<?= htmlspecialchars($_POST['admin_username'] ?? '') ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="admin_email">Admin Email:</label>
                    <input type="email" id="admin_email" name="admin_email" value="<?= htmlspecialchars($_POST['admin_email'] ?? '') ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="admin_password">Admin Password:</label>
                    <input type="password" id="admin_password" name="admin_password" required>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password:</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                
                <button type="submit" class="btn">Create Admin Account</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>