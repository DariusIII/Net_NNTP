<?php

namespace DariusIII\NetNntp;

use DariusIII\NetNntp\Protocol\Client as ProtocolClient;

/**
 * Implementation of the client side of NNTP (Network News Transfer Protocol)
 *
 * The Client class is a frontend class to the Protocol Client class.
 *
 * PHP versions 8.5 and above
 *
 * @category   Net
 * @package    Net_NNTP
 * @author     Heino H. Gehlsen <heino@gehlsen.dk>
 * @copyright  2002-2017 Heino H. Gehlsen <heino@gehlsen.dk>. All Rights Reserved.
 * @license    http://www.w3.org/Consortium/Legal/2002/copyright-software-20021231 W3C SOFTWARE NOTICE AND LICENSE
 * @version    package: @package_version@ (@package_state@)
 * @version    api: @api_version@ (@api_state@)
 * @access     public
 */
class Client extends ProtocolClient
{
    /**
     * Information summary about the currently selected group.
     *
     * @var array|null
     * @access private
     */
    protected ?array $_selectedGroupSummary = null;

    /**
     * @var array|null
     * @access private
     * @since 1.3.0
     */
    protected ?array $_overviewFormatCache = null;

    /**
     * Constructor
     *
     * @access public
     */
    public function __construct()
    {
    	parent::__construct();
    }

    /**
     * Connect to a server.
     *
     * @param  string|null  $host	(optional) The hostname og IP-address of the NNTP-server to connect to, defaults to localhost.
     * @param  string|null  $encryption	(optional) false|'tls'|'ssl', defaults to false.
     * @param  int|null  $port	(optional) The port number to connect to, defaults to 119 or 563 dependng on $encryption.
     * @param  int|null  $timeout	(optional)
     *
     * @return mixed <br>
     *  - (bool)	True when posting allowed, otherwise false
     *  - (object)	Error on failure
     * @access public
     */
    public function connect(?string $host = null, ?string $encryption = null, ?int $port = null, ?int $timeout = null): mixed
    {
    	return parent::connect($host, $encryption, $port, $timeout);
    }

    /**
     * Disconnect from server.
     *
     * @return mixed <br>
     *  - (bool)
     *  - (object)	Error on failure
     * @access public
     */
    public function disconnect(): mixed
    {
        return parent::disconnect();
    }


	/**
	 * Authenticate.
	 *
	 * @param  string|null  $user  The username
	 * @param  string  $pass  The password
	 *
	 * @return mixed <br>
	 *  - (bool)    True on successful authentification, otherwise false
	 *  - (object)    Error on failure
	 * @access public
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
     *
     * @param  string  $group	Name of the group to select
     * @param  false|mixed  $articles	(optional) experimental! When true the article numbers is returned in 'articles'
     *
     * @return mixed <br>
     *  - (array)	Summary about the selected group
     *  - (object)	Error on failure
     * @access public
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
     *
     * @param  int  $_ret	(optional) Experimental
     *
     * @return mixed <br>
     *  - (integer)	Article number, if $ret=0 (default)
     *  - (string)	Message-id, if $ret=1
     *  - (array)	Both article number and message-id, if $ret=-1
     *  - (bool)	False if no prevoius article exists
     *  - (object)	Error on failure
     * @access public
     */
    public function selectPreviousArticle(int $_ret = 0): mixed
    {
        $response = $this->cmdLast();

    	if (Error::isError($response)) {
    	    return false;
    	}

	    return match ($_ret) {
		    -1 => ['Number' => (int) $response[0], 'Message-ID' => (string) $response[1]],
		    0 => (int) $response[0],
		    1 => (string) $response[1],
		    default => $this->throwError("ERROR"),
	    };
    }

    /**
     * Select the next article.
     *
     * @param  int  $_ret	(optional) Experimental
     *
     * @return mixed <br>
     *  - (integer)	Article number, if $ret=0 (default)
     *  - (string)	Message-id, if $ret=1
     *  - (array)	Both article number and message-id, if $ret=-1
     *  - (bool)	False if no further articles exist
     *  - (object)	Error on unexpected failure
     * @access public
     */
    public function selectNextArticle(int $_ret = 0): mixed
    {
        $response = $this->cmdNext();

    	if (Error::isError($response)) {
    	    return $response;
	}

	    return match ($_ret) {
		    -1 => ['Number' => (int) $response[0], 'Message-ID' => (string) $response[1]],
		    0 => (int) $response[0],
		    1 => (string) $response[1],
		    default => $this->throwError("ERROR"),
	    };
    }

    /**
     * Selects an article by article message-number.
     *
     * @param  mixed|null  $article	The message-number (on the server) of
     *                                  the article to select as current article.
     * @param  int  $_ret	(optional) Experimental
     *
     * @return mixed <br>
     *  - (integer)	Article number
     *  - (bool)	False if article doesn't exists
     *  - (object)	Error on failure
     * @access public
     */
    public function selectArticle(mixed $article = null, int $_ret = 0): mixed
    {
        $response = $this->cmdStat($article);

    	if (Error::isError($response)) {
    	    return $response;
	}

	    return match ($_ret) {
		    -1 => ['Number' => (int) $response[0], 'Message-ID' => (string) $response[1]],
		    0 => (int) $response[0],
		    1 => (string) $response[1],
		    default => $this->throwError("ERROR"),
	    };
    }

    /**
     * Fetch article into transfer object.
     *
     * @param  mixed|null  $article	(optional) Either the message-id or the
     *                                  message-number on the server of the
     *                                  article to fetch.
     * @param  bool  $implode	(optional) When true the result array
     *                                  is imploded to a string, defaults to
     *                                  false.
     *
     * @return mixed <br>
     *  - (array)	Complete article (when $implode is false)
     *  - (string)	Complete article (when $implode is true)
     *  - (object)	Error on failure
     * @access public
     */
    public function getArticle(mixed $article = null, bool $implode = false): mixed
    {
        $data = $this->cmdArticle($article);
        if (Error::isError($data)) {
    	    return $data;
    	}

    	if ($implode) {
    	    $data = implode("\r\n", $data);
    	}

    	return $data;
    }

    /**
     * Fetch article header.
     *
     * @param  mixed|null  $article	(optional) Either message-id or message
     *                                  number of the article to fetch.
     * @param  bool  $implode	(optional) When true the result array
     *                                  is imploded to a string, defaults to
     *                                  false.
     *
     * @return mixed <br>
     *  - (bool)	False if article does not exist
     *  - (array)	Header fields (when $implode is false)
     *  - (string)	Header fields (when $implode is true)
     *  - (object)	Error on failure
     * @access public
     */
    public function getHeader(mixed $article = null, bool $implode = false): mixed
    {
        $data = $this->cmdHead($article);
        if (Error::isError($data)) {
    	    return $data;
    	}

    	if ($implode) {
    	    $data = implode("\r\n", $data);
    	}

    	return $data;
    }

    /**
     * Fetch article body.
     *
     * @param  mixed|null  $article	(optional) Either the message-id or the
     *                                  message-number on the server of the
     *                                  article to fetch.
     * @param  bool  $implode	(optional) When true the result array
     *                                  is imploded to a string, defaults to
     *                                  false.
     *
     * @return mixed <br>
     *  - (array)	Message body (when $implode is false)
     *  - (string)	Message body (when $implode is true)
     *  - (object)	Error on failure
     * @access public
     */
    public function getBody(mixed $article = null, bool $implode = false): mixed
    {
        $data = $this->cmdBody($article);
        if (Error::isError($data)) {
    	    return $data;
    	}

    	if ($implode) {
    	    $data = implode("\r\n", $data);
    	}

    	return $data;
    }

    /**
     * Post a raw article to a number of groups.
     *
     * @param mixed	$article
     *
     * @return mixed <br>
     *  - (string)	Server response
     *  - (object)	Error on failure
     * @access public
     * @ignore
     */
    public function post(mixed $article): mixed
    {
    	if (!\is_array($article) && !\is_string($article) && !is_callable($article)) {
    	    return $this->throwError('Ups', null, 0);
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
     * Post an article to a number of groups - using same parameters as PHP's mail() function.
     *
     * @param  string  $groups	The groups to post to.
     * @param  string  $subject	The subject of the article.
     * @param  string  $body	The body of the article.
     * @param  string|null  $additional	(optional) Additional header fields to send.
     *
     * @return mixed <br>
     *  - (string)	Server response
     *  - (object)	Error on failure
     * @access public
     */
    public function mail(string $groups, string $subject, string $body, ?string $additional = null): mixed
    {
    	$post = $this->cmdPost();
        if (Error::isError($post)) {
    	    return $post;
    	}

        $header  = "Newsgroups: $groups\r\n";
        $header .= "Subject: $subject\r\n";
        $header .= "X-poster: PEAR::Net_NNTP v@package_version@ (@package_state@)\r\n";
    	if ($additional !== null) {
    	    $header .= $additional;
    	}
        $header .= "\r\n";

    	return $this->cmdPost2([$header, $body]);
    }

    /**
     * Get the server's internal date
     *
     * @param  int  $format	(optional) Determines the format of returned date:
     *                           - 0: return string
     *                           - 1: return integer/timestamp
     *                           - 2: return an array('y'=>year, 'm'=>month,'d'=>day)
     *
     * @return mixed <br>
     *  - (mixed)
     *  - (object)	Error on failure
     * @access public
     */
    public function getDate(int $format = 1): mixed
    {
        $date = $this->cmdDate();
        if (Error::isError($date)) {
    	    return $date;
    	}

	    return match ($format) {
		    0 => $date,
		    1 => strtotime(substr($date, 0, 8).' '.substr($date, 8, 2).':'.substr($date, 10, 2).':'.substr($date, 12,
				    2)),
		    2 => [
			    'y' => substr($date, 0, 4), 'm' => substr($date, 4, 2), 'd' => substr($date, 6, 2)
		    ],
		    default => $this->throwError("ERROR"),
	    };
    }

    /**
     * Get new groups since a date.
     *
     * @param mixed	$time
     * @param  string|null  $distributions	(optional)
     *
     * @return mixed <br>
     *  - (array)
     *  - (object)	Error on failure
     * @access public
     */
    public function getNewGroups(mixed $time, ?string $distributions = null): mixed
    {
    	switch (true) {
    	    case \is_integer($time):
	    	break;
    	    case \is_string($time):
    	    	$time = strtotime($time);
    	    	if ($time === false) {
    	    	    return $this->throwError('$time could not be converted into a timestamp!', null, 0);
    	    	}
    	    	break;
    	    default:
    	    	throw new \InvalidArgumentException('$time must be either a string or an integer/timestamp!');
    	}

    	return $this->cmdNewgroups($time, $distributions);
    }

    /**
     * Get new articles since a date.
     *
     * @param mixed	$time
     * @param  string  $groups	(optional)
     * @param  string|null  $distribution	(optional)
     *
     * @return mixed <br>
     *  - (array)
     *  - (object)	Error on failure
     * @access public
     * @since 1.3.0
     */
    public function getNewArticles(mixed $time, string $groups = '*', ?string $distribution = null)
    {
    	switch (true) {
    	    case \is_integer($time):
	    	break;
    	    case \is_string($time):
    	    	$time = strtotime($time);
			if ($time === false) {
    	    	    return $this->throwError('$time could not be converted into a timestamp!', null, 0);
		}
    	    	break;
    	    default:
    	    	throw new \InvalidArgumentException('$time must be either a string or an integer/timestamp!');
    	}

    	return $this->cmdNewnews($time, $groups, $distribution);
    }

    /**
     * Fetch valid groups.
     *
     * @return mixed $wildmat <br>
     *  - (array)	Nested array with information about every valid group
     *  - (object)	Error on failure
     * @access public
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

    	if ($backup) {
    	    if ($wildmat !== null) {
    	    	return $this->throwError("The server does not support the 'LIST ACTIVE' command, and the 'LIST' command does not support the wildmat parameter!", null, null);
    	    }

    	    $groups2 = $this->cmdList();
    	    if (Error::isError($groups2)) {
    		// Ignore...
    	    } else {
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
     *
     * @param  mixed|null  $wildmat	(optional)
     *
     * @return mixed <br>
     *  - (array)	Associated array with descriptions of known groups
     *  - (object)	Error on failure
     * @access public
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
     * Fetch an overview of article(s) in the currently selected group.
     *
     * @param  mixed|null  $range	(optional)
     * @param  boolean  $_names	(optional) experimental parameter! Use field names as array kays
     * @param  boolean  $_forceNames	(optional) experimental parameter!
     *
     * @return mixed <br>
     *  - (array)	Nested array of article overview data
     *  - (object)	Error on failure
     * @access public
     */
    public function getOverview(mixed $range = null, bool $_names = true, bool $_forceNames = true): mixed
    {
    	$overview = $this->cmdXOver($range);
    	if (Error::isError($overview)) {
    	    return $overview;
    	}

	    if ($_names) {

    	    if ($this->_overviewFormatCache === null) {
    	        $format = $this->getOverviewFormat($_forceNames, true);
    	        if (Error::isError($format)){
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
                $mappedArticle = [];

                for ($i = 0; $i < $fieldCount; $i++) {
                    $value = $article[$i] ?? '';

                    if ($fieldFlags[$i] === true) {
                        $pos = strpos($value, ':');
                        $value = ltrim(substr($value, ($pos === false ? 0 : $pos + 1)), " \t");
                    }

                    $mappedArticle[$fieldNames[$i]] = $value;
                }

                $overview[$key] = $mappedArticle;
            }
	    }

    	$expectSingle = $range === null
    	    || \is_int($range)
    	    || (\is_string($range) && ctype_digit($range))
    	    || (\is_string($range) && str_starts_with($range, '<') && str_ends_with($range, '>'));

    	if ($expectSingle) {
    	    return \count($overview) === 0 ? false : reset($overview);
    	}

    	return $overview;
    }

    /**
     * Fetch names of fields in overview database
     *
     * @return mixed <br>
     *  - (array)	Overview field names
     *  - (object)	Error on failure
     * @access public
     */
    public function getOverviewFormat(bool $_forceNames = true, bool $_full = false): mixed
    {
        $format = $this->cmdListOverviewFmt();
    	if (Error::isError($format)) {
    	    return $format;
    	}

    	if ($_forceNames) {
    	    array_splice($format, 0, 7);
    	    $format = array_merge(['Subject'    => false,
    	                           'From'       => false,
    	                           'Date'       => false,
    	                           'Message-ID' => false,
    	    	                   'References' => false,
    	                           ':bytes'     => false,
    	                           ':lines'     => false], $format);
    	}

    	return $_full ? $format : array_keys($format);
    }

    /**
     * Fetch content of a header field from message(s).
     *
     * @param  string  $field	The name of the header field to retreive
     * @param  mixed|null  $range	(optional)
     *
     * @return mixed <br>
     *  - (array)	Nested array
     *  - (object)	Error on failure
     * @access public
     */
    public function getHeaderField(string $field, mixed $range = null): mixed
    {
    	$fields = $this->cmdXHdr($field, $range);
    	if (Error::isError($fields)) {
    	    return $fields;
    	}

    	$expectSingle = $range === null
    	    || \is_int($range)
    	    || (\is_string($range) && ctype_digit($range))
    	    || (\is_string($range) && str_starts_with($range, '<') && str_ends_with($range, '>'));

    	if ($expectSingle) {
    	    return \count($fields) === 0 ? false : reset($fields);
    	}

    	return $fields;
    }

    /**
     * @param  mixed|null  $range	(optional) Experimental!
     *
     * @return mixed <br>
     *  - (array)
     *  - (object)	Error on failure
     * @access public
     * @since 1.3.0
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
     *
     * @param  mixed|null  $range	(optional)
     *
     * @return mixed <br>
     *  - (array)	Nested array of references
     *  - (object)	Error on failure
     * @access public
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
    	    if (Error::isError($references2)) {
    		// Ignore...
    	    } else {
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

    	$expectSingle = $range === null
    	    || \is_int($range)
    	    || (\is_string($range) && ctype_digit($range))
    	    || (\is_string($range) && str_starts_with($range, '<') && str_ends_with($range, '>'));

    	if ($expectSingle) {
    	    return \count($references) === 0 ? false : reset($references);
    	}

    	return $references;
    }

    /**
     * Number of articles in currently selected group
     *
     * @return mixed <br>
     *  - (string)	the number of article in group
     *  - (object)	Error on failure
     * @access public
     * @ignore
     */
    public function count(): mixed
    {
        return $this->_selectedGroupSummary['count'] ?? null;
    }

    /**
     * Maximum article number in currently selected group
     *
     * @return mixed <br>
     *  - (string)	the last article's number
     *  - (object)	Error on failure
     * @access public
     * @ignore
     */
    public function last(): mixed
    {
    	return $this->_selectedGroupSummary['last'] ?? null;
    }

    /**
     * Minimum article number in currently selected group
     *
     * @return mixed <br>
     *  - (string)	the first article's number
     *  - (object)	Error on failure
     * @access public
     * @ignore
     */
    public function first(): mixed
    {
    	return $this->_selectedGroupSummary['first'] ?? null;
    }

    /**
     * Currently selected group
     *
     * @return mixed <br>
     *  - (string)	group name
     *  - (object)	Error on failure
     * @access public
     * @ignore
     */
    public function group(): mixed
    {
    	return $this->_selectedGroupSummary['group'] ?? null;
    }

}
