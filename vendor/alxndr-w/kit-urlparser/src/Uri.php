<?php

namespace Riimu\Kit\UrlParser;

use Psr\Http\Message\UriInterface;

/**
 * Immutable value object that represents a RFC3986 compliant URI.
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2015-2017 Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class Uri implements UriInterface
{
    use ExtendedUriTrait;

    /** @var string The scheme component of the URI */
    private $scheme = '';

    /** @var string The user information component of the URI */
    private $userInfo = '';

    /** @var string The host component of the URI */
    private $host = '';

    /** @var int|null The port component of the URI or null for none */
    private $port = null;

    /** @var string The path component of the URI */
    private $path = '';

    /** @var string The query component of the URI */
    private $query = '';

    /** @var string The fragment component of the URI */
    private $fragment = '';

    /**
     * Creates a new instance of Uri.
     * @param string $uri The URI provided as a string or empty string for none
     * @param int $mode The parser mode used to parse the provided URI
     * @throws \InvalidArgumentException If the provided URI is invalid
     */
    public function __construct($uri = '', $mode = UriParser::MODE_RFC3986)
    {
        $uri = (string) $uri;

        if ($uri !== '') {
            $parser = new UriParser();
            $parser->setMode($mode);
            $parsed = $parser->parse($uri);

            if (!$parsed instanceof self) {
                throw new \InvalidArgumentException("Invalid URI '$uri'");
            }

            $properties = get_object_vars($parsed);
            array_walk($properties, function ($value, $name) {
                $this->$name = $value;
            });
        }
    }

    /**
     * Returns the scheme component of the URI.
     *
     * Note that the returned value will always be normalized to lowercase,
     * as per RFC 3986 Section 3.1. If no scheme has been provided, an empty
     * string will be returned instead.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-3.1
     * @return string The URI scheme or an empty string if no scheme has been provided
     */
    public function getScheme() :string
    {
        return $this->scheme;
    }

    /**
     * Returns the authority component of the URI.
     *
     * If no authority information has been provided, an empty string will be
     * returned instead. Note that the host component in the authority component
     * will always be normalized to lowercase as per RFC 3986 Section 3.2.2.
     *
     * Also note that even if a port has been provided, but it is the standard port
     * for the current scheme, the port will not be included in the returned value.
     *
     * The format of the returned value is `[user-info@]host[:port]`
     *
     * @see https://tools.ietf.org/html/rfc3986#section-3.2
     * @return string The URI authority or an empty string if no authority information has been provided
     */
    public function getAuthority() :string
    {
        return $this->constructString([
            '%s%s@' => $this->getUserInfo(),
            '%s%s' => $this->getHost(),
            '%s:%s' => $this->getPort(),
        ]);
    }

    /**
     * Returns the user information component of the URI.
     *
     * The user information component contains the username and password in the
     * URI separated by a colon. If no username has been provided, an empty
     * string will be returned instead. If no password has been provided, the returned
     * value will only contain the username without the delimiting colon.
     *
     * @see http://tools.ietf.org/html/rfc3986#section-3.2.1
     * @return string The URI user information or an empty string if no username has been provided
     */
    public function getUserInfo() :string
    {
        return $this->userInfo;
    }

    /**
     * Returns the host component of the URI.
     *
     * Note that the returned value will always be normalized to lowercase,
     * as per RFC 3986 Section 3.2.2. If no host has been provided, an empty
     * string will be returned instead.
     *
     * @see http://tools.ietf.org/html/rfc3986#section-3.2.2
     * @return string The URI host or an empty string if no host has been provided
     */
    public function getHost() :string
    {
        return $this->host;
    }

    /**
     * Returns the port component of the URI.
     *
     * If no port has been provided, this method will return a null instead.
     * Note that this method will also return a null, if the provided port is
     * the standard port for the current scheme.
     *
     * @see http://tools.ietf.org/html/rfc3986#section-3.2.3
     * @return int|null The URI port or null if no port has been provided
     */
    public function getPort() :?int
    {
        if ($this->port === $this->getStandardPort()) {
            return null;
        }

        return $this->port;
    }

    /**
     * Returns the path component of the URI.
     * @see https://tools.ietf.org/html/rfc3986#section-3.3
     * @return string The URI path or an empty string if no path has been provided
     */
    public function getPath() :string
    {
        return $this->path;
    }

    /**
     * Returns the query string of the URI.
     * @see https://tools.ietf.org/html/rfc3986#section-3.4
     * @return string The URI query string or an empty string if no query has been provided
     */
    public function getQuery() :string
    {
        return $this->query;
    }

    /**
     * Returns the fragment component of the URI.
     * @see https://tools.ietf.org/html/rfc3986#section-3.5
     * @return string The URI fragment or an empty string if no fragment has been provided
     */
    public function getFragment() :string
    {
        return $this->fragment;
    }

    /**
     * Returns a URI instance with the specified scheme.
     *
     * This method allows all different kinds of schemes. Note, however, that
     * the different components are only validated based on the generic URI
     * syntax. No additional validation is performed based on the scheme. An
     * empty string can be used to remove the scheme. Note that the provided
     * scheme will be normalized to lowercase.
     *
     * @param string $scheme The scheme to use with the new instance
     * @return static A new instance with the specified scheme
     * @throws \InvalidArgumentException If the scheme is invalid
     */
    public function withScheme($scheme) :UriInterface
    {
        $scheme = strtolower($scheme);
        $pattern = new UriPattern();

        if (strlen($scheme) === 0 || $pattern->matchScheme($scheme)) {
            return $this->with('scheme', $scheme);
        }

        throw new \InvalidArgumentException("Invalid scheme '$scheme'");
    }

    /**
     * Returns a URI instance with the specified user information.
     *
     * Note that the password is optional, but unless an username is provided,
     * the password will be ignored. Note that this method assumes that neither
     * the username nor the password contains encoded characters. Thus, all
     * encoded characters will be double encoded, if present. An empty username
     * can be used to remove the user information.
     *
     * @param string $user The username to use for the authority component
     * @param string|null $password The password associated with the user
     * @return static A new instance with the specified user information
     */
    public function withUserInfo($user, $password = null) :UriInterface
    {
        $username = rawurlencode($user);

        if (strlen($username) > 0) {
            return $this->with('userInfo', $this->constructString([
                '%s%s' => $username,
                '%s:%s' => rawurlencode((string) $password),
            ]));
        }

        return $this->with('userInfo', '');
    }

    /**
     * Returns a URI instance with the specified host.
     *
     * An empty host can be used to remove the host. Note that since host names
     * are treated in a case insensitive manner, the host will be normalized
     * to lowercase. This method does not support international domain names and
     * hosts with non ascii characters are considered invalid.
     *
     * @param string $host The hostname to use with the new instance
     * @return static A new instance with the specified host
     * @throws \InvalidArgumentException If the hostname is invalid
     */
    public function withHost($host) :UriInterface
    {
        $pattern = new UriPattern();

        if ($pattern->matchHost($host)) {
            return $this->with('host', $this->normalize(strtolower($host)));
        }

        throw new \InvalidArgumentException("Invalid host '$host'");
    }

    /**
     * Returns a URI instance with the specified port.
     *
     * A null value can be used to remove the port number. Note that if an
     * invalid port number is provided (a number less than 0 or more than
     * 65535), an exception will be thrown.
     *
     * @param int|null $port The port to use with the new instance
     * @return static A new instance with the specified port
     * @throws \InvalidArgumentException If the port is invalid
     */
    public function withPort($port) :UriInterface
    {
        if ($port !== null) {
            $port = (int) $port;

            if (max(0, min(65535, $port)) !== $port) {
                throw new \InvalidArgumentException("Invalid port number '$port'");
            }
        }

        return $this->with('port', $port);
    }

    /**
     * Returns a URI instance with the specified path.
     *
     * The provided path may or may not begin with a forward slash. The path
     * will be automatically normalized with the appropriate number of slashes
     * once the string is generated from the Uri instance. An empty string can
     * be used to remove the path. The path may also contain percent encoded
     * characters as these characters will not be double encoded.
     *
     * @param string $path The path to use with the new instance
     * @return static A new instance with the specified path
     */
    public function withPath($path) :UriInterface
    {
        return $this->with('path', $this->encode($path, '@/'));
    }

    /**
     * Returns a URI instance with the specified query string.
     *
     * An empty string can be used to remove the query string. The provided
     * value may contain both encoded and unencoded characters. Encoded
     * characters will not be double encoded.
     *
     * @param string $query The query string to use with the new instance
     * @return static A new instance with the specified query string
     */
    public function withQuery($query) :UriInterface
    {
        return $this->with('query', $this->encode($query, ':@/?'));
    }

    /**
     * Returns a URI instance with the specified URI fragment.
     *
     * An empty string can be used to remove the fragment. The provided value
     * may contain both encoded and unencoded characters. Encoded characters
     * will not be double encoded.
     *
     * @param string $fragment The fragment to use with the new instance
     * @return static A new instance with the specified fragment
     */
    public function withFragment($fragment) :UriInterface
    {
        return $this->with('fragment', $this->encode($fragment, ':@/?'));
    }

    /**
     * Returns an Uri instance with the given value.
     * @param string $variable Name of the variable to change
     * @param mixed $value New value for the variable
     * @return static A new or the same instance depending on if the value changed
     */
    private function with($variable, $value)
    {
        if ($value === $this->$variable) {
            return $this;
        }

        $uri = clone $this;
        $uri->$variable = $value;

        return $uri;
    }

    /**
     * Percent encodes the value without double encoding.
     * @param string $string The value to encode
     * @param string $extra Additional allowed characters in the value
     * @return string The encoded string
     */
    private function encode($string, $extra = '')
    {
        $pattern = sprintf(
            '/[^0-9a-zA-Z%s]|%%(?![0-9A-F]{2})/',
            preg_quote("%-._~!$&'()*+,;=" . $extra, '/')
        );

        return preg_replace_callback($pattern, function ($match) {
            return sprintf('%%%02X', ord($match[0]));
        }, $this->normalize($string));
    }

    /**
     * Normalizes the percent encoded characters to upper case.
     * @param string $string The string to normalize
     * @return string String with percent encodings normalized to upper case
     */
    private function normalize($string)
    {
        return preg_replace_callback(
            '/%(?=.?[a-f])[0-9a-fA-F]{2}/',
            function ($match) {
                return strtoupper($match[0]);
            },
            (string) $string
        );
    }

    /**
     * Returns the string representation of the URI.
     *
     * The resulting URI will be composed of the provided components. All
     * components that have not been provided will be omitted from the generated
     * URI. The provided path will be normalized based on whether the authority
     * is included in the URI or not.
     *
     * @return string The string representation of the URI
     */
    public function __toString()
    {
        return $this->constructString([
            '%s%s:' => $this->getScheme(),
            '%s//%s' => $this->getAuthority(),
            '%s%s' => $this->getNormalizedUriPath(),
            '%s?%s' => $this->getQuery(),
            '%s#%s' => $this->getFragment(),
        ]);
    }

    /**
     * Constructs the string from the non empty parts with specific formats.
     * @param array $components Associative array of formats and values
     * @return string The constructed string
     */
    private function constructString(array $components)
    {
        $formats = array_keys($components);
        $values = array_values($components);
        $keys = array_keys(array_filter($values, function ($value) {
            return $value !== null && $value !== '';
        }));

        return array_reduce($keys, function ($string, $key) use ($formats, $values) {
            return sprintf($formats[$key], $string, $values[$key]);
        }, '');
    }

    /**
     * Returns the path normalized for the string representation.
     * @return string The normalized path for the string representation
     */
    private function getNormalizedUriPath()
    {
        $path = $this->getPath();

        if ($this->getAuthority() === '') {
            return preg_replace('#^/+#', '/', $path);
        } elseif ($path === '' || $path[0] === '/') {
            return $path;
        }

        return '/' . $path;
    }
}
