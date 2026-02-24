<?php

declare(strict_types=1);

/**
 * Low level NNTP Protocol Client
 *
 * PHP versions 8.5 and above
 *
 * @category   Net
 * @package    Net_NNTP
 * @author     Heino H. Gehlsen <heino@gehlsen.dk>
 * @copyright  2002-2017 Heino H. Gehlsen <heino@gehlsen.dk>. All Rights Reserved.
 * @license    http://www.w3.org/Consortium/Legal/2002/copyright-software-20021231 W3C® SOFTWARE NOTICE AND LICENSE
 * @link       https://github.com/DariusIII/Net_NNTP
 */

namespace DariusIII\NetNntp\Protocol;

use DariusIII\NetNntp\Error;
use Psr\Log\LoggerInterface;

/**
 * Low level NNTP Client
 *
 * Implements the client part of the NNTP standard according to
 * RFC 977, RFC 2980, RFC 850/1036, and RFC 822/2822.
 *
 * Each NNTP command is represented by a method: cmd*()
 */
class Client
{
    /** @var resource|null */
    protected mixed $_socket = null;

    /** @var array{0: int, 1: string}|null */
    protected ?array $_currentStatusResponse = null;

    protected ?LoggerInterface $_logger = null;
    protected ?string $_encryption = null;

    public function __construct()
    {
        $this->_socket = null;
    }

    protected function throwError(string $message, ?int $code = null, mixed $userInfo = null): Error
    {
        return new Error($message, $code, $userInfo);
    }

    public function getPackageVersion(): string
    {
        return '@package_version@';
    }

    public function getApiVersion(): string
    {
        return '@api_version@';
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->_logger = $logger;
    }

    public function _clearOpensslErrors(): void
    {
        if ($this->_encryption === null) {
            return;
        }

        while (($message = openssl_error_string()) !== false) {
            $this->_logger?->debug('OpenSSL: ' . $message);
        }
    }

    protected function _sendCommand(string $cmd): mixed
    {
        if (\strlen($cmd) > 510) {
            return $this->throwError('Failed writing to socket! (Command too long - max 510 chars)');
        }

        if (strpbrk($cmd, "\r\n") !== false) {
            $this->_logger?->debug('Illegal character in command: contains carriage return/new line');
            return $this->throwError('Illegal character(s) in NNTP command!');
        }

        if (!$this->_isConnected()) {
            return $this->throwError('Failed to write to socket! (connection lost!)');
        }

        $written = @fwrite($this->_socket, $cmd . "\r\n");
        if ($written === false) {
            return $this->throwError('Failed to write to socket!');
        }

        $this->_logger?->debug('C: ' . $cmd);

        return $this->_getStatusResponse();
    }

    protected function _getStatusResponse(): mixed
    {
        $this->_clearOpensslErrors();
        $response = @fgets($this->_socket, 4096);
        $this->_clearOpensslErrors();

        if ($response === false) {
            $meta = stream_get_meta_data($this->_socket);
            if ($meta['timed_out']) {
                return $this->throwError('Connection timed out', null);
            }
            return $this->throwError('Failed to read from socket...!', null);
        }

        $this->_logger?->debug('S: ' . rtrim($response, "\r\n"));

        $response = ltrim($response);

        $this->_currentStatusResponse = [
            (int) substr($response, 0, 3),
            (string) rtrim(substr($response, 4)),
        ];

        return $this->_currentStatusResponse[0];
    }

    protected function _getTextResponse(): mixed
    {
        $data = [];
        $line = '';

        while (!feof($this->_socket)) {
            $this->_clearOpensslErrors();
            $received = @fgets($this->_socket, 8192);
            $this->_clearOpensslErrors();

            if ($received === false) {
                $meta = stream_get_meta_data($this->_socket);
                if ($meta['timed_out']) {
                    return $this->throwError('Connection timed out', null);
                }
                return $this->throwError('Failed to read line from socket.', null);
            }

            $line .= $received;

            if (!str_ends_with($line, "\r\n") || \strlen($line) < 2) {
                continue;
            }

            $line = substr($line, 0, -2);

            if ($line === '.') {
                $this->_logger?->debug('T: .');
                return $data;
            }

            // If 1st char is '.' it's doubled (NNTP/RFC977 2.4.1)
            if (str_starts_with($line, '..')) {
                $line = substr($line, 1);
            }

            $this->_logger?->debug('T: ' . $line);

            $data[] = $line;
            $line = '';
        }

        $this->_logger?->warning('Broke out of reception loop! This shouldn\'t happen unless connection has been lost?');

        return $this->throwError('End of stream! Connection lost?', null);
    }

    /** @param string|array<int, string> $article */
    protected function _sendArticle(string|array $article): void
    {
        if (\is_string($article)) {
            @fwrite($this->_socket, preg_replace("|\n\.|", "\n..", $article));
            @fwrite($this->_socket, "\r\n.\r\n");

            if ($this->_logger) {
                foreach (explode("\r\n", $article) as $l) {
                    $this->_logger->debug('D: ' . $l);
                }
                $this->_logger->debug('D: .');
            }
            return;
        }

        // Array: [header, body]
        $header = (string) reset($article);
        $body = (string) next($article);

        @fwrite($this->_socket, preg_replace("|\n\.|", "\n..", $header));
        @fwrite($this->_socket, "\r\n");

        if ($this->_logger) {
            foreach (explode("\r\n", $header) as $l) {
                $this->_logger->debug('D: ' . $l);
            }
        }

        @fwrite($this->_socket, preg_replace("|\n\.|", "\n..", $body));
        @fwrite($this->_socket, "\r\n.\r\n");

        if ($this->_logger) {
            foreach (explode("\r\n", $body) as $l) {
                $this->_logger->debug('D: ' . $l);
            }
            $this->_logger->debug('D: .');
        }
    }

    protected function _currentStatusResponse(): string
    {
        return $this->_currentStatusResponse[1];
    }

    protected function _handleUnexpectedResponse(?int $code = null, ?string $text = null): Error
    {
        $code ??= $this->_currentStatusResponse[0];
        $text ??= $this->_currentStatusResponse();

        if ($code === ResponseCode::NotPermitted->value) {
            return $this->throwError('Command not permitted / Access restriction / Permission denied', $code, $text);
        }

        return $this->throwError("Unexpected response: '$text'", $code, $text);
    }

    /* Session administration commands */

    protected function connect(?string $host = null, mixed $encryption = null, ?int $port = null, ?int $timeout = null): mixed
    {
        if ($this->_isConnected()) {
            return $this->throwError('Already connected, disconnect first!', null);
        }


        $host ??= 'localhost';

        // Choose transport based on encryption
        [$transport, $port] = match ($encryption) {
            null, false  => ['tcp', $port ?? 119],
            'ssl', 'tls' => [$encryption, $port ?? 563],
            default      => throw new \InvalidArgumentException('$encryption parameter must be either tcp, tls or ssl.'),
        };

        if ($encryption === 'ssl' || $encryption === 'tls') {
            $this->_encryption = $encryption;
        }

        $timeout ??= 15;

        $R = @stream_socket_client("$transport://$host:$port", $errno, $errstr, $timeout);
        if ($R === false) {
            $this->_logger?->notice("Connection to $transport://$host:$port failed.");
            return $R;
        }

        $this->_socket = $R;
        $this->_logger?->info("Connection to $transport://$host:$port has been established.");

        stream_set_timeout($this->_socket, $timeout);

        $response = $this->_getStatusResponse();
        if (Error::isError($response)) {
            return $response;
        }

        return match ($response) {
            ResponseCode::ReadyPostingAllowed->value    => true,
            ResponseCode::ReadyPostingProhibited->value => $this->_logAndReturn(false, 'Posting not allowed!'),
            ResponseCode::DisconnectingForced->value,
            ResponseCode::NotPermitted->value           => $this->throwError('Server refused connection', $response, $this->_currentStatusResponse()),
            default                                     => $this->_handleUnexpectedResponse($response),
        };
    }

    /**
     * Helper: log an info message and return a value.
     */
    private function _logAndReturn(mixed $value, string $message): mixed
    {
        $this->_logger?->info($message);
        return $value;
    }

    protected function disconnect(): mixed
    {
        return $this->cmdQuit();
    }

    protected function cmdCapabilities(): mixed
    {
        $response = $this->_sendCommand('CAPABILITIES');
        if (Error::isError($response)) {
            return $response;
        }

        if ($response === ResponseCode::CapabilitiesFollow->value) {
            $data = $this->_getTextResponse();
            return Error::isError($data) ? $data : $data;
        }

        return $this->_handleUnexpectedResponse($response);
    }

    protected function cmdModeReader(): mixed
    {
        $response = $this->_sendCommand('MODE READER');
        if (Error::isError($response)) {
            return $response;
        }

        return match ($response) {
            ResponseCode::ReadyPostingAllowed->value    => true,
            ResponseCode::ReadyPostingProhibited->value => $this->_logAndReturn(false, 'Posting not allowed!'),
            ResponseCode::NotPermitted->value           => $this->throwError('Connection being closed, since service so permanently unavailable', $response, $this->_currentStatusResponse()),
            default                                     => $this->_handleUnexpectedResponse($response),
        };
    }

    protected function cmdQuit(): mixed
    {
        $response = $this->_sendCommand('QUIT');
        if (Error::isError($response)) {
            return $response;
        }

        if ($response === ResponseCode::DisconnectingRequested->value) {
            if ($this->_isConnected()) {
                fclose($this->_socket);
            }
            $this->_logger?->info('Connection closed.');
            return true;
        }

        return $this->_handleUnexpectedResponse($response);
    }

    protected function cmdStartTLS(): mixed
    {
        $response = $this->_sendCommand('STARTTLS');
        if (Error::isError($response)) {
            return $response;
        }

        return match ($response) {
            ResponseCode::TlsContinue->value => $this->_handleTlsNegotiation($response),
            ResponseCode::TlsRefused->value  => $this->throwError('Can not initiate TLS negotiation', $response, $this->_currentStatusResponse()),
            default                          => $this->_handleUnexpectedResponse($response),
        };
    }

    private function _handleTlsNegotiation(int $response): mixed
    {
        $encrypted = stream_socket_enable_crypto($this->_socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);

        if ($encrypted === true) {
            $this->_logger?->info('TLS encryption started.');
            return true;
        }

        if ($encrypted === false) {
            $this->_logger?->info('TLS encryption failed.');
            return $this->throwError('Could not initiate TLS negotiation', $response, $this->_currentStatusResponse());
        }

        return $this->throwError('Internal error - unknown response from stream_socket_enable_crypto()', $response, $this->_currentStatusResponse());
    }

    /* Article posting and retrieval — Group and article selection */

    protected function cmdGroup(string $newsgroup): mixed
    {
        $response = $this->_sendCommand('GROUP ' . $newsgroup);
        if (Error::isError($response)) {
            return $response;
        }

        return match ($response) {
            ResponseCode::GroupSelected->value => $this->_parseGroupSelected(),
            ResponseCode::NoSuchGroup->value   => $this->throwError('No such news group', $response, $this->_currentStatusResponse()),
            default                            => $this->_handleUnexpectedResponse($response),
        };
    }

    /** @return array{group: string, first: string, last: string, count: string} */
    private function _parseGroupSelected(): array
    {
        $parts = explode(' ', trim($this->_currentStatusResponse()));
        $this->_logger?->info('Group selected: ' . $parts[3]);

        return [
            'group' => $parts[3],
            'first' => $parts[1],
            'last'  => $parts[2],
            'count' => $parts[0],
        ];
    }

    protected function cmdListgroup(?string $newsgroup = null, mixed $range = null): mixed
    {
        $command = 'LISTGROUP';
        if ($newsgroup !== null) {
            $command .= ' ' . $newsgroup;
            if ($range !== null) {
                $command .= ' ' . $range;
            }
        }

        $response = $this->_sendCommand($command);
        if (Error::isError($response)) {
            return $response;
        }

        return match ($response) {
            ResponseCode::GroupSelected->value  => $this->_parseListgroupResponse(),
            ResponseCode::NoGroupSelected->value => $this->throwError('Not currently in newsgroup', $response, $this->_currentStatusResponse()),
            ResponseCode::NotPermitted->value    => $this->throwError('No permission', $response, $this->_currentStatusResponse()),
            default                              => $this->_handleUnexpectedResponse($response),
        };
    }

    /** @return array<string, mixed>|Error */
    private function _parseListgroupResponse(): array|Error
    {
        $articles = $this->_getTextResponse();
        if (Error::isError($articles)) {
            return $articles;
        }

        $parts = explode(' ', trim($this->_currentStatusResponse()), 4);

        if (!is_numeric($parts[0]) || !is_numeric($parts[1]) || !is_numeric($parts[2]) || empty($parts[3])) {
            return ['group' => null, 'first' => null, 'last' => null, 'count' => null, 'articles' => $articles];
        }

        return [
            'group'    => $parts[3],
            'first'    => $parts[1],
            'last'     => $parts[2],
            'count'    => $parts[0],
            'articles' => $articles,
        ];
    }

    protected function cmdLast(): mixed
    {
        $response = $this->_sendCommand('LAST');
        if (Error::isError($response)) {
            return $response;
        }

        return match ($response) {
            ResponseCode::ArticleSelected->value  => $this->_parseArticlePointer('Selected previous article'),
            ResponseCode::NoGroupSelected->value  => $this->throwError('No newsgroup has been selected', $response, $this->_currentStatusResponse()),
            ResponseCode::NoArticleSelected->value => $this->throwError('No current article has been selected', $response, $this->_currentStatusResponse()),
            ResponseCode::NoPreviousArticle->value => $this->throwError('No previous article in this group', $response, $this->_currentStatusResponse()),
            default                                => $this->_handleUnexpectedResponse($response),
        };
    }

    protected function cmdNext(): mixed
    {
        $response = $this->_sendCommand('NEXT');
        if (Error::isError($response)) {
            return $response;
        }

        return match ($response) {
            ResponseCode::ArticleSelected->value  => $this->_parseArticlePointer('Selected next article'),
            ResponseCode::NoGroupSelected->value  => $this->throwError('No newsgroup has been selected', $response, $this->_currentStatusResponse()),
            ResponseCode::NoArticleSelected->value => $this->throwError('No current article has been selected', $response, $this->_currentStatusResponse()),
            ResponseCode::NoNextArticle->value     => $this->throwError('No next article in this group', $response, $this->_currentStatusResponse()),
            default                                => $this->_handleUnexpectedResponse($response),
        };
    }

    /**
     * Parse a 223 response into [number, message-id] and log.
     */
    /** @return array{0: string, 1: string} */
    private function _parseArticlePointer(string $logPrefix): array
    {
        $parts = explode(' ', trim($this->_currentStatusResponse()));
        $this->_logger?->info("$logPrefix: {$parts[0]} - {$parts[1]}");
        return [$parts[0], (string) $parts[1]];
    }

    /* Retrieval of articles and article sections */

    protected function cmdArticle(mixed $article = null): mixed
    {
        $command = $article === null ? 'ARTICLE' : 'ARTICLE ' . $article;

        $response = $this->_sendCommand($command);
        if (Error::isError($response)) {
            return $response;
        }

        return match ($response) {
            ResponseCode::ArticleFollows->value       => $this->_fetchTextAndLog($article === null ? 'Fetched current article' : "Fetched article: $article"),
            ResponseCode::NoGroupSelected->value      => $this->throwError('No newsgroup has been selected', $response, $this->_currentStatusResponse()),
            ResponseCode::NoArticleSelected->value    => $this->throwError('No current article has been selected', $response, $this->_currentStatusResponse()),
            ResponseCode::NoSuchArticleNumber->value  => $this->throwError('No such article number in this group', $response, $this->_currentStatusResponse()),
            ResponseCode::NoSuchArticleId->value      => $this->throwError('No such article found', $response, $this->_currentStatusResponse()),
            default                                   => $this->_handleUnexpectedResponse($response),
        };
    }

    protected function cmdHead(mixed $article = null): mixed
    {
        $command = $article === null ? 'HEAD' : 'HEAD ' . $article;

        $response = $this->_sendCommand($command);
        if (Error::isError($response)) {
            return $response;
        }

        return match ($response) {
            ResponseCode::HeadFollows->value          => $this->_fetchTextAndLog($article === null ? 'Fetched current article header' : "Fetched article header for article: $article"),
            ResponseCode::NoGroupSelected->value      => $this->throwError('No newsgroup has been selected', $response, $this->_currentStatusResponse()),
            ResponseCode::NoArticleSelected->value    => $this->throwError('No current article has been selected', $response, $this->_currentStatusResponse()),
            ResponseCode::NoSuchArticleNumber->value  => $this->throwError('No such article number in this group', $response, $this->_currentStatusResponse()),
            ResponseCode::NoSuchArticleId->value      => $this->throwError('No such article found', $response, $this->_currentStatusResponse()),
            default                                   => $this->_handleUnexpectedResponse($response),
        };
    }

    protected function cmdBody(mixed $article = null): mixed
    {
        $command = $article === null ? 'BODY' : 'BODY ' . $article;

        $response = $this->_sendCommand($command);
        if (Error::isError($response)) {
            return $response;
        }

        return match ($response) {
            ResponseCode::BodyFollows->value          => $this->_fetchTextAndLog($article === null ? 'Fetched current article body' : "Fetched article body for article: $article"),
            ResponseCode::NoGroupSelected->value      => $this->throwError('No newsgroup has been selected', $response, $this->_currentStatusResponse()),
            ResponseCode::NoArticleSelected->value    => $this->throwError('No current article has been selected', $response, $this->_currentStatusResponse()),
            ResponseCode::NoSuchArticleNumber->value  => $this->throwError('No such article number in this group', $response, $this->_currentStatusResponse()),
            ResponseCode::NoSuchArticleId->value      => $this->throwError('No such article found', $response, $this->_currentStatusResponse()),
            default                                   => $this->_handleUnexpectedResponse($response),
        };
    }

    /**
     * Fetch text response and log a message on success.
     */
    private function _fetchTextAndLog(string $logMessage): mixed
    {
        $data = $this->_getTextResponse();
        if (Error::isError($data)) {
            return $data;
        }
        $this->_logger?->info($logMessage);
        return $data;
    }

    protected function cmdStat(mixed $article = null): mixed
    {
        $command = $article === null ? 'STAT' : 'STAT ' . $article;

        $response = $this->_sendCommand($command);
        if (Error::isError($response)) {
            return $response;
        }

        return match ($response) {
            ResponseCode::ArticleSelected->value      => $this->_parseArticlePointer('Selected article'),
            ResponseCode::NoGroupSelected->value      => $this->throwError('No newsgroup has been selected', $response, $this->_currentStatusResponse()),
            ResponseCode::NoSuchArticleNumber->value  => $this->throwError('No such article number in this group', $response, $this->_currentStatusResponse()),
            ResponseCode::NoSuchArticleId->value      => $this->throwError('No such article found', $response, $this->_currentStatusResponse()),
            default                                   => $this->_handleUnexpectedResponse($response),
        };
    }

    /* Article posting */

    protected function cmdPost(): mixed
    {
        $response = $this->_sendCommand('POST');
        if (Error::isError($response)) {
            return $response;
        }

        return match ($response) {
            ResponseCode::PostingSend->value       => true,
            ResponseCode::PostingProhibited->value => $this->throwError('Posting not allowed', $response, $this->_currentStatusResponse()),
            default                                => $this->_handleUnexpectedResponse($response),
        };
    }

    protected function cmdPost2(mixed $article): mixed
    {
        $this->_sendArticle($article);

        $response = $this->_getStatusResponse();
        if (Error::isError($response)) {
            return $response;
        }

        return match ($response) {
            ResponseCode::PostingSuccess->value => true,
            ResponseCode::PostingFailure->value => $this->throwError('Posting failed', $response, $this->_currentStatusResponse()),
            default                             => $this->_handleUnexpectedResponse($response),
        };
    }

    protected function cmdIhave(string $id): mixed
    {
        $response = $this->_sendCommand('IHAVE ' . $id);
        if (Error::isError($response)) {
            return $response;
        }

        return match ($response) {
            ResponseCode::TransferSend->value    => true,
            ResponseCode::TransferUnwanted->value => $this->throwError('Article not wanted', $response, $this->_currentStatusResponse()),
            ResponseCode::TransferFailure->value  => $this->throwError('Transfer not possible; try again later', $response, $this->_currentStatusResponse()),
            default                              => $this->_handleUnexpectedResponse($response),
        };
    }

    protected function cmdIhave2(mixed $article): mixed
    {
        $this->_sendArticle($article);

        $response = $this->_getStatusResponse();
        if (Error::isError($response)) {
            return $response;
        }

        return match ($response) {
            ResponseCode::TransferSuccess->value  => true,
            ResponseCode::TransferFailure->value  => $this->throwError('Transfer not possible; try again later', $response, $this->_currentStatusResponse()),
            ResponseCode::TransferRejected->value => $this->throwError('Transfer rejected; do not retry', $response, $this->_currentStatusResponse()),
            default                               => $this->_handleUnexpectedResponse($response),
        };
    }

    /* Information commands */

    protected function cmdDate(): mixed
    {
        $response = $this->_sendCommand('DATE');
        if (Error::isError($response)) {
            return $response;
        }

        return match ($response) {
            ResponseCode::ServerDate->value => $this->_currentStatusResponse(),
            default                         => $this->_handleUnexpectedResponse($response),
        };
    }

    protected function cmdHelp(): mixed
    {
        $response = $this->_sendCommand('HELP');
        if (Error::isError($response)) {
            return $response;
        }

        if ($response === ResponseCode::HelpFollows->value) {
            $data = $this->_getTextResponse();
            return Error::isError($data) ? $data : $data;
        }

        return $this->_handleUnexpectedResponse($response);
    }

    protected function cmdNewgroups(int $time, ?string $distributions = null): mixed
    {
        $date = gmdate('ymd His', $time);
        $command = $distributions === null
            ? "NEWGROUPS $date GMT"
            : "NEWGROUPS $date GMT <$distributions>";

        $response = $this->_sendCommand($command);
        if (Error::isError($response)) {
            return $response;
        }

        if ($response === ResponseCode::NewGroupsFollow->value) {
            $data = $this->_getTextResponse();
            if (Error::isError($data)) {
                return $data;
            }
            return $this->_parseGroupLines($data);
        }

        return $this->_handleUnexpectedResponse($response);
    }

    protected function cmdNewnews(int $time, mixed $newsgroups, mixed $distribution = null): mixed
    {
        $date = gmdate('ymd His', $time);

        if (\is_array($newsgroups)) {
            $newsgroups = implode(',', $newsgroups);
        }

        $command = $distribution === null
            ? "NEWNEWS $newsgroups $date GMT"
            : 'NEWNEWS ' . $newsgroups . ' ' . $date . ' GMT <' . (\is_array($distribution) ? implode(',', $distribution) : $distribution) . '>';

        $response = $this->_sendCommand($command);
        if (Error::isError($response)) {
            return $response;
        }

        if ($response === ResponseCode::NewArticlesFollow->value) {
            $textResponse = $this->_getTextResponse();
            return Error::isError($textResponse) ? $textResponse : $textResponse;
        }

        return $this->_handleUnexpectedResponse($response);
    }

    /* The LIST commands */

    protected function cmdList(): mixed
    {
        $response = $this->_sendCommand('LIST');
        if (Error::isError($response)) {
            return $response;
        }

        if ($response === ResponseCode::GroupsFollow->value) {
            $data = $this->_getTextResponse();
            if (Error::isError($data)) {
                return $data;
            }
            return $this->_parseGroupLines($data);
        }

        return $this->_handleUnexpectedResponse($response);
    }

    protected function cmdListActive(?string $wildmat = null): mixed
    {
        $command = $wildmat === null ? 'LIST ACTIVE' : 'LIST ACTIVE ' . $wildmat;

        $response = $this->_sendCommand($command);
        if (Error::isError($response)) {
            return $response;
        }

        if ($response === ResponseCode::GroupsFollow->value) {
            $data = $this->_getTextResponse();
            if (Error::isError($data)) {
                return $data;
            }

            $groups = [];
            foreach ($data as $line) {
                $arr = explode(' ', trim($line));
                $groups[$arr[0]] = ['group' => $arr[0], 'last' => $arr[1], 'first' => $arr[2]];
            }

            $this->_logger?->info('Fetched list of available groups');
            return $groups;
        }

        return $this->_handleUnexpectedResponse($response);
    }

    protected function cmdListNewsgroups(?string $wildmat = null): mixed
    {
        $command = $wildmat === null ? 'LIST NEWSGROUPS' : 'LIST NEWSGROUPS ' . $wildmat;

        $response = $this->_sendCommand($command);
        if (Error::isError($response)) {
            return $response;
        }

        return match ($response) {
            ResponseCode::GroupsFollow->value   => $this->_parseNewsgroupDescriptions(),
            ResponseCode::NotSupported->value   => $this->throwError('Internal server error, function not performed', $response, $this->_currentStatusResponse()),
            default                             => $this->_handleUnexpectedResponse($response),
        };
    }

    /** @return array<string, string>|Error */
    private function _parseNewsgroupDescriptions(): array|Error
    {
        $data = $this->_getTextResponse();
        if (Error::isError($data)) {
            return $data;
        }

        $groups = [];
        foreach ($data as $line) {
            if (preg_match("/^(\S+)\s+(.*)$/", ltrim($line), $matches)) {
                $groups[$matches[1]] = (string) $matches[2];
            } else {
                $this->_logger?->warning("Received non-standard line: '$line'");
            }
        }

        $this->_logger?->info('Fetched group descriptions');
        return $groups;
    }

    /**
     * Parse group list lines into associative array.
     */
    /**
     * @param array<int, string> $data
     * @return array<string, array{group: string, last: string, first: string, posting: string}>
     */
    private function _parseGroupLines(array $data): array
    {
        $groups = [];
        foreach ($data as $line) {
            $arr = explode(' ', trim($line));
            $groups[$arr[0]] = [
                'group'   => $arr[0],
                'last'    => $arr[1],
                'first'   => $arr[2],
                'posting' => $arr[3],
            ];
        }
        return $groups;
    }

    /* Article field access commands */

    protected function cmdOver(?string $range = null): mixed
    {
        $command = $range === null ? 'OVER' : 'OVER ' . $range;

        $response = $this->_sendCommand($command);
        if (Error::isError($response)) {
            return $response;
        }

        return match ($response) {
            ResponseCode::OverviewFollows->value      => $this->_parseOverviewResponse($range),
            ResponseCode::NoGroupSelected->value      => $this->throwError('No news group current selected', $response, $this->_currentStatusResponse()),
            ResponseCode::NoArticleSelected->value    => $this->throwError('No article(s) selected', $response, $this->_currentStatusResponse()),
            ResponseCode::NoSuchArticleNumber->value  => $this->throwError('No articles in that range', $response, $this->_currentStatusResponse()),
            ResponseCode::NotPermitted->value         => $this->throwError('No permission', $response, $this->_currentStatusResponse()),
            default                                   => $this->_handleUnexpectedResponse($response),
        };
    }

    protected function cmdXOver(?string $range = null): mixed
    {
        $command = $range === null ? 'XOVER' : 'XOVER ' . $range;

        $response = $this->_sendCommand($command);
        if (Error::isError($response)) {
            return $response;
        }

        return match ($response) {
            ResponseCode::OverviewFollows->value   => $this->_parseOverviewResponse($range),
            ResponseCode::NoGroupSelected->value   => $this->throwError('No news group current selected', $response, $this->_currentStatusResponse()),
            ResponseCode::NoArticleSelected->value => $this->throwError('No article(s) selected', $response, $this->_currentStatusResponse()),
            ResponseCode::NotPermitted->value      => $this->throwError('No permission', $response, $this->_currentStatusResponse()),
            default                                => $this->_handleUnexpectedResponse($response),
        };
    }

    private function _parseOverviewResponse(?string $range): mixed
    {
        $data = $this->_getTextResponse();
        if (Error::isError($data)) {
            return $data;
        }

        foreach ($data as $key => $value) {
            $data[$key] = explode("\t", $value);
        }

        $this->_logger?->info('Fetched overview ' . ($range === null ? 'for current article' : "for range: $range"));
        return $data;
    }

    protected function cmdListOverviewFmt(): mixed
    {
        $response = $this->_sendCommand('LIST OVERVIEW.FMT');
        if (Error::isError($response)) {
            return $response;
        }

        return match ($response) {
            ResponseCode::GroupsFollow->value  => $this->_parseOverviewFormat(),
            ResponseCode::NotSupported->value  => $this->throwError('Internal server error, function not performed', $response, $this->_currentStatusResponse()),
            default                            => $this->_handleUnexpectedResponse($response),
        };
    }

    /** @return array<string, bool>|Error */
    private function _parseOverviewFormat(): array|Error
    {
        $data = $this->_getTextResponse();
        if (Error::isError($data)) {
            return $data;
        }

        $format = [];
        foreach ($data as $line) {
            if (strcasecmp(substr($line, -5, 5), ':full') === 0) {
                $format[substr($line, 0, -5)] = true;
            } else {
                $format[substr($line, 0, -1)] = false;
            }
        }

        $this->_logger?->info('Fetched overview format');
        return $format;
    }

    protected function cmdXHdr(string $field, ?string $range = null): mixed
    {
        $command = $range === null ? "XHDR $field" : "XHDR $field $range";

        $response = $this->_sendCommand($command);
        if (Error::isError($response)) {
            return $response;
        }

        return match ($response) {
            ResponseCode::HeadFollows->value       => $this->_parseKeyValueResponse(),
            ResponseCode::NoGroupSelected->value   => $this->throwError('No news group current selected', $response, $this->_currentStatusResponse()),
            ResponseCode::NoArticleSelected->value => $this->throwError('No current article selected', $response, $this->_currentStatusResponse()),
            ResponseCode::NoSuchArticleId->value   => $this->throwError('No such article', $response, $this->_currentStatusResponse()),
            ResponseCode::NotPermitted->value      => $this->throwError('No permission', $response, $this->_currentStatusResponse()),
            default                                => $this->_handleUnexpectedResponse($response),
        };
    }

    /**
     * @deprecated as of RFC2980.
     */
    protected function cmdXGTitle(string $wildmat = '*'): mixed
    {
        $response = $this->_sendCommand('XGTITLE ' . $wildmat);
        if (Error::isError($response)) {
            return $response;
        }

        return match ($response) {
            ResponseCode::XgtitleFollows->value     => $this->_parseXgtitleResponse(),
            ResponseCode::XgtitleUnavailable->value => $this->throwError('Groups and descriptions unavailable', $response, $this->_currentStatusResponse()),
            default                                 => $this->_handleUnexpectedResponse($response),
        };
    }

    /** @return array<string, string>|Error */
    private function _parseXgtitleResponse(): array|Error
    {
        $data = $this->_getTextResponse();
        if (Error::isError($data)) {
            return $data;
        }

        $groups = [];
        foreach ($data as $line) {
            preg_match("/^(.*?)\s(.*?$)/", trim($line), $matches);
            $groups[$matches[1]] = (string) $matches[2];
        }
        return $groups;
    }

    protected function cmdXROver(?string $range = null): mixed
    {
        $command = $range === null ? 'XROVER' : 'XROVER ' . $range;

        $response = $this->_sendCommand($command);
        if (Error::isError($response)) {
            return $response;
        }

        return match ($response) {
            ResponseCode::OverviewFollows->value   => $this->_parseKeyValueResponse(),
            ResponseCode::NoGroupSelected->value   => $this->throwError('No news group current selected', $response, $this->_currentStatusResponse()),
            ResponseCode::NoArticleSelected->value => $this->throwError('No article(s) selected', $response, $this->_currentStatusResponse()),
            ResponseCode::NotPermitted->value      => $this->throwError('No permission', $response, $this->_currentStatusResponse()),
            default                                => $this->_handleUnexpectedResponse($response),
        };
    }

    protected function cmdXPat(string $field, string $range, mixed $wildmat): mixed
    {
        if (\is_array($wildmat)) {
            $wildmat = implode(' ', $wildmat);
        }

        $response = $this->_sendCommand("XPAT $field $range $wildmat");
        if (Error::isError($response)) {
            return $response;
        }

        return match ($response) {
            ResponseCode::HeadFollows->value      => $this->_parseKeyValueResponse(),
            ResponseCode::NoSuchArticleId->value  => $this->throwError('No current article selected', $response, $this->_currentStatusResponse()),
            ResponseCode::NotPermitted->value     => $this->throwError('No permission', $response, $this->_currentStatusResponse()),
            default                               => $this->_handleUnexpectedResponse($response),
        };
    }

    /**
     * Parse text response as key-value pairs (space-separated).
     */
    /** @return array<string, string>|Error */
    private function _parseKeyValueResponse(): array|Error
    {
        $data = $this->_getTextResponse();
        if (Error::isError($data)) {
            return $data;
        }

        $result = [];
        foreach ($data as $line) {
            $parts = explode(' ', trim($line), 2);
            $result[$parts[0]] = $parts[1];
        }
        return $result;
    }

    /* Authentication */

    protected function cmdAuthinfo(string $user, ?string $pass = null): mixed
    {
        $response = $this->_sendCommand('AUTHINFO user ' . $user);
        if (Error::isError($response)) {
            return $response;
        }

        if ($response === ResponseCode::AuthenticationContinue->value && $pass !== null) {
            $response = $this->_sendCommand('AUTHINFO pass ' . $pass);
            if (Error::isError($response)) {
                return $response;
            }
        }

        return match ($response) {
            ResponseCode::AuthenticationAccepted->value => $this->_logAndReturn(true, "Authenticated (as user '$user')"),
            ResponseCode::AuthenticationContinue->value => $this->throwError('Authentication uncompleted', $response, $this->_currentStatusResponse()),
            ResponseCode::AuthenticationRejected->value,
            ResponseCode::NotPermitted->value           => $this->throwError('Authentication rejected', $response, $this->_currentStatusResponse()),
            default                                     => $this->_handleUnexpectedResponse($response),
        };
    }

    protected function cmdAuthinfoSimple(string $user, string $pass): mixed
    {
        return $this->throwError("The auth mode: 'simple' has not been implemented yet", null);
    }

    protected function cmdAuthinfoGeneric(string $user, string $pass): mixed
    {
        return $this->throwError("The auth mode: 'generic' has not been implemented yet", null);
    }

    protected function _isConnected(): bool
    {
        return \is_resource($this->_socket) && !feof($this->_socket);
    }
}

