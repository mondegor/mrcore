<?php declare(strict_types=1);
namespace mrcore\validators;
use PHPUnit\Framework\TestCase;
use mrcore\validators\testdata\ConcreteEmail;

require_once 'mrcore/validators/Email.php';

class EmailTest extends TestCase
{
    /**
     * @dataProvider listOfProtectedValidateValuesProvider
     */
    public function testProtectedValidate(string $email, bool $expected): void
    {
        $validator = $this->createPartialMock(ConcreteEmail::class, []);

        $this->assertSame($expected, $validator->testValidateItem($email));
    }

    public function listOfProtectedValidateValuesProvider(): array
    {
        return [
            ['test@mail.org', true],
            ['test1@mail.org', true],
            ['test-mail@qw.org', true],
            ['test-mail.org', false],
            ['te st@mail.org', false],
        ];
    }

}