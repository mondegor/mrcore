<?php declare(strict_types=1);
namespace mrcore\console;

// :TODO: сейчас здесь функциональные тесты, нужно переделать в юнит тесты

use PHPUnit\Framework\TestCase;
use mrcore\services\EnvService;

use mrcore\console\testdata\ConcreteConsole;

require_once 'mrcore/console/AbstractConsole.php';
require_once 'mrcore/services/EnvService.php';

class AbstractConsoleTest extends TestCase
{
    /**
     * @dataProvider listOfSuccessOptionsFromConsoleProvider
     */
    public function testGetOptionForSuccessOptions(array $args, $expected): void
    {
        $env = $this->createStub(EnvService::class);
        $env->method('isCli')->willReturn(true);

        $options = [];

        foreach ($args as $arg)
        {
            $arg = explode('=', $arg);
            $options[] = $arg[0];
        }

        $argv = ['test.php'];

        foreach (['--option-required', '--option-required-value-required', '-r', '-a'] as $requiredOption)
        {
            if (!in_array($requiredOption, $options, true))
            {
                $argv[] = $requiredOption;

                if ('--option-required-value-required' === $requiredOption || '-a' === $requiredOption)
                {
                    $argv[] = 'rvalue1';
                }
            }
        }

        foreach ($args as $arg)
        {
            $argv[] = $arg;
        }

        $console = new ConcreteConsole($env, $argv, count($argv));

        $this->assertSame($expected, $console->getOption(ltrim($options[0], '-')));
    }

    public function listOfSuccessOptionsFromConsoleProvider(): array
    {
        return [
            [['--option-value-off'],                                 true],
            [['--option-value-off', 'test1'],                        true],
            [['--option-required'],                                  null],
            [['--option-required', 'test1'],                      'test1'],
            [['--option-required=', 'test1'],                          ''],
            [['--option-required=test1', 'test1'],                'test1'],
            [['--option-value-required', 'test1'],                'test1'],
            [['--option-value-required=test1', 'test1'],          'test1'],
            [['--option-required-value-required', 'test1'],       'test1'],
            [['--option-required-value-required=test1', 'test1'], 'test1'],
            [['--option-value'],                                     null],
            [['--option-value', 'test1'],                         'test1'],
            [['--option-value=', 'test1'],                             ''],
            [['--option-value=test1', 'test1'],                   'test1'],
            [['-e'],              true],
            [['-e',  'test1'],    true],
            [['-r'],              null],
            [['-r',  'test1'], 'test1'],
            [['-v',  'test1'], 'test1'],
            [['-a',  'test1'], 'test1'],
            [['-o'],              null],
            [['-o', 'test1'],  'test1'],
        ];
    }

    ##################################################################################

    /**
     * @dataProvider listOfFailedOptionsFromConsoleProvider
     */
    public function testGetOptionForFailedOptions(array $args, $exceptionClass): void
    {
        $env = $this->createStub(EnvService::class);
        $env->method('isCli')->willReturn(true);

        $options = [];

        foreach ($args as $arg)
        {
            $arg = explode('=', $arg);
            $options[] = $arg[0];
        }

        $argv = ['test.php'];

        foreach (['--option-required-value-required', '-a'] as $requiredOption)
        {
            if (!in_array($requiredOption, $options, true))
            {
                $argv[] = $requiredOption;
                $argv[] = 'rvalue1';
            }
        }

        foreach ($args as $arg)
        {
            $argv[] = $arg;
        }

        $this->expectException($exceptionClass);

        $console = new ConcreteConsole($env, $argv, count($argv));
        // $console->getOption(ltrim($options[0], '-'));
    }

    public function listOfFailedOptionsFromConsoleProvider(): array
    {
        return [
            [['--option-required', '-r', '--option-value-off='],               'InvalidArgumentException'],
            [['--option-required', '-r', '--option-value-off=test'],           'InvalidArgumentException'],
            [['-r'],                                                           'InvalidArgumentException'],
            [['--option-required', '-r', '--option-value-required'],           'InvalidArgumentException'],
            [['--option-required', '-r', '--option-value-required='],          'InvalidArgumentException'],
            [['--option-required', '-r', '--option-required-value-required'],  'InvalidArgumentException'],
            [['--option-required', '-r', '--option-required-value-required='], 'InvalidArgumentException'],
            [['--option-required', '-r', '-e='],                               'InvalidArgumentException'],
            [['--option-required', '-r', '-e=test'],                           'InvalidArgumentException'],
            [['--option-required'],                                            'InvalidArgumentException'],
            [['--option-required', '-r', '-v'],                                'InvalidArgumentException'],
            [['--option-required', '-r', '-v='],                               'InvalidArgumentException'],
            [['--option-required', '-r', '-v=1'],                              'InvalidArgumentException'],
            [['--option-required', '-r', '-a'],                                'InvalidArgumentException'],
            [['--option-required', '-r', '-a='],                               'InvalidArgumentException'],
            [['--option-required', '-r', '-a=1'],                              'InvalidArgumentException'],
        ];
    }

    ##################################################################################

    public function testgetOptionIfArgNotExists(): void
    {
        $env = $this->createStub(EnvService::class);
        $env->method('isCli')->willReturn(true);

        $args = ['test.php', '--option-required', '--option-required-value-required=1', '-r', '-a', '1'];

        $console = new ConcreteConsole($env, $args, count($args));

        $this->assertNull($console->getOption('test-option'));
    }

    ##################################################################################

    /**
     * @dataProvider listOfGetFreeArgIfParamExistsProvider
     */
    public function testGetFreeArgIfParamExists(array $argv, int $number, string $expected): void
    {
        $env = $this->createStub(EnvService::class);
        $env->method('isCli')->willReturn(true);

        $args = ['test.php', '--option-required-value-required=1', '-r', '-a', '1'];

        foreach ($argv as $arg)
        {
            $args[] = $arg;
        }

        $console = new ConcreteConsole($env, $args, count($args));
        $this->assertEquals($expected, $console->getFreeArg($number));
    }

    public function listOfGetFreeArgIfParamExistsProvider(): array
    {
        return [
            [['--option-required', 'test1'], 1, 'test1'],
            [['--option-required', 'test1', 'test2'], 2, 'test2'],
            [['--option-required', 'test1', 'test2', '--option-value-off', 'test3'], 3, 'test3'],
            [['test1', 'test2', '--option-required=', 'test3'], 3, 'test3'],
            [['test1', 'test2', '--option-required=1', 'test3'], 3, 'test3'],
            [['--option-required', 'test1', 'test2', '--option-value', 'test3'], 3, 'test3'],
            [['--option-required', 'test1', 'test2', '--option-value=', 'test3'], 3, 'test3'],
            [['--option-required', 'test1', 'test2', '--option-value=1', 'test3'], 3, 'test3'],
        ];
    }

}