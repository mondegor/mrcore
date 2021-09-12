<?php declare(strict_types=1);
use mrcore\base\EnumColors;
use mrcore\console\AbstractPainter;

require_once 'mrcore/base/EnumColors.php';
require_once 'mrcore/console/AbstractPainter.php';

/**
 * Класс для предназначен для вывода сообщений на экран.
 *
 * Примеры использования:
 *   MrScreen::setPainter($painter);
 *   MrScreen::echoNotice('message', $var1, $var2, ...);
 *   MrScreen::echoError('message', $var1, $var2, ...);
 *
 * @author     Andrey J. Nazarov <mondegor@gmail.com>
 * @package    mrcore
 */
/*__class_static__*/ class MrScreen
{
    /**
     * Объект раскраски сообщения.
     *
     * @var    AbstractPainter
     */
    private static ?AbstractPainter $_painter = null;

    #################################### Methods #####################################

    /**
     * Установка раскрасчика сообщений.
     *
     * @param      AbstractPainter $painter OPTIONAL
     */
    public static function setPainter(AbstractPainter $painter = null)
    {
        self::$_painter = $painter;
    }

    /**
     * Вывод указанного сообщения в указанном цвете на экран.
     *
     * @param      string  $message
     * @param      int     $doubleColor
     * @param      array  $args
     */
    public static function echo(string $message, int $doubleColor, ...$args): void
    {
        static::_echo($message, $args, $doubleColor);
    }

    /**
     * Вывод указанного сообщения на экран.
     *
     * @param      string  $message
     * @param      array  $args
     */
    public static function echoMessage(string $message, ...$args): void
    {
        static::_echo($message, $args, 0);
    }

    /**
     * Вывод указанного уведомления на экран.
     *
     * @param      string  $message
     * @param      array  $args
     */
    public static function echoNotice(string $message, ...$args): void
    {
        static::_echo($message, $args, EnumColors::LIGHT_BLUE_BLACK);
    }

    /**
     * Вывод указанного предупреждения на экран.
     *
     * @param      string  $message
     * @param      array  $args
     */
    public static function echoWarning(string $message, ...$args): void
    {
        static::_echo($message, $args, EnumColors::DARK_YELLOW_BLACK);
    }

    /**
     * Вывод указанной ошибки на экран.
     *
     * @param      string  $message
     * @param      array  $args
     */
    public static function echoError(string $message, ...$args): void
    {
        static::_echo($message, $args, EnumColors::RED_BLACK);
    }

    /**
     * Вывод указанного сообщения об успехе на экран.
     *
     * @param      string  $message
     * @param      array  $args
     */
    public static function echoSuccess(string $message, ...$args): void
    {
        static::_echo($message, $args, EnumColors::GREEN_BLACK);
    }

    /**
     * Обёртывание указанного сообщения указанным цветом.
     *
     * @param      string  $message
     * @param      int     $doubleColor
     * @return     string
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
     * @param      string  $message
     * @param      array  $args
     * @param      int  $doubleColor
     */
    /*__private__*/protected static function _echo(string $message, array $args, int $doubleColor): void
    {
        if (0 === ($cmp = strncmp($message, '!', 1)))
        {
            $message = substr($message, 1);
        }

        if ($doubleColor > 0 && null !== self::$_painter)
        {
            $message = self::$_painter->coloring($message, $doubleColor);
        }

        vprintf($message . (0 === $cmp ? '' : "\n"), $args);
    }

}