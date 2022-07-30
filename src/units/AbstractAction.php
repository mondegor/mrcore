<?php declare(strict_types=1);
namespace mrcore\units;
use mrcms\units\ControllerInterface;
use mrcms\view\ViewModelInterface;
use mrcore\web\ServiceBag;
use mrcore\exceptions\HttpException;
use mrcore\http\ResponseInterface;

/**
 * Абстракция обработчика внешнего запроса.
 *
 * @author  Andrey J. Nazarov
 *
 * @template  T_ACTION_CONTEXT=array<string, mixed>
 * @template  T_Response=ResponseInterface
 * @template  T_ViewEngine=AbstractViewEngine
 * @template  T_ViewModel=ViewModelInterface
 */
abstract class AbstractAction
{
    /**
     * Настройки экшена, которые могут быть переопределены из вне.
     * Внешний контекст приоритетнее внутреннего.
     *
     * @var  T_ACTION_CONTEXT
     */
    protected array $context = [];

    #################################### Methods #####################################

    /**
     * @param  T_ACTION_CONTEXT  $context
     */
    public function __construct(private ControllerInterface $controller,
                                protected ServiceBag $serviceBag,
                                array $context = null)
    {
        if (null === $context)
        {
            $this->context = array_replace($this->context, $context);
        }

        $this->_init();
    }

//    /**
//     * Возвращается контекст (внешние настройки) экшена.
//     *
//     * @return  array {@see AbstractAction::$context}
//     */
//    public function setContext(array $context): array
//    {
//        return $this->context;
//    }

    /**
     * Проверка присутствия указанной переменной в контексте экшена.
     * WARNING: переменные с null значениями считаются отсутствующими.
     */
    public function hasContext(string $name): bool
    {
        return isset($this->context[$name]);
    }

    /**
     * Возвращается текущий контекст (настройки) экшена.
     *
     * @return  string|int|float|bool|array|null {@see AbstractAction::$context}
     */
    public function getContext(string $name = null): string|int|float|bool|array|null
    {
        if (null === $name)
        {
            return $this->context;
        }

        return $this->context[$name] ?? null;
    }

    /**
     * Обработчик запроса.
     * В результатом могут быть следующие типы:
     *   ResponseInterface - объект ответа сервера, процесс обработки останавливается (даже в случае с middleware экшеном) и
     *                       отправляется ответ клиенту;
     *   ViewModelInterface - объект модели представления, если его вернул узловой экшен (последний в цепочке) тогда контроллер
     *                        сам создаст ответ сервера по умолчанию и далее работает как с ResponseInterface;
     *                        или если его вернул middleware экшен, то будет Fatal Error;
     *   AbstractAction - объект экшена (это не элемент цепочки), он будет точно также обработан, как текущий экшен,
     *                    который должен вернуть в качестве результата один из этих типов;
     *   T_PROPERTIES - массив, если его вернул middleware экшен, то будет передано управление следующему экшену в цепочке,
     *                  то результат будет смержен с контекстом следующего экшена в цепочке и будет передано ему управление;
     *                  (:WARNING: самый последний экшен получит все смержанные результаты в цепочке);
     *                  или если его вернул узловой экшен, тогда это работает как с ViewModelInterface;
     *   string - если его вернул middleware экшен, то будет Fatal Error;
     *            или если его вернул узловой экшен, то это работает как с ViewModelInterface;
     *   null - если его вернул middleware экшен, то будет передано управление следующему экшену в цепочке,
     *          или если его вернул узловой экшен, то будет Fatal Error;
     *
     * @return  ResponseInterface|ViewModelInterface|AbstractAction|T_PROPERTIES|string|null
     */
    abstract public function run(): mixed;

    /**
     * Возвращается модель представления используемая при формировании ответа экшена.
     *
     * @param  class-string<T_ViewModel> $viewModelClass
     * @return T_ViewModel
     */
    protected function createModel(string $viewModelClass): ViewModelInterface
    {
        return $this->controller->createModel($viewModelClass);
    }

    /**
     * Возвращается объект ответа с указанием класса для формирования ответа экшена.
     *
     * @param  string|T_PROPERTIES|ViewModelInterface $data
     */
    protected function createResponseOf(string $responseClass,
                                        string|array|ViewModelInterface $data,
                                        int $statusCode = null,
                                        array $headers = null)
    {
        return $this->controller->createResponse
        (
            $this->_prepareContent($data),
            $statusCode,
            $headers,
            $responseClass,
            $this->context
        );
    }

    /**
     * Возвращается объект ответа по умолчанию для формирования ответа экшена.
     *
     * @param  string|T_PROPERTIES|ViewModelInterface $data
     */
    protected function createResponse(string|array|ViewModelInterface $data,
                                      int $statusCode = null,
                                      array $headers = null): ResponseInterface
    {
        return $this->controller->createResponse
        (
            $this->_prepareContent($data),
            $statusCode,
            $headers,
            $this->context['responseClass'] ?? null,
            $this->context
        );
    }

    /**
     * Возвращается объект ответа по умолчанию для формирования ответа экшена.
     *
     * @param  string|T_PROPERTIES|ViewModelInterface $data
     */
    protected function renderResponse(array|ViewModelInterface $data,
                                      int $statusCode = null,
                                      array $headers = null): ResponseInterface
    {
        return $this->createResponse
        (
            $this->controller->renderView
            (
                $this->_prepareContent($data),
                $this->context['templateName'] ?? null,
                $this->context['viewEngineClass'] ?? null,
                $this->context
            ),
            $statusCode,
            $headers
        );
    }

    /**
     * Возвращается объект для формирования ответа экшена.
     *
     * @param  class-string<T_ViewEngine> $viewEngineClass
     * @return T_ViewEngine
     */
    protected function renderViewOfEngine(string $viewEngineClass, string $templateName, array $variables = null): string
    {

        return $this->controller->renderView
        (
            $variables,
            $templateName,
            $viewEngineClass,
            $this->context
        );
    }

    protected function renderView(string $templateName, array $variables = null): string
    {
        return $this->controller->renderView
        (
            $variables,
            $templateName,
            $this->context['viewEngineClass'] ?? null,
            $this->context
        );
    }

    protected function createNotFoundException(string $message = null): HttpException
    {
        return HttpException::isNotFound($message);
    }

    ##################################################################################

    /**
     * Инициализация объекта экшена, вызывается во время работы конструктора.
     * В массиве $this->context содержатся данные для экшена:
     * как внутренние настройки, так и переданные из вне.
     *
     * :WARNING: Данный метод должен быть абстрактным,
     *           но т.к. в дочерних классах он редко используется,
     *           то он объявлен реальным, но пустым.
     */
    protected function _init(): void { }

    protected function _prepareContent(string|array|ViewModelInterface $data): string|array
    {
        if ($data instanceof ViewModelInterface)
        {
            $data = $data->getArray();
        }

        return $data;
    }

}