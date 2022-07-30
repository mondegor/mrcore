<?php declare(strict_types=1);
namespace mrcore\console;

/**
 * Абстракция для реализации различных скриптов запускаемых из консоли.
 *
 * @author  Andrey J. Nazarov
 *
 * @template  T_CONSOLE_SCHEME_OPTION
 */
abstract class AbstractScript implements ScriptInterface
{
    /**
     * Системное название скрипта (должно быть уникальным).
     */
    protected ?string $name = null;

    /**
     * Схема опций скрипта.
     *
     * @var  array<string, T_CONSOLE_SCHEME_OPTION>
     * @see ConsoleArgs::$optionSchema
     */
    protected array $optionSchema = [];

    /**
     * Возвращается справка по аргументам, используемых скриптом.
     *
     * @var  array<string, string>
     * @see ScriptInterface::getOptionSchema()
     */
    protected array $helpForArgs = [];

    #################################### Methods #####################################

    /**
     * @inheritdoc
     */
    public function getName(): string
    {
        return ($this->name ?? static::class);
    }

    /**
     * @inheritdoc
     */
    public function getOptionSchema(): array
    {
        return $this->optionSchema;
    }

    /**
     * @inheritdoc
     */
    public function getHelpForArgs(): array
    {
        return $this->helpForArgs;
    }

    /**
     * @inheritdoc
     * По умолчанию скрипт не использует собственные аргументы.
     */
    public function parseArgs(ConsoleArgs $arguments): array
    {
        return [];
    }

}