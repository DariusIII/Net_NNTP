<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4 foldmethod=marker: */

/**
 *
 *
 * PHP versions 8.5 and above
 *
 * <pre>
 * +-----------------------------------------------------------------------+
 * |                                                                       |
 * | W3C® SOFTWARE NOTICE AND LICENSE                                      |
 * | http://www.w3.org/Consortium/Legal/2002/copyright-software-20021231   |
 * |                                                                       |
 * | This work (and included software, documentation such as READMEs,      |
 * | or other related items) is being provided by the copyright holders    |
 * | under the following license. By obtaining, using and/or copying       |
 * | this work, you (the licensee) agree that you have read, understood,   |
 * | and will comply with the following terms and conditions.              |
 * |                                                                       |
 * | Permission to copy, modify, and distribute this software and its      |
 * | documentation, with or without modification, for any purpose and      |
 * | without fee or royalty is hereby granted, provided that you include   |
 * | the following on ALL copies of the software and documentation or      |
 * | portions thereof, including modifications:                            |
 * |                                                                       |
 * | 1. The full text of this NOTICE in a location viewable to users       |
 * |    of the redistributed or derivative work.                           |
 * |                                                                       |
 * | 2. Any pre-existing intellectual property disclaimers, notices,       |
 * |    or terms and conditions. If none exist, the W3C Software Short     |
 * |    Notice should be included (hypertext is preferred, text is         |
 * |    permitted) within the body of any redistributed or derivative      |
 * |    code.                                                              |
 * |                                                                       |
 * | 3. Notice of any changes or modifications to the files, including     |
 * |    the date changes were made. (We recommend you provide URIs to      |
 * |    the location from which the code is derived.)                      |
 * |                                                                       |
 * | THIS SOFTWARE AND DOCUMENTATION IS PROVIDED "AS IS," AND COPYRIGHT    |
 * | HOLDERS MAKE NO REPRESENTATIONS OR WARRANTIES, EXPRESS OR IMPLIED,    |
 * | INCLUDING BUT NOT LIMITED TO, WARRANTIES OF MERCHANTABILITY OR        |
 * | FITNESS FOR ANY PARTICULAR PURPOSE OR THAT THE USE OF THE SOFTWARE    |
 * | OR DOCUMENTATION WILL NOT INFRINGE ANY THIRD PARTY PATENTS,           |
 * | COPYRIGHTS, TRADEMARKS OR OTHER RIGHTS.                               |
 * |                                                                       |
 * | COPYRIGHT HOLDERS WILL NOT BE LIABLE FOR ANY DIRECT, INDIRECT,        |
 * | SPECIAL OR CONSEQUENTIAL DAMAGES ARISING OUT OF ANY USE OF THE        |
 * | SOFTWARE OR DOCUMENTATION.                                            |
 * |                                                                       |
 * | The name and trademarks of copyright holders may NOT be used in       |
 * | advertising or publicity pertaining to the software without           |
 * | specific, written prior permission. Title to copyright in this        |
 * | software and any associated documentation will at all times           |
 * | remain with copyright holders.                                        |
 * |                                                                       |
 * +-----------------------------------------------------------------------+
 * </pre>
 *
 * @category   Net
 * @package    Net_NNTP
 * @author     Heino H. Gehlsen <heino@gehlsen.dk>
 * @copyright  2002-2017 Heino H. Gehlsen <heino@gehlsen.dk>. All Rights Reserved.
 * @license    http://www.w3.org/Consortium/Legal/2002/copyright-software-20021231 W3C® SOFTWARE NOTICE AND LICENSE
 * @version    SVN: $Id$
 * @link       http://pear.php.net/package/Net_NNTP
 * @see
 */

// Loaded via Composer autoload (classmap). Net_NNTP_Error and ResponseCode are resolved by the autoloader.

/**
 * Log level constant for debug messages (compatible with PEAR_LOG_DEBUG)
 */
if (!\defined('PEAR_LOG_DEBUG')) {
    define('PEAR_LOG_DEBUG', 7);
}


// {{{ constants

/**
 * Default host
 *
 * @access     public
 * @ignore
 */
	const NET_NNTP_PROTOCOL_CLIENT_DEFAULT_HOST = 'localhost';

/**
 * Default port
 *
 * @access     public
 * @ignore
 */
	const NET_NNTP_PROTOCOL_CLIENT_DEFAULT_PORT = '119';

// }}}
// {{{ Net_NNTP_Protocol_Client

/**
 * Low level NNTP Client
 *
 * Implements the client part of the NNTP standard acording to:
 *  - RFC 977,
 *  - RFC 2980,
 *  - RFC 850/1036, and
 *  - RFC 822/2822
 *
 * Each NNTP command is represented by a method: cmd*()
 *
 * WARNING: The Net_NNTP_Protocol_Client class is considered an internal class
 *          (and should therefore currently not be extended directly outside of
 *          the Net_NNTP package). Therefore its API is NOT required to be fully
 *          stable, for as long as such changes doesn't affect the public API of
 *          the Net_NNTP_Client class, which is considered stable.
 *
 * TODO:	cmdListActiveTimes()
 *      	cmdDistribPats()
 *
 * @category   Net
 * @package    Net_NNTP
 * @author     Heino H. Gehlsen <heino@gehlsen.dk>
 * @version    package: @package_version@ (@package_state@)
 * @version    api: @api_version@ (@api_state@)
 * @access     protected
 * @see        Net_NNTP_Client
 */
class Net_NNTP_Protocol_Client
{
    // {{{ properties

    /**
     * The socket resource being used to connect to the NNTP server.
     *
     * @var resource|null
     * @access protected
     */
    protected $_socket = null;

    /**
     * Contains the last recieved status response code and text
     *
     * @var array|null
     * @access protected
     */
    protected ?array $_currentStatusResponse = null;

    /**
     * @var object|null
     * @access protected
     */
    protected ?object $_logger = null;

    /**
     * Cached debug flag to avoid repeated _isMasked() calls
     *
     * @var bool
     * @access protected
     */
    protected bool $_debug = false;

    /**
    * Contains false on non-ssl connection and string when encrypted
    *
    * @var string|null
    * @access protected
    */
    protected ?string $_encryption = null;

    // }}}
    // {{{ constructor

    /**
     * Constructor
     *
     * @access public
     */
    public function __construct()
	{
    	$this->_socket = null;
    }

    // }}}
    // {{{ throwError()

    /**
     * Create and return an error object
     *
     * @param string $message Error message
     * @param int|null $code Error code
     * @param mixed $userInfo Additional error information
     * @return Net_NNTP_Error
     * @access protected
     */
    protected function throwError(string $message, ?int $code = null, mixed $userInfo = null): Net_NNTP_Error
    {
        return new Net_NNTP_Error($message, $code, $userInfo);
    }
    // }}}
    // {{{ getPackageVersion()

    /**
     *
     *
     * @access public
     */
    public function getPackageVersion(): string
    {
	return '@package_version@';
    }

    // }}}
    // {{{ getApiVersion()

    /**
     *
     *
     * @access public
     */
    public function getApiVersion(): string
    {
	return '@api_version@';
    }

    // }}}
    // {{{ setLogger()

    /**
     *
     *
     * @param  object  $logger
     *
     * @access protected
     */
    protected function setLogger(object $logger): void
    {
        $this->_logger = $logger;
        $this->_debug = $logger->_isMasked(PEAR_LOG_DEBUG);
    }

    // }}}
    // {{{ setDebug()

    /**
     * @deprecated
     */
    function setDebug($debug = true): void
    {
    	trigger_error('You are using deprecated API v1.0 in Net_NNTP_Protocol_Client: setDebug() ! Debugging in now automatically handled when a logger is given.', E_USER_NOTICE);
    }
    // }}}
    // {{{ _clearOpensslErrors()

    /**
    * Clears ssl errors from the openssl error stack
    */
    public function _clearOpensslErrors(): void
    {
        if ($this->_encryption === null) {
            return;
        }

        while (($message = openssl_error_string()) !== false) {
            if ($this->_debug) {
                $this->_logger->debug('OpenSSL: ' . $message);
            }
        }
    }

    // }}}
    // {{{ _sendCommand()

    /**
     * Send command
     *
     * Send a command to the server. A carriage return / linefeed (CRLF) sequence
     * will be appended to each command string before it is sent to the IMAP server.
     *
     * @param  string  $cmd The command to launch, ie: "ARTICLE 1004853"
     *
     * @return mixed (int) response code on success or (object) pear_error on failure
     * @access protected
     */
    protected function _sendCommand(string $cmd): mixed
    {
        if (\strlen($cmd) > 510) {
            return $this->throwError('Failed writing to socket! (Command to long - max 510 chars)');
        }

        if (strpbrk($cmd, "\r\n") !== false) {
            if ($this->_debug) {
                $this->_logger->debug('Illegal character in command: contains carriage return/new line');
            }

            return $this->throwError("Illegal character(s) in NNTP command!");
        }

    	if (!$this->_isConnected()) {
            return $this->throwError('Failed to write to socket! (connection lost!)');
        }

    	$written = @fwrite($this->_socket, $cmd . "\r\n");
        if ($written === false) {
            return $this->throwError('Failed to write to socket!');
        }

    	if ($this->_debug) {
    	    $this->_logger->debug('C: ' . $cmd);
        }

    	return $this->_getStatusResponse();
    }

    // }}}
    // {{{ _getStatusResponse()

    /**
     * Get servers status response after a command.
     *
     * @return mixed (int) statuscode on success or (object) pear_error on failure
     * @access protected
     */
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

    	if ($this->_debug) {
    	    $this->_logger->debug('S: ' . rtrim($response, "\r\n"));
        }

    	$response = ltrim($response);

        $this->_currentStatusResponse = [
            (int) substr($response, 0, 3),
            (string) rtrim(substr($response, 4)),
        ];

    	return $this->_currentStatusResponse[0];
    }

    // }}}
    // {{{ _getTextResponse()

    /**
     * Retrieve textural data
     *
     * Get data until a line with only a '.' in it is read and return data.
     *
     * @return mixed (array) text response on success or (object) pear_error on failure
     * @access protected
     */
    protected function _getTextResponse(): mixed
    {
        $data = [];
        $line = '';
        $debug = $this->_debug;

        $this->_clearOpensslErrors();

        while (!feof($this->_socket)) {

            $recieved = @fgets($this->_socket, 8192);

            if ($recieved === false) {
                $this->_clearOpensslErrors();

				$meta = stream_get_meta_data($this->_socket);
				if ($meta['timed_out']) {
					return $this->throwError('Connection timed out', null);
				}

                return $this->throwError('Failed to read line from socket.', null);
    	    }

            $line .= $recieved;

            if (!str_ends_with($line, "\r\n") || \strlen($line) < 2) {
                continue;
            }

            $line = substr($line, 0, -2);

            if ($line === '.') {
	            $this->_logger?->debug('T: .');
                $this->_clearOpensslErrors();
                return $data;
            }

            if ($line[0] === '.' && ($line[1] ?? '') === '.') {
                $line = substr($line, 1);
            }

    	    if ($debug) {
    	    	$this->_logger->debug('T: ' . $line);
    	    }

            $data[] = $line;
            $line = '';
        }

        $this->_clearOpensslErrors();
	    $this->_logger?->warning('Broke out of reception loop! This souldn\'t happen unless connection has been lost?');

    	return $this->throwError('End of stream! Connection lost?', null);
    }

    // }}}
    // {{{ _sendText()

    /**
     *
     *
     * @access protected
     */
    protected function _sendArticle($article): void
    {
    	/* data should be in the format specified by RFC850 */

    	switch (true) {
    	case \is_string($article):
    	    @fwrite($this->_socket, str_replace("\n.", "\n..", $article) . "\r\n.\r\n");

    	    if ($this->_debug) {
    	        foreach (explode("\r\n", $article) as $line) {
    		    $this->_logger->debug('D: ' . $line);
    	        }
    	    	$this->_logger->debug('D: .');
    	    }
	    break;

    	case \is_array($article):
    	    $header = reset($article);
    	    $body = next($article);

    	    @fwrite($this->_socket, str_replace("\n.", "\n..", $header) . "\r\n");

    	    if ($this->_debug) {
    	        foreach (explode("\r\n", $header) as $line) {
    	    	    $this->_logger->debug('D: ' . $line);
    	    	}
    	    }

    	    @fwrite($this->_socket, str_replace("\n.", "\n..", $body) . "\r\n.\r\n");

    	    if ($this->_debug) {
    	        foreach (explode("\r\n", $body) as $line) {
    	    	    $this->_logger->debug('D: ' . $line);
    	    	}
    	        $this->_logger->debug('D: .');
    	    }
	    break;

	default:
    	    $this->throwError('Ups...', null, null);

		return;
    	}
    }

    // }}}
    // {{{ _currentStatusResponse()

    /**
     *
     *
     * @return string status text
     * @access protected
     */
    protected function _currentStatusResponse(): string
    {
    	return $this->_currentStatusResponse[1];
    }

    // }}}
    // {{{ _handleUnexpectedResponse()

    /**
     *
     *
     * @param  int|null  $code Status code number
     * @param  string|null  $text Status text
     *
     * @return mixed
     * @access protected
     */
    protected function _handleUnexpectedResponse(?int $code = null, ?string $text = null)
    {
    	if ($code === null) {
    	    $code = $this->_currentStatusResponse[0];
	}

    	if ($text === null) {
    	    $text = $this->_currentStatusResponse();
	}

	    if ($code === ResponseCode::NotPermitted->value) {
		    return $this->throwError('Command not permitted / Access restriction / Permission denied', $code, $text);
	    }

	    return $this->throwError("Unexpected response: '$text'", $code, $text);
    }

    // }}}

/* Session administration commands */

    // {{{ Connect()

    /**
     * Connect to a NNTP server
     *
     * @param  string|null  $host	(optional) The address of the NNTP-server to connect to, defaults to 'localhost'.
     * @param  mixed|null  $encryption	(optional)
     * @param  int|null  $port	(optional) The port number to connect to, defaults to 119.
     * @param  int|null  $timeout	(optional)
     *
     * @return mixed (bool) on success (true when posting allowed, otherwise false) or (object) pear_error on failure
     * @access protected
     */
    protected function connect(?string $host = null, mixed $encryption = null, ?int $port = null, ?int $timeout = null): mixed
    {
        if ($this->_isConnected() ) {
    	    return $this->throwError('Already connected, disconnect first!', null);
    	}

    	// v1.0.x API
    	if (\is_int($encryption)) {
	    trigger_error('You are using deprecated API v1.0 in Net_NNTP_Protocol_Client: connect() !', E_USER_NOTICE);
    	    $port = $encryption;
	    $encryption = false;
    	}

    	if (\is_null($host)) {
    	    $host = 'localhost';
    	}

    	switch ($encryption) {
	    case null:
	    case false:
		$transport = 'tcp';
    	    	$port = \is_null($port) ? 119 : $port;
		break;
	    case 'ssl':
	    case 'tls':
		$transport = $encryption;
    	    	$port = \is_null($port) ? 563 : $port;
	        $this->_encryption = $encryption;
		break;
	    default:
    	    	throw new \InvalidArgumentException('$encryption parameter must be either tcp, tls or ssl.');
    	}

    	if (\is_null($timeout)) {
    	    $timeout = 15;
    	}

    	$R = @stream_socket_client($transport . '://' . $host . ':' . $port, $errno, $errstr, $timeout);
    	if ($R === false) {
    	    if ($this->_logger) {
    	        $this->_logger->notice("Connection to $transport://$host:$port failed.");
    	    }
    	    return $R;
    	}

    	$this->_socket = $R;

    	if ($this->_logger) {
    	    $this->_logger->info("Connection to $transport://$host:$port has been established.");
    	}

		stream_set_timeout($this->_socket, $timeout);

    	$response = $this->_getStatusResponse();
    	if (Net_NNTP_Error::isError($response)) {
    	    return $response;
        }

        switch ($response) {
    	    case ResponseCode::ReadyPostingAllowed->value:
    	        return true;
    	    case ResponseCode::ReadyPostingProhibited->value:
    	    	if ($this->_logger) {
    	    	    $this->_logger->info('Posting not allowed!');
    	    	}
    	    	return false;
    	    case 400:
    	    	return $this->throwError('Server refused connection', $response, $this->_currentStatusResponse());
    	    case ResponseCode::NotPermitted->value:
    	    	return $this->throwError('Server refused connection', $response, $this->_currentStatusResponse());
    	    default:
    	    	return $this->_handleUnexpectedResponse($response);
    	}
    }

    // }}}
    // {{{ disconnect()

    /**
     * alias for cmdQuit()
     *
     * @access protected
     */
    protected function disconnect()
    {
    	return $this->cmdQuit();
    }

    // }}}
    // {{{ cmdCapabilities()

    /**
     * Returns servers capabilities
     *
     * @return mixed (array) list of capabilities on success or (object) pear_error on failure
     * @access protected
     */
    protected function cmdCapabilities(): mixed
    {
        $response = $this->_sendCommand('CAPABILITIES');
        if (Net_NNTP_Error::isError($response)) {
            return $response;
        }

	    if ($response === ResponseCode::CapabilitiesFollow->value) {
		    $data = $this->_getTextResponse();
		    if (Net_NNTP_Error::isError($data)) {
			    return $data;
		    }

		    return $data;
	    }

	    return $this->_handleUnexpectedResponse($response);
    }

    // }}}
    // {{{ cmdModeReader()

    /**
     *
     *
     * @return mixed (bool) true when posting allowed, false when postind disallowed or (object) pear_error on failure
     * @access protected
     */
    protected function cmdModeReader(): mixed
    {
        $response = $this->_sendCommand('MODE READER');
        if (Net_NNTP_Error::isError($response)) {
            return $response;
        }

    	switch ($response) {
            case ResponseCode::ReadyPostingAllowed->value:
    	    	return true;
    	    case ResponseCode::ReadyPostingProhibited->value:
    	    	if ($this->_logger) {
    	    	    $this->_logger->info('Posting not allowed!');
    	    	}
    	    	return false;
    	    case ResponseCode::NotPermitted->value:
    	    	return $this->throwError('Connection being closed, since service so permanently unavailable', $response, $this->_currentStatusResponse());
    	    default:
    	    	return $this->_handleUnexpectedResponse($response);
    	}
    }

    // }}}
    // {{{ cmdQuit()

    /**
     * Disconnect from the NNTP server
     *
     * @return mixed (bool) true on success or (object) pear_error on failure
     * @access protected
     */
    protected function cmdQuit(): mixed
    {
    	$response = $this->_sendCommand('QUIT');
        if (Net_NNTP_Error::isError($response)) {
            return $response;
    	}

	    if ($response === 205) {
		    if ($this->_isConnected()) {
			    fclose($this->_socket);
		    }

		    $this->_socket = null;
		    $this->_logger?->info('Connection closed.');

		    return true;
	    }

	    return $this->_handleUnexpectedResponse($response);
    }

    // }}}

/* */

    // {{{ cmdStartTLS()

    /**
     *
     *
     * @return mixed (bool) on success or (object) pear_error on failure
     * @access protected
     */
    protected function cmdStartTLS(): mixed
    {
        $response = $this->_sendCommand('STARTTLS');
        if (Net_NNTP_Error::isError($response)) {
            return $response;
        }

    	switch ($response) {
    	    case 382:
    	    	$encrypted = stream_socket_enable_crypto($this->_socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
    	    	switch (true) {
    	    	    case $encrypted === true:
    	    	    	if ($this->_logger) {
    	    	    	    $this->_logger->info('TLS encryption started.');
    	    	    	}
    	    	    	$this->_encryption = 'tls';
    	    	    	return true;
    	    	    case $encrypted === false:
    	    	    	$this->_logger?->info('TLS encryption failed.');
    	    	    	return $this->throwError('Could not initiate TLS negotiation', $response, $this->_currentStatusResponse());
    	    	    case \is_int($encrypted):
    	    	    	return $this->throwError('', $response, $this->_currentStatusResponse());
    	    	    default:
    	    	    	return $this->throwError('Internal error - unknown response from stream_socket_enable_crypto()', $response, $this->_currentStatusResponse());
    	    	}
    	    case 580:
    	    	return $this->throwError('', $response, $this->_currentStatusResponse());
    	    default:
    	    	return $this->_handleUnexpectedResponse($response);
    	}
    }

    // }}}

/* Article posting and retrieval */

    /* Group and article selection */

    // {{{ cmdGroup()

    /**
     * Selects a newsgroup (issue a GROUP command to the server)
     *
     * @param  string  $newsgroup The newsgroup name
     *
     * @return mixed (array) groupinfo on success or (object) pear_error on failure
     * @access protected
     */
    protected function cmdGroup(string $newsgroup): mixed
    {
        $response = $this->_sendCommand('GROUP '.$newsgroup);
        if (Net_NNTP_Error::isError($response)) {
            return $response;
        }

    	switch ($response) {
    	    case ResponseCode::GroupSelected->value:
    	    	$response_arr = explode(' ', $this->_currentStatusResponse());

		        $this->_logger?->info('Group selected: '.$response_arr[3]);

    	    	return ['group' => $response_arr[3],
    	                'first' => $response_arr[1],
    	    	        'last'  => $response_arr[2],
    	                'count' => $response_arr[0]];
    	    case ResponseCode::NoSuchGroup->value:
    	    	return $this->throwError('No such news group', $response, $this->_currentStatusResponse());
    	    default:
    	    	return $this->_handleUnexpectedResponse($response);
    	}
    }

    // }}}
    // {{{ cmdListgroup()

    /**
     *
     *
     * @param  string|null  $newsgroup
     * @param  mixed|null  $range
     *
     * @return mixed (array) on success or (object) pear_error on failure
     * @access protected
     */
    protected function cmdListgroup(?string $newsgroup = null, mixed $range = null): mixed
    {
        if (\is_null($newsgroup)) {
    	    $command = 'LISTGROUP';
    	} else {
    	    $command = \is_null($range) ? 'LISTGROUP ' . $newsgroup : 'LISTGROUP ' . $newsgroup . ' ' . $range;
        }

        $response = $this->_sendCommand($command);
        if (Net_NNTP_Error::isError($response)){
            return $response;
        }

    	switch ($response) {
    	    case ResponseCode::GroupSelected->value:

    	    	$articles = $this->_getTextResponse();
    	        if (Net_NNTP_Error::isError($articles)) {
    	            return $articles;
    	        }

    	        $response_arr = explode(' ', $this->_currentStatusResponse(), 4);

    	    	if (!is_numeric($response_arr[0]) || !is_numeric($response_arr[1]) || !is_numeric($response_arr[2]) || empty($response_arr[3])) {
    	    	    return ['group'    => null,
    	        	    'first'    => null,
    	    	    	    'last'     => null,
    	    	    	    'count'    => null,
    	    	    	    'articles' => $articles];
		}

    	    	return ['group'    => $response_arr[3],
    	                'first'    => $response_arr[1],
    	    	        'last'     => $response_arr[2],
    	    	        'count'    => $response_arr[0],
    	    	        'articles' => $articles];
    	    case ResponseCode::NoGroupSelected->value:
    	    	return $this->throwError('Not currently in newsgroup', $response, $this->_currentStatusResponse());
    	    case 502:
    	    	return $this->throwError('No permission', $response, $this->_currentStatusResponse());
    	    default:
    	    	return $this->_handleUnexpectedResponse($response);
    	}
    }

    // }}}
    // {{{ cmdLast()

    /**
     *
     *
     * @return mixed (array) or (string) or (int) or (object) pear_error on failure
     * @access protected
     */
    protected function cmdLast(): mixed
    {
        $response = $this->_sendCommand('LAST');
        if (Net_NNTP_Error::isError($response)) {
            return $response;
        }

    	switch ($response) {
    	    case ResponseCode::ArticleSelected->value:
    	    	$response_arr = explode(' ', $this->_currentStatusResponse());

    	    	if ($this->_logger) {
    	    	    $this->_logger->info('Selected previous article: ' . $response_arr[0] .' - '. $response_arr[1]);
    	    	}

    	    	return [$response_arr[0], (string) $response_arr[1]];
    	    case ResponseCode::NoGroupSelected->value:
    	    	return $this->throwError('No newsgroup has been selected', $response, $this->_currentStatusResponse());
    	    case ResponseCode::NoArticleSelected->value:
    	    	return $this->throwError('No current article has been selected', $response, $this->_currentStatusResponse());
    	    case ResponseCode::NoPreviousArticle->value:
    	    	return $this->throwError('No previous article in this group', $response, $this->_currentStatusResponse());
    	    default:
    	    	return $this->_handleUnexpectedResponse($response);
    	}
    }

    // }}}
    // {{{ cmdNext()

    /**
     *
     *
     * @return mixed (array) or (string) or (int) or (object) pear_error on failure
     * @access protected
     */
    protected function cmdNext(): mixed
    {
        $response = $this->_sendCommand('NEXT');
        if (Net_NNTP_Error::isError($response)) {
            return $response;
        }

    	switch ($response) {
    	    case ResponseCode::ArticleSelected->value:
    	    	$response_arr = explode(' ', $this->_currentStatusResponse());

		        $this->_logger?->info('Selected next article: '.$response_arr[0].' - '.$response_arr[1]);

    	    	return [$response_arr[0], (string) $response_arr[1]];
    	    case ResponseCode::NoGroupSelected->value:
    	    	return $this->throwError('No newsgroup has been selected', $response, $this->_currentStatusResponse());
    	    case ResponseCode::NoArticleSelected->value:
    	    	return $this->throwError('No current article has been selected', $response, $this->_currentStatusResponse());
    	    case ResponseCode::NoNextArticle->value:
    	    	return $this->throwError('No next article in this group', $response, $this->_currentStatusResponse());
    	    default:
    	    	return $this->_handleUnexpectedResponse($response);
    	}
    }

    // }}}

    /* Retrieval of articles and article sections */

    // {{{ cmdArticle()

    /**
     * Get an article from the currently open connection.
     *
     * @param  mixed|null  $article Either a message-id or a message-number of the article to fetch. If null or '', then use current article.
     *
     * @return mixed (array) article on success or (object) pear_error on failure
     * @access protected
     */
    protected function cmdArticle(mixed $article = null): mixed
    {
        $command = \is_null($article) ? 'ARTICLE' : 'ARTICLE ' . $article;

        $response = $this->_sendCommand($command);
        if (Net_NNTP_Error::isError($response)) {
            return $response;
        }

    	switch ($response) {
    	    case ResponseCode::ArticleFollows->value:
    	    	$data = $this->_getTextResponse();
    	    	if (Net_NNTP_Error::isError($data)) {
    	    	    return $data;
    	    	}

    	    	if ($this->_logger) {
    	    	    $this->_logger->info($article === null ? 'Fetched current article' : 'Fetched article: '.$article);
    	    	}
    	    	return $data;
    	    case ResponseCode::NoGroupSelected->value:
    	    	return $this->throwError('No newsgroup has been selected', $response, $this->_currentStatusResponse());
    	    case ResponseCode::NoArticleSelected->value:
    	    	return $this->throwError('No current article has been selected', $response, $this->_currentStatusResponse());
    	    case ResponseCode::NoSuchArticleNumber->value:
    	    	return $this->throwError('No such article number in this group', $response, $this->_currentStatusResponse());
    	    case ResponseCode::NoSuchArticleId->value:
    	    	return $this->throwError('No such article found', $response, $this->_currentStatusResponse());
    	    default:
    	    	return $this->_handleUnexpectedResponse($response);
    	}
    }

    // }}}
    // {{{ cmdHead()

    /**
     * Get the headers of an article from the currently open connection.
     *
     * @param  mixed|null  $article Either a message-id or a message-number of the article to fetch the headers from. If null or '', then use current article.
     *
     * @return mixed (array) headers on success or (object) pear_error on failure
     * @access protected
     */
    protected function cmdHead(mixed $article = null): mixed
    {
        $command = \is_null($article) ? 'HEAD' : 'HEAD ' . $article;

        $response = $this->_sendCommand($command);
        if (Net_NNTP_Error::isError($response)) {
            return $response;
        }

    	switch ($response) {
    	    case ResponseCode::HeadFollows->value:
    	    	$data = $this->_getTextResponse();
    	    	if (Net_NNTP_Error::isError($data)) {
    	    	    return $data;
    	    	}

		        $this->_logger?->info($article === null ? 'Fetched current article header' : 'Fetched article header for article: '.$article);

    	        return $data;
    	    case ResponseCode::NoGroupSelected->value:
    	    	return $this->throwError('No newsgroup has been selected', $response, $this->_currentStatusResponse());
    	    case ResponseCode::NoArticleSelected->value:
    	    	return $this->throwError('No current article has been selected', $response, $this->_currentStatusResponse());
    	    case ResponseCode::NoSuchArticleNumber->value:
    	    	return $this->throwError('No such article number in this group', $response, $this->_currentStatusResponse());
    	    case ResponseCode::NoSuchArticleId->value:
    	    	return $this->throwError('No such article found', $response, $this->_currentStatusResponse());
    	    default:
    	    	return $this->_handleUnexpectedResponse($response);
    	}
    }

    // }}}
    // {{{ cmdBody()

    /**
     * Get the body of an article from the currently open connection.
     *
     * @param  mixed|null  $article Either a message-id or a message-number of the article to fetch the body from. If null or '', then use current article.
     *
     * @return mixed (array) body on success or (object) pear_error on failure
     * @access protected
     */
    protected function cmdBody(mixed $article = null): mixed
    {
        $command = \is_null($article) ? 'BODY' : 'BODY ' . $article;

        $response = $this->_sendCommand($command);
        if (Net_NNTP_Error::isError($response)) {
            return $response;
        }

    	switch ($response) {
    	    case ResponseCode::BodyFollows->value:
    	    	$data = $this->_getTextResponse();
    	    	if (Net_NNTP_Error::isError($data)) {
    	    	    return $data;
    	    	}

		        $this->_logger?->info($article === null ? 'Fetched current article body' : 'Fetched article body for article: '.$article);

    	        return $data;
    	    case ResponseCode::NoGroupSelected->value:
    	    	return $this->throwError('No newsgroup has been selected', $response, $this->_currentStatusResponse());
    	    case ResponseCode::NoArticleSelected->value:
    	    	return $this->throwError('No current article has been selected', $response, $this->_currentStatusResponse());
    	    case ResponseCode::NoSuchArticleNumber->value:
    	    	return $this->throwError('No such article number in this group', $response, $this->_currentStatusResponse());
    	    case ResponseCode::NoSuchArticleId->value:
    	    	return $this->throwError('No such article found', $response, $this->_currentStatusResponse());
    	    default:
    	    	return $this->_handleUnexpectedResponse($response);
    	}
    }

    // }}}
    // {{{ cmdStat

    /**
     *
     *
     * @param  mixed|null  $article
     *
     * @return mixed (array) or (string) or (int) or (object) pear_error on failure
     * @access protected
     */
    protected function cmdStat(mixed $article = null): mixed
    {
        $command = \is_null($article) ? 'STAT' : 'STAT ' . $article;

        $response = $this->_sendCommand($command);
        if (Net_NNTP_Error::isError($response)) {
            return $response;
        }

    	switch ($response) {
    	    case ResponseCode::ArticleSelected->value:
    	    	$response_arr = explode(' ', $this->_currentStatusResponse());

		        $this->_logger?->info('Selected article: '.$response_arr[0].' - '.$response_arr[1]);

    	    	return [$response_arr[0], (string) $response_arr[1]];
    	    case ResponseCode::NoGroupSelected->value:
    	    	return $this->throwError('No newsgroup has been selected', $response, $this->_currentStatusResponse());
    	    case ResponseCode::NoSuchArticleNumber->value:
    	    	return $this->throwError('No such article number in this group', $response, $this->_currentStatusResponse());
    	    case ResponseCode::NoSuchArticleId->value:
    	    	return $this->throwError('No such article found', $response, $this->_currentStatusResponse());
    	    default:
    	    	return $this->_handleUnexpectedResponse($response);
    	}
    }

    // }}}

    /* Article posting */

    // {{{ cmdPost()

    /**
     * Post an article to a newsgroup.
     *
     * @return mixed (bool) true on success or (object) pear_error on failure
     * @access protected
     */
    protected function cmdPost(): mixed
    {
    	$response = $this->_sendCommand('POST');
    	if (Net_NNTP_Error::isError($response)) {
    	    return $response;
        }

	    return match ($response) {
		    ResponseCode::PostingSend->value => true,
		    ResponseCode::PostingProhibited->value => $this->throwError('Posting not allowed', $response,
			    $this->_currentStatusResponse()),
		    default => $this->_handleUnexpectedResponse($response),
	    };
    }

    // }}}
    // {{{ cmdPost2()

    /**
     * Post an article to a newsgroup.
     *
     * @param mixed $article (string/array)
     *
     * @return mixed (bool) true on success or (object) pear_error on failure
     * @access protected
     */
    protected function cmdPost2(mixed $article): mixed
    {
    	/* should be presented in the format specified by RFC850 */

    	$this->_sendArticle($article);

    	$response = $this->_getStatusResponse();
    	if (Net_NNTP_Error::isError($response)) {
    	    return $response;
    	}

	    return match ($response) {
		    ResponseCode::PostingSuccess->value => true,
		    ResponseCode::PostingFailure->value => $this->throwError('Posting failed', $response,
			    $this->_currentStatusResponse()),
		    default => $this->_handleUnexpectedResponse($response),
	    };
    }

    // }}}
    // {{{ cmdIhave()

    /**
     *
     *
     * @param  string  $id
     *
     * @return mixed (bool) true on success or (object) pear_error on failure
     * @access protected
     */
    protected function cmdIhave(string $id): mixed
    {
    	$response = $this->_sendCommand('IHAVE ' . $id);
    	if (Net_NNTP_Error::isError($response)) {
    	    return $response;
        }

	    return match ($response) {
		    ResponseCode::TransferSend->value => true,
		    ResponseCode::TransferUnwanted->value => $this->throwError('Article not wanted', $response,
			    $this->_currentStatusResponse()),
		    ResponseCode::TransferFailure->value => $this->throwError('Transfer not possible; try again later',
			    $response, $this->_currentStatusResponse()),
		    default => $this->_handleUnexpectedResponse($response),
	    };
    }

    // }}}
    // {{{ cmdIhave2()

    /**
     *
     *
     * @param mixed $article (string/array)
     *
     * @return mixed (bool) true on success or (object) pear_error on failure
     * @access protected
     */
    protected function cmdIhave2(mixed $article): mixed
    {
    	/* should be presented in the format specified by RFC850 */

    	$this->_sendArticle($article);

    	$response = $this->_getStatusResponse();
    	if (Net_NNTP_Error::isError($response)) {
    	    return $response;
    	}

	    return match ($response) {
		    ResponseCode::TransferSuccess->value => true,
		    ResponseCode::TransferFailure->value => $this->throwError('Transfer not possible; try again later',
			    $response, $this->_currentStatusResponse()),
		    ResponseCode::TransferRejected->value => $this->throwError('Transfer rejected; do not retry',
			    $response, $this->_currentStatusResponse()),
		    default => $this->_handleUnexpectedResponse($response),
	    };
    }

    // }}}

/* Information commands */

    // {{{ cmdDate()

    /**
     * Get the date from the news server format of returned date
     *
     * @return mixed (string) 'YYYYMMDDhhmmss' / (int) timestamp on success or (object) pear_error on failure
     * @access protected
     */
    protected function cmdDate(): mixed
    {
        $response = $this->_sendCommand('DATE');
        if (Net_NNTP_Error::isError($response)){
            return $response;
        }

	    return match ($response) {
		    ResponseCode::ServerDate->value => $this->_currentStatusResponse(),
		    default => $this->_handleUnexpectedResponse($response),
	    };
    }
    // }}}
    // {{{ cmdHelp()

    /**
     * Returns the server's help text
     *
     * @return mixed (array) help text on success or (object) pear_error on failure
     * @access protected
     */
    protected function cmdHelp(): mixed
    {
        $response = $this->_sendCommand('HELP');
        if (Net_NNTP_Error::isError($response)) {
            return $response;
        }

	    if ($response === ResponseCode::HelpFollows->value) {
		    $data = $this->_getTextResponse();
		    if (Net_NNTP_Error::isError($data)) {
			    return $data;
		    }

		    return $data;
	    }

	    return $this->_handleUnexpectedResponse($response);
    }

    // }}}
    // {{{ cmdNewgroups()

    /**
     * Fetches a list of all newsgroups created since a specified date.
     *
     * @param  int  $time Last time you checked for groups (timestamp).
     * @param  string|null  $distributions (deprecated in rfc draft)
     *
     * @return mixed (array) nested array with informations about existing newsgroups on success or (object) pear_error on failure
     * @access protected
     */
    protected function cmdNewgroups(int $time, ?string $distributions = null): mixed
    {
	$date = gmdate('ymd His', $time);

        if (\is_null($distributions)) {
    	    $command = 'NEWGROUPS ' . $date . ' GMT';
    	} else {
    	    $command = 'NEWGROUPS ' . $date . ' GMT <' . $distributions . '>';
        }

        $response = $this->_sendCommand($command);
        if (Net_NNTP_Error::isError($response)){
            return $response;
        }

	    if ($response === ResponseCode::NewGroupsFollow->value) {
		    $data = $this->_getTextResponse();
		    if (Net_NNTP_Error::isError($data)) {
			    return $data;
		    }

		    $groups = [];
		    foreach ($data as $line) {
			    $arr = explode(' ', $line);

			    $group = [
				    'group' => $arr[0], 'last' => $arr[1], 'first' => $arr[2], 'posting' => $arr[3]
			    ];

			    $groups[$group['group']] = $group;
		    }

		    return $groups;
	    }

	    return $this->_handleUnexpectedResponse($response);
    }

	// }}}
    // {{{ cmdNewnews()

    /**
     *
     *
     * @param  int  $time
     * @param mixed $newsgroups (string or array of strings)
     * @param  mixed|null  $distribution (string or array of strings)
     *
     * @return mixed
     * @access protected
     */
    protected function cmdNewnews(int $time, mixed $newsgroups, mixed $distribution = null): mixed
    {
        $date = gmdate('ymd His', $time);

    	if (\is_array($newsgroups)) {
    	    $newsgroups = implode(',', $newsgroups);
    	}

        if (\is_null($distribution)) {
    	    $command = 'NEWNEWS ' . $newsgroups . ' ' . $date . ' GMT';
    	} else {
    	    if (\is_array($distribution)) {
    		$distribution = implode(',', $distribution);
    	    }

    	    $command = 'NEWNEWS ' . $newsgroups . ' ' . $date . ' GMT <' . $distribution . '>';
        }

        $response = $this->_sendCommand($command);
        if (Net_NNTP_Error::isError($response)){
            return $response;
        }

	    if ($response === ResponseCode::NewArticlesFollow->value) {
		    $data = $this->_getTextResponse();
		    if (Net_NNTP_Error::isError($data)) {
			    return $data;
		    }

		    return $data;
	    }

	    return $this->_handleUnexpectedResponse($response);
    }

    // }}}

    /* The LIST commands */

    // {{{ cmdList()

    /**
     * Fetches a list of all avaible newsgroups
     *
     * @return mixed (array) nested array with information about existing newsgroups on success or (object) pear_error on failure
     * @access protected
     */
    protected function cmdList(): mixed
    {
        $response = $this->_sendCommand('LIST');
        if (Net_NNTP_Error::isError($response)){
            return $response;
        }

	    if ($response === ResponseCode::GroupsFollow->value) {
		    $data = $this->_getTextResponse();
		    if (Net_NNTP_Error::isError($data)) {
			    return $data;
		    }

		    $groups = [];
		    foreach ($data as $line) {
			    $arr = explode(' ', $line);

			    $group = [
				    'group' => $arr[0], 'last' => $arr[1], 'first' => $arr[2], 'posting' => $arr[3]
			    ];

			    $groups[$group['group']] = $group;
		    }

		    return $groups;
	    }

	    return $this->_handleUnexpectedResponse($response);
    }

	// }}}
    // {{{ cmdListActive()

    /**
     * Fetches a list of all avaible newsgroups
     *
     * @param  string|null  $wildmat
     *
     * @return mixed (array) nested array with information about existing newsgroups on success or (object) pear_error on failure
     * @access protected
     */
    protected function cmdListActive(?string $wildmat = null): mixed
    {
        $command = \is_null($wildmat) ? 'LIST ACTIVE' : 'LIST ACTIVE ' . $wildmat;

        $response = $this->_sendCommand($command);
        if (Net_NNTP_Error::isError($response)){
            return $response;
        }

	    if ($response === ResponseCode::GroupsFollow->value) {
		    $data = $this->_getTextResponse();
		    if (Net_NNTP_Error::isError($data)) {
			    return $data;
		    }

		    $groups = [];
		    foreach ($data as $line) {
			    $arr = explode(' ', $line);

			    $groups[$arr[0]] = [
				    'group' => $arr[0], 'last' => $arr[1], 'first' => $arr[2],
			    ];
		    }

		    $this->_logger?->info('Fetched list of available groups');

		    return $groups;
	    }

	    return $this->_handleUnexpectedResponse($response);
    }

	// }}}
    // {{{ cmdListNewsgroups()

    /**
     * Fetches a list of (all) avaible newsgroup descriptions.
     *
     * @param  string|null  $wildmat Wildmat of the groups, that is to be listed, defaults to null;
     *
     * @return mixed (array) nested array with description of existing newsgroups on success or (object) pear_error on failure
     * @access protected
     */
    protected function cmdListNewsgroups(?string $wildmat = null): mixed
    {
        $command = \is_null($wildmat) ? 'LIST NEWSGROUPS' : 'LIST NEWSGROUPS ' . $wildmat;

        $response = $this->_sendCommand($command);
        if (Net_NNTP_Error::isError($response)){
            return $response;
        }

    	switch ($response) {
    	    case ResponseCode::GroupsFollow->value:
    	    	$data = $this->_getTextResponse();
    	        if (Net_NNTP_Error::isError($data)) {
    	            return $data;
    	        }

    	    	$groups = [];

    	        foreach ($data as $line) {
    	            $pos = strpos($line, "\t") ?: strpos($line, ' ');
    	            if ($pos !== false) {
    	    	        $groups[substr($line, 0, $pos)] = ltrim(substr($line, $pos + 1));
    	    	    } else {
		                $this->_logger?->warning("Recieved non-standard line: '$line'");
    	    	    }
    	        }

		        $this->_logger?->info('Fetched group descriptions');

    	        return $groups;
    	    case 503:
    	    	return $this->throwError('Internal server error, function not performed', $response, $this->_currentStatusResponse());
    	    default:
    	    	return $this->_handleUnexpectedResponse($response);
    	}
    }

    // }}}

/* Article field access commands */

    // {{{ cmdOver()

	/**
	 * Fetch message header from message number $first until $last
	 *
	 * The format of the returned array is:
	 * $messages[][header_name]
	 *
	 * @param  string|null  $range  articles to fetch
	 *
	 * @return mixed (array) nested array of message and there headers on success or (object) pear_error on failure
	 * @access protected
	 */
    protected function cmdOver(?string $range = null): mixed
    {
        $command = \is_null($range) ? 'OVER' : 'OVER ' . $range;

        $response = $this->_sendCommand($command);
        if (Net_NNTP_Error::isError($response)){
            return $response;
        }

	    switch ($response) {
	        case ResponseCode::OverviewFollows->value:
	    	    $data = $this->_getTextResponse();
	            if (Net_NNTP_Error::isError($data)) {
	                return $data;
	            }

	            foreach ($data as $key => $value) {
	                $data[$key] = explode("\t", $value);
	            }

		        $this->_logger?->info('Fetched overview '.($range === null ? 'for current article' : 'for range: '.$range));

    	    	return $data;
    	    case ResponseCode::NoGroupSelected->value:
    	    	return $this->throwError('No news group current selected', $response, $this->_currentStatusResponse());
    	    case ResponseCode::NoArticleSelected->value:
    	    	return $this->throwError('No article(s) selected', $response, $this->_currentStatusResponse());
    	    case ResponseCode::NoSuchArticleNumber->value:
    	    	return $this->throwError('No articles in that range', $response, $this->_currentStatusResponse());
    	    case 502:
    	    	return $this->throwError('No permission', $response, $this->_currentStatusResponse());
    	    default:
    	    	return $this->_handleUnexpectedResponse($response);
    	}
    }

    // }}}
    // {{{ cmdXOver()

    /**
     * Fetch message header from message number $first until $last
     *
     * The format of the returned array is:
     * $messages[message_id][header_name]
     *
     * @param string $range articles to fetch
     *
     * @return mixed (array) nested array of message and there headers on success or (object) pear_error on failure
     * @access protected
     */
    protected function cmdXOver(?string $range = null): mixed
    {
	// deprecated API (the code _is_ still in alpha state)
    	if (\func_num_args() > 1 ) {
    	    die('The second parameter in cmdXOver() has been deprecated! Use x-y instead...');
        }

        $command = \is_null($range) ? 'XOVER' : 'XOVER ' . $range;

        $response = $this->_sendCommand($command);
        if (Net_NNTP_Error::isError($response)){
            return $response;
        }

	    switch ($response) {
	        case ResponseCode::OverviewFollows->value:
	    	    $data = $this->_getTextResponse();
	            if (Net_NNTP_Error::isError($data)) {
	                return $data;
	            }

	            foreach ($data as $key => $value) {
	                $data[$key] = explode("\t", $value);
	            }

    	    	if ($this->_logger) {
    	    	    $this->_logger->info('Fetched overview ' . ($range === null ? 'for current article' : 'for range: '.$range));
    	    	}

    	    	return $data;
    	    case ResponseCode::NoGroupSelected->value:
    	    	return $this->throwError('No news group current selected', $response, $this->_currentStatusResponse());
    	    case ResponseCode::NoArticleSelected->value:
    	    	return $this->throwError('No article(s) selected', $response, $this->_currentStatusResponse());
    	    case 502:
    	    	return $this->throwError('No permission', $response, $this->_currentStatusResponse());
    	    default:
    	    	return $this->_handleUnexpectedResponse($response);
    	}
    }

    // }}}
    // {{{ cmdListOverviewFmt()

    /**
     * Returns a list of avaible headers which are send from news server to client for every news message
     *
     * @return mixed (array) of header names on success or (object) pear_error on failure
     * @access protected
     */
    protected function cmdListOverviewFmt(): mixed
    {
    	$response = $this->_sendCommand('LIST OVERVIEW.FMT');
        if (Net_NNTP_Error::isError($response)){
            return $response;
        }

    	switch ($response) {
    	    case ResponseCode::GroupsFollow->value:
    	    	$data = $this->_getTextResponse();
    	        if (Net_NNTP_Error::isError($data)) {
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
    	    case 503:
    	    	return $this->throwError('Internal server error, function not performed', $response, $this->_currentStatusResponse());
    	    default:
    	    	return $this->_handleUnexpectedResponse($response);
    	}
    }

    // }}}
    // {{{ cmdXHdr()

    /**
     *
     *
     * The format of the returned array is:
     * $messages[message_id]
     *
     * @param  string  $field
     * @param  string|null  $range articles to fetch
     *
     * @return mixed (array) nested array of message and there headers on success or (object) pear_error on failure
     * @access protected
     */
    protected function cmdXHdr(string $field, ?string $range = null): mixed
    {
        $command = \is_null($range) ? 'XHDR ' . $field : 'XHDR ' . $field . ' ' . $range;

        $response = $this->_sendCommand($command);
        if (Net_NNTP_Error::isError($response)){
            return $response;
        }

    	switch ($response) {
    	    case 221:
    	    	$data = $this->_getTextResponse();
    	        if (Net_NNTP_Error::isError($data)) {
    	            return $data;
    	        }

    	    	$return = [];
    	        foreach ($data as $line) {
    	    	    $parts = explode(' ', $line, 2);
    	    	    $return[$parts[0]] = $parts[1];
    	        }

    	    	return $return;
    	    case ResponseCode::NoGroupSelected->value:
    	    	return $this->throwError('No news group current selected', $response, $this->_currentStatusResponse());
    	    case ResponseCode::NoArticleSelected->value:
    	    	return $this->throwError('No current article selected', $response, $this->_currentStatusResponse());
    	    case 430:
    	    	return $this->throwError('No such article', $response, $this->_currentStatusResponse());
    	    case 502:
    	    	return $this->throwError('No permission', $response, $this->_currentStatusResponse());
    	    default:
    	    	return $this->_handleUnexpectedResponse($response);
    	}
    }

    // }}}

    /**
     * Fetches a list of (all) avaible newsgroup descriptions.
     *
     * @param string $wildmat Wildmat of the groups, that is to be listed, defaults to '*';
     *
     * @return mixed (array) nested array with description of existing newsgroups on success or (object) pear_error on failure
     * @access protected
     *@deprecated  as of RFC2980.
     *
     */
    protected function cmdXGTitle(string $wildmat = '*'): mixed
    {
        $response = $this->_sendCommand('XGTITLE '.$wildmat);
        if (Net_NNTP_Error::isError($response)){
            return $response;
        }

    	switch ($response) {
    	    case 282:
    	    	$data = $this->_getTextResponse();
    	        if (Net_NNTP_Error::isError($data)) {
    	            return $data;
    	        }

    	    	$groups = [];

    	        foreach ($data as $line) {
    	            $pos = strpos($line, ' ');
    	            if ($pos !== false) {
    	                $groups[substr($line, 0, $pos)] = substr($line, $pos + 1);
    	            }
    	        }

    	        return $groups;

    	    case 481:
    	    	return $this->throwError('Groups and descriptions unavailable', $response, $this->_currentStatusResponse());
    	    default:
    	    	return $this->_handleUnexpectedResponse($response);
    	}
    }

    // }}}
    // {{{ cmdXROver()

    /**
     * Fetch message references from message number $first to $last
     *
     * @param  string|null  $range articles to fetch
     *
     * @return mixed (array) assoc. array of message references on success or (object) pear_error on failure
     * @access protected
     */
    protected function cmdXROver(?string $range = null): mixed
    {
	// Warn about deprecated API (the code _is_ still in alpha state)
    	if (\func_num_args() > 1 ) {
    	    die('The second parameter in cmdXROver() has been deprecated! Use x-y instead...');
    	}

        $command = \is_null($range) ? 'XROVER' : 'XROVER ' . $range;

        $response = $this->_sendCommand($command);
        if (Net_NNTP_Error::isError($response)){
            return $response;
        }

    	switch ($response) {
    	    case ResponseCode::OverviewFollows->value:
    	    	$data = $this->_getTextResponse();
    	        if (Net_NNTP_Error::isError($data)) {
    	            return $data;
    	        }

    	    	$return = [];
    	        foreach ($data as $line) {
    	    	    $parts = explode(' ', $line, 2);
    	    	    $return[$parts[0]] = $parts[1];
    	        }
    	    	return $return;
    	    case ResponseCode::NoGroupSelected->value:
    	    	return $this->throwError('No news group current selected', $response, $this->_currentStatusResponse());
    	    case ResponseCode::NoArticleSelected->value:
    	    	return $this->throwError('No article(s) selected', $response, $this->_currentStatusResponse());
    	    case 502:
    	    	return $this->throwError('No permission', $response, $this->_currentStatusResponse());
    	    default:
    	    	return $this->_handleUnexpectedResponse($response);
    	}
    }

    // }}}




    // {{{ cmdXPat()

    /**
     *
     *
     * @param  string  $field
     * @param  string  $range
     * @param mixed $wildmat
     *
     * @return mixed (array) nested array of message and there headers on success or (object) pear_error on failure
     * @access protected
     */
    protected function cmdXPat(string $field, string $range, mixed $wildmat): mixed
    {
        if (\is_array($wildmat)) {
	    $wildmat = implode(' ', $wildmat);
    	}

        $response = $this->_sendCommand('XPAT ' . $field . ' ' . $range . ' ' . $wildmat);
        if (Net_NNTP_Error::isError($response)){
            return $response;
        }

    	switch ($response) {
    	    case 221:
    	    	$data = $this->_getTextResponse();
    	        if (Net_NNTP_Error::isError($data)) {
    	            return $data;
    	        }

    	    	$return = [];
    	        foreach ($data as $line) {
    	    	    $parts = explode(' ', $line, 2);
    	    	    $return[$parts[0]] = $parts[1];
    	        }

    	    	return $return;
    	    case 430:
    	    	return $this->throwError('No current article selected', $response, $this->_currentStatusResponse());
    	    case 502:
    	    	return $this->throwError('No permission', $response, $this->_currentStatusResponse());
    	    default:
    	    	return $this->_handleUnexpectedResponse($response);
    	}
    }

    // }}}
    // {{{ cmdAuthinfo()

	/**
	 * Authenticate using 'original' method
	 *
	 * @param  string  $user  The username to authenticate as.
	 * @param  string|null  $pass  The password to authenticate with.
	 *
	 * @return mixed (bool) true on success or (object) pear_error on failure
	 * @access protected
	 */
    protected function cmdAuthinfo(string $user, ?string $pass = null): mixed
    {
    	$response = $this->_sendCommand('AUTHINFO user '.$user);
        if (Net_NNTP_Error::isError($response)) {
            return $response;
    	}

    	if (($response === 381) && ($pass !== null)) {
            $response = $this->_sendCommand('AUTHINFO pass '.$pass);
    	    if (Net_NNTP_Error::isError($response)) {
    	    	return $response;
    	    }
    	}

        switch ($response) {
    	    case 281:
    	    	if ($this->_logger) {
    	    	    $this->_logger->info("Authenticated (as user '$user')");
    	    	}
    	        return true;
    	    case 381:
    	        return $this->throwError('Authentication uncompleted', $response, $this->_currentStatusResponse());
    	    case 482:
    	    	return $this->throwError('Authentication rejected', $response, $this->_currentStatusResponse());
    	    case 502:
    	    	return $this->throwError('Authentication rejected', $response, $this->_currentStatusResponse());
    	    default:
    	    	return $this->_handleUnexpectedResponse($response);
    	}
    }

    // }}}
    // {{{ cmdAuthinfoSimple()

    /**
     * Authenticate using 'simple' method
     *
     * @param  string  $user The username to authenticate as.
     * @param  string  $pass The password to authenticate with.
     *
     * @return mixed (bool) true on success or (object) pear_error on failure
     * @access protected
     */
    protected function cmdAuthinfoSimple(string $user, string $pass): mixed
    {
        return $this->throwError("The auth mode: 'simple' is has not been implemented yet", null);
    }

    // }}}
    // {{{ cmdAuthinfoGeneric()

    /**
     * Authenticate using 'generic' method
     *
     * @param  string  $user The username to authenticate as.
     * @param  string  $pass The password to authenticate with.
     *
     * @return mixed (bool) true on success or (object) pear_error on failure
     * @access protected
     */
    protected function cmdAuthinfoGeneric(string $user, string $pass): mixed
    {
        return $this->throwError("The auth mode: 'generic' is has not been implemented yet", null);
    }

    // }}}
    // {{{ _isConnected()

    /**
     * Test whether we are connected or not.
     *
     * @return bool true or false
     * @access protected
     */
    protected function _isConnected(): bool
    {
        return $this->_socket !== null && \is_resource($this->_socket) && !feof($this->_socket);
    }

    // }}}

}

// }}}
