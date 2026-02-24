<?php

/**
 * Demo configuration — loads settings from .env at project root.
 *
 * @category   Net
 * @package    Net_NNTP
 * @link       https://github.com/DariusIII/Net_NNTP
 */

require_once __DIR__ . '/../../../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../..');
$dotenv->safeLoad();

$frontpage = true;

// Connection
$host       = $_ENV['NNTP_HOST'] ?? 'news.php.net';
$port       = !empty($_ENV['NNTP_PORT']) ? (int) $_ENV['NNTP_PORT'] : null;
$timeout    = !empty($_ENV['NNTP_TIMEOUT']) ? (int) $_ENV['NNTP_TIMEOUT'] : null;
$encryption = !empty($_ENV['NNTP_ENCRYPTION']) ? $_ENV['NNTP_ENCRYPTION'] : null;

// Authentication
$user = !empty($_ENV['NNTP_USER']) ? $_ENV['NNTP_USER'] : null;
$pass = !empty($_ENV['NNTP_PASS']) ? $_ENV['NNTP_PASS'] : null;

// Demo defaults
$wildmat  = $_ENV['NNTP_WILDMAT'] ?? 'php.pear*';
$useRange = filter_var($_ENV['NNTP_USE_RANGE'] ?? false, FILTER_VALIDATE_BOOLEAN);
$max      = (int) ($_ENV['NNTP_MAX_ARTICLES'] ?? 10);

// Logging: 0=emergency … 7=debug
$loglevel = (int) ($_ENV['NNTP_LOG_LEVEL'] ?? 5);

// Allow URL query-string to override connection settings
$allowOverwrite     = filter_var($_ENV['NNTP_ALLOW_OVERWRITE'] ?? true, FILTER_VALIDATE_BOOLEAN);
$allowPortOverwrite = filter_var($_ENV['NNTP_ALLOW_PORT_OVERWRITE'] ?? false, FILTER_VALIDATE_BOOLEAN);

// Input validation
$validateInput           = filter_var($_ENV['NNTP_VALIDATE_INPUT'] ?? true, FILTER_VALIDATE_BOOLEAN);
$hostValidationRegExp    = '/^([^<>]+)$/';
$articleValidationRegExp = '/^([0-9]+|<[^<]+>)$/';
$groupValidationRegExp   = '/^([^<>]+)$/';

