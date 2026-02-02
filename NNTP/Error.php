<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4 foldmethod=marker: */

/**
 * Net_NNTP Error class - replaces PEAR_Error dependency
 *
 * PHP versions 8.5 and above
 *
 * @category   Net
 * @package    Net_NNTP
 * @author     Heino H. Gehlsen <heino@gehlsen.dk>
 * @copyright  2002-2017 Heino H. Gehlsen <heino@gehlsen.dk>. All Rights Reserved.
 * @license    http://www.w3.org/Consortium/Legal/2002/copyright-software-20021231 W3C SOFTWARE NOTICE AND LICENSE
 * @version    SVN: $Id$
 * @link       http://pear.php.net/package/Net_NNTP
 */

/**
 * Net_NNTP_Error class
 *
 * A lightweight error class that replaces PEAR_Error for error handling.
 *
 * @category   Net
 * @package    Net_NNTP
 */
class Net_NNTP_Error
{
    /**
     * Error message
     *
     * @var string
     */
    protected string $message;

    /**
     * Error code
     *
     * @var int|null
     */
    protected ?int $code;

    /**
     * User info (additional error details)
     *
     * @var mixed
     */
    protected mixed $userInfo;

    /**
     * Constructor
     *
     * @param string $message Error message
     * @param int|null $code Error code
     * @param mixed $userInfo Additional error information
     */
    public function __construct(string $message = '', ?int $code = null, mixed $userInfo = null)
    {
        $this->message = $message;
        $this->code = $code;
        $this->userInfo = $userInfo;
    }

    /**
     * Get the error message
     *
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * Get the error code
     *
     * @return int|null
     */
    public function getCode(): ?int
    {
        return $this->code;
    }

    /**
     * Get user info
     *
     * @return mixed
     */
    public function getUserInfo(): mixed
    {
        return $this->userInfo;
    }

    /**
     * Check if a value is a Net_NNTP_Error instance
     *
     * @param mixed $data The value to check
     * @return bool True if $data is a Net_NNTP_Error instance
     */
    public static function isError(mixed $data): bool
    {
        return $data instanceof Net_NNTP_Error;
    }

    /**
     * String representation of the error
     *
     * @return string
     */
    public function __toString(): string
    {
        $str = get_class($this) . ': ' . $this->message;
        if ($this->code !== null) {
            $str .= ' (code: ' . $this->code . ')';
        }
        return $str;
    }
}

