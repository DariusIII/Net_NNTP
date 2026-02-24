<?php

declare(strict_types=1);

namespace DariusIII\NetNntp\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Verifies that the .env-based configuration system works correctly.
 */
final class ConfigTest extends TestCase
{
    public function testEnvExampleFileExists(): void
    {
        $this->assertFileExists(__DIR__ . '/../../.env.example');
    }

    public function testEnvExampleContainsAllKeys(): void
    {
        $content = file_get_contents(__DIR__ . '/../../.env.example');

        $expectedKeys = [
            'NNTP_HOST',
            'NNTP_PORT',
            'NNTP_ENCRYPTION',
            'NNTP_TIMEOUT',
            'NNTP_USER',
            'NNTP_PASS',
            'NNTP_WILDMAT',
            'NNTP_USE_RANGE',
            'NNTP_MAX_ARTICLES',
            'NNTP_LOG_LEVEL',
            'NNTP_ALLOW_OVERWRITE',
            'NNTP_ALLOW_PORT_OVERWRITE',
            'NNTP_VALIDATE_INPUT',
        ];

        foreach ($expectedKeys as $key) {
            $this->assertStringContainsString(
                $key,
                $content,
                ".env.example should contain key: $key"
            );
        }
    }

    public function testPhpdotenvPackageInstalled(): void
    {
        $this->assertTrue(
            class_exists(\Dotenv\Dotenv::class),
            'vlucas/phpdotenv should be installed'
        );
    }

    public function testConfigIncFileExists(): void
    {
        $this->assertFileExists(__DIR__ . '/../../docs/examples/demo/config.inc.php');
    }

    public function testConfigIncLoadsDotenv(): void
    {
        $content = file_get_contents(__DIR__ . '/../../docs/examples/demo/config.inc.php');

        $this->assertStringContainsString('Dotenv\Dotenv', $content);
        $this->assertStringContainsString('safeLoad', $content);
    }

    public function testConfigIncUsesEnvVars(): void
    {
        $content = file_get_contents(__DIR__ . '/../../docs/examples/demo/config.inc.php');

        $this->assertStringContainsString("\$_ENV['NNTP_HOST']", $content);
        $this->assertStringContainsString("\$_ENV['NNTP_PORT']", $content);
        $this->assertStringContainsString("\$_ENV['NNTP_LOG_LEVEL']", $content);
    }

    public function testGitignoreContainsDotenv(): void
    {
        $content = file_get_contents(__DIR__ . '/../../.gitignore');

        $this->assertStringContainsString('.env', $content);
    }
}

