<?php

declare(strict_types=1);

namespace DariusIII\NetNntp\Tests\Unit;

use DariusIII\NetNntp\Error;
use PHPUnit\Framework\TestCase;

final class ErrorTest extends TestCase
{
    public function testConstructorDefaults(): void
    {
        $error = new Error();

        $this->assertSame('', $error->getMessage());
        $this->assertNull($error->getCode());
        $this->assertNull($error->getUserInfo());
    }

    public function testConstructorWithArguments(): void
    {
        $error = new Error('Something went wrong', 42, ['detail' => 'extra']);

        $this->assertSame('Something went wrong', $error->getMessage());
        $this->assertSame(42, $error->getCode());
        $this->assertSame(['detail' => 'extra'], $error->getUserInfo());
    }

    public function testIsErrorReturnsTrueForErrorInstance(): void
    {
        $error = new Error('test');

        $this->assertTrue(Error::isError($error));
    }

    public function testIsErrorReturnsFalseForNonError(): void
    {
        $this->assertFalse(Error::isError(null));
        $this->assertFalse(Error::isError(false));
        $this->assertFalse(Error::isError(0));
        $this->assertFalse(Error::isError(''));
        $this->assertFalse(Error::isError([]));
        $this->assertFalse(Error::isError(new \stdClass()));
    }

    public function testToStringWithoutCode(): void
    {
        $error = new Error('Connection failed');

        $this->assertStringContainsString('Connection failed', (string) $error);
        $this->assertStringNotContainsString('code:', (string) $error);
    }

    public function testToStringWithCode(): void
    {
        $error = new Error('Server refused', 502);

        $this->assertStringContainsString('Server refused', (string) $error);
        $this->assertStringContainsString('502', (string) $error);
    }

    public function testUserInfoCanBeString(): void
    {
        $error = new Error('fail', 1, 'extra info');

        $this->assertSame('extra info', $error->getUserInfo());
    }
}

