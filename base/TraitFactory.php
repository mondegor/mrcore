<?php declare(strict_types=1);
namespace mrcore\base;
use RuntimeException;

/**
 * Код используется в классах, которые имеют наследников и объекты
 * которых создаются динамически, т.е. заренее не известно когда они
 * будут созданы и будут ли созданы вообще.
 *
 * :WARNING: данный код ДОЛЖЕН подключаться с объявлением и определением в классе
 *           свойства $_defaultNamespace, шаблон данного свойства указан ниже
 *
 * @author     Andrey J. Nazarov <mondegor@gmail.com>
 * @package    mrcore/base
 */
trait TraitFactory
{
    /**
     * Namespace по умолчанию используемой в TraitFactory::factory(),
     * для подстановки в $source если в нём не был указан свой namespace.
     *
     * @var string
     */
    // private static string $_defaultNamespace = '';

    #################################### Methods #####################################

    /**
     * Метод для создания объектов наследуемых от класса Class.
     * Перед созданием объекта подключается его файл с классом указанным в $source.
     * Если в значении $source отсуствует символ \, то считается, что подключаемый
     * класс располагается в той же директории, что и Class поэтому к $source
     * будет доавлена приставка \package\
     * Если создаваемый объект не является наследником Class, то сформируется
     * сообщение об ошибке с аварийным завершением работы программы.
     *
     * @param      string  $source (Class) or (\package\Class)
     * @param      array  $params OPTIONAL [string => mixed, ...]
     * @return     object
     * @throws     RuntimeException
     */
    public static function &factory(string $source, array $params = []): object
    {
        // в случае отсутствия символа \\ будет добавлен namespace текущего класса в качестве приставки
        if (false === strpos($source, '\\'))
        {
            $source = self::$_defaultNamespace . '\\' . $source;
        }

        require_once strtr(ltrim($source, '\\'), '\\', '/') . '.php';

        if (!class_exists($source, false))
        {
            throw new RuntimeException(sprintf('Class %s is not found', $source));
        }

        $result = new $source(...$params);

        if (!($result instanceof self))
        {
            throw new RuntimeException(sprintf('The created object of class %s is not an inheritor of class %s', get_class($result), static::class));
        }

        return $result;
    }

}