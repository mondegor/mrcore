<?php declare(strict_types=1);
namespace mrcore\console\testdata;
use mrcore\console\AbstractConsole;

require_once 'mrcore/console/AbstractConsole.php';

class ConcreteConsole extends AbstractConsole
{
    /**
     * @inheritdoc
     */
    protected array $_listOptions = ['option-value-off' => ['flags' => self::FLAG_OPTION_VALUE_OFF],
                                     'option-required' => ['flags' => self::FLAG_OPTION_REQUIRED],
                                     'option-value-required' => ['flags' => self::FLAG_OPTION_VALUE_REQUIRED],
                                     'option-required-value-required' => ['flags' => self::FLAG_OPTION_REQUIRED + self::FLAG_OPTION_VALUE_REQUIRED],
                                     'option-value' => ['flags' => 0],
                                     'e' => ['flags' => self::FLAG_OPTION_VALUE_OFF],
                                     'r' => ['flags' => self::FLAG_OPTION_REQUIRED],
                                     'v' => ['flags' => self::FLAG_OPTION_VALUE_REQUIRED],
                                     'a' => ['flags' => self::FLAG_OPTION_REQUIRED + self::FLAG_OPTION_VALUE_REQUIRED],
                                     'o' => ['flags' => 0]];

}