<?php declare(strict_types=1);
/**
 * Hybula Looking Glass
 *
 * Provides UI and input for the looking glass backend.
 *
 * @copyright 2022 Hybula B.V.
 * @license Mozilla Public License 2.0
 * @version 1.1.0
 * @since File available since release 1.1.0
 * @link https://github.com/hybula/lookingglass
 */
use Hybula\LookingGlass;

if (!file_exists(__DIR__ . '/config.php')) {
    die('config.php is not found, but is required for application to work!');
}

require __DIR__ . '/LookingGlass.php';
require __DIR__ . '/config.php';

LookingGlass::validateConfig();
LookingGlass::startSession();

function exitErrorMessage(string $message): void
{
    unset($_SESSION[LookingGlass::SESSION_CALL_BACKEND]);
    $_SESSION[LookingGlass::SESSION_ERROR_MESSAGE] = $message;
    exitNormal();
}

function exitNormal(): void
{
    header('Location: /');
    exit;
}

$templateData           = [
    'title'                    => LG_TITLE,
    'custom_css'               => LG_CSS_OVERRIDES,
    'custom_head'              => LG_CUSTOM_HEAD,
    'logo_url'                 => LG_LOGO_URL,
    'logo_data'                => LG_LOGO,
    //
    'block_network'            => LG_BLOCK_NETWORK,
    'block_lookingglas'        => LG_BLOCK_LOOKINGGLAS,
    'block_speedtest'          => LG_BLOCK_SPEEDTEST,
    'block_custom'             => LG_BLOCK_CUSTOM,
    'custom_html'              => '',
    //
    'locations'                => LG_LOCATIONS,
    'current_location'         => LG_LOCATION,
    'maps_query'               => LG_MAPS_QUERY,
    'facility'                 => LG_FACILITY,
    'facility_url'             => LG_FACILITY_URL,
    'ipv4'                     => LG_IPV4,
    'ipv6'                     => LG_IPV6,
    'methods'                  => LG_METHODS,
    'user_ip'                  => LookingGlass::detectIpAddress(),
    //
    'speedtest_iperf'          => LG_SPEEDTEST_IPERF,
    'speedtest_incoming_label' => LG_SPEEDTEST_LABEL_INCOMING,
    'speedtest_incoming_cmd'   => LG_SPEEDTEST_CMD_INCOMING,
    'speedtest_outgoing_label' => LG_SPEEDTEST_LABEL_OUTGOING,
    'speedtest_outgoing_cmd'   => LG_SPEEDTEST_CMD_OUTGOING,
    'speedtest_files'          => LG_SPEEDTEST_FILES,
    //
    'tos'                      => LG_TERMS,
    'tos_url'                  => LG_TOS_URL,
    'error_message'            => false,
];

// Dynamic detection of server public IPv4 and location
// These values override configured defaults when detection succeeds
(function() use (&$templateData) {
    $validatePublicIPv4 = function (string $ip): bool {
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return false;
        }
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return false;
        }
        return true;
    };

    $fetchJson = function (string $url, bool $ipv4Only = false): ?array {
        if (!function_exists('curl_init')) {
            return null;
        }
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        curl_setopt($ch, CURLOPT_USERAGENT, 'LookingGlass/1.0');
        if ($ipv4Only) {
            if (defined('CURL_IPRESOLVE_V4')) {
                curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
            }
        }
        $resp = curl_exec($ch);
        curl_close($ch);
        if (!is_string($resp) || $resp === '') {
            return null;
        }
        $data = json_decode($resp, true);
        return is_array($data) ? $data : null;
    };

    $fetchText = function (string $url, bool $ipv4Only = false): ?string {
        if (!function_exists('curl_init')) {
            return null;
        }
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        curl_setopt($ch, CURLOPT_USERAGENT, 'LookingGlass/1.0');
        if ($ipv4Only) {
            if (defined('CURL_IPRESOLVE_V4')) {
                curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
            }
        }
        $resp = curl_exec($ch);
        curl_close($ch);
        if (!is_string($resp) || $resp === '') {
            return null;
        }
        return trim($resp);
    };

    $detectServerIPv4 = function () use ($fetchJson, $fetchText, $validatePublicIPv4): ?string {
        $ip = null;
        $data = $fetchJson('https://api.ipify.org?format=json', true);
        if (is_array($data) && isset($data['ip']) && $validatePublicIPv4($data['ip'])) {
            $ip = $data['ip'];
        }
        if (!$ip) {
            $txt = $fetchText('https://ipv4.icanhazip.com', true);
            if (is_string($txt) && $validatePublicIPv4($txt)) {
                $ip = $txt;
            }
        }
        if (!$ip && isset($_SERVER['SERVER_ADDR']) && $validatePublicIPv4($_SERVER['SERVER_ADDR'])) {
            $ip = $_SERVER['SERVER_ADDR'];
        }
        if (!$ip) {
            $hostIp = gethostbyname(gethostname());
            if (is_string($hostIp) && $validatePublicIPv4($hostIp)) {
                $ip = $hostIp;
            }
        }
        return $ip ?: null;
    };

    $detectLocation = function (): array {
        $city = null; $region = null; $country = null; $loc = null; $org = null;
        if (function_exists('curl_init')) {
            $ch = curl_init('https://ipinfo.io/json');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
            curl_setopt($ch, CURLOPT_TIMEOUT, 3);
            curl_setopt($ch, CURLOPT_USERAGENT, 'LookingGlass/1.0');
            $resp = curl_exec($ch);
            curl_close($ch);
            if (is_string($resp) && $resp !== '') {
                $data = json_decode($resp, true);
                if (is_array($data)) {
                    $city = $data['city'] ?? null;
                    $region = $data['region'] ?? null;
                    $country = $data['country'] ?? null;
                    $loc = $data['loc'] ?? null; // "lat,lon"
                    $org = $data['org'] ?? null;
                }
            }
        }
        $parts = array_values(array_filter([$city, $region, $country], function ($v) { return is_string($v) && $v !== ''; }));
        $location = $parts ? implode(',', $parts) : null;
        $mapsQuery = $location ?: ($loc ?: null);
        return [
            'location' => $location,
            'maps_query' => $mapsQuery,
            'org' => $org,
        ];
    };

    if (defined('LG_AUTO_DETECT_IPV4') ? LG_AUTO_DETECT_IPV4 : true) {
        try {
            $publicIPv4 = $detectServerIPv4();
            if (is_string($publicIPv4) && $publicIPv4 !== '') {
                $templateData['ipv4'] = $publicIPv4;
            }
        } catch (\Throwable $e) {
            // keep configured default
        }
    }

    if (defined('LG_AUTO_DETECT_LOCATION') ? LG_AUTO_DETECT_LOCATION : true) {
        try {
            $loc = $detectLocation();
            if (!empty($loc['location'])) {
                $templateData['current_location'] = $loc['location'];
            }
            if (!empty($loc['maps_query'])) {
                $templateData['maps_query'] = $loc['maps_query'];
            }
        } catch (\Throwable $e) {
            // keep configured defaults
        }
    }
})();
