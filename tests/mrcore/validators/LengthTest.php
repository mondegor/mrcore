<?php declare(strict_types=1);
namespace mrcore\validators;
use PHPUnit\Framework\TestCase;
use mrcore\testing\Helper;
use mrcore\validators\testdata\ConcreteLength;

require_once 'mrcore/validators/Length.php';

class LengthTest extends TestCase
{
    /**
     * @dataProvider listOfProtectedValidateValuesProvider
     */
    public function testProtectedValidate(string $value, int $minLength, int $maxLength): void
    {
        $data = ['value' => $value];
        $listErrors = [];

        $validator = $this->createPartialMock(ConcreteLength::class, ['_getAttr', 'addErrorByCode']);
        Helper::mergeProperty($validator, '_attrs', ['showFullError' => true]);
        $validator->expects($this->exactly(2))->method('_getAttr')->will($this->onConsecutiveCalls($minLength, $maxLength));

        $this->assertTrue($validator->testValidate($data, $listErrors));
    }

    public function listOfProtectedValidateValuesProvider(): array
    {
        return [
            ['abc', 0, 10],
            ['abcde12345', 0, 10],
            ['abcde12345', 10, 10],
            ['abcde12345', 10, 0],
            ['abcde123456', 10, 0],
        ];
    }

    ##################################################################################

    /**
     * @dataProvider listOfProtectedValidateValuesIfValueEmptyProvider
     */
    public function testProtectedValidateIfValueEmpty(int $minLength, int $maxLength): void
    {
        $data = ['value' => ''];
        $listErrors = [];

        $validator = $this->createPartialMock(ConcreteLength::class, ['_getAttr', 'addErrorByCode']);
        $this->assertTrue($validator->testValidate($data, $listErrors));
    }

    public function listOfProtectedValidateValuesIfValueEmptyProvider(): array
    {
        return [
            [0, 0],
            [0, 10],
            [10, 10],
        ];
    }

    ##################################################################################

    /**
     * @dataProvider listOfProtectedValidateValuesIfErrorProvider
     */
    public function testProtectedValidateIfError(string $value, int $minLength, int $maxLength, int $errorCode): void
    {
        $data = ['value' => $value];
        $listErrors = [];

        $validator = $this->createPartialMock(ConcreteLength::class, ['_getAttr', 'addErrorByCode']);
        Helper::mergeProperty($validator, '_attrs', ['showFullError' => true]);

        $validator->expects($this->exactly(2))->method('_getAttr')->will($this->onConsecutiveCalls($minLength, $maxLength));
        $validator->expects($this->once())->method('addErrorByCode')->with($this->equalTo($errorCode));

        $this->assertFalse($validator->testValidate($data, $listErrors));
    }

    public function listOfProtectedValidateValuesIfErrorProvider(): array
    {
        return [
            ['abc', 10, 10, Length::INVALID_LENGTH],
            ['abc', 10, 0, Length::INVALID_LENGTH_MIN],
            ['abc', 10, 11, Length::INVALID_LENGTH],
            ['abcde123456', 0, 10, Length::INVALID_LENGTH_MAX],
            ['abcde123456', 5, 10, Length::INVALID_LENGTH],
        ];
    }

}