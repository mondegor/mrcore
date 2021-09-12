<?php declare(strict_types=1);
namespace mrcore\base\testdata;
use Closure;
use mrcore\base\BuilderLink;

require_once 'mrcore/base/BuilderLink.php';

class ConcreteBuilderLinkFactory extends BuilderLink
{
    public string $anchor = '';

    public function __construct(string $host, string $path, array $args = [], string $scheme = 'http', Closure $cbUrl = null)
    {
        $this->scheme = '';
        $this->host = '';
        $this->path = [];
        $this->file = '';
        $this->cbUrl = null;
        $this->_anchor = '';
        $this->_args = [];
    }

    public function &setAnchor(string $anchor): BuilderLink
    {
        $this->anchor = $anchor;

        return $this;
    }

}