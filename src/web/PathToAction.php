<?php declare(strict_types=1);
namespace mrcore\web;

/**
 * Объект разобранного пути (URL) к экшену, с помощью него извлекаются
 * на основе указанных списков как системные элементы: текущая секция, язык,
 * так и элементы необходимые конкретному экшену.
 *
 * Пример полной структуры пути к экшену: [section] + [language] + [category, ...] + [action]
 *
 * @author  Andrey J. Nazarov
 *
 * @template  T_MRAPP_HOST_MAPPING
 */
class PathToAction
{
    /**
     * Путь, по которому достаточно обратиться, чтобы загрузить данный экшен (относительно хоста и секции сайта);
     * Он может отличаться от $rewriteFullPath тогда, когда экшен вызывается по умолчанию.
     *
     * @var    string[]
     */
    public array $rewritePath = [];

    /**
     * Успешно разобранный полный путь к экшену (относительно хоста и секции сайта);
     *
     * @var    string[]
     */
    public array $rewriteFullPath = [];

    /**
     * Остаток от успешно разобранного пути, который не удалось до конца разобрать фронт контроллеру.
     * :WARNING: данный путь необходимо доразобрать текущим экшеном (в противном случае система выдаст 404 ошибку).
     *
     * @var    string[]
     */
    public array $residuePath = [];

    #################################### Methods #####################################

    public function __construct(string $path)
    {
        $path = trim($path, '/');

        if ('' !== $path)
        {
            $this->residuePath = explode('/', $path);
        }
    }

    /**
     * Возвращается текущий элемент из остатка пути.
     */
    public function getCurrent(): string
    {
        return $this->residuePath[0] ?? '';
    }

    /**
     * Извлечение текущего элемента из остатка пути.
     */
    public function fetchCurrent(): string
    {
        $item = array_shift($this->residuePath);

        return $item ?? '';
    }

    /**
     * Извлечение текущего название секции из пути.
     *
     * @param  string $hostName // текущий адрес http хоста без префикса "www."
     * @param  T_MRAPP_HOST_MAPPING  $mapping
     */
    public function fetchSection(string $hostName, array $mapping): string
    {
        if (!isset($mapping[$hostName]))
        {
            $hostName = '*';
        }

        assert(isset($mapping['*']), 'Хост по умолчанию должен быть обязательно объявлен в $mapping');
        assert(isset($mapping[$hostName][0]), sprintf('Хост %s отсутствует в списке $mapping', $hostName));

        return $this->fetchItem($mapping[$hostName]);
    }

    /**
     * Извлечение текущего элемента из пути, если он находится в указанном списке.
     *
     * @param  string[]  $itemList
     */
    public function fetchItem(array $itemList): string
    {
        assert(array_is_list($itemList));

        // :WARNING: если название элемента по умолчанию совпадёт с названием первого элемента
        //           в массиве пути, то такой элемент не будет извлечён из массива пути
        //           (это сделано для того, чтобы к названию элемента по умолчанию из вне можно было
        //            обратиться только одним уникальным способом)

        if (!isset($this->residuePath[0]) ||
            !in_array($this->residuePath[0], $itemList, true) ||
            $this->residuePath[0] === $itemList[0])
        {
            $itemName = $itemList[0];
        }
        else
        {
            // иначе первое указанное значение является элементом из указанного списка
            $itemName = array_shift($this->residuePath);
            $this->rewritePath[] = $itemName;
        }

        $this->rewriteFullPath[] = $itemName;

        return $itemName;
    }

}