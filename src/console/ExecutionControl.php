<?php declare(strict_types=1);
namespace mrcore\console;

/**
 * Контроль времени выполнения кода.
 *
 * @author  Andrey J. Nazarov
 */
class ExecutionControl
{
    /**
     * Время запуска кода (в секундах).
     */
    private int $startTime;

    /**
     * Счётчик вызовов метода isInterrupt.
     */
    private int $interruptCounter = 0;

    #################################### Methods #####################################

    /**
     * @param  int  $maxLifeTime // максимальное время исполнения кода, в секундах
     */
    public function __construct(private int $maxLifeTime)
    {
        assert($maxLifeTime >= 0);

        $this->startTime = time();
    }

    ##################################################################################

    /**
     * Проверяется наступило ли прерывание,
     * при котором можно выполнить какой-либо системный код.
     */
    public function isInterrupt(int $checkEvery = 1): bool
    {
        if ($checkEvery < 2)
        {
            return $checkEvery > 0;
        }

        return (0 === ((++$this->interruptCounter) % $checkEvery));
    }

    /**
     * Проверяется наступил ли таймаут,
     * при котором следует завершить выполнение кода.
     */
    public function isTimeout(): bool
    {
        if ($this->maxLifeTime < 1)
        {
            return false;
        }

        return $this->maxLifeTime < (time() - $this->startTime);
    }

    /**
     * Возвращается текущее время выполнения кода (в секундах).
     */
    public function geExecutionTime(): float
    {
        return time() - $this->startTime;
    }

}