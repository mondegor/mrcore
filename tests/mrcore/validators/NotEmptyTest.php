<?php declare(strict_types=1);
namespace mrcore\validators;
use PHPUnit\Framework\TestCase;
use mrcore\validators\testdata\ConcreteNotEmpty;

require_once 'mrcore/validators/NotEmpty.php';

class NotEmptyTest extends TestCase
{
    /**
     * @dataProvider listOfProtectedValidateValuesProvider
     */
    public function testProtectedValidate($value): void
    {
        $data = ['value' => $value];
        $listErrors = [];

        $validator = $this->createPartialMock(ConcreteNotEmpty::class, ['_getAttr']);
        $validator->expects($this->once())->method('_getAttr')->willReturn(null);

        $this->assertTrue($validator->testValidate($data, $listErrors));
    }

    public function listOfProtectedValidateValuesProvider(): array
    {
        return [
            [false],
            [true],
            [-1],
            [0],
            [1],
            [0.0],
            [1.2],
            ['0'],
            ['0.0'],
            ['value1']
        ];
    }

    ##################################################################################

    /**
     * @dataProvider listOfProtectedValidateIfEmptyValuesProvider
     */
    public function testProtectedValidateIfEmpty($value): void
    {
        $data = ['value' => $value];
        $listErrors = [];

        $validator = $this->createPartialMock(ConcreteNotEmpty::class, ['_getAttr', 'addErrorByCode']);
        $validator->expects($this->never())->method('_getAttr');
        $validator->expects($this->once())->method('addErrorByCode')->with($this->equalTo(NotEmpty::EMPTY_VALUE));

        $this->assertFalse($validator->testValidate($data, $listErrors));
    }

    public function listOfProtectedValidateIfEmptyValuesProvider(): array
    {
        return [
            [''],
            [[]],
            [null],
        ];
    }

    ##################################################################################

    /**
     * @dataProvider listOfProtectedValidateValuesProvider
     */
    public function testProtectedValidatIfSetEmptyValue($value): void
    {
        $data = ['value' => $value];
        $listErrors = [];

        $validator = $this->createPartialMock(ConcreteNotEmpty::class, ['_getAttr', 'addErrorByCode']);
        $validator->expects($this->once())->method('_getAttr')->willReturn($value);
        $validator->expects($this->once())->method('addErrorByCode')->with($this->equalTo(NotEmpty::EMPTY_VALUE));

        $this->assertFalse($validator->testValidate($data, $listErrors));
    }

}