<?php declare(strict_types=1);
namespace mrcore\console;
use mrcore\base\Environment;
use mrcore\services\AbstractServiceBag;

// :TODO: заменить объекты переданные в качестве параметров на интерфейсы

/**
 * Консольный вариант контейнера общих сервисов.
 *
 * @author  Andrey J. Nazarov
 */
class ServiceBag extends AbstractServiceBag
{

    public function __construct(private Environment $environment,
                                private Console $console,
                                private ConsoleArgs $consoleOptions) { }

    public function getEnv(): Environment
    {
        return $this->environment;
    }

    public function getConsole(): Console
    {
        return $this->console;
    }

    public function getOptions(): ConsoleArgs
    {
        return $this->consoleOptions;
    }

}