<?php declare(strict_types=1);
namespace mrcore\base;
use PHPUnit\Framework\TestCase;
use RuntimeException;

use mrcore\base\testdata\ClassTraitFactory;

class TraitFactoryTest extends TestCase
{

    public function testFactoryIfTheClassOfTheObjectBeingCreatedIsAExtendsOfClassTraitFactory(): void
    {
        $trait = &ClassTraitFactory::factory('ConcreteClassTraitFactory');

        $this->assertIsObject($trait);
    }

    ##################################################################################

    public function testFactoryInitObjectWithManyParams(): void
    {
        $params = ['param1', 100, true];

        $trait = &ClassTraitFactory::factory('ConcreteClassTraitFactory', $params);
        $this->assertSame($params, $trait->testGetParams());
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