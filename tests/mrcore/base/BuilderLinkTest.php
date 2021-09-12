<?php declare(strict_types=1);
namespace mrcore\base;
use PHPUnit\Framework\TestCase;

use mrcore\base\testdata\ConcreteBuilderLinkFactory;
use mrcore\testing\Helper;

require_once 'mrcore/base/BuilderLink.php';

class BuilderLinkTest extends TestCase
{

    public function testFactory(): void
    {
        $anchor = 'anchor1';
        $link = &ConcreteBuilderLinkFactory::factory('http://sample.org/#' . $anchor);

        $this->assertSame(ConcreteBuilderLinkFactory::class, get_class($link));
        $this->assertSame($anchor, $link->anchor);
    }

    ##################################################################################

    public function testConstructor(): void
    {
        $scheme = 'testhttp';
        $host = 'testhost';
        $path = 'testname1/testname2';
        $args = ['arg1' => 'value1'];

        $cbUrl = static function () {
            return 'testcburl';
        };

        $link = new BuilderLink($host, $path, $args, $scheme, $cbUrl);
        $testCbUrl = $link->cbUrl;

        $this->assertSame($scheme, $link->scheme);
        $this->assertSame($host, $link->host);
        $this->assertSame([dirname($path)], $link->path);
        $this->assertSame(basename($path), $link->file);
        $this->assertSame($args, Helper::getProperty($link, '_args'));
        $this->assertSame($cbUrl(), $testCbUrl());
    }

    ##################################################################################

    public function testPush(): void
    {
        $link = $this->createPartialMock(BuilderLink::class, []);
        $link->push('testname1');

        $this->assertSame(['testname1'], $link->path);
    }

    ##################################################################################

    public function testGet(): void
    {
        $link = $this->createPartialMock(BuilderLink::class, []);
        Helper::setProperty($link, '_args', ['testname1' => 'value1']);

        $this->assertSame('value1', $link->get('testname1'));
    }

    ##################################################################################

    public function testSet(): void
    {
        $link = $this->createPartialMock(BuilderLink::class, []);
        $link->set('testname1', 'value1');

        $this->assertSame(['testname1' => 'value1'], Helper::getProperty($link, '_args'));
    }

    ##################################################################################

    public function testRemove(): void
    {
        $link = $this->createPartialMock(BuilderLink::class, []);
        Helper::setProperty($link, '_args', ['testname1' => 'value1']);
        $link->remove('testname1');

        $this->assertEmpty(Helper::getProperty($link, '_args'));
    }

    ##################################################################################

    public function testAddRange(): void
    {
        $args1 = ['testname1' => 'value1'];
        $args2 = ['testname2' => 'value2'];

        $link = $this->createPartialMock(BuilderLink::class, []);
        Helper::setProperty($link, '_args', $args1);
        $link->addRange($args2);

        $this->assertSame(array_merge($args1, $args2), Helper::getProperty($link, '_args'));
    }

    ##################################################################################

    public function testSetAnchor(): void
    {
        $anchor = 'anchor1';
        $link = $this->createPartialMock(BuilderLink::class, []);
        $link->setAnchor($anchor);

        $this->assertSame($anchor, Helper::getProperty($link, '_anchor'));
    }

    ##################################################################################

    /**
     * @dataProvider listOfGetUrlValuesProvider
     */
    public function testGetUrl(string $url): void
    {
        $parsed = @parse_url($url); // :WARNING: заглушены ошибки в функции
        $args = [];

        if (!empty($parsed['query']))
        {
            parse_str($parsed['query'], $args);
        }

        $path = (empty($parsed['path']) ? [] : explode('/', $parsed['path']));
        $file = (empty($path) ? '' : array_pop($path));
        $fragment = (empty($parsed['fragment']) ? '' : $parsed['fragment']);

        $link = $this->createPartialMock(BuilderLink::class, []);

        $link->scheme = (empty($parsed['scheme']) ? 'http' : $parsed['scheme']);
        $link->host = (empty($parsed['host']) ? '' : $parsed['host']);
        $link->path = $path;
        $link->file = $file;
        $link->cbUrl = null;

        Helper::setProperty($link, '_args', $args);
        Helper::setProperty($link, '_anchor', $fragment);

        $this->assertSame($url, $link->getUrl());
    }

    public function listOfGetUrlValuesProvider(): array
    {
        return [
            ['https://mail.adomain.com/mail/u/0/eee#inbox'],
            ['http://mail.adomain.com/mail/u/0/eee.html#inbox'],
            ['http://domain3/rus/services/contests/rating/?page=25'],
            ['http://domain4.com/uni/partreportp2/all/'],
            ['http://d.test.domain.com/dstatus/'],
            ['https://www.domain.com/news/stock-market-news/u.s.-stock-futures-muted-ahead-of-data,-fed-speakers-498619'],
            ['https://www.domain6.com/cards/credit-cards/tinkoff-platinum/#form-application'],
            // ['https://news.qdomain.ru/search?cl4url=tass.ru/ekonomika/4360598&lang=ru&from=main_portal&stid=atIDOyDBMZGU14PSSqYL&lr=213&msid=1498219430.36005.22874.2712&mlid=1498218763.glob_225.34396ba9'],
            ['https://news.qdomain.ru/search?cl4url=tass.ru%2Fekonomika%2F4360598&lang=ru&from=main_portal&stid=atIDOyDBMZGU14PSSqYL&lr=213&msid=1498219430.36005.22874.2712&mlid=1498218763.glob_225.34396ba9'],
            ['https://www.adomain.ru/?gws_rd=ssl#newwindow=1&q=fff&btnK=%D0%9F%D0%BE%D0%B8%D1%81%D0%BA%20%D0%B2%20Google'],
            ['adomain.com/mail/u/0/eee#inbox'],
            ['adomain.com/mail/u/0/eee/'],
            ['/adomain.com/mail/u/0/eee/'],
            ['/adomain.com/mail/u/0/eee/?d=2'],
            ['/adomain.com/mail/u/0/eee'],
            ['/adomain.com/mail/u/0/eee.hee'],
            // ['adomain.com/mail/u/0/eee.hee?'],
            ['adomain.com/mail/u/0/eee.hee?d=2'],
            ['https://www.domain.com/tutorials/ftp/ftp_chmod.htm'],
            ['http://ftp.domain.ru/pub/Certificates/%d1%81%d0%b5%d1%80%d1%82%d0%b8%d1%84%d0%b8%d0%ba%d0%b0%d1%82%d1%8b.part12.rar'],
            ['ftp://ftp.domain.com/'],
            ['ftp://ftp.domain.com/public/dansk/removal_tools/DRE.exe'],
            // ['http://domain.com'],
            ['http://domain2.com/'],
            // ['http://domain.com?ok=1'],
            ['http://domain2.com/?ok=1'],
            ['?ok1=1&ok2=1'],
            ['/?ok1=1&ok2=1'],
            ['index.php?ok1=1&ok2=1'],
            ['/index.php?ok1=1&ok2=1'],
            ['part/index.php?ok1=1&ok2=1'],
            ['/part/index.php?ok1=1&ok2=1'],
            ['/'],
            [''],
        ];
    }

}