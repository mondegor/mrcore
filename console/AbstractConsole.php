<?php declare(strict_types=1);
namespace mrcore\console;
use InvalidArgumentException;
use RuntimeException;
use MrDebug;
use mrcore\services\EnvService;
use mrcore\services\TraitServiceInjection;

require_once 'mrcore/MrDebug.php';
require_once 'mrcore/services/TraitServiceInjection.php';

/**
 * Класс обрабатывает входные параметры, поступающие с командной строки
 * и даёт методы для удобства работы с ними.
 * Также может принимать симолы с клавиатуры пользователя.
 *
 * @author     Andrey J. Nazarov <mondegor@gmail.com>
 * @package    mrcore/console
 */
abstract class AbstractConsole
{
    use TraitServiceInjection;

    /**
     * Типы завершения приложения.
     *
     * @var  int
     */
    public const EXIT_FAILURE = 1,
                 EXIT_SUCCESS = 0,
                 EXIT_EXCEPTION = 2;

    /**
     * Флаги применяемые к опциям.
     *
     * @var  int
     */
    public const // FLAG_OPTION_OPTIONAL = 0, // необязательный параметр
                 FLAG_OPTION_REQUIRED = 1, // обязательный параметр
                 // FLAG_OPTION_VALUE_DEFAULT = 0, // допускает занчение по умолчанию
                 FLAG_OPTION_VALUE_OFF = 2, // параметр может быть без начения
                 FLAG_OPTION_VALUE_REQUIRED = 4; // параметр требует указанного значения

    ################################### Properties ###################################

    /**
     * Список опций, которые используются системой.
     *
     * @var  array [string => [types => [int, ...], default => mixed OPTIONAL], ...]
     */
    protected array $_listOptions = [];

    /**
     * Зарегистрированные аргументы поступившие из консоли.
     *
     * @var  array [[int index => [string, mixed value], ...]
     */
    private array $_args = [];

    /**
     * Зарегистрированные опции полученные из аргументов.
     * В качестве значения задан индекс массива self::$_args
     *
     * @var  array [[string => int index], ...]
     */
    private array $_options = [];

    #################################### Methods #####################################

    /**
     * @inheritdoc
     */
    /*__override__*/ protected function _getSubscribedServices(): array
    {
        return array
        (
            'global.env' => true,
        );
    }

    /**
     * Конструктор класса.
     * Инициализация рабочего окружения для работы в консоле.
     *
     * @param array $argv
     * @param int   $argc
     * @throws      RuntimeException
     * @throws      InvalidArgumentException
     */
    public function __construct(array $argv, int $argc)
    {
        /* @var $env EnvService */ $env = &$this->injectService('global.env');

        if (!$env->isCli())
        {
            throw new RuntimeException(sprintf('%s class cannot be used outside of the command line', static::class));
        }

        ##################################################################################

        for ($i = 0; $i < $argc - 1; $i++)
        {
            $arg = $argv[$i + 1];

            if (0 === strncmp($arg, '--', 2))
            {
                $nameAndValue = explode('=', $arg);
                $name = ltrim($nameAndValue[0], '-');

                $this->_args[$i] = [$name, $nameAndValue[1] ?? true];
                $this->_options[$name] = $i;
            }
            else if (0 === strncmp($arg, '-', 1))
            {
                $this->_args[$i] = [ltrim($arg, '-'), true];

                // ограничение на представление -abcdeafg
                $count = min(strlen($arg), 10);

                for ($j = 1; $j < $count; $j++)
                {
                    $this->_options[$arg[$j]] = $i;
                }
            }
            else
            {
                $this->_args[$i] = [$arg, null];
            }
        }

        ##################################################################################

        if (MrDebug::isGroupEnabled('mrcore:0'))
        {
            echo sprintf("%s::\$_args:\n", static::class);
            MrDebug::dump($this->_args);

            echo sprintf("\n%s::\$_options:\n", static::class);
            MrDebug::dump($this->_options);
        }

        ##################################################################################

        $this->_checkAndInitOptions();
    }

    /**
     * Вовзращение списка зарегистрированных опций.
     * Аргументы являются опциями только с впереди стоящими символами "-" и "--".
     *
     * @return   array [string, ...]
     */
    public function getOptions(): array
    {
        return array_keys($this->_options);
    }

    /**
     * Существует ли указанная опция.
     * Аргументы являются опциями только с впереди стоящими символами "-" и "--".
     *
     * @param    string $name (param1, param-name)
     * @return   bool
     */
    public function hasOption(string $name): bool
    {
        return isset($this->_options[$name]);
    }

    /**
     * Получение значения опции переданного через консоль по его имени.
     * Аргументы являются опциями только с впереди стоящими символами "-" и "--".
     *
     * Примеры допустимых опций:
     *   --named-param         -> true
     *   --named-param --test1 -> true
     *   --named-param=test1   -> test1
     *   --named-param test1   -> true or test1 (зависит от $nextArgAsValue)
     *
     * @param    string $name (param1, param-name)
     * @return   string|bool|null
     */
    public function getOption(string $name)
    {
        if (!isset($this->_options[$name]))
        {
            return null;
        }

        $index = $this->_options[$name];

        // если это опция без значения или если значение явно указано, то оно и возвращается
        if (self::FLAG_OPTION_VALUE_OFF & $this->_listOptions[$name]['flags'] ||
            true !== $this->_args[$index][1])
        {
            return $this->_args[$index][1];
        }

        // если у аргумента значение не указано, то значение вибирается из следующего
        // аргумента, но только если он сам не является опцией
        if (isset($this->_args[$index + 1]) &&
                null === $this->_args[$index + 1][1])
        {
            return $this->_args[$index + 1][0];
        }

        return null;
    }

    /**
     * Получение "свободного" аргумента, т.е. который не привязан ни к одной из опций.
     *
     * @param    int  $number
     * @return   string|null
     */
    public function getFreeArg(int $number): ?string
    {
        assert($number > 0);

        $prev = null;

        foreach ($this->_args as $i => $arg)
        {
            // если это опция
            if (null !== $arg[1])
            {
                $prev = $arg;
                continue;
            }

            if (null === $prev ||
                0 === (self::FLAG_OPTION_VALUE_REQUIRED & $this->_listOptions[$prev[0]]['flags']))
            {
                if (1 === $number)
                {
                    return $arg[0];
                }

                $number--;
            }

            $prev = null;
        }

        return null;
    }

    /**
     * Приглашение к вводу символа от пользователя.
     *
     * @param    string  $prefix OPTIONAL
     * @return   string
     */
    public function input(string $prefix = null): string
    {
        if (null !== $prefix)
        {
            echo $prefix;
        }

        return (string)fgets(STDIN);
    }

    /**
     * Проверяется, чтобы переданные опции были зарегистрированны и
     * содержали нужные им значения или наоборот были заданы без значений.
     *
     * @throws   InvalidArgumentException
     */
    private function _checkAndInitOptions(): void
    {
        // сначала проверяются только параметры, которые должны присутствовать обязательно
        foreach ($this->_listOptions as $name => $options)
        {
            $prefix = (strlen($name) > 1 ? '--' : '-');

            if ((self::FLAG_OPTION_REQUIRED & $options['flags']) && !$this->hasOption($name))
            {
                // :TODO: заменить на EmptyArgumentException
                throw new InvalidArgumentException(sprintf('Required option "%s%s" is missing', $prefix, $name));
            }
        }

        // далее проверяется, что были переданы только правильные аргументы
        foreach (array_keys($this->_options) as $name)
        {
            $prefix = (strlen($name) > 1 ? '--' : '-');

            if (!isset($this->_listOptions[$name]))
            {
                throw new InvalidArgumentException(sprintf('Unknown option "%s%s"', $prefix, $name));
            }

            if (self::FLAG_OPTION_VALUE_OFF & $this->_listOptions[$name]['flags'])
            {
                if (true !== $this->getOption($name))
                {
                    throw new InvalidArgumentException(sprintf('Option "%s%s" does not allow an argument', $prefix, $name));
                }
            }
            else if (self::FLAG_OPTION_VALUE_REQUIRED & $this->_listOptions[$name]['flags'])
            {
                $value = $this->getOption($name);

                if (true === $value || null === $value || '' === $value)
                {
                    // :TODO: заменить на EmptyArgumentException
                    throw new InvalidArgumentException(sprintf('Required argument for option "%s%s" is missing', $prefix, $name));
                }
            }
            // иначе опция может иметь значение, но оно не обязательно
            else
            {
                if (isset($this->_listOptions[$name]['default']) && true === $this->getOption($name))
                {
                    $this->_args[$this->_options[$name]][1] = $this->_listOptions[$name]['default'];
                }
            }
        }
    }

}