<?php

namespace Riimu\Kit\UrlParser;

/**
 * Provides PCRE based matching for URIs.
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2015-2017 Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class UriPattern
{
    /** @var string PCRE pattern that conforms to the URI ABNF */
    private static $absoluteUri;

    /** @var string PCRE pattern that conforms to the relative-ref ABNF */
    private static $relativeUri;

    /** @var string PCRE pattern that conforms to the scheme ABNF */
    private static $scheme;

    /** @var string PCRE pattern that conforms to the host ABNF */
    private static $host;

    /** @var bool Whether non ascii characters are allowed or not */
    private $allowNonAscii;

    /**
     * Creates a new instance of UriPattern.
     */
    public function __construct()
    {
        $this->allowNonAscii = false;

        if (!isset(self::$absoluteUri)) {
            $this->buildPatterns();
        }
    }

    /**
     * Allows or forbids non ascii characters in some parts of the URI.
     *
     * When enabled, non ascii characters are allowed in `userinfo`, `reg_name`,
     * `path`, `query` and `fragment` parts of the URI. Note that pattern does
     * not verify whether the bytes actually form valid UTF-8 sequences or not.
     * Enabling this option simply allows bytes within the range of `x80-xFF`.
     *
     * @param bool $enabled True to allow, false to forbid
     */
    public function allowNonAscii($enabled = true)
    {
        $this->allowNonAscii = (bool) $enabled;
    }

    /**
     * Matches the string against URI or relative-ref ABNF.
     * @param string $uri The string to match
     * @param array $matches Provides the matched sub sections from the match
     * @return bool True if the URI matches, false if not
     */
    public function matchUri($uri, & $matches = [])
    {
        if ($this->matchAbsoluteUri($uri, $matches)) {
            return true;
        }

        return $this->matchRelativeUri($uri, $matches);
    }

    /**
     * Matches the string against the URI ABNF.
     * @param string $uri The string to match
     * @param array $matches Provides the matched sub sections from the match
     * @return bool True if the URI matches, false if not
     */
    public function matchAbsoluteUri($uri, & $matches = [])
    {
        return $this->match(self::$absoluteUri, $uri, $matches);
    }

    /**
     * Matches the string against the relative-ref ABNF.
     * @param string $uri The string to match
     * @param array $matches Provides the matched sub sections from the match
     * @return bool True if the URI matches, false if not
     */
    public function matchRelativeUri($uri, & $matches = [])
    {
        return $this->match(self::$relativeUri, $uri, $matches);
    }

    /**
     * Matches the string against the scheme ABNF.
     * @param string $scheme The string to match
     * @param array $matches Provides the matched sub sections from the match
     * @return bool True if the scheme matches, false if not
     */
    public function matchScheme($scheme, & $matches = [])
    {
        return $this->match(self::$scheme, $scheme, $matches);
    }

    /**
     * Matches the string against the host ABNF.
     * @param string $host The string to match
     * @param array $matches Provides the matched sub sections from the match
     * @return bool True if the host matches, false if not
     */
    public function matchHost($host, & $matches = [])
    {
        return $this->match(self::$host, $host, $matches);
    }

    /**
     * Matches the subject against the pattern and provides the literal sub patterns.
     * @param string $pattern The pattern to use for matching
     * @param string $subject The subject to match
     * @param array $matches The provided list of literal sub patterns
     * @return bool True if the pattern matches, false if not
     */
    private function match($pattern, $subject, & $matches)
    {
        $matches = [];
        $subject = (string) $subject;

        if ($this->allowNonAscii || preg_match('/^[\\x00-\\x7F]*$/', $subject)) {
            if (preg_match($pattern, $subject, $match)) {
                $matches = $this->getNamedPatterns($match);

                return true;
            }
        }

        return false;
    }

    /**
     * Returns nonempty named sub patterns from the match set.
     * @param array $matches Sub pattern matches
     * @return array<string,string> Nonempty named sub pattern matches
     */
    private function getNamedPatterns($matches)
    {
        foreach ($matches as $key => $value) {
            if (!is_string($key) || strlen($value) < 1) {
                unset($matches[$key]);
            }
        }

        return $matches;
    }

    /**
     * Builds the PCRE patterns according to the ABNF definitions.
     */
    private static function buildPatterns()
    {
        $alpha = 'A-Za-z';
        $digit = '0-9';
        $hex = $digit . 'A-Fa-f';
        $unreserved = "$alpha$digit\\-._~";
        $delimiters = "!$&'()*+,;=";
        $utf8 = '\\x80-\\xFF';

        $octet = "(?:[$digit]|[1-9][$digit]|1[$digit]{2}|2[0-4]$digit|25[0-5])";
        $ipv4address = "(?>$octet\\.$octet\\.$octet\\.$octet)";

        $encoded = "%[$hex]{2}";
        $h16 = "[$hex]{1,4}";
        $ls32 = "(?:$h16:$h16|$ipv4address)";

        $data = "[$unreserved$delimiters:@$utf8]++|$encoded";

        // Defining the scheme
        $scheme = "(?'scheme'(?>[$alpha][$alpha$digit+\\-.]*+))";

        // Defining the authority
        $ipv6address = "(?'IPv6address'" .
            "(?:(?:$h16:){6}$ls32)|" .
            "(?:::(?:$h16:){5}$ls32)|" .
            "(?:(?:$h16)?::(?:$h16:){4}$ls32)|" .
            "(?:(?:(?:$h16:){0,1}$h16)?::(?:$h16:){3}$ls32)|" .
            "(?:(?:(?:$h16:){0,2}$h16)?::(?:$h16:){2}$ls32)|" .
            "(?:(?:(?:$h16:){0,3}$h16)?::$h16:$ls32)|" .
            "(?:(?:(?:$h16:){0,4}$h16)?::$ls32)|" .
            "(?:(?:(?:$h16:){0,5}$h16)?::$h16)|" .
            "(?:(?:(?:$h16:){0,6}$h16)?::))";

        $regularName = "(?'reg_name'(?>(?:[$unreserved$delimiters$utf8]++|$encoded)*))";

        $ipvFuture = "(?'IPvFuture'v[$hex]++\\.[$unreserved$delimiters:]++)";
        $ipLiteral = "(?'IP_literal'\\[(?>$ipv6address|$ipvFuture)\\])";

        $port = "(?'port'(?>[$digit]*+))";
        $host = "(?'host'$ipLiteral|(?'IPv4address'$ipv4address)|$regularName)";
        $userInfo = "(?'userinfo'(?>(?:[$unreserved$delimiters:$utf8]++|$encoded)*))";
        $authority = "(?'authority'(?:$userInfo@)?$host(?::$port)?)";

        // Defining the path
        $segment = "(?>(?:$data)*)";
        $segmentNotEmpty = "(?>(?:$data)+)";
        $segmentNoScheme = "(?>([$unreserved$delimiters@$utf8]++|$encoded)+)";

        $pathAbsoluteEmpty = "(?'path_abempty'(?:/$segment)*)";
        $pathAbsolute = "(?'path_absolute'/(?:$segmentNotEmpty(?:/$segment)*)?)";
        $pathNoScheme = "(?'path_noscheme'$segmentNoScheme(?:/$segment)*)";
        $pathRootless = "(?'path_rootless'$segmentNotEmpty(?:/$segment)*)";
        $pathEmpty = "(?'path_empty')";

        // Defining other parts
        $query = "(?'query'(?>(?:$data|[/?])*))";
        $fragment = "(?'fragment'(?>(?:$data|[/?])*))";

        $absolutePath = "(?'hier_part'//$authority$pathAbsoluteEmpty|$pathAbsolute|$pathRootless|$pathEmpty)";
        $relativePath = "(?'relative_part'//$authority$pathAbsoluteEmpty|$pathAbsolute|$pathNoScheme|$pathEmpty)";

        self::$absoluteUri = "#^$scheme:$absolutePath(?:\\?$query)?(?:\\#$fragment)?$#";
        self::$relativeUri = "#^$relativePath(?:\\?$query)?(?:\\#$fragment)?$#";
        self::$scheme = "#^$scheme$#";
        self::$host = "#^$host$#";
    }
}
