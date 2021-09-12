<?php declare(strict_types=1);
namespace mrcore\lib;
use Exception;
use mrcore\exceptions\UnitTestException;

/**
 * Библиотека объединяющая методы генерации
 * строковых последовательностей и шифрования данных.
 *
 * @author     Andrey J. Nazarov <mondegor@gmail.com>
 * @package    mrcore/lib
 */
/*__class_static__*/ class Crypt
{
    /**
     * Символы участвующие в генерации пароля.
     */
    public const PW_VOWELS = 1,
                 PW_CONSONANTS = 2,
                 PW_NUMERALS = 4,
                 PW_SIGNS = 8,
                 PW_ABC = 3, // PW_VOWELS + PW_CONSONANTS
                 PW_ABC_NUMERALS = 7, // PW_VOWELS + PW_CONSONANTS + PW_NUMERALS
                 PW_ALL = 15; // PW_VOWELS + PW_CONSONANTS + PW_NUMERALS + PW_SIGNS

    // sub arrays: [0 = successively max; 1 = first or last; 2 = letters len; 3 = letters]
    private const _PW_CHAR_SET = array
    (
        // vowels
        self::PW_VOWELS => [2, true, 10, 'aeiuyAEIUY'], // oO - символы удалены, чтобы не перепутать с нулём
        // consonants
        self::PW_CONSONANTS => [2, true, 40, 'bcdfghjklmnpqrstvwxzBCDFGHJKLMNPQRSTVWXZ'],
        // numerals
        self::PW_NUMERALS => [1, false, 9,  '123456789'], // 0 - символ удалён, чтобы не перепутать с oO
        // signs
        self::PW_SIGNS => [1, false, 6,  '.!?@#&'],
    );

    /**
     * Уровни прочности паролей.
     */
    public const STRENGTH_NOT_RATED = 0, // без оценки
                 STRENGTH_WEAK      = 1, // простой
                 STRENGTH_MEDIUM    = 2, // средний
                 STRENGTH_STRONG    = 3, // сильный
                 STRENGTH_BEST      = 4; // лучший

    #################################### Methods #####################################

    /**
     * Возвращение хэша указанной строки. Длина хеша равна 64 символа.
     *
     * @param      string  $string
     * @param      string  $salt OPTIONAL
     * @return     string
     */
    public static function getHash(string $string, string $salt = ''): string
    {
        return hash('sha256', $string . '|' . $salt);
    }

    /**
     * Генерация случайного хэша. Длина хеша равна 64 символа.
     *
     * @param string $salt OPTIONAL
     * @return     string
     * @throws     Exception
     */
    public static function generateHash(string $salt = ''): string
    {
        return hash('sha256', uniqid(getmypid() . '|' . random_int(1, PHP_INT_MAX), true) . '|' . time() . '|' . $salt);
    }

    /**
     * Генерация уникального имени для файла.
     *
     * @param string $fileName
     * @return     string
     * @throws     Exception
     */
    public static function generateFileName(string $fileName): string
    {
        if ('' === ($ext = mb_strtolower(pathinfo(basename($fileName), PATHINFO_EXTENSION))))
        {
            $ext = 'tmp';
        }

        return sprintf('%08x%016x%016x.%s', time(), random_int(1, PHP_INT_MAX), random_int(1, PHP_INT_MAX), $ext);
    }

    /**
     * Generates the human-friendly password.
     * Максимальная длина должна быть не больше 32 символов.
     *
     * @param      int   $length
     * @param      int   $charType OPTIONAL
     * @return     string
     * @throws     Exception
     */
    public static function generatePassword(int $length, int $charType = self::PW_ALL): string
    {
        if ($length > 32) { $length = 32; }

        $abc = [];
        $abc_count = 0;

        foreach (self::_PW_CHAR_SET as $type => $params)
        {
            if ($charType & $type)
            {
                $abc[$abc_count++] = $params;
            }
        }

        // если указан только один список символов
        if (1 === $abc_count)
        {
            $abc[0][0] = $length; // максимальная длина совпадает с длиной пароля
            $abc[0][1] = true; // первый и последний символ не проверяется
        }

        ##################################################################################

        $result = '';
        $cur = [0, 0]; // array: [0 = cur index in $abc; 1 = count successively signs]

        for ($i = 0, $abc_c = $abc_count - 1; $i < $length; $i++)
        {
            do
            {
                $abc_i = random_int(0, $abc_c);

                // если генерация разрешена в любом месте или
                // если знак не первый и не последний
                if ((true === $abc[$abc_i][1]) || (0 !== $i && $i !== ($length - 1)))
                {
                    // если предыдущий знак такого же типа
                    if ($abc_i === $cur[0])
                    {
                        // если подряд идущих знаков не превышает
                        if ($abc[$abc_i][0] > $cur[1])
                        {
                            $cur[1]++;
                            break;
                        }
                    }
                    else
                    {
                        $cur = [$abc_i, 1];
                        break;
                    }
                }
            }
            while (true);

            ##################################################################################

            // обращение к случайному знаку строки
            $result .= $abc[$abc_i][3][random_int(0, $abc[$abc_i][2] - 1)];
        }

        return $result;
    }

    /**
     * Оценка надёжности пароля.
     *
     * @param      string  $password
     * @return     int
     */
    public static function getPasswordStrength(string $password): int
    {
        if (($length = mb_strlen($password)) < 1)
        {
            return self::STRENGTH_NOT_RATED;
        }

        $chars = [];

        for ($i = 0; $i < $length; $i++)
        {
            $chars[$password[$i]] = '';
        }

        $password = implode('', array_keys($chars));
        $length = mb_strlen($password);

        static $sets = ['a-z', 'A-Z', '0-9', "!#$%&()*+,\\-.\\:;=@\\^_`~"];
        static $lengths = [26, 26, 10, 20];

        $charset = 0;
        $types = 0;

        foreach ($sets as $key => $set)
        {
            if (preg_match('/[' . $set .  ']+/', $password))
            {
                $charset += $lengths[$key];
                $types++;
            }
        }

        if ($types > 1) // минимально два набора символов должно использоваться
        {
            // вычисление информационной энтропии
            $bits = floor($length * log($charset) / log(2));

            if ($bits >= 67) // min(11 uniq chars and 4 sets[69] OR 12 uniq chars and 3 sets[69] OR 13 uniq chars and 2 sets[67])
            {
                return self::STRENGTH_BEST;
            }

            if ($bits >= 56) // min(9 uniq chars and 4 sets[57] OR 10 uniq chars and 3 sets[58] OR 11 uniq chars and 2 sets[56])
            {
                return self::STRENGTH_STRONG;
            }

            if ($bits >= 44) // min(7 uniq chars and 4 sets[44] OR 8 uniq chars and 3 sets[46] OR 9 uniq chars and 2 sets[46])
            {
                return self::STRENGTH_MEDIUM;
            }

            if ($bits >= 31) // min(5 uniq chars and 4 sets[31] OR 6 uniq chars and 3 sets[34] OR 7 uniq chars and 2 sets[36])
            {
                return self::STRENGTH_WEAK;
            }
        }

        return self::STRENGTH_NOT_RATED;
    }

    /**
     * Преобразование из десятичной системы в N-ричную.
     * где N - n мерная система счисления, она не должна быть более 62.
     *
     * @param      int  $number
     * @param      int  $N
     * @return     string
     */
    public static function dec2N(int $number, int $N): string
    {
        $result = '';

        if ($N > 1 && $N < 63 && $number <= PHP_INT_MAX)
        {
            while ($number > 0)
            {
                $q = ($number % $N);
                $number = (int)(($number - $q) / $N);

                // числа от 0 (48) до 9 (57)
                if ($q < 10)
                {
                    $q += 48;
                }
                // символы от a (97) до z (122)
                else if ($q < 36)
                {
                    $q += 87; // 97 - 10
                }
                // символы от A (65) до Z(90)
                else if ($q < 62)
                {
                    $q += 29; // 65 - 10 - 26
                }

                $result = chr($q) . $result;
            }
        }
        // else ERROR

        return $result;
    }

    /**
     * Шифруется строка $str накладывая на себя $keyword методом XOR.
     *
     * @param      string  $str
     * @param      string  $keyword
     * @param      string  $salt OPTIONAL
     * @return     string
     */
    public static function encryptXOR(string $str, string $keyword, string $salt = ''): string
    {
        return base64_encode(static::_encryptionXOR($str, $keyword, $salt));
    }

    /**
     * Расшифровывается строка $str накладывая на себя $keyword методом XOR.
     *
     * @param      string  $str
     * @param      string  $keyword
     * @param      string  $salt OPTIONAL
     * @return     string
     */
    public static function decryptXOR(string $str, string $keyword, string $salt = ''): string
    {
        if (false === ($str = base64_decode($str, true)))
        {
            return '';
        }

        return static::_encryptionXOR($str, $keyword, $salt);
    }

   /**
    * Возвращение временного ключевого слова
    * используемого для шифрования данных.
    *
    * @param      string  $filePath
    * @param      int  $expire OPTIONAL (в секундах, 900 сек. = 15 мин. = 15 * 60)
    * @param      bool  $regenerate OPTIONAL
    * @return     string
    * @throws     Exception
    * @throws     UnitTestException
    */
    public static function getKeyword(string $filePath, int $expire = 900, bool $regenerate = false): string
    {
        $keyword = null;
        $time = null;

        if (!$regenerate && file_exists($filePath))
        {
           require ($filePath);
        }

        ##################################################################################

        // если переменные не были подключены или их срок жизни истёк,
        // то генерится новое ключевое слово
        if (!isset($keyword, $time) || $time < time() - $expire)
        {
            $keyword = static::generatePassword(32);

            if ($fh = fopen($filePath, 'ab'))
            {
                flock($fh, LOCK_EX);
                ftruncate($fh, 0);
                fwrite($fh, '<?php $keyword = \'' . $keyword . '\'; $time = ' . time() . ';');
                fflush($fh);
                flock($fh, LOCK_UN);
                fclose($fh);
            }
        }

        ##################################################################################

        if (!empty($_ENV['MRCORE_UNITTEST']))
        {
            require_once 'mrcore/exceptions/UnitTestException.php';

            throw new UnitTestException
            (
                __CLASS__ . '::' . __METHOD__,
                array
                (
                    'keyword' => $keyword,
                    'time' => $time ?? 0,
                )
            );
        }

        return $keyword;
    }

    /**
     * Реализуется алгоритм накладывания строк $str и $keyword
     * друг на друга методом XOR.
     *
     * @param      string  $str
     * @param      string  $keyword
     * @param      string  $salt OPTIONAL
     * @return     string
     */
    /*__private__*/protected static function _encryptionXOR(string $str, string $keyword, string $salt): string
    {
        $len = strlen($str);
        $gamma = '';
        $n = $len > 100 ? 8 : 2;

        while (strlen($gamma) < $len)
        {
            $gamma .= substr(sha1($salt . '|' . $keyword . $gamma, true), 0, $n);
        }

        return $str ^ $gamma;
    }

}