<?php

declare(strict_types=1);

/**
 * Net_NNTP Error class
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

namespace DariusIII\NetNntp;

/**
 * Lightweight error value object.
 */
class Error
{
    public function __construct(
        protected readonly string $message = '',
        protected readonly ?int $code = null,
        protected readonly mixed $userInfo = null,
    ) {}

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getCode(): ?int
    {
        return $this->code;
    }

    public function getUserInfo(): mixed
    {
        return $this->userInfo;
    }

    public static function isError(mixed $data): bool
    {
        return $data instanceof self;
    }

    public function __toString(): string
    {
        $str = static::class . ': ' . $this->message;
        if ($this->code !== null) {
            $str .= ' (code: ' . $this->code . ')';
        }
        return $str;
    }
}
