<?php declare(strict_types=1);
namespace mrcore\lib;

/**
 * Библиотека для работы с URL.
 *
 * @author  Andrey J. Nazarov
 */
/*__class_static__*/ class Url
{
    /**
     * Проверяется чтобы в указанном url содержалось указанное название хоста.
     *
     * @param      string  $url (https://mrcore.localhost/page/)
     * @param      string  $hostName (mrcore.localhost)
     * @return     bool
     */
    public static function checkHost(string $url, string $hostName): bool
    {
        $arrUrl = parse_url($url); // :WARNING: заглушены ошибки в функции

        return isset($arrUrl['host']) && ($arrUrl['host'] === $hostName || $arrUrl['host'] === 'www.' . $hostName);
    }

    /**
     * Удаление из URL указанных параметров.
     *
     * @param      string  $url (https://mrcore.localhost/page/?param1=test1)
     * @param      string[]  $params
     * @return     string
     */
    public static function removeParams(string $url, array $params): string
    {
        if (empty($params))
        {
            return $url;
        }

        ##################################################################################

        $arrUrl = @parse_url($url); // :WARNING: заглушены ошибки в функции

        if (empty($arrUrl['query']))
        {
            return $url;
        }

        ##################################################################################

        // удаление параметров из query
        $args = [];
        parse_str($arrUrl['query'], $args);
        $args = array_diff_key($args, array_flip($params));
        $arrUrl['query'] = http_build_query($args);

        // последовательная обратная сборка урла
        $url = '';

        if (!empty($arrUrl['scheme']))
        {
            $url .= $arrUrl['scheme'] . '://';
        }

        if (!empty($arrUrl['host']))
        {
            $url .= $arrUrl['host'];

            if (!empty($arrUrl['port']))
            {
                $url .= ':' . $arrUrl['port'];
            }
        }

        if (empty($arrUrl['path']))
        {
            $url .= '/';
        }
        else
        {
            $url .= $arrUrl['path'];
        }

        if (!empty($arrUrl['query']))
        {
            $url .= '?' . $arrUrl['query'];
        }

        if (!empty($arrUrl['fragment']))
        {
            $url .= '#' . $arrUrl['fragment'];
        }

        return $url;
    }

}