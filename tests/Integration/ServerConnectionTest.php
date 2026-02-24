<?php

declare(strict_types=1);

namespace DariusIII\NetNntp\Tests\Integration;

use DariusIII\NetNntp\Client;
use DariusIII\NetNntp\Error;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * Integration tests that connect to a real NNTP server.
 *
 * Requires network access. Uses the public news.php.net server by default.
 * Override via environment variables:
 *   NNTP_HOST, NNTP_PORT, NNTP_ENCRYPTION, NNTP_USER, NNTP_PASS
 *
 * Run only integration tests:
 *   vendor/bin/phpunit --testsuite Integration
 */
final class ServerConnectionTest extends TestCase
{
    private static Client $nntp;
    private static string $host;
    private static ?int $port;
    private static mixed $encryption;
    private static ?string $user;
    private static ?string $pass;
    private static ?array $selectedGroup = null;

    public static function setUpBeforeClass(): void
    {
        self::$host       = $_ENV['NNTP_HOST'] ?? 'news.php.net';
        self::$port       = !empty($_ENV['NNTP_PORT']) ? (int) $_ENV['NNTP_PORT'] : null;
        self::$encryption = !empty($_ENV['NNTP_ENCRYPTION']) ? $_ENV['NNTP_ENCRYPTION'] : null;
        self::$user       = !empty($_ENV['NNTP_USER']) ? $_ENV['NNTP_USER'] : null;
        self::$pass       = !empty($_ENV['NNTP_PASS']) ? $_ENV['NNTP_PASS'] : null;

        self::$nntp = new Client();
        self::$nntp->setLogger(new NullLogger());
    }

    public static function tearDownAfterClass(): void
    {
        @self::$nntp->disconnect();
    }

    // ── Connection ─────────────────────────────────────────────────

    public function testConnectToServer(): void
    {
        $result = self::$nntp->connect(self::$host, self::$encryption, self::$port);

        $this->assertNotInstanceOf(Error::class, $result, 'Connection should succeed');
        $this->assertIsBool($result);
    }

    /**
     * @depends testConnectToServer
     */
    public function testAuthenticateIfCredentialsProvided(): void
    {
        if (self::$user === null || self::$pass === null) {
            $this->markTestSkipped('No NNTP_USER / NNTP_PASS provided');
        }

        $result = self::$nntp->authenticate(self::$user, self::$pass);
        $this->assertNotInstanceOf(Error::class, $result);
    }

    // ── Groups ─────────────────────────────────────────────────────

    /**
     * @depends testConnectToServer
     */
    public function testGetGroupsReturnsArray(): void
    {
        $groups = self::$nntp->getGroups();

        $this->assertNotInstanceOf(Error::class, $groups);
        $this->assertIsArray($groups);
        $this->assertNotEmpty($groups, 'Server should have at least one group');

        // Each group entry should have the expected keys
        $first = reset($groups);
        $this->assertArrayHasKey('group', $first);
        $this->assertArrayHasKey('first', $first);
        $this->assertArrayHasKey('last', $first);
    }

    /**
     * @depends testConnectToServer
     */
    public function testGetDescriptionsReturnsArray(): void
    {
        $descriptions = self::$nntp->getDescriptions('php.*');

        $this->assertNotInstanceOf(Error::class, $descriptions);
        $this->assertIsArray($descriptions);
        $this->assertNotEmpty($descriptions);

        // Each entry should be group-name => description-string
        $this->assertIsString(reset($descriptions));
        $this->assertIsString(key($descriptions));
    }

    // ── Group selection ────────────────────────────────────────────

    /**
     * @depends testGetGroupsReturnsArray
     */
    public function testSelectGroupReturnsSummary(): void
    {
        // Pick a known group on news.php.net
        $summary = self::$nntp->selectGroup('php.general');

        $this->assertNotInstanceOf(Error::class, $summary, 'selectGroup should succeed');
        $this->assertIsArray($summary);
        $this->assertArrayHasKey('group', $summary);
        $this->assertArrayHasKey('first', $summary);
        $this->assertArrayHasKey('last', $summary);
        $this->assertArrayHasKey('count', $summary);

        $this->assertSame('php.general', $summary['group']);
        $this->assertTrue(is_numeric($summary['first']));
        $this->assertTrue(is_numeric($summary['last']));

        self::$selectedGroup = $summary;
    }

    /**
     * @depends testSelectGroupReturnsSummary
     */
    public function testGroupAccessorsAfterSelect(): void
    {
        $this->assertSame('php.general', self::$nntp->group());
        $this->assertNotNull(self::$nntp->first());
        $this->assertNotNull(self::$nntp->last());
        $this->assertNotNull(self::$nntp->count());
    }

    // ── Article selection & retrieval ──────────────────────────────

    /**
     * @depends testSelectGroupReturnsSummary
     */
    public function testSelectFirstArticle(): void
    {
        $first = self::$nntp->first();
        $article = self::$nntp->selectArticle($first);

        $this->assertNotInstanceOf(Error::class, $article);
        $this->assertIsInt($article);
    }

    /**
     * @depends testSelectFirstArticle
     */
    public function testGetHeaderReturnsArray(): void
    {
        $header = self::$nntp->getHeader();

        $this->assertNotInstanceOf(Error::class, $header);
        $this->assertIsArray($header);
        $this->assertNotEmpty($header);
    }

    /**
     * @depends testSelectFirstArticle
     */
    public function testGetBodyReturnsArray(): void
    {
        $body = self::$nntp->getBody();

        $this->assertNotInstanceOf(Error::class, $body);
        $this->assertIsArray($body);
    }

    /**
     * @depends testSelectFirstArticle
     */
    public function testGetHeaderAsStringWhenImploded(): void
    {
        $header = self::$nntp->getHeader(null, true);

        $this->assertNotInstanceOf(Error::class, $header);
        $this->assertIsString($header);
        $this->assertNotEmpty($header);
    }

    /**
     * @depends testSelectGroupReturnsSummary
     */
    public function testSelectArticleByNumber(): void
    {
        $first = self::$nntp->first();
        $result = self::$nntp->selectArticle($first);

        $this->assertIsInt($result);
    }

    // ── Overview ───────────────────────────────────────────────────

    /**
     * @depends testSelectGroupReturnsSummary
     */
    public function testGetOverviewForCurrentArticle(): void
    {
        $first = self::$nntp->first();
        self::$nntp->selectArticle($first);

        $overview = self::$nntp->getOverview();

        if ($overview === false) {
            // Article may not exist, that's acceptable
            $this->assertFalse($overview);
        } else {
            $this->assertNotInstanceOf(Error::class, $overview);
            $this->assertIsArray($overview);
            $this->assertArrayHasKey('Subject', $overview);
            $this->assertArrayHasKey('From', $overview);
            $this->assertArrayHasKey('Date', $overview);
            $this->assertArrayHasKey('Message-ID', $overview);
        }
    }

    /**
     * @depends testSelectGroupReturnsSummary
     */
    public function testGetOverviewForRange(): void
    {
        $first = self::$nntp->first();
        $range = $first . '-' . $first;

        $overview = self::$nntp->getOverview($range);

        $this->assertNotInstanceOf(Error::class, $overview);
        $this->assertIsArray($overview);
    }

    // ── Disconnect ─────────────────────────────────────────────────

    /**
     * @depends testConnectToServer
     */
    public function testDisconnect(): void
    {
        $result = self::$nntp->disconnect();

        $this->assertNotInstanceOf(Error::class, $result);
    }

    /**
     * @depends testDisconnect
     */
    public function testConnectTimeoutToInvalidHost(): void
    {
        $client = new Client();
        $result = @$client->connect('non-existing-host.invalid', null, null, 2);

        $this->assertFalse($result);
    }
}

