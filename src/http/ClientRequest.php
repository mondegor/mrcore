<?php declare(strict_types=1);
namespace mrcore\http;
use mrcore\base\EnumType;

/**
 * Обёртка для доступа к массиву $_REQUEST.
 *
 * @author  Andrey J. Nazarov
 * @uses       $_REQUEST
 */
class ClientRequest extends AbstractClientData
{

    public function __construct()
    {
        // распаковывание json данных, если они были переданы
        if (ResponseInterface::CONTENT_TYPE_JSON === getenv('CONTENT_TYPE'))
        {
            $_REQUEST = array_replace($_REQUEST, json_decode(file_get_contents('php://input'), true));
        }
    }

    /**
     * @inheritdoc
     */
    public function has(string $name): bool
    {
        return isset($_REQUEST[$name]);
    }

    /**
     * @inheritdoc
     */
    public function get(string $name, string $default = null): string
    {
        if (!isset($_REQUEST[$name]))
        {
            return ($default ?? '');
        }

        return $this->_wrapConvert($this->_wrapCast(EnumType::STRING, $_REQUEST[$name]));
    }

    /**
     * @inheritdoc
     */
    public function set(string $name, string|int|float|bool|null|array $value): AbstractClientData
    {
        $_REQUEST[$name] = $value;

        return $this;
    }

    /**
     * Возвращает INT значение параметра $name из $_REQUEST,
     * либо значение по умолчанию $default (если указано).
     */
    public function getInt(string $name, int $default = null): int
    {
        if (!isset($_REQUEST[$name]))
        {
            return ($default ?? 0);
        }

        return $this->_wrapCast(EnumType::INT, $_REQUEST[$name]);
    }

    /**
     * Возвращает FLOAT значение параметра $name из $_REQUEST,
     * либо значение по умолчанию $default (если указано).
     */
    public function getFloat(string $name, float $default = null): float
    {
        if (!isset($_REQUEST[$name]))
        {
            return ($default ?? 0.0);
        }

        return $this->_wrapCast(EnumType::FLOAT, $_REQUEST[$name]);
    }

    /**
     * Возвращает ARRAY значение параметра $name из $_REQUEST,
     * либо значение по умолчанию $default (если указано).
     *
     * @param  int        $type {@see EnumType::STRING}
     * @param  array|null $default [int => mixed, ...]
     * @return array [int => mixed, ...]
     */
    public function getArray(int $type, string $name, array $default = null): array
    {
        assert(in_array($type, [EnumType::STRING,
                                EnumType::INT,
                                EnumType::FLOAT,
                                EnumType::BOOL,
                                EnumType::ARRAY], true));

        if (!isset($_REQUEST[$name]))
        {
            return ($default ?? []);
        }

        $value = $this->_wrapCast(EnumType::ARRAY, $_REQUEST[$name]);
        $value = match ($type) {
            EnumType::INT => array_map('intval', $value),
            EnumType::FLOAT => array_map('floatval', $value),
            EnumType::BOOL => array_map('bool', $value),
            default => $this->_wrapConvert($value),
        };

        return array_unique($value);
    }

    /**
     * @inheritdoc
     */
    public function getRaw(): array
    {
        return $_REQUEST;
    }

}