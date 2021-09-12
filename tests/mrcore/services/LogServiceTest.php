<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use mrcore\exceptions\UnitTestException;
use mrcore\services\EnvService;
use mrcore\services\LogService;

use mrcore\testing\Helper;
use mrcore\services\testdata\ConcreteLogService;

require_once 'mrcore/services/EnvService.php';
require_once 'mrcore/services/LogService.php';

class LogServiceTest extends TestCase
{

    public function testConstructor(): void
    {
        $envService = $this->createStub(EnvService::class);

        $logService = new LogService($envService, '#testPathToLog', 'devlogin');

        $this->assertInstanceOf(EnvService::class, Helper::getProperty($logService, '_envService'));
        $this->assertSame('#testPathToLog', Helper::getProperty($logService, '_pathToLog'));
        $this->assertSame('devlogin_', Helper::getProperty($logService, '_developerLogin'));
    }

    ##################################################################################

    public function testWriteTo(): void
    {
        $expected = ['filePath' => '/#testpath/#mrlog',
                     'event' => '#event',
                     'developer' => '#testlogin'];

        $envService = $this->createStub(EnvService::class);
        $envService->method('getUserIP')->willReturn(['ip_real' => 1, 'string' => '#ip-string']);
        $envService->method('getRequestUrl')->willReturn('#url');

        $logService = $this->createPartialMock(LogService::class, ['_parseFileName']);
        $logService->method('_parseFileName')->willReturn(['#mrlog', '#event']);

        Helper::setProperties($logService, [
            '_envService' => &$envService,
            '_pathToLog' => '/#testpath/',
            '_developerLogin' => '#testlogin'
        ]);

        try
        {
            $logService->writeTo('', '#message');
        }
        catch (UnitTestException $e)
        {
            $args = $e->getArgs();
            $this->assertStringContainsString('#message', $args['message']);
            $this->assertStringContainsString('url: #url', $args['message']);

            unset($args['message']);

            $this->assertEquals($expected, $args);
        }
    }

    ##################################################################################

    /**
     * @dataProvider listOfProtectedParseFileNameProvider
     */
    public function testProtectedParseFileName(string $string, array $expected): void
    {
        $logService = $this->createPartialMock(ConcreteLogService::class, []);
        $this->assertSame($expected, $logService->testParseFileName($string, ['default-file', 'default-event']));
    }

    public function listOfProtectedParseFileNameProvider(): array
    {
        return [
            ['', ['default-file', 'default-event']],
            ['filename', ['filename', 'default-event']],
            ['filename:event', ['filename', 'event']],
            ['filename:', ['filename', '']],
            [':event', ['default-file', 'event']],
            ['filena!me', ['default-file', 'default-event']],
        ];
    }

}