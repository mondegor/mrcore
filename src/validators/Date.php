<?php declare(strict_types=1);
namespace mrcore\validators;
use mrcore\lib\Date as LibDate;

/*
   use mrcore\validators\Date;

   'date' => ['class' => Date::class]

    // OR

    use mrcore\validators\Date;

    ...

    'date' => [
        'class' => Date::class,
        'attrs' => [
            // 'format' => LibDate::DATE_ISO,
            // 'outputFormat' => Date::DATE_EU,
            // 'rangeLower' => null,
            // 'rangeUpper' => null,
        ],
        'errors' => [
            Date::INVALID_VALUE => __targs('Значение не соответствует формату "%s"', 'format'),
            Date::INVALID_RANGE => __targs('Значение должно находится в интервале от %s до %s', 'rangeLower', 'rangeUpper'),
        ],
    ],
*/

/**
 * Проверка на соответствие значения с заданным шаблоном,
 * а также проверка длины значения.
 *
 * При валидации используется формат в $attrs['format'],
 * но при формировании сообщения об ошибке выводится дата в формате $attrs['outputFormat']
 * Сделано для того, чтобы в браузере пользователь заполнял дату в $attrs['outputFormat'],
 * js приводил к формату $attrs['format'] и отправлял это значение на сервер.
 *
 * @author  Andrey J. Nazarov
 */
class Date extends AbstractValidator
{
    /**
     * @inheritdoc
     */
    protected int $dataTypes = self::DTYPE_STRING;

    /**
     * @inheritdoc
     */
    protected array $attrs = array
    (
        'format' => LibDate::DATE_ISO, // формат, который использует валидатор при проверке значения
        'outputFormat' => LibDate::DATE_EU, // при выводе ошибок пользователю даты указываются в данном формате
        'rangeLower' => null,
        'rangeUpper' => null,
    );

    #################################### Methods #####################################

    /**
     * @inheritdoc
     */
    public function __construct(array $attrs = [], array $errors = [])
    {
        $this->errors[self::INVALID_VALUE] = __targs('Значение не соответствует формату "%s"', 'format');
        $this->errors[self::INVALID_RANGE] = __targs('Значение должно находится в интервале от %s до %s', 'rangeLower', 'rangeUpper');
        $this->errors[self::INVALID_VALUE_MIN] = __targs('Значение должно быть не меньше %s', 'rangeLower');
        $this->errors[self::INVALID_VALUE_MAX] = __targs('Значение должно быть не больше %s', 'rangeUpper');

        parent::__construct($attrs, $errors);

        assert(is_string($this->attrs['format']));
        assert(is_string($this->attrs['outputFormat']));
        assert(null === $this->attrs['rangeLower'] || is_string($this->attrs['rangeLower']));
        assert(null === $this->attrs['rangeUpper'] || is_string($this->attrs['rangeUpper']));
    }

    /**
     * @inheritdoc
     */
    protected function _validate(array $data, array &$listErrors): bool
    {
        if (!$this->_isValidDate($data['value'], $data['format']))
        {
            $this->addErrorByCode(self::INVALID_VALUE, $data, $listErrors);
            return false;
        }

        // дата приводится к внутреннему формату если это необходимо
        $date = $this->_convertDate($data['value'], $data['format'], LibDate::DATE_ISO);

        if (null !== $data['rangeLower'])
        {
            if ($this->_getDateFromPattern($data['rangeLower']) > $date)
            {
                $this->addErrorByCode(isset($this->errors[self::INVALID_VALUE_MIN]) ? self::INVALID_VALUE_MIN : self::INVALID_RANGE, $data, $listErrors);
                return false;
            }
        }

        if (null !== $data['rangeUpper'])
        {
            if ($this->_getDateFromPattern($data['rangeUpper']) < $date)
            {
                $this->addErrorByCode(isset($this->errors[self::INVALID_VALUE_MAX]) ? self::INVALID_VALUE_MAX : self::INVALID_RANGE, $data, $listErrors);
                return false;
            }
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    protected function _makeArgForMessage(string &$name, array $data): bool
    {
        $outputFormat = $data['outputFormat'];

        if ('rangeLower' === $name || 'rangeUpper' === $name)
        {
            $name = $this->_convertDate($data[$name], LibDate::DATE_ISO, $outputFormat);
            return true;
        }

        // при валидации используется формат в $attrs['format'],
        // но при формировании сообщения об ошибке выводится дата в формате $attrs['outputFormat']
        if ('format' === $name)
        {
            $name = match ($outputFormat) {
                LibDate::DATE_EU => 'dd.mm.YYYY',
                LibDate::DATE_US => 'mm/dd/YYYY',
                LibDate::DATE_ISO => 'YYYY-mm-dd',
                default => $outputFormat,
            };

            return true;
        }

        return false;
    }

    ##################################################################################

    protected function _isValidDate(string $date, string $format): bool
    {
        return LibDate::isValidDate($date, $format);
    }

    protected function _getDateFromPattern(string $pattern): string
    {
        return LibDate::getDateFromPattern($pattern);
    }

    protected function _convertDate(string $convertDate, string $fromFormat, string $toFormat): string
    {
        return LibDate::convertDate($convertDate, $fromFormat, $toFormat);
    }

}