<?php
/**
 * LookingGlass Admin Panel
 * Provides web interface for managing configuration settings
 */

require_once 'db_manager.php';

session_start();

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit;
}

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    try {
        $db = DatabaseManager::getInstance();
        if ($db->authenticateAdmin($username, $password)) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_username'] = $username;
            header('Location: admin.php');
            exit;
        } else {
            $login_error = 'Invalid username or password';
        }
    } catch (Exception $e) {
        $login_error = 'Database connection error: ' . $e->getMessage();
    }
}

// Handle configuration updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_config']) && isLoggedIn()) {
    try {
        $db = DatabaseManager::getInstance();
        
        // Update configuration values
        $config_fields = [
            'LG_TITLE', 'LG_LOGO', 'LG_LOGO_URL', 'LG_LOCATION', 'LG_MAPS_QUERY',
            'LG_FACILITY', 'LG_FACILITY_URL', 'LG_IPV4', 'LG_IPV6', 'LG_FOOTER',
            'LG_SPEEDTEST_LABEL_INCOMING', 'LG_SPEEDTEST_LABEL_OUTGOING',
            'LG_SPEEDTEST_CMD_INCOMING', 'LG_SPEEDTEST_CMD_OUTGOING'
        ];
        
        foreach ($config_fields as $field) {
            if (isset($_POST[$field])) {
                $db->setConfig($field, $_POST[$field]);
            }
        }
        
        // Handle locations (array of name=>url) as JSON config type
        $locations = [];
        if (isset($_POST['locations_name'], $_POST['locations_url']) && is_array($_POST['locations_name']) && is_array($_POST['locations_url'])) {
            $names = $_POST['locations_name'];
            $urls = $_POST['locations_url'];
            $count = min(count($names), count($urls));
            for ($i = 0; $i < $count; $i++) {
                $name = trim((string)$names[$i]);
                $url = trim((string)$urls[$i]);
                if ($name !== '' && $url !== '') {
                    $locations[$name] = $url;
                }
            }
        }
        $db->setConfig('LG_LOCATIONS', $locations, 'json');
        
        // Handle boolean fields
        $boolean_fields = [
            'LG_BLOCK_NETWORK', 'LG_BLOCK_LOOKINGGLAS', 'LG_BLOCK_SPEEDTEST', 'LG_BLOCK_CUSTOM',
            'LG_TERMS', 'LG_SPEEDTEST_IPERF',
            'LG_AUTO_DETECT_IPV4', 'LG_AUTO_DETECT_LOCATION'
        ];
        foreach ($boolean_fields as $field) {
            $db->setConfig($field, isset($_POST[$field]) ? 1 : 0, 'boolean');
        }
        
        // Handle Terms of Service URL
        if (isset($_POST['LG_TOS_URL'])) {
            $db->setConfig('LG_TOS_URL', trim((string)$_POST['LG_TOS_URL']));
        }
        
        $success_message = 'Configuration updated successfully!';
    } catch (Exception $e) {
        $error_message = 'Error updating configuration: ' . $e->getMessage();
    }
}

// Get current configuration if logged in
$config = [];
if (isLoggedIn()) {
    try {
        $db = DatabaseManager::getInstance();
        $config_fields = [
            'LG_TITLE', 'LG_LOGO', 'LG_LOGO_URL', 'LG_LOCATION', 'LG_MAPS_QUERY',
            'LG_FACILITY', 'LG_FACILITY_URL', 'LG_IPV4', 'LG_IPV6', 'LG_FOOTER',
            'LG_BLOCK_NETWORK', 'LG_BLOCK_LOOKINGGLAS', 'LG_BLOCK_SPEEDTEST', 'LG_BLOCK_CUSTOM',
            'LG_LOCATIONS', 'LG_TERMS', 'LG_TOS_URL', 'LG_SPEEDTEST_IPERF', 'LG_SPEEDTEST_LABEL_INCOMING',
            'LG_SPEEDTEST_LABEL_OUTGOING', 'LG_SPEEDTEST_CMD_INCOMING', 'LG_SPEEDTEST_CMD_OUTGOING',
            'LG_AUTO_DETECT_IPV4', 'LG_AUTO_DETECT_LOCATION'
        ];
        
        foreach ($config_fields as $field) {
            $default = $field === 'LG_LOCATIONS' ? [] : '';
            $config[$field] = $db->getConfig($field, $default);
        }
        if (!is_array($config['LG_LOCATIONS'])) {
            $config['LG_LOCATIONS'] = [];
        }
    } catch (Exception $e) {
        $error_message = 'Error loading configuration: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LookingGlass Admin Panel</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            width: 100%;
            max-width: 800px;
            margin: 20px;
        }
        
        .header {
            background: #933bff;
            color: white;
            padding: 20px;
            text-align: center;
        }
        
        .header h1 {
            margin-bottom: 5px;
        }
        
        .content {
            padding: 30px;
        }
        
        .login-form {
            max-width: 400px;
            margin: 0 auto;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
        }
        
        input[type="text"], input[type="password"], input[type="email"], textarea, select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e1e5e9;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        input[type="text"]:focus, input[type="password"]:focus, input[type="email"]:focus, textarea:focus, select:focus {
            outline: none;
            border-color: #933bff;
        }
        
        textarea {
            resize: vertical;
            min-height: 80px;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: auto;
            margin-right: 10px;
        }
        
        .btn {
            background: #933bff;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: background-color 0.3s;
        }
        
        .btn:hover {
            background: #7c2fd9;
        }
        
        .btn-logout {
            background: #dc3545;
            float: right;
        }
        
        .btn-logout:hover {
            background: #c82333;
        }
        
        .alert {
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .config-section {
            margin-bottom: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 6px;
        }
        
        .config-section h3 {
            margin-bottom: 15px;
            color: #333;
            border-bottom: 2px solid #933bff;
            padding-bottom: 5px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .container {
                margin: 10px;
            }
            
            .content {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>LookingGlass Admin Panel</h1>
            <p>Configuration Management System</p>
            <?php if (isLoggedIn()): ?>
                <a href="?logout=1" class="btn btn-logout">Logout</a>
            <?php endif; ?>
        </div>
        
        <div class="content">
            <?php if (!isLoggedIn()): ?>
                <!-- Login Form -->
                <div class="login-form">
                    <h2 style="text-align: center; margin-bottom: 20px; color: #333;">Admin Login</h2>
                    
                    <?php if (isset($login_error)): ?>
                        <div class="alert alert-error"><?php echo htmlspecialchars($login_error); ?></div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="form-group">
                            <label for="username">Username:</label>
                            <input type="text" id="username" name="username" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="password">Password:</label>
                            <input type="password" id="password" name="password" required>
                        </div>
                        
                        <button type="submit" name="login" class="btn" style="width: 100%;">Login</button>
                    </form>
                </div>
            <?php else: ?>
                <!-- Admin Panel -->
                <h2 style="margin-bottom: 20px; color: #333;">Welcome, <?php echo htmlspecialchars($_SESSION['admin_username']); ?>!</h2>
                
                <?php if (isset($success_message)): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
                <?php endif; ?>
                
                <?php if (isset($error_message)): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($error_message); ?></div>
                <?php endif; ?>
                
                <form method="POST">
                    <!-- Basic Settings -->
                    <div class="config-section">
                        <h3>Basic Settings</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="LG_TITLE">Site Title:</label>
                                <input type="text" id="LG_TITLE" name="LG_TITLE" value="<?php echo htmlspecialchars($config['LG_TITLE'] ?? ''); ?>">
                            </div>
                            
                        </div>
                        
                        <div class="form-group">
                            <label for="LG_LOGO">Logo HTML:</label>
                            <textarea id="LG_LOGO" name="LG_LOGO"><?php echo htmlspecialchars($config['LG_LOGO'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="LG_LOGO_URL">Logo URL:</label>
                            <input type="text" id="LG_LOGO_URL" name="LG_LOGO_URL" value="<?php echo htmlspecialchars($config['LG_LOGO_URL'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <!-- Location Settings -->
                    <div class="config-section">
                        <h3>Location Settings</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="LG_LOCATION">Location:</label>
                                <input type="text" id="LG_LOCATION" name="LG_LOCATION" value="<?php echo htmlspecialchars($config['LG_LOCATION'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label for="LG_MAPS_QUERY">Maps Query:</label>
                                <input type="text" id="LG_MAPS_QUERY" name="LG_MAPS_QUERY" value="<?php echo htmlspecialchars($config['LG_MAPS_QUERY'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="LG_FACILITY">Facility:</label>
                                <input type="text" id="LG_FACILITY" name="LG_FACILITY" value="<?php echo htmlspecialchars($config['LG_FACILITY'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label for="LG_FACILITY_URL">Facility URL:</label>
                                <input type="text" id="LG_FACILITY_URL" name="LG_FACILITY_URL" value="<?php echo htmlspecialchars($config['LG_FACILITY_URL'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Network Settings -->
                    <div class="config-section">
                        <h3>Network Settings</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="LG_IPV4">IPv4 Address:</label>
                                <input type="text" id="LG_IPV4" name="LG_IPV4" value="<?php echo htmlspecialchars($config['LG_IPV4'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label for="LG_IPV6">IPv6 Address:</label>
                                <input type="text" id="LG_IPV6" name="LG_IPV6" value="<?php echo htmlspecialchars($config['LG_IPV6'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Block Settings -->
                    <div class="config-section">
                        <h3>Block Visibility</h3>
                        <div class="form-row">
                            <div>
                                <div class="checkbox-group">
                                    <input type="checkbox" id="LG_BLOCK_NETWORK" name="LG_BLOCK_NETWORK" <?php echo ($config['LG_BLOCK_NETWORK'] ?? 0) ? 'checked' : ''; ?>>
                                    <label for="LG_BLOCK_NETWORK">Show Network Block</label>
                                </div>
                                <div class="checkbox-group">
                                    <input type="checkbox" id="LG_BLOCK_LOOKINGGLAS" name="LG_BLOCK_LOOKINGGLAS" <?php echo ($config['LG_BLOCK_LOOKINGGLAS'] ?? 0) ? 'checked' : ''; ?>>
                                    <label for="LG_BLOCK_LOOKINGGLAS">Show Looking Glass Block</label>
                                </div>
                            </div>
                            <div>
                                <div class="checkbox-group">
                                    <input type="checkbox" id="LG_BLOCK_SPEEDTEST" name="LG_BLOCK_SPEEDTEST" <?php echo ($config['LG_BLOCK_SPEEDTEST'] ?? 0) ? 'checked' : ''; ?>>
                                    <label for="LG_BLOCK_SPEEDTEST">Show Speed Test Block</label>
                                </div>
                                <div class="checkbox-group">
                                    <input type="checkbox" id="LG_BLOCK_CUSTOM" name="LG_BLOCK_CUSTOM" <?php echo ($config['LG_BLOCK_CUSTOM'] ?? 0) ? 'checked' : ''; ?>>
                                    <label for="LG_BLOCK_CUSTOM">Show Custom Block</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Looking Glass Locations -->
                    <div class="config-section">
                        <h3>Looking Glass Locations</h3>
                        <div id="locations-container">
                            <?php 
                            $locations = $config['LG_LOCATIONS'] ?? [];
                            if (!is_array($locations)) { $locations = []; }
                            if (empty($locations)) { $locations = ['' => '']; }
                            foreach ($locations as $name => $url): ?>
                            <div class="form-row" style="margin-bottom: 10px;">
                                <div class="form-group">
                                    <label>Location Name</label>
                                    <input type="text" name="locations_name[]" value="<?php echo htmlspecialchars($name); ?>" placeholder="City,Country">
                                </div>
                                <div class="form-group">
                                    <label>Location URL</label>
                                    <input type="text" name="locations_url[]" value="<?php echo htmlspecialchars($url); ?>" placeholder="https://lg.example.com">
                                </div>
                                <div class="form-group" style="align-self: end;">
                                    <button type="button" class="btn" onclick="removeLocationRow(this)" style="background:#dc3545">Remove</button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" class="btn" onclick="addLocationRow()">Add Location</button>
                        <small style="color: #666; font-size: 12px; display: block; margin-top: 10px;">Add pairs of Location Name and URL. They will be saved to the database.</small>
                    </div>
                    <script>
                        function addLocationRow() {
                            const container = document.getElementById('locations-container');
                            const row = document.createElement('div');
                            row.className = 'form-row';
                            row.style.marginBottom = '10px';
                            row.innerHTML = `
                                <div class="form-group">
                                    <label>Location Name</label>
                                    <input type="text" name="locations_name[]" value="" placeholder="City,Country">
                                </div>
                                <div class="form-group">
                                    <label>Location URL</label>
                                    <input type="text" name="locations_url[]" value="" placeholder="https://lg.example.com">
                                </div>
                                <div class="form-group" style="align-self: end;">
                                    <button type="button" class="btn" onclick="removeLocationRow(this)" style="background:#dc3545">Remove</button>
                                </div>
                            `;
                            container.appendChild(row);
                        }
                        function removeLocationRow(button) {
                            const row = button.closest('.form-row');
                            if (row && row.parentNode) {
                                row.parentNode.removeChild(row);
                            }
                        }
                    </script>
                    
                    <!-- Terms of Service -->
                    <div class="config-section">
                        <h3>Terms of Service</h3>
                        <div class="checkbox-group">
                            <input type="checkbox" id="LG_TERMS" name="LG_TERMS" <?php echo ($config['LG_TERMS'] ?? 0) ? 'checked' : ''; ?>>
                            <label for="LG_TERMS">Enable Terms of Service</label>
                        </div>
                        <div class="form-group">
                            <label for="LG_TOS_URL">Terms of Service URL:</label>
                            <input type="text" id="LG_TOS_URL" name="LG_TOS_URL" value="<?php echo htmlspecialchars($config['LG_TOS_URL'] ?? ''); ?>" placeholder="https://example.com/tos">
                        </div>
                    </div>
                    
                    <!-- Speed Test Settings -->
                    <div class="config-section">
                        <h3>Speed Test Settings</h3>
                        <div class="checkbox-group">
                            <input type="checkbox" id="LG_SPEEDTEST_IPERF" name="LG_SPEEDTEST_IPERF" <?php echo ($config['LG_SPEEDTEST_IPERF'] ?? 0) ? 'checked' : ''; ?>>
                            <label for="LG_SPEEDTEST_IPERF">Enable iPerf Speed Test</label>
                        </div>
                        <small style="color:#666;display:block;margin:-10px 0 15px 0;">When enabled, the labels and commands below are shown as examples for users. Replace <code>hostname</code> with your iPerf server host and adjust port if needed.</small>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="LG_SPEEDTEST_LABEL_INCOMING">Incoming Test Label:</label>
                                <input type="text" id="LG_SPEEDTEST_LABEL_INCOMING" name="LG_SPEEDTEST_LABEL_INCOMING" value="<?php echo htmlspecialchars($config['LG_SPEEDTEST_LABEL_INCOMING'] ?? 'iPerf3 Incoming'); ?>">
                            </div>
                            <div class="form-group">
                                <label for="LG_SPEEDTEST_LABEL_OUTGOING">Outgoing Test Label:</label>
                                <input type="text" id="LG_SPEEDTEST_LABEL_OUTGOING" name="LG_SPEEDTEST_LABEL_OUTGOING" value="<?php echo htmlspecialchars($config['LG_SPEEDTEST_LABEL_OUTGOING'] ?? 'iPerf3 Outgoing'); ?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="LG_SPEEDTEST_CMD_INCOMING">Incoming Test Command:</label>
                            <input type="text" id="LG_SPEEDTEST_CMD_INCOMING" name="LG_SPEEDTEST_CMD_INCOMING" value="<?php echo htmlspecialchars($config['LG_SPEEDTEST_CMD_INCOMING'] ?? 'iperf3 -4 -c hostname -p 5201 -P 4'); ?>">
                            <small style="color:#666;">Example: <code>iperf3 -4 -c lg.example.com -p 5201 -P 4</code> (client downloads from server)</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="LG_SPEEDTEST_CMD_OUTGOING">Outgoing Test Command:</label>
                            <input type="text" id="LG_SPEEDTEST_CMD_OUTGOING" name="LG_SPEEDTEST_CMD_OUTGOING" value="<?php echo htmlspecialchars($config['LG_SPEEDTEST_CMD_OUTGOING'] ?? 'iperf3 -4 -c hostname -p 5201 -P 4 -R'); ?>">
                            <small style="color:#666;">Example: <code>iperf3 -4 -c lg.example.com -p 5201 -P 4 -R</code> (client uploads to server)</small>
                        </div>
                    </div>
                    
                    <!-- Footer Settings -->
                    <div class="config-section">
                        <h3>Footer Settings</h3>
                        <div class="form-group">
                            <label for="LG_FOOTER">Footer Text:</label>
                            <textarea id="LG_FOOTER" name="LG_FOOTER"><?php echo htmlspecialchars($config['LG_FOOTER'] ?? ''); ?></textarea>
                        </div>
                    </div>
                    
                    <button type="submit" name="update_config" class="btn">Update Configuration</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>