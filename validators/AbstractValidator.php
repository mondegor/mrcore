<?php
namespace mrcore\validators;
use mrcore\base\TraitFactory;

require_once 'mrcore/base/TraitFactory.php';

/**
 * Все классы наследуемые от Validator являются
 * компонентами проверки корректности заданных данных.
 *
 * @package    mrcore/validators
 * @uses       __(...)
 */
abstract class AbstractValidator
{
    use TraitFactory; // defined method factory($source, $params)

    /**
     * Основные коды возможных ошибок валидатора.
     */
    public const EMPTY_VALUE          = 1,
                 INVALID_VALUE        = 2,
                 INVALID_VALUES       = 3,
                 INVALID_LENGTH       = 4,
                 INVALID_LENGTH_MIN   = 5,
                 INVALID_LENGTH_MAX   = 6,
                 INVALID_RANGE        = 7,
                 INVALID_VALUE_MIN    = 8,
                 INVALID_VALUE_MAX    = 9,
                 VALUE_NOT_EXISTS     = 10,
                 VALUE_ALREADY_EXISTS = 11,
                 INVALID_SPECIAL      = 12;

    /**
     * Сообщение об неизвестной для обработчика ошибке.
     */
    private const _ERROR_UNKNOWN = 'Code error: %04u [validator: %s]';

    ################################### Properties ###################################

    /**
     * Namespace по умолчанию используемой в TraitFactory::factory(),
     * для подстановки в $source если в нём не был указан свой namespace.
     *
     * @var string
     */
    private static string $_defaultNamespace = 'mrcore\validators';

    /**
     * Произвольные атрибуты валидатора.
     *
     * @param      array [string => mixed, ...]
     */
    protected array $_attrs = [];

    /**
     * Сообщения об ошибках валидатора соответствующим кодам ошибок.
     *
     * @param      array [int => string|[string, ...]]
     */
    protected array $_errors = [];

    #################################### Methods #####################################

    /**
     * Конструктор класса.
     *
     * @param      array  $attrs  [string => mixed, ...] OPTIONAL,
     * @param      array  $errors [int => string | [string, ...]]
     */
    public function __construct(array $attrs = [], array $errors = [])
    {
        // определение обязательного ID элемента
        if (empty($this->_attrs['id']))
        {
            $this->_attrs['id'] = 'vfid';
        }

        // переопределение стандартных значений атрибутов
        foreach ($attrs as $key => $value)
        {
            assert(array_key_exists($key, $this->_attrs), sprintf('Key "%s" not found in array $this->_attrs.', $key));

            if (array_key_exists($key, $this->_attrs))
            {
                $this->_attrs[$key] = $value;
            }
        }

        // переопределение стандартных ошибок
        foreach ($errors as $key => $value)
        {
            assert(isset($this->_errors[$key]), 'key=' . $key . ' not found in array $this->_errors');

            if (isset($this->_errors[$key]))
            {
                $this->_errors[$key] = $value;
            }
        }
    }

    /**
     * Запускается валидация указанных данных.
     *
     * @param      mixed  $value (if array: [value => mixed, id => string OPTIONAL, string => mixed OPTIONAL ...])
     * @param      array  $listErrors OPTIONAL &[[string, string], ...]
     * @return     bool
     */
    public function __invoke($value, array &$listErrors = null): bool
    {
        return $this->validate($value, $listErrors);
    }

//     /**
//      * Возвращаются все атрибуты валидатора в виде стоки.
//      *
//      * @return     string
//      */
//     public function __toString(): string
//     {
//         $string = get_class($this) . ' :: ';

//         foreach ($this->_attrs as $name => $value)
//         {
//             if (is_array($value))
//             {
//                 $value = implode(', ', $value);
//             }

//             $string .= $name . ' = ' . $value . '; ';
//         }

//         return $string;
//     }

    /**
     * Запускается валидация указанных данных.
     *
     * @param      mixed  $value (if array: [value => mixed, id => string OPTIONAL, string => mixed OPTIONAL ...])
     * @param      array  $listErrors OPTIONAL &[[string, string], ...]
     * @return     bool
     */
    public function validate($value, array &$listErrors = null): bool
    {
        if (null === $listErrors)
        {
            $listErrors = [];
        }

        if (is_array($value))
        {
            if (!isset($value['value']))
            {
                $value['value'] = '';
            }
        }
        else
        {
            $value = ['value' => $value];
        }

        return $this->_validate($value, $listErrors);
    }

    /**
     * Добавление ошибки в массив $listErrors по её зарегистрированному в валидаторе коду.
     * Как правило, используется внутри метода _validate().
     *
     * Массив $data должен обязательно содержать следующие значения:
     *     [id] - уникальный идентификатор проверяемого поля;
     *     [value] - текущее значение поля;
     *
     * @param      int  $errorCode
     * @param      array  $data &[value => mixed, id => string OPTIONAL, string => mixed OPTIONAL, ...]
     * @param      array  $listErrors &[[string, string], ...]
     */
    public function addErrorByCode(int $errorCode, array &$data, array &$listErrors): void
    {
        // поиск кода ошибки в массиве ошибок
        if (isset($this->_errors[$errorCode]))
        {
            $message = $this->_getErrorMessage($this->_errors[$errorCode], $data);
        }
        // код ошибки не найден ни в одном массиве,
        // поэтому генерируется сообщение об ошибке по умолчанию
        else
        {
            $message = sprintf(self::_ERROR_UNKNOWN, $errorCode, get_class($this));
        }

        ##################################################################################

        // если сообщение сформировано не пустое (а такое может быть), то оно добавляется
        if ('' !== $message)
        {
            $listErrors[] = [$this->_getAttr('id', $data), $message]; // $errorCode
        }
    }

    /**
     * Проверка правильности данных.
     *
     * Массив $data должен обязательно содержать следующие значения:
     *     [id] - уникальный идентификатор проверяемого поля;
     *     [value] - текущее значение поля;
     *
     * @param      array  $data [value => mixed, id => string OPTIONAL, string => mixed OPTIONAL, ...]
     * @param      array  $listErrors &[[string, string], ...]
     * @return     bool
     */
    abstract protected function _validate(array $data, array &$listErrors): bool;

    /**
     * Возвращается значение атрибута или его переопределённое значение.
     *
     * @param      string  $name
     * @param      array  $data &[value => mixed, id => string OPTIONAL, string => mixed OPTIONAL, ...]
     * @return     mixed
     */
    protected function _getAttr(string $name, array &$data)
    {
        if (!isset($data[$name]))
        {
            $data[$name] = $this->_attrs[$name];
        }

        return $data[$name];
    }

    /**
     * Метод преднозначен для наследников класса, чтобы они могли
     * сформировать своё сообщение об ошибке по его имени.
     *
     * @param      string  $name
     * @param      array  $data [value => mixed, id => string OPTIONAL, string => mixed OPTIONAL, ...]
     * @return     bool
     */
    protected function _makeArgForMessage(string &$name, array $data): bool
    {
        return false;
    }

    /**
     * Получение сообщения об ошибке.
     *
     * @param      string|array $error [string, ...]
     * @param      array  $data [value => mixed, id => string OPTIONAL, string => mixed OPTIONAL, ...]
     * @return     mixed
     */
    /*__private__*/protected function _getErrorMessage($error, array $data)
    {
        assert(is_string($error) || is_array($error));

        if (!is_array($error))
        {
            return $error;
        }

        // первые два аргумента должны соответствовать
        // параметрам __($sentence, $default = '')
        $cnt = count($error);

        for ($i = 2; $i < $cnt; $i++)
        {
            $arg = &$error[$i];

            if (!$this->_makeArgForMessage($arg, $data))
            {
                $arg = $data[$arg] ?? ('{' . $arg . '}');
            }
        }

        return __(...$error);
    }

}