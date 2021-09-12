<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;

use mrcore\testdata\ConcreteMrDebug;
use mrcore\testing\Helper;
use mrcore\testing\Snapshot;

require_once 'mrcore/MrDebug.php';

class MrDebugTest extends TestCase
{

    protected function setUp(): void
    {
        Snapshot::storeStaticProperties('MrDebug');
        Snapshot::storeSuperglobal('$_SERVER', ['HTTP_HEADER_TEST1', 'HTTP_HEADER_TEST2', 'HEADER_TEST3']);
    }

    protected function tearDown(): void
    {
        Snapshot::restoreAll();
    }

    ##################################################################################

    public function testSetGroupsInvalidArgumentException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        MrDebug::setGroups(['te st1']);
    }

    ##################################################################################

    /**
     * @dataProvider listOfSetLevelProvider
     */
    public function testSetLevelAsOutOfRangeException(int $level): void
    {
        $this->expectException(OutOfRangeException::class);
        MrDebug::setLevel($level);
    }

    public function listOfSetLevelProvider(): array
    {
        return [
            [-5, -1, 4, 5]
        ];
    }

    ##################################################################################

    /**
     * @dataProvider listOfIsGroupEnabledProvider
     */
    public function testIsGroupEnabled(string $group, int $curLevel, bool $expected): void
    {
        Helper::setStaticProperty('MrDebug', '_groups', ['test1' => true, 'test2' => true]);
        Helper::setStaticProperty('MrDebug', '_isAllGroups', false);
        Helper::setStaticProperty('MrDebug', '_level', $curLevel);

        $this->assertSame($expected, MrDebug::isGroupEnabled($group));
    }

    public function listOfIsGroupEnabledProvider(): array
    {
        return [
            ['test1:0', 0, true],
            ['test1:3', 0, true],

            ['test1:0', 3, false],
            ['test1:3', 3, true],

            ['test2:0', 0, true],
            ['test2:3', 0, true],

            ['test2:0', 3, false],
            ['test2:3', 3, true],

            ['test3:0', 0, false],
            ['test3:3', 0, false],

            ['test3:0', 3, false],
            ['test3:3', 3, false],
        ];
    }

    ##################################################################################

    /**
     * @dataProvider listOfHeadersProvider
     */
    public function testHeaders(array $headers, array $expected): void
    {
        $_SERVER = array_replace($_SERVER, $headers);

        $this->assertSame($expected, MrDebug::headers());
    }

    public function listOfHeadersProvider(): array
    {
        return [
            [['HTTP_HEADER_TEST1' => 'VALUE1'],
             ['Header-Test1' => 'VALUE1']],

            [['HTTP_HEADER_TEST2' => 'VALUE2', 'HEADER_TEST3' => 'VALUE3'],
             ['Header-Test2' => 'VALUE2']],

            [['HTTP_HEADER_TEST1' => 'VALUE1', 'HTTP_HEADER_TEST2' => 'VALUE2', 'HEADER_TEST3' => 'VALUE3'],
             ['Header-Test1' => 'VALUE1', 'Header-Test2' => 'VALUE2']],
        ];
    }

    ##################################################################################

    /**
     * @dataProvider listOfProtectedPackageFilterProvider
     */
    public function testProtectedPackageFilter(array $classes, array $packages, array $expected): void
    {
        $mrDebug = $this->createPartialMock(ConcreteMrDebug::class, []);

        $this->assertEquals($expected, $mrDebug->testProtectedPackageFilter($classes, $packages));
    }

    public function listOfProtectedPackageFilterProvider(): array
    {
        return [
            [['package1\Class1', 'package2\Class2', 'package3\Class3', 'package1Class1'],
             ['package1', 'package2'],
             ['package1\Class1', 'package2\Class2']],

            [['package1\Class1', 'package2\Class2', 'package3\Class3', 'package1Class1'],
             ['\package1', '\package2'],
             ['package1\Class1', 'package2\Class2']],

            [['package1\Class1', 'package2\Class2', 'package3\Class3', 'package1Class1'],
             ['package1\\', 'package2\\'],
             ['package1\Class1', 'package2\Class2']],

            [['package1\Class1', 'package2\Class2', 'package3\Class3', 'package1Class1'],
             ['\package1\\', '\package2\\'],
             ['package1\Class1', 'package2\Class2']],
        ];
    }

}