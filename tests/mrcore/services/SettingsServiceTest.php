<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use mrcore\db\Adapter;

use mrcore\testing\Helper;

require_once 'mrcore/db/Adapter.php';
require_once 'mrcore/services/SettingsService.php';

class SettingsServiceTest extends TestCase
{

    //protected function setUp(): void
    //{
    //}

    //protected function tearDown(): void
    //{
    //}

    ##################################################################################

    /**
     * @dataProvider listOfGetProvider
     */
    public function testGet(array $row, $expected): void
    {
        $connDb = $this->createStub(Adapter::class);
        $connDb->method('fetchRow')->willReturn($row);

        $settingsService = $this->createPartialMock(SettingsService::class, []);
        Helper::setProperty($settingsService, '_connDb', $connDb);

        $this->assertSame($expected, $settingsService->get('name'));
    }

    public function listOfGetProvider(): array
    {
        return [
            [[], null],

            [['0', 'bool'], false],
            [['1', 'bool'], true],
            [['2', 'bool'], true], // error

            [['1', 'float'], 1.0],
            [['1.0', 'float'], 1.0],
            [['1.5', 'float'], 1.5],
            [['10.5', 'float'], 10.5],
            [['0', 'float'], 0.0],
            [['0.0', 'float'], 0.0],
            [['', 'float'], 0.0], // error

            [['1', 'int'], 1],
            [['10', 'int'], 10],
            [['0', 'int'], 0],
            [['', 'int'], 0], // error

            [['10', 'string'], '10'],
            [['abc', 'string'], 'abc'],
            [['', 'string'], ''],

            [['', 'array'], []],
            [['{"key":1}', 'array'], ['key' => 1]],
            [['{"key":1,"key2":"value2"}', 'array'], ['key' => 1, 'key2' => 'value2']],
        ];
    }

    ##################################################################################

    /**
     * @dataProvider listOfGroupNamesForGetAndSetProvider
     */
    public function testGroupNamesForGet(string $name, array $expected): void
    {
        $connDb = $this->createMock(Adapter::class);
        $connDb->expects($this->once())->method('fetchRow')
                                       ->with($this->stringContains('SELECT setting_value,'),
                                              $this->equalTo($expected),
                                              $this->equalTo(false))
                                       ->willReturn([]);

        $settingsService = $this->createPartialMock(SettingsService::class, []);
        Helper::setProperty($settingsService, '_connDb', $connDb);

        $settingsService->get($name);
    }

    public function listOfGroupNamesForGetAndSetProvider(): array
    {
        return [
            ['name', ['', 'name']],
            ['group.name', ['group', 'name']],
            ['group.subgroup.name', ['group.subgroup', 'name']],
        ];
    }

    ##################################################################################

    /**
     * @dataProvider listOfGetGroupAndSomeProvider
     */
    public function testGetGroup(array $rows, array $expected): void
    {
        $rows[] = []; // признак завершения $connDb->fetch()

        $connDb = $this->createStub(Adapter::class);
        $connDb->method('fetch')->will($this->onConsecutiveCalls(...$rows));

        $settingsService = $this->createPartialMock(SettingsService::class, []);
        Helper::setProperty($settingsService, '_connDb', $connDb);

        $this->assertSame($expected, $settingsService->getGroup('group.name', true));
    }

    public function listOfGetGroupAndSomeProvider(): array
    {
        return [
            [[], []],

            [[['name1', '0', 'bool']], ['name1' => false]],
            [[['name1', '1', 'bool']], ['name1' => true]],
            [[['name1', '2', 'bool']], ['name1' => true]], // error

            [[['name1', '1', 'float']], ['name1' => 1.0]],
            [[['name1', '1.0', 'float']], ['name1' => 1.0]],
            [[['name1', '10.0', 'float']], ['name1' => 10.0]],
            [[['name1', '0', 'float']], ['name1' => 0.0]],
            [[['name1', '0.0', 'float']], ['name1' => 0.0]],
            [[['name1', '', 'float']], ['name1' => 0.0]], // error

            [[['name1', '1', 'int']], ['name1' => 1]],
            [[['name1', '10', 'int']], ['name1' => 10]],
            [[['name1', '0', 'int']], ['name1' => 0]],
            [[['name1', '', 'int']], ['name1' => 0]], // error

            [[['name1', '10', 'string']], ['name1' => '10']],
            [[['name1', 'abc', 'string']], ['name1' => 'abc']],
            [[['name1', '', 'string']], ['name1' => '']],

            [[['name1', '', 'array']], ['name1' => []]],
            [[['name1', '{"key":1}', 'array']], ['name1' => ['key' => 1]]],
            [[['name1', '{"key":1,"key2":"value2"}', 'array']], ['name1' => ['key' => 1, 'key2' => 'value2']]],

            [[['name1', '1', 'int'], ['name2', 'abc', 'string']], ['name1' => 1, 'name2' => 'abc']],
        ];
    }

    ##################################################################################

    public function testGetGroupMethods(): void
    {
        $connDb = $this->createMock(Adapter::class);
        $connDb->expects($this->once())->method('execQuery');
        $connDb->expects($this->once())->method('fetch')->willReturn([]);

        $settingsService = $this->createPartialMock(SettingsService::class, []);
        Helper::setProperty($settingsService, '_connDb', $connDb);

        $settingsService->getGroup('group.name', true);
    }

    ##################################################################################

    /**
     * @dataProvider listOfGetSomeMethodsProvider
     */
    public function testGetSomeMethods(array $names, bool $isFullName): void
    {
        $fieldName = $isFullName ? "CONCAT(setting_group, '.', setting_name)" : 'setting_name';

        $connDb = $this->createMock(Adapter::class);
        $connDb->expects($this->once())->method('execQuery')
                                       ->with($this->stringContains('SELECT ' . $fieldName . ','));
        $connDb->expects($this->once())->method('fetch')->willReturn([]);

        $settingsService = $this->createPartialMock(SettingsService::class, ['_getExpr']);
        $settingsService->expects($this->exactly(count($names)))->method('_getExpr');
        Helper::setProperty($settingsService, '_connDb', $connDb);

        $settingsService->getSome(...$names);
    }

    public function listOfGetSomeMethodsProvider(): array
    {
        return [
            [[''], false],
            [['name'], false],
            [['group.name'], false],
            [['name1', 'name2'], false],
            [['group.name1', 'group.name2'], false],
            [['name1', 'group2.name1'], true],
            [['group1.name1', 'group2.name1'], true],
            [['name1', 'name2', 'group2.name1'], true],
        ];
    }

    ##################################################################################

    /**
     * @dataProvider listOfGetGroupAndSomeProvider
     */
    public function testGetSome(array $rows, array $expected): void
    {
        $names = [];

        foreach ($rows as $row)
        {
            $names[] = $row[0];
        }

        $rows[] = []; // признак завершения $connDb->fetch()

        $connDb = $this->createStub(Adapter::class);
        $connDb->method('fetch')->will($this->onConsecutiveCalls(...$rows));

        $settingsService = $this->createPartialMock(SettingsService::class, ['_getExpr']);
        Helper::setProperty($settingsService, '_connDb', $connDb);

        $this->assertSame($expected, $settingsService->getSome(...$names));
    }

    ##################################################################################

    /**
     * @dataProvider listOfGroupNamesForGetAndSetProvider
     */
    public function testGroupNamesForSet(string $name, array $expected): void
    {
        $value = 0;
        array_unshift($expected, 'int', $value);

        $connDb = $this->createMock(Adapter::class);
        $connDb->expects($this->once())->method('execQuery')
                                       ->with($this->stringContains('UPDATE mrcore_global_settings'),
                                              $this->equalTo($expected))
                                       ->willReturn([]);
        $connDb->expects($this->once())->method('getAffectedRows')->willReturn(1);

        $settingsService = $this->createPartialMock(SettingsService::class, []);
        Helper::setProperty($settingsService, '_connDb', $connDb);

        $settingsService->set($name, $value);
    }

    ##################################################################################

    /**
     * @dataProvider listOfSetProvider
     */
    public function testSet($row, array $expected): void
    {
        array_push($expected, 'group', 'name');

        $connDb = $this->createMock(Adapter::class);
        $connDb->expects($this->once())->method('execQuery')
                                       ->with($this->anything(),
                                              $this->equalTo($expected))
                                       ->willReturn([]);
        $connDb->expects($this->once())->method('getAffectedRows')->willReturn(1);

        $settingsService = $this->createPartialMock(SettingsService::class, []);
        Helper::setProperty($settingsService, '_connDb', $connDb);

        $settingsService->set('group.name', $row);
    }

    public function listOfSetProvider(): array
    {
        return [
            [false, ['bool', false]],
            [true, ['bool', true]],

            [0.0, ['float', 0.0]],
            [1.0, ['float', 1.0]],
            [10.0, ['float', 10.0]],

            [0, ['int', 0]],
            [1, ['int', 1]],
            [10, ['int', 10]],

            [null, ['string', '']],
            ['', ['string', '']],
            ['abc', ['string', 'abc']],
            ['10', ['string', '10']],

            [[], ['array', '']],
            [['key' => 1], ['array', '{"key":1}']],
            [['key' => 1, 'key2' => 'value2'], ['array', '{"key":1,"key2":"value2"}']],
        ];
    }

}