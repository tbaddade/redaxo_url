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

    private $manager;

    public function __construct()
    {
        $this->manager = Url::resolveCurrent();
        $this->rewriter = Url::getRewriter();
        $this->rewriterSeo = $this->rewriter->getSeoInstance();
    }

    public function getTitle()
    {
        $title = $this->rewriterSeo->getTitle();
        if ($this->isUrl() && $this->manager->getSeoTitle()) {
            $title = $this->manager->getSeoTitle().' - '.$title;
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
        if ($this->isUrl() && $this->manager->getSeoDescription()) {
            $description = $this->manager->getSeoDescription();
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
        $url = $this->manager->getUrl();
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
            $items = $this->manager->getHreflang(\rex_clang::getAllIds(true));

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
        return $this->manager->getSeoImage();
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
            if (!$profileUrls) {
                continue;
            }

            foreach ($profileUrls as $profileUrl) {
				$url = new \Url\Url($profileUrl['url']);
				$picture = json_decode($profileUrl['seo'])->image;
                $sitemap[] =
                    "\n".'<url>'.
                    "\n".'<loc>'. urldecode($url->withSolvedScheme()) .'</loc>'.
                    "\n".'<lastmod>'. $profileUrl['lastmod'] .'</lastmod>'.
					($picture != '' ? "\n".'<image:image><image:loc>'. $url->getSchemeAndHttpHost(). \rex_url::media($picture) .'</image:loc></image:image>' : '').
                    "\n".'<changefreq>'. $profile->getSitemapFrequency() .'</changefreq>'.
                    "\n".'<priority>'. $profile->getSitemapPriority() .'</priority>'.
                    "\n".'</url>';
            }
        }

        return $sitemap;
    }

    protected function isUrl()
    {
        return $this->manager instanceof UrlManager;
    }

    protected function normalize($string)
    {
        return str_replace(["\n", "\r"], [' ', ''], $string);
    }
}
