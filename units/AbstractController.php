<?php declare(strict_types=1);
namespace mrcore\units;

use http\Exception\RuntimeException;
use mrcore\services\ResponseService;
use mysql_xdevapi\Exception;

require_once 'mrcore/units/AbstractController.php';

/**
 * Базовой контроллер, который может передавать управление своим экшенам-методам.
 *
 * @author     Andrey J. Nazarov <mondegor@gmail.com>
 * @package    mrcore/units
 */
abstract class AbstractController extends AbstractAction
{
    /**
     * Название метода для роутинга экшенов.
     *
     * @conts  string
     */
    public const METHOD_ROUTER = 'router';

    ################################### Properties ###################################

    /**
     * @inheritdoc
     */
    /*__override__*/ protected string $_actionType = self::TYPE_CONTROLLER;

    /**
     * Описание экшенов контроллера.
     *
     * @var    array [string => [string => mixed, ...], ...]
     */
    protected array $_actions = [];

    /**
     * Успешно разобранный путь к контроллеру (относительно домена и секции сайта);
     *
     * @var    string
     */
    private string $_controllerPath;

    /**
     * Название текущего экшена, которому передаёт управление контроллер.
     *
     * @var    string
     */
    private ?string $_actionName = null;

    #################################### Methods #####################################

    /**
     * @inheritdoc
     */
    /*__override__*/ public function __construct($unitName, $parsedPath, array $residuePath, array $context)
    {
        parent::__construct($unitName, $parsedPath, $residuePath, $context);

        // если имеется остаток от разобранного пути,
        // то первым элементом считается название экшена
        if (isset($this->residuePath[0]))
        {
            // если экшен зарегистрирован у контроллера, то управление передаётся ему
            if (isset($this->_actions[$this->residuePath[0]]))
            {
                $this->_actionName = array_shift($this->residuePath);
            }

            // если в пути ещё имеется необработанный параметр (это не имя экшена, иначе сработало бы предыдущее условие)
            // и если в контроллере зарегистрирован экшен-роутер, то управление передаётся ему
            if (isset($this->residuePath[0], $this->_actions[self::METHOD_ROUTER]))
            {
                $this->_actions[self::METHOD_ROUTER]['actionName'] = $this->_actionName;
                $this->_actionName = self::METHOD_ROUTER;
            }
        }
        else
        {
            // иначе выбирается экшен по умолчанию (из $context['defaultAction'] или первый в списке)
            // reset($this->_actions);
            $this->_actionName = $context['defaultAction'] ?? array_key_first($this->_actions);
        }

        ##################################################################################

        // если экшен для контроллера не найден, то остаётся отобразить 404
        if (null === $this->_actionName)
        {
            /* @var $response ResponseService */
            $this->injectService('global.response')->setAnswer(404);
        }
    }

    /**
     * Возвращается тип текущего экшена или базовый тип.
     *
     * @inheritdoc
     */
    /*__override__*/ public function getActionType(): string
    {
        if (isset($this->_actions[$this->_actionName]['actionType']))
        {
            return $this->_actions[$this->_actionName]['actionType'];
        }

        return parent::getActionType();
    }

    /**
     * Контроллер определяет экшен, который
     * нужно вызывать и передаёт ему управление.
     *
     * @return     int  (RESULT_SUCCESS, RESULT_FORBIDDEN, RESULT_NOT_FOUND)
     */
    public function run(): int
    {
        $this->_controllerPath = $this->rewriteFullPath;

        return $this->_callAction($this->_actionName);
    }

    /**
     * Контроллер передаёт управление своему экшену.
     *
     * @param      string  $actionName
     * @return     int  (RESULT_SUCCESS, RESULT_FORBIDDEN, RESULT_NOT_FOUND)
     */
    final protected function _callAction(string $actionName): int
    {
        if (!isset($this->_actions[$actionName]))
        {
            return self::RESULT_NOT_FOUND;
        }

        // $this->rewriteFullPath .= $actionName;
        // 0 === strncmp($actionName, '#', 1)
        // $this->rewritePath = $this->rewriteFullPath;

        if ($this->_actionName !== $actionName)
        {
            //// для экшена по умолчанию достаточно пути к самому контроллеру
            //if ($actionName === array_key_first($this->_actions))
            //{
            //    $this->rewritePath = $this->_controllerPath;
            //}

            // запоминается последний запущенный экшен
            $this->_actionName = $actionName;
        }

        ##################################################################################

        $context = $this->_actions[$actionName];
        $key = 'action-' . $actionName;

        // если был передан контекст экшену снаружи, то этот контекст
        // дополняет/заменяет контекст экшена пределённого в контроллере
        if (isset($this->_context[$key]))
        {
            $context = array_replace($context, $this->_context[$key]);
        }

        // копирование некоторых полей из контекста контроллера в контекст экшена
        foreach (['name', 'head'] as $key)
        {
            if (!isset($context[$key]) && isset($this->_context[$key]))
            {
                $context[$key] = $this->_context[$key];
            }
        }

        // для того, чтобы системные экшены могли запускаться, применяется ltrim - #
        $actionMethodName = ltrim($actionName, '#') . 'Action';

        return $this->$actionMethodName($context);
    }

}