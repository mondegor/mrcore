<?php declare(strict_types=1);
namespace mrcore\validators;

/**
 * Абстракция компонента проверки корректности заданных данных.
 *
 * @author  Andrey J. Nazarov
 * @uses       __(...)
 */
abstract class AbstractValidator
{
    /**
     * Типы данных которые могут быть использованы
     * валидатором {@see AbstractValidator::$dataTypes}.
     */
    public const DTYPE_INT = 1,
                 DTYPE_FLOAT = 2,
                 DTYPE_STRING = 4,
                 DTYPE_ARRAY = 8;

    /**
     * Основные коды возможных ошибок валидатора.
     */
    public const INVALID_TYPE         = 0,
                 EMPTY_VALUE          = 1,
                 INVALID_VALUE        = 2,
                 INVALID_VALUES       = 3,
                 INVALID_LENGTH       = 4,
                 INVALID_LENGTH_EQUAL = 5,
                 INVALID_LENGTH_MIN   = 6,
                 INVALID_LENGTH_MAX   = 7,
                 INVALID_RANGE        = 8,
                 INVALID_VALUE_MIN    = 9,
                 INVALID_VALUE_MAX    = 10,
                 // VALUE_NOT_EXISTS     = 11,
                 // VALUE_ALREADY_EXISTS = 12,
                 INVALID_SPECIAL      = 13;

    /**
     * Сообщение об неизвестной для обработчика ошибке.
     */
    private const ERROR_UNKNOWN = 'Code error: %04u [validator: %s]';

    ################################### Properties ###################################

    /**
     * Поддерживаемые типы данных валидатором.
     * Если при проверке будет указан неподдерживаемый тип данных,
     * то валидатор вернёт ошибку INVALID_TYPE.
     */
    /*__abstract__*/ protected int $dataTypes;

    /**
     * Произвольные атрибуты валидатора.
     *
     * @param  array [string => mixed, ...]
     */
    protected array $attrs = [];

    /**
     * Сообщения об ошибках валидатора соответствующим кодам ошибок.
     *
     * @param  array [int => string | string[]] // [errorCode => errorMessage | [errorMessage, arg1, ...]]
     */
    protected array $errors = [self::INVALID_TYPE => 'The type of the specified value is not supported by the validator'];

    #################################### Methods #####################################

    /**
     * @param  array|null  $attrs [string => mixed, ...]
     * @param  array|null  $errors [int => string | string[]] // [errorCode => errorMessage | [errorMessage, arg1, ...]]
     */
    public function __construct(array $attrs = null, array $errors = null)
    {
        if (empty($this->attrs['id']))
        {
            $this->attrs['id'] = 'vfid';
        }

        if (null !== $attrs)
        {
            // переопределение стандартных значений атрибутов
            foreach ($attrs as $key => $value)
            {
                assert(array_key_exists($key, $this->attrs), sprintf('Key %s not found in array $this->attrs', $key));
                $this->attrs[$key] = $value;
            }
        }

        if (null !== $errors)
        {
            // переопределение стандартных ошибок
            foreach ($errors as $key => $value)
            {
                assert(isset($this->errors[$key]), sprintf('Key %s not found in array $this->errors', $key));
                $this->errors[$key] = $value;
            }
        }
    }

    /**
     * Запускается валидация указанныго значения.
     *
     * @param  string|int|float|array|null  $value [int => mixed, ...]
     * @param  array|null  $listErrors {@see AbstractValidator::validate():$listErrors}
     */
    public function __invoke(string|int|float|array|null $value, array &$listErrors = null): bool
    {
        return $this->validate(['value' => $value], $listErrors);
    }

//     /**
//      * Возвращаются все атрибуты валидатора в виде стоки.
//      */
//     public function __toString(): string
//     {
//         $string = get_class($this) . ' :: ';

//         foreach ($this->attrs as $name => $value)
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
     * Запускается валидация данных с указанными атрибутами.
     *
     * @param  array $data [value => mixed, id => string OPTIONAL, string => mixed OPTIONAL, ...]
     * @param  array|null $listErrors [[string, string], ...]
     */
    public function validate(array $data, array &$listErrors = null): bool
    {
        assert(array_key_exists('value', $data));

        if (null === $listErrors)
        {
            $listErrors = [];
        }

        // копирование атрибутов по умолчанию, которые отсутствуют в $data
        $data = array_replace($this->attrs, $data);

        // если указано проверить массив однотипных данных
        if (is_array($data['value']) && !empty($data['value']) && array_is_list($data['value']))
        {
            $items = $data['value'];
            $id = $data['id'];

            foreach ($items as $i => $value)
            {
                $data['id'] = sprintf("%s[%u]", $id,  $i);
                $data['value'] = $value;

                if (!$this->_validateValue($data, $listErrors))
                {
                    return false;
                }
            }

            return true;
        }

        return $this->_validateValue($data, $listErrors);
    }

    /**
     * Добавление ошибки в массив $listErrors по её зарегистрированному в валидаторе коду.
     * Как правило, используется внутри метода _validate().
     *
     * @param  array  $data {@see AbstractValidator::validate():$data}
     * @param  array  $listErrors {@see AbstractValidator::validate():$listErrors}
     */
    public function addErrorByCode(int $errorCode, array $data, array &$listErrors): void
    {
        // поиск кода ошибки в массиве ошибок
        if (isset($this->errors[$errorCode]))
        {
            $message = $this->_getErrorMessage($this->errors[$errorCode], $data);
        }
        // код ошибки не найден ни в одном массиве,
        // поэтому генерируется сообщение об ошибке по умолчанию
        else
        {
            $message = sprintf(self::ERROR_UNKNOWN, $errorCode, get_class($this));
        }

        ##################################################################################

        // если сообщение сформировано не пустое (а такое может быть), то оно добавляется
        if ('' !== $message)
        {
            $listErrors[] = [$data['id'], $message]; // $errorCode
        }
    }

    ##################################################################################

    /**
     * Проверка указанного значения данных.
     *
     * @param  array  $data {@see AbstractValidator::validate():$data}
     * @param  array  $listErrors {@see AbstractValidator::validate():$listErrors}
     */
    protected function _validateValue(array $data, array &$listErrors): bool
    {
        if (!$this->_checkDataType(gettype($data['value'])))
        {
            $this->addErrorByCode(self::INVALID_TYPE, $data, $listErrors);
            return false;
        }

        if ($this->_checkIfEmpty($data['value']))
        {
            return $this->_validateEmpty($data, $listErrors);
        }

        return $this->_validate($data, $listErrors);
    }

    /**
     * Проверяется, поддерживает ли валидатор указанный тип данных.
     */
    protected function _checkDataType(string $type): bool
    {
        $dataType = match ($type) {
            'string' => self::DTYPE_STRING,
            'integer' => self::DTYPE_INT,
            'double' => self::DTYPE_FLOAT,
            'array' => self::DTYPE_ARRAY,
            default/*'NULL'*/ => 0
        };

        return (0 === $dataType || ($this->dataTypes & $dataType) > 0);
    }

    /**
     * Проверяется, является ли указанное значение данных
     * абсолютно пустым (не содержит символа "0" или 0, false)
     */
    protected function _checkIfEmpty(string|int|float|array|null $value): bool
    {
        return empty($value) && (null === $value || '' === $value || is_array($value));
    }

    /**
     * Проверка данных в случае если они пустые, по умолчанию всегда true.
     *
     * @param  array  $data {@see AbstractValidator::validate():$data}
     * @param  array  $listErrors {@see AbstractValidator::validate():$listErrors}
     */
    protected function _validateEmpty(array $data, array &$listErrors): bool
    {
        return true;
    }

    /**
     * Валидация не пустых данных.
     *
     * @param  array  $data {@see AbstractValidator::validate():$data}
     * @param  array  $listErrors {@see AbstractValidator::validate():$listErrors}
     */
    abstract protected function _validate(array $data, array &$listErrors): bool;

    /**
     * Метод преднозначен для наследников класса, чтобы они могли
     * сформировать своё сообщение об ошибке по его имени.
     *
     * @param  array  $data {@see AbstractValidator::validate():$data}
     */
    protected function _makeArgForMessage(string &$name, array $data): bool
    {
        return false;
    }

    /**
     * Возвращается сообщение об ошибке.
     *
     * @param  string|string[] $error // [errorMessage, arg1, ...]
     * @param  array        $data {@see AbstractValidator::validate():$data}
     */
    protected function _getErrorMessage(string|array $error, array $data): string|array
    {
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