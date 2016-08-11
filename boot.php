<?php

/**
 * This file is part of the Url package.
 *
 * @author (c) Thomas Blum <thomas@addoff.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use \Url\Url;
use \Url\Generator;
use \Url\Seo;

class_alias('Url\Url', 'Url');
class_alias('Url\Generator', 'UrlGenerator');
class_alias('Url\Seo', 'UrlSeo');

Url::boot();
Generator::boot();
if (null !== Url::getRewriter()) {
    Url::getRewriter()->articleIdNotFound();
}

rex_extension::register('PACKAGES_INCLUDED', function ($params) {

    // if anything changes -> refresh PathFile
    if (rex::isBackend()) {
        $extensionPoints = [
            'CAT_ADDED', 'CAT_UPDATED', 'CAT_DELETED', 'CAT_STATUS',
            'ART_ADDED', 'ART_UPDATED', 'ART_DELETED', 'ART_STATUS',
            'CLANG_ADDED', 'CLANG_UPDATED', 'CLANG_DELETED',
            'ARTICLE_GENERATED',
            'ALL_GENERATED',
            'REX_FORM_SAVED',
            'YFORM_DATA_ADDED', 'YFORM_DATA_UPDATED',
        ];

        foreach ($extensionPoints as $extensionPoint) {
            rex_extension::register($extensionPoint, function (rex_extension_point $ep) {
                $params = $ep->getParams();
                $params['subject'] = $ep->getSubject();
                $params['extension_point'] = $ep->getName();
                Generator::generatePathFile($params);
            });
        }
    }

    rex_extension::register('URL_REWRITE', function (rex_extension_point $ep) {
        $params = $ep->getParams();
        $params['subject'] = $ep->getSubject();
        return Generator::rewrite($params);
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

