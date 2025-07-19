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
    public function articleIdNotFound(): string
    {
        \rex_extension::register('YREWRITE_PREPARE', function (\rex_extension_point $ep) {
            return UrlManager::getArticleParams();
        }, \rex_extension::EARLY);
        return '';
    }

    public function getDomainByArticleId($articleId)
    {
        return \rex_yrewrite::getDomainByArticleId($articleId);
    }

    public function getCurrentDomain()
    {
        return \rex_yrewrite::getCurrentDomain();
    }

    /**
     * @return string
     */
    public function getSitemapExtensionPoint(): string
    {
        return 'YREWRITE_SITEMAP';
    }

    /**
     * @return array
     */
    public function getSitemapFrequency(): array
    {
        return \rex_yrewrite_seo::$changefreq;
    }

    /**
     * @return array
     */
    public function getSitemapPriority(): array
    {
        return \rex_yrewrite_seo::$priority;
    }

    /**
     * @return \rex_yrewrite_seo
     */
    public function getSeoInstance(): \rex_yrewrite_seo
    {
        return new \rex_yrewrite_seo();
    }

    /**
     * @return string
     */
    public function getSeoTags(): string
    {
        return '';
    }

    /**
     * @return string
     */
    public function getSeoTagsExtensionPoint(): string
    {
        return 'YREWRITE_SEO_TAGS';
    }

    /**
     * @param int $article_id
     * @param int $clang_id
     *
     * @return string
     */
    public function getFullUrl($article_id, $clang_id): string
    {
        return \rex_yrewrite::getFullUrlByArticleId($article_id, $clang_id);
    }

    /**
     * @param string $path
     *
     * @return string
     */
    public function getFullPath($path): string
    {
        return \rex_yrewrite::getFullPath($path);
    }

    /**
     * @return string
     */
    public function getSuffix(): string
    {
        $scheme = \rex_yrewrite::getScheme();
        return $scheme->getSuffix();
    }

    /**
     * @param string $domain
     *
     * @return string
     */
    public function getSchemeByDomain($domain): string
    {
        $domain = \rex_yrewrite::getDomainByName($domain);
        if (!$domain) {
            return '';
        }
        return $domain->getScheme();
    }

    /**
     * @return bool
     */
    public function isHttps(): bool
    {
        return \rex_yrewrite::isHttps();
    }

    /**
     * @param string $string
     * @param int    $clang
     *
     * @return string
     */
    public function normalize($string, $clang = 1): string
    {
        $scheme = \rex_yrewrite::getScheme();
        return $scheme->normalize($string, $clang);
    }
}
