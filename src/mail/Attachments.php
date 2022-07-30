<?php declare(strict_types=1);
namespace mrcore\mail;

/**
 * Контейнер прикреплённых файлов письма.
 *
 * @author  Andrey J. Nazarov
 */
class Attachments
{
    /**
     * Ассоциативный массив путей к файлам.
     *
     * @var   array map[string]string
     */
    private array $attachments = [];

    /**
     * Идентификатор границы используемой в письме.
     */
    private string $boundary = '';

    #################################### Methods #####################################

    public function __construct(private Mail $owner) { }

    /**
     * Проверка прикреплённости файла к письму по его имени.
     */
    public function isExists(string $name): bool
    {
        return isset($this->attachments[$name]);
    }

    /**
     * Прикрепление/изменение файлу к письму.
     */
    public function setAttachment(string $name, string $fileName): Attachments
    {
        $this->attachments[$name] = $fileName;

        return $this;
    }

    /**
     * Возвращается прикреплённый файл к письму по его имени.
     */
    public function getAttachment(string $name): string
    {
        return $this->attachments[$name] ?? '';
    }

    /**
     * Удаление файла из контейнера по его имени.
     */
    public function remove(string $name): Attachments
    {
        unset($this->attachments[$name]);

        return $this;
    }

    /**
     * Полное удаление файлов из контейнера.
     */
    public function clear(): Attachments
    {
        $this->attachments = [];

        return $this;
    }

    /**
     * Возвращается разделитель (используемый в письме).
     */
    public function getBoundary(): string
    {
        if ('' === $this->boundary)
        {
            $this->boundary = 'MIXED-' . md5((string)mt_rand());
        }

        return $this->boundary;
    }

    /**
     * Возвращается строка отображения заголовков.
     */
    public function toString(): string
    {
        if (empty($this->attachments))
        {
            return '';
        }

        $debugMode = in_array(MailDebug::getMode(), [MailDebug::DEBUG, MailDebug::UNITTEST], true);

        $result = '';
        $boundary = $this->getBoundary();

        foreach ($this->attachments as $name => $attachment)
        {
            if (!is_file($attachment))
            {
                continue;
            }

            $result .= '--' . $boundary . "\r\n" .
                       'Content-Type: ' . mime_content_type($attachment) . ';' . "\r\n\t" .
                           'name="' . ($debugMode ? $name : $this->_encode($name, $this->owner->charset)) . '"' . "\r\n" .
                       'Content-Disposition: attachment' . "\r\n" .
                       'Content-Transfer-Encoding: base64' . "\r\n\r\n" .
                       ($debugMode ? file_get_contents($attachment) : chunk_split(base64_encode(file_get_contents($attachment)))) . "\r\n";
        }

        if ('' !== $result)
        {
            $result .= '--' . $boundary . '--';
        }

        return $result;
    }

    ##################################################################################

    protected function _encode(string $value, string $charset): string
    {
        return Format::encode($value, $charset);
    }

}