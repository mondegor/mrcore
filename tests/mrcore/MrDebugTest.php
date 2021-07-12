<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use mrcore\testing\Snapshot;

require_once 'mrcore/MrDebug.php';

class MrDebugTest extends TestCase
{

    public static function setUpBeforeClass(): void
    {
        Snapshot::storeStaticProperties('MrDebug');
    }

    public static function tearDownAfterClass(): void
    {
        Snapshot::restoreAll();
    }

    ##################################################################################

    /**
     * @dataProvider listOfIsGroupEnabledProvider
     */
    public function testIsGroupEnabled(string $group, int $curLevel, bool $expected): void
    {
        MrDebug::setGroups(['test1', 'test2']);
        MrDebug::setLevel($curLevel);
        MrDebug::isGroupEnabled($group);

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
     * @dataProvider listOfSetLevelProvider
     */
    public function testSetLevelAsOutOfRange(int $level): void
    {
        $this->expectException(OutOfRangeException::class);
        MrDebug::setLevel($level);
    }

    public function listOfSetLevelProvider(): array
    {
        return [
            [-1, 4, 5]
        ];
    }

    ##################################################################################

    /**
     * @dataProvider listOfPackageFilterProvider
     */
    public function testPackageFilter(array $classes, array $packages, array $expected): void
    {
        // хитрый способ обращения к приватному методу класса
        $packageFilter = (static function ($classes, $packages) {
            return self::_packageFilter($classes, $packages);
        })->bindTo(null, MrDebug::class);

        $this->assertEquals($expected, $packageFilter($classes, $packages));
    }

    public function listOfPackageFilterProvider(): array
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