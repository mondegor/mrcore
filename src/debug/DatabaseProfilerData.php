<?php declare(strict_types=1);
namespace mrcore\debug;
use mrcore\MrScreen;
use mrcore\console\EnumColor;
use mrcore\console\EnumLiteral;
use mrcore\storage\ConnectionManager;

/**
 * Класс предназначен для отображения результата профилирования SQL запросов
 * исполняющихся в процессе работы приложения.
 *
 * @author  Andrey J. Nazarov
 */
class DatabaseProfilerData
{
    /**
     * Возвращается результат профилирования SQL запросов.
     *
     * @param int $queryFilter {@see DatabaseProfiler::TYPES}
     */
    public function getResult(ConnectionManager $conn, int $queryFilter): string
    {
        assert($queryFilter > 0 && $queryFilter <= DatabaseProfiler::ALL);

        if ($queryFilter < 1 || $queryFilter > DatabaseProfiler::ALL)
        {
            return '';
        }

        $_conns = [];

        foreach ($conn->all('db') as $connName => $connDb)
        {
            if (null !== ($profiler = $connDb->getProfiler()))
            {
                $_queries = array
                (
                    'queryProfiles'    => $profiler->getQueryProfiles($queryFilter),
                    'countQueries'     => $profiler->getTotalNumQueries($queryFilter),
                    'totalTime'        => $profiler->getTotalElapsedSecs($queryFilter),
                    'slowestQuery'     => $profiler->getSlowestQuery($queryFilter),
                    'averageQueryTime' => 0.0,
                    'calcPerformance'  => 0.0,
                    'cntShowQueries'   => 0,
                );

                $_queries['averageQueryTime'] = $_queries['countQueries'] > 0 ? ($_queries['totalTime'] / $_queries['countQueries']) : 0;
                $_queries['calcPerformance']  = $_queries['totalTime']    > 0 ? ($_queries['countQueries'] / $_queries['totalTime']) : 0;

                // отображается не больше 128 первых запросов
                $_queries['cntShowQueries'] = min($_queries['countQueries'], 128);

                $_conns[$connName] = $_queries;
            }
        }

        ##################################################################################

        $result = EnumLiteral::LINE_DOUBLE .
                  MrScreen::wrapColor('[SQL PROFILER INFO]', EnumColor::YELLOW_BLACK) . "\n" .
                  EnumLiteral::LINE_DOUBLE;

        if (!empty($_conns))
        {
            foreach ($_conns as $connName => $_queries)
            {
                if (empty($_queries))
                {
                    continue;
                }

                $result .= MrScreen::wrapColor('CONNECTON NAME: ' . $connName, EnumColor::LIGHT_BLUE_BLACK) . "\n";
                $result .= EnumLiteral::LINE_DASH;
                $result .= MrScreen::wrapColor('All sql queries: ' . $_queries['countQueries'], EnumColor::CYAN_BLACK) . "\n";
                $result .= MrScreen::wrapColor('All executed time: ' . number_format($_queries['totalTime'], 5, '.', '') . ' sec', EnumColor::CYAN_BLACK) . "\n";
                $result .= MrScreen::wrapColor('Average time of query: ' . number_format($_queries['averageQueryTime'], 5, '.', '') . ' sec', EnumColor::CYAN_BLACK) . "\n";
                $result .= MrScreen::wrapColor('Calculating performance: ' . number_format($_queries['calcPerformance'], 3, '.', '') . ' queries in sec', EnumColor::CYAN_BLACK) . "\n";
                $result .= EnumLiteral::LINE_DOUBLE;
                $result .= MrScreen::wrapColor('Slowest sql query:', EnumColor::LIGHT_BLUE_BLACK) . "\n";
                $result .= EnumLiteral::LINE_DASH;
                $result .= MrScreen::wrapColor(number_format($_queries['slowestQuery']['time'], 5, '.', ''), EnumColor::GREEN_BLACK) . ' sec (' . MrScreen::wrapColor(sprintf('%06.2f%%', $_queries['slowestQuery']['timep']), EnumColor::LIGHT_RED_BLACK) . ') | ' . MrScreen::wrapColor($_queries['slowestQuery']['text'], EnumColor::RED_BLACK) . "\n";
                $result .= EnumLiteral::LINE_DOUBLE;

                if ($_queries['countQueries'] > 0)
                {
                    $result .= MrScreen::wrapColor('Texts of sql queries:', EnumColor::LIGHT_BLUE_BLACK) . "\n";

                    for ($i = 0; $i < $_queries['cntShowQueries']; $i++)
                    {
                        $_query = $_queries['queryProfiles'][$i];
                        $result .= EnumLiteral::LINE_DASH;
                        $result .= MrScreen::wrapColor(number_format($_query['time'], 5, '.', ''), EnumColor::GREEN_BLACK) . ' sec (' . MrScreen::wrapColor(sprintf('%06.2f%%', $_query['timep']), EnumColor::LIGHT_RED_BLACK) . ') | ' . MrScreen::wrapColor($_query['text'], EnumColor::YELLOW_BLACK) . "\n";
                    }

                    if ($_queries['cntShowQueries'] < $_queries['countQueries'])
                    {
                        $result .= EnumLiteral::LINE_DOUBLE;
                        $result .= MrScreen::wrapColor('!!!!!! :WARNING: !!!!!! Was hidden ' . ($_queries['countQueries'] - $_queries['cntShowQueries'])  . ' SQL queries', EnumColor::YELLOW_RED) . "\n";
                    }
                }

                $result .= EnumLiteral::LINE_DOUBLE;
                $result .= MrScreen::wrapColor('All sql queries: ' . $_queries['countQueries'], EnumColor::LIGHT_GREEN_BLACK) . "\n";
                $result .= MrScreen::wrapColor('All executed time: ' . number_format($_queries['totalTime'], 5, '.', '') . ' sec', EnumColor::LIGHT_GREEN_BLACK) . "\n";
                $result .= EnumLiteral::LINE_DOUBLE;
            }
        }
        else
        {
            $result .= 'List of sql queries is empty' . "\n" .
                       EnumLiteral::LINE_DOUBLE;
        }

        return $result;
    }

}