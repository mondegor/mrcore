<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use mrcore\services\EnvService;

use mrcore\testing\Snapshot;

require_once 'mrcore/services/EnvService.php';

class EnvServiceTest extends TestCase
{

    protected function setUp(): void
    {
        Snapshot::storeEnv(['HTTP_X_FORWARDED_FOR', 'HTTP_USER_AGENT',
                            'HTTP_HOST', 'REQUEST_URI',
                            'HTTP_REFERER', 'HTTP_X_REQUESTED_WITH']);
    }

    protected function tearDown(): void
    {
        Snapshot::restoreAll();
    }

    ##################################################################################

    /**
     * @dataProvider listOfCorrectDomainsProvider
     */
    public function testCheckDomainIfCorrect(string $url, string $domain): void
    {
        $this->assertTrue(EnvService::checkDomain($url, $domain));
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

    ##################################################################################

    /**
     * @dataProvider listOfInvalidDataProvider
     */
    public function testCheckDomainIfInvalidData(string $url, string $domain): void
    {
        $this->assertFalse(EnvService::checkDomain($url, $domain));
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
        $this->assertSame($expected, EnvService::removeParams($url, $params));
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

    ##################################################################################

    public function testIsCli(): void
    {
        $envService = $this->createPartialMock(EnvService::class, []);

        $this->assertSame(defined('STDIN'), $envService->isCli());
    }

    ##################################################################################

    public function testIsWindows(): void
    {
        $envService = $this->createPartialMock(EnvService::class, []);

        $this->assertSame('/' === DIRECTORY_SEPARATOR, $envService->isWindows());
    }

    ##################################################################################

    public function testGet(): void
    {
        $expected = 'test-value';
        putenv('HTTP_X_FORWARDED_FOR=' . $expected);

        $envService = $this->createPartialMock(EnvService::class, []);
        $this->assertSame($expected, $envService->get('HTTP_X_FORWARDED_FOR'));
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

        $envService = $this->createPartialMock(EnvService::class, []);
        $ips = $envService->getUserIP();

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

    public function testGetUserAgent(): void
    {
        $expected = 'test-agent';
        putenv('HTTP_USER_AGENT=' . $expected);

        $envService = $this->createPartialMock(EnvService::class, []);
        $this->assertSame($expected, $envService->getUserAgent());
    }

    ##################################################################################

    public function testGetRequestUrl(): void
    {
        $expected = 'https://#test-host/#uri';
        putenv('HTTP_HOST=#test-host');
        putenv('REQUEST_URI=/#uri');

        $envService = $this->createPartialMock(EnvService::class, []);
        $this->assertSame($expected, $envService->getRequestUrl());
    }

    ##################################################################################

    public function testGetRequestUrlIfHostEmpty(): void
    {
        putenv('HTTP_HOST=');
        putenv('REQUEST_URI=/#uri');

        $envService = $this->createPartialMock(EnvService::class, []);
        $this->assertEmpty($envService->getRequestUrl());
    }

    ##################################################################################

    public function testGetRefererUrl(): void
    {
        $expected = 'test-referer';
        putenv('HTTP_REFERER=' . $expected);

        $envService = $this->createPartialMock(EnvService::class, []);
        $this->assertSame($expected, $envService->getRefererUrl());
    }

    ##################################################################################

    public function testIsXmlHttpRequest(): void
    {
        putenv('HTTP_X_REQUESTED_WITH=XMLHttpRequest');

        $envService = $this->createPartialMock(EnvService::class, []);
        $this->assertTrue($envService->isXmlHttpRequest());
    }

    ##################################################################################

    public function testIsXmlHttpRequestIfBadValue(): void
    {
        putenv('HTTP_X_REQUESTED_WITH=badvalue');

        $envService = $this->createPartialMock(EnvService::class, []);
        $this->assertFalse($envService->isXmlHttpRequest());
    }

}