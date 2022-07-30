<?php declare(strict_types=1);
namespace mrcore\validators;
use Exception;

/*
     use mrcore\validators\File;

    'file' => ['class' => File::class]

    // OR

    use mrcore\validators\File;

    ...

    'file' => [
        'class' => File::class,
        'attrs' => [
            // 'maxSize' => 0,
            // 'extensions' => [],
        ],
        'errors' => [
            File::UPLOADED_NONE => __targs('Файл не был загружен при помощи HTTP POST'),
            File::UPLOADED_EMPTY => __targs('Файл является пустым'),
            File::INVALID_SIZE => __targs('Размер файла не должен превышать %s', 'maxSize'),
            File::INVALID_EXTENSION => __targs('Недопустимое расширение загруженного файла'),
        ],
    ],
*/

/**
 * Валидатор загружаемых файлов.
 *
 * @author  Andrey J. Nazarov
 */
class File extends AbstractValidator
{
    /**
     * Коды возможных персональных ошибок валидатора.
     */
    public const UPLOADED_NONE     = 201, // файл не был загружен при помощи HTTP POST
                 UPLOADED_EMPTY    = 202, // загруженный файл пустой
                 INVALID_SIZE      = 203, // загруженный превышает допустимый размер
                 INVALID_EXTENSION = 204; // загруженный файл имеет некорректное расширение

    ################################### Properties ###################################

    /**
     * @inheritdoc
     */
    protected int $dataTypes = self::DTYPE_ARRAY;

    /**
     * @inheritdoc
     */
    protected array $attrs = array
    (
        'maxSize' => 0,
        'extensions' => [],
    );

    #################################### Methods #####################################

    /**
     * @inheritdoc
     */
    public function __construct(array $attrs = [], array $errors = [])
    {
        $this->errors[self::UPLOADED_NONE] = __targs('Файл не был загружен при помощи HTTP POST');
        $this->errors[self::UPLOADED_EMPTY] = __targs('Файл является пустым');
        $this->errors[self::INVALID_SIZE] = __targs('Размер файла не должен превышать %s', 'maxSize');
        $this->errors[self::INVALID_EXTENSION] = __targs('Недопустимое расширение загруженного файла');

        parent::__construct($attrs, $errors);
    }

    /**
     * @inheritdoc
     */
    protected function _validate(array $data, array &$listErrors): bool
    {
        if (empty($data['value']['tmp_name']))
        {
            return true;
        }

        $maxSize = $data['maxSize'];
        $extensions = $data['extensions'];

        if (is_uploaded_file($data['value']['tmp_name']))
        {
            if ($data['value']['error'] > 0)
            {
                throw new Exception(sprintf('Uploaded file have error #%u!', $data['value']['error']));
            }

            if (0 === $data['value']['size'])
            {
                $this->addErrorByCode(self::UPLOADED_EMPTY, $data, $listErrors);
                return false;
            }

            if ($maxSize > 0 && $maxSize < $data['value']['size'])
            {
                $this->addErrorByCode(self::INVALID_SIZE, $data, $listErrors);
                return false;
            }

            ##################################################################################
?????????????????
            if (!\mrcore\lib\File::checkExtension($data['value']['name'], $extensions))
            {
                $this->addErrorByCode(self::INVALID_EXTENSION, $data, $listErrors);
                return false;
            }

            return true;
        }

        $this->addErrorByCode(self::UPLOADED_NONE, $data, $listErrors);
        return false;
    }

    /**
     * @inheritdoc
     */
    protected function _makeArgForMessage(string &$name, array $data): bool
    {
        $result = false;

        if ('maxSize' === $name)
        {
            $name = \mrcore\lib\File::fileSizeToWords($data['maxSize']);
            $result = true;
        }

        return $result;
    }

}