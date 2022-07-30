<?php declare(strict_types=1);
namespace mrcore\debug;

/**
 * Формирование фрагмента кода, где произошло событие.
 *
 * @author  Andrey J. Nazarov
 */
class HelperCodeFragment
{

    public function __construct(private bool $isEnabled = true, private ?int $padding = null)
    {
        assert(null === $padding || $padding >= 0);

        if (null === $padding)
        {
            $this->padding = 5;
        }
    }

    /**
     * Возвращается фрагмент кода, где произошло событие.
     */
    public function getInfo(string $filePath, int $line): string
    {
        assert($line >= 0);

        if (!$this->isEnabled || !is_file($filePath))
        {
            return '';
        }

        if (false === ($fd = fopen($filePath, 'rb')))
        {
            return '';
        }

        $start = max(0, $line - $this->padding - 1);
        $end = $line + $this->padding - 1;

        $current = 0;
        $buff = '';
        $fragment = '';

        while (false !== ($bytes = fgets($fd, 1024)))
        {
            if ($current >= $start)
            {
                $buff .= $bytes;
            }

            // если чтение строки завершено (найден признак окончания строки)
            if (false !== strrpos($bytes, "\n"/*, strlen($bytes) - 2*/))
            {
                $this->_appendLine($fragment, $buff, $start, $current);

                $current++;

                if ($current > $end)
                {
                    break;
                }
            }
        }

        if ('' !== $buff && $current <= $end)
        {
            $this->_appendLine($fragment, $buff, $start, $current);
        }

        fclose($fd);

        return rtrim($fragment);
    }

    /**
     * Добавление очередной линии кода из буфера.
     */
    protected function _appendLine(string &$fragment, string &$buff, int $start, int $current): void
    {
        if ($current >= $start)
        {
            $fragment .= ($current + 1) . '] ' . $buff;
            $buff = '';
        }
    }

}