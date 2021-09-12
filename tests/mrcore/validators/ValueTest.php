<?php declare(strict_types=1);
namespace mrcore\validators;
use PHPUnit\Framework\TestCase;
use mrcore\validators\testdata\ConcreteValue;

require_once 'mrcore/validators/Value.php';

class ValueTest extends TestCase
{
    /**
     * @dataProvider listOfProtectedValidateValuesProvider
     */
    public function testProtectedValidate(string $pattern, string $value): void
    {
        $data = ['value' => $value];
        $listErrors = [];

        $validator = $this->createPartialMock(ConcreteValue::class, ['_getAttr']);
        $validator->expects($this->once())->method('_getAttr')->willReturn($pattern);

        $this->assertTrue($validator->testValidate($data, $listErrors));
    }

    public function listOfProtectedValidateValuesProvider(): array
    {
        return [
            [Value::PATTER_LOGIN, 'a-a'],
            [Value::PATTER_LOGIN, 'A.A'],
            [Value::PATTER_LOGIN, '010'],
            [Value::PATTER_LOGIN, '1a1'],
            [Value::PATTER_LOGIN, '2A2'],
            [Value::PATTER_LOGIN, 'aabc-0.A'],

            // [Value::PATTER_PASSWORD, 'a'],
            // [Value::PATTER_PASSWORD, 'azAZ09"\'!#$%&()*+,-./:;<=>?@[\\]^_`{|}~'],

            [Value::PATTERN_NAME, 'a'],
            [Value::PATTERN_NAME, 'az \',-. 09 аЯёЁ'],

            [Value::PATTERN_ENGLISH_NAME, 'aa'],
            [Value::PATTERN_ENGLISH_NAME, 'zz'],
            [Value::PATTERN_ENGLISH_NAME, 'AA'],
            [Value::PATTERN_ENGLISH_NAME, 'ZZ'],
            [Value::PATTERN_ENGLISH_NAME, 'aazAZ09 \',-.a'],
            [Value::PATTERN_ENGLISH_NAME, 'zazAZ09 \',-.z'],
            [Value::PATTERN_ENGLISH_NAME, 'AazAZ09 \',-.A'],
            [Value::PATTERN_ENGLISH_NAME, 'ZazAZ09 \',-.Z'],

            [Value::PATTERN_ENGLISH_TEXT, 'azAZ09 \t\n\r"\'!#$%&()*+,-./:;<=>?@[\\]^_`{|}~'],

            [Value::PATTERN_PHONE, '+(0) 123-11'],
            [Value::PATTERN_PHONE, '+7 (123) 456-78-90'],

            [Value::PATTERN_PHONE_EXTEND, '+(0) 123-11'],
            [Value::PATTERN_PHONE_EXTEND, '+7 (123) 456-78-90'],

            [Value::PATTERN_NOT_SPECIALS, 'az 09 аЯёЁ'],
        ];
    }

    ##################################################################################

    public function testProtectedValidateIfValueEmpty(): void
    {
        $data = ['value' => ''];
        $listErrors = [];

        $validator = $this->createPartialMock(ConcreteValue::class, []);
        $this->assertTrue($validator->testValidate($data, $listErrors));
    }

    ##################################################################################

    /**
     * @dataProvider listOfProtectedValidateIfNotValidValuesProvider
     */
    public function testProtectedValidateIfNotValid(string $pattern, string $value): void
    {
        $data = ['value' => $value];
        $listErrors = [];

        $validator = $this->createPartialMock(ConcreteValue::class, ['_getAttr', 'addErrorByCode']);
        $validator->expects($this->once())->method('_getAttr')->willReturn($pattern);
        $validator->expects($this->once())->method('addErrorByCode')->with($this->equalTo(ConcreteValue::INVALID_VALUE));

        $this->assertFalse($validator->testValidate($data, $listErrors));
    }

    public function listOfProtectedValidateIfNotValidValuesProvider(): array
    {
        return [
            [Value::PATTER_LOGIN, '.'],
            // [Value::PATTER_PASSWORD, ' '],
            [Value::PATTERN_NAME, '!'],
            [Value::PATTERN_ENGLISH_NAME, '!'],
            [Value::PATTERN_ENGLISH_TEXT, 'й'],
            [Value::PATTERN_ENGLISH_TEXT, 'Ё'],
            [Value::PATTERN_PHONE, 'a'],
            [Value::PATTERN_PHONE, '!'],
            [Value::PATTERN_PHONE_EXTEND, 'a'],
            [Value::PATTERN_PHONE_EXTEND, '!'],
            [Value::PATTERN_NOT_SPECIALS, '!'],
        ];
    }

}