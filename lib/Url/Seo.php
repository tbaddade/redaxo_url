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
        if (!\rex::isBackend()) {
            $this->manager     = Url::resolveCurrent();
            $this->rewriter    = Url::getRewriter();
            $this->rewriterSeo = $this->rewriter->getSeoInstance();
        }
    }

    public function init()
    {
        if ($this->rewriter) {

            \rex_extension::register($this->rewriter->getSeoTitleEP(), [$this, 'setTitle']);
            \rex_extension::register($this->rewriter->getSeoDescriptionEP(), [$this, 'setDescription']);
            \rex_extension::register($this->rewriter->getSeoHreflangEP(), [$this, 'setHrefLangs']);
            \rex_extension::register($this->rewriter->getSeoCanonicalEP(), [$this, 'setCanonicalUrl']);
            \rex_extension::register($this->rewriter->getSeoImagesEP(), [$this, 'setImages']);
            \rex_extension::register($this->rewriter->getSitemapExtensionPoint(), [$this, 'setSitemap']);
        }
    }

    public function setTitle(\rex_extension_point $Ep)
    {
        $title = $Ep->getSubject();

        if ($this->isUrl() && $this->manager->getSeoTitle()) {
            $title = $this->manager->getSeoTitle() . ' / ' . $title;
        }
        return $title;
    }

    public function setDescription(\rex_extension_point $Ep)
    {
        $description = $Ep->getSubject();

        if ($this->isUrl() && $this->manager->getSeoDescription()) {
            $description = $this->manager->getSeoDescription();
        }
        return $description;
    }

    public function setHrefLangs(\rex_extension_point $Ep)
    {
        $href_langs = $Ep->getSubject();

        if ($this->isUrl()) {
            $items = $this->manager->getHreflang(\rex_clang::getAllIds(true));

            if ($items) {
                $href_langs = [];

                foreach ($items as $item) {
                    $clang = \rex_clang::get($item->getClangId());
                    $url   = $item->getUrl();
                    $url->withSolvedScheme();
                    $href_langs[$clang->getCode()] = (string)$url;
                }
            }
        }
        return $href_langs;
    }

    public function setCanonicalUrl(\rex_extension_point $Ep)
    {
        $canonical = $Ep->getSubject();

        if ($this->isUrl()) {
            $url = $this->manager->getUrl();
            $url->withSolvedScheme();
            $canonical = (string)$url;
        }
        return $canonical;
    }

    public function setImages(\rex_extension_point $Ep)
    {
        $images = $Ep->getSubject();

        if ($images === '' && $this->isUrl()) {
            $images = $this->manager->getSeoImage();
        }
        return $images;
    }

    public function setSitemap(\rex_extension_point $Ep)
    {
        $urls     = (array)$Ep->getSubject();
        $profiles = Profile::getAll();

        if ($profiles) {
            foreach ($profiles as $profile) {
                if (!$profile->inSitemap()) {
                    continue;
                }

                $profileUrls = $profile->getUrls();
                if (!$profileUrls) {
                    continue;
                }

                foreach ($profileUrls as $profileUrl) {
                    $url = $profileUrl->getUrl();
                    $url->withSolvedScheme();

                    $url = [
                        'loc'        => (string)$url,
                        'lastmod'    => $profileUrl->getLastmod(),
                        'changefreq' => $profile->getSitemapFrequency(),
                        'priority'   => $profile->getSitemapPriority(),
                        'image'      => [],
                    ];

                    $images = $profileUrl->getSeoImage();

                    if ($images) {
                        $images = array_unique(array_filter(explode(',', $images)));


                        foreach ($images as $media_name) {
                            $media = \rex_media::get($media_name);

                            if ($media && $media->isImage()) {
                                $imgUrl         = [
                                    'loc'   => $this->rewriter->getFullPath(ltrim($media->getUrl(), '/')),
                                    'title' => rex_escape($media->getTitle()),
                                ];
                                $url['image'][] = $imgUrl;
                            }
                        }
                    }
                    $urls[] = $url;
                }
            }
        }
        $Ep->setSubject($urls);
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
