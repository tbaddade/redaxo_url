# Changelog #

## v2.2.0 (2022-12-13) ##

  * Add support for PHP 8.2
  * Fix passing nulls to string functions
  * Deprecated `UriParser::MODE_IDNA2003`
    * Added `UriParser::MODE_IDNA`, which should be used instead
    * Internally, the parser will now use UTS46 mode whenever it is available
  * Minor bundled autoloader improvements
  * Address minor code quality issues in tests
  * Update CI to use Github Actions

## v2.1.0 (2017-06-30) ##

  * Increase the minimum PHP version requirement to 5.6
  * Update to latest coding standards
  * Update tests to work with PHPUnit 6
  * Update travis build to test for PHP 7.1

## v2.0.2 (2015-11-08) ##

  * Address minor coding standards issues

## v2.0.1 (2015-10-04) ##

  * Fix forward slash normalization in some URIs in PHP7
  * Fix several methods accidentally accepting string arrays

## v2.0.0 (2015-09-11) ##

  * The `UrlInfo` has been renamed to`Uri`
  * The `UrlParser` has been renamed to `UriParser`
  * Some methods and return values have changed to provide a more unified API
  * The `Uri` component now has methods for modifying the URI
  * The `Uri` component now conforms to the PSR-7 `UriInterface`
  * The `Uri` component can now take the URI as a constructor parameter
  * The parser now has optional parsing modes to allow UTF-8 and IDNs.

## v1.1.0 (2015-01-12) ##

  * Improvements in code quality and documentation
  * Added UrlInfo::getDefaultPort()
  * Added UrlInfo::getFileExtension()

## v1.0.3 (2014-06-01) ##

  * Code cleanup and documentation fixes
