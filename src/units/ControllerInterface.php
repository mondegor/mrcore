<?php declare(strict_types=1);
namespace mrcms\units;
use mrcore\http\ResponseInterface;
use mrcore\units\AbstractAction;
use mrcore\view\ViewDataBagInterface;

/**
 * Интерфейс контроллера, задача которого обработать запрос и вернуть на него ответ.
 *
 * @author  Andrey J. Nazarov
 *
 * @template  T_Action=AbstractAction
 * @template  T_ViewDataBag=ViewDataBagInterface
 * @template  T_Response=ResponseInterface
 * @template  T_PROPERTIES
 */
interface ControllerInterface
{
    /**
     * Запуск контроллера.
     */
    public function run(): ResponseInterface;

    /**
     * Создание экшена по указанному источнику.
     *
     * @param  class-string<T_Action>  $class
     * @param  array|null  $context {@see AbstractAction::$context}
     * @return T_Action
     */
    public function createAction(string $class, array $context = null): AbstractAction;

    /**
     * @param  class-string<T_ViewDataBag>|null  $class
     * @return T_ViewDataBag
     */
    public function createModel(string $class = null): ViewDataBagInterface;

    /**
     * Возвращается объект для формирования ответа экшена.
     *
     * @param  class-string<T_Response>|null  $responseClass
     * @param  T_PROPERTIES|null  $extra
     * @return T_Response
     */
    public function createResponse(string $data,
                                   int $statusCode = null,
                                   array $headers = null,
                                   string $responseClass = null,
                                   array $extra = null): ResponseInterface;

    /**
     * Возвращаются данные преобразованные шаблонизатором в строку.
     *
     * @param  class-string<T_Response>|null  $viewEngineClass
     * @param  T_PROPERTIES|null  $extra
     */
    public function renderView(string $templateName,
                               array $variables,
                               string $viewEngineClass = null,
                               array $extra = null): string;

}