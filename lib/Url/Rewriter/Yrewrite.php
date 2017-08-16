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

use Url\Generator;

class Yrewrite extends Rewriter
{
    /**
     * @return string
     */
    public function articleIdNotFound()
    {
        \rex_extension::register('YREWRITE_PREPARE', function (\rex_extension_point $ep) {
            return Generator::getArticleParams();
        }, \rex_extension::EARLY);
    }

    /**
     * @return string
     */
    public function getSitemapExtensionPoint()
    {
        return 'YREWRITE_SITEMAP';
    }

    /**
     * @return array
     */
    public function getSitemapFrequency()
    {
        return \rex_yrewrite_seo::$changefreq;
    }

    /**
     * @return array
     */
    public function getSitemapPriority()
    {
        return \rex_yrewrite_seo::$priority;
    }

    /**
     * @return object
     */
    public function getSeoInstance()
    {
        return (new \rex_yrewrite_seo());
    }

    /**
     * @return string
     */
    public function getSeoTitleTagMethod()
    {
        return 'getTitleTag';
    }

    /**
     * @return string
     */
    public function getSeoDescriptionTagMethod()
    {
        return 'getDescriptionTag';
    }

    /**
     * @return string
     */
    public function getSeoCanonicalTagMethod()
    {
        return 'getCanonicalUrlTag';
    }

    /**
     * @return string
     */
    public function getSeoHreflangTagsMethod()
    {
        return 'getHreflangTags';
    }

    /**
     * @return string
     */
    public function getSeoRobotsTagMethod()
    {
        return 'getRobotsTag';
    }



    /**
     * @return string
     */
    public function getFullUrl($article_id, $clang_id)
    {
        return \rex_yrewrite::getFullUrlByArticleId($article_id, $clang_id);
    }

    /**
     * @return string
     */
    public function getFullPath($path)
    {
        return \rex_yrewrite::getFullPath($path);
    }

    /**
     * @return string
     */
    public function getSuffix()
    {
        $scheme = \rex_yrewrite::getScheme();
        return $scheme->getSuffix();
    }

    /**
     * @param string $string
     * @param int    $clang
     *
     * @return string
     */
    public function normalize($string, $clang = 0)
    {
        $scheme = \rex_yrewrite::getScheme();
        return $scheme->normalize($string, $clang);
    }
}
