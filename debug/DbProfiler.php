<?php declare(strict_types=1);
namespace mrcore\debug;

/**
 * Класс предназначен для профилирования SQL запросов
 * исполняющихся в процессе работы приложения.
 *
 * @author     Andrey J. Nazarov <mondegor@gmail.com>
 * @package    mrcore/debug
 */
class DbProfiler
{
    /**
     * Типы возможных sql запросов.
     */
    public const CONNECT = 1,  // A connection operation or selecting a database;
                 QUERY   = 2,  // Any general database query that does not fit into the other constants;
                 INSERT  = 4,  // Adding new data to the database, such as SQL's INSERT;
                 UPDATE  = 8,  // Updating existing information in the database, such as SQL's UPDATE;
                 DELETE  = 16, // An operation related to deleting data in the database, such as SQL's DELETE;
                 SELECT  = 32; // Retrieving information from the database, such as SQL's SELECT;

    ################################### Properties ###################################

    /**
     * Массив запросов, которые были произведены данным соединением.
     *
     * @var    array
     */
    private array $_queryProfiles = [];

    #################################### Methods #####################################

    /**
     * Начала профилирования запроса.
     *
     * @param      string  $sql
     * @param      int  $queryType OPTIONAL
     * @return     string
     */
    public function queryStart(string $sql, int $queryType = 0): string
    {
        if (0 === $queryType)
        {
            $queryType = $this->_getQueryType($sql);
        }

        ##################################################################################

        // $sql = preg_replace('/[ \t](FROM|LEFT JOIN|RIGHT JOIN|INNER JOIN|JOIN|ON|WHERE|AND|GROUP BY|ORDER BY|LIMIT)[ \t]/i', PHP_EOL . '$1' . PHP_EOL, $sql);
        // $sql = preg_replace('/[\n\r]{2,}/', PHP_EOL, $sql);

        $sql2 = '';

        foreach (explode(PHP_EOL, $sql) as $line)
        {
            $line = trim($line);

            // если это не sql команда, то устанавливается отступ
            if (!preg_match('/^(FROM|LEFT|RIGHT|INNER|JOIN|ON|WHERE|GROUP BY|ORDER BY|LIMIT)/i', $line))
            {
                $line = '       ' . $line; // 7 пробелов
            }

            $sql2 .= '                        ' . $line . PHP_EOL; // 24 пробела
        }

        $sql = trim($sql2);

        ##################################################################################

        $token = (string)count($this->_queryProfiles);

        $this->_queryProfiles[$token] = array
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
     *
     * @param      string  $token
     */
    public function queryEnd(string $token): void
    {
        assert(isset($this->_queryProfiles[$token]) && 0 === $this->_queryProfiles[$token]['end']);

        $this->_queryProfiles[$token]['end'] = microtime(true);
    }

    /**
     * Возвращаются все профилированные запросы.
     * Если указан $queryTypes, то будут возвращены
     * все запросы указанного типа или типов.
     *
     * @param      int  $queryTypes OPTIONAL
     * @param      bool  $showUnfinished OPTIONAL
     * @return     array
     */
    public function getQueryProfiles(int $queryTypes = 0, bool $showUnfinished = false): array
    {
        $result = [];

        $all = $this->getTotalElapsedSecs($queryTypes);

        foreach ($this->_queryProfiles as $token => &$_query)
        {
            if (($_query['end'] > 0 || $showUnfinished) && (0 === $queryTypes || $_query['type'] & $queryTypes))
            {
                $result[$token] = $_query;
                $result[$token]['time']  = $_query['end'] > 0 ? ($_query['end'] - $_query['start']) : 0;
                $result[$token]['timep'] = $all > 0 ? ($result[$token]['time'] / $all) * 100 : 0;
            }
        }

        return $result;
    }

    /**
     * Возвращается суммарное время исполнения профилированных запросов.
     * Если указан $queryTypes, то будет возвращено суммарное время
     * запросов указанного типа или типов.
     *
     * @param      int  $queryTypes OPTIONAL
     * @return     float
     */
    public function getTotalElapsedSecs(int $queryTypes = 0): float
    {
        $result = 0.0;

        foreach ($this->_queryProfiles as &$_query)
        {
            if ($_query['end'] > 0 && (0 === $queryTypes || $_query['type'] & $queryTypes))
            {
                $result += ($_query['end'] - $_query['start']);
            }
        }

        return $result;
    }

    /**
     * Возвращается количество профилированных запросов.
     * Если указан $queryTypes, то будет возвращено количество
     * запросов указанного типа или типов.
     *
     * @param      int  $queryTypes OPTIONAL
     * @return     int
     */
    public function getTotalNumQueries(int $queryTypes = 0): int
    {
        if (0 === $queryTypes)
        {
            return count($this->_queryProfiles);
        }

        $result = 0;

        foreach ($this->_queryProfiles as &$_query)
        {
            if ($_query['end'] > 0 && ($_query['type'] & $queryTypes))
            {
                $result++;
            }
        }

        return $result;
    }

    /**
     * Возвращение самого медленного запроса.
     * Если указан $queryTypes, то будет возвращён
     * самый медленный запрос из указанного типа или типов.
     *
     * @param      int  $queryTypes OPTIONAL
     * @return     array
     */
    public function getSlowestQuery(int $queryTypes = 0): array
    {
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

        foreach ($this->_queryProfiles as $token => $_query)
        {
            if ($_query['end'] > 0 && (0 === $queryTypes || $_query['type'] & $queryTypes))
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
     *
     * @author     Andrey J. Nazarov <mondegor@gmail.com>
     * @param      string  $sql
     * @return     int
     */
    private function _getQueryType(string $sql): int
    {
        $result = self::QUERY;

        switch (strtoupper(substr(ltrim($sql), 0, 6)))
        {
            case 'SELECT':
                $result = self::SELECT;
                break;

            case 'INSERT':
                $result = self::INSERT;
                break;

            case 'UPDATE':
                $result = self::UPDATE;
                break;

            case 'DELETE':
                $result = self::DELETE;
                break;
        }

        return $result;
    }

}