<?php declare(strict_types=1);
namespace mrcore\units;
use PHPUnit\Framework\TestCase;

require_once 'mrcore/units/RouterAction.php';

class RouterActionTest extends TestCase
{

    public function testRun(): void
    {
        $action = $this->createPartialMock(RouterAction::class, []);

        $this->assertSame(AbstractAction::RESULT_NOT_FOUND, $action->run());
    }

}