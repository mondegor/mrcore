<?php declare(strict_types=1);
namespace mrcore\web;
use mrcore\http\ClientEnvironment;
use mrcore\http\ClientCookie;
use mrcore\http\ClientRequest;
use mrcore\services\AbstractServiceBag;

// :TODO: заменить объекты переданные в качестве параметров на интерфейсы

/**
 * It is container for shared services.
 *
 * @author  Andrey J. Nazarov
 */
class ServiceBag extends AbstractServiceBag
{

    public function __construct(private ClientEnvironment $clientEnvironment,
                                private ClientRequest $clientRequest,
                                private ClientCookie $clientCookie,
                                private PathToAction $pathToAction) { }

    public function getEnv(): ClientEnvironment
    {
        return $this->clientEnvironment;
    }

    public function getRequest(): ClientRequest
    {
        return $this->clientRequest;
    }

    public function getCookie(): ClientCookie
    {
        return $this->clientCookie;
    }

    public function getPath(): PathToAction
    {
        return $this->pathToAction;
    }

}