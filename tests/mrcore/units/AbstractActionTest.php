<?php declare(strict_types=1);
namespace mrcore\units;
use PHPUnit\Framework\TestCase;
use mrcore\testing\Helper;
use mrcore\units\testdata\ConcreteAction;

require_once 'mrcore/units/AbstractAction.php';

class AbstractActionTest extends TestCase
{

    public function testConstructor(): void
    {
        $parsedPath = 'path/page/';
        $residuePath = ['rest1', 'rest2'];
        $context = ['actionType' => AbstractAction::TYPE_TEXTPAGE, 'context-name' => 1, 'context-name2' => 'value2'];

        $action = new ConcreteAction('ActionName1', ['path' => $parsedPath, 'residue' => $residuePath], $context);

        $this->assertSame($parsedPath, $action->rewriteFullPath);
        $this->assertSame($residuePath, $action->residuePath);
        $this->assertSame(AbstractAction::TYPE_TEXTPAGE, Helper::getProperty($action, '_actionType'));
        $this->assertEquals($context, Helper::getProperty($action, '_context'));
        $this->assertTrue($action->isInited);
    }

    ##################################################################################

    public function testGetActionType(): void
    {
        $action = $this->createPartialMock(ConcreteAction::class, []);

        Helper::setProperty($action, '_actionType', AbstractAction::TYPE_TEXTPAGE);
        $this->assertSame(AbstractAction::TYPE_TEXTPAGE, $action->getActionType());
    }

    ##################################################################################

    public function testProtectedGetSubscribedServices(): void
    {
        $expected = array
        (
            'global.app' => true,
            'global.env' => true,
            'global.response' => true,
            'global.var' => true,
        );

        $action = $this->createPartialMock(ConcreteAction::class, []);
        $this->assertSame($expected, $action->testGetSubscribedServices());
    }

}