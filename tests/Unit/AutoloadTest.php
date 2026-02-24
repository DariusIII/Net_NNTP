<?php

declare(strict_types=1);

namespace Net\NNTP\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Verifies PSR-4 autoloading, namespace structure, and composer.json wiring.
 */
final class AutoloadTest extends TestCase
{
    public function testClientClassAutoloads(): void
    {
        $this->assertTrue(class_exists(\Net\NNTP\Client::class));
    }

    public function testErrorClassAutoloads(): void
    {
        $this->assertTrue(class_exists(\Net\NNTP\Error::class));
    }

    public function testProtocolClientClassAutoloads(): void
    {
        $this->assertTrue(class_exists(\Net\NNTP\Protocol\Client::class));
    }

    public function testClientNamespace(): void
    {
        $ref = new \ReflectionClass(\Net\NNTP\Client::class);
        $this->assertSame('Net\\NNTP', $ref->getNamespaceName());
    }

    public function testErrorNamespace(): void
    {
        $ref = new \ReflectionClass(\Net\NNTP\Error::class);
        $this->assertSame('Net\\NNTP', $ref->getNamespaceName());
    }

    public function testProtocolClientNamespace(): void
    {
        $ref = new \ReflectionClass(\Net\NNTP\Protocol\Client::class);
        $this->assertSame('Net\\NNTP\\Protocol', $ref->getNamespaceName());
    }

    public function testClientExtendsProtocolClient(): void
    {
        $this->assertTrue(is_subclass_of(\Net\NNTP\Client::class, \Net\NNTP\Protocol\Client::class));
    }

    public function testResponsecodeConstantsLoadedViaFilesAutoload(): void
    {
        // These are loaded via composer "files" autoload, not via PSR-4
        $this->assertTrue(defined('NET_NNTP_PROTOCOL_RESPONSECODE_READY_POSTING_ALLOWED'));
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

