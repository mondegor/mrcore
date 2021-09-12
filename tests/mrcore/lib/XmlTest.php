<?php declare(strict_types=1);
namespace mrcore\lib;
use PHPUnit\Framework\TestCase;

use mrcore\base\testdata\ConcreteXmlCastBool;
use mrcore\base\testdata\ConcreteXmlPrepareValue;

require_once 'mrcore/lib/Xml.php';

class XmlTest extends TestCase
{
    /**
     * @dataProvider listOfArray2xmlValuesProvider
     */
    public function testArray2xml($value, string $expected): void
    {
        $xml = $this->createPartialMock(ConcreteXmlPrepareValue::class, []);

        $this->assertSame($expected, $xml->array2xml('test-tag', $value));
    }

    public function listOfArray2xmlValuesProvider(): array
    {
        return [
            [['key' => 'abc'], "<test-tag>\n    <key>prepared-abc</key>\n</test-tag>\n"],
            [['key' => ['key1' => 1, 'key2' => 'value2']], "<test-tag>\n    <key>\n        <key1>prepared-1</key1>\n        <key2>prepared-value2</key2>\n    </key>\n</test-tag>\n"],
            [['@key' => 'abc'], "<test-tag key=\"prepared-abc\"/>\n"],
            [['-key' => 'abc'], "<test-tag>\n    <key>abc</key>\n</test-tag>\n"],
            [['~key' => 'abc'], "<test-tag>\n    <key escape=\"no\">prepared-abc</key>\n</test-tag>\n"],
            [['#key' => ['key1' => 1, 'key2' => 'value2']], "<test-tag>\n    <key>\n        <item id=\"key1\">prepared-1</item>\n        <item id=\"key2\">prepared-value2</item>\n    </key>\n</test-tag>\n"],
        ];
    }

    ##################################################################################

    public function testCastBool(): void
    {
        $this->assertSame(Xml::BOOL_FALSE, Xml::castBool(false));
        $this->assertSame(Xml::BOOL_TRUE, Xml::castBool(true));
    }

    ##################################################################################

    /**
     * @dataProvider listOfPrepareValueValuesProvider
     */
    public function testPrepareValue($value, $expected): void
    {
        $xml = $this->createPartialMock(ConcreteXmlCastBool::class, []);

        $this->assertSame($expected, $xml->prepareValue($value));
    }

    public function listOfPrepareValueValuesProvider(): array
    {
        return [
            [false, Xml::BOOL_TRUE],
            [true, Xml::BOOL_TRUE],
            [0, '0'],
            [1, '1'],
            [0.0, '0'],
            [1.0, '1'],
            [1.2, '1.2'],
            ['abc', 'abc'],
            ["abc&'\"", 'abc&amp;&#039;&quot;'],
        ];
    }

}