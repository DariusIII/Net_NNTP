<?php

declare(strict_types=1);

namespace Net\NNTP\Tests\Unit;

use Net\NNTP\Error;
use Net\NNTP\Protocol\Client as ProtocolClient;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class ProtocolClientTest extends TestCase
{
    private ProtocolClient $protocol;

    protected function setUp(): void
    {
        // ProtocolClient has a protected constructor, so we need an anonymous subclass
        $this->protocol = new class extends ProtocolClient {
            // expose protected methods for testing
            public function publicThrowError(string $msg, ?int $code = null, mixed $info = null): Error
            {
                return $this->throwError($msg, $code, $info);
            }

            public function publicIsConnected(): bool
            {
                return $this->_isConnected();
            }

            public function publicHandleUnexpectedResponse(?int $code = null, ?string $text = null): mixed
            {
                // Set up the internal state so _handleUnexpectedResponse can read it
                $this->_currentStatusResponse = [$code ?? 0, $text ?? ''];
                return $this->_handleUnexpectedResponse($code, $text);
            }
        };
    }

    // ── Instantiation ──────────────────────────────────────────────

    public function testConstructorInitializesSocketToNull(): void
    {
        $ref = new \ReflectionProperty(ProtocolClient::class, '_socket');
        $this->assertNull($ref->getValue($this->protocol));
    }

    public function testEncryptionIsNullByDefault(): void
    {
        $ref = new \ReflectionProperty(ProtocolClient::class, '_encryption');
        $this->assertNull($ref->getValue($this->protocol));
    }

    // ── throwError ─────────────────────────────────────────────────

    public function testThrowErrorReturnsErrorObject(): void
    {
        $error = $this->protocol->publicThrowError('test error', 500, 'extra');

        $this->assertInstanceOf(Error::class, $error);
        $this->assertSame('test error', $error->getMessage());
        $this->assertSame(500, $error->getCode());
        $this->assertSame('extra', $error->getUserInfo());
    }

    public function testThrowErrorDefaultsCodeToNull(): void
    {
        $error = $this->protocol->publicThrowError('msg');

        $this->assertNull($error->getCode());
        $this->assertNull($error->getUserInfo());
    }

    // ── _isConnected ───────────────────────────────────────────────

    public function testIsConnectedReturnsFalseWithoutSocket(): void
    {
        $this->assertFalse($this->protocol->publicIsConnected());
    }

    // ── setLogger ──────────────────────────────────────────────────

    public function testSetLoggerStoresLogger(): void
    {
        $logger = new NullLogger();
        $this->protocol->setLogger($logger);

        $ref = new \ReflectionProperty(ProtocolClient::class, '_logger');
        $this->assertSame($logger, $ref->getValue($this->protocol));
    }

    // ── Version ────────────────────────────────────────────────────

    public function testGetPackageVersion(): void
    {
        $this->assertIsString($this->protocol->getPackageVersion());
    }

    public function testGetApiVersion(): void
    {
        $this->assertIsString($this->protocol->getApiVersion());
    }

    // ── _handleUnexpectedResponse ──────────────────────────────────

    public function testHandleUnexpectedResponseReturns502Error(): void
    {
        $result = $this->protocol->publicHandleUnexpectedResponse(502, 'Permission denied');

        $this->assertInstanceOf(Error::class, $result);
        $this->assertSame(502, $result->getCode());
        $this->assertStringContainsString('Permission denied', $result->getUserInfo());
    }

    public function testHandleUnexpectedResponseReturnsGenericError(): void
    {
        $result = $this->protocol->publicHandleUnexpectedResponse(999, 'Unknown');

        $this->assertInstanceOf(Error::class, $result);
        $this->assertSame(999, $result->getCode());
        $this->assertStringContainsString('Unexpected response', $result->getMessage());
    }

    // ── _clearOpensslErrors does not crash without encryption ─────

    public function testClearOpensslErrorsWithoutEncryption(): void
    {
        // Should simply return without error when _encryption is null
        $this->protocol->_clearOpensslErrors();
        $this->assertTrue(true); // no exception thrown
    }
}

