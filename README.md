# Net_NNTP

A PHP library for communicating with NNTP (Usenet) servers.

[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.5-8892BF.svg)](https://php.net)
[![PHPStan Level](https://img.shields.io/badge/phpstan-level%208-brightgreen.svg)](https://phpstan.org)
[![License](https://img.shields.io/badge/license-W3C-blue.svg)](LICENSE.md)

## Description

Net_NNTP provides a full-featured client for the NNTP protocol ([RFC 3977](https://tools.ietf.org/html/rfc3977)), allowing PHP applications to connect to Usenet/NNTP servers to read, post, and manage newsgroup articles.

The library is split into two layers:

- **`DariusIII\NetNntp\Protocol\Client`** — low-level protocol implementation that maps directly to NNTP commands (`GROUP`, `ARTICLE`, `HEAD`, `BODY`, `POST`, `XOVER`, etc.)
- **`DariusIII\NetNntp\Client`** — high-level API that provides a convenient interface for common operations (selecting groups, fetching articles, posting, overview, etc.)

### Features

- Full NNTP protocol support (RFC 3977 + common extensions)
- SSL/TLS encrypted connections
- STARTTLS negotiation
- Authentication (AUTHINFO USER/PASS)
- Article retrieval (full, header-only, body-only)
- Article posting via raw data or mail-style parameters
- Group listing with wildmat filtering
- Overview (XOVER) and header field (XHDR) support
- PSR-3 compatible logging — plug in any logger (Monolog, etc.)
- Strongly typed with `declare(strict_types=1)` throughout
- Int-backed `ResponseCode` enum with all 53 NNTP response codes
- PHPStan level 8 verified

## Requirements

- PHP 8.5 or later
- `psr/log` ^3.0
- `vlucas/phpdotenv` ^5.6 (for demo configuration)

## Installation

```bash
composer require dariusiii/net_nntp
```

## Quick Start

```php
use DariusIII\NetNntp\Client;
use DariusIII\NetNntp\Error;

$client = new Client();

// Connect
$result = $client->connect('news.example.com', 'ssl', 563);

// Authenticate (if required)
$client->authenticate('username', 'password');

// Select a group
$summary = $client->selectGroup('alt.test');
echo "Group: {$summary['group']}, Articles: {$summary['count']}\n";

// Fetch an article header
$header = $client->getHeader(null, implode: true);

// Fetch article body
$body = $client->getBody();

// Get overview for a range
$overview = $client->getOverview("{$summary['first']}-{$summary['last']}");

// Post an article
$client->post("Newsgroups: alt.test\r\nSubject: Test\r\nFrom: user@example.com\r\n\r\nHello, Usenet!");

// Disconnect
$client->disconnect();
```

## Error Handling

Methods return an `Error` object on failure rather than throwing exceptions. Check with `Error::isError()`:

```php
$result = $client->selectGroup('nonexistent.group');
if (Error::isError($result)) {
    echo "Error: " . $result->getMessage() . "\n";
}
```

## Logging

The library accepts any PSR-3 compatible logger:

```php
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

$logger = new Logger('nntp');
$logger->pushHandler(new StreamHandler('nntp.log'));

$client = new Client();
$client->setLogger($logger);
```

## Response Codes

All NNTP response codes are available as a strongly-typed int-backed enum:

```php
use DariusIII\NetNntp\Protocol\ResponseCode;

$code = ResponseCode::from(200);           // ResponseCode::ReadyPostingAllowed
echo $code->value;                         // 200
echo $code->description();                 // "Server ready, posting allowed"
echo ResponseCode::tryFrom(999);           // null (invalid code)
```

## Development

```bash
# Install dependencies
composer install

# Run static analysis (PHPStan level 8)
composer analyse

# Run tests
composer test

# Run both
composer check
```

## Project Structure

```
src/
├── Client.php                  # High-level NNTP client API
├── Error.php                   # Error class
└── Protocol/
    ├── Client.php              # Low-level NNTP protocol implementation
    └── ResponseCode.php        # Int-backed enum of all NNTP response codes
tests/
├── Unit/                       # Unit tests
│   ├── AutoloadTest.php
│   ├── ClientTest.php
│   ├── ConfigTest.php
│   ├── ErrorTest.php
│   ├── ProtocolClientTest.php
│   └── ResponsecodeTest.php
└── Integration/                # Live server integration tests
    └── ServerConnectionTest.php
docs/
└── examples/                   # Demo application and phpdoc examples
```

## ⚠️ Breaking Changes in v4.x

**If you are upgrading from v3.x or earlier, please read carefully.**

### v4.0.0

This was a complete modernization release. Key breaking changes:

- **Namespace change**: All classes moved from PEAR-style names (`Net_NNTP_Client`) to PSR-4 namespaces (`Net\NNTP\Client`)
- **PEAR removed entirely**: No more PEAR dependencies, `PEAR::isError()`, or `PEAR_Error`. Use `Error::isError()` instead.
- **PSR-3 logging only**: The old `setDebug()` / PEAR Log integration was removed. Use `setLogger()` with any PSR-3 logger.
- **PHP 8.5+ required**: Uses enums, `match` expressions, `readonly` properties, `str_starts_with()`, `declare(strict_types=1)`, and other modern PHP features.
- **Response code enum**: `NET_NNTP_PROTOCOL_RESPONSECODE_*` global constants were replaced by a backwards-compatible shim delegating to the `ResponseCode` enum.

### v4.1.0

This release completes the cleanup by removing all backward compatibility:

- **Namespace renamed again**: `Net\NNTP` → `DariusIII\NetNntp`
  - `Net\NNTP\Client` → `DariusIII\NetNntp\Client`
  - `Net\NNTP\Error` → `DariusIII\NetNntp\Error`
  - `Net\NNTP\Protocol\Client` → `DariusIII\NetNntp\Protocol\Client`
  - `Net\NNTP\Protocol\ResponseCode` → `DariusIII\NetNntp\Protocol\ResponseCode`
- **Legacy constants removed**: `NET_NNTP_PROTOCOL_RESPONSECODE_*` global constants no longer exist. Use the `ResponseCode` enum.
- **Deprecated methods removed** — these methods no longer exist:

  | Removed method          | Replacement          |
  |-------------------------|----------------------|
  | `quit()`                | `disconnect()`       |
  | `isConnected()`         | *(internal only)*    |
  | `getArticleRaw()`       | `getArticle()`       |
  | `getHeaderRaw()`        | `getHeader()`        |
  | `getBodyRaw()`          | `getBody()`          |
  | `getNewNews()`          | `getNewArticles()`   |
  | `getReferencesOverview()` | `getReferences()`  |

- **Method signatures tightened**:
  - `getArticle()`, `getHeader()`, `getBody()`: `$implode` parameter is now `bool` (was `mixed` for v1.1 class-name compat)
  - `post()`: parameter is now `string|array` (was `mixed`; use `mail()` for multi-arg posting)
  - `getNewGroups()`, `getNewArticles()`: `$time` parameter is now `int|string` (was `mixed`)
  - `connect()`: no longer accepts port as the second argument

## License

[W3C Software Notice and License](LICENSE.md)

## Credits

Originally created by [Heino H. Gehlsen](mailto:heino@gehlsen.dk). Modernized and maintained by [DariusIII](https://github.com/DariusIII).

