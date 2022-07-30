<?php declare(strict_types=1);
namespace mrcore;
use InvalidArgumentException;
use OutOfRangeException;
use RuntimeException;

/**
 * Класс помогает отображать/скрывать отладочную информацию
 * на основе заданных групп и уровня.
 * Основной метод {@see MrInfo::isGroupEnabled()} применяется в условиях исполнения блоков кода.
 * По умолчанию он всегда возвращает false (отключена вся отладочная информация),
 * но если предварительно вызвать метод {@see MrInfo::setGroups(MrInfo::GROUP_ALL)},
 * то он станет всегда возвращать true (вся отладочная информация будет отображена).
 * Методами {@see MrInfo::setGroups()} и {@see MrInfo::setLevel()} регулируется
 * когда методу {@see MrInfo::isGroupEnabled()} возвращать true, а когда false.
 *
 * @author  Andrey J. Nazarov
 */
/*__class_static__*/ class MrInfo
{
    /**
     * Специальная группа, при которой разрешен вывод отладочной информации
     * любой группы указанной в {@see MrInfo::isGroupEnabled()}.
     */
    public const GROUP_ALL = 'all';

    /**
     * Группа отображающая отладочную информацию
     * текущего консольного скрипта или экшена, а также их сервисов.
     */
    public const GROUP_CURRENT = 'current';

    /**
     * Уровни отображения отладочной информации.
     * Градации: от подробной информации включая отладочную,
     * до самой краткой, отражающую суть происходящего.
     */
    public const L_DBG  = 0, // всё, включая информацию по отладке
                 L_FULL = 1, // заголовки и подробная информация
                 L_INFO = 2, // заголовки и информация
                 L_HEAD = 3; // только заголовки

    ################################### Properties ###################################

    /**
     * При true будет разрешено всем группам отображать отладочную информацию.
     */
    private static bool $isAllGroups = false;

    /**
     * Список групп, для которых разрешено отображать отладочную информацию.
     *
     * @var  array [string => true, ...]
     */
    private static array $groups = [];

    /**
     * Текущий уровень отображения отладочной информации {@see MrInfo::L_DBG}.
     */
    private static int $level = self::L_FULL;

    #################################### Methods #####################################

    /**
     * Возвращаются текущие установленные группы.
     *
     * @return string[]
     */
    public static function getGroups(): array
    {
        if (self::$isAllGroups)
        {
            return [self::GROUP_ALL];
        }

        return array_values(self::$groups);
    }

    /**
     * Установка групп, для которых разрешено отображать отладочную информацию.
     * В режиме строки можно указать несколько групп разделённых запятой (,).
     * Если указать $names = [] или '', то отладочная информация будет отключена.
     *
     * @param  string|string[] $names
     * @throws InvalidArgumentException
     */
	public static function setGroups(string|array $names): void
	{
        $groups = [];
        $isAllGroups = false;

        if (!empty($names))
        {
            if (is_string($names))
            {
                $names = explode(',', $names);
            }

            foreach ($names as $name)
            {
                if (!is_string($name) || preg_match('/^[a-z][a-z0-9.\-_]*$/i', $name) <= 0)
                {
                    throw new InvalidArgumentException(sprintf('The group name "%s" is incorrect in %s', $name, self::class));
                }

                $nameLower = strtolower($name);

                if (self::GROUP_ALL === $nameLower)
                {
                    $isAllGroups = true;
                    continue;
                }

                $groups[$nameLower] = $name;
            }
        }

        self::$groups = $isAllGroups ? [] : $groups;
        self::$isAllGroups = $isAllGroups;
	}

    /**
     * Возвращается текущий установленный уровень отображения отладочной информации.
     */
    public static function getLevel(): int
    {
        return self::$level;
    }

    /**
     * Установка уровня отображения отладочной информации.
     *
     * @param   int $value {@see MrInfo::L_DBG}
     * @throws  OutOfRangeException
     */
    public static function setLevel(int $value): void
    {
        if ($value < self::L_DBG || $value > self::L_HEAD)
        {
            throw new OutOfRangeException(sprintf('Level %d out of range in %s', $value, self::class));
        }

        self::$level = $value;
    }

    /**
     * Проверяется активна ли указанная группа: числится ли она
     * в списке групп и соответствует ли её указанный уровень текущему.
     * Можно указать:
     *   - только название группы, тогда уровень по умолчанию будет соответствовать {@see MrInfo::L_DBG};
     *   - название группы и уровень группы в виде string:int // group1:2
     *
     * @param   string $name // group1 | group1:1
     */
	public static function isGroupEnabled(string $name): bool
    {
        // [L_DBG | L_FULL | L_INFO | L_HEAD]
        if (preg_match('/^([a-z0-9.\-_]+)(?::([0123]))?$/i', $name, $m) > 0)
        {
            $groupName = strtolower($m[1]);
            $level = isset($m[2]) ? (int)$m[2] : self::L_DBG;

            return self::$level <= $level && (self::$isAllGroups || isset(self::$groups[$groupName]));
        }

        throw new RuntimeException(sprintf('Group name "%s" is incorrect in %s', $name, self::class));
    }

}