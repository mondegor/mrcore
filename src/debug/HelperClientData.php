<?php declare(strict_types=1);
namespace mrcore\debug;
use mrcore\http\ClientEnvironment;
use mrcore\http\ClientRequest;

/**
 * Формирование дополнительной информации о данных клиента, которые поступили на сервер.
 *
 * @author  Andrey J. Nazarov
 */
class HelperClientData
{
    /**
     * @param  string[]|null // список слов, которые нужно скрывать при записи в логи
     */
    public function __construct(private ?array $wordsToHide = null,
                                private ?ClientEnvironment $clientEnvironment = null,
                                private ?ClientRequest $clientRequest = null)
    {
        if (null === $wordsToHide)
        {
            $this->wordsToHide = ['pass', 'pw'];
        }
    }

    /**
     * @see HelperClientData::__construct()
     */
    public function getWords(): array
    {
        return $this->wordsToHide;
    }

    /**
     * Возвращается дополнительная информация о данных клиента, которые поступили на сервер.
     */
    public function getInfo(): string
    {
        $debuggingInfo = '';

        if (null !== $this->clientEnvironment)
        {
            $ips = $this->clientEnvironment->getRemoteIp();

            $debuggingInfo .= 'Client: ' . $ips['string'] . '; URL: ' . $this->clientEnvironment->getRequestUrl() . PHP_EOL .
                              'User Agent: ' . $this->clientEnvironment->getUserAgent() . PHP_EOL .
                              'Referrer URL: ' . $this->clientEnvironment->getReferrerUrl() . PHP_EOL;
        }

        if (null !== $this->clientRequest)
        {
            $request = $this->clientRequest->getRaw();

            if (!empty($request))
            {
                $debuggingInfo .= ('$_REQUEST = ' . rtrim(var_export(Tools::getHiddenData($request, $this->wordsToHide), true), ')') . ' );' . PHP_EOL);
            }
        }

        return $debuggingInfo;
    }

    ##################################################################################

    /**
     * Возвращается название хоста, от которого пришел запрос.
     */
    public function getHostname(): string
    {
        if (null !== $this->clientEnvironment)
        {
            return $this->clientEnvironment->getHostName(null, true);
        }

        return '';
    }

}