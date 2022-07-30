<?php declare(strict_types=1);
namespace mrcore\console;
use mrcore\console\exceptions\ConsoleInvalidArgumentException;
use RuntimeException;

/**
 * Интерфейс для реализации различных скриптов запускаемых из консоли.
 *
 * @author  Andrey J. Nazarov
 *
 * @template  T_PROPERTIES
 * @template  T_CONSOLE_SCHEME_OPTION
 */
interface ScriptInterface
{
    /**
     * Возвращается системное название скрипта.
     */
    public function getName(): string;

    /**
     * Возвращается схема опций скрипта {@see ConsoleArgs::$optionSchema}
     *
     * @var  array<string, T_CONSOLE_SCHEME_OPTION>
     */
    public function getOptionSchema(): array;

    /**
     * Возвращается справка по аргументам, используемых скриптом.
     *
     * @var  array<string, string> // key - название аргумента, value - описание аргумента
     */
    public function getHelpForArgs(): array;

    /**
     * Разбор аргументов скрипта, которые могут быть переданы из консоли.
     * Аргументы содержатся в объекте $arguments, на который
     * предварительно накладывается схема опций скрипта;
     *
     * @return  T_PROPERTIES // массив разобранных аргументов скрипта
     * @throws  ConsoleInvalidArgumentException
     */
    public function parseArgs(ConsoleArgs $arguments): array;

    /**
     * Запуск с основным кодом скрипта.
     *
     * @param  T_PROPERTIES  $parsedArgs
     * @throws  RuntimeException
     */
    public function exec(array $parsedArgs): string|null;

}