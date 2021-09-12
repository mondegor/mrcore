<?php declare(strict_types=1);
namespace mrcore\validators;
use PHPUnit\Framework\TestCase;
use mrcore\lib\Crypt;
use mrcore\validators\testdata\ConcretePassword;

require_once 'mrcore/lib/Crypt.php';
require_once 'mrcore/validators/Password.php';

class PasswordTest extends TestCase
{
    /**
     * @dataProvider listOfProtectedValidateValuesProvider
     */
    public function testProtectedValidate(string $pattern, string $value): void
    {
        $data = ['value' => $value];
        $listErrors = [];

        $validator = $this->createPartialMock(ConcretePassword::class, ['_getAttr', '_getPasswordStrength']);
        $validator->expects($this->once())->method('_getAttr')->willReturn($pattern);
        $validator->method('_getPasswordStrength')->willReturn(Crypt::STRENGTH_BEST);

        $this->assertTrue($validator->testValidate($data, $listErrors));
    }

    public function listOfProtectedValidateValuesProvider(): array
    {
        return [
            [Password::PATTERN_PASSWORD, 'a'],
            [Password::PATTERN_PASSWORD, 'azAZ09!#$%&()*+,-.:;=@^_`~'],

            [Password::PATTERN_PASSWORD_az_AZ_09, 'a'],
            [Password::PATTERN_PASSWORD_az_AZ_09, 'azAZ09'],
        ];
    }

    ##################################################################################

    public function testProtectedValidateIfValueEmpty(): void
    {
        $data = ['value' => ''];
        $listErrors = [];

        $validator = $this->createPartialMock(ConcretePassword::class, []);
        $this->assertTrue($validator->testValidate($data, $listErrors));
    }

    ##################################################################################

    /**
     * @dataProvider listOfProtectedValidateIfNotValidValuesProvider
     */
    public function testProtectedValidateIfNotValid(string $pattern, int $errorCode, string $value): void
    {
        $data = ['value' => $value];
        $listErrors = [];

        $validator = $this->createPartialMock(ConcretePassword::class, ['_getAttr', '_getPasswordStrength', 'addErrorByCode']);
        $validator->expects($this->once())->method('_getAttr')->willReturn($pattern);
        $validator->method('_getPasswordStrength')->willReturn(Crypt::STRENGTH_NOT_RATED);
        $validator->expects($this->once())->method('addErrorByCode')->with($this->equalTo($errorCode));

        $this->assertFalse($validator->testValidate($data, $listErrors));
    }

    public function listOfProtectedValidateIfNotValidValuesProvider(): array
    {
        return [
            [Password::PATTERN_PASSWORD, Password::INVALID_SPECIAL, 'a'],
            [Password::PATTERN_PASSWORD, Password::INVALID_VALUE, '['],
            [Password::PATTERN_PASSWORD_az_AZ_09, Password::INVALID_SPECIAL, 'a'],
            [Password::PATTERN_PASSWORD_az_AZ_09, Password::INVALID_VALUE, '['],
        ];
    }

}