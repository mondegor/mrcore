<?php declare(strict_types=1);
namespace mrcore\testdata;
use MrDebug;

require_once 'mrcore/MrDebug.php';

class ConcreteMrDebug extends MrDebug
{

    public function testProtectedPackageFilter(array $classes, $packages): array
    {
        return static::_packageFilter($classes, $packages);
    }

}