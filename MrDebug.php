<?php declare(strict_types=1);
use mrcore\debug\Tools;

require_once 'mrcore/Constants.php';
require_once 'mrcore/debug/Tools.php';

/**
 * Класс содержит набор методов для формирования блоков
 * отладочного кода и осуществления доступа к ним.
 *
 * А также список других полезных методов применяющихся в отладке кода.
 *
 * @author     Andrey J. Nazarov <mondegor@gmail.com>
 * @package    mrcore
 * @uses       $_SERVER
 */
class MrDebug
{
    /**
     * Специальная группа, при которой разрешен
     * вывод информации сразу со всех групп.
     *
     * @const  string
     */
    public const GROUP_ALL = 'ALL';

    /**
     * Типы уровней отображения информации пользователю.
     * Градации от подробной информации включая отладочную,
     * до самой краткой, отражающую суть происходящего.
     *
     * @const  string
     */
    public const L_DBG  = 0, // всё включая иформацию по отладке
                 L_FULL = 1, // заголовки и подробная информация
                 L_INFO = 2, // заголовки и информация
                 L_HEAD = 3; // только заголовки

    ################################### Properties ###################################

    /**
     * При true будет разрешено всем группам отображать данные.
     *
     * @var  bool
     */
    private static bool $_isAllGroups = true;

    /**
     * Группы, для которых разрешено отображать данные.
     *
     * @var  array [string, ...]
     */
    private static array $_groups = array();

    /**
     * Текущий уровень отображения информации.
     *
     * @var  int [L_DBG, L_FULL, L_INFO, L_HEAD]
     */
    private static int $_level = self::L_DBG;

    #################################### Methods #####################################

    /**
     * Установка групп, для которых разрешено отображать данные.
     *
     * @param   string|array $names (name1 or [string, ...])
     * @throws  InvalidArgumentException
     */
	public static function setGroups($names): void
	{
	    assert(is_string($names) || is_array($names));

	    if (is_string($names))
        {
            $names = explode(',', $names);
        }

        $groups = [];
        $isAllGroups = false;

	    foreach ($names as $name)
        {
            if (!is_string($name) || preg_match('/^[a-z0-9.\-_]+$/i', $name) <= 0)
            {
                throw new InvalidArgumentException(sprintf('The group name "%s" is incorrect in %s', $name, self::class));
            }

            if (self::GROUP_ALL === $name)
            {
                $isAllGroups = true;
                continue;
            }

            $groups[trim($name)] = true;
        }

        self::$_groups = $isAllGroups ? array() : $groups;
        self::$_isAllGroups = $isAllGroups;
	}

    /**
     * Установка уровня отображения информации пользователю.
     *
     * @param   int $value [L_DBG, L_FULL, L_INFO, L_HEAD]
     * @throws  OutOfRangeException
     */
    public static function setLevel(int $value): void
    {
        if ($value < self::L_DBG || $value > self::L_HEAD)
        {
            throw new OutOfRangeException(sprintf('Level %d out of range in %s', $value, self::class));
        }

        self::$_level = $value;
    }

    /**
     * Проверяется доступна ли указанная группа: числится ли она
     * в списке групп и соответствует ли её указанный уровень текущему.
     *
     * Варианты задания
     *
     * @param   string $name (name1)
     * @return  bool
     * @throws  RuntimeException
     */
	public static function isGroupEnabled(string $name): bool
    {
        if (self::$_isAllGroups && self::L_DBG === self::$_level)
        {
            return true;
        }

        if (preg_match('/^([a-z0-9.\-_]+)(?::([0123]))?$/i', $name, $m) <= 0)
        {
            throw new RuntimeException(sprintf('Group name "%s" is incorrect in %s', $name, self::class));
        }

        $groupName = $m[1];
        $level = isset($m[2]) ? (int)$m[2] : self::L_DBG;

        return self::$_level <= $level && (self::$_isAllGroups || isset(self::$_groups[$groupName]));
    }

    /**
     * Возвращается дамп указанной переменной.
     * :TODO: нужно доработать на подобии var_dump и с поддержкой цветов
     *
     * @param   mixed $value
     */
    public static function dump($value): void
    {
        echo json_encode($value) . "\n";
    }

    /**
     * Формирование callstack вызовов, в точке вызова данного метода.
     *
     * @param array $backTrace OPTIONAL
     */
    public static function backTrace(array $backTrace = []): void
    {
        if (empty($backTrace))
        {
            $backTrace = debug_backtrace();

            // выкидывается информация о вхождении в данный метод класса
            $first = array_shift($backTrace);
        }
        else
        {
            $first = $backTrace[0] ?? [];
        }

        $count = count($backTrace) + 1;

        if ($count > 1 && isset($backTrace[$count - 2]['file']))
        {
            $first['file'] = $backTrace[$count - 2]['file'];
        }

        // вставляется информация о точке входа приложения
        $backTrace[] = array
        (
            'function' => '{main}',
            'file'     => $first['file'] ?? '',
            'line'     => 1,
        );

        ##################################################################################

        $debuggingInfo = MRCORE_LINE_DOUBLE .
                         ' Call Stack (Function [Location])' . PHP_EOL .
                         MRCORE_LINE_DASH;

        ##################################################################################

        for ($i = 0; $i < $count; $i++)
        {
            $item = &$backTrace[$i];

            $cellFunction = ($item['class'] ?? '') .
                            ($item['type'] ?? '') .
                            (isset($item['function']) ? $item['function'] . '(' . (isset($item['args']) ? Tools::args2str($item['args']) : '') . ')' : '');

            $cellLocation = (isset($item['file']) ? $item['file'] . ':' : '') .
                            ($item['line'] ?? '');

            $debuggingInfo .= '' . ($count - $i - 1) . ') ' .
                              '' . $cellFunction . ' ' .
                              '[' . $cellLocation . ']' . PHP_EOL .
                              MRCORE_LINE_DASH;
        }

        echo $debuggingInfo;
    }

    ##################################################################################

	/**
	 * Prints a list of all currently declared classes.
     *
     * @param   string|array $packages OPTIONAL
	 */
	public static function classes($packages = null): void
	{
		var_dump(self::_packageFilter(get_declared_classes(), $packages));
	}

	/**
	 * Prints a list of all currently declared interfaces.
     *
     * @param   string|array $packages OPTIONAL
	 */
	public static function interfaces($packages = null): void
	{
		var_dump(self::_packageFilter(get_declared_interfaces(), $packages));
	}

	/**
	 * Prints a list of all currently declared traits.
     *
     * @param   string|array $packages OPTIONAL
	 */
	public static function traits($packages = null): void
	{
		var_dump(self::_packageFilter(get_declared_traits(), $packages));
	}

	/**
	 * Prints a list of all currently included (or required) files.
	 */
	public static function includes(): void
	{
        var_dump(get_included_files());
	}

	///**
	// * Prints a list of all currently declared functions.
	// */
	//public static function functions(): void
	//{
    //    var_dump(get_defined_functions(true));
	//}

	/**
	 * Prints a list of all currently declared constants.
	 */
	public static function constants(): void
	{
        var_dump(get_defined_constants());
	}

	/**
	 * Prints a list of all currently loaded PHP extensions.
	 */
	public static function extensions(): void
	{
        var_dump(get_loaded_extensions());
	}

	/**
	 * Prints a list of all HTTP request headers.
	 */
	public static function headers(): void
	{
       $headers = [];

       foreach ($_SERVER as $name => $value)
       {
           if (0 === strncmp($name, 'HTTP_', 5))
           {
               // HTTP_CONTENT_TYPE -> Content-Type
               $headers[strtr(ucwords(strtolower(strtr(substr($name, 5), '_', ' '))), ' ', '-')] = $value;
           }
       }

       var_dump($headers);
	}

    ##################################################################################

    /**
     * Из указанного списка классов|интерфейсов|трейтов выбираются те классы,
     * которые входят в указанные пакеты (namespace).
     *
     * @param       array $classes
     * @param       string|array $packages
     * @return      array
     */
	private static function _packageFilter(array $classes, $packages): array
	{
	    assert(null === $packages || is_string($packages) || is_array($packages));

	    if (empty($packages))
        {
            return $classes;
        }

        $packages = (array)($packages);

	    return array_filter
	    (
	        $classes,
	        static function ($item) use ($packages) {
	            foreach ($packages as $package) {
	                $package = trim($package, '\\') . '\\';
	                $item = ltrim($item, '\\');

                    if (0 === strncmp($item, $package, strlen($package))) {
                        return true;
                    }
                }

                return false;
            }
        );
	}

}