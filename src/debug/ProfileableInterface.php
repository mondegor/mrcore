<?php declare(strict_types=1);
namespace mrcore\debug;

/**
 * Интерфейс позволяющий профилировать запросы.
 *
 * @author  Andrey J. Nazarov
 */
interface ProfileableInterface
{
    /**
     * Возвращается объект профилирующего запросы.
     */
    public function getProfiler(): DatabaseProfiler|null;

}