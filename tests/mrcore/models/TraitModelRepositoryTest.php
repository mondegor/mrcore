<?php declare(strict_types=1);
namespace mrcore\models;
use mrcore\models\testdata\CachedConcreteModelRepository;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use InjectedServicesContainer;
use mrcore\db\Adapter;
use mrcore\services\ConnService;
use mrcore\models\testdata\ConcreteModelRepository;

require_once 'mrcore/InjectedServicesContainer.php';
require_once 'mrcore/db/Adapter.php';
require_once 'mrcore/services/ConnService.php';

class TraitModelRepositoryTest extends TestCase
{
    private function _initInviroment(): void
    {
        $conn = $this->getMockBuilder(ConnService::class)
                     ->onlyMethods(['db'])
                     ->getMock();

        $adapter = $this->getMockForAbstractClass(Adapter::class, [], '', false);

        $conn->expects($this->once())->method('db')->willReturn($adapter);

        $container = new InjectedServicesContainer();
        $container->addService('global.connection', $conn);
    }

    ##################################################################################

    public function testGetModelRepository(): void
    {
        $this->_initInviroment();

        $object = new ConcreteClassTraitModelRepository();
        $repository = &$object->getModelRepository(ConcreteModelRepository::class, false);

        $this->assertSame(ConcreteModelRepository::class, get_class($repository));
    }

    ##################################################################################

    public function testGetModelRepositoryFromCache(): void
    {
        $this->_initInviroment();

        $object = new ConcreteClassTraitModelRepository();
        $repository1 = &$object->getModelRepository(CachedConcreteModelRepository::class, false);
        $repository1->name = 'repository1';

        $repository2 = &$object->getModelRepository(CachedConcreteModelRepository::class);

        $this->assertSame($repository1->name, $repository2->name);
    }

    ##################################################################################

    public function testGetModelRepositoryIfClassNotExists(): void
    {
        $this->expectException(RuntimeException::class);

        $object = new ConcreteClassTraitModelRepository();
        $object->getModelRepository('\mrcore\models\testdata\EmptyFileModelRepository', false);
    }

}