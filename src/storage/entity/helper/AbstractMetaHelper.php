<?php declare(strict_types=1);
namespace mrcore\storage\entity\helper;
use mrcore\storage\entity\AbstractEntityMeta;

/**
 * Абстракция для работы с метаданными сущности.
 *
 * @author  Andrey J. Nazarov
 */
abstract class AbstractMetaHelper
{

    public function __construct(protected AbstractEntityMeta $meta) { }

}