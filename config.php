<?php declare(strict_types=1);
use Hybula\LookingGlass;

// Load database configuration manager
require_once __DIR__ . '/db_manager.php';

// Get configuration from database
try {
    $db = DatabaseManager::getInstance();
    
    // Define constants from database configuration
    if (!defined('LG_TITLE')) define('LG_TITLE', $db->getConfig('LG_TITLE', 'NOXHosting - Looking Glass'));
    if (!defined('LG_LOGO')) define('LG_LOGO', $db->getConfig('LG_LOGO', '<h2>NOXHosting Looking Glass</h2>'));
    if (!defined('LG_LOGO_URL')) define('LG_LOGO_URL', $db->getConfig('LG_LOGO_URL', 'https://noxhosting.io'));
    if (!defined('LG_CSS_OVERRIDES')) define('LG_CSS_OVERRIDES', $db->getConfig('LG_CSS_OVERRIDES', false));
    if (!defined('LG_CUSTOM_HEAD')) define('LG_CUSTOM_HEAD', $db->getConfig('LG_CUSTOM_HEAD', false));
    if (!defined('LG_BLOCK_NETWORK')) define('LG_BLOCK_NETWORK', $db->getConfig('LG_BLOCK_NETWORK', true));
    if (!defined('LG_BLOCK_LOOKINGGLAS')) define('LG_BLOCK_LOOKINGGLAS', $db->getConfig('LG_BLOCK_LOOKINGGLAS', true));
    if (!defined('LG_BLOCK_SPEEDTEST')) define('LG_BLOCK_SPEEDTEST', $db->getConfig('LG_BLOCK_SPEEDTEST', true));
    if (!defined('LG_BLOCK_CUSTOM')) define('LG_BLOCK_CUSTOM', $db->getConfig('LG_BLOCK_CUSTOM', false));
    if (!defined('LG_LOCATION')) define('LG_LOCATION', $db->getConfig('LG_LOCATION', 'Toronto,Canada'));
    if (!defined('LG_MAPS_QUERY')) define('LG_MAPS_QUERY', $db->getConfig('LG_MAPS_QUERY', 'Toronto,Canada'));
    if (!defined('LG_FACILITY')) define('LG_FACILITY', $db->getConfig('LG_FACILITY', 'Akamai Technologies, Inc'));
    if (!defined('LG_FACILITY_URL')) define('LG_FACILITY_URL', $db->getConfig('LG_FACILITY_URL', 'https://www.akamai.com'));
    if (!defined('LG_IPV4')) define('LG_IPV4', $db->getConfig('LG_IPV4', '127.0.0.1'));
    if (!defined('LG_IPV6')) define('LG_IPV6', $db->getConfig('LG_IPV6', '::1'));
    if (!defined('LG_TERMS')) define('LG_TERMS', $db->getConfig('LG_TERMS', false));
    if (!defined('LG_FOOTER')) define('LG_FOOTER', $db->getConfig('LG_FOOTER', 'Powered by LookingGlass'));
    if (!defined('LG_THEME')) define('LG_THEME', $db->getConfig('LG_THEME', 'light'));
    // Auto-detect flags
    if (!defined('LG_AUTO_DETECT_IPV4')) define('LG_AUTO_DETECT_IPV4', $db->getConfig('LG_AUTO_DETECT_IPV4', true));
    if (!defined('LG_AUTO_DETECT_LOCATION')) define('LG_AUTO_DETECT_LOCATION', $db->getConfig('LG_AUTO_DETECT_LOCATION', true));
    
    // Get methods from database (stored as JSON)
    if (!defined('LG_METHODS')) {
        $methods = $db->getConfig('LG_METHODS', [
            LookingGlass::METHOD_PING,
            LookingGlass::METHOD_PING6,
            LookingGlass::METHOD_MTR,
            LookingGlass::METHOD_MTR6,
            LookingGlass::METHOD_TRACEROUTE,
            LookingGlass::METHOD_TRACEROUTE6,
        ]);
        define('LG_METHODS', $methods);
    }
    
    // Get locations from database (stored as JSON)
    if (!defined('LG_LOCATIONS')) {
        $locations = $db->getConfig('LG_LOCATIONS', []);
        if (!is_array($locations)) {
            $locations = [];
        }
        define('LG_LOCATIONS', $locations);
    }
    
    // Get speedtest settings from database
    if (!defined('LG_SPEEDTEST_IPERF')) define('LG_SPEEDTEST_IPERF', $db->getConfig('LG_SPEEDTEST_IPERF', false));
    if (!defined('LG_SPEEDTEST_LABEL_INCOMING')) define('LG_SPEEDTEST_LABEL_INCOMING', $db->getConfig('LG_SPEEDTEST_LABEL_INCOMING', 'iPerf3 Incoming'));
    if (!defined('LG_SPEEDTEST_CMD_INCOMING')) define('LG_SPEEDTEST_CMD_INCOMING', $db->getConfig('LG_SPEEDTEST_CMD_INCOMING', 'iperf3 -4 -c hostname -p 5201 -P 4'));
    if (!defined('LG_SPEEDTEST_LABEL_OUTGOING')) define('LG_SPEEDTEST_LABEL_OUTGOING', $db->getConfig('LG_SPEEDTEST_LABEL_OUTGOING', 'iPerf3 Outgoing'));
    if (!defined('LG_SPEEDTEST_CMD_OUTGOING')) define('LG_SPEEDTEST_CMD_OUTGOING', $db->getConfig('LG_SPEEDTEST_CMD_OUTGOING', 'iperf3 -4 -c hostname -p 5201 -P 4 -R'));
    
    // Terms of Service URL
    if (!defined('LG_TOS_URL')) define('LG_TOS_URL', $db->getConfig('LG_TOS_URL', ''));

    // Define file paths
    if (!defined('LG_CUSTOM_HTML')) define('LG_CUSTOM_HTML', __DIR__.'/custom.html.php');
    if (!defined('LG_CUSTOM_PHP')) define('LG_CUSTOM_PHP', __DIR__.'/custom.post.php');
    
} catch (Exception $e) {
    // Fallback to default values if database is not available
    if (!defined('LG_TITLE')) define('LG_TITLE', 'NOXHosting - Looking Glass');
    if (!defined('LG_LOGO')) define('LG_LOGO', '<h2>NOXHosting Looking Glass</h2>');
    if (!defined('LG_LOGO_URL')) define('LG_LOGO_URL', 'https://noxhosting.io');
    if (!defined('LG_CSS_OVERRIDES')) define('LG_CSS_OVERRIDES', false);
    if (!defined('LG_CUSTOM_HEAD')) define('LG_CUSTOM_HEAD', false);
    if (!defined('LG_BLOCK_NETWORK')) define('LG_BLOCK_NETWORK', true);
    if (!defined('LG_BLOCK_LOOKINGGLAS')) define('LG_BLOCK_LOOKINGGLAS', true);
    if (!defined('LG_BLOCK_SPEEDTEST')) define('LG_BLOCK_SPEEDTEST', true);
    if (!defined('LG_BLOCK_CUSTOM')) define('LG_BLOCK_CUSTOM', false);
    if (!defined('LG_LOCATION')) define('LG_LOCATION', 'Toronto,Canada');
    if (!defined('LG_MAPS_QUERY')) define('LG_MAPS_QUERY', 'Toronto,Canada');
    if (!defined('LG_FACILITY')) define('LG_FACILITY', 'Akamai Technologies, Inc');
    if (!defined('LG_FACILITY_URL')) define('LG_FACILITY_URL', 'https://www.akamai.com');
    if (!defined('LG_IPV4')) define('LG_IPV4', '127.0.0.1');
    if (!defined('LG_IPV6')) define('LG_IPV6', '::1');
    if (!defined('LG_TERMS')) define('LG_TERMS', false);
    if (!defined('LG_FOOTER')) define('LG_FOOTER', 'Powered by LookingGlass');
    if (!defined('LG_THEME')) define('LG_THEME', 'light');
    // Auto-detect flags fallback
    if (!defined('LG_AUTO_DETECT_IPV4')) define('LG_AUTO_DETECT_IPV4', true);
    if (!defined('LG_AUTO_DETECT_LOCATION')) define('LG_AUTO_DETECT_LOCATION', true);
    if (!defined('LG_METHODS')) {
        define('LG_METHODS', [
            LookingGlass::METHOD_PING,
            LookingGlass::METHOD_PING6,
            LookingGlass::METHOD_MTR,
            LookingGlass::METHOD_MTR6,
            LookingGlass::METHOD_TRACEROUTE,
            LookingGlass::METHOD_TRACEROUTE6,
        ]);
    }
    
    // Define locations fallback
    if (!defined('LG_LOCATIONS')) {
        define('LG_LOCATIONS', []);
    }
    
    // Define speedtest settings fallback
    if (!defined('LG_SPEEDTEST_IPERF')) define('LG_SPEEDTEST_IPERF', false);
    if (!defined('LG_SPEEDTEST_LABEL_INCOMING')) define('LG_SPEEDTEST_LABEL_INCOMING', 'iPerf3 Incoming');
    if (!defined('LG_SPEEDTEST_CMD_INCOMING')) define('LG_SPEEDTEST_CMD_INCOMING', 'iperf3 -4 -c hostname -p 5201 -P 4');
    if (!defined('LG_SPEEDTEST_LABEL_OUTGOING')) define('LG_SPEEDTEST_LABEL_OUTGOING', 'iPerf3 Outgoing');
    if (!defined('LG_SPEEDTEST_CMD_OUTGOING')) define('LG_SPEEDTEST_CMD_OUTGOING', 'iperf3 -4 -c hostname -p 5201 -P 4 -R');
    
    // Terms of Service URL fallback
    if (!defined('LG_TOS_URL')) define('LG_TOS_URL', '');

    // Define file paths
    if (!defined('LG_CUSTOM_HTML')) define('LG_CUSTOM_HTML', __DIR__.'/custom.html.php');
    if (!defined('LG_CUSTOM_PHP')) define('LG_CUSTOM_PHP', __DIR__.'/custom.post.php');
}
// Define speedtest files with URLs to the actual files;
const LG_SPEEDTEST_FILES = [
    '100M' => '100MB.test',
    '1GB' => '1GB.test',
    '2GB' => '2GB.test',
];

// LG_TERMS is now loaded from database in the configuration section above
