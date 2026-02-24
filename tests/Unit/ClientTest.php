<?php

declare(strict_types=1);

namespace DariusIII\NetNntp\Tests\Unit;

use DariusIII\NetNntp\Client;
use DariusIII\NetNntp\Protocol\Client as ProtocolClient;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class ClientTest extends TestCase
{
    private Client $client;

    protected function setUp(): void
    {
        $this->client = new Client();
    }

    // ── Instantiation ──────────────────────────────────────────────

    public function testCanBeInstantiated(): void
    {
        $this->assertInstanceOf(Client::class, $this->client);
    }

    public function testExtendsProtocolClient(): void
    {
        $this->assertInstanceOf(ProtocolClient::class, $this->client);
    }

    // ── Logger ─────────────────────────────────────────────────────

    public function testSetLoggerAcceptsPsr3Logger(): void
    {
        $logger = new NullLogger();
        $this->client->setLogger($logger);

        $ref = new \ReflectionProperty(ProtocolClient::class, '_logger');
        $this->assertSame($logger, $ref->getValue($this->client));
    }

    public function testSetLoggerAcceptsAnyLoggerInterface(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $this->client->setLogger($logger);

        $ref = new \ReflectionProperty(ProtocolClient::class, '_logger');
        $this->assertInstanceOf(LoggerInterface::class, $ref->getValue($this->client));
    }

    public function testLoggerIsNullByDefault(): void
    {
        $ref = new \ReflectionProperty(ProtocolClient::class, '_logger');
        $this->assertNull($ref->getValue($this->client));
    }

    // ── Version strings ────────────────────────────────────────────

    public function testGetPackageVersionReturnsString(): void
    {
        $this->assertIsString($this->client->getPackageVersion());
    }

    public function testGetApiVersionReturnsString(): void
    {
        $this->assertIsString($this->client->getApiVersion());
    }

    // ── Group summary helpers (before selectGroup) ─────────────────

    public function testCountReturnsNullBeforeGroupSelected(): void
    {
        $this->assertNull($this->client->count());
    }

    public function testFirstReturnsNullBeforeGroupSelected(): void
    {
        $this->assertNull($this->client->first());
    }

    public function testLastReturnsNullBeforeGroupSelected(): void
    {
        $this->assertNull($this->client->last());
    }

    public function testGroupReturnsNullBeforeGroupSelected(): void
    {
        $this->assertNull($this->client->group());
    }

    // ── Removed deprecated methods should not exist ────────────────

    public function testDeprecatedMethodsRemoved(): void
    {
        $removedMethods = [
            'quit',
            'isConnected',
            'getArticleRaw',
            'getHeaderRaw',
            'getBodyRaw',
            'getNewNews',
            'getReferencesOverview',
        ];

        foreach ($removedMethods as $method) {
            $this->assertFalse(
                method_exists($this->client, $method),
                "Deprecated method '$method' should have been removed"
            );
        }
    }

    // ── Public API method signatures ───────────────────────────────

    public function testPublicApiMethodsExist(): void
    {
        $expectedMethods = [
            'connect',
            'disconnect',
            'authenticate',
            'selectGroup',
            'selectArticle',
            'selectNextArticle',
            'selectPreviousArticle',
            'getArticle',
            'getHeader',
            'getBody',
            'getGroups',
            'getDescriptions',
            'getOverview',
            'getOverviewFormat',
            'getHeaderField',
            'getGroupArticles',
            'getReferences',
            'getDate',
            'getNewGroups',
            'getNewArticles',
            'post',
            'mail',
            'setLogger',
        ];

        foreach ($expectedMethods as $method) {
            $this->assertTrue(
                method_exists($this->client, $method),
                "Public method '$method' should exist on Client"
            );
        }
    }

    public function testConnectMethodIsPublic(): void
    {
        $ref = new \ReflectionMethod(Client::class, 'connect');
        $this->assertTrue($ref->isPublic());
    }

    public function testSetLoggerMethodIsPublic(): void
    {
        $ref = new \ReflectionMethod(Client::class, 'setLogger');
        $this->assertTrue($ref->isPublic());
    }

    public function testSetLoggerParameterIsTypedLoggerInterface(): void
    {
        $ref = new \ReflectionMethod(ProtocolClient::class, 'setLogger');
        $params = $ref->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame(LoggerInterface::class, $params[0]->getType()->getName());
    }

    public function testGetArticleImplodeParameterIsBool(): void
    {
        $ref = new \ReflectionMethod(Client::class, 'getArticle');
        $params = $ref->getParameters();
        $this->assertSame('bool', $params[1]->getType()->getName());
    }

    public function testGetHeaderImplodeParameterIsBool(): void
    {
        $ref = new \ReflectionMethod(Client::class, 'getHeader');
        $params = $ref->getParameters();
        $this->assertSame('bool', $params[1]->getType()->getName());
    }

    public function testGetBodyImplodeParameterIsBool(): void
    {
        $ref = new \ReflectionMethod(Client::class, 'getBody');
        $params = $ref->getParameters();
        $this->assertSame('bool', $params[1]->getType()->getName());
    }

    public function testPostParameterIsStringOrArray(): void
    {
        $ref = new \ReflectionMethod(Client::class, 'post');
        $params = $ref->getParameters();
        $type = $params[0]->getType();
        $this->assertInstanceOf(\ReflectionUnionType::class, $type);
        $typeNames = array_map(fn($t) => $t->getName(), $type->getTypes());
        sort($typeNames);
        $this->assertSame(['array', 'string'], $typeNames);
    }
}
