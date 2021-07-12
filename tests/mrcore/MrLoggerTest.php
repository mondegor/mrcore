<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use mrcore\exceptions\UnitTestException;
use mrcore\testing\Snapshot;

require_once 'mrcore/MrLogger.php';

class MrLoggerTest extends TestCase
{

    public static function setUpBeforeClass(): void
    {
        Snapshot::storeStaticProperties('MrLogger');
    }

    public static function tearDownAfterClass(): void
    {
        Snapshot::restoreAll();
    }

    ##################################################################################

    /**
     * @dataProvider listOfWriteToProvider
     */
    public function testWriteTo(string $fileName, string $message, array $expected): void
    {
        MrLogger::init('/testpath/', 'testlogin');

        try
        {
            MrLogger::writeTo($fileName, $message);
        }
        catch (UnitTestException $e)
        {
            $args = $e->getArgs();
            $this->assertStringContainsString($message, $args['message']);
            unset($args['message']);

            $this->assertEquals($expected, $args);
        }
    }

    public function listOfWriteToProvider(): array
    {
        return [
            ['fileforlog', 'mymessage', ['filePath' => '/testpath/fileforlog',
                                         'event' => '',
                                         'developer' => 'testlogin_']],

            ['fileforlog:', 'mymessage', ['filePath' => '/testpath/mrlog',
                                          'event' => '',
                                          'developer' => 'testlogin_']],

            ['fileforlog:event', 'mymessage', ['filePath' => '/testpath/fileforlog',
                                               'event' => 'event',
                                               'developer' => 'testlogin_']],

            [':event', 'mymessage', ['filePath' => '/testpath/mrlog',
                                     'event' => 'event',
                                     'developer' => 'testlogin_']],

            ['filefo!rlog', 'mymessage', ['filePath' => '/testpath/mrlog',
                                          'event' => '',
                                          'developer' => 'testlogin_']],
        ];
    }

}