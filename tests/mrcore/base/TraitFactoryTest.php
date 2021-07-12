<?php declare(strict_types=1);
namespace mrcore\base\TraitFactory;
use RuntimeException;
use PHPUnit\Framework\TestCase;

require_once 'mrcore/base/TraitFactory/ClassTraitFactory.php';

class TraitFactoryTest extends TestCase
{
    public function testFactoryIfTheClassOfTheObjectBeingCreatedIsAExtendsOfClassTraitFactory(): void
    {
        $o = &ClassTraitFactory::factory('ConcreteClassTraitFactory');
        $this->assertIsObject($o);
    }

    ##################################################################################

    public function testFactoryIfTheClassOfTheObjectBeingCreatedIsNotFound(): void
    {
        $this->expectException(RuntimeException::class);
        ClassTraitFactory::factory('EmptyFileTraitFactory');
    }

    ##################################################################################

    public function testFactoryIfTheClassOfTheObjectBeingCreatedIsNotAExtendsOfClassTraitFactory(): void
    {
        $this->expectException(RuntimeException::class);
        ClassTraitFactory::factory('NotClassTraitFactory');
    }

}