<?php declare(strict_types=1);
namespace mrcore\units;
use PHPUnit\Framework\TestCase;
use mrcore\services\AppService;
use mrcore\services\EnvService;
use mrcore\services\ResponseService;
use mrcore\testing\Helper;
use mrcore\units\testdata\ConcreteErrorAction;

require_once 'mrcore/services/AppService.php';
require_once 'mrcore/services/EnvService.php';
require_once 'mrcore/services/ResponseService.php';
require_once 'mrcore/units/ErrorAction.php';

class ErrorActionTest extends TestCase
{

    public function testInitIfCodeAnswerGreaterOrEqual400(): void
    {
        $response = $this->createPartialMock(ResponseService::class, ['getAnswer', 'setAnswer']);
        $response->expects($this->once())->method('getAnswer')->willReturn(401);
        $response->expects($this->once())->method('setAnswer')->with($this->equalTo(200));

        $action = $this->createPartialMock(ConcreteErrorAction::class, ['injectService']);
        $action->expects($this->once())->method('injectService')
                                       ->with($this->equalTo('global.response'))
                                       ->willReturn($response);

        $action->testInit();
    }

    ##################################################################################

    public function testInitIfCodeAnswerLessThen400(): void
    {
        $response = $this->createPartialMock(ResponseService::class, ['getAnswer']);
        $response->expects($this->once())->method('getAnswer')->willReturn(301);

        $action = $this->createPartialMock(ConcreteErrorAction::class, ['injectService']);
        $action->expects($this->once())->method('injectService')
                                       ->with($this->equalTo('global.response'))
                                       ->willReturn($response);

        $action->testInit();

        $this->assertSame(404, Helper::getProperty($action, '_codeAnswer'));
    }

    ##################################################################################

    public function testRun(): void
    {
        $app = $this->createPartialMock(AppService::class, []);
        $app->environment = array
        (
            'section' => array
            (
                'isCache' => true,
                'charset' => 'test-charset',
                'contentType' => 'application/json',
            ),
            'decorTemplatePath' => '',
        );

        $env = $this->createPartialMock(EnvService::class, ['get']);
        $env->expects($this->once())->method('get')
                                    ->willReturn('path/pagenotfound/');

        $response = $this->createPartialMock(ResponseService::class, ['setAnswer']);
        $response->expects($this->once())->method('setAnswer');

        $action = $this->createPartialMock(ConcreteErrorAction::class, ['injectService']);
        Helper::setProperty($action, '_codeAnswer', 404);
        $action->expects($this->exactly(3))->method('injectService')
                                           ->will($this->onConsecutiveCalls($app, $env, $response));

        $action->run();

        $this->assertEmpty($action->residuePath);
        $this->assertFalse($app->environment['section']['isCache']);

        $this->assertSame(['codeAnswer' => 404,
                           'charset' => 'test-charset',
                           'contentType' => 'application/json',
                           'shortContentType' => 'json',
                           'requestedUri' => 'path/pagenotfound/'], $action->viewData);
    }

    ##################################################################################

    /**
     * @dataProvider listOfContentTypesAndDecorTemplatePathsProvider
     */
    public function testDecorTemplatePathThatOneIsCorrect(string $contentType, string $decorTemplatePath, string $expected): void
    {
        $app = $this->createPartialMock(AppService::class, []);
        $app->environment = array
        (
            'section' => array
            (
                // 'isCache' => true,
                'charset' => 'test-charset',
                'contentType' => $contentType,
            ),
            'decorTemplatePath' => $decorTemplatePath,
        );

        $env = $this->createPartialMock(EnvService::class, ['get']);
        $response = $this->createPartialMock(ResponseService::class, ['setAnswer']);

        $action = $this->createPartialMock(ConcreteErrorAction::class, ['injectService']);
        Helper::setProperty($action, '_codeAnswer', 404);
        $action->expects($this->exactly(3))->method('injectService')
                                           ->will($this->onConsecutiveCalls($app, $env, $response));

        $action->run();

        $this->assertSame($expected, $app->environment['decorTemplatePath']);
    }

    public function listOfContentTypesAndDecorTemplatePathsProvider(): array
    {
        return [
            ['text/html', '', 'system/error.html.tpl.php'],
            ['text/html', 'index.php', 'system/error.html.tpl.php'],
            ['text/html', 'index.xml', 'system/error.html.tpl.xml'],
            ['application/xml', 'index.xml', 'system/error.xml.tpl.xml'],
            ['application/js', 'index.xml', 'system/error.js.tpl.xml'],
            ['application/json', 'index.xml', 'system/error.json.tpl.xml'],
        ];
    }

}