<?php declare(strict_types=1);
namespace mrcore\lib;
use PHPUnit\Framework\TestCase;
use mrcore\db\Query;
use mrcore\debug\DbProfiler;

use mrcore\db\testdata\ConcreteAdapter;
use mrcore\db\testdata\ConcreteAdapterEscape;
use mrcore\db\testdata\ConcreteAdapterEscapeValue;
use mrcore\testing\Helper;

require_once 'mrcore/db/Adapter.php';

class AdapterTest extends TestCase
{

    public function testConstructor(): void
    {
        $userError = E_USER_NOTICE;
        $dbProfile = $this->createStub(DbProfiler::class);
        $dbProfileClassName = get_class($dbProfile);

        ConcreteAdapter::$dbProfile = $dbProfile;
        $adapter = new ConcreteAdapter(['userError' => $userError, 'useProfiler' => true]);
        ConcreteAdapter::$dbProfile = null;

        $this->assertSame($userError, Helper::getProperty($adapter, '_userError'));
        $this->assertInstanceOf($dbProfileClassName, Helper::getProperty($adapter, '_profiler'));
    }

    ##################################################################################

    public function testConstructorIfEmptyParams(): void
    {
        $adapter = new ConcreteAdapter([]);

        $this->assertSame(E_USER_ERROR, Helper::getProperty($adapter, '_userError'));
        $this->assertNull(Helper::getProperty($adapter, '_profiler'));
    }

    ##################################################################################

    public function testGetProfiler(): void
    {
        $dbProfile = $this->createStub(DbProfiler::class);

        $adapter = $this->createPartialMock(ConcreteAdapter::class, []);

        $this->assertNull($adapter->getProfiler());
        Helper::setProperty($adapter, '_profiler', $dbProfile);
        $this->assertInstanceOf(get_class($dbProfile), $adapter->getProfiler());
    }

    ##################################################################################

    public function testQuery(): void
    {
        $query = $this->createStub(Query::class);

        $adapter = $this->createPartialMock(ConcreteAdapter::class, ['execQuery', '_createQuery']);
        $adapter->expects($this->once())->method('execQuery')->willReturn(true);
        $adapter->expects($this->once())->method('_createQuery')->willReturn($query);

        $this->assertInstanceOf(get_class($query), $adapter->query('sql string', []));
    }

    ##################################################################################

    public function testQueryIfError(): void
    {
        $adapter = $this->createPartialMock(ConcreteAdapter::class, ['execQuery']);
        $adapter->expects($this->once())->method('execQuery')->willReturn(false);

        $this->assertNull($adapter->query('sql string', []));
    }

    ##################################################################################

    /**
     * @dataProvider listOfBindValuesProvider
     */
    public function testBind(string $expr, $bind, string $expected): void
    {
        $bindEscaped = array_map(static function ($s) {
            return (string)$s;
        }, (is_array($bind) ? $bind : [$bind]));

        $adapter = $this->createPartialMock(ConcreteAdapter::class, ['_escapeValue']);
        $adapter->expects($this->exactly(count($bindEscaped)))
                ->method('_escapeValue')
                ->will($this->onConsecutiveCalls(...$bindEscaped));

        $this->assertSame($expected, $adapter->bind($expr, $bind));
    }

    public function listOfBindValuesProvider(): array
    {
        return [
            ['?', false, ''],
            ['?', true, '1'],
            ['?', 1, '1'],
            ['?', 1.0, '1'],
            ['?', 1.2, '1.2'],
            ['?', 'abc', 'abc'],
            ['? ?', [1, 'abc'], '1 abc'],
            ['(?) (?)', [1, 'abc'], '(1) (abc)'],
            ['%s ? %s', 'abc', '%s abc %s'],
        ];
    }

    ##################################################################################

    /**
     * @dataProvider listOfProtectedEscapeValueValuesProvider
     */
    public function testProtectedEscapeValue($value, string $expected): void
    {
        $adapter = $this->createPartialMock(ConcreteAdapterEscape::class, ['escape']);

        if (is_array($value))
        {
            $valueEscaped = array_map(static function ($s) {
                return (string)$s;
            }, $value);
            $adapter->method('escape')->will($this->onConsecutiveCalls(...$valueEscaped));
        }
        else
        {
            $adapter->method('escape')->willReturn((string)$value);
        }

        $this->assertSame($expected, $adapter->testEscapeValue($value));
    }

    public function listOfProtectedEscapeValueValuesProvider(): array
    {
        return [
            ['', "''"],
            [null, "''"],
            [[], "''"],
            [true, '1'],
            [false, '0'],
            [0, '0'],
            [1, '1'],
            [2.2, '2.2'],
            ['abc', "'abc'"],
            [['a', 1, 1.2, true, false, 33], "'a', 1, 1.2, 1, 0, 33"],
        ];
    }

    ##################################################################################

    /**
     * @dataProvider listOfProtectedSetExprValuesProvider
     */
    public function testProtectedSetExpr(array $set, string $expected): void
    {
        $adapter = $this->createPartialMock(ConcreteAdapter::class, ['_escapeValue']);

        $this->assertSame($expected, $adapter->testSetExpr($set, false));
    }

    public function listOfProtectedSetExprValuesProvider(): array
    {
        return [
            [['key1' => 'value1', 'key2' => 2], 'key1 = value1, key2 = 2'],
            [['key3' => false, 'key4' => 2.2, 'key5' => true], 'key3 = , key4 = 2.2, key5 = 1'],
        ];
    }

    ##################################################################################

    /**
     * @dataProvider listOfProtectedSetExprValuesProvider
     */
    public function testProtectedSetExprWithEscape(array $set, string $expected): void
    {
        $adapter = $this->createPartialMock(ConcreteAdapterEscapeValue::class, []);

        $this->assertSame($expected, $adapter->testSetExpr($set, true));
    }

    ##################################################################################

    /**
     * @dataProvider listOfProtectedWhereExprValuesProvider
     */
    public function testProtectedWhereExpr(array $where, string $expected): void
    {
        $adapter = $this->createPartialMock(ConcreteAdapter::class, []);

        $this->assertSame($expected, $adapter->testWhereExpr($where));
    }

    public function listOfProtectedWhereExprValuesProvider(): array
    {
        return [
            [[''], ''],
            [['test1'], '(test1)'],
            [['test1', 1], '(test1) AND (1)'],
            [['test1', true], '(test1) AND (1)'],
        ];
    }

}