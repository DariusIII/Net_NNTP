<?php

declare(strict_types=1);

ini_set('memory_limit', '1024M');

require_once __DIR__ . '/../NNTP/Client.php';

/**
 * End-to-end NNTP benchmark helper.
 *
 * Usage example:
 * php tests/benchmark_integration.php \
 *   --host=your.news.server \
 *   --port=563 \
 *   --encryption=tls \
 *   --user=your-user \
 *   --pass=your-pass \
 *   --group=alt.binaries.example \
 *   --range=100000-100200 \
 *   --iterations=5
 *
 * Environment fallback:
 * NNTP_HOST, NNTP_PORT, NNTP_ENCRYPTION, NNTP_USER, NNTP_PASS, NNTP_GROUP, NNTP_RANGE, NNTP_ITERATIONS, NNTP_TIMEOUT
 */
final class NetNntpBenchmarkClient extends Net_NNTP_Client
{
    public function fetchXoverRaw(string $range): mixed
    {
        return $this->cmdXOver($range);
    }

    public function fetchXoverUsingLegacyReader(string $range): mixed
    {
        $response = $this->_sendCommand('XOVER ' . $range);
        if (Net_NNTP_Error::isError($response)) {
            return $response;
        }

        if ($response !== NET_NNTP_PROTOCOL_RESPONSECODE_OVERVIEW_FOLLOWS) {
            return $this->_handleUnexpectedResponse($response);
        }

        $data = $this->getTextResponseLegacy();
        if (Net_NNTP_Error::isError($data)) {
            return $data;
        }

        foreach ($data as $key => $value) {
            $data[$key] = explode("\t", $value);
        }

        return $data;
    }

    public function mapOverviewLegacy(array $overview, array $format): array
    {
        foreach ($overview as $key => $article) {
            $mappedArticle = $format;
            $index = 0;

            foreach ($mappedArticle as $tag => $full) {
                $mappedArticle[$tag] = $article[$index++] ?? '';

                if ($full === true) {
                    $mappedArticle[$tag] = ltrim(substr($mappedArticle[$tag], strpos($mappedArticle[$tag], ':') + 1), " \t");
                }
            }

            $overview[$key] = $mappedArticle;
        }

        return $overview;
    }

    public function mapOverviewOptimized(array $overview, array $format): array
    {
        $fieldNames = array_keys($format);
        $fieldFlags = array_values($format);
        $fieldCount = \count($fieldNames);

        foreach ($overview as $key => $article) {
            $mappedArticle = array();

            for ($i = 0; $i < $fieldCount; $i++) {
                $value = $article[$i] ?? '';

                if ($fieldFlags[$i] === true) {
                    $position = strpos($value, ':');
                    $value = ltrim(substr($value, ($position === false ? 0 : $position + 1)), " \t");
                }

                $mappedArticle[$fieldNames[$i]] = $value;
            }

            $overview[$key] = $mappedArticle;
        }

        return $overview;
    }

    protected function getTextResponseLegacy(): mixed
    {
        $data = array();
        $line = '';

        while (!feof($this->_socket)) {
            $received = @fgets($this->_socket, 1024);

            if ($received === false) {
                $meta = stream_get_meta_data($this->_socket);
                if (!empty($meta['timed_out'])) {
                    return $this->throwError('Connection timed out', null);
                }

                return $this->throwError('Failed to read line from socket.', null);
            }

            $line .= $received;

            if (substr($line, -2) != "\r\n" || \strlen($line) < 2) {
                usleep(25000);
                continue;
            }

            $line = substr($line, 0, -2);

            if ($line === '.') {
                return $data;
            }

            if (substr($line, 0, 2) == '..') {
                $line = substr($line, 1);
            }

            $data[] = $line;
            $line = '';
        }

        return $this->throwError('End of stream! Connection lost?', null);
    }
}

/**
 * @return array<string, mixed>
 */
function parseOptions(): array
{
    $opts = getopt('', [
        'host:',
        'port::',
        'encryption::',
        'user::',
        'pass::',
        'group:',
        'range:',
        'iterations::',
        'timeout::',
        'include-legacy-reader::',
        'help::',
    ]);

    $values = [
        'host' => $opts['host'] ?? getenv('NNTP_HOST') ?: null,
        'port' => (int) ($opts['port'] ?? getenv('NNTP_PORT') ?: 119),
        'encryption' => $opts['encryption'] ?? getenv('NNTP_ENCRYPTION') ?: null,
        'user' => $opts['user'] ?? getenv('NNTP_USER') ?: null,
        'pass' => $opts['pass'] ?? getenv('NNTP_PASS') ?: null,
        'group' => $opts['group'] ?? getenv('NNTP_GROUP') ?: null,
        'range' => $opts['range'] ?? getenv('NNTP_RANGE') ?: null,
        'iterations' => max(1, (int) ($opts['iterations'] ?? getenv('NNTP_ITERATIONS') ?: 3)),
        'timeout' => max(1, (int) ($opts['timeout'] ?? getenv('NNTP_TIMEOUT') ?: 15)),
        'includeLegacyReader' => \array_key_exists('include-legacy-reader', $opts),
        'help' => \array_key_exists('help', $opts),
    ];

    return $values;
}

function printUsage(): void
{
    $usage = <<<'TXT'
Usage:
  php tests/benchmark_integration.php --host=HOST --group=GROUP --range=RANGE [options]

Required:
  --host                 NNTP host (or NNTP_HOST env)
  --group                Newsgroup to select (or NNTP_GROUP env)
  --range                Article range for XOVER, e.g. 100000-100250 (or NNTP_RANGE env)

Options:
  --port=PORT            NNTP port (default: 119, or NNTP_PORT)
  --encryption=MODE      null|ssl|tls (default: null, or NNTP_ENCRYPTION)
  --user=USER            Username (or NNTP_USER)
  --pass=PASS            Password (or NNTP_PASS)
  --iterations=N         Iterations per benchmark (default: 3, or NNTP_ITERATIONS)
  --timeout=SECONDS      Connect timeout in seconds (default: 15, or NNTP_TIMEOUT)
  --include-legacy-reader Benchmark XOVER using the legacy text-reader implementation
  --help                 Show this message
TXT;

    echo $usage . PHP_EOL;
}

/**
 * @param array<int, float> $values
 */
function median(array $values): float
{
    sort($values);
    $count = \count($values);

    if ($count === 0) {
        return 0.0;
    }

    $mid = intdiv($count, 2);

    if (($count % 2) === 0) {
        return ($values[$mid - 1] + $values[$mid]) / 2;
    }

    return $values[$mid];
}

/**
 * @param callable():mixed $callback
 * @return array<string, mixed>
 */
function runBenchmark(string $name, int $iterations, callable $callback): array
{
    $times = array();
    $rows = null;

    for ($i = 0; $i < $iterations; $i++) {
        $start = hrtime(true);
        $result = $callback();
        $elapsedMs = (hrtime(true) - $start) / 1_000_000;
        $times[] = $elapsedMs;

        if (Net_NNTP_Error::isError($result)) {
            throw new RuntimeException($name . ' failed: ' . $result->getMessage() . ' [' . $result->getCode() . ']');
        }

        if (\is_array($result)) {
            $rows = \count($result);
        }
    }

    return [
        'benchmark' => $name,
        'iterations' => $iterations,
        'rows' => $rows,
        'median_ms' => median($times),
        'min_ms' => min($times),
        'max_ms' => max($times),
        'avg_ms' => array_sum($times) / \count($times),
    ];
}

function printResult(array $result): void
{
    printf(
        "%-34s rows=%-7s iter=%-3d median=%9.3fms min=%9.3fms max=%9.3fms avg=%9.3fms\n",
        (string) $result['benchmark'],
        (string) ($result['rows'] ?? '-'),
        (int) $result['iterations'],
        (float) $result['median_ms'],
        (float) $result['min_ms'],
        (float) $result['max_ms'],
        (float) $result['avg_ms']
    );
}

function percentDelta(float $baseline, float $value): float
{
    if ($baseline <= 0.0) {
        return 0.0;
    }

    return (($value - $baseline) / $baseline) * 100.0;
}

$options = parseOptions();

if ($options['help']) {
    printUsage();
    exit(0);
}

if (empty($options['host']) || empty($options['group']) || empty($options['range'])) {
    printUsage();
    fwrite(STDERR, "Error: --host, --group and --range are required.\n");
    exit(1);
}

$client = new NetNntpBenchmarkClient();

try {
    $connected = $client->connect(
        (string) $options['host'],
        $options['encryption'] !== '' ? $options['encryption'] : null,
        (int) $options['port'],
        (int) $options['timeout']
    );

    if (Net_NNTP_Error::isError($connected)) {
        throw new RuntimeException('Connect failed: ' . $connected->getMessage() . ' [' . $connected->getCode() . ']');
    }

    if (!empty($options['user'])) {
        $auth = $client->authenticate((string) $options['user'], (string) ($options['pass'] ?? ''));
        if (Net_NNTP_Error::isError($auth)) {
            throw new RuntimeException('Authenticate failed: ' . $auth->getMessage() . ' [' . $auth->getCode() . ']');
        }
    }

    $groupSummary = $client->selectGroup((string) $options['group']);
    if (Net_NNTP_Error::isError($groupSummary)) {
        throw new RuntimeException('Select group failed: ' . $groupSummary->getMessage() . ' [' . $groupSummary->getCode() . ']');
    }

    $format = $client->getOverviewFormat(true, true);
    if (Net_NNTP_Error::isError($format)) {
        throw new RuntimeException('Failed to fetch overview format: ' . $format->getMessage() . ' [' . $format->getCode() . ']');
    }
    $format = array_merge(array('Number' => false), $format);

    echo 'Connected to ' . $options['host'] . ':' . $options['port'] . ' group=' . $options['group'] . ' range=' . $options['range'] . PHP_EOL;
    echo str_repeat('-', 120) . PHP_EOL;

    $results = array();
    $iterations = (int) $options['iterations'];
    $range = (string) $options['range'];

    $results[] = runBenchmark(
        'getOverview() current',
        $iterations,
        fn (): mixed => $client->getOverview($range, true, true)
    );

    $results[] = runBenchmark(
        'XOVER raw',
        $iterations,
        fn (): mixed => $client->fetchXoverRaw($range)
    );

    $results[] = runBenchmark(
        'XOVER + legacy map',
        $iterations,
        function () use ($client, $format, $range): mixed {
            $overview = $client->fetchXoverRaw($range);
            if (Net_NNTP_Error::isError($overview)) {
                return $overview;
            }

            return $client->mapOverviewLegacy($overview, $format);
        }
    );

    $results[] = runBenchmark(
        'XOVER + optimized map',
        $iterations,
        function () use ($client, $format, $range): mixed {
            $overview = $client->fetchXoverRaw($range);
            if (Net_NNTP_Error::isError($overview)) {
                return $overview;
            }

            return $client->mapOverviewOptimized($overview, $format);
        }
    );

    if ($options['includeLegacyReader']) {
        $results[] = runBenchmark(
            'XOVER using legacy reader',
            $iterations,
            fn (): mixed => $client->fetchXoverUsingLegacyReader($range)
        );
    }

    foreach ($results as $result) {
        printResult($result);
    }

    $baseline = null;
    foreach ($results as $result) {
        if ($result['benchmark'] === 'getOverview() current') {
            $baseline = (float) $result['median_ms'];
            break;
        }
    }

    if ($baseline !== null) {
        echo str_repeat('-', 120) . PHP_EOL;
        echo "Delta vs getOverview() current (median):" . PHP_EOL;
        foreach ($results as $result) {
            $median = (float) $result['median_ms'];
            printf(
                "  %-34s %+8.2f%%\n",
                (string) $result['benchmark'],
                percentDelta($baseline, $median)
            );
        }
    }
} catch (Throwable $e) {
    fwrite(STDERR, 'Benchmark failed: ' . $e->getMessage() . PHP_EOL);
    exit(1);
} finally {
    $client->disconnect();
}
