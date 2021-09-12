<?php declare(strict_types=1);
namespace mrcore\db;
use PHPUnit\Framework\TestCase;
// use mrcore\db\HelperExpr;
use mrcore\exceptions\DbException;
use mrcore\models\HelperFieldSet;
use mrcore\services\VarService;

use mrcore\models\testdata\ConcreteHelperFieldSet;
use mrcore\testing\Helper;

require_once 'mrcore/db/HelperExpr.php';
require_once 'mrcore/models/HelperFieldSet.php';
require_once 'mrcore/exceptions/DbException.php';

class HelperFieldSetTest extends TestCase
{

    public function testConstructor(): void
    {
        $fields = ['fieldName' => ['key1' => 'value1']];
        $props = ['propName' => ['key1' => 'value1']];

        $fieldSet = new HelperFieldSet($fields, $props);

        $this->assertSame($fields, Helper::getProperty($fieldSet, '_fields'));
        $this->assertSame($props, Helper::getProperty($fieldSet, '_props'));
    }

    ##################################################################################

    /**
     * @dataProvider listOfValidPropsForAddProvider
     */
    public function testAddValidField(string $name, $value, $expected): void
    {
        $fieldSet = $this->createPartialMock(HelperFieldSet::class, []);

        Helper::setProperty($fieldSet, '_fields', $GLOBALS['HelperFieldSetTestFields']);
        Helper::setProperty($fieldSet, '_props', [$name => $value]);

        $fieldSet->add($name);
        $set = Helper::getProperty($fieldSet, '_set');
        $setValue = $set[$name] ?? null;

        if (is_object($expected))
        {
            $this->assertSame(serialize($expected), serialize($setValue));
        }
        else
        {
            $this->assertSame($expected, $setValue);
        }
    }

    ##################################################################################

    /**
     * @dataProvider listOfValidPropsForAddProvider
     */
    public function testAddFieldWithDefaultValue(string $name, $value, $expected): void
    {
        $fieldSet = $this->createPartialMock(HelperFieldSet::class, []);

        Helper::setProperty($fieldSet, '_fields', $GLOBALS['HelperFieldSetTestFields']);
        Helper::setProperty($fieldSet, '_props', [$name => $value]);

        $fieldSet->add($name, $value);
        $set = Helper::getProperty($fieldSet, '_set');
        $setValue = $set[$name] ?? null;

        if (is_object($expected))
        {
            $this->assertSame(serialize($expected), serialize($setValue));
        }
        else
        {
            $this->assertSame($expected, $setValue);
        }
    }

    ##################################################################################

    /**
     * @dataProvider listOfValidPropsForAddProvider
     */
    public function testSetValidField(string $name, $value, $expected): void
    {
        $fieldSet = $this->createPartialMock(HelperFieldSet::class, []);

        Helper::setProperty($fieldSet, '_fields', $GLOBALS['HelperFieldSetTestFields']);
        Helper::setProperty($fieldSet, '_props', [$name => $value]);

        $fieldSet->set($name);
        $set = Helper::getProperty($fieldSet, '_set');
        $setValue = $set[$name] ?? null;

        if (is_object($expected))
        {
            $this->assertSame(serialize($expected), serialize($setValue));
        }
        else
        {
            $this->assertSame($expected, $setValue);
        }
    }

    public function listOfValidPropsForAddProvider(): array
    {
        $NULL = new HelperExpr('NULL');

        return [
            // ['boolField', null, 0], // ERROR
            ['boolField', true, 1],
            ['boolField', false, 0],

            // ['intField', null, 0], // ERROR
            ['intField', 0, 0],
            ['intField', 1, 1],
            ['intField', -1, -1],
            ['intField', 100, 100],

            // ['floatField', null, 0.0], // ERROR
            ['floatField', 0.0, 0.0],
            ['floatField', 1.0, 1.0],
            ['floatField', -1.0, -1.0],
            ['floatField', 100.0, 100.0],

            // ['timeField', null, ''], // ERROR
            // ['timeField', '', ''], // ERROR
            ['timeField', '22:33:22', '22:33:22'],

            // ['dateField', null, ''], // ERROR
            // ['dateField', '', ''], // ERROR
            ['dateField', '2030-12-11', '2030-12-11'],

            // ['datetimeField', null, ''], // ERROR
            // ['datetimeField', '', ''], // ERROR
            ['datetimeField', '2030-12-11 22:33:22', '2030-12-11 22:33:22'],

            // ['timestampField', null, ''], // ERROR
            // ['timestampField', '', ''], // ERROR
            ['timestampField', '20301211223322', '20301211223322'],

            // ['stringField', null, ''], // ERROR
            ['stringField', '', ''],
            ['stringField', 'abc', 'abc'],

            // ['enumField', null, ''], // ERROR
            // ['enumField', '', ''], // ERROR
            ['enumField', 'value1', 'value1'],
            // ['enumField', 0, ''], // ERROR
            ['enumField', 1, 1],

            // ['esetField', null, ''], // ERROR
            // ['esetField', '', ''], // ERROR
            ['esetField', 'value1', 'value1'],
            ['esetField', 'value1,value2', 'value1,value2'],
            ['esetField', ['value1'], 'value1'],
            ['esetField', ['value1', 'value2'], 'value1,value2'],
            // ['esetField', 0, ''], // ERROR
            ['esetField', 1, 1],

            // ['arrayField', null, ''], // ERROR
            ['arrayField', '', ''],
            ['arrayField', [], ''],
            ['arrayField', ['value1'], '["value1"]'],

            // ['ipField', null, ''], // ERROR
            ['ipField', '', ''],
            ['ipField', 0, ''],
            ['ipField', '11.22.33.44', '11.22.33.44'],
            ['ipField', 185999660, '11.22.33.44'],

            // ['iplongField', null, 0], // ERROR
            ['iplongField', 0, 0],
            ['iplongField', '', 0],
            ['iplongField', '11.22.33.44', 185999660],
            ['iplongField', 185999660, 185999660],



            ['boolFieldNull', null, $NULL],
            ['boolFieldNull', true, 1],
            ['boolFieldNull', false, 0],

            ['intFieldNull', null, $NULL],
            ['intFieldNull', 0, 0],
            ['intFieldNull', 1, 1],
            ['intFieldNull', -1, -1],
            ['intFieldNull', 100, 100],

            ['floatFieldNull', null, $NULL],
            ['floatFieldNull', 0.0, 0.0],
            ['floatFieldNull', 1.0, 1.0],
            ['floatFieldNull', -1.0, -1.0],
            ['floatFieldNull', 100.0, 100.0],

            ['timeFieldNull', null, $NULL],
            ['timeFieldNull', '', $NULL],
            ['timeFieldNull', '22:33:22', '22:33:22'],

            ['dateFieldNull', null, $NULL],
            ['dateFieldNull', '', $NULL],
            ['dateFieldNull', '2030-12-11', '2030-12-11'],

            ['datetimeFieldNull', null, $NULL],
            ['datetimeFieldNull', '', $NULL],
            ['datetimeFieldNull', '2030-12-11 22:33:22', '2030-12-11 22:33:22'],

            ['timestampFieldNull', null, $NULL],
            ['timestampFieldNull', '', $NULL],
            ['timestampFieldNull', '20301211223322', '20301211223322'],

            ['stringFieldNull', null, $NULL],
            ['stringFieldNull', '', $NULL],
            ['stringFieldNull', 'abc', 'abc'],

            ['enumFieldNull', null, $NULL],
            ['enumFieldNull', '', $NULL],
            ['enumFieldNull', 'value1', 'value1'],
            ['enumFieldNull', 0, $NULL],
            ['enumFieldNull', 1, 1],

            ['esetFieldNull', null, $NULL],
            ['esetFieldNull', '', $NULL],
            ['esetFieldNull', 'value1', 'value1'],
            ['esetFieldNull', 'value1,value2', 'value1,value2'],
            ['esetFieldNull', ['value1'], 'value1'],
            ['esetFieldNull', ['value1', 'value2'], 'value1,value2'],
            ['esetFieldNull', 0, $NULL],
            ['esetFieldNull', 1, 1],

            ['arrayFieldNull', null, $NULL],
            ['arrayFieldNull', '', $NULL],
            ['arrayFieldNull', [], $NULL],
            ['arrayFieldNull', ['value1'], '["value1"]'],

            ['ipFieldNull', null, $NULL],
            ['ipFieldNull', '', $NULL],
            ['ipFieldNull', 0, $NULL],
            ['ipFieldNull', '11.22.33.44', '11.22.33.44'],
            ['ipFieldNull', 185999660, '11.22.33.44'],

            ['iplongFieldNull', null, $NULL],
            ['iplongFieldNull', 0, $NULL],
            ['iplongFieldNull', '', $NULL],
            ['iplongFieldNull', '11.22.33.44', 185999660],
            ['iplongFieldNull', 185999660, 185999660],



            ['boolFieldNull2', null, $NULL],
            ['boolFieldNull2', true, 1],
            ['boolFieldNull2', false, 0],

            ['intFieldNull2', null, $NULL],
            ['intFieldNull2', 0, 0],
            ['intFieldNull2', 1, 1],
            ['intFieldNull2', -1, -1],
            ['intFieldNull2', 100, 100],

            ['floatFieldNull2', null, $NULL],
            ['floatFieldNull2', 0.0, 0.0],
            ['floatFieldNull2', 1.0, 1.0],
            ['floatFieldNull2', -1.0, -1.0],
            ['floatFieldNull2', 100.0, 100.0],

            ['timeFieldNull2', null, $NULL],
            // ['timeFieldNull2', '', $NULL], // ERROR
            ['timeFieldNull2', '22:33:22', '22:33:22'],

            ['dateFieldNull2', null, $NULL],
            // ['dateFieldNull2', '', $NULL], // ERROR
            ['dateFieldNull2', '2030-12-11', '2030-12-11'],

            ['datetimeFieldNull2', null, $NULL],
            // ['datetimeFieldNull2', '', $NULL], // ERROR
            ['datetimeFieldNull2', '2030-12-11 22:33:22', '2030-12-11 22:33:22'],

            ['timestampFieldNull2', null, $NULL],
            // ['timestampFieldNull2', '', $NULL], // ERROR
            ['timestampFieldNull2', '20301211223322', '20301211223322'],

            ['stringFieldNull2', null, $NULL],
            // ['stringFieldNull2', '', $NULL], // ERROR
            ['stringFieldNull2', 'abc', 'abc'],

            ['enumFieldNull2', null, $NULL],
            // ['enumFieldNull2', '', $NULL], // ERROR
            ['enumFieldNull2', 'value1', 'value1'],
            // ['enumFieldNull2', 0, $NULL], // ERROR
            ['enumFieldNull2', 1, 1],

            ['esetFieldNull2', null, $NULL],
            // ['esetFieldNull2', '', $NULL], // ERROR
            ['esetFieldNull2', 'value1', 'value1'],
            ['esetFieldNull2', 'value1,value2', 'value1,value2'],
            ['esetFieldNull2', ['value1'], 'value1'],
            ['esetFieldNull2', ['value1', 'value2'], 'value1,value2'],
            // ['esetFieldNull2', 0, $NULL], // ERROR
            ['esetFieldNull2', 1, 1],

            ['arrayFieldNull2', null, $NULL],
            // ['arrayFieldNull2', '', $NULL], // ERROR
            // ['arrayFieldNull2', [], $NULL], // ERROR
            ['arrayFieldNull2', ['value1'], '["value1"]'],

            ['ipFieldNull2', null, $NULL],
            // ['ipFieldNull2', '', $NULL], // ERROR
            // ['ipFieldNull2', 0, $NULL], // ERROR
            ['ipFieldNull2', '11.22.33.44', '11.22.33.44'],
            ['ipFieldNull2', 185999660, '11.22.33.44'],

            ['iplongFieldNull2', null, $NULL],
            // ['iplongFieldNull2', 0, $NULL], // ERROR
            // ['iplongFieldNull2', '', $NULL], // ERROR
            ['iplongFieldNull2', '11.22.33.44', 185999660],
            ['iplongFieldNull2', 185999660, 185999660],
        ];
    }

    ##################################################################################

    public function testGetValueIfExists(): void
    {
        $fieldSet = $this->createPartialMock(HelperFieldSet::class, []);
        Helper::setProperty($fieldSet, '_set', ['testName' => 'testValue']);

        $this->assertSame('testValue', $fieldSet->getValue('testName'));
    }

    ##################################################################################

    public function testGetValueIfNotExists(): void
    {
        $this->expectException(DbException::class);

        $fieldSet = $this->createPartialMock(HelperFieldSet::class, []);

        $fieldSet->getValue('testName');
    }

    ##################################################################################

    public function testIsEmptyTrue(): void
    {
        $fieldSet = $this->createPartialMock(HelperFieldSet::class, []);
        $this->assertTrue($fieldSet->isEmpty());
    }

    ##################################################################################

    public function testIsEmptyFalse(): void
    {
        $fieldSet = $this->createPartialMock(HelperFieldSet::class, []);

        Helper::setProperty($fieldSet, '_set', ['test' => true]);

        $this->assertFalse($fieldSet->isEmpty());
    }

    ##################################################################################

    /**
     * @dataProvider listValuesForDbNameProvider
     */
    public function testDbName(string $dbName, string $expected): void
    {
        $fieldSet = $this->createPartialMock(HelperFieldSet::class, []);

        Helper::setProperty($fieldSet, '_fields', ['testName' => ['dbName' => $dbName]]);
        $this->assertSame($expected, $fieldSet->dbName('testName'));
    }

    public function listValuesForDbNameProvider(): array
    {
        return [
            ['name1', 'name1'],
            ['table1.name1', 'name1']
        ];
    }

    ##################################################################################

    public function testProtectedDbType(): void
    {
        $fieldSet = $this->createPartialMock(ConcreteHelperFieldSet::class, []);

        Helper::setProperty($fieldSet, '_fields', ['testName1' => ['type' => VarService::T_INT]]);

        $this->assertSame(VarService::T_INT, $fieldSet->testProtectedDbType('testName1'));
        $this->assertSame(VarService::T_STRING, $fieldSet->testProtectedDbType('testName2'));
    }

    ##################################################################################

    /**
     * @dataProvider listValuesForProtectedIsNullProvider
     */
    public function testProtectedIsNull($value, bool $expected): void
    {
        $fieldSet = $this->createPartialMock(ConcreteHelperFieldSet::class, []);

        Helper::setProperty($fieldSet, '_fields', ['testName1' => ('EMPTY' === $value ? [] : ['null' => $value])]);

        $this->assertSame($expected, $fieldSet->testProtectedIsNull('testName1'));
    }

    public function listValuesForProtectedIsNullProvider(): array
    {
        return [
            [true, true],
            [false, false],
            [null, false],
            ['EMPTY', false],
        ];
    }

    ##################################################################################

    /**
     * @dataProvider listValuesForProtectedIsEmptyToNullProvider
     */
    public function testProtectedIsEmptyToNull($value, bool $expected): void
    {
        $fieldSet = $this->createPartialMock(ConcreteHelperFieldSet::class, []);

        Helper::setProperty($fieldSet, '_fields', ['testName1' => ('EMPTY' === $value ? [] : ['emptyToNull' => $value])]);

        $this->assertSame($expected, $fieldSet->testProtectedIsEmptyToNull('testName1'));
    }

    public function listValuesForProtectedIsEmptyToNullProvider(): array
    {
        return [
            [true, true],
            [false, false],
            [null, true],
            ['EMPTY', true],
        ];
    }

    ##################################################################################

    /**
     * @dataProvider listValuesForProtectedGetDbValueProvider
     */
    public function testProtectedGetDbValue($value, $zero, bool $isNull, bool $isEmptyToNull, $expected): void
    {
        $fieldSet = $this->createPartialMock(ConcreteHelperFieldSet::class, []);

        $value = $fieldSet->testProtectedGetDbValue($value, $zero, $isNull, $isEmptyToNull);

        if (is_object($expected))
        {
            $this->assertSame(serialize($expected), serialize($value));
        }
        else
        {
            $this->assertSame($expected, $value);
        }
    }

    public function listValuesForProtectedGetDbValueProvider(): array
    {
        $NULL = new HelperExpr('NULL');

        return [
            [null, null, false, false, null],
            [null, null, false, true, null],
            [null, null, true, false, $NULL],
            [null, null, true, true, $NULL],

            [null, 0, false, false, 0],
            [null, 0, false, true,0],
            [null, 0, true, false, $NULL],
            [null, 0, true, true, $NULL],

            [0, null, false, false, 0],
            [0, null, false, true, 0],
            [0, null, true, false, 0],
            [0, null, true, true,0],

            [0, 0, false, false, 0],
            [0, 0, false, true, 0],
            [0, 0, true, false, 0],
            [0, 0, true, true,$NULL],
        ];
    }

}