# Upgrade Guide

## Migrating to PSR-4 Namespaced Package

This release replaces the legacy PEAR-style class naming with PSR-4 namespaces under `DariusIII\NetNntp`. Source files have moved from `NNTP/` to `src/` and Composer autoloading has switched from `classmap` to `psr-4`.

### Requirements

- PHP >= 8.5
- Composer (for PSR-4 autoloading)

### Breaking Changes

#### 1. Class Renames

| Old Class                    | New Class                                  |
|------------------------------|--------------------------------------------|
| `Net_NNTP_Client`            | `DariusIII\NetNntp\Client`                 |
| `Net_NNTP_Protocol_Client`   | `DariusIII\NetNntp\Protocol\Client`        |
| `Net_NNTP_Error`             | `DariusIII\NetNntp\Error`                  |
| `ResponseCode` (global enum) | `DariusIII\NetNntp\Protocol\ResponseCode`  |

#### 2. File Locations

| Old Path                       | New Path                       |
|--------------------------------|--------------------------------|
| `NNTP/Client.php`              | `src/Client.php`               |
| `NNTP/Protocol/Client.php`     | `src/Protocol/Client.php`      |
| `NNTP/Error.php`               | `src/Error.php`                |
| `NNTP/Protocol/Responsecode.php` | `src/Protocol/ResponseCode.php` |

#### 3. Global Constants Removed

The following global constants have been moved to class constants on `DariusIII\NetNntp\Protocol\Client`:

| Old Global Constant                        | New Class Constant                                  |
|--------------------------------------------|-----------------------------------------------------|
| `PEAR_LOG_DEBUG` (conditionally defined)   | `DariusIII\NetNntp\Protocol\Client::LOG_DEBUG`      |
| `NET_NNTP_PROTOCOL_CLIENT_DEFAULT_HOST`    | `DariusIII\NetNntp\Protocol\Client::DEFAULT_HOST`   |
| `NET_NNTP_PROTOCOL_CLIENT_DEFAULT_PORT`    | `DariusIII\NetNntp\Protocol\Client::DEFAULT_PORT`   |

#### 4. Autoloading

Composer autoloading changed from `classmap` to `psr-4`. After updating, run:

```bash
composer dump-autoload
```

### Migration Steps

#### Step 1: Update `composer.json`

If you depend on this package, ensure your `composer.json` requires the latest version. Then run:

```bash
composer update dariusiii/net_nntp
composer dump-autoload
```

#### Step 2: Find and Replace Class References

Search your codebase for the old class names and replace them:

```php
// Before
use Net_NNTP_Client;
$client = new Net_NNTP_Client();
if (Net_NNTP_Error::isError($result)) { ... }

// After
use DariusIII\NetNntp\Client;
use DariusIII\NetNntp\Error;
$client = new Client();
if (Error::isError($result)) { ... }
```

Or use fully-qualified names if you prefer not to add `use` statements:

```php
$client = new \DariusIII\NetNntp\Client();
if (\DariusIII\NetNntp\Error::isError($result)) { ... }
```

#### Step 3: Update `ResponseCode` References

If you reference the `ResponseCode` enum directly, it is now namespaced:

```php
// Before
use ResponseCode;

// After
use DariusIII\NetNntp\Protocol\ResponseCode;
```

#### Step 4: Update Global Constant References

```php
// Before
$port = NET_NNTP_PROTOCOL_CLIENT_DEFAULT_PORT;
$level = PEAR_LOG_DEBUG;

// After
use DariusIII\NetNntp\Protocol\Client as ProtocolClient;
$port = ProtocolClient::DEFAULT_PORT;
$level = ProtocolClient::LOG_DEBUG;
```

#### Step 5: Update Subclass Extends

If you extend `Net_NNTP_Client` or `Net_NNTP_Protocol_Client`:

```php
// Before
class MyClient extends Net_NNTP_Client { ... }

// After
use DariusIII\NetNntp\Client;
class MyClient extends Client { ... }
```

#### Step 6: Remove Old require/include Statements

Any manual `require_once 'Net/NNTP/Client.php'` or similar includes can be removed. Composer's PSR-4 autoloader handles all class loading.

### Quick Reference: Search and Replace

Run these across your project to catch most references:

| Search For                                  | Replace With                                     |
|---------------------------------------------|--------------------------------------------------|
| `Net_NNTP_Client`                           | `\DariusIII\NetNntp\Client`                     |
| `Net_NNTP_Protocol_Client`                  | `\DariusIII\NetNntp\Protocol\Client`             |
| `Net_NNTP_Error`                            | `\DariusIII\NetNntp\Error`                       |
| `NET_NNTP_PROTOCOL_CLIENT_DEFAULT_HOST`     | `\DariusIII\NetNntp\Protocol\Client::DEFAULT_HOST` |
| `NET_NNTP_PROTOCOL_CLIENT_DEFAULT_PORT`     | `\DariusIII\NetNntp\Protocol\Client::DEFAULT_PORT` |
| `PEAR_LOG_DEBUG`                            | `\DariusIII\NetNntp\Protocol\Client::LOG_DEBUG`  |

#### 5. Deprecated Methods Removed

The following deprecated methods have been removed entirely:

| Removed Method              | Replacement                          |
|-----------------------------|--------------------------------------|
| `quit()`                    | `disconnect()`                       |
| `isConnected()`             | N/A (use internal `_isConnected()`)  |
| `getArticleRaw()`           | `getArticle()`                       |
| `getHeaderRaw()`            | `getHeader()`                        |
| `getBodyRaw()`              | `getBody()`                          |
| `getNewNews()`              | `getNewArticles()`                   |
| `getReferencesOverview()`   | `getReferences()`                    |
| `setDebug()`                | Automatic via logger                 |

The legacy `connect($host, $port_as_int)` calling convention (passing an integer as the second parameter) is also no longer supported. Use `connect($host, $encryption, $port, $timeout)` with a string encryption parameter (`'tls'`, `'ssl'`, or `null`).

### What Has NOT Changed

- The public API (method names, parameters, return types) is identical
- Error handling via `Error::isError()` works the same way
- The `ResponseCode` enum cases and values are unchanged
- Connection, authentication, and article retrieval all behave the same
