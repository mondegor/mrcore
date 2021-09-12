<?php declare(strict_types=1);
namespace mrcore\units;
use PHPUnit\Framework\TestCase;
use mrcore\units\testdata\ConcreteUnit;
use mrcore\testing\Helper;

require_once 'mrcore/units/AbstractUnit.php';

class AbstractUnitTest extends TestCase
{

    public function testConstructor(): void
    {
        $unit = new ConcreteUnit('Module1.SubModule1.UnitName1');

        $this->assertEquals('Module1.SubModule1.UnitName1', Helper::getProperty($unit, '_unitName'));
        $this->assertEquals('Module1', Helper::getProperty($unit, '_moduleName'));
    }

    ##################################################################################

    public function testGetName(): void
    {
        $unit = $this->createPartialMock(ConcreteUnit::class, []);

        Helper::setProperty($unit, '_unitName', 'UnitName1');
        $this->assertEquals('UnitName1', $unit->getName());
    }

}