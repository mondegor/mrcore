<?php declare(strict_types=1);
namespace mrcore\units;
use mrcore\services\AppService;
use mrcore\services\EnvService;
use mrcore\services\ResponseService;

require_once 'mrcore/units/AbstractAction.php';

/**
 * Системный экшен отображения ошибок с кодами 4xx.
 * Вызывается системой, например, тогда, когда по запросу или
 * не удалось найти нужный экшен или доступ к экшену был отклонён.
 *
 * @author     Andrey J. Nazarov <mondegor@gmail.com>
 * @package    mrcore/units
 */
class ErrorAction extends AbstractAction
{
    /**
     * @inheritdoc
     */
    /*__override__*/ protected string $_actionType = self::TYPE_ERRORPAGE;

    /**
     * Код ошибки, который поступил от предыдушего экшена.
     *
     * @var    int
     */
    protected int $_codeAnswer;

    #################################### Methods #####################################

    /**
     * @inheritdoc
     */
    /*__override__*/ protected function _init(): void
    {
        /* @var $response ResponseService */ $response = &$this->injectService('global.response');

        // считывается текущий код ответа установленный предыдущим экшеном
        $this->_codeAnswer = $response->getAnswer();

        // если код ответа не является ошибкой, то устанавливается 404
        if ($this->_codeAnswer < 400)
        {
            $this->_codeAnswer = 404;
        }
        // если код ответа является ошибкой,
        // то системой временно устанавливается код ответа 200
        // (иначе система не запустит данный экшен)
        else
        {
            $response->setAnswer(200);
        }
    }

    /**
     * Страница вывода ошибок в случае некорректных
     * или запрещённых запросов к системе.
     *
     * @inheritdoc
     */
    /*__override__*/ public function run(): int
    {
        /* @var $app AppService */ $app = &$this->injectService('global.app');
        /* @var $env EnvService */ $env = &$this->injectService('global.env');
        /* @var $response ResponseService */ $response = &$this->injectService('global.response');

        // очистка переданного экшену остатка пути,
        // т.к. этот экшен вывода ошибок в любом случае должен отобразиться
        $this->residuePath = [];

        $app->environment['section']['isCache'] = false; // кэширование ошибок доступа к странице запрещается
        $response->setAnswer($this->_codeAnswer); // устанавливается код ошибки переданного предыдущим экшеном

        // :WARNING: если contentType не будет содержать "/" или если decorTemplatePath не будет содержать ".",
        //           то будет Fatal Error, но если такое случится, то это баг, который нужно немедленно исправить
        $shortContentType = substr(strrchr($app->environment['section']['contentType'], '/'), 1);
        $ext = empty($app->environment['decorTemplatePath']) ? 'php' : substr(strrchr($app->environment['decorTemplatePath'], '.'), 1);

        // :WARNING: переопределяется обрамляющий шаблон
        $app->environment['decorTemplatePath'] = 'system/error.' . $shortContentType . '.tpl.' . $ext;

        // формируются данные для предачи их в шаблон
        $this->viewData = array
        (
            // копирование url на который пытался пользователь зайти
            'codeAnswer'       => $this->_codeAnswer,
            'charset'          => $app->environment['section']['charset'],
            'contentType'      => $app->environment['section']['contentType'],
            'shortContentType' => $shortContentType,
            'requestedUri'     => $env->get('REQUEST_URI'),
        );

        return self::RESULT_SUCCESS;
    }

}