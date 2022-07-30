<?php declare(strict_types=1);
namespace mrcore;
use mrcore\console\AbstractPainter;
use mrcore\console\EnumColor;

/**
 * Класс для предназначен для вывода сообщений на экран.
 *
 * Примеры использования:
 *   MrScreen::setPainter($painter);
 *   MrScreen::echoNotice('message', $var1, $var2, ...);
 *   MrScreen::echoError('message', $var1, $var2, ...);
 *
 * @author  Andrey J. Nazarov
 */
/*__class_static__*/ class MrScreen
{
    /**
     * Объект раскраски сообщения.
     */
    private static ?AbstractPainter $_painter = null;

    #################################### Methods #####################################

    /**
     * Установка раскрасчика сообщений.
     */
    public static function setPainter(AbstractPainter $painter = null): void
    {
        self::$_painter = $painter;
    }

    /**
     * Вывод указанного сообщения в указанном цвете на экран.
     */
    public static function echo(string $message, int $doubleColor): void
    {
        static::_echo($message, [], $doubleColor);
    }

    /**
     * Вывод указанного сообщения на экран.
     *
     * @param  array  $args {@see MrScreen::_echo()}
     */
    public static function echoMessage(string $message, ...$args): void
    {
        static::_echo($message, $args, 0);
    }

    /**
     * Вывод указанного уведомления на экран.
     *
     * @param  array  $args {@see MrScreen::_echo()}
     */
    public static function echoNotice(string $message, ...$args): void
    {
        static::_echo($message, $args, EnumColor::WHITE_BLUE);
    }

    /**
     * Вывод указанного предупреждения на экран.
     *
     * @param  array  $args {@see MrScreen::_echo()}
     */
    public static function echoWarning(string $message, ...$args): void
    {
        static::_echo($message, $args, EnumColor::BLACK_YELLOW);
    }

    /**
     * Вывод указанной ошибки на экран.
     *
     * @param  array  $args {@see MrScreen::_echo()}
     */
    public static function echoError(string $message, ...$args): void
    {
        static::_echo($message, $args, EnumColor::WHITE_RED);
    }

    /**
     * Вывод указанного сообщения об успехе на экран.
     *
     * @param  array  $args {@see MrScreen::_echo()}
     */
    public static function echoSuccess(string $message, ...$args): void
    {
        static::_echo($message, $args, EnumColor::BLACK_GREEN);
    }

    /**
     * Обёртывание указанного сообщения указанным цветом.
     */
    public static function wrapColor(string $message, int $doubleColor): string
    {
        if (null === self::$_painter)
        {
            return $message;
        }

        return self::$_painter->coloring($message, $doubleColor);
    }

    /**
     * Вывод указанного сообщения на экран.
     * Если в классе инициализировано использование цвета,
     * то к сообщению этот цвет применяется.
     *
     * @param  array  $args [string|int|float|bool|null, ...]
     */
    protected static function _echo(string $message, array $args, int $doubleColor): void
    {
        $newLine = true;

        if (0 === strncmp($message, '!', 1))
        {
            $message = substr($message, 1);
            $newLine = false;
        }

        $message = vsprintf($message, $args);

        // :WARNING: обёртывание нужно делать именно после подставления аргументов,
        //           т.к. в ConsolePainter это может влиять на результат
        if ($doubleColor > 0 && null !== self::$_painter)
        {
            $message = self::$_painter->coloring($message, $doubleColor);
        }

        echo $message . ($newLine ? "\n" : '');
    }

}