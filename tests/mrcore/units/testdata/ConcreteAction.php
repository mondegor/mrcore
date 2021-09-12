<?php declare(strict_types=1);
namespace mrcore\units\testdata;
use mrcore\units\AbstractAction;

require_once 'mrcore/units/AbstractAction.php';

class ConcreteAction extends AbstractAction
{
    public bool $isInited = false;

    public function run(): int
    {
        return AbstractAction::RESULT_SUCCESS;
    }

    public function testGetSubscribedServices(): array
    {
        return $this->_getSubscribedServices();
    }

    protected function _init(): void
    {
        $this->isInited = true;
    }

}