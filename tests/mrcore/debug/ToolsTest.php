<?php declare(strict_types=1);
namespace mrcore\debug;
use PHPUnit\Framework\TestCase;

require_once 'mrcore/debug/Tools.php';

class TestObject1 { private int $_var = 1; }
class TestObject2 { private int $_var1 = 1; private static int $_var2 = 2; }

class ToolsTest extends TestCase
{
    /**
     * @dataProvider listOfVar2StrProvider
     */
    public function testVar2Str($variable, int $number, $expected): void
    {
        $this->assertSame($expected, Tools::var2str($variable, $number));
    }

    public function listOfVar2StrProvider(): array
    {
        return [
            [null, 0, 'NULL'],
            [1, 0, 'int(1)'],
            [1.0, 0, 'float(1)'],
            [1.01, 0, 'float(1.01)'],
            [false, 0, 'bool(false)'],
            [true, 0, 'bool(true)'],
            ['', 0, 'string(0)'],
            ['abc', 0, 'string(3) "abc"'],
            ['abcd', 0, 'string(4) "abcd"'],
            ['abcdefghij0123456789abcdefghij0123456789abc', 0, 'string(43) "abcdefghij0123456789abcdefghij0123456789abc"'],
            ['abcdefghij0123456789abcdefghij0123456789abcd', 0, 'string(44) "abcdefghij0123456789abcdefghij01...6789abcd"'],
            ['abcdefghij0123456789abcdefghij0123456789abcdefghij', 0, 'string(50) "abcdefghij0123456789abcdefghij01...cdefghij"'],
            [[], 0, 'array(0)'],
            [['a'], 0, 'array(1) {string(1) "a"}'],
            [['a', 'b', 'c'], 0, 'array(3) {string(1) "a", string(1) "b", string(1) "c"}'],
            [['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j'], 0, 'array(10) {string(1) "a", string(1) "b", string(1) "c", string(1) "d", string(1) "e", string(1) "f", string(1) "g", string(1) "h", string(1) "i", string(1) "j"}'],
            [['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k'], 0, 'array(11) {string(1) "a", string(1) "b", string(1) "c", string(1) "d", string(1) "e", string(1) "f", string(1) "g", string(1) "h", string(1) "i", string(1) "j", ...}'],
            [['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n'], 0, 'array(14) {string(1) "a", string(1) "b", string(1) "c", string(1) "d", string(1) "e", string(1) "f", string(1) "g", string(1) "h", string(1) "i", string(1) "j", ...}'],
            [[0 => 'a', 1 => 'b', 2 => 'c'], 0, 'array(3) {string(1) "a", string(1) "b", string(1) "c"}'],
            [[0 => 'a', 'i' => 'b', 2 => 'c'], 0, 'array(3) {0 => string(1) "a", i => string(1) "b", 2 => string(1) "c"}'],
            [new TestObject1(), 0, 'object(1) "mrcore\debug\TestObject1"'],
            [new TestObject2(), 0, 'object(2) "mrcore\debug\TestObject2"'],
            [[0 => 'a', 1 => 'b', 2 => [0 => 'a', 1 => 'b']], 0, 'array(3) {string(1) "a", string(1) "b", array(2) {string(1) "a", string(1) "b"}}'],

            [null, 1, '$arg1 = NULL'],
            [1, 1, '$arg1 = int(1)'],
            [1.0, 1, '$arg1 = float(1)'],
            [1.01, 1, '$arg1 = float(1.01)'],
            [false, 1, '$arg1 = bool(false)'],
            [true, 1, '$arg1 = bool(true)'],
            ['', 1, '$arg1 = string(0)'],
            ['abc', 1, '$arg1 = string(3) "abc"'],
            ['abcd', 1, '$arg1 = string(4) "abcd"'],
            ['abcdefghij0123456789abcdefghij0123456789abc', 1, '$arg1 = string(43) "abcdefghij0123456789abcdefghij0123456789abc"'],
            ['abcdefghij0123456789abcdefghij0123456789abcd', 1, '$arg1 = string(44) "abcdefghij0123456789abcdefghij01...6789abcd"'],
            ['abcdefghij0123456789abcdefghij0123456789abcdefghij', 1, '$arg1 = string(50) "abcdefghij0123456789abcdefghij01...cdefghij"'],
            [[], 1, '$arg1 = array(0)'],
            [['a'], 1, '$arg1 = array(1) {string(1) "a"}'],
            [['a', 'b', 'c'], 1, '$arg1 = array(3) {string(1) "a", string(1) "b", string(1) "c"}'],
            [['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j'], 1, '$arg1 = array(10) {string(1) "a", string(1) "b", string(1) "c", string(1) "d", string(1) "e", string(1) "f", string(1) "g", string(1) "h", string(1) "i", string(1) "j"}'],
            [['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k'], 1, '$arg1 = array(11) {string(1) "a", string(1) "b", string(1) "c", string(1) "d", string(1) "e", string(1) "f", string(1) "g", string(1) "h", string(1) "i", string(1) "j", ...}'],
            [['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n'], 1, '$arg1 = array(14) {string(1) "a", string(1) "b", string(1) "c", string(1) "d", string(1) "e", string(1) "f", string(1) "g", string(1) "h", string(1) "i", string(1) "j", ...}'],
            [[0 => 'a', 1 => 'b', 2 => 'c'], 1, '$arg1 = array(3) {string(1) "a", string(1) "b", string(1) "c"}'],
            [[0 => 'a', 'i' => 'b', 2 => 'c'], 1, '$arg1 = array(3) {0 => string(1) "a", i => string(1) "b", 2 => string(1) "c"}'],
            [new TestObject1(), 1, '$arg1 = object(1) "mrcore\debug\TestObject1"'],
            [new TestObject2(), 1, '$arg1 = object(2) "mrcore\debug\TestObject2"'],
            [[0 => 'a', 1 => 'b', 2 => [0 => 'a', 1 => 'b']], 1, '$arg1 = array(3) {string(1) "a", string(1) "b", array(2) {string(1) "a", string(1) "b"}}'],
        ];
    }

    ##################################################################################

    public function testHiddenDataIfEmptyData(): void
    {
        $this->assertEmpty(Tools::getHiddenData([], ['word1', 'word2', 'word3']));
    }

    public function testHiddenDataIfEmptyWords(): void
    {
        $data = ['field1' => 'value1', 'field2' => 'value2'];
        $this->assertEquals($data, Tools::getHiddenData($data, []));
    }

    /**
     * @dataProvider listOfHiddenDataProvider
     */
    public function testHiddenData(array $data, array $words, array $expected): void
    {
        $this->assertEquals($expected, Tools::getHiddenData($data, $words));
    }

    public function listOfHiddenDataProvider(): array
    {
        return [
            [[1, 2, 3], ['word1', 'word2', 'word3'], [1, 2, 3]],
            [['field1' => 1, 'field2' => 2, 'field3' => 3], ['word1', 'word2', 'word3'], ['field1' => 1, 'field2' => 2, 'field3' => 3]],
            [['field1' => 'value1', 'field2' => 'value2', 'field3' => 'value3'], ['word1', 'word2', 'word3'], ['field1' => 'value1', 'field2' => 'value2', 'field3' => 'value3']],
            [['word2field1' => 'value1', 'fieword2ld2' => 'value2', 'field3word2' => 'value3'], ['word1', 'word2', 'word3'], ['word2field1' => '***hidden***', 'fieword2ld2' => '***hidden***', 'field3word2' => '***hidden***']],
        ];
    }

}