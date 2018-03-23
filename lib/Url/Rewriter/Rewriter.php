<?php

/**
 * This file is part of the Url package.
 *
 * @author (c) Thomas Blum <thomas@addoff.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Url\Rewriter;

abstract class Rewriter
{
    /**
     * @return string
     */
    abstract function articleIdNotFound();

    /**
     * @return string
     */
    abstract function getSitemapExtensionPoint();

    /**
     * @return array
     */
    abstract function getSitemapFrequency();

    /**
     * @return array
     */
    abstract function getSitemapPriority();

    /**
     * @return object
     */
    abstract function getSeoInstance();

    /**
     * @return string
     */
    abstract function getSeoTitleTagMethod();

    /**
     * @return string
     */
    abstract function getSeoDescriptionTagMethod();

    /**
     * @return string
     */
    abstract function getSeoCanonicalTagMethod();

    /**
     * @return string
     */
    abstract function getSeoHreflangTagsMethod();

    /**
     * @return string
     */
    abstract function getSeoRobotsTagMethod();


    /**
     * @param int $article_id
     * @param int $clang_id
     *
     * @return string
     */
    abstract function getFullUrl($article_id, $clang_id);

    /**
     * @param string $path
     *
     * @return string
     */
    abstract function getFullPath($path);

    /**
     * @return string
     */
    abstract function getSuffix();

    /**
     * @param string $domain
     *
     * @return string
     */
    abstract function getSchemeByDomain($domain);

    /**
     * @return bool
     */
    abstract function isHttps();

    /**
     * @param string $string
     *
     * @return string
     */
    abstract function normalize($string);


}
