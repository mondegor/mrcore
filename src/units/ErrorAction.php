<?php declare(strict_types=1);
namespace mrcore\units;
use mrcore\http\ClientRequest;
use mrcore\http\ResponseInterface;

/**
 * Системный экшен отображения ошибок с кодами 4xx.
 * Вызывается системой, например, тогда, когда по запросу или
 * не удалось найти нужный экшен или доступ к экшену был отклонён.
 *
 * @author  Andrey J. Nazarov
 */
class ErrorAction extends AbstractAction
{
    /**
     * Страница вывода ошибок в случае некорректных
     * или запрещённых запросов к системе.
     *
     * @inheritdoc
     */
    public function run(): mixed
    {
        // очистка переданного экшену остатка пути,
        // т.к. этот экшен вывода ошибок в любом случае должен отобразиться
        $this->section->pathToAction->residuePath = [];

        //// если код ошибки "доступ запрещён", но пользователь
        //// не авторизован, то код заменяется на 401
        //if (403 === $code && MrUser::$info['id'] <= MrUser::ID_GUEST)
        //{
        //    $code = 401;
        //}

        // устанавливается код ошибки переданного предыдущим экшеном
        $this->getResponse()
             ->setAnswer($this->context['statusCode'])
             ->setCache(false);

        return $this->createResponse()
    }

}