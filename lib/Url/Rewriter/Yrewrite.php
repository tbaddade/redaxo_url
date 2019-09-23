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

use Url\UrlManager;

class Yrewrite extends Rewriter
{
    /**
     * @return string
     */
    public function articleIdNotFound()
    {
        \rex_extension::register('YREWRITE_PREPARE', function (\rex_extension_point $ep) {
            return UrlManager::getArticleParams();
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
     * @return \rex_yrewrite_seo
     */
    public function getSeoInstance()
    {
        return new \rex_yrewrite_seo();
    }

    /**
     * @return string
     */
    public function getSeoTags()
    {
        return '';
    }

    /**
     * @param int $article_id
     * @param int $clang_id
     *
     * @return string
     */
    public function getFullUrl($article_id, $clang_id)
    {
        return \rex_yrewrite::getFullUrlByArticleId($article_id, $clang_id);
    }

    /**
     * @param string $path
     *
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
     * @param string $domain
     *
     * @return string
     */
    public function getSchemeByDomain($domain)
    {
        $domain = \rex_yrewrite::getDomainByName($domain);
        if (!$domain) {
            return null;
        }
        return $domain->getScheme();
    }

    /**
     * @return bool
     */
    public function isHttps()
    {
        return \rex_yrewrite::isHttps();
    }

    /**
     * @param string $string
     * @param int    $clang
     *
     * @return string
     */
    public function normalize($string, $clang = 1)
    {
        $scheme = \rex_yrewrite::getScheme();
        return $scheme->normalize($string, $clang);
    }
}
