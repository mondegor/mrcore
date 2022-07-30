<?php declare(strict_types=1);
namespace mrcore\console;
use mrcore\console\exceptions\ConsoleInvalidArgumentException;
use mrcore\MrInfo;
use mrcore\base\EnumType;
use RuntimeException;

/**
 * Реализация методов для работы с аргументами поступающих с консоли.
 *
 * Примеры допустимых опций:
 *   --named-param         -> named-param=true
 *   --named-param --test1 -> named-param=true test1=true
 *   --named-param=test1   -> named-param=test1
 *   --named-param test1   -> named-param=true or named-param=test1 (зависит от OPTION_FLAG_VALUE_*)
 *
 * @author  Andrey J. Nazarov
 *
 * @template  T_CONSOLE_SCHEME_OPTION=array{flags: ConsoleArgs::OPTION_FLAG_*,
 *                                          ?type => EnumType::INT|EnumType::FLOAT,
 *                                          ?default => string|int|float|null}
 *
 * @template  T_CONSOLE_PARSED_ARG=array{0: string, 1: string|true|null} // 0 - название опции или значение аргумента,
 *                                                                       // 1 - значение опции или null
 */
class ConsoleArgs
{
    /**
     * Флаги применяемые к опциям.
     * :WARNING: нельзя использовать одновременно флаги: VALUE_OFF и VALUE_REQUIRED
     */
    public const // OPTION_FLAG_OPTIONAL = 0, // необязательный параметр
                 OPTION_FLAG_REQUIRED = 1, // обязательный параметр
                 // OPTION_FLAG_VALUE_DEFAULT = 0, // допускает значение по умолчанию
                 OPTION_FLAG_VALUE_OFF = 2, // параметр должен быть без значения
                 OPTION_FLAG_VALUE_REQUIRED = 4; // параметр требует указанного значения

    ################################### Properties ###################################

    /**
     * Обработанные аргументы поступившие из консоли.
     *
     * @var  T_CONSOLE_PARSED_ARG[]
     */
    private array $args = [];

    /**
     * Схема опций, которая описывает интерпретацию обработанных аргументов.
     * Под опциями понимаются аргументы с префиксами "-" и "--".
     *
     * @var  array<string, T_CONSOLE_SCHEME_OPTION>
     */
    private array $optionSchema = [];

    /**
     * Опции полученные из аргументов (без применения схемы опций).
     *
     * @var  array<string, int> // key - название опции, value - индекс распарсенного аргумента в $this->args.
     */
    private array $options = [];

    #################################### Methods #####################################

    /**
     * @param  string[] $args // from $_SERVER['argv']
     * @param  array<string, T_CONSOLE_SCHEME_OPTION>|null $optionSchema
     */
    public function __construct(array $args, array $optionSchema = null)
    {
        $this->_initArgs($args);

        if (MrInfo::isGroupEnabled('mrcore:0'))
        {
            echo sprintf("%s::\$args:\n", static::class);
            var_dump($this->args);

            echo sprintf("\n%s::\$options:\n", static::class);
            var_dump($this->options);
        }

        if (null !== $optionSchema)
        {
            $this->applyOptionSchema($optionSchema, false);
        }
    }

    /**
     * Применение новой схемы опций.
     * При $expand = true новая схема будет наложена на старую, иначе новая применится вместо старой.
     *
     * Обработанные опции, которые неизвестны схеме будут пропущены,
     * дополнительно проверить их можно с помощью {@see ConsoleArgs::checkOptionsRegisteredSchema()}
     *
     * @param  array<string, T_CONSOLE_SCHEME_OPTION> $optionSchema
     */
    public function applyOptionSchema(array $optionSchema, bool $expand = true): void
    {
        foreach ($optionSchema as $name => $option)
        {
            assert((self::OPTION_FLAG_VALUE_OFF + self::OPTION_FLAG_VALUE_REQUIRED) !== ((self::OPTION_FLAG_VALUE_OFF + self::OPTION_FLAG_VALUE_REQUIRED) & $option['flags']));
            assert(!isset($option['type']) || (EnumType::INT === $option['type'] || EnumType::FLOAT === $option['type']));
            assert(!isset($option['default']) || (is_string($option['default']) || is_int($option['default']) || is_float($option['default'])));

            if (!isset($this->options[$name]))
            {
                if (self::OPTION_FLAG_REQUIRED & $option['flags'])
                {
                    throw ConsoleInvalidArgumentException::requiredOptionMissing($name);
                }

                continue;
            }

            if ((self::OPTION_FLAG_VALUE_OFF & $option['flags']) > 0)
            {
                if (true !== $this->args[$this->options[$name]][1])
                {
                    throw ConsoleInvalidArgumentException::optionNotAllowedArgument($name);
                }
            }
            else if ((self::OPTION_FLAG_VALUE_REQUIRED & $option['flags']) > 0)
            {
                $value = $this->_getOption($this->options[$name], $option);

                if (true === $value || null === $value || '' === $value)
                {
                    throw ConsoleInvalidArgumentException::requiredArgumentForOptionMissing($name);
                }
            }
        }

        $this->optionSchema = $expand ?
                                  array_replace($this->optionSchema, $optionSchema) :
                                  $optionSchema;
    }

    /**
     * Проверяется, что все обработанные опции
     * соответствуют установленной схеме.
     */
    public function checkOptionsRegisteredSchema(): void
    {
        if (empty($this->optionSchema))
        {
            throw new RuntimeException('The option schema is not initialized');
        }

        foreach ($this->options as $name => $index)
        {
            if (!isset($this->optionSchema[$name]))
            {
                throw ConsoleInvalidArgumentException::optionNotFoundInSchema($name);
            }
        }
    }

    /**
     * Передана ли указанная опция (без префиксов "-" и "--").
     */
    public function hasOption(string $name): bool
    {
        return isset($this->options[$name]);
    }

    /**
     * Возвращается значение опции по его имени (без префиксов "-" и "--").
     */
    public function getOption(string $name): string|int|float|bool|null
    {
        $hasSchema = isset($this->optionSchema[$name]);

        if (!isset($this->options[$name]))
        {
            if ($hasSchema)
            {
                return null;
            }

            throw ConsoleInvalidArgumentException::optionNotFoundInSchema($name);
        }

        if (!$hasSchema)
        {
            return null;
        }

        return $this->_getOption($this->options[$name], $this->optionSchema[$name]);
    }

    /**
     * Возвращается "свободный" аргумент, который не привязан ни к одной из опций.
     *
     * @param  int|null  $type {@see EnumType::INT|EnumType::FLOAT}
     */
    public function getFreeArg(int $number, int $type = null): string|int|float|bool|null
    {
        assert($number > 0);
        assert(null === $type || (EnumType::INT === $type || EnumType::FLOAT === $type));

        $prev = null;

        foreach ($this->args as $arg)
        {
            if (null !== $arg[1]) // если это опция
            {
                $prev = $arg;
                continue;
            }

            if (null === $prev ||
                (self::OPTION_FLAG_VALUE_OFF & $this->optionSchema[$prev[0]]['flags']) > 0)
            {
                if (1 === $number)
                {
                    return (null === $type ? $arg[0] : EnumType::cast($type, $arg[0]));
                }

                $number--;
            }

            $prev = null;
        }

        return null;
    }

    /**
     * :WARNING: Первый параметр в аргументах пропускается (т.к. там должен находиться путь к скрипту)
     *
     * @param  string[]  $args // from $_SERVER['argv']
     */
    protected function _initArgs(array $args): void
    {
        for ($i = 0, $argc = count($args); $i < $argc - 1; $i++)
        {
            $arg = $args[$i + 1];

            assert(is_string($arg) && '' !== $arg);

            if (0 === strncmp($arg, '--', 2))
            {
                $nameAndValue = explode('=', substr($arg, 2), 2);
                $name = $nameAndValue[0];

                $this->args[$i] = [$name, $nameAndValue[1] ?? true];
                $this->options[$name] = $i;
            }
            else if (0 === strncmp($arg, '-', 1))
            {
                $this->args[$i] = [substr($arg, 1), true];

                // ограничение на представление -abcdeafg
                $count = min(strlen($arg), 16);

                for ($j = 1; $j < $count; $j++)
                {
                    $this->options[$arg[$j]] = $i;
                }
            }
            else
            {
                $this->args[$i] = [$arg, null];
            }
        }
    }

    /**
     * Возвращается значение опции переданного через консоль по его имени.
     *
     * @param  T_CONSOLE_SCHEME_OPTION $schemaOption
     */
    protected function _getOption(int $index, array $schemaOption): string|int|float|bool|null
    {
        // если это опция без значения
        if ((self::OPTION_FLAG_VALUE_OFF & $schemaOption['flags']) > 0)
        {
            assert(true === $this->args[$index][1]); // :TODO: можно закомментировать
            return true;
        }

        // если у опции значение явно указано, то оно и возвращается
        if (true !== $this->args[$index][1])
        {
            assert(is_string($this->args[$index][1])); // :TODO: можно закомментировать

            if ('' === $this->args[$index][1] &&
                    array_key_exists('default', $schemaOption))
            {
                return $schemaOption['default'];
            }

            return isset($schemaOption['type']) ?
                       EnumType::cast($schemaOption['type'], $this->args[$index][1]) :
                       $this->args[$index][1];
        }

        // если у аргумента значение не указано, то значение выбирается из следующего
        // аргумента, но только если он сам не является опцией
        if (isset($this->args[$index + 1]) && null === $this->args[$index + 1][1])
        {
            assert(is_string($this->args[$index + 1][0]) && '' !== $this->args[$index + 1][0]); // :TODO: можно закомментировать

            return isset($schemaOption['type']) ?
                       EnumType::cast($schemaOption['type'], $this->args[$index + 1][0]) :
                       $this->args[$index + 1][0];
        }

        return $schemaOption['default'] ?? null;
    }

}