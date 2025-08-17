-- LookingGlass Database Schema
-- This file contains the database structure for the LookingGlass admin panel

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- Database: `lookingglass`

-- --------------------------------------------------------

--
-- Table structure for table `lg_admins`
--

CREATE TABLE `lg_admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_login` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lg_config`
--

CREATE TABLE `lg_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `config_key` varchar(100) NOT NULL,
  `config_value` text,
  `config_type` enum('string','integer','boolean','json') NOT NULL DEFAULT 'string',
  `description` text,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `config_key` (`config_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lg_themes`
--

CREATE TABLE `lg_themes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `theme_name` varchar(50) NOT NULL,
  `primary_color` varchar(7) NOT NULL,
  `secondary_color` varchar(7) NOT NULL,
  `background_color` varchar(7) NOT NULL,
  `text_color` varchar(7) NOT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `theme_name` (`theme_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lg_installation`
--

CREATE TABLE `lg_installation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `is_installed` tinyint(1) NOT NULL DEFAULT 0,
  `installation_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `version` varchar(20) NOT NULL DEFAULT '1.0.0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Default configuration data
--

INSERT INTO `lg_config` (`config_key`, `config_value`, `config_type`, `description`) VALUES
('LG_TITLE', 'NOXHosting | Looking Glass', 'string', 'HTML title of the looking glass'),
('LG_LOGO', '<h2>NOXHosting Looking Glass</h2>', 'string', 'Logo HTML content'),
('LG_LOGO_URL', 'https://noxhosting.io', 'string', 'URL where the logo points to'),
('LG_CSS_OVERRIDES', '0', 'boolean', 'Enable custom CSS file'),
('LG_CUSTOM_HEAD', '0', 'boolean', 'Enable custom head content'),
('LG_BLOCK_NETWORK', '1', 'boolean', 'Enable network information block'),
('LG_BLOCK_LOOKINGGLAS', '1', 'boolean', 'Enable looking glass block'),
('LG_BLOCK_SPEEDTEST', '1', 'boolean', 'Enable speedtest block'),
('LG_BLOCK_CUSTOM', '0', 'boolean', 'Enable custom block'),
('LG_LOCATION', 'Toronto,Canada', 'string', 'Network location'),
('LG_MAPS_QUERY', 'Toronto,Canada', 'string', 'Maps query location'),
('LG_FACILITY', 'Akamai Technologies, Inc', 'string', 'Facility name'),
('LG_FACILITY_URL', 'https://www.akamai.com', 'string', 'Facility URL'),
('LG_IPV4', '9.9.9.9', 'string', 'IPv4 for testing'),
('LG_IPV6', '::1', 'string', 'IPv6 for testing'),
('LG_METHODS', '["ping","ping6","mtr","mtr6","traceroute","traceroute6"]', 'json', 'Available testing methods'),
('LG_TERMS', '0', 'boolean', 'Enable terms of service'),
('LG_FOOTER', 'Powered by LookingGlass', 'string', 'Footer content'),
('LG_THEME', 'light', 'string', 'Current theme (light/dark)'),
('LG_LOCATIONS', '{"Denver,Colorado":"https://lg.denver.noxhosting.io","Los Angeles,California":"https://lg.la.noxhosting.io","San Jose,California":"https://lg.sanjose.noxhosting.io","New York City,NYC":"https://lg.nyc.noxhosting.io","Paris,France":"https://lg.paris.noxhosting.io"}', 'json', 'Other looking glass locations'),
('LG_SPEEDTEST_IPERF', '0', 'boolean', 'Enable iPerf info in speedtest block'),
('LG_SPEEDTEST_LABEL_INCOMING', 'iPerf3 Incoming', 'string', 'Label for incoming iPerf test'),
('LG_SPEEDTEST_CMD_INCOMING', 'iperf3 -4 -c hostname -p 5201 -P 4', 'string', 'Command for incoming speed test'),
('LG_SPEEDTEST_LABEL_OUTGOING', 'iPerf3 Outgoing', 'string', 'Label for outgoing iPerf test'),
('LG_SPEEDTEST_CMD_OUTGOING', 'iperf3 -4 -c hostname -p 5201 -P 4 -R', 'string', 'Command for outgoing speed test'),
('LG_AUTO_DETECT_IPV4', '1', 'boolean', 'Auto-detect server public IPv4 for display'),
('LG_AUTO_DETECT_LOCATION', '1', 'boolean', 'Auto-detect server location using ipinfo.io');

-- --------------------------------------------------------

--
-- Default theme data
--

INSERT INTO `lg_themes` (`theme_name`, `primary_color`, `secondary_color`, `background_color`, `text_color`, `is_default`, `is_active`) VALUES
('light', '#933bff', '#ffffff', '#ffffff', '#000000', 1, 1),
('dark', '#933bff', '#0d091c', '#0d091c', '#ffffff', 0, 1);

COMMIT;