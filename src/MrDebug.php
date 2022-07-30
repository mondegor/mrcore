<?php declare(strict_types=1);
namespace mrcore;
use mrcore\console\EnumColor;
use mrcore\console\EnumLiteral;
use mrcore\debug\Tools;

/**
 * Класс содержит набор методов для формирования блоков
 * отладочного кода и осуществления доступа к ним.
 * А также список других полезных методов применяющихся в отладке кода.
 *
 * @author  Andrey J. Nazarov
 * @uses       $_SERVER
 */
/*__class_static__*/ class MrDebug
{
    /**
     * Возвращается дамп указанной переменной.
     * :TODO: нужно доработать на подобии var_dump и с поддержкой цветов
     */
    public static function dump(mixed $value): void
    {
        echo json_encode($value) . PHP_EOL;
    }

    /**
     * Формирование callstack вызовов, в точке вызова данного метода.
     *
     * @param array|null $backTrace [[function => string, file => string, line => int], ...]
     */
    public static function backTrace(array $backTrace = null): void
    {
        if (null === $backTrace)
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

        $debuggingInfo = EnumLiteral::LINE_DOUBLE .
                         MrScreen::wrapColor(' Call Stack (Function [Location])', EnumColor::LIGHT_BLUE_BLACK) . PHP_EOL .
                         EnumLiteral::LINE_DASH;

        ##################################################################################

        for ($i = 0; $i < $count; $i++)
        {
            $item = &$backTrace[$i];

            $cellFunction = (isset($item['class']) ? MrScreen::wrapColor($item['class'], EnumColor::WHITE_BLACK) : '') .
                            (isset($item['type']) ? MrScreen::wrapColor($item['type'], EnumColor::LIGHT_RED_BLACK) : '') .
                            (isset($item['function']) ? MrScreen::wrapColor($item['function'] . '(' . (isset($item['args']) ? MrScreen::wrapColor(Tools::args2str($item['args']), EnumColor::LIGHT_BLUE_BLACK) : '') . ')', EnumColor::YELLOW_BLACK) : '');

            $cellLocation = (isset($item['file']) ? MrScreen::wrapColor($item['file'], EnumColor::CYAN_BLACK) . ':' : '') .
                            (isset($item['line']) ? MrScreen::wrapColor((string)$item['line'], EnumColor::LIGHT_RED_BLACK) : '');

            $debuggingInfo .= '' . MrScreen::wrapColor((string)($count - $i - 1) . ')', EnumColor::LIGHT_RED_BLACK) .
                              ' ' . $cellFunction . ' ' .
                              '[' . $cellLocation . ']' . PHP_EOL .
                              EnumLiteral::LINE_DASH;
        }

        echo $debuggingInfo . PHP_EOL;
    }

    ##################################################################################

    /**
     * Отладочный метод для визуально проверки
     * правильной настройки переменных окружения.
     */
    public static function env(): void
    {
        $vars = [
            'CONTENT_LENGTH',
            'CONTENT_TYPE',
            'HTTP_HOST',
            'HTTP_ORIGIN',
            'HTTP_REFERER',
            'HTTP_USER_AGENT',
            'HTTPS',
            'REMOTE_ADDR',
            'REMOTE_PORT',
            // 'QUERY_STRING',
            'REQUEST_METHOD',
            'REQUEST_SCHEME',
            'REQUEST_TIME_FLOAT',
            'REQUEST_URI',

            'HTTP_CLIENT_IP',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
        ];

        echo sprintf('%-30s %-6s %-5s %-6s', 'VARNAME', 'SERVER', 'ENV', 'EQUAL?') . PHP_EOL;

        foreach ($vars as $var)
        {
            $envValue = (getenv($var, true) ?: getenv($var));

            $isEnv = (false !== $envValue);
            $isEqual = null;

            if ($isServer = isset($_SERVER[$var]))
            {
                $isEqual = $isEnv && ($_SERVER[$var] === $envValue);
            }

            echo sprintf('%-30s [%-4s] [%-3s] [%-4s]',
                    $var,
                    ($isServer ? 'YES' : 'NO'),
                    ($isEnv ? 'YES' : 'NO'),
                    (null === $isEqual ? '----' : ($isEqual ? 'YES' : 'NO'))
                ) . PHP_EOL;
        }
    }

    /**
     * Prints a list of all currently declared classes.
     *
     * @param  string|string[]|null $packages
     */
    public static function classes(string|array $packages = null): void
    {
        var_dump(static::_packageFilter(get_declared_classes(), $packages));
    }

    /**
     * Prints a list of all currently declared interfaces.
     *
     * @param  string|string[]|null $packages
     */
    public static function interfaces(string|array $packages = null): void
    {
        var_dump(static::_packageFilter(get_declared_interfaces(), $packages));
    }

    /**
     * Prints a list of all currently declared traits.
     *
     * @param  string|string[]|null $packages
     */
    public static function traits(string|array $packages = null): void
    {
        var_dump(static::_packageFilter(get_declared_traits(), $packages));
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
     *
     * @return      string[]
     */
    public static function headers(): array
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

        return $headers;
    }

    ##################################################################################

    /**
     * Из указанного списка классов|интерфейсов|трейтов выбираются те классы,
     * которые входят в указанные пакеты (namespace).
     *
     * @param       string[] $classes
     * @param       string|string[] $packages
     * @return      string[]
     */
	protected static function _packageFilter(array $classes, string|array $packages): array
	{
	    assert(null === $packages || is_string($packages) || is_array($packages));

	    if (empty($packages))
        {
            return $classes;
        }

        $packages = (array)$packages;

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