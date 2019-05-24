<?php

/**
 * This file is part of the Url package.
 *
 * @author (c) Thomas Blum <thomas@addoff.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Url;

class Seo
{
    /**
     * @var \Url\Rewriter\Rewriter
     */
    private $rewriter;

    private $rewriterSeo;

    private $urlData;

    public function __construct()
    {
        $this->urlData = UrlManager::getData();
        $this->rewriter = Url::getRewriter();
        $this->rewriterSeo = $this->rewriter->getSeoInstance();
    }

    public function getTitle()
    {
        $title = $this->rewriterSeo->getTitle();
        if ($this->isUrl() && $this->urlData->getSeoTitle()) {
            $title = $this->urlData->getSeoTitle().' - '.$title;
        }

        $title = \rex_extension::registerPoint(new \rex_extension_point('URL_SEO_TITLE', $title));

        if (!$title) {
            return '';
        }

        return $this->normalize($title);
    }

    public function getTitleTag()
    {
        if ($this->isUrl()) {
            return '<title>'.htmlspecialchars($this->getTitle()).'</title>';
        }
        return $this->rewriterSeo->{$this->rewriter->getSeoTitleTagMethod()}();
    }

    public function getDescription()
    {
        $description = $this->rewriterSeo->getDescription();
        if ($this->isUrl() && $this->urlData->getSeoDescription()) {
            $description = $this->urlData->getSeoDescription();
        }

        $description = \rex_extension::registerPoint(new \rex_extension_point('URL_SEO_DESCRIPTION', $description));

        if (!$description) {
            return '';
        }

        return $this->normalize($description);
    }

    public function getDescriptionTag()
    {
        if ($this->isUrl()) {
            return '<meta name="description" content="'.htmlspecialchars($this->getDescription()).'" />';
        }
        return $this->rewriterSeo->{$this->rewriter->getSeoDescriptionTagMethod()}();
    }

    public function getCanonicalUrl()
    {
        $url = $this->urlData->getUrl();
        $url->withSolvedScheme();
        return $url->getSchemeAndHttpHost().$url->getPath();
    }

    public function getCanonicalUrlTag()
    {
        if ($this->isUrl()) {
            return '<link rel="canonical" href="'.$this->getCanonicalUrl().'" />';
        }
        return $this->rewriterSeo->{$this->rewriter->getSeoCanonicalTagMethod()}();
    }

    public function getHreflangTags()
    {
        if ($this->isUrl()) {
            $items = $this->urlData->getHreflangUrls(\rex_clang::getAllIds(true));

            if ($items) {
                $metas = [];
                foreach ($items as $item) {
                    $url = $item->getUrl();
                    $url->withSolvedScheme();
                    $metas[] = '<link rel="alternate" hreflang="'.\rex_clang::get($item->getClangId())->getCode().'" href="'.$url.'" />';
                }
                return implode("\n", $metas);
            }
        }

        return $this->rewriterSeo->{$this->rewriter->getSeoHreflangTagsMethod()}();
    }

    public function getRobotsTag()
    {
        return $this->rewriterSeo->{$this->rewriter->getSeoRobotsTagMethod()}();
    }

    public function getImage()
    {
        return $this->urlData->getSeoImage();
    }

    public static function getSitemap()
    {
        $profiles = Profile::getAll();
        if (!$profiles) {
            return [];
        }

        $sitemap = [];
        foreach ($profiles as $profile) {
            if (!$profile->inSitemap()) {
                continue;
            }

            $profileUrls = $profile->getUrls();
            if(!$profileUrls) {
                continue;
            }

            foreach ($profileUrls as $profileUrl) {
                if (!is_object($profileUrl)) {
                    continue;
                }
                $url = $profileUrl->getUrl();
                $url->withSolvedScheme();

                $sitemap[] =
                    "\n".'<url>'.
                    "\n".'<loc>'.$url->getSchemeAndHttpHost().$url->getPath().'</loc>'.
                    "\n".'<lastmod>'.$profileUrl->getLastmod().'</lastmod>'.
                    "\n".'<changefreq>'.$profile->getSitemapFrequency().'</changefreq>'.
                    "\n".'<priority>'.$profile->getSitemapPriority().'</priority>'.
                    "\n".'</url>';
            }
        }

        return $sitemap;
    }

    protected function isUrl()
    {
        return $this->urlData instanceof UrlManager;
    }

    protected function normalize($string)
    {
        return str_replace(["\n", "\r"], [' ', ''], $string);
    }
}
