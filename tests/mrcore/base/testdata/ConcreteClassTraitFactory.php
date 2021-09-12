<?php declare(strict_types=1);
namespace mrcore\base\testdata;

require_once 'mrcore/base/testdata/ClassTraitFactory.php';

class ConcreteClassTraitFactory extends ClassTraitFactory
{
    private array $_params;

    public function __construct(string $str = null, int $number = null, bool $flag = null)
    {
        $this->_params = [$str, $number, $flag];
    }

    public function testGetParams(): array
    {
        return $this->_params;
    }

}