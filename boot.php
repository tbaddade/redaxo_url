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

\Url\Generator::boot();
if (null !== Url::getRewriter()) {
    Url::getRewriter()->articleIdNotFound();
}

rex_extension::register('PACKAGES_INCLUDED', function (\rex_extension_point $epPackagesIncluded) {
    // if anything changes -> refresh PathFile
    if (rex::isBackend() && rex::getUser()) {
        $extensionPoints = [
            'ART_ADDED', 'ART_DELETED', 'ART_MOVED', 'ART_STATUS', 'ART_UPDATED',
            'CAT_ADDED', 'CAT_DELETED', 'CAT_MOVED', 'CAT_STATUS', 'CAT_UPDATED',
            'CLANG_ADDED', 'CLANG_DELETED','CLANG_UPDATED',
            'CACHE_DELETED',
            'REX_FORM_SAVED',
            'REX_YFORM_SAVED',
            'YFORM_DATA_ADDED', 'YFORM_DATA_UPDATED',
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
                $regexp = '@<a href="index\.php\?page=structure.*?category-id='.$id.'.*?rex-api-call=category_delete.*?>(.*?)<\/a>@';
                if (preg_match($regexp, $subject, $matches)) {
                    $subject = str_replace($matches[0], '<span class="text-muted">'.$matches[1].'</span>', $subject);
                }
            }
            return $subject;
        });

        // Profilartikel - löschen nicht erlauben
        $rexApiCall = rex_request(rex_api_function::REQ_CALL_PARAM, 'string', '');
        if (($rexApiCall == 'category_delete' && in_array(rex_request('category-id', 'int'), $profileArticleIds)) ||
            ($rexApiCall == 'article_delete' && in_array(rex_request('article_id', 'int'), $profileArticleIds))) {
            $_REQUEST[rex_api_function::REQ_CALL_PARAM] = '';
            rex_extension::register('PAGE_TITLE_SHOWN', function (\rex_extension_point $ep) {
                $subject = $ep->getSubject();
                $ep->setSubject(rex_view::error(rex_i18n::msg('url_generator_rex_api_delete')).$subject);
            });
        }
    }

    rex_extension::register('URL_REWRITE', function (\rex_extension_point $ep) {
        return UrlManager::getRewriteUrl($ep);
    }, rex_extension::EARLY);

    if (null !== Url::getRewriter() && Url::getRewriter()->getSitemapExtensionPoint()) {
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
