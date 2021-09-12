<?php declare(strict_types=1);
namespace mrcore\testdata;
use MrScreen;
use mrcore\exceptions\UnitTestException;

require_once 'mrcore/MrScreen.php';
require_once 'mrcore/exceptions/UnitTestException.php';

class ConcreteMrScreen extends MrScreen
{

    public function testProtectedEcho(string $message, array $args, int $doubleColor): void
    {
        static::_echo($message, $args, $doubleColor);
    }

}

class ConcreteMrScreenStubProtectedEcho extends MrScreen
{

    protected static function _echo(string $message, array $args, int $doubleColor): void
    {
        throw new UnitTestException(__CLASS__ . '::' . __METHOD__, ['message' => $message]);
    }

}