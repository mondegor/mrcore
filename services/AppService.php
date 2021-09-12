<?php declare(strict_types=1);
namespace mrcore\services;
use mrcore\units\AbstractAction;

require_once 'mrcore/services/InterfaceInjectableService.php';

/**
 * Класс MrApp является фронт контроллером, он первым обрабатывает внешние запросы,
 * определяет к какому контроллеру был адресован запрос, далее запускает его,
 * также подключает необходимые шаблоны и отправляет в виде ответа сформированную страницу.
 *
 * @author     Andrey J. Nazarov <mondegor@gmail.com>
 * @package    mrcore/services
 */
class AppService implements InterfaceInjectableService
{
    /**
     * Текущий экшен, которому было передано управление.
     * // :TODO: свойство сделать private (в PageView передавать ссылку)
     *
     * @var    AbstractAction
     */
    public ?AbstractAction $curAction = null;

    /**
     * Глобальные параметры текущей страницы (документа).
     * - section           // текущая секция используемая при отображении страницы;
     * - sectionRoot       // url к текущей секции сайта (без домена);
     * - hostRoot          // текущий путь к сайту (http(s):://домен);
     * - path              // текущий путь к странице (без экшенов по умолчанию)
     * - themePath         // относительный путь к текущей теме оформления сайта;
     * - // resourcesPath     // относительный путь к ресурсам текущей секции;
     * - decorTemplatePath // относительный путь к текущему обрамляющему шаблону;
     * - templatePath      // относительный путь к текущему шаблону страницы;
     * - sectionPages      // древовидная структура страниц текущей секции;
     * - isIndexed         // флаг индексации текущей страницы;
     * - breadcrumbs       // массив дополнительных хлебных крошек, которы сливается с системным;
     *
     * @var    array
     */
    public array $environment = [];

    /**
     * Названия системных экшенов,
     * которые вызываются системой по мере необходимости.
     *
     * @var    array
     */
    private array $_systemActions = array
    (
        'error' => '\mrcore\units\ErrorAction', // системный экшен отображения ошибок с кодами 4xx;
        'redirect' => '\mrcore\units\RedirectAction', // системный экшен редирека по определённым правилам;
        'router' => '\mrcore\units\RouterAction', // системный экшен перенаправления запросов - роутер;
    );
 

    ///**
    // * Внешний обработчик вызываемый после инициализации шаблонизатора.
    // * В этом обработчике формируются дополнительные блоки (виджеты),
    // * которые в виде html блоков передаются в шаблонизатор.
    // *
    // * @param      InterfaceTemplater  $templater
    // */
    //abstract public function render(InterfaceTemplater $templater): void;



}