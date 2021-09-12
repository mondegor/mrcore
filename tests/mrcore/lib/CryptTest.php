<?php declare(strict_types=1);
namespace mrcore\lib;
use PHPUnit\Framework\TestCase;
use mrcore\exceptions\UnitTestException;

require_once 'mrcore/lib/Crypt.php';

class CryptTest extends TestCase
{

    public function testGetHash(): void
    {
        $hash = Crypt::getHash('test');
        $hashWithSalt = Crypt::getHash('test', 'salt');

        $this->assertSame(64, strlen($hash));
        $this->assertNotSame($hash, $hashWithSalt);
    }

    ##################################################################################

    public function testGenerateHash(): void
    {
        $hash = Crypt::generateHash();
        $hashWithSalt = Crypt::generateHash('salt');

        $this->assertSame(64, strlen($hash));
        $this->assertNotSame($hash, $hashWithSalt);
    }

    ##################################################################################

    public function testGenerateFileNameWithExt(): void
    {
        $fileName = Crypt::generateFileName('filedata.txtfile');

        $this->assertSame(40 + 8, strlen($fileName)); // 8 - lengh of file ext
        $this->assertStringContainsString('.txtfile', $fileName);
    }

    ##################################################################################

    public function testGenerateFileNameWithEmptyExt(): void
    {
        $fileName = Crypt::generateFileName('filedatatxt');

        $this->assertSame(40 + 4, strlen($fileName)); // 4 - lengh of .tmp
        $this->assertStringContainsString('.tmp', $fileName);
    }

    ##################################################################################

    /**
     * @dataProvider listOfPasswordLengthsProvider
     */
    public function testGeneratePasswordLenght(int $length, int $expected): void
    {
        $password = Crypt::generatePassword($length);

        $this->assertSame($expected, strlen($password));
    }

    public function listOfPasswordLengthsProvider(): array
    {
        return [
            [1, 1],
            [5, 5],
            [10, 10],
            [32, 32],
            [33, 32],
            [64, 32],
        ];
    }

    ##################################################################################

    /**
     * @dataProvider listOfPasswordSymbolSetProvider
     */
    public function testGeneratePassword(int $charType, string $expectedCharSet): void
    {
        $password = Crypt::generatePassword(32, $charType);
        $badChar = '';

        for ($i = 0; $i < 32; $i++)
        {
            if (false === strpos($expectedCharSet, $password[$i]))
            {
                $badChar = $password[$i];
                break;
            }
        }

        $this->assertEmpty($badChar, sprintf('The password %s contains an invalid character %s', $password, $badChar));
    }

    public function listOfPasswordSymbolSetProvider(): array
    {
        $vowels = 'aeiuyAEIUY';
        $consonants = 'bcdfghjklmnpqrstvwxzBCDFGHJKLMNPQRSTVWXZ';
        $numerals = '123456789';
        $signs = '.!?@#&';

        return [
            [Crypt::PW_VOWELS, $vowels . $signs],
            [Crypt::PW_CONSONANTS, $consonants],
            [Crypt::PW_NUMERALS, $numerals],
            [Crypt::PW_SIGNS, $signs],
            [Crypt::PW_ABC, $vowels . $consonants],
            [Crypt::PW_ABC_NUMERALS, $vowels . $consonants . $numerals],
            [Crypt::PW_ALL, $vowels . $consonants . $numerals . $signs],
        ];
    }

    ##################################################################################

    /**
     * @dataProvider listOfGetPasswordStrengthValuesProvider
     */
    public function testGetPasswordStrength(string $password, int $expected): void
    {
        $this->assertSame($expected, Crypt::getPasswordStrength($password));
    }

    public function listOfGetPasswordStrengthValuesProvider(): array
    {
        return [
            ['', Crypt::STRENGTH_NOT_RATED],
            ['a', Crypt::STRENGTH_NOT_RATED],

            ['abcdefghI2!', Crypt::STRENGTH_BEST],
            ['abcdefghijK2', Crypt::STRENGTH_BEST],
            ['abcdefghijkLm', Crypt::STRENGTH_BEST],

            ['abcdefG2!', Crypt::STRENGTH_STRONG],
            ['abcdefghE2', Crypt::STRENGTH_STRONG],
            ['abcdefghiJk', Crypt::STRENGTH_STRONG],

            ['abcdE2!', Crypt::STRENGTH_MEDIUM],
            ['abcdefE2', Crypt::STRENGTH_MEDIUM],
            ['abcdefgHi', Crypt::STRENGTH_MEDIUM],

            ['abC2!', Crypt::STRENGTH_WEAK],
            ['abcdE2', Crypt::STRENGTH_WEAK],
            ['abcdeFg', Crypt::STRENGTH_WEAK],
        ];
    }

    ##################################################################################

    /**
     * @dataProvider listOfNumbersProvider
     */
    public function testDec2N(int $number, int $N, string $expected): void
    {
        $this->assertSame($expected, Crypt::dec2N($number, $N));
    }

    public function listOfNumbersProvider(): array
    {
        return [
            [1, 1, ''],
            [1, 63, ''],
            [1, 2, '1'],
            [1, 10, '1'],
            [1, 62, '1'],
            [16, 2, '10000'],
            [16, 10, '16'],
            [16, 62, 'g'],
            [32, 62, 'w'],
            [61, 62, 'Z'],
            [62, 62, '10'],
            [1000000, 62, '4c92'], // 4 * 62**3 + 12 * 62**2 + 9 * 62**1 + 2 * 62**0
        ];
    }

    ##################################################################################

    public function testEncryptXOR(): void
    {
        $encryptedString = Crypt::encryptXOR('secure-string', 'key1', 'salt1');
        $encryptedStringOtherSalt = Crypt::encryptXOR('secure-string', 'key1', 'salt2');

        $this->assertSame('LiE7ctDj8UvFUVQIbA==', $encryptedString);
        $this->assertNotSame($encryptedStringOtherSalt, $encryptedString);
    }

    ##################################################################################

    public function testDecryptXOR(): void
    {
        $decryptedString = Crypt::decryptXOR('LiE7ctDj8UvFUVQIbA==', 'key1', 'salt1');
        $decryptedStringOtherKey = Crypt::decryptXOR('LiE7ctDj8UvFUVQIbA==', 'key2', 'salt1');
        $decryptedStringOtherSalt = Crypt::decryptXOR('LiE7ctDj8UvFUVQIbA==', 'key1', 'salt2');

        $this->assertSame('secure-string', $decryptedString);
        $this->assertNotSame($decryptedStringOtherKey, $decryptedString);
        $this->assertNotSame($decryptedStringOtherSalt, $decryptedString);
    }

    ##################################################################################

    public function testGetKeywordWithIncludeFile(): void
    {
        try
        {
            Crypt::getKeyword($_ENV['MRCORE_DIR_BASE'] . 'mrcore/tests/mrcore/lib/testdata/getKeywordData.php', time());
        }
        catch (UnitTestException $e)
        {
            $args = $e->getArgs();

            $this->assertSame('keywordValue', $args['keyword']);
            $this->assertSame(1234567890, $args['time']);
        }
    }

    ##################################################################################

    /**
     * @dataProvider listOfSecureStringsProvider
     */
    public function testEncryptXORAndDecryptXOR(string $secureString): void
    {
        $encryptedString = Crypt::encryptXOR($secureString, 'key1', 'salt1');
        $decryptedString = Crypt::decryptXOR($encryptedString, 'key1', 'salt1');

        $this->assertSame($secureString, $decryptedString);

        ##################################################################################

        $encryptedString = Crypt::encryptXOR($secureString, $secureString, 'salt1');
        $decryptedString = Crypt::decryptXOR($encryptedString, $secureString, 'salt1');

        $this->assertSame($secureString, $decryptedString);

        ##################################################################################

        $encryptedString = Crypt::encryptXOR($secureString, $secureString, $secureString);
        $decryptedString = Crypt::decryptXOR($encryptedString, $secureString, $secureString);

        $this->assertSame($secureString, $decryptedString);

        ##################################################################################

        $encryptedString = Crypt::encryptXOR('short-string', $secureString, $secureString);
        $decryptedString = Crypt::decryptXOR($encryptedString, $secureString, $secureString);

        $this->assertSame('short-string', $decryptedString);
    }

    public function listOfSecureStringsProvider(): array
    {
        return [
            ['1'],
            ['123'],
            ['abc'],
            ['abc-abc-abc-abc-abc-abc-abc-ab32'],
            ['abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-ab64'],
            ['abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-a128'],
            ['abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-1024' .
                'abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-1024' .
                'abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-1024' .
                'abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-1024' .
                'abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-1024' .
                'abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-1024' .
                'abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-1024' .
                'abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-abc-1024']
        ];
    }

}