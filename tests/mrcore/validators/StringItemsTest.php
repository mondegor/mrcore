<?php declare(strict_types=1);
namespace mrcore\validators;
use PHPUnit\Framework\TestCase;
use mrcore\validators\testdata\ConcreteStringItems;

require_once 'mrcore/validators/StringItems.php';

class StringItemsTest extends TestCase
{
    /**
     * @dataProvider listOfProtectedValidateValuesProvider
     */
    public function testProtectedValidate(string $value, bool $isMulty): void
    {
        $separator = ',';
        $data = ['value' => $value];
        $listErrors = [];
        $itemCount = substr_count($value, $separator) + 1;

        $validator = $this->createPartialMock(ConcreteStringItems::class, ['_getAttr', '_validateItem']);
        $validator->expects($this->exactly(2))->method('_getAttr')->will($this->onConsecutiveCalls($isMulty, $separator));
        $validator->expects($this->exactly($itemCount))->method('_validateItem')->willReturn(true);

        $this->assertTrue($validator->testValidate($data, $listErrors));
    }

    public function listOfProtectedValidateValuesProvider(): array
    {
        return [
            ['item', false],
            ['item', true],
            ['item, item2', true],
        ];
    }

    ##################################################################################

    public function testProtectedValidateIfValueEmpty(): void
    {
        $data = ['value' => ''];
        $listErrors = [];

        $validator = $this->createPartialMock(ConcreteStringItems::class, []);
        $this->assertTrue($validator->testValidate($data, $listErrors));
    }

    ##################################################################################

    /**
     * @dataProvider listOfProtectedValidateIfNotValidValuesProvider
     */
    public function testProtectedValidateIfNotValid(string $value, bool $isMulty, int $errorCode): void
    {
        $separator = ',';
        $data = ['value' => $value];
        $listErrors = [];

        $validator = $this->createPartialMock(ConcreteStringItems::class, ['_getAttr', '_validateItem', 'addErrorByCode']);
        $validator->expects($this->exactly(2))->method('_getAttr')->will($this->onConsecutiveCalls($isMulty, $separator));
        $validator->method('_validateItem')->willReturn(false);
        $validator->expects($this->once())->method('addErrorByCode')->with($this->equalTo($errorCode));

        $this->assertFalse($validator->testValidate($data, $listErrors));
    }

    public function listOfProtectedValidateIfNotValidValuesProvider(): array
    {
        return [
            ['item', false, StringItems::INVALID_VALUE],
            ['item', true, StringItems::INVALID_VALUES],
            ['item, item2', true, StringItems::INVALID_VALUES],
        ];
    }

}