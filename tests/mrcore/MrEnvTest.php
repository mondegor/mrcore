<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use mrcore\testing\Snapshot;

require_once 'mrcore/MrEnv.php';
require_once 'mrcore/MrDebug.php';

class MrEnvTest extends TestCase
{

    public static function setUpBeforeClass(): void
    {
        Snapshot::storeEnv(['HTTP_X_FORWARDED_FOR']);
    }

    public static function tearDownAfterClass(): void
    {
        Snapshot::restoreAll();
    }

    ##################################################################################

    /**
     * @dataProvider listOfGetUserIpProvider
     */
    public function testGetUserIp($httpClientIp, $expected): void
    {
        // putenv('REMOTE_ADDR=' . $realIp);
        // putenv('HTTP_CLIENT_IP=' . $httpClientIp);
        // putenv('HTTP_X_CLUSTER_CLIENT_IP=' . $httpClientIp);
        putenv('HTTP_X_FORWARDED_FOR=' . $httpClientIp);

        $ips = MrEnv::getUserIP();

        $ips['ip_real'] = long2ip($ips['ip_real']);
        $ips['ip_client'] = long2ip($ips['ip_client']);
        $ips['ip_proxy'] = long2ip($ips['ip_proxy']);

        $this->assertEquals($expected, $ips);
    }

    public function listOfGetUserIpProvider(): array
    {
        $realIp = getenv('REMOTE_ADDR');

        return [
            [$realIp, ['ip_real' => $realIp,
                       'ip_client' => $realIp,
                       'ip_proxy' => '0.0.0.0',
                       'string' => $realIp]],

            ['100.100.100.100, ' . $realIp, ['ip_real' => $realIp,
                                            'ip_client' => '100.100.100.100',
                                            'ip_proxy' => $realIp,
                                            'string' => 'IP: ' . $realIp . '; X_FORWARDED_FOR: 100.100.100.100']],

            ['first string 100.100.100.100, middle string ' . $realIp . ' last string', ['ip_real' => $realIp,
                                                                                         'ip_client' => '100.100.100.100',
                                                                                         'ip_proxy' => $realIp,
                                                                                         'string' => 'IP: ' . $realIp . '; X_FORWARDED_FOR: 100.100.100.100']],

            [$realIp . ', 100.100.100.100', ['ip_real' => $realIp,
                                            'ip_client' => '100.100.100.100',
                                            'ip_proxy' => $realIp,
                                            'string' => 'IP: ' . $realIp . '; X_FORWARDED_FOR: 100.100.100.100']],

            ['first string ' . $realIp . ', middle string 100.100.100.100 last string', ['ip_real' => $realIp,
                                                                                         'ip_client' => '100.100.100.100',
                                                                                         'ip_proxy' => $realIp,
                                                                                         'string' => 'IP: ' . $realIp . '; X_FORWARDED_FOR: 100.100.100.100']],

            [$realIp . ', 100.100.100.100', ['ip_real' => $realIp,
                                            'ip_client' => '100.100.100.100',
                                            'ip_proxy' => $realIp,
                                            'string' => 'IP: ' . $realIp . '; X_FORWARDED_FOR: 100.100.100.100']],

            [$realIp . ', 100.100.100.100, 200.200.200.200, 255.255.255.255', ['ip_real' => $realIp,
                                                                              'ip_client' => '100.100.100.100',
                                                                              'ip_proxy' => $realIp,
                                                                              'string' => 'IP: ' . $realIp . '; X_FORWARDED_FOR: 100.100.100.100, 200.200.200.200']],
        ];
    }

    ##################################################################################

    /**
     * @dataProvider listOfCorrectDomainsProvider
     */
    public function testCheckDomainIfCorrect(string $url, string $domain): void
    {
        $this->assertTrue(MrEnv::checkDomain($url, $domain));
    }

    public function listOfCorrectDomainsProvider(): array
    {
        return [
            ['http://sample.domain', 'sample.domain'],
            ['https://sample.domain', 'sample.domain'],
            ['http://sample.domain/', 'sample.domain'],
            ['https://sample.domain/', 'sample.domain'],
            ['http://sample.domain/page1/', 'sample.domain'],
            ['https://sample.domain/page1/', 'sample.domain'],
            ['http://www.sample.domain', 'sample.domain'],
            ['https://www.sample.domain', 'sample.domain'],
            ['http://www.sample.domain/', 'sample.domain'],
            ['https://www.sample.domain/', 'sample.domain'],
            ['http://www.sample.domain/page1/', 'sample.domain'],
            ['https://www.sample.domain/page1/', 'sample.domain'],
        ];
    }

    /**
     * @dataProvider listOfInvalidDataProvider
     */
    public function testCheckDomainIfInvalidData(string $url, string $domain): void
    {
        $this->assertFalse(MrEnv::checkDomain($url, $domain));
    }

    public function listOfInvalidDataProvider(): array
    {
        return [
            ['http://sample.domain1', 'sample.domain'],
            ['https://1sample.domain', 'sample.domain'],
            ['http://sample.domain1/', 'sample.domain'],
            ['https://1sample.domain/', 'sample.domain'],
            ['http://1sample.domain/page1/', 'sample.domain'],
            ['https://sample.domain1/page1/', 'sample.domain'],
            ['http://1www.sample.domain', 'sample.domain'],
            ['https://www1.sample.domain', 'sample.domain'],
            ['http://1www.sample.domain/', 'sample.domain'],
            ['https://www1.sample.domain/', 'sample.domain'],
            ['http://1www.sample.domain/page1/', 'sample.domain'],
            ['https://www1.sample.domain/page1/', 'sample.domain'],
            ['http://sample.domain', 'sample.domain/'],
            ['http://sample.domain/', 'sample.domain/'],
        ];
    }

    ##################################################################################

    /**
     * @dataProvider listOfRemoveParamsProvider
     */
    public function testRemoveParams(string $url, array $params, $expected): void
    {
        $this->assertSame($expected, MrEnv::removeParams($url, $params));
    }

    public function listOfRemoveParamsProvider(): array
    {
        return [
            ['https://sample.domain/page/?param1=test1',              [],                   'https://sample.domain/page/?param1=test1'],
            ['https://sample.domain/page/?param1=test1',              ['param1'],           'https://sample.domain/page/'],
            ['https://sample.domain/page/?param1=test1',              ['param1', 'param2'], 'https://sample.domain/page/'],
            ['https://sample.domain/page/?param1=test1&param2=test2', ['param1'],           'https://sample.domain/page/?param2=test2'],
            ['https://sample.domain/page/?param1=test1&param2=test2', ['param2'],           'https://sample.domain/page/?param1=test1'],
            ['https://sample.domain/page/?param1=test1&param2=test2', ['param1', 'param2'], 'https://sample.domain/page/'],
            ['https://sample.domain/page/',                           ['param1', 'param2'], 'https://sample.domain/page/'],
        ];
    }

}