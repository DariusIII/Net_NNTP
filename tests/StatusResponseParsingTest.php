<?php

declare(strict_types=1);

use DariusIII\NetNntp\Error;
use DariusIII\NetNntp\Protocol\Client as ProtocolClient;
use PHPUnit\Framework\TestCase;

final class StatusResponseParsingTest extends TestCase
{
    public function testSingleLineStatusResponseParsesCodeAndText(): void
    {
        $client = $this->makeClientWithBuffer("200 news.vipernews.com NNRP Service Ready - support@vipernews.com\r\n");

        $status = $client->readStatusForTesting();

        self::assertSame(200, $status);
        self::assertSame(
            [200, 'news.vipernews.com NNRP Service Ready - support@vipernews.com'],
            $client->currentStatusForTesting()
        );
    }

    public function testContinuationStatusResponseConsumesFinalStatusLine(): void
    {
        $client = $this->makeClientWithBuffer(
            "200-Welcome (n03.eu1)\r\n200 news.vipernews.com NNRP Service Ready - support@vipernews.com\r\n"
        );

        $status = $client->readStatusForTesting();

        self::assertSame(200, $status);
        self::assertSame(
            [200, 'news.vipernews.com NNRP Service Ready - support@vipernews.com'],
            $client->currentStatusForTesting()
        );

        $next = $client->readStatusForTesting();
        self::assertTrue(Error::isError($next));
    }

    public function testMalformedStatusLinePreservesFullText(): void
    {
        $client = $this->makeClientWithBuffer("Welcome (n03.eu1)\r\n");

        $status = $client->readStatusForTesting();

        self::assertSame(0, $status);
        self::assertSame([0, 'Welcome (n03.eu1)'], $client->currentStatusForTesting());

        $error = $client->unexpectedForTesting();
        self::assertSame("Unexpected response [0]: 'Welcome (n03.eu1)'", $error->getMessage());
    }

    private function makeClientWithBuffer(string $buffer): TestableProtocolClient
    {
        $stream = fopen('php://temp', 'r+');
        self::assertNotFalse($stream);

        fwrite($stream, $buffer);
        rewind($stream);

        $client = new TestableProtocolClient();
        $client->setSocketForTesting($stream);

        return $client;
    }
}

final class TestableProtocolClient extends ProtocolClient
{
    /**
     * @param resource $socket
     */
    public function setSocketForTesting($socket): void
    {
        $this->_socket = $socket;
    }

    public function readStatusForTesting(): mixed
    {
        return $this->_getStatusResponse();
    }

    /**
     * @return array{0:int,1:string}|null
     */
    public function currentStatusForTesting(): ?array
    {
        return $this->_currentStatusResponse;
    }

    public function unexpectedForTesting(): Error
    {
        return $this->_handleUnexpectedResponse();
    }
}
