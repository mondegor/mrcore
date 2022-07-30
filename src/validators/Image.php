<?php declare(strict_types=1);
namespace mrcore\validators;

/*
     use mrcore\validators\Image;

    'image' => ['class' => Image::class]

    // OR

    use mrcore\validators\Image;

    ...

    'image' => [
        'class' => Image::class,
        'attrs' => [
            // 'maxSize'    => 0,
            // 'maxFormat'  => '10000x10000',
            // 'extensions' => [],
        ],
        'errors' => [
            Image::UPLOADED_NONE => __targs('Графический файл не был загружен при помощи HTTP POST'),
            Image::UPLOADED_EMPTY => __targs('Графический файл является пустым'),
            Image::INVALID_SIZE => __targs('Размер графического файла не должен превышать %s', 'maxSize'),
            Image::INVALID_EXTENSION => __targs('Недопустимое расширение загруженного графического файла'),
            Image::INVALID_IMAGE => __targs('Загруженный файл не является графическим'),
            Image::INVALID_FORMAT => __targs('Формат загруженного графического файла должен быть не больше %s. Текущий его формат %s', 'maxFormat', 'imageFormat'),
            Image::EMPTY_VALUE => __targs('Необходимо выбрать графический файл'),
        ],
    ],
*/

/**
 * Валидатор загружаемых изображений.
 *
 * @author  Andrey J. Nazarov
 */
class Image extends File
{
    /**
     * Коды возможных персональных ошибок валидатора.
     */
    public const INVALID_IMAGE  = 401, // загруженный файл не является графическим
                 INVALID_FORMAT = 402; // Формат загруженного графического файла некорректен

    #################################### Methods #####################################

    /**
     * @inheritdoc
     */
    public function __construct(array $attrs = [], array $errors = [])
    {
        $this->errors[File::UPLOADED_NONE] = __targs('Графический файл не был загружен при помощи HTTP POST');
        $this->errors[File::UPLOADED_EMPTY] = __targs('Графический файл является пустым');
        $this->errors[File::INVALID_SIZE] = __targs('Размер графического файла не должен превышать %s', 'maxSize');
        $this->errors[File::INVALID_EXTENSION] = __targs('Недопустимое расширение загруженного графического файла');
        $this->errors[self::INVALID_IMAGE] = __targs('Загруженный файл не является графическим');
        $this->errors[self::INVALID_FORMAT] = __targs('Формат загруженного графического файла должен быть не больше %s. Текущий его формат %s', 'maxFormat', 'imageFormat');
        $this->errors[self::EMPTY_VALUE] = __targs('Необходимо выбрать графический файл');

        // добавление атрибута "максимальный формат изображения"
        $this->attrs['maxFormat'] = '0x0'; // '10000x10000';

        parent::__construct($attrs, $errors);
    }
???????????????????
    /**
     * @inheritdoc
     */
    protected function _validate(array $data, array &$listErrors): bool
    {
        if ($result = parent::_validate($data, $listErrors))
        {
            if (!empty($data['value']['tmp_name']))
            {
                $imageFormat = empty($data['value']['imageFormat']) ? getimagesize($data['value']['tmp_name']) : $data['value']['imageFormat'];

                if (isset($imageFormat[0], $imageFormat[1]) && $imageFormat[0] > 0/*width*/ && $imageFormat[1] > 0/*height*/)
                {
                    $data['maxFormat'] = RectFormat::formatToString($data['maxFormat']);
                    $data['value']['imageFormat'] = $imageFormat;

                    if ('0x0' !== $data['maxFormat'] && in_array(RectFormat::compareFormats($imageFormat, $data['maxFormat']), [1, 2]))
                    {
                        $this->addErrorByCode(self::INVALID_FORMAT, $data, $listErrors);
                        $result = false;
                    }
                }
                else
                {
                    $this->addErrorByCode(self::INVALID_IMAGE, $data, $listErrors);
                    $result = false;
                }
            }
        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    protected function _makeArgForMessage(string &$name, array $data): bool
    {
        if (!($result = parent::_makeArgForMessage($name, $data)))
        {
            if ('imageFormat' === $name)
            {
                $name = RectFormat::formatToString($data['value']['imageFormat']);
                $result = true;
            }
        }

        return $result;
    }

}