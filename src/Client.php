<?php

declare(strict_types=1);

/**
 * NNTP Client — high-level API
 *
 * PHP versions 8.5 and above
 *
 * @category   Net
 * @package    Net_NNTP
 * @author     Heino H. Gehlsen <heino@gehlsen.dk>
 * @copyright  2002-2017 Heino H. Gehlsen <heino@gehlsen.dk>. All Rights Reserved.
 * @license    http://www.w3.org/Consortium/Legal/2002/copyright-software-20021231 W3C SOFTWARE NOTICE AND LICENSE
 * @link       https://github.com/DariusIII/Net_NNTP
 */

namespace Net\NNTP;

use Net\NNTP\Protocol\Client as ProtocolClient;

/**
 * Implementation of the client side of NNTP (Network News Transfer Protocol).
 *
 * The Net\NNTP\Client class is a frontend class to the Net\NNTP\Protocol\Client class.
 */
class Client extends ProtocolClient
{
    protected ?array $_selectedGroupSummary = null;
    protected ?array $_overviewFormatCache = null;

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Connect to a server.
     */
    public function connect(?string $host = null, mixed $encryption = null, ?int $port = null, ?int $timeout = null): mixed
    {
        // v1.0.x API
        if (\is_int($encryption)) {
            trigger_error('You are using deprecated API v1.0 in Net\NNTP\Client: connect() !', E_USER_NOTICE);
            $port = $encryption;
            $encryption = null;
        }

        return parent::connect($host, $encryption, $port, $timeout);
    }

    /**
     * Disconnect from server.
     */
    public function disconnect(): mixed
    {
        return parent::disconnect();
    }

    /**
     * @deprecated Use disconnect() instead.
     */
    public function quit(): mixed
    {
        return $this->disconnect();
    }

    /**
     * Authenticate.
     */
    public function authenticate(?string $user, string $pass): mixed
    {
        if ($user === null) {
            return $this->throwError('No username supplied', null);
        }

        return $this->cmdAuthinfo($user, $pass);
    }

    /**
     * Selects a group.
     */
    public function selectGroup(string $group, mixed $articles = false): mixed
    {
        $summary = $this->cmdGroup($group);
        if (Error::isError($summary)) {
            return $summary;
        }

        $this->_selectedGroupSummary = $summary;

        if ($articles !== false) {
            $summary2 = $this->cmdListgroup($group, ($articles === true ? null : $articles));
            if (Error::isError($summary2)) {
                return $summary2;
            }

            if ($summary2['group'] === $group) {
                $summary = $summary2;
            } else {
                $summary['articles'] = $summary2['articles'];
            }
        }

        return $summary;
    }

    /**
     * Select the previous article.
     */
    public function selectPreviousArticle(int $_ret = 0): mixed
    {
        $response = $this->cmdLast();

        if (Error::isError($response)) {
            return false;
        }

        return match ($_ret) {
            -1 => ['Number' => (int) $response[0], 'Message-ID' => (string) $response[1]],
            0  => (int) $response[0],
            1  => (string) $response[1],
            default => $this->throwError('ERROR'),
        };
    }

    /**
     * Select the next article.
     */
    public function selectNextArticle(int $_ret = 0): mixed
    {
        $response = $this->cmdNext();

        if (Error::isError($response)) {
            return $response;
        }

        return match ($_ret) {
            -1 => ['Number' => (int) $response[0], 'Message-ID' => (string) $response[1]],
            0  => (int) $response[0],
            1  => (string) $response[1],
            default => $this->throwError('ERROR'),
        };
    }

    /**
     * Selects an article by article message-number.
     */
    public function selectArticle(mixed $article = null, int $_ret = 0): mixed
    {
        $response = $this->cmdStat($article);

        if (Error::isError($response)) {
            return $response;
        }

        return match ($_ret) {
            -1 => ['Number' => (int) $response[0], 'Message-ID' => (string) $response[1]],
            0  => (int) $response[0],
            1  => (string) $response[1],
            default => $this->throwError('ERROR'),
        };
    }

    /**
     * Fetch article (header + body).
     */
    public function getArticle(mixed $article = null, bool $implode = false): mixed
    {
        // v1.1.x API
        if (\is_string($implode)) {
            trigger_error('You are using deprecated API v1.1 in Net\NNTP\Client: getArticle() !', E_USER_NOTICE);
            $class = $implode;
            $implode = false;

            if (!class_exists($class)) {
                return $this->throwError("Class '$class' does not exist!");
            }
        }

        $data = $this->cmdArticle($article);
        if (Error::isError($data)) {
            return $data;
        }

        if ($implode === true) {
            $data = implode("\r\n", $data);
        }

        if (isset($class)) {
            return new $class($data);
        }

        return $data;
    }

    /**
     * Fetch article header.
     */
    public function getHeader(mixed $article = null, bool $implode = false): mixed
    {
        // v1.1.x API
        if (\is_string($implode)) {
            trigger_error('You are using deprecated API v1.1 in Net\NNTP\Client: getHeader() !', E_USER_NOTICE);
            $class = $implode;
            $implode = false;

            if (!class_exists($class)) {
                return $this->throwError("Class '$class' does not exist!");
            }
        }

        $data = $this->cmdHead($article);
        if (Error::isError($data)) {
            return $data;
        }

        if ($implode === true) {
            $data = implode("\r\n", $data);
        }

        if (isset($class)) {
            return new $class($data);
        }

        return $data;
    }

    /**
     * Fetch article body.
     */
    public function getBody(mixed $article = null, bool $implode = false): mixed
    {
        // v1.1.x API
        if (\is_string($implode)) {
            trigger_error('You are using deprecated API v1.1 in Net\NNTP\Client: getBody() !', E_USER_NOTICE);
            $class = $implode;
            $implode = false;

            if (!class_exists($class)) {
                return $this->throwError("Class '$class' does not exist!");
            }
        }

        $data = $this->cmdBody($article);
        if (Error::isError($data)) {
            return $data;
        }

        if ($implode === true) {
            $data = implode("\r\n", $data);
        }

        if (isset($class)) {
            return new $class($data);
        }

        return $data;
    }

    /**
     * Post a raw article to a number of groups.
     */
    public function post(mixed $article): mixed
    {
        // API v1.0
        if (\func_num_args() >= 4) {
            trigger_error('You are using deprecated API v1.0 in Net\NNTP\Client: post() !', E_USER_NOTICE);
            $groups = \func_get_arg(0);
            $subject = \func_get_arg(1);
            $body = \func_get_arg(2);
            $from = \func_get_arg(3);
            $additional = \func_get_arg(4);
            return $this->mail($groups, $subject, $body, "From: $from\r\n" . $additional);
        }

        if (!\is_array($article) && !\is_string($article)) {
            return $this->throwError('Article must be a string or array', null, 0);
        }

        $post = $this->cmdPost();
        if (Error::isError($post)) {
            return $post;
        }

        if (is_callable($article)) {
            $article = \call_user_func($article);
        }

        return $this->cmdPost2($article);
    }

    /**
     * Post an article using parameters similar to PHP's mail() function.
     */
    public function mail(string $groups, string $subject, string $body, ?string $additional = null): mixed
    {
        $post = $this->cmdPost();
        if (Error::isError($post)) {
            return $post;
        }

        $header  = "Newsgroups: $groups\r\n";
        $header .= "Subject: $subject\r\n";
        $header .= "X-poster: Net_NNTP v@package_version@ (@package_state@)\r\n";
        if ($additional !== null) {
            $header .= $additional;
        }
        $header .= "\r\n";

        return $this->cmdPost2([$header, $body]);
    }

    /**
     * Get the server's internal date.
     */
    public function getDate(int $format = 1): mixed
    {
        $date = $this->cmdDate();
        if (Error::isError($date)) {
            return $date;
        }

        return match ($format) {
            0 => $date,
            1 => strtotime(substr($date, 0, 8) . ' ' . substr($date, 8, 2) . ':' . substr($date, 10, 2) . ':' . substr($date, 12, 2)),
            2 => [
                'y' => (int) substr($date, 0, 4),
                'm' => (int) substr($date, 4, 2),
                'd' => (int) substr($date, 6, 2),
            ],
            default => $this->throwError('Invalid date format'),
        };
    }

    /**
     * Get new groups since a date.
     */
    public function getNewGroups(mixed $time, ?string $distributions = null): mixed
    {
        $time = $this->_resolveTimestamp($time);
        if (Error::isError($time)) {
            return $time;
        }

        return $this->cmdNewgroups($time, $distributions);
    }

    /**
     * Get new articles since a date.
     */
    public function getNewArticles(mixed $time, string $groups = '*', ?string $distribution = null): mixed
    {
        $time = $this->_resolveTimestamp($time);
        if (Error::isError($time)) {
            return $time;
        }

        return $this->cmdNewnews($time, $groups, $distribution);
    }

    /**
     * Resolve a mixed time value into a unix timestamp.
     */
    private function _resolveTimestamp(mixed $time): int|Error
    {
        if (\is_integer($time)) {
            return $time;
        }

        if (\is_string($time)) {
            $ts = strtotime($time);
            if ($ts === false) {
                return $this->throwError('$time could not be converted into a timestamp!', null, 0);
            }
            return $ts;
        }

        throw new \InvalidArgumentException('$time must be either a string or an integer/timestamp!');
    }

    /**
     * Fetch valid groups.
     */
    public function getGroups(mixed $wildmat = null): mixed
    {
        $backup = false;

        $groups = $this->cmdListActive($wildmat);
        if (Error::isError($groups)) {
            switch ($groups->getCode()) {
                case 500:
                case 501:
                    $backup = true;
                    break;
                default:
                    return $groups;
            }
        }

        if ($backup === true) {
            if ($wildmat !== null) {
                // wildmat not supported, fall back to full list
            }

            $groups2 = $this->cmdList();
            if (!Error::isError($groups2)) {
                $groups = $groups2;
            }
        }

        if (Error::isError($groups)) {
            return $groups;
        }

        return $groups;
    }

    /**
     * Fetch all known group descriptions.
     */
    public function getDescriptions(mixed $wildmat = null): mixed
    {
        if (\is_array($wildmat)) {
            $wildmat = implode(',', $wildmat);
        }

        $descriptions = $this->cmdListNewsgroups($wildmat);
        if (Error::isError($descriptions)) {
            return $descriptions;
        }

        return $descriptions;
    }

    /**
     * Fetch overview data for articles.
     */
    public function getOverview(mixed $range = null, bool $_names = true, bool $_forceNames = true): mixed
    {
        // API v1.0 compat
        if (\func_num_args() === 2 && !\is_bool(\func_get_arg(1))) {
            trigger_error('You are using deprecated API v1.0 in Net\NNTP\Client: getOverview() !', E_USER_NOTICE);

            $overview = $this->getOverview(\func_get_arg(0) . '-' . \func_get_arg(1), true, false);
            if (Error::isError($overview)) {
                return $overview;
            }

            $articles = [];
            foreach ($overview as $article) {
                $article = array_merge(['number' => array_shift($article)], $article);
                $articles[$article['Message-ID']] = $article;
            }
            return $articles;
        }

        $overview = $this->cmdXOver($range);
        if (Error::isError($overview)) {
            return $overview;
        }

        if ($_names) {
            if ($this->_overviewFormatCache === null) {
                $format = $this->getOverviewFormat($_forceNames, true);
                if (Error::isError($format)) {
                    return $format;
                }

                $format = array_merge(['Number' => false], $format);
                $this->_overviewFormatCache = $format;
            } else {
                $format = $this->_overviewFormatCache;
            }

            $fieldNames = array_keys($format);
            $fieldFlags = array_values($format);
            $fieldCount = \count($fieldNames);

            foreach ($overview as $key => $article) {
                $mapped = [];
                for ($i = 0; $i < $fieldCount; $i++) {
                    $value = $article[$i] ?? '';
                    if ($fieldFlags[$i] === true) {
                        $pos = strpos($value, ':');
                        $value = ltrim(substr($value, ($pos === false ? 0 : $pos + 1)), " \t");
                    }
                    $mapped[$fieldNames[$i]] = $value;
                }
                $overview[$key] = $mapped;
            }
        }

        // Single article expected?
        $isSingle = $range === null
            || (\is_string($range) && ctype_digit($range))
            || (\is_string($range) && str_starts_with($range, '<') && str_ends_with($range, '>'));

        if ($isSingle) {
            return \count($overview) === 0 ? false : reset($overview);
        }

        return $overview;
    }

    /**
     * Fetch overview format field names.
     */
    public function getOverviewFormat(bool $_forceNames = true, bool $_full = false): mixed
    {
        $format = $this->cmdListOverviewFmt();
        if (Error::isError($format)) {
            return $format;
        }

        if ($_forceNames) {
            array_splice($format, 0, 7);
            $format = array_merge([
                'Subject'    => false,
                'From'       => false,
                'Date'       => false,
                'Message-ID' => false,
                'References' => false,
                ':bytes'     => false,
                ':lines'     => false,
            ], $format);
        }

        return $_full ? $format : array_keys($format);
    }

    /**
     * Fetch content of a header field from message(s).
     */
    public function getHeaderField(string $field, mixed $range = null): mixed
    {
        $fields = $this->cmdXHdr($field, $range);
        if (Error::isError($fields)) {
            return $fields;
        }

        $isSingle = $range === null
            || \is_int($range)
            || (\is_string($range) && ctype_digit($range))
            || (\is_string($range) && str_starts_with($range, '<') && str_ends_with($range, '>'));

        if ($isSingle && \count($fields) === 0) {
            return false;
        }

        if ($isSingle) {
            return reset($fields);
        }

        return $fields;
    }

    /**
     * Fetch article numbers in current group.
     */
    public function getGroupArticles(mixed $range = null): mixed
    {
        $summary = $this->cmdListgroup();
        if (Error::isError($summary)) {
            return $summary;
        }

        if ($summary['group'] !== null) {
            $this->_selectedGroupSummary = $summary;
        }

        return $summary['articles'];
    }

    /**
     * Fetch reference header field of message(s).
     */
    public function getReferences(mixed $range = null): mixed
    {
        $backup = false;

        $references = $this->cmdXHdr('References', $range);
        if (Error::isError($references)) {
            switch ($references->getCode()) {
                case 500:
                case 501:
                    $backup = true;
                    break;
                default:
                    return $references;
            }
        }

        if (\is_array($references) && \count($references) === 0) {
            $backup = true;
        }

        if ($backup) {
            $references2 = $this->cmdXROver($range);
            if (!Error::isError($references2)) {
                $references = $references2;
            }
        }

        if (Error::isError($references)) {
            return $references;
        }

        if (\is_array($references)) {
            foreach ($references as $key => $val) {
                $references[$key] = preg_split("/ +/", trim($val), -1, PREG_SPLIT_NO_EMPTY);
            }
        }

        $isSingle = $range === null
            || \is_int($range)
            || (\is_string($range) && ctype_digit($range))
            || (\is_string($range) && str_starts_with($range, '<') && str_ends_with($range, '>'));

        if ($isSingle) {
            return \count($references) === 0 ? false : reset($references);
        }

        return $references;
    }

    /**
     * Number of articles in currently selected group.
     */
    public function count(): mixed
    {
        return $this->_selectedGroupSummary['count'] ?? null;
    }

    /**
     * Maximum article number in currently selected group.
     */
    public function last(): mixed
    {
        return $this->_selectedGroupSummary['last'] ?? null;
    }

    /**
     * Minimum article number in currently selected group.
     */
    public function first(): mixed
    {
        return $this->_selectedGroupSummary['first'] ?? null;
    }

    /**
     * Currently selected group name.
     */
    public function group(): mixed
    {
        return $this->_selectedGroupSummary['group'] ?? null;
    }

    /**
     * @deprecated Use _isConnected() internally.
     */
    public function isConnected(): bool
    {
        trigger_error('You are using deprecated API v1.0 in Net\NNTP\Client: isConnected() !', E_USER_NOTICE);
        return parent::_isConnected();
    }

    /** @deprecated Use getArticle() */
    public function getArticleRaw($article, $implode = false)
    {
        trigger_error('You are using deprecated API v1.0 in Net\NNTP\Client: getArticleRaw() !', E_USER_NOTICE);
        return $this->getArticle($article, $implode);
    }

    /** @deprecated Use getHeader() */
    public function getHeaderRaw($article = null, $implode = false)
    {
        trigger_error('You are using deprecated API v1.0 in Net\NNTP\Client: getHeaderRaw() !', E_USER_NOTICE);
        return $this->getHeader($article, $implode);
    }

    /** @deprecated Use getBody() */
    public function getBodyRaw($article = null, $implode = false)
    {
        trigger_error('You are using deprecated API v1.0 in Net\NNTP\Client: getBodyRaw() !', E_USER_NOTICE);
        return $this->getBody($article, $implode);
    }

    /** @deprecated Use getNewArticles() */
    public function getNewNews($time, $groups = '*', $distribution = null)
    {
        trigger_error('You are using deprecated API v1.1 in Net\NNTP\Client: getNewNews() !', E_USER_NOTICE);
        return $this->getNewArticles($time, $groups, $distribution);
    }

    /** @deprecated Use getReferences() */
    public function getReferencesOverview($first, $last)
    {
        trigger_error('You are using deprecated API v1.0 in Net\NNTP\Client: getReferencesOverview() !', E_USER_NOTICE);
        return $this->getReferences($first . '-' . $last);
    }
}

