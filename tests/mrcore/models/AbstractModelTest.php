<?php declare(strict_types=1);
namespace mrcore\models;

// :TODO: сейчас здесь функциональные тесты, нужно переделать в юнит тесты

use PHPUnit\Framework\TestCase;
use mrcore\exceptions\DbException;

use mrcore\models\testdata\ConcreteModel;

require_once 'mrcore/models/AbstractModel.php';

class AbstractModelTest extends TestCase
{

    public function testConstructorAsNewObject(): void
    {
        $model = new ConcreteModel();

        $this->assertSame(0, $model->getId());
        $this->assertFalse($model->isLoaded());
    }

    ##################################################################################

    /**
     * @dataProvider listOfExistingPropsInObjectProvider
     */
    public function testSetExistingPropsInObjectWhenObjectCreating(string $propName, $value, $expected): void
    {
        $props = [$propName => $value];
        $model = new ConcreteModel(17891, $props);

        if (null === $expected && 'tagHelperField' !== $propName)
        {
            $expected = $value;
        }

        $this->assertSame($expected, $model->getProperty($propName, true));
        $this->assertTrue($model->isLoaded());
    }

    ##################################################################################

    /**
     * @dataProvider listOfExistingPropsInObjectProvider
     */
    public function testSetExistingPropsInObjectWhenObjectLoading(string $propName, $value, $expected): void
    {
        $props = [$propName => $value];
        $model = new ConcreteModel();
        $model->load(17891, [], $props);

        if (null === $expected)
        {
            $expected = $value;
        }

        $this->assertSame($expected, $model->getProperty($propName, true));
        $this->assertTrue($model->isLoaded());
    }

    public function listOfExistingPropsInObjectProvider(): array
    {
        return [
            ['primaryId',      100, 17891], // :WARNING 100 будет затираться, его бессмысленно указывать в $props
            ['boolField',      true, null],
            ['intField',       200, null],
            ['floatField',     10.0, null],
            ['timeField',      '12:33:44', null],
            ['dateField',      '2001-07-19', null],
            ['datetimeField',  '2001-07-19 12:33:44', null],
            ['timestampField', '20010719123344', null],
            ['stringField',    'stringValue', null],
            ['enumField',      'enumValue', null],
            ['esetField',      ['esetValue1', 'esetValue2'], ['esetValue1', 'esetValue2']],
            ['arrayField',     [1, 2, 3], [1, 2, 3]],
            ['ipField',        '11.22.33.44', null],
            ['ipField',        185999660, '11.22.33.44'],
            ['iplongField',    '11.22.33.44', 185999660],
            ['iplongField',    185999660, null],

            // ['tagHelperField',     150, null],
            ['tagComplexField',    'stringValue', null],
            ['tagCalculatedField', 200, 200]
        ];
    }

    ##################################################################################

    public function testSetTagHelperInObjectWhenObjectCreating(): void
    {
        $props = ['tagHelperField' => 192873];
        $model = new ConcreteModel(17891, $props);

        $this->assertNull($model->getProperty('tagHelperField', true));
        $this->assertFalse($model->isLoaded());
    }

    ##################################################################################

    public function testSetTagHelperInObjectWhenObjectLoading(): void
    {
        $props = ['tagHelperField' => 192873];
        $model = new ConcreteModel();
        $model->load(17891, [], $props);

        $this->assertNull($model->getProperty('tagHelperField', true));
        $this->assertFalse($model->isLoaded());
    }

    ##################################################################################

    public function testEmptyPropsWhenObjectResetting(): void
    {
        $props = ['boolField' => true, 'intField' => 1243];
        $model = new ConcreteModel(17891, $props);
        $model->reset();

        $this->assertSame(0, $model->getId());
        $this->assertSame(['primaryId' => 0], $model->getPropertiesForTest());
    }

    ##################################################################################

    public function testGetPrimaryName(): void
    {
        $model = new ConcreteModel();

        $this->assertSame('primaryId', $model->getPrimaryName(false));
        $this->assertSame('primary_id', $model->getPrimaryName());
        $this->assertSame('tbl.primary_id', $model->getPrimaryName(true, false));
    }

    ##################################################################################

    public function testGetPropertyIfObjectIsNotLoaded(): void
    {
        $this->expectException(DbException::class);
        $model = new ConcreteModel();
        $model->getProperty('boolField');
    }

    ##################################################################################

    public function testGetPropertyFromCache(): void
    {
        $props = ['boolField' => true, 'intField' => 1243, 'intFieldNull' => null];
        $model = new ConcreteModel(17891, $props);
        $this->assertSame(1243, $model->getProperty('intField', true));
        $this->assertNull($model->getProperty('intFieldNull', true));
    }

    ##################################################################################

    public function testGetPropertiesAlisesArray(): void
    {
        $props = ['boolField' => true, 'intField' => 1243, 'intFieldNull' => null];
        $fields = ['field1' => 'boolField', 'field2' => 'intField', 'field3' => 'intFieldNull'];

        $model = new ConcreteModel(17891, $props);
        $this->assertSame(['field1' => true, 'field2' => 1243, 'field3' => null], $model->getProperties($fields));
    }

}