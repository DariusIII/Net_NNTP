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
- **CicerBro** _(Claude Opus 4.6)_ — Performance overhaul, PHP 8.5+ enum usage, and related improvements.

---

## Installation

```bash
composer require dariusiii/net_nntp
```

The package registers a **classmap** autoload; no manual `require` of NNTP files is needed. Use Composer’s `vendor/autoload.php` (or your app’s bootstrap) so `Net_NNTP_Client`, `Net_NNTP_Error`, and `ResponseCode` are loaded on demand.

---

## Requirements

- PHP **8.5** or later
- Optional: OpenSSL for TLS/SSL connections

---

## Quick example

```php
<?php

require __DIR__ . '/vendor/autoload.php';

$nntp = new Net_NNTP_Client();

// Connect (optionally with TLS and auth)
$ok = $nntp->connect('news.example.com', 'tls', 563, 15);
if (Net_NNTP_Error::isError($ok)) {
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
- TLS/SSL and STARTTLS
- AUTHINFO user/pass authentication
- Group listing and selection, article retrieval (article/head/body)
- XOVER, XHDR, LIST OVERVIEW.FMT, LIST NEWSGROUPS
- Posting (POST, IHAVE-style transfer)
- Error handling via `Net_NNTP_Error` (no PEAR dependency)
- Response codes as PHP 8.5 backed enum `ResponseCode`

---

## Tests and benchmarking

- **Unit/integration:** `tests/basics.php` (PHPUnit)
- **Benchmark:** `tests/benchmark_integration.php` — run against a real NNTP server (requires `composer install` first):
  ```bash
  composer install
  php tests/benchmark_integration.php --host=news.example.com --group=alt.binaries.example --range=1-500 --iterations=5
  ```
  Use `--help` for all options.

**Understanding the benchmark output**

- **getOverview() current** is the baseline (0%). It uses the full stack: XOVER fetch, cached overview format, and field mapping.
- **XOVER raw** = same optimized fetch, no field mapping.
- **XOVER + legacy map** = same fetch + mapping that copies the format per article and iterates with `foreach` over format keys.
- **XOVER + optimized map** = same fetch + mapping that precomputes field names/flags and uses an integer-index `for` loop (same as `getOverview()`). Same output as legacy map, fewer PHP ops.
- **XOVER using legacy reader** = same XOVER command but with an older line-by-line reader (no v2.2 optimizations).

Percentages are relative to the baseline. With only a few iterations, network and server variance is high—check **min** and **max**; if they differ a lot, medians can flip between runs. Use `--iterations=10` or run the script several times for a stabler comparison.

---

## Changelog and breaking changes

See [changelog.md](changelog.md). **v4.0** removes all global `define()` response code constants in favour of the `ResponseCode` enum; any code using `NET_NNTP_PROTOCOL_RESPONSECODE_*` must be updated to `ResponseCode::*->value`.
