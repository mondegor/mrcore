<?php declare(strict_types=1);
namespace mrcore\http;
use mrcore\base\Environment;

/**
 * Обёртка позволяющая обращаться к данным поступивших от клиента на сервер.
 *
 * @author  Andrey J. Nazarov
 * @uses       getenv('HTTP_HOST')
 * @uses       getenv('HTTP_REFERER')
 * @uses       getenv('HTTP_USER_AGENT')
 * @uses       getenv('HTTPS')
 * @uses       getenv('REMOTE_ADDR')
 * @uses       getenv('REMOTE_PORT')
 * @uses       getenv('REQUEST_METHOD')
 * @uses       getenv('REQUEST_SCHEME')
 * @uses       getenv('REQUEST_URI')
 * @uses       getenv('HTTP_CLIENT_IP')
 * @uses       getenv('HTTP_X_CLUSTER_CLIENT_IP')
 * @uses       getenv('HTTP_X_FORWARDED_FOR')
 */
class ClientEnvironment extends Environment
{
    /**
     * Кэш метода {@see WebEnvService::_getUserIp()}
     */
    private ?array $userIpCache = null;

    #################################### Methods #####################################

    /**
     * Возвращается ip адреса реального/клиента/прокси, который отправил запрос на сервер.
     * :WARNING: Относительно доверять можно только 'ip_real', все остальные ip используются для справки.
     * В 'string' - содержитcя IP или набор IP адресов в текстовом виде, в остальных полях IP преобразован в int.
     *
     * @param      bool $excludeReserved {@see WebEnvService::_getUserIp()}
     * @return     array {@see WebEnvService::_getUserIp()}
     */
    public function getRemoteIp(bool $excludeReserved = true): array
    {
        if (null === $this->userIpCache)
        {
            $this->userIpCache = array
            (
                'ip_real'   => 0,
                'ip_client' => 0,
                'ip_proxy'  => 0,
                'string'    => '',
            );

            $this->_getUserIp($this->userIpCache, $excludeReserved);
        }

        return $this->userIpCache;
    }

//    /**
//     * Возвращается порт, через который был отправлен запрос на сервер.
//     */
//    public function getRemotePort(): int
//    {
//        return (int)$this->get('REMOTE_PORT');
//    }

    /**
     * Возвращается метод по которому произошел запрос от клиента.
     * [GET, POST, PUT, DELETE, ...]
     */
    public function getRequestMethod(): string
    {
        return $this->get('REQUEST_METHOD');
    }

    /**
     * Возвращается SCHEME по которой произошел запрос от клиента.
     * [HTTP, HTTPS, ...]
     */
    public function getRequestScheme(): string
    {
        return $this->get('REQUEST_SCHEME');
    }

    /**
     * Проверяется, происходит ли запрос от клиента через HTTPS протокол.
     */
    public function isHttps(): bool
    {
        return ('' !== $this->get('HTTPS'));
    }

    /**
     * Название хоста (домена) к которому пришёл запрос от клиента.
     */
    public function getHostName(int $level = null, bool $removeWww = false): string
    {
        $host = $this->get('HTTP_HOST');

        if (null !== $level)
        {
            assert($level > 0);

            for ($i = strlen($host) - 2; $i >= 0; $i--)
            {
                if (('.' === $host[$i]) && ($level-- < 2))
                {
                    return substr($host, $i + 1);
                }
            }
        }

        if ($removeWww && 0 === strncmp($host, 'www.', 4))
        {
            $host = substr($host, 4);
        }

        return $host;
    }

    /**
     * {@link https://wiki.mozilla.org/Security/Origin}
     *
     * Пример: http://mrcore.localhost
     */
    public function getOrigin(): string
    {
        return rtrim($this->get('HTTP_ORIGIN'), '/');
    }

    /**
     * Возвращается URL которому пришел запрос от клиента.
     */
    public function getRequestUrl(bool $removeWww = false): string
    {
        if ('' !== ($host = $this->getHostName(null, $removeWww)))
        {
            if ('' === ($scheme = $this->getRequestScheme()))
            {
                $scheme = 'http';
            }

            return $scheme . '://' . $host . $this->get('REQUEST_URI');
        }

        return '';
    }

    /**
     * Возвращается информация об агенте клиента, который отправил запрос на сервер.
     * :WARNING: Эти данные ненадёжные, их стоит дополнительно проверять.
     */
    public function getUserAgent(): string
    {
        return $this->get('HTTP_USER_AGENT');
    }

    /**
     * Возвращается URL с которого был переход на текущий запрос.
     * :WARNING: Эти данные ненадёжные, их стоит дополнительно проверять.
     */
    public function getReferrerUrl(): string
    {
        return $this->get('HTTP_REFERER');
    }

    ##################################################################################

    /**
     * @see WebEnvService::getRemoteIp()
     *
     * @param  array $data ['ip_real' => int,
     *                      'ip_client' => int,
     *                      'ip_proxy' => int,
     *                      'string' => string]
     * @param  bool $excludeReserved // исключать местные IP, например, 192.168.0.1
     */
    protected function _getUserIp(array &$data, bool $excludeReserved): void
    {
        // в данном поле содержится ip компьютера, с которого был последний запрос,
        // т.е. это или реальный ip клиента или ip прокси сервера
        $realIp = $this->get('REMOTE_ADDR');

        if (false !== ($ipi = ip2long($realIp)))
        {
            $data['ip_real'] = $ipi; // since PHP 7.0
            $data['ip_client'] = $ipi; // since PHP 7.0
        }

        // в данных заголовках может находиться реальный ip клиента переданный прокси сервером,
        // :WARNING: но такому адресу доверять нельзя, это только для справочной информации
        foreach (['HTTP_CLIENT_IP', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_X_FORWARDED_FOR'] as $key)
        {
            if ('' === ($ip = $this->get($key)))
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
                        $data['ip_client'] = $ipi; // since PHP 7.0
                        $data['ip_proxy'] = $data['ip_real'];
                    }

                    $foundIps[$testIp] = true;
                }
            }

            if (!empty($foundIps))
            {
                $data['string'] .= substr($key, 5) . ': ' . implode(', ', array_keys($foundIps)) . '; ';
            }
        }

        $data['string'] = ('' === $data['string'] ? $realIp : 'IP: ' . $realIp . '; ' . substr($data['string'], 0, -2));
    }

    function getClientLanguage(): string
    {
//Accept-Language
//	en-US,en;q=0.5
//    WebLangService
//    User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:102.0) Gecko/20100101 Firefox/102.0
    }

}