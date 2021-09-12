<?php declare(strict_types=1);
namespace mrcore\models\testdata;
use mrcore\models\ModelRepository;

require_once 'mrcore/models/ModelRepository.php';

class CachedConcreteModelRepository extends ModelRepository
{
    public string $name = '';
}