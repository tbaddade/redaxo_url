<?php

/**
 * This file is part of the Url package.
 *
 * @author (c) Thomas Blum <thomas@addoff.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Url;

use Riimu\Kit\UrlParser\Uri;
use Url\Rewriter\Rewriter;

class Url
{
    public $uri;

    protected $handleRewriterSuffix = false;

    protected $sitemap = false;
    protected $sitemapLastmod = '';

    /**
     * @var \Url\Rewriter\Rewriter
     */
    private static $rewriter;

    public function __construct($url)
    {
        // $this->uri = (new UriParser())->parse($url);
        $this->uri = (new Uri($url));
    }

    public function __call($method, $arguments)
    {
        return $this->uri->$method(...$arguments);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        if ($this->handleRewriterSuffix) {
            $this->appendRewriterSuffix();
            $this->handleRewriterSuffix = false;
        }
        return $this->uri->__toString();
    }

    public function handleRewriterSuffix()
    {
        $this->handleRewriterSuffix = true;
        $this->removeRewriterSuffix();
    }

    public function appendPathSegments(array $segments, $clangId = 0)
    {
        $segments = $this->normalize($segments, $clangId);
        return $this->modifyPathSegments($this->uri->getPathSegments(), $segments);
    }

    public function prependPathSegments(array $segments, $clangId = 0)
    {
        $segments = $this->normalize($segments, $clangId);
        return $this->modifyPathSegments($segments, $this->uri->getPathSegments());
    }

    public function withHost($domain)
    {
        $this->uri = $this->uri->withHost($domain);
        return $this;
    }

    public function withQuery($query)
    {
        $this->uri = $this->uri->withQuery($query);
        return $this;
    }

    public function withScheme($scheme)
    {
        $this->uri = $this->uri->withScheme($scheme);
        return $this;
    }

    public function withSolvedScheme()
    {
        return $this->withScheme(self::getRewriter()->getSchemeByDomain($this->getDomain()) ?: (self::getRewriter()->isHttps() ? 'https' : 'http'));
    }

    /**
     * Gets the scheme and HTTP host.
     *
     * @return string
     */
    public function getSchemeAndHttpHost()
    {
        return $this->uri->getScheme().'://'.$this->uri->getHost();
    }

    /**
     * @return string
     */
    public function getDomain()
    {
        return $this->uri->getHost();
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->uri->getPath();
    }

    /**
     * @return string
     */
    public function getQuery()
    {
        return $this->uri->getQuery();
    }

    public function getSegment(int $index, $default = null)
    {
        $segments = $this->getSegments();
        if ($index === 0) {
            // throw InvalidArgument::segmentZeroDoesNotExist();
        }
        if ($index < 0) {
            $segments = array_reverse($segments);
            $index = abs($index);
        }
        return $segments[$index - 1] ?? $default;
    }

    public function getSegments()
    {
        return $this->uri->getPathSegments();
    }

    /**
     * Get the filename from the path.
     *
     * @return string
     */
    public function getFileName()
    {
        return $this->getSegment(-1);
    }

    /**
     * Get the directory name from the path.
     *
     * @return string
     */
    public function getDirName()
    {
        $segments = $this->uri->getPathSegments();
        array_pop($segments);
        return '/'.implode('/', $segments);
    }

    public function sitemap($value)
    {
        $this->sitemap = $value;
    }

    public function sitemapLastmod($value)
    {
        if (strpos($value, '-')) {
            // mysql date
            $datetime = new \DateTime($value);
            $value = $datetime->getTimestamp();
        }
        $this->sitemapLastmod = date(DATE_W3C, $value);
    }

    public static function getRewriter()
    {
        return self::$rewriter;
    }

    public static function setRewriter(Rewriter $rewriter)
    {
        self::$rewriter = $rewriter;
    }

    public static function get($url)
    {
        return new self($url);
    }

    public static function getCurrent()
    {
        return new self(
            sprintf(
                '%s://%s%s',
                empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off' ? 'http' : 'https',
                $_SERVER['HTTP_HOST'],
                $_SERVER['REQUEST_URI']
            )
        );
    }

    public static function getPrevious()
    {
        return new self(
            isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : ''
        );
    }

    /**
     * @throws \rex_sql_exception
     *
     * @return null|UrlManager
     */
    public static function resolveCurrent()
    {
        return UrlManager::resolveUrl(self::getCurrent());
    }

    // public static function parse($url)
    // {
    //     return new self($url);
    // }

    protected function modifyPathSegments(array $arrayA, array $arrayB)
    {
        $this->uri = $this->uri->withPathSegments(array_merge($arrayA, $arrayB));
        // Path neu setzen, da der Path den / am Anfang durch withPathSegments verloren hat
        $this->uri = $this->uri->withPath('/'.$this->uri->getPath());
        return $this;
    }

    protected function appendRewriterSuffix()
    {
        return $this->uri = $this->uri->withPath($this->uri->getPath().self::$rewriter->getSuffix());
    }

    protected function removeRewriterSuffix()
    {
        $path = $this->uri->getPath();
        $suffix = self::$rewriter->getSuffix();
        if (strlen($suffix) !== 0 && substr($path, (strlen($suffix) * -1)) == $suffix) {
            $path = substr($path, 0, (strlen($suffix) * -1));
        }
        $this->uri = $this->uri->withPath($path);
        return $this;
    }

    private function normalize($sick, $clangId = 0)
    {
        if (is_string($sick)) {
            $sick = [$sick];
        }

        foreach ($sick as $index => $value) {
            $sick[$index] = self::$rewriter->normalize($value, $clangId);
        }
        return $sick;
    }
}
