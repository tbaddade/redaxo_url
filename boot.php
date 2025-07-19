<?php

/**
 * This file is part of the Url package.
 *
 * @author (c) Thomas Blum <thomas@addoff.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Url\ExtensionPointManager;
use Url\Generator;
use Url\Profile;
use Url\Seo;
use Url\Url;
use Url\UrlManager;

$addon = rex_addon::get('url');

Generator::boot();
if (null !== Url::getRewriter()) {
    Url::getRewriter()->articleIdNotFound();
}

rex_extension::register('PACKAGES_INCLUDED', function (\rex_extension_point $epPackagesIncluded) {
    if (rex::isBackend() && rex::getUser() !== null) {
        $extensionPoints = [
            'ART_ADDED', 'ART_DELETED', 'ART_MOVED', 'ART_STATUS', 'ART_UPDATED',
            'CAT_ADDED', 'CAT_DELETED', 'CAT_MOVED', 'CAT_STATUS', 'CAT_UPDATED',
            'CLANG_ADDED', 'CLANG_DELETED','CLANG_UPDATED',
            'CACHE_DELETED',
            'REX_FORM_SAVED',
            'REX_YFORM_SAVED',
            'YFORM_DATA_ADDED', 'YFORM_DATA_DELETED', 'YFORM_DATA_UPDATED',
        ];

        foreach ($extensionPoints as $extensionPoint) {
            rex_extension::register($extensionPoint, function (\rex_extension_point $ep) {
                $manager = new ExtensionPointManager($ep);
                $generator = new Generator($manager);
                $generator->execute();
            }, rex_extension::LATE);
        }

        $profileArticleIds = Profile::getAllArticleIds();

        // Profilartikel nicht löschen
        // Manipulation des löschen Links
        rex_extension::register('OUTPUT_FILTER', function (\rex_extension_point $ep) use($profileArticleIds) {
            $subject = $ep->getSubject();

            foreach ($profileArticleIds as $id) {
                $regexp = '@<a.*?href="index\.php\?page=structure[^>]*category-id='.$id.'&[^>]*rex-api-call=category_delete[^>]*>([^&]*)<\/a>@';
                if (preg_match($regexp, $subject, $matches)) {
                    $subject = str_replace($matches[0], '<span class="text-muted" title="'.rex_i18n::msg('url_generator_structure_disallow_to_delete_category').'">'.$matches[1].'</span>', $subject);
                }
                $regexp = '@<a[^>]*href="index\.php\?page=structure[^>]*article_id='.$id.'&[^>]*rex-api-call=article_delete[^>]*>([^&]*)<\/a>@';
                if (preg_match($regexp, $subject, $matches)) {
                    $subject = str_replace($matches[0], '<span class="text-muted" title="'.rex_i18n::msg('url_generator_structure_disallow_to_delete_article').'">'.$matches[1].'</span>', $subject);
                }
            }
            return $subject;
        });

        // Profilartikel - löschen nicht erlauben
        $rexApiCall = rex_request(rex_api_function::REQ_CALL_PARAM, 'string', '');
        if ((($rexApiCall === 'category_delete') && in_array(rex_request('category-id', 'int'), $profileArticleIds, true)) ||
            (($rexApiCall === 'article_delete') && in_array(rex_request('article_id', 'int'), $profileArticleIds, true))) {
            rex_request::request(rex_api_function::REQ_CALL_PARAM, 'string', '');
            rex_extension::register('PAGE_TITLE_SHOWN', function (\rex_extension_point $ep) {
                $subject = $ep->getSubject();
                $ep->setSubject(rex_view::error(rex_i18n::msg('url_generator_rex_api_delete')).$subject);
            });
        }
    }

    rex_extension::register('URL_REWRITE', function (\rex_extension_point $ep) {
        return UrlManager::getRewriteUrl($ep);
    }, rex_extension::EARLY);

    if (null !== Url::getRewriter() && Url::getRewriter()->getSitemapExtensionPoint() !== '') {
        rex_extension::register(Url::getRewriter()->getSitemapExtensionPoint(), function (rex_extension_point $ep) {
            $sitemap = $ep->getSubject();
            if (is_array($sitemap)) {
                $sitemap = array_merge($sitemap, Seo::getSitemap());
            } else {
                $sitemap = Seo::getSitemap();
            }
            $ep->setSubject($sitemap);
        }, rex_extension::EARLY);
    }
}, rex_extension::EARLY);


if (rex::isBackend() && rex::getUser() !== null) {
    rex_view::addCssFile($addon->getAssetsUrl('styles.css'));
}

if (null !== Url::getRewriter() && Url::getRewriter()->getSeoTagsExtensionPoint() !== '') {
    rex_extension::register(Url::getRewriter()->getSeoTagsExtensionPoint(), function (rex_extension_point $rewriterExtensionPoint) {
        $seoTags = $rewriterExtensionPoint->getSubject();

        if (!is_array($seoTags)) {
            $seoTags = [];
        }

        \rex_extension::register('URL_SEO_TAGS', function($urlExtensionPoint) use ($rewriterExtensionPoint, $seoTags) {
            $seoTags = array_merge($seoTags, $urlExtensionPoint->getSubject());

            $bucket = [];
            $bucketOg = [];
            $bucketTwitter = [];

            foreach ($seoTags as $key => $value) {
                if (str_starts_with($key, 'og:')) {
                    $bucketOg[$key] = $value;
                } elseif (str_starts_with($key, 'twitter:')) {
                    $bucketTwitter[$key] = $value;
                } else {
                    $bucket[$key] = $value;
                }
            }
            $seoTags = $bucket + $bucketOg + $bucketTwitter;
            $rewriterExtensionPoint->setSubject($seoTags);
        });

        $urlSeo = new Seo();
        $urlSeoTags = $urlSeo->getTags();

    }, rex_extension::EARLY);
}
