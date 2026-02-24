# Net_NNTP

PHP client for the **Network News Transfer Protocol (NNTP)** / Usenet. Connect to news servers, list groups, fetch articles, post, and use extensions such as XOVER and AUTHINFO.

- **PHP 8.5+**
- **License:** [W3C Software Notice and License](LICENSE.md)

---

## Credits

- **Heino H. Gehlsen** — Original author and long-time maintainer (2002–2017).
  [pear.php.net/package/Net_NNTP](https://pear.php.net/package/Net_NNTP)
- **DariusIII** — Maintainer; PHP 8 compatibility and modernisation.
  [github.com/DariusIII/Net_NNTP](https://github.com/DariusIII/Net_NNTP)

---

## Installation

```bash
composer require dariusiii/net_nntp
```

The package uses **PSR-4** autoloading under the `DariusIII\NetNntp` namespace. Use Composer's `vendor/autoload.php` (or your app's bootstrap) so all classes are loaded on demand.

---

## Requirements

- PHP **8.5** or later
- Optional: OpenSSL for TLS/SSL connections

---

## Quick example

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use DariusIII\NetNntp\Client;
use DariusIII\NetNntp\Error;

$nntp = new Client();

// Connect (optionally with TLS and auth)
$ok = $nntp->connect('news.example.com', 'tls', 563, 15);
if (Error::isError($ok)) {
    die('Connect failed: ' . $ok->getMessage());
}

$nntp->authenticate('user', 'pass');

// List groups, select one, fetch overview
$groups = $nntp->getGroups();
$nntp->selectGroup('comp.lang.php');
$overview = $nntp->getOverview('1-10');

// Get an article
$article = $nntp->getArticle(12345, true);  // true = return as string

$nntp->disconnect();
```

See `docs/examples/` and the demo under `docs/examples/demo/` for more usage.

---

## Features

- RFC 977 / RFC 2980 oriented command set
- PSR-4 namespaced under `DariusIII\NetNntp`
- TLS/SSL and STARTTLS
- AUTHINFO user/pass authentication
- Group listing and selection, article retrieval (article/head/body)
- XOVER, XHDR, LIST OVERVIEW.FMT, LIST NEWSGROUPS
- Posting (POST, IHAVE-style transfer)
- Error handling via `DariusIII\NetNntp\Error` (no PEAR dependency)
- Response codes as PHP 8.5 backed enum `DariusIII\NetNntp\Protocol\ResponseCode`

---

## Tests

- **Unit/integration:** `tests/basics.php` (PHPUnit)

---

## Upgrading

See [UPGRADE.md](UPGRADE.md) for detailed migration instructions (class renames, namespace changes, constant replacements).

---

## Changelog and breaking changes

See [CHANGELOG.md](CHANGELOG.md). Key breaking changes in **v4.0**:

- All global `define()` response code constants replaced by the `DariusIII\NetNntp\Protocol\ResponseCode` enum
- PSR-4 namespaces — old `Net_NNTP_*` class names no longer exist
- All deprecated v1.0/v1.1 API methods removed (`quit()`, `isConnected()`, `getArticleRaw()`, `getHeaderRaw()`, `getBodyRaw()`, `getNewNews()`, `getReferencesOverview()`, `setDebug()`)
- Legacy int-based `$encryption` parameter in `connect()` no longer accepted
