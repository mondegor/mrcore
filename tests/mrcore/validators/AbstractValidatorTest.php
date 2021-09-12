<?php declare(strict_types=1);
namespace mrcore\validators;
use mrcore\testing\Helper;
use PHPUnit\Framework\TestCase;
use mrcore\validators\testdata\ConcreteValidator;

require_once 'mrcore/validators/AbstractValidator.php';

class AbstractValidatorTest extends TestCase
{

    public function testConstructor(): void
    {
        $attrs = ['attr1' => 'value2', 'id' => 'id1'];
        $errors = [1000 => 'error2'];

        $validator = new ConcreteValidator($attrs, $errors);

        $this->assertSame($attrs, Helper::getProperty($validator, '_attrs'));
        $this->assertSame($errors, Helper::getProperty($validator, '_errors'));
    }

    ##################################################################################

    public function testInvokeValidator(): void
    {
        $validator = $this->createPartialMock(ConcreteValidator::class, ['validate']);
        $validator->expects($this->once())->method('validate')->willReturn(true);

        $this->assertTrue($validator('test'));
    }

    ##################################################################################

    /**
     * @dataProvider listOfValidateValuesProvider
     */
    public function testValidate($value, array $expected): void
    {
        $validator = $this->createPartialMock(ConcreteValidator::class, ['_validate']);
        $validator->expects($this->once())->method('_validate')
                                          ->with($this->equalTo($expected),
                                                 $this->equalTo([]))->willReturn(true);

        $this->assertTrue($validator->validate($value));
    }

    public function listOfValidateValuesProvider(): array
    {
        return [
            [1, ['value' => 1]],
            ['value1', ['value' => 'value1']],
            [['param1' => 'value1'], ['param1' => 'value1', 'value' => '']],
            [['param1' => 3, 'value' => 4], ['param1' => 3, 'value' => 4]],
        ];
    }

    ##################################################################################

    public function testAddErrorByCodeIfErrorCodeExitsAndErrorMessageNotEmpty(): void
    {
        $componentId = 'id';
        $errorMessage = 'Error message';
        $data = [];
        $listErrors = [];

        $validator = $this->createPartialMock(ConcreteValidator::class, ['_getErrorMessage', '_getAttr']);
        $validator->expects($this->once())->method('_getErrorMessage')
                                          ->with($this->equalTo($errorMessage), $this->equalTo($data))
                                          ->willReturn($errorMessage);
        $validator->expects($this->once())->method('_getAttr')->willReturn($componentId);
        Helper::setProperty($validator, '_errors', [AbstractValidator::INVALID_VALUE => $errorMessage]);

        $validator->addErrorByCode(AbstractValidator::INVALID_VALUE, $data, $listErrors);

        $this->assertSame([[$componentId, $errorMessage]], $listErrors);
    }

    ##################################################################################

    public function testAddErrorByCodeIfErrorCodeExitsAndErrorMessageEmpty(): void
    {
        $errorMessage = '';
        $data = [];
        $listErrors = [];

        $validator = $this->createPartialMock(ConcreteValidator::class, ['_getErrorMessage', '_getAttr']);
        $validator->method('_getErrorMessage')->willReturn($errorMessage);
        $validator->expects($this->never())->method('_getAttr');

        Helper::setProperty($validator, '_errors', [AbstractValidator::INVALID_VALUE => $errorMessage]);

        $validator->addErrorByCode(AbstractValidator::INVALID_VALUE, $data, $listErrors);

        $this->assertEmpty($listErrors);
    }

    ##################################################################################

    public function testAddErrorByCodeIfErrorCodeNotExits(): void
    {
        $componentId = 'id';
        $data = [];
        $listErrors = [];

        $validator = $this->createPartialMock(ConcreteValidator::class, ['_getAttr']);
        $validator->method('_getAttr')->willReturn($componentId);

        $validator->addErrorByCode(AbstractValidator::INVALID_VALUE, $data, $listErrors);

        $this->assertSame([[$componentId, sprintf('Code error: 0002 [validator: %s]', get_class($validator))]], $listErrors);
    }

    ##################################################################################

    /**
     * @dataProvider listOfProtectedGetAttrDataProvider
     */
    public function testProtectedGetAttr(string $name, array $data, array $attrs, $expected): void
    {
        $validator = $this->createPartialMock(ConcreteValidator::class, []);
        Helper::setProperty($validator, '_attrs', $attrs);

        $this->assertSame($expected, $validator->testGetAttr($name, $data));
    }

    public function listOfProtectedGetAttrDataProvider(): array
    {
        return [
            ['name1', [], ['name1' => 'value1'], 'value1'],
            ['name1', ['name1' => 'value1'], [], 'value1'],
            ['name1', ['name1' => 'value2'], ['name1' => 'value1'], 'value2'],
        ];
    }

    ##################################################################################

    public function testProtectedMakeArgForMessage(): void
    {
        $name = '';
        $data = [];

        $validator = $this->createPartialMock(ConcreteValidator::class, []);
        $this->assertFalse($validator->testMakeArgForMessage($name, $data));
    }

    ##################################################################################

    public function testProtectedGetErrorMessageIfErrorAsString(): void
    {
        $error = 'error1';
        $data = [];

        $validator = $this->createPartialMock(ConcreteValidator::class, []);
        $this->assertSame($error, $validator->testGetErrorMessage($error, $data));
    }

    ##################################################################################

    public function testProtectedGetErrorMessageIfErrorAsArray(): void
    {
        $error = __targs('Error "%s" invalid values %s', 'caption', 'values');
        $data = ['caption' => 'Caption1', 'values' => 'Many Values'];
        $expected = 'Error "Caption1" invalid values Many Values';

        $validator = $this->createPartialMock(ConcreteValidator::class, []);
        $this->assertSame($expected, $validator->testGetErrorMessage($error, $data));
    }

    ##################################################################################

    public function testProtectedGetErrorMessageIfErrorAsArrayAndDataEmpty(): void
    {
        $error = __targs('Error "%s" invalid values %s', 'caption', 'values');
        $data = [];
        $expected = 'Error "{caption}" invalid values {values}';

        $validator = $this->createPartialMock(ConcreteValidator::class, []);
        $this->assertSame($expected, $validator->testGetErrorMessage($error, $data));
    }

    ##################################################################################

    public function testFactoryValidator(): void
    {
        $validator = &AbstractValidator::factory(ConcreteValidator::class);
        $this->assertInstanceOf(ConcreteValidator::class, $validator);
    }

}