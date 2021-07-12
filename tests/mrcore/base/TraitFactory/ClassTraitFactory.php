<?php declare(strict_types=1);
namespace mrcore\base\TraitFactory;
use mrcore\base\TraitFactory;

require_once 'mrcore/base/TraitFactory.php';

class ClassTraitFactory
{
    use TraitFactory;

    /**
     * {@inheritdoc}
     */
    private static string $_defaultNamespace = 'mrcore\base\TraitFactory';

}
