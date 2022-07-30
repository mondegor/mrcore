<?php declare(strict_types=1);
namespace mrcore\debug;

/**
 * Класс предназначен для профилирования SQL запросов
 * исполняющихся в процессе работы приложения.
 *
 * @author  Andrey J. Nazarov
 */
class DatabaseProfiler
{
    /**
     * Типы возможных sql запросов.
     */
    public const CONNECT = 1,  // A connection operation or selecting a database;
                 QUERY   = 2,  // Any general database query that does not fit into the other constants;
                 INSERT  = 4,  // Adding new data to the database, such as SQL's INSERT;
                 UPDATE  = 8,  // Updating existing information in the database, such as SQL's UPDATE;
                 DELETE  = 16, // An operation related to deleting data in the database, such as SQL's DELETE, TRUNCATE;
                 SELECT  = 32, // Retrieving information from the database, such as SQL's SELECT;
                 ALL     = 63;

    /**
     * Соответствие названия типа своему коду.
     */
    public const TYPES = array
    (
        'CONNECT' => self::CONNECT,
        'QUERY'   => self::QUERY,
        'INSERT'  => self::INSERT,
        'UPDATE'  => self::UPDATE,
        'DELETE'  => self::DELETE,
        'SELECT'  => self::SELECT,
    );

    ################################### Properties ###################################

    /**
     * Битовое поле кодирует список типов SQL запросов, которые должны быть обработаны (все типы = 1).
     *
     * @see DatabaseProfiler::TYPES
     */
    private int $queryFilter;

    /**
     * Массив SQL запросов, которые были профилированны.
     *
     * @var    array [string => [text => string,
     *                           type => string,
     *                           start => float,
     *                           end => float,
     *                           time => float,
     *                           timep => float], ...]
     */
    private array $queryProfiles = [];

    #################################### Methods #####################################

    /**
     * Парсинг строки с названиями типов {@link DatabaseProfiler::TYPES} разделённых через запятую в битовое поле.
     *
     * @param string $queryFilter // query,insert,...
     */
    public static function parseQueryFilter(string $queryFilter, string &$error = null): int
    {
        $queryFilter = trim($queryFilter);

        if ('' === $queryFilter)
        {
            if (null !== $error)
            {
                $error = sprintf('DatabaseProfiler query filter %s is empty', $queryFilter);
            }

            return 0;
        }

        if ('ALL' === strtoupper($queryFilter))
        {
            return self::ALL;
        }

        if (preg_match('/^[a-z,]+$/i', $queryFilter) < 1)
        {
            if (null !== $error)
            {
                $error = sprintf('DatabaseProfiler query filter %s is incorrect', $queryFilter);
            }

            return 0;
        }

        ##################################################################################

        $result = 0;

        foreach (explode(',', strtoupper($queryFilter)) as $type)
        {
            $type = trim($type);

            if ('' === $type)
            {
                if (null !== $error)
                {
                    $error = sprintf('DatabaseProfiler query filter %s is incorrect', $queryFilter);
                }

                return 0;
            }

            if (!isset(self::TYPES[$type]))
            {
                if (null !== $error)
                {
                    $error = sprintf('DatabaseProfiler query filter %s is incorrect, type %s is unknown', $queryFilter, $type);
                }

                return 0;
            }

            $result += self::TYPES[$type];
        }

        return $result;
    }

    ##################################################################################

    /**
     * @param int $queryFilter {@see DatabaseProfiler::TYPES}
     */
    public function __construct(int $queryFilter)
    {
        assert($queryFilter > 0 && $queryFilter <= self::ALL);

        $this->queryFilter = $queryFilter;
    }

    /**
     * Начало профилирования запроса.
     *
     * @param int $queryType {@see DatabaseProfiler::TYPES}
     */
    public function queryStart(string $sql, int $queryType = 0): string
    {
        assert($queryType >= 0);

        if (0 === $queryType)
        {
            $queryType = $this->_getQueryType($sql);
        }

        if (0 === ($this->queryFilter & $queryType))
        {
            return '';
        }

        ##################################################################################

        // $sql = preg_replace('/[ \t](FROM|LEFT JOIN|RIGHT JOIN|INNER JOIN|JOIN|ON|WHERE|AND|GROUP BY|ORDER BY|LIMIT)[ \t]/i', PHP_EOL . '$1' . PHP_EOL, $sql);
        // $sql = preg_replace('/[\n\r]{2,}/', PHP_EOL, $sql);

        $sql2 = '';

        foreach (explode(PHP_EOL, $sql) as $line)
        {
            $line = trim($line);

            // если это не sql команда, то устанавливается отступ
            if (preg_match('/^(FROM|LEFT|RIGHT|INNER|JOIN|ON|WHERE|GROUP BY|ORDER BY|LIMIT)/i', $line) <= 0)
            {
                $line = '       ' . $line; // 7 пробелов
            }

            $sql2 .= '                        ' . $line . PHP_EOL; // 24 пробела
        }

        $sql = trim($sql2);

        ##################################################################################

        $token = (string)count($this->queryProfiles);

        $this->queryProfiles[$token] = array
        (
            'text'  => $sql,
            'type'  => $queryType,
            'start' => microtime(true),
            'end'   => 0,
        );

        return $token;
    }

    /**
     * Окончание профилирования запроса.
     */
    public function queryEnd(string $token): void
    {
        assert(isset($this->queryProfiles[$token]) && 0 === $this->queryProfiles[$token]['end']);

        $this->queryProfiles[$token]['end'] = microtime(true);
    }

    /**
     * Возвращаются все профилированные запросы.
     * Если указан $queryFilter, то будут возвращены
     * все запросы указанного типа или типов.
     *
     * @param   int|null $queryFilter {@see DatabaseProfiler::TYPES}
     * @return  array {@see DatabaseProfiler::$queryProfiles}
     */
    public function getQueryProfiles(int $queryFilter = null): array
    {
        if (null === $queryFilter)
        {
            $queryFilter = self::ALL;
        }

        $result = [];

        $all = $this->getTotalElapsedSecs($queryFilter);
        $i = 0;

        foreach ($this->queryProfiles as $_query)
        {
            if ($_query['end'] > 0 && ($_query['type'] & $queryFilter))
            {
                $result[$i] = $_query;
                $result[$i]['time']  = $_query['end'] > 0 ? ($_query['end'] - $_query['start']) : 0;
                $result[$i]['timep'] = $all > 0 ? ($result[$i]['time'] / $all) * 100 : 0;

                $i++;
            }
        }

        return $result;
    }

    /**
     * Возвращается суммарное время исполнения профилированных запросов.
     * Если указан $queryFilter, то будет возвращено суммарное время
     * запросов указанного типа или типов.
     *
     * @param  int|null $queryFilter {@see DatabaseProfiler::TYPES}
     */
    public function getTotalElapsedSecs(int $queryFilter = null): float
    {
        if (null === $queryFilter)
        {
            $queryFilter = self::ALL;
        }

        $result = 0.0;

        foreach ($this->queryProfiles as $_query)
        {
            if ($_query['end'] > 0 && ($_query['type'] & $queryFilter))
            {
                $result += ($_query['end'] - $_query['start']);
            }
        }

        return $result;
    }

    /**
     * Возвращается количество профилированных запросов.
     * Если указан $queryFilter, то будет возвращено количество
     * запросов указанного типа или типов.
     *
     * @param  int|null $queryFilter {@see DatabaseProfiler::TYPES}
     */
    public function getTotalNumQueries(int $queryFilter = null): int
    {
        if (null === $queryFilter)
        {
            $queryFilter = self::ALL;
        }

        $result = 0;

        foreach ($this->queryProfiles as $_query)
        {
            if ($_query['end'] > 0 && ($_query['type'] & $queryFilter))
            {
                $result++;
            }
        }

        return $result;
    }

    /**
     * Возвращается самый медленный запрос.
     * Если указан $queryFilter, то будет возвращён
     * самый медленный запрос из указанного типа или типов.
     *
     * @param   int|null $queryFilter {@see DatabaseProfiler::TYPES}
     * @return  array // item of {@see DatabaseProfiler::$queryProfiles}
     */
    public function getSlowestQuery(int $queryFilter = null): array
    {
        if (null === $queryFilter)
        {
            $queryFilter = self::ALL;
        }

        $result = array
        (
            'text'  => '',
            'type'  => 0,
            'start' => 0,
            'end'   => 0,
            'time'  => 0,
            'timep' => 0,
        );

        ##################################################################################

        $all = 0;

        foreach ($this->queryProfiles as $_query)
        {
            if ($_query['end'] > 0 && $_query['type'] & $queryFilter)
            {
                $time = $_query['end'] - $_query['start'];

                if ($time > $result['time'])
                {
                    $result = $_query;
                    $result['time'] = $time;
                }

                $all += $time;
            }
        }

        ##################################################################################

        if ($all > 0)
        {
            $result['timep'] = ($result['time'] / $all) * 100;
        }

        return $result;
    }

    /**
     * Автоматическое определение типа запроса.
     */
    protected function _getQueryType(string $sql): int
    {
        return match (strtoupper(substr(ltrim($sql), 0, 6))) {
            'SELECT' => self::SELECT,
            'INSERT' => self::INSERT,
            'UPDATE' => self::UPDATE,
            'DELETE', 'TRUNCA' => self::DELETE,
            default => self::QUERY,
        };
    }

}