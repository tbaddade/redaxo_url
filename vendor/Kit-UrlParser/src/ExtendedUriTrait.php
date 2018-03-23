<?php

namespace Riimu\Kit\UrlParser;

/**
 * Provides convenience methods for accessing the standard URI interface.
 *
 * ExtendedUriTrait provides additional methods for common use cases that build
 * on top the standard URI interface. Note that due to RFC 3986 compliance, the
 * methods do not treat the plus sign as a space character.
 *
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2015-2017 Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
trait ExtendedUriTrait
{
    /** @var array<string,int> List of known ports for different schemes */
    private static $standardPorts = [
        'ftp'   => 21,
        'http'  => 80,
        'https' => 443,
    ];

    /**
     * Returns the scheme component of the URI.
     * @return string The URI scheme
     */
    abstract public function getScheme();

    /**
     * Returns the standard port for the current scheme.
     *
     * The known ports are:
     *
     * - ftp : 21
     * - http : 80
     * - https : 443
     *
     * @return int|null The standard port for the current scheme or null if not known
     */
    public function getStandardPort()
    {
        $scheme = $this->getScheme();

        if (isset(self::$standardPorts[$scheme])) {
            return self::$standardPorts[$scheme];
        }

        return null;
    }

    /**
     * Returns the user information component of the URI.
     * @return string The URI user information
     */
    abstract public function getUserInfo();

    /**
     * Returns the decoded username from the URI.
     * @return string The decoded username
     */
    public function getUsername()
    {
        $info = $this->getUserInfo();
        $username = strstr($info, ':', true);

        return rawurldecode($username === false ? $info : $username);
    }

    /**
     * Returns the decoded password from the URI.
     * @return string The decoded password
     */
    public function getPassword()
    {
        $password = strstr($this->getUserInfo(), ':');

        return $password === false ? '' : rawurldecode(substr($password, 1));
    }

    /**
     * Returns the host component of the URI.
     * @return string The URI host
     */
    abstract public function getHost();

    /**
     * Returns the IP address from the host component.
     * @return string|null IP address from the host or null if the host is not an IP address
     */
    public function getIpAddress()
    {
        $pattern = new UriPattern();
        $pattern->matchHost($this->getHost(), $match);

        if (isset($match['IPv4address'])) {
            return $match['IPv4address'];
        } elseif (isset($match['IP_literal'])) {
            return preg_replace('/^\\[(v[^.]+\\.)?([^\\]]+)\\]$/', '$2', $match['IP_literal']);
        }

        return null;
    }

    /**
     * Returns the top level domain from the host component.
     *
     * Note that if the host component represents an IP address, an empty string
     * will be returned instead. Additionally, if the host component ends in a
     * period, the section prior that period will be returned instead. If no
     * period is present in the host component, the entire host component will
     * be returned.
     *
     * @return string The top level domain or an empty string, if no TLD is present
     */
    public function getTopLevelDomain()
    {
        if ($this->getIpAddress() !== null) {
            return '';
        }

        $host = rawurldecode($this->getHost());
        $tld = strrchr($host, '.');

        if ($tld === '.') {
            $host = substr($host, 0, -1);
            $tld = strrchr($host, '.');
        }

        return $tld === false ? $host : substr($tld, 1);
    }

    /**
     * Returns the path component of the URI.
     * @return string The URI path
     */
    abstract public function getPath();

    /**
     * Returns the decoded path segments from the path component.
     * @return string[] The decoded non empty path segments
     */
    public function getPathSegments()
    {
        return array_values(array_map(
            'rawurldecode',
            array_filter(explode('/', $this->getPath()), 'strlen')
        ));
    }

    /**
     * Returns the file extension for the last segment in the path.
     * @return string The file extension from the last non empty segment
     */
    public function getPathExtension()
    {
        $segments = $this->getPathSegments();
        $filename = array_pop($segments);
        $extension = strrchr($filename, '.');

        if ($extension === false) {
            return '';
        }

        return substr($extension, 1);
    }

    /**
     * Returns the query string of the URI.
     * @return string The URI query string
     */
    abstract public function getQuery();

    /**
     * Returns the decoded parameters parsed from the query component.
     * @return array The decoded parameters parsed from the query
     */
    public function getQueryParameters()
    {
        parse_str(str_replace('+', '%2B', $this->getQuery()), $parameters);

        return $parameters ? $parameters : [];
    }

    /**
     * Returns a URI instance with the specified path.
     * @param string $path The path to use with the new instance
     * @return static A new instance with the specified path
     */
    abstract public function withPath($path);

    /**
     * Returns a URI instance with path constructed from given path segments.
     *
     * Note that all the segments are assumed to be decoded. Thus any percent
     * encoded characters in the segments will be double encoded. Due to
     * aggressive encoding, this method will encode even the forward slashes in
     * the provided segments.
     *
     * @param string[] $segments Path segments for the new path
     * @return static A new instance with the specified path
     */
    public function withPathSegments(array $segments)
    {
        return $this->withPath(
            implode('/', array_map('rawurlencode', array_filter($segments, 'strlen')))
        );
    }

    /**
     * Returns a URI instance with the specified query string.
     * @param string $query The query string to use with the new instance
     * @return static A new instance with the specified query string
     */
    abstract public function withQuery($query);

    /**
     * Returns a URI instance with the query constructed from the given parameters.
     *
     * The provided associative array will be used to construct the query
     * string. Even characters such as the ampersand and equal sign will be
     * encoded in resulting string. Note the any percent encoded characters will
     * be double encoded, since this method assumes that all the values are
     * unencoded.
     *
     * @param array $parameters Parameters for the query
     * @return static A new instance with the specified query string
     */
    public function withQueryParameters(array $parameters)
    {
        return $this->withQuery(http_build_query($parameters, '', '&', PHP_QUERY_RFC3986));
    }
}
