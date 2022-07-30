<?php declare(strict_types=1);
namespace mrcore\units;
use mrcore\base\HttpResponse;
use mrcore\exceptions\CoreException;
use RuntimeException;
use mrcore\base\EnumType;
use mrcore\validators\AbstractValidator;
use mrcore\validators\ItemList;
use mrcore\validators\NotEmpty;
use mrcore\validators\Length;
use mrcore\validators\Number;
use mrcore\validators\Value;

/**
 * Данный трейд внедряется в классы наследуемые от mrcore\units\AbstractAction,
 * он производит валидацию данных с помощью метода _validate(), сами данные должны быть
 * заранее заданы в экшене в методе _getExpectedRequest().
 *
 * Примеры формата, в котором функция _getExpectedRequest данные о валидируемых полях:
 *
 *   // короткая запись без указанния валидаторов
 *  'fieldNameShortParams' => ['type' => EnumType::*, 'isRequire' => true, 'length' => [5, 10], 'default' => 'valueByDefault'],
 *
 *  // расширенная запись с указаннием валидатора
 *  'fieldNameLongParams' => array
 *  (
 *      'type' => EnumType::*,
 *      'isRequire' => true,
 *      'requireError' => 'Field %s is empty',
 *      'length' => [5, 100],
 *      'default' => 'valueByDefault',
 *
 *      'validators' => array
 *      (
 *          'email' => array
 *          (
 *              'class' => Email::class,
 *
 *              //'attrs' => array
 *              //(
 *              //    'multy' => true,
 *              //    'separator' => ',',
 *              //    'maxLength' => 1000,
 *              //),
 *
 *              //'errors' => array
 *              //(
 *              //    Email::INVALID_VALUE => __targs('Указанный e-mail не является электронным адресом'),
 *              //    // Email::INVALID_VALUES => __targs('E-mail адреса должны быть разделены знаком "%s"', 'separator'),
 *              //    Email::INVALID_LENGTH_MAX => __targs('E-mail адрес должен быть не длиннее %d символов', 'maxLength'),
 *              //),
 *          ),
 *          ...
 *      )
 *  );
 *
 * @author  Andrey J. Nazarov
 * @uses       $this->getResponse()
 * @uses       $this->section->serviceBag->getService(...)
 */
trait TraitRequestValidation
{
    /**
     * Проверенные и обработанные параметры из внешнего запроса.
     *
     * @return array  [string => mixed, ...]
     */
    protected array $validRequest = [];

    #################################### Methods #####################################

    /**
     * Возвращается описание ожидаемых параметров из внешнего запроса.
     *
     * @return array [[type => EnumType::* OPTIONAL,
     *                 isRequire => true OPTIONAL,
     *                 length => int|array OPTIONAL, [int, int] // [min length, max length]
     *                 interval => array OPTIONAL, [int|float, int|float] // [min value, max value]
     *                 pattern => string OPTIONAL,
     *                 default => mixed OPTIONAL,
     *                 list => array OPTIONAL,
     *                 validators => [string => [class => string, attrs => [string => mixed, ...], errors => [int => string, ...]], ...]], ...]
     */
    abstract protected function _getExpectedRequest(): array;

    /**
     * Валидация параметров поступивших из внешнего запроса.
     * :WARNING: НУЖНО ВРУЧНУЮ ВЫЗВАТЬ В ACTION
     */
    protected function _validate(): bool
    {
        $response = $this->section->getResponse();

        if ($response->getStatusCode() > 0)
        {
            throw new RuntimeException(sprintf('Статус ответа уже был ранее установлен в значение %u, поэтому валидация отменяется', $response->getStatusCode()));
        }

        if ($response->hasStringContent())
        {
            throw CoreException::responseContentIsAllReadyExists($response->getContent());
        }

        $expectedRequest = $this->_getExpectedRequest();

        if ($this->_baseValidate($expectedRequest))
        {
            $this->_actionValidate($response, $this->validRequest);
        }

        if ($response->getStatusCode() >= ResponseInterface::HTTP_BAD_REQUEST)
        {
            return false;
        }

        // все параметры запроса, у которых было указано явное приведение к типу
        foreach ($expectedRequest as $name => $paramMeta)
        {
            if (null !== $this->validRequest[$name] && isset($paramMeta['cast']))
            {
                if (empty($paramMeta['multy']))
                {
                    $this->validRequest[$name] = EnumType::cast($paramMeta['cast'], $this->validRequest[$name]);
                    continue;
                }

                $this->validRequest[$name] = array_map
                (
                    static fn ($value) => EnumType::cast($paramMeta['cast'], $value),
                    $this->validRequest[$name]
                );
            }
        }

        return true;
    }

    /**
     * Базовая валидация параметров внешнего запроса.
     *
     * @param  array $expectedRequest {@see TraitRequestValidation::_getExpectedRequest()}
     */
    protected function _baseValidate(array $expectedRequest): bool
    {
        $request = $this->section->serviceBag->getRequest();
        $requestErrors = [];

        foreach ($expectedRequest as $name => $paramMeta)
        {
            assert(empty($paramMeta['cast']) || is_int($paramMeta['cast']));
            assert(empty($paramMeta['type']) || (is_int($paramMeta['type']) && in_array($paramMeta['type'], [EnumType::STRING, EnumType::INT, EnumType::FLOAT, EnumType::BOOL, EnumType::ARRAY], true)));
            assert(empty($paramMeta['length']) || empty($paramMeta['type']) || EnumType::STRING === $paramMeta['type'], 'При указании length тип параметра должен быть STRING');
            assert(empty($paramMeta['length']) || is_int($paramMeta['length']) || (is_array($paramMeta['length']) && isset($paramMeta['length'][0], $paramMeta['length'][1])), 'При указании length в качестве массива должы быть указаны min и max длины');
            assert(empty($paramMeta['interval']) || empty($paramMeta['type']) || in_array($paramMeta['type'], [EnumType::STRING, EnumType::INT, EnumType::FLOAT], true), 'При указании interval тип параметра должен быть STRING, INT или FLOAT');
            assert(empty($paramMeta['interval']) || (is_array($paramMeta['interval']) && array_key_exists(0, $paramMeta['interval']) && array_key_exists(1, $paramMeta['interval'])));
            assert(empty($paramMeta['pattern']) || is_string($paramMeta['pattern']));
            assert(empty($paramMeta['list']) || empty($paramMeta['type']) || in_array($paramMeta['type'], [EnumType::STRING, EnumType::INT], true), 'При указании list тип параметра должен быть STRING или INT');

            $value = null;

            if ($request->has($name))
            {
                $valueType = ($paramMeta['type'] ?? EnumType::STRING);
                $value = match (empty($paramMeta['multy']) ? $valueType : EnumType::ARRAY) {
                    EnumType::INT => $request->getInt($name, $paramMeta['default'] ?? null),
                    EnumType::FLOAT => $request->getFloat($name, $paramMeta['default'] ?? null),
                    EnumType::BOOL => $request->getInt($name, $paramMeta['default'] ?? null) > 0,
                    EnumType::ARRAY => $request->getArray($valueType, $name, $paramMeta['default'] ?? null),
                    default => $request->get($name, $paramMeta['default'] ?? null),
                };
            }

            ##################################################################################

            $validators = $paramMeta['validators'] ?? [];

            // если была указана настройка length, то регистрируется валидатор 'length'
            if (/*empty($validators['length']) && */!empty($paramMeta['length']))
            {
                $length = is_array($paramMeta['length']) ?
                              $paramMeta['length'] :
                              [$paramMeta['length'], $paramMeta['length']];

                $attrs = ['minLength' => $length[0], 'maxLength' => $length[1]];
                $validators['length'] = ['class' => Length::class, 'attrs' => $attrs];
            }

            // если была указана настройка interval, то регистрируется валидатор 'number'
            if (/*empty($validators['number']) && */!empty($paramMeta['interval']))
            {
                $attrs = ['minValue' => $paramMeta['interval'][0], 'maxValue' => $paramMeta['interval'][1]];
                $validators['number'] = ['class' => Number::class, 'attrs' => $attrs];
            }

            // если была указана настройка pattern, то регистрируется валидатор 'value'
            if (/*empty($validators['value']) && */!empty($paramMeta['pattern']))
            {
                $validators['value'] = ['class' => Value::class, 'attrs' => ['pattern' => $paramMeta['pattern']]];
            }

            // если был указан список возможных значений, то регистрируется валидатор 'itemlist'
            if (/*empty($validators['itemlist']) && */!empty($paramMeta['list']))
            {
                $attrs = ['items' => $paramMeta['list']];
                $validators['itemlist'] = ['class' => ItemList::class, 'attrs' => $attrs];
            }

            // если было указано требование isRequire = true, то регистрируется валидатор 'notempty'
            if (/*empty($validators['notempty']) && */!empty($paramMeta['isRequire']))
            {
                $attrs = isset($paramMeta['emptyValue']) ? ['emptyValue' => $paramMeta['emptyValue']] : [];
                $errors = empty($paramMeta['requireError']) ? [] : [AbstractValidator::EMPTY_VALUE => $paramMeta['requireError']];
                $validators['notempty'] = ['class' => NotEmpty::class, 'attrs' => $attrs, 'errors' => $errors];
            }

            ##################################################################################

            // если у параметра зарегистрирован хотя бы один валидатор
            if (!empty($validators))
            {
                $data = array
                (
                    // передача в валидатор обязательного параметра - уникального идентификатора
                    'id' => $name,

                    // передача в валидатор названия обрабатываемого параметра
                    // 'caption' => $paramMeta['caption'] ?? $name,

                    // передача в валидатор значения обрабатываемого параметра
                    'value' => $value,
                );

                $hasError = false;

                foreach ($validators as $validatorParams)
                {
                    $validator = $this->_createValidator($validatorParams);

                    if (!$validator->validate($data, $requestErrors))
                    {
                        $hasError = true;
                        break;
                    }
                }

                if ($hasError)
                {
                    continue; // :WARNING: ошибка, переход к следующему параметру
                }
            }

            $this->validRequest[$name] = $value;
        }

        ##################################################################################

        $response = $this->section->getResponse();

        foreach ($requestErrors as $error)
        {
            $response->addErrorIntoContent($error);
        }

        return empty($requestErrors);
    }

    /**
     * Необязательный метод, для того чтобы экшеном провести
     * дополнительную валидацию параметров внешнего запроса.
     * Параметры $validRequest уже инициализированы и проверены
     * стандартными валидаторами, а также явно приведены к соответствующим типам
     * (если в настройках конкретного параметра запроса указан тип).
     *
     * :WARNING: ДЛЯ ПЕРЕОПРЕДЕЛЕНИЯ В ACTION
     *
     * @param  array $validRequest {@see TraitRequestValidation::$validRequest}
     */
    protected function _actionValidate(ActionResponseInterface $response, array &$validRequest): void { }

    ##################################################################################

    protected function _createValidator(array $params): AbstractValidator
    {
        return $params['class']
        (
            $params['attrs'] ?? null,
            $params['errors'] ?? null
        );
    }

}