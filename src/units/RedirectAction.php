<?php declare(strict_types=1);
namespace mrcore\units;
use mrcore\base\BuilderLink;
use mrcore\services\ResponseService;

/**
 * Системный экшен редиректа на указанный URL.
 *
 * @author  Andrey J. Nazarov
 */
class RedirectAction extends AbstractAction
{
    /**
     * Если указан URL для редиректа, то он обрабатывается и устанавливается редирект.
     *
     * @inheritdoc
     */
    public function run(): mixed
    {
        if (empty($this->context['redirect']))
        {
            return self::RESULT_NOT_FOUND;
        }

        // очистка переданного экшену остатка пути,
        // т.к. этот экшен редиректа в любом случае должен сработать
        $this->section->pathToAction->residuePath = [];

        // :TODO: переписать MrLink

        $link = BuilderLink::factory($this->context['redirect']);

        if (!empty($this->context['referrer']))
        {
            $link->set('ref', $this->section->serviceBag->getEnv()->getRequestUrl());
        }

        /* @var $response ResponseService */
        $response = $this->section->serviceBag->getService('sys.response');
        $response->setAnswer(302, $link);

        return self::RESULT_PROCESSED;
    }

}