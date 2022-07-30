<?php declare(strict_types=1);
namespace mrcore\http;
use mrcore\base\EnumType;

/**
 * Обёртка для доступа к массиву $_COOKIE.
 *
 * @author  Andrey J. Nazarov
 * @uses       $_COOKIE
 */
class ClientCookie extends AbstractClientData
{
    /**
     * @inheritdoc
     */
    public function has(string $name): bool
    {
        return isset($_COOKIE[$name]);
    }

    /**
     * @inheritdoc
     */
    public function get(string $name, string $default = null): string
    {
        if (!isset($_COOKIE[$name]))
        {
            return ($default ?? '');
        }

        return $this->_wrapConvert($this->_wrapCast(EnumType::STRING, $_COOKIE[$name]));
    }

    /**
     * @inheritdoc
     */
    public function set(string $name, string|int|float|bool|null|array $value): AbstractClientData
    {
        $_COOKIE[$name] = $value;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getRaw(): array
    {
        return $_COOKIE;
    }

}