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
use Riimu\Kit\UrlParser\UriParser;
use Url\Rewriter\Rewriter;

class Url
{
    public Uri $uri;
    public Uri $requestUri;

    protected bool $sitemap = false;
    protected string $sitemapLastmod = '';

    /**
     * @var Rewriter|null
     */
    private static ?Rewriter $rewriter = null;

    /**
     * @param string $url
     */
    public function __construct(string $url)
    {
        try {
            $this->uri = (new Uri($url, UriParser::MODE_UTF8));
        } catch (\InvalidArgumentException $ex) {
            $this->uri = new Uri();
        }
        $this->requestUri = $this->uri;
        $this->removeRewriterSuffix();
    }

    public function __call(string $method, array $arguments): mixed
    {
        return $this->uri->$method(...$arguments);
    }

    /**
     * @return string
     */
    public function toString(): string
    {
        $this->appendRewriterSuffix();
        return $this->uri->__toString();
    }

    /**
     * @return string
     */
    public function getRequestPath(): string
    {
        return $this->requestUri->getPath();
    }

    public function appendPathSegments(array $segments, int $clangId = 1): self
    {
        $segments = $this->normalize($segments, $clangId);
        return $this->modifyPathSegments($this->uri->getPathSegments(), $segments);
    }

    public function prependPathSegments(array $segments, int $clangId = 1): self
    {
        $segments = $this->normalize($segments, $clangId);
        return $this->modifyPathSegments($segments, $this->uri->getPathSegments());
    }

    /**
     * @return self
     */
    public function withHost(string $domain): self
    {
        $this->uri = $this->uri->withHost($domain);
        return $this;
    }

    /**
     * @return self
     */
    public function withQuery(string $query): self
    {
        $this->uri = $this->uri->withQuery($query);
        return $this;
    }

    /**
     * @return self
     */
    public function withScheme(string $scheme): self
    {
        $this->uri = $this->uri->withScheme($scheme);
        return $this;
    }

    public function withSolvedScheme(): self
    {
        return $this->withScheme(self::getRewriter()->getSchemeByDomain($this->getDomain()) ?: (self::getRewriter()->isHttps() ? 'https' : 'http'));
    }

    /**
     * Gets the scheme and HTTP host.
     *
     * @return string
     */
    public function getSchemeAndHttpHost(): string
    {
        return $this->uri->getScheme().'://'.$this->uri->getHost();
    }

    /**
     * @return string
     */
    public function getDomain(): string
    {
        return $this->uri->getHost();
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        $this->appendRewriterSuffix();
        return $this->uri->getPath();
    }

    /**
     * @return string
     */
    public function getPathWithoutSuffix(): string
    {
        $this->removeRewriterSuffix();
        return $this->uri->getPath();
    }

    /**
     * @return string
     */
    public function getQuery(): string
    {
        return $this->uri->getQuery();
    }

    public function getSegment(int $index, mixed $default = null): mixed
    {
        $segments = $this->getSegments();
        if ($index < 0) {
            $segments = array_reverse($segments);
            $index = abs($index);
        }
        return $segments[$index - 1] ?? $default;
    }

    public function getSegments(): array
    {
        $this->appendRewriterSuffix();
        return $this->uri->getPathSegments();
    }

    /**
     * Get the filename from the path.
     *
     * @return string
     */
    public function getFileName(): string
    {
        return $this->getSegment(-1);
    }

    /**
     * Get the directory name from the path.
     *
     * @return string
     */
    public function getDirName(): string
    {
        $segments = $this->uri->getPathSegments();
        array_pop($segments);
        return '/'.implode('/', $segments);
    }

    /**
     * @return void
     */
    public function sitemap(bool $value): void
    {
        $this->sitemap = $value;
    }

    /**
     * @return void
     */
    public function sitemapLastmod(string|int $value): void
    {
        if (strpos($value, '-')) {
            // mysql date
            $datetime = new \DateTime($value);
            $value = $datetime->getTimestamp();
        }
        $this->sitemapLastmod = date(DATE_W3C, $value);
    }

    /**
     * @return Rewriter|null
     */
    public static function getRewriter(): ?Rewriter
    {
        return self::$rewriter;
    }

    /**
     * @return void
     */
    public static function setRewriter(Rewriter $rewriter): void
    {
        self::$rewriter = $rewriter;
    }

    /**
     * @return self
     */
    public static function get(string $url): self
    {
        return new self($url);
    }

    /**
     * @return self
     */
    public static function getCurrent(): self
    {

        return new self(
            sprintf(
                '%s://%s%s',
                empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off' ? 'http' : 'https',
                ($_SERVER['HTTP_HOST'] ?? ''),
                ($_SERVER['REQUEST_URI'] ?? '')
            )
        );
    }


    /**
     * @return array
     */
    public static function getCurrentUserPath(): array
    {
        $manager = self::resolveCurrent();
        if ($manager && $manager->isUserPath() && $profile = $manager->getProfile()) {
            $segments = $manager->getUrl()->getSegments();
            foreach ($profile->getUserPaths() as $path => $title) {
                if (in_array($path, $segments)) {
                    return ['path' => $path, 'title' => $title];
                }
            }
        }
        return [];
    }

    /**
     * @return self
     */
    public static function getPrevious(): self
    {
        return new self(
            $_SERVER['HTTP_REFERER'] ?? ''
        );
    }

    /**
     * @throws \rex_sql_exception
     *
     * @return null|UrlManager
     */
    public static function resolveCurrent(): ?UrlManager
    {
        return UrlManager::resolveUrl(self::getCurrent());
    }

    // public static function parse($url)
    // {
    //     return new self($url);
    // }

    /**
     * @return self
     */
    protected function modifyPathSegments(array $arrayA, array $arrayB): self
    {
        $this->uri = $this->uri->withPathSegments(array_merge($arrayA, $arrayB));
        // Path neu setzen, da der Path den / am Anfang durch withPathSegments verloren hat
        $this->uri = $this->uri->withPath('/'.$this->uri->getPath());
        return $this;
    }

    protected function appendRewriterSuffix(): Uri
    {
        $this->removeRewriterSuffix();
        return $this->uri = $this->uri->withPath($this->uri->getPath().self::$rewriter->getSuffix());
    }

    /**
     * @return self
     */
    protected function removeRewriterSuffix(): self
    {
        $path = $this->uri->getPath();
        $suffix = self::$rewriter->getSuffix();
        if (strlen($suffix) !== 0 && substr($path, (strlen($suffix) * -1)) == $suffix) {
            $path = substr($path, 0, (strlen($suffix) * -1));
        }
        $this->uri = $this->uri->withPath($path);
        return $this;
    }

    private function normalize(array|string $sick, int $clangId = 1): array
    {
        if (is_string($sick)) {
            $sick = [$sick];
        }

        $suffix = self::$rewriter->getSuffix();
        foreach ($sick as $index => $value) {
            if (strlen($suffix) !== 0 && substr($value, (strlen($suffix) * -1)) == $suffix) {
                $value = substr($value, 0, (strlen($suffix) * -1));
            }
            $sick[$index] = self::$rewriter->normalize($value, $clangId);
        }
        return $sick;
    }
}
