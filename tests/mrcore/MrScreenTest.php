<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use mrcore\base\EnumColors;
use mrcore\console\AbstractPainter;
use mrcore\exceptions\UnitTestException;

use mrcore\testdata\ConcreteMrScreen;
use mrcore\testdata\ConcreteMrScreenStubProtectedEcho;
use mrcore\testing\Helper;
use mrcore\testing\Snapshot;

require_once 'mrcore/MrScreen.php';
require_once 'mrcore/base/EnumColors.php';
require_once 'mrcore/console/AbstractPainter.php';

class MrScreenTest extends TestCase
{

    protected function setUp(): void
    {
        Snapshot::storeStaticProperties('MrScreen');
    }

    protected function tearDown(): void
    {
        Snapshot::restoreAll();
    }

    ##################################################################################

    public function testSetPainter(): void
    {
        $painter = $this->createStub(AbstractPainter::class);

        MrScreen::setPainter($painter);

        $this->assertInstanceOf(AbstractPainter::class, Helper::getStaticProperty(MrScreen::class, '_painter'));
    }

    ##################################################################################

    public function testEcho(): void
    {
        try
        {
            ConcreteMrScreenStubProtectedEcho::echo('message1', EnumColors::BLACK_GREEN);
        }
        catch (UnitTestException $e)
        {
            $this->assertSame('message1', $e->get('message'));
        }
    }

    ##################################################################################

    public function testEchoMessage(): void
    {
        try
        {
            ConcreteMrScreenStubProtectedEcho::echoMessage('message1');
        }
        catch (UnitTestException $e)
        {
            $this->assertSame('message1', $e->get('message'));
        }
    }

    ##################################################################################

    public function testEchoNotice(): void
    {
        try
        {
            ConcreteMrScreenStubProtectedEcho::echoNotice('message1');
        }
        catch (UnitTestException $e)
        {
            $this->assertSame('message1', $e->get('message'));
        }
    }

    ##################################################################################

    public function testEchoWarning(): void
    {
        try
        {
            ConcreteMrScreenStubProtectedEcho::echoWarning('message1');
        }
        catch (UnitTestException $e)
        {
            $this->assertSame('message1', $e->get('message'));
        }
    }

    ##################################################################################

    public function testEchoError(): void
    {
        try
        {
            ConcreteMrScreenStubProtectedEcho::echoError('message1');
        }
        catch (UnitTestException $e)
        {
            $this->assertSame('message1', $e->get('message'));
        }
    }

    ##################################################################################

    public function testEchoSuccess(): void
    {
        try
        {
            ConcreteMrScreenStubProtectedEcho::echoSuccess('message1');
        }
        catch (UnitTestException $e)
        {
            $this->assertSame('message1', $e->get('message'));
        }
    }

    ##################################################################################

    public function testWrapColorPainterEmpty(): void
    {
        $this->assertSame('Wrap color ...', MrScreen::wrapColor('Wrap color ...', 0));
    }

    ##################################################################################

    public function testWrapColorWithPainter(): void
    {
        $painter = $this->createStub(AbstractPainter::class);
        $painter->expects($this->once())->method('coloring');

        Helper::setStaticProperty(MrScreen::class, '_painter', $painter);

        MrScreen::wrapColor('Wrap color ...', 0);
    }

    ##################################################################################

    /**
     * @dataProvider listOfProtectedEchoProvider
     */
    public function testProtectedEcho(string $message, array $args, string $expected): void
    {
        $mrScreen = $this->createPartialMock(ConcreteMrScreen::class, []);

        ob_start();
        $mrScreen->testProtectedEcho($message, $args, EnumColors::BLACK_GREEN);
        $result = ob_get_clean();

        $this->assertSame($expected, $result);
    }

    ##################################################################################

    public function listOfProtectedEchoProvider(): array
    {
        return [
            ['Echo %s', ['Message'], "Echo Message\n"],
            ['Echo %s - %s', ['Message1', 'Message2'], "Echo Message1 - Message2\n"],
            ['!Echo %s', ['Message'], 'Echo Message'],
        ];
    }

    ##################################################################################

    public function testProtectedEchoPainter(): void
    {
        $painter = $this->createMock(AbstractPainter::class);
        $painter->expects($this->once())->method('coloring');

        $mrScreen = $this->createPartialMock(ConcreteMrScreen::class, []);
        Helper::setStaticProperty('MrScreen', '_painter', $painter);

        ob_start();
        $mrScreen->testProtectedEcho('Echo', ['Message'], EnumColors::BLACK_GREEN);
        ob_get_clean();
    }

}