<?php declare(strict_types=1);

/**
 * Класс описывает сущность "Доступ к внешнему окружению"
 * :WARNING: все перменные с префиксом HTTP_ являются ненадёжними,
 *           и используются только в ознакомительных целях
 *
 * @author     Andrey J. Nazarov <mondegor@gmail.com>
 * @package    mrcore
 * @uses       getenv('REMOTE_ADDR')
 * @uses       getenv('HTTP_USER_AGENT')
 * @uses       getenv('HTTP_HOST')
 * @uses       getenv('REQUEST_URI')
 * @uses       getenv('HTTP_REFERER')
 * @uses       getenv('HTTP_CLIENT_IP')
 * @uses       getenv('HTTP_X_CLUSTER_CLIENT_IP')
 * @uses       getenv('HTTP_X_FORWARDED_FOR')
 * @uses       getenv('HTTP_X_REQUESTED_WITH')
 * @uses       $_ENV['MRCORE_UNITTEST'] OPTIONAL
 */
/*__class_static__*/ final class MrEnv
{
    /**
     * Скрипт запущен из консоли (command line)?
     *
     * @return     bool
     */
    public static function isCli(): bool
    {
        return defined('STDIN'); // 'cli' === PHP_SAPI
    }

    /**
     * Получение значение из переменной переменного окружения.
     *
     * @param string $name
     * @return string
     */
    public static function get(string $name): string
    {
        return (string)(getenv($name, true) ?: getenv($name));
    }

    /**
     * Получение ip адреса реального/клиента/прокси, который отправил запрос на сервер.
     * Относительно доверять можно только $result['ip_real'], все остальные ip используются для справки.
     * В 'string' - содержитcя IP или набор IP адресов в текстовом виде, в остальных полях IP преобразован в int.
     *
     * @param      bool $excludeReserved
     * @return     array ['ip_real' => int, 'ip_client' => int, 'ip_proxy' => int, 'string' => string]
     */
    public static function getUserIP(bool $excludeReserved = true): array
    {
        static $result = null;

        if (empty($_ENV['MRCORE_UNITTEST']) && null !== $result)
        {
            return $result;
        }

        ##################################################################################

        $result = array
        (
            'ip_real'   => 0,
            'ip_client' => 0,
            'ip_proxy'  => 0,
            'string'    => '',
        );

        // в данном поле содержится ip компьютера, с которого был последний запрос,
        // т.е. это или реальный ip клиента или ip прокси сервера
        $realIp = self::get('REMOTE_ADDR');

        if (false !== ($ipi = ip2long($realIp)))
        {
            $result['ip_real'] = $ipi; // since PHP 7.0
            $result['ip_client'] = $ipi; // since PHP 7.0
        }

        // в данных заголовках может находиться реальный ip клиента переданный прокси сервером,
        // :WARNING: но такому адресу доверять нельзя, это только для справочной информации
        foreach (['HTTP_CLIENT_IP', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_X_FORWARDED_FOR'] as $key)
        {
            if ('' === ($ip = self::get($key)))
            {
                continue;
            }

            // выборка всех элементов похожих на IP
            preg_match_all('/\d+\.\d+\.\d+\.\d+/', $ip, $m);

            $foundIps = [];

            foreach ($m[0] as $i => $testIp)
            {
                // попытка выборки самого первого валидного ip адреса,
                // встречаемого в строке не совпадающим с REMOTE_ADDR
                if ($realIp !== $testIp &&
                    !isset($foundIps[$testIp]) &&
                    false !== ($ipi = filter_var($testIp, FILTER_VALIDATE_IP, $excludeReserved ? FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE : null)) &&
                    false !== ($ipi = ip2long($ipi)))
                {
                    if (empty($foundIps))
                    {
                        $result['ip_client'] = $ipi; // since PHP 7.0
                        $result['ip_proxy'] = $result['ip_real'];
                    }

                    $foundIps[$testIp] = true;
                }
            }

            if (!empty($foundIps))
            {
                $result['string'] .= substr($key, 5) . ': ' . implode(', ', array_keys($foundIps)) . '; ';
            }
        }

        $result['string'] = ('' === $result['string'] ? $realIp : 'IP: ' . $realIp . '; ' . substr($result['string'], 0, -2));

        return $result;
    }

    /**
     * Получение информации об агенте пользователя,
     * который отправил запрос на получение документа.
     *
     * @return     string
     */
    public static function getUserAgent(): string
    {
        return self::get('HTTP_USER_AGENT');
    }

    /**
     * Получение URL с которого пришел запрос на сервер.
     *
     * @return     string
     */
    public static function getRequestUrl(): string
    {
        if ('' !== ($host = self::get('HTTP_HOST')))
        {
            return 'https://' . $host . self::get('REQUEST_URI');
        }

        return '';
    }

    /**
     * Получение URL с которого был переход на текущий запрос.
     *
     * @return     string
     */
    public static function getRefererUrl(): string
    {
        return self::get('HTTP_REFERER');
    }

//     /**
//      * Получение информации о браузере пользователя,
//      * который отправил запрос на получение документа.
//      *
//      * @return     array ['type' => string, 'version' => string]
//      */
//     public static function getBrowserInfo(): array
//     {
//         $result = array
//         (
//             'type'    => '',
//             'version' => '0.0',
//         );

//         ##################################################################################

//         if (preg_match('#([a-z]+)/([0-9.]+) \((.*?)\)(.*)#i', self::getUserAgent(), $m1) > 0)
//         {
//             if ('Mozilla' === $m1[1])
//             {
//                 if (preg_match('/.*?(MSIE|Netscape|Opera).([0-9.]+).*/i', $m1[3], $m2) > 0 ||      // heritrix|Konqueror
//                     preg_match('#.*(Firefox|Netscape|Safari|Gecko)/([0-9.]+)#i', $m1[4], $m2) > 0) // SeaMonkey
//                 {
//                     $result['type'] = $m2[1];
//                     $result['version'] = $m2[2];
//                 }
//             }
//             else if ('Opera' === $m1[1])
//             {
//                 $result['type'] = $m1[1];
//                 $result['version'] = $m1[2];
//             }
//         }

//         return $result;
//     }

    /**
     * Проверяется, является ли это XMLHttpRequest запросом?
     *
     * @return     bool
     */
    public static function isXmlHttpRequest(): bool
    {
        return ('XMLHttpRequest' === self::get('HTTP_X_REQUESTED_WITH'));
    }

//     /**
//      * Проверяется, является ли это Flash запросом?
//      *
//      * @return     bool
//      */
//     public static function isFlashRequest(): bool
//     {
//         return (false !== strpos(strtolower(self::get('HTTP_USER_AGENT')), ' flash'));
//     }

    /**
     * Скрипт запущен из под WINDOWS?
     *
     * @return     bool
     */
 	public static function isWindows(): bool
 	{
 		return ('/' === DIRECTORY_SEPARATOR);
 	}

    /**
     * Проверяется чтобы в указанном url содержалось указанное название домена.
     *
     * @param      string  $url (https://sample.domain/page/)
     * @param      string  $domain (sample.domain)
     * @return     boolean
     */
    public static function checkDomain(string $url, string $domain): bool
    {
        $arrUrl = @parse_url($url); // :WARNING: заглушены ошибки в функции
        return isset($arrUrl['host']) && ($arrUrl['host'] === $domain || $arrUrl['host'] === 'www.' . $domain);
    }

    /**
     * Удаление из URL указанных параметров.
     *
     * @param      string  $url (https://sample.domain/page/?param1=test1)
     * @param      array  $params [string, ...]
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