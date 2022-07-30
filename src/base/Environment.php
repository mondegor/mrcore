<?php declare(strict_types=1);
namespace mrcore\base;

/**
 * Обёртка доступа к внешнему окружению организованной ОС.
 *
 * @author  Andrey J. Nazarov
 * @uses       $_SERVER['REQUEST_TIME_FLOAT']
 */
class Environment
{

    public function __construct()
    {
        if (!isset($_SERVER['REQUEST_TIME_FLOAT']))
        {
            $_SERVER['REQUEST_TIME_FLOAT'] = microtime(true);
        }
    }

    /**
     * Возвращается значение из переменной переменного окружения.
     */
    public function get(string $name): string
    {
        return (string)(getenv($name, true) ?: getenv($name));
    }

    /**
     * Установка значения переменной в переменное окружение.
     */
    public function put(string $name, string $value): bool
    {
        return putenv(sprintf('%s=%s', $name, $value));
    }

    /**
     * Возвращается URL с которого был переход на текущий запрос.
     */
    public function getStartTime(): float
    {
        return $_SERVER['REQUEST_TIME_FLOAT'];
    }

    /**
     * Возвращается порт на компьютере сервера, используемый сервером для соединения.
     */
    public function getServerPort(): int
    {
        return (int)$this->get('SERVER_PORT');
    }

}