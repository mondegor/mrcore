<?php declare(strict_types=1);
namespace mrcore\services\testdata;
use mrcore\services\LogService;

require_once 'mrcore/services/LogService.php';

class ConcreteLogService extends LogService
{

    public function testParseFileName(string $string, array $default) : array
    {
        return $this->_parseFileName($string, $default);
    }

}