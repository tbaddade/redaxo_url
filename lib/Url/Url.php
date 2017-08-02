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

use \Url\Rewriter\Rewriter;
use \Url\Rewriter\YRewrite;

class Url
{
    /**
     * @var \Url\Rewriter\Rewriter
     */
    private static $rewriter;

    const PATH_SEGMENT_SEPARATOR = '/';
    const WRITE_FLAG_AS_IS = 0;
    const WRITE_FLAG_OMIT_SCHEME = 1;
    const WRITE_FLAG_OMIT_HOST = 2;

    protected $original_url;

    protected $scheme;
    protected $user;
    protected $pass;
    protected $host;
    protected $port;
    protected $path;
    protected $query;
    protected $fragment;

    protected $query_array = array();

    public function __construct($url)
    {
        $this->original_url = trim($url);
        // Workaround: parse_url doesn't recognize host in protocol relative urls (starting with //)
        // so temporarily prepend "http:" for parsing and remove it later
        if ($this->isProtocolRelative()) {
            $url = 'http:' . $url;
        }
        $components = parse_url($url);
        if (isset($components['scheme']) && !$this->isProtocolRelative()) {
            $this->scheme = strtolower($components['scheme']);
        }
        if (isset($components['user'])) {
            $this->user = $components['user'];
        }
        if (isset($components['pass'])) {
            $this->pass = $components['pass'];
        }
        if (isset($components['host'])) {
            $this->host = strtolower($components['host']);
        }
        if (isset($components['port'])) {
            $this->port = intval($components['port']);
        }
        if (isset($components['path'])) {
            $this->path = self::normalizePath($components['path']);
        }
        if (isset($components['query'])) {
            $this->query = $components['query'];
        }
        if ($this->query != '') {
            parse_str($this->query, $this->query_array);
        }
        if (isset($components['fragment'])) {
            $this->fragment = $components['fragment'];
        }
    }

    /**
     * Check whether we have a URL in the narrower meaning as link to a document
     * with a path that has file system semantics
     * (as opposed to e.g. mailto:, javascript: and tel: URIs)
     *
     * <p>This function simply checks whether the scheme is http(s), ftp(s), file or empty.
     * (Since we are dealing with URLs in HTML pages we assume that if no scheme
     * is provided it is a relative HTTP-URL).</p>
     *
     * <p>This function is useful to filter out mailto: and other links
     * after finding all hrefs in a page and before calling
     * path manipulation functions like makeAbsolute() that make no sense on mailto-URIs:</p>
     * <code>
     *  $c = new \Symfony\Component\DomCrawler\Crawler($htmlcode, $pageurl);
     *  $links = $c->filter('a');
     *  foreach ($links as $elem) {
     *      $url = Url::parse($elem->getAttribute('href');
     *      if ($url->isUrl()) {
     *          echo (string) $url->makeAbsolute($pageurl); // convert relative links to absolute
     *      } else {
     *          echo (string) $url; // leave mailto:-links untouched
     *      }
     *  }
     * </code>
     *
     * @return bool
     */
    public function isUrl()
    {
        return ($this->scheme == ''
            || $this->scheme == 'http'
            || $this->scheme == 'https'
            || $this->scheme == 'ftp'
            || $this->scheme == 'ftps'
            || $this->scheme == 'file'
        );
    }

    /**
     * @return bool
     */
    public function isLocal()
    {
        return (substr($this->original_url, 0, 1) == '#');
    }


    /**
     * @return bool
     */
    public function isRelative()
    {
        return ($this->scheme == '' && $this->host == '' && substr($this->path, 0, 1) != '/');
    }

    /**
     * @return bool
     */
    public function isHostRelative()
    {
        return ($this->scheme == '' && $this->host == '' && substr($this->path, 0, 1) == '/');
    }

    /**
     * @return bool
     */
    public function isAbsolute()
    {
        return ($this->scheme != '');
    }

    /**
     * @return bool
     */
    public function isProtocolRelative()
    {
        return (substr($this->original_url, 0, 2) == '//');
    }

    /**
     * @return bool
     */
    public function isSecure()
    {
        return ('https' == $this->scheme && $this->port == 443);
    }

    /**
     * @return string
     */
    public function __toString() {
        return $this->write();
    }

    /**
     * Write out the url
     *
     * <p>With the $write_flags parameter one can force to output protocol-relative and
     * host-relative URLs from absolute URLs (a relative URl is always output as-is)</p>
     * <ul>
     *   <li>write(Url::WRITE_FLAG_OMIT_SCHEME) returns protocol-relative url</li>
     *   <li>write(Url::WRITE_FLAG_OMIT_SCHEME | Url::WRITE_FLAG_OMIT_HOST) returns host-relative url</li>
     * </ul>
     *
     * @param int $write_flags A combination of the WRITE_FLAG_* constants
     * @return string
     */
    public function write($write_flags = self::WRITE_FLAG_AS_IS)
    {
        $show_scheme = $this->scheme && (!($write_flags & self::WRITE_FLAG_OMIT_SCHEME));
        $show_host = $this->host && (!($write_flags & self::WRITE_FLAG_OMIT_HOST));
        $url = ($show_scheme ? $this->scheme . ':' : '');
        if ($show_host || $this->scheme == 'file') $url .= '//';
        if ($show_host) {
            if ($this->user) {
                $url .= $this->user . ($this->pass ? ':' . $this->pass : '') . '@';
            }
            $url .= $this->host;
            if ($this->port) {
                $url .= ':' . $this->port;
            }
        }
        $url .= ($this->path ? $this->path : '');
        $url .= ($this->query ? '?' . $this->query : '');
        $url .= ($this->fragment ? '#' . $this->fragment : '');
        return $url;
    }

    /**
     * @param string $fragment
     * @return Url $this
     */
    public function setFragment($fragment)
    {
        $this->fragment = $fragment;
        return $this;
    }

    /**
     * @return string
     */
    public function getFragment()
    {
        return $this->fragment;
    }

    /**
     * @param string $host
     * @return Url $this
     */
    public function setHost($host)
    {
        $this->host = strtolower($host);
        return $this;
    }

    /**
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }


    /**
     * Returns the HTTP host being requested.
     *
     * @return string
     */
    public function getHttpHost()
    {
        $scheme = $this->getScheme();
        $port   = $this->getPort();

        if (('http' == $scheme && $port == 80) || ('https' == $scheme && $port == 443)) {
            return $this->getHost();
        }

        return $this->getHost() . ($port != '' ? ':' . $port : '');
    }


    /**
     * Gets the scheme and HTTP host.
     *
     * @return string
     */
    public function getSchemeAndHttpHost()
    {
        return $this->getScheme() . '://' . $this->getHttpHost();
    }

    /**
     * @return string
     */
    public function getDomain()
    {
        return $this->getHost();
    }

    /**
     * @param $pass
     * @return Url $this
     */
    public function setPass($pass)
    {
        $this->pass = $pass;
        return $this;
    }

    /**
     * @return string
     */
    public function getPass()
    {
        return $this->pass;
    }

    /**
     * @param string $path
     * @return Url $this
     */
    public function setPath($path)
    {
        $this->path = static::normalizePath($path);
        return $this;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @param int $port
     */
    public function setPort($port)
    {
        $this->port = intval($port);
    }

    /**
     * @return int
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * Set the query from an already url encoded query string
     *
     * @param string $query The query string, must be already url encoded!!
     * @return Url $this
     */
    public function setQuery($query)
    {
        $this->query = $query;
        parse_str($this->query, $this->query_array);
        return $this;
    }

    /**
     * @return string The url encoded query string
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * @param string $scheme
     * @return Url $this
     */
    public function setScheme($scheme)
    {
        $this->scheme = strtolower($scheme);
        return $this;
    }

    /**
     * @return string
     */
    public function getScheme()
    {
        return $this->scheme;
    }

    /**
     * @param string $user
     * @return Url $this
     */
    public function setUser($user)
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @return string
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @return string
     */
    public function getFullUrl()
    {
        return $this->getSchemeAndHttpHost() . $this->getUrl();
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        $string = '';
        if ($this->getPath()) {
            $string .= $this->getPath();
        }
        if ($this->getQuery()) {
            $string .= '?' . $this->getQuery();
        }
        if ($this->getFragment()) {
            $string .= '#' . $this->getFragment();
        }

        return $string;
    }

    /**
     * Get the filename from the path (the last path segment as returned by basename())
     *
     * @return string
     */
    public function getFilename()
    {
        return static::filename($this->path);
    }

    /**
     * Get the directory name from the path
     *
     * @return string
     */
    public function getDirname()
    {
        return static::dirname($this->path);
    }

    public function appendPathSegment($segment)
    {
        if (substr($this->path, -1) != static::PATH_SEGMENT_SEPARATOR) {
            $this->path .= static::PATH_SEGMENT_SEPARATOR;
        }
        if (substr($segment, 0, 1) == static::PATH_SEGMENT_SEPARATOR) {
            $segment = substr($segment, 1);
        }
        $this->path .= $segment;
        return $this;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasQueryParameter($name)
    {
        return isset($this->query_array[$name]);
    }

    /**
     * @param string $name
     * @return string|null
     */
    public function getQueryParameter($name)
    {
        if (isset($this->query_array[$name])) {
            return $this->query_array[$name];
        } else {
            return null;
        }
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return Url $this
     */
    public function setQueryParameter($name, $value)
    {
        $this->query_array[$name] = $value;
        $this->query = http_build_query($this->query_array);
        return $this;
    }

    /**
     * @param array $query_array
     * @return Url $this
     */
    public function setQueryFromArray(array $query_array)
    {
        $this->query_array = $query_array;
        $this->query = http_build_query($this->query_array);
        return $this;
    }

    /**
     * Get the query parameters as array
     *
     * @return array
     */
    public function getQueryArray()
    {
        return $this->query_array;
    }

    /**
     * Make this (relative) URL absolute using another absolute base URL
     *
     * Does nothing if this URL is not relative
     *
     * @param Url|string|null $baseurl
     * @return Url $this
     */
    public function makeAbsolute($baseurl = null) {
        if (is_string($baseurl)) {
            $baseurl = new static($baseurl);
        }
        if ($this->isUrl() && $this->isRelative() && $baseurl instanceof Url) {
            $this->host = $baseurl->getHost();
            $this->scheme = $baseurl->getScheme();
            $this->user = $baseurl->getUser();
            $this->pass = $baseurl->getPass();
            $this->port = $baseurl->getPort();
            $this->path = static::buildAbsolutePath($this->path, $baseurl->getPath());
        }
        return $this;
    }

    /**
     * @param Url|string $another_url
     * @return bool
     */
    public function equals($another_url)
    {
        if (!($another_url instanceof Url)) {
            $another_url = new static($another_url);
        }
        return $this->getScheme() == $another_url->getScheme()
            && $this->getUser() == $another_url->getUser()
            && $this->getPass() == $another_url->getPass()
            && $this->equalsHost($another_url->getHost())
            && $this->getPort() == $another_url->getPort()
            && $this->equalsPath($another_url->getPath())
            && $this->equalsQuery($another_url->getQuery())
            && $this->getFragment() == $another_url->getFragment()
        ;
    }

    /**
     * @param string $another_path
     * @return bool
     */
    public function equalsPath($another_path)
    {
        return $this->getPath() == static::normalizePath($another_path);
    }

    /**
     * Check whether the path is within another path
     *
     * @param string $another_path
     * @return bool True if $this->path is a subpath of $another_path
     */
    public function isInPath($another_path)
    {
        $p = static::normalizePath($another_path);
        if ($p == $this->path) return true;
        if (substr($p, -1) != self::PATH_SEGMENT_SEPARATOR) $p .= self::PATH_SEGMENT_SEPARATOR;
        return (strlen($this->path) > $p && substr($this->path, 0, strlen($p)) == $p);
    }

    /**
     * @param string|array|Url $another_query
     * @return bool
     */
    public function equalsQuery($another_query)
    {
        $another_query_array = array();
        if (is_array($another_query)) {
            $another_query_array = $another_query;
        } elseif ($another_query instanceof Url) {
            $another_query_array = $another_query->getQueryArray();
        } else {
            parse_str((string) $another_query, $another_query_array);
        }
        return !count(array_diff_assoc($this->getQueryArray(), $another_query_array));
    }

    /**
     * @param string $another_hostname
     * @return bool
     */
    public function equalsHost($another_hostname)
    {
        // TODO: normalize IDN
        return $this->getHost() == strtolower($another_hostname);
    }

    /**
     * Build an absolute path from given relative path and base path
     *
     * @param string $relative_path
     * @param string $basepath
     * @return string
     */
    public static function buildAbsolutePath($relative_path, $basepath) {
        $basedir = static::dirname($basepath);
        if ($basedir == '.' || $basedir == '/' || $basedir == '\\' || $basedir == DIRECTORY_SEPARATOR) {
            $basedir = '';
        }
        return static::normalizePath($basedir . self::PATH_SEGMENT_SEPARATOR . $relative_path);
    }

    /**
     * @param string $path
     * @return string
     */
    public static function normalizePath($path)
    {
        $path = preg_replace('|/\./|', '/', $path);   // entferne /./
        $path = preg_replace('|^\./|', '', $path);    // entferne ./ am Anfang
        $i = 0;
        while (preg_match('|[^/]+/\.{2}/|', $path) && $i < 10) {
            $path = preg_replace('|([^/]+)(/\.{2}/)|e', "'\\1'=='..'?'\\0':''", $path);
            $i++;
        }
        return $path;
    }

    /**
     * @param $path
     * @return string
     */
    public static function filename($path)
    {
        if (substr($path, -1) == self::PATH_SEGMENT_SEPARATOR) {
            return '';
        } else {
            return basename($path);
        }
    }

    public static function dirname($path)
    {
        if (substr($path, -1) == self::PATH_SEGMENT_SEPARATOR) {
            return substr($path, 0, -1);
        } else {
            $d = dirname($path);
            if ($d == DIRECTORY_SEPARATOR) {
                $d = self::PATH_SEGMENT_SEPARATOR;
            }
            return $d;
        }
    }

    public static function current()
    {
        $secure = (! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? true : false;
        $scheme = substr(strtolower($_SERVER['SERVER_PROTOCOL']), 0, strpos(strtolower($_SERVER['SERVER_PROTOCOL']), '/')) . (($secure) ? 's' : '');
        $current = $scheme . '://' . $_SERVER['HTTP_HOST'] . ':' . $_SERVER['SERVER_PORT'] . $_SERVER['REQUEST_URI'];
        return static::parse($current);
    }


    public static function previous()
    {
        $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';

        $url = static::parse($referer);

        if ($url->getScheme() == 'http') {
            $url->setPort(80);
        } elseif ($url->getScheme() == 'https') {
            $url->setPort(443);
        }

        return $url;
    }

    /**
     * @param string $url
     *
     * @return static
     */
    public static function parse($url)
    {
        return new static($url);
    }


    public static function boot()
    {
        if (null === self::$rewriter) {
            if (\rex_addon::get('yrewrite')->isAvailable()) {
                self::setRewriter(new YRewrite());
            } else {
                if (\rex_be_controller::getCurrentPage() == 'packages') {
                    \rex_extension::register('PAGE_TITLE_SHOWN', function (\rex_extension_point $ep) {
                        return $ep->setSubject(\rex_view::error('<h4>Url Addon:</h4><p>Please install a rewriter addon or deactivate the Url AddOn.</p>'));
                    });
                }
            }
        }
    }

    public static function getRewriter()
    {
        return self::$rewriter;
    }

    public static function setRewriter(Rewriter $rewriter)
    {
        self::$rewriter = $rewriter;
    }
}
