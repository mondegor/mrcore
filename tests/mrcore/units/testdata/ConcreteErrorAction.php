<?php declare(strict_types=1);
namespace mrcore\units\testdata;
use mrcore\units\ErrorAction;

require_once 'mrcore/units/ErrorAction.php';

class ConcreteErrorAction extends ErrorAction
{

    public function testInit(): void
    {
        $this->_init();
    }

}