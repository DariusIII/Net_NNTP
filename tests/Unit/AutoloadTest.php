<?php

declare(strict_types=1);

namespace DariusIII\NetNntp\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Verifies PSR-4 autoloading, namespace structure, and composer.json wiring.
 */
final class AutoloadTest extends TestCase
{
    public function testClientClassAutoloads(): void
    {
        $this->assertTrue(class_exists(\DariusIII\NetNntp\Client::class));
    }

    public function testErrorClassAutoloads(): void
    {
        $this->assertTrue(class_exists(\DariusIII\NetNntp\Error::class));
    }

    public function testProtocolClientClassAutoloads(): void
    {
        $this->assertTrue(class_exists(\DariusIII\NetNntp\Protocol\Client::class));
    }

    public function testClientNamespace(): void
    {
        $ref = new \ReflectionClass(\DariusIII\NetNntp\Client::class);
        $this->assertSame('DariusIII\\NetNntp', $ref->getNamespaceName());
    }

    public function testErrorNamespace(): void
    {
        $ref = new \ReflectionClass(\DariusIII\NetNntp\Error::class);
        $this->assertSame('DariusIII\\NetNntp', $ref->getNamespaceName());
    }

    public function testProtocolClientNamespace(): void
    {
        $ref = new \ReflectionClass(\DariusIII\NetNntp\Protocol\Client::class);
        $this->assertSame('DariusIII\\NetNntp\\Protocol', $ref->getNamespaceName());
    }

    public function testClientExtendsProtocolClient(): void
    {
        $this->assertTrue(is_subclass_of(\DariusIII\NetNntp\Client::class, \DariusIII\NetNntp\Protocol\Client::class));
    }

    public function testResponseCodeEnumAutoloads(): void
    {
        $this->assertTrue(enum_exists(\DariusIII\NetNntp\Protocol\ResponseCode::class));
    }

    public function testResponseCodeEnumNamespace(): void
    {
        $ref = new \ReflectionEnum(\DariusIII\NetNntp\Protocol\ResponseCode::class);
        $this->assertSame('DariusIII\\NetNntp\\Protocol', $ref->getNamespaceName());
    }

    public function testLegacyResponsecodeShimRemoved(): void
    {
        $this->assertFalse(
            defined('NET_NNTP_PROTOCOL_RESPONSECODE_READY_POSTING_ALLOWED'),
            'Legacy global constants shim should no longer be loaded'
        );
    }

    public function testPsrLogInterfaceAvailable(): void
    {
        $this->assertTrue(interface_exists(\Psr\Log\LoggerInterface::class));
    }

    public function testNullLoggerAvailable(): void
    {
        $this->assertTrue(class_exists(\Psr\Log\NullLogger::class));
    }

    public function testAbstractLoggerAvailable(): void
    {
        $this->assertTrue(class_exists(\Psr\Log\AbstractLogger::class));
    }
}

