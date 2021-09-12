<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use mrcore\services\VarService;
use mrcore\testing\Snapshot;

require_once 'mrcore/services/VarService.php';

class VarServiceTest extends TestCase
{

    protected function setUp(): void
    {
        Snapshot::storeSuperglobal('$_REQUEST', ['testInt', 'testFloat', 'testString', 'testArray']);
        Snapshot::storeSuperglobal('$_COOKIE', ['testString']);
    }

    protected function tearDown(): void
    {
        Snapshot::restoreAll();
    }

    ##################################################################################

    /**
     * @dataProvider listOfValuesForCastProvider
     */
    public function testCast(int $type, $value, $expected): void
    {
        $this->assertSame($expected, VarService::cast($type, $value));
    }

    public function listOfValuesForCastProvider(): array
    {
        return [
            [VarService::T_INT, null,  0],
            [VarService::T_INT,    0,  0],
            [VarService::T_INT,  100,  100],
            [VarService::T_INT, -100,  -100],
            [VarService::T_INT, false, 0],
            [VarService::T_INT,  true, 1],
            [VarService::T_INT,  0.0,  0],
            [VarService::T_INT,  10.1, 10],
            [VarService::T_INT,  10.7, 10],
            [VarService::T_INT, -10.1, -10],
            [VarService::T_INT, -10.1, -10],
            [VarService::T_INT, -10.7, -10],
            [VarService::T_INT, '',    0],
            [VarService::T_INT, 'abc', 0],
            [VarService::T_INT, [],    0],
            [VarService::T_INT, ['item1'], 0],
            [VarService::T_INT, ['15item'], 15],
            [VarService::T_INT,  [''], 0],
            [VarService::T_INT, [124], 124],
            [VarService::T_INT, [[['17itemDeep3-1', 'itemDeep3-2']]], 17],

            [VarService::T_STRING, null,  ''],
            [VarService::T_STRING, 0,  '0'],
            [VarService::T_STRING, 100,  '100'],
            [VarService::T_STRING, -100,  '-100'],
            [VarService::T_STRING, false, ''],
            [VarService::T_STRING, true, '1'],
            [VarService::T_STRING, 0.0,  '0'],
            [VarService::T_STRING, 10.1, '10.1'],
            [VarService::T_STRING, -10.1, '-10.1'],
            [VarService::T_STRING, '',    ''],
            [VarService::T_STRING, 'abc', 'abc'],
            [VarService::T_STRING, [],    ''],
            [VarService::T_STRING, [''], ''],
            [VarService::T_STRING, ['item1'], 'item1'],
            [VarService::T_STRING, [[['itemDeep3-1', 'itemDeep3-2']]], 'itemDeep3-1'],

            [VarService::T_FLOAT, null,  0.0],
            [VarService::T_FLOAT,    0,  0.0],
            [VarService::T_FLOAT,  100,  100.0],
            [VarService::T_FLOAT, -100,  -100.0],
            [VarService::T_FLOAT, false, 0.0],
            [VarService::T_FLOAT,  true, 1.0],
            [VarService::T_FLOAT,  0.0,  0.0],
            [VarService::T_FLOAT,  10.1, 10.1],
            [VarService::T_FLOAT,  10.7, 10.7],
            [VarService::T_FLOAT, -10.1, -10.1],
            [VarService::T_FLOAT, -10.1, -10.1],
            [VarService::T_FLOAT, -10.7, -10.7],
            [VarService::T_FLOAT, '',    0.0],
            [VarService::T_FLOAT, 'abc', 0.0],
            [VarService::T_FLOAT, [],    0.0],
            [VarService::T_FLOAT, ['item1'], 0.0],
            [VarService::T_FLOAT, ['15.0item'], 15.0],
            [VarService::T_FLOAT, ['16.1item'], 16.1],
            [VarService::T_FLOAT, ['17,0item'], 17.0],
            [VarService::T_FLOAT, ['18,2item'], 18.2],
            [VarService::T_FLOAT, [''], 0.0],
            [VarService::T_FLOAT, [124], 124.0],
            [VarService::T_FLOAT, [[['17itemDeep3-1', 'itemDeep3-2']]], 17.0],

            [VarService::T_BOOL, null, false],
            [VarService::T_BOOL, 0, false],
            [VarService::T_BOOL, 185273099, true],
            [VarService::T_BOOL, -100,  true],
            [VarService::T_BOOL, false, false],
            [VarService::T_BOOL,  true, true],
            [VarService::T_BOOL,  0.0,  false],
            [VarService::T_BOOL, 185273099.1, true],
            [VarService::T_BOOL, -10.1, true],
            [VarService::T_BOOL, '',    false],
            [VarService::T_BOOL, 'abc', true],
            [VarService::T_BOOL, [],    false],
            [VarService::T_BOOL, [''], false],
            [VarService::T_BOOL, ['item1'], true],
            [VarService::T_BOOL, [[['itemDeep3-1', 'itemDeep3-2']]], true],

            [VarService::T_ENUM, null, ''],
            [VarService::T_ENUM, 0, ''],
            [VarService::T_ENUM, 185273099, 185273099],
            [VarService::T_ENUM, -100,  ''],
            [VarService::T_ENUM, false, ''],
            [VarService::T_ENUM,  true, 1],
            [VarService::T_ENUM,  0.0,  ''],
            [VarService::T_ENUM, 185273099.1, 185273099],
            [VarService::T_ENUM, -10.1, ''],
            [VarService::T_ENUM, '',    ''],
            [VarService::T_ENUM, 'abc', 'abc'],
            [VarService::T_ENUM, '  abc  ', 'abc'],
            [VarService::T_ENUM, [],    ''],
            [VarService::T_ENUM, [''], ''],
            [VarService::T_ENUM, ['  '], ''],
            [VarService::T_ENUM, ['item1'], 'item1'],
            [VarService::T_ENUM, [[['itemDeep3-1', 'itemDeep3-2']]], 'itemDeep3-1'],

            [VarService::T_DATE, null, ''],
            [VarService::T_DATE, 0, ''],
            [VarService::T_DATE, 185273099, ''],
            [VarService::T_DATE, -100,  ''],
            [VarService::T_DATE, 'abc',  ''],
            [VarService::T_DATE, '2020-01-01',  '2020-01-01'],

            [VarService::T_ARRAY, null,  []],
            [VarService::T_ARRAY,    0,  [0]],
            [VarService::T_ARRAY,  100,  [100]],
            [VarService::T_ARRAY, -100,  [-100]],
            [VarService::T_ARRAY, false, [false]],
            [VarService::T_ARRAY,  true, [true]],
            [VarService::T_ARRAY,  0.0,  [0.0]],
            [VarService::T_ARRAY,  10.1, [10.1]],
            [VarService::T_ARRAY,  10.7, [10.7]],
            [VarService::T_ARRAY, -10.1, [-10.1]],
            [VarService::T_ARRAY, -10.1, [-10.1]],
            [VarService::T_ARRAY, -10.7, [-10.7]],
            [VarService::T_ARRAY, '',    []],
            [VarService::T_ARRAY, 'abc', ['abc']],
            [VarService::T_ARRAY, [],    []],
            [VarService::T_ARRAY, [''], ['']],
            [VarService::T_ARRAY, ['item1'], ['item1']],
            [VarService::T_ARRAY, [124], [124]],
            [VarService::T_ARRAY, [[['17itemDeep3-1', 'itemDeep3-2']]], [[['17itemDeep3-1', 'itemDeep3-2']]]],

            [VarService::T_ESET, null, []],
            [VarService::T_ESET, 0, []],
            [VarService::T_ESET, 185273099, 185273099],
            [VarService::T_ESET, -100,  []],
            [VarService::T_ESET, false, []],
            [VarService::T_ESET,  true, 1],
            [VarService::T_ESET,  0.0,  []],
            [VarService::T_ESET, 185273099.1, 185273099],
            [VarService::T_ESET, -10.1, []],
            [VarService::T_ESET, '',    []],
            [VarService::T_ESET, '    ', []],
            [VarService::T_ESET, '  ,  ', []],
            [VarService::T_ESET, 'abc', ['abc']],
            [VarService::T_ESET, 'abc,abc2', ['abc', 'abc2']],
            [VarService::T_ESET, '  abc  ', ['abc']],
            [VarService::T_ESET, '  abc , abc2 ', ['abc', 'abc2']],
            [VarService::T_ESET, [],    []],
            [VarService::T_ESET, [''], []],
            [VarService::T_ESET, ['  ', '   '], []],
            [VarService::T_ESET, ['item1'], ['item1']],
            [VarService::T_ESET, ['item1', 'item2'], ['item1', 'item2']],
            [VarService::T_ESET, ['  item1  ', '  item2  ', 22], ['item1', 'item2']],
            [VarService::T_ESET, [[['itemDeep3-1', 'itemDeep3-2']]], []],

            [VarService::T_IP, null, ''],
            [VarService::T_IP, 0, ''],
            [VarService::T_IP, 185273099, '11.11.11.11'],
            [VarService::T_IP, -100,  ''],
            [VarService::T_IP, false, ''],
            [VarService::T_IP,  true, '0.0.0.1'],
            [VarService::T_IP,  0.0,  ''],
            [VarService::T_IP, 185273099.1, '11.11.11.11'],
            [VarService::T_IP, -10.1, ''],
            [VarService::T_IP, '',    ''],
            [VarService::T_IP, 'abc', ''],
            [VarService::T_IP, '11.11.11.11', '11.11.11.11'],
            [VarService::T_IP, [],    ''],
            [VarService::T_IP, [''], ''],
            [VarService::T_IP, ['item1'], ''],
            [VarService::T_IP, [[['itemDeep3-1', 'itemDeep3-2']]], ''],

            [VarService::T_IPLONG, null, 0],
            [VarService::T_IPLONG, 0, 0],
            [VarService::T_IPLONG, 185273099, 185273099],
            [VarService::T_IPLONG, -100,  0],
            [VarService::T_IPLONG, false, 0],
            [VarService::T_IPLONG,  true, 1],
            [VarService::T_IPLONG,  0.0,  0],
            [VarService::T_IPLONG, 185273099.1, 185273099],
            [VarService::T_IPLONG, -10.1, 0],
            [VarService::T_IPLONG, '',    0],
            [VarService::T_IPLONG, 'abc', 0],
            [VarService::T_IPLONG, '11.11.11.11', 185273099],
            [VarService::T_IPLONG, [],    0],
            [VarService::T_IPLONG, [''], 0],
            [VarService::T_IPLONG, ['item1'], 0],
            [VarService::T_IPLONG, [[['itemDeep3-1', 'itemDeep3-2']]], 0],
        ];
    }

    ##################################################################################

    /**
     * @dataProvider listOfValuesForConvertProvider
     */
    public function testConvert($value, $expected): void
    {
        $this->assertSame($expected, VarService::convert($value));
    }

    public function listOfValuesForConvertProvider(): array
    {
        return [
            [false, false],
            [1, 1],
            [1.0, 1.0],
            ['', ''],
            ['     ', ''],
            ['abc', 'abc'],
            ['  abc  ', 'abc'],
            [[false], [false]],
            [[1], [1]],
            [[1.0], [1.0]],
            [[''], ['']],
            [['     '], ['']],
            [['abc'], ['abc']],
            [['  abc  '], ['abc']],
        ];
    }

    ##################################################################################

    public function testGetByDefault(): void
    {
        $expected = 'defaultValue';

        $varService = $this->createPartialMock(VarService::class, []);
        $this->assertSame($expected, $varService->get('concreteVar', $expected));
    }

    ##################################################################################

    public function testGet(): void
    {
        $_REQUEST['testString'] = 'testValue';

        $varService = $this->createPartialMock(VarService::class, ['_wrapCast', '_wrapConvert']);
        $varService->expects($this->once())->method('_wrapCast');
        $varService->expects($this->once())->method('_wrapConvert')->willReturn('');

        $varService->get('testString');
    }

    ##################################################################################

    public function testGetIntByDefault(): void
    {
        $expected = 10;

        $varService = $this->createPartialMock(VarService::class, []);
        $this->assertSame($expected, $varService->getInt('concreteVar', $expected));
    }

    ##################################################################################

    public function testGetInt(): void
    {
        $_REQUEST['testInt'] = 0;

        $varService = $this->createPartialMock(VarService::class, ['_wrapCast']);
        $varService->expects($this->once())->method('_wrapCast')->willReturn(0);

        $varService->getInt('testInt');
    }

    ##################################################################################

    public function testGetFloatByDefault(): void
    {
        $expected = 10.0;

        $varService = $this->createPartialMock(VarService::class, []);
        $this->assertSame($expected, $varService->getFloat('concreteVar', $expected));
    }

    ##################################################################################

    public function testGetFloat(): void
    {
        $_REQUEST['testFloat'] = 0.0;

        $varService = $this->createPartialMock(VarService::class, ['_wrapCast']);
        $varService->expects($this->once())->method('_wrapCast')->willReturn(0.0);

        $varService->getFloat('testFloat');
    }

    ##################################################################################

    public function testGetArrayByDefault(): void
    {
        $expected = [1];

        $varService = $this->createPartialMock(VarService::class, []);
        $this->assertSame($expected, $varService->getArray('concreteVar', $expected));
    }

    ##################################################################################

    public function testGetArray(): void
    {
        $_REQUEST['testArray'] = [];

        $varService = $this->createPartialMock(VarService::class, ['_wrapCast', '_wrapConvert']);
        $varService->expects($this->once())->method('_wrapCast');
        $varService->expects($this->once())->method('_wrapConvert')->willReturn([]);

        $varService->getArray('testArray');
    }

    ##################################################################################

    public function testCookieIfEmpty(): void
    {
        $varService = $this->createPartialMock(VarService::class, []);
        $this->assertEmpty($varService->cookie('concreteVar'));
    }

    ##################################################################################

    public function testCookie(): void
    {
        $_COOKIE['testString'] = '';

        $varService = $this->createPartialMock(VarService::class, ['_wrapCast', '_wrapConvert']);
        $varService->expects($this->once())->method('_wrapCast');
        $varService->expects($this->once())->method('_wrapConvert')->willReturn('');

        $varService->cookie('testString');
    }

}