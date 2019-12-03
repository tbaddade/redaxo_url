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

    public function getTags()
    {
        $tags = [];
        $tags['title'] = $this->rewriterSeo->getTitleTag();
        $tags['description'] = $this->rewriterSeo->getDescriptionTag();
        $tags['robots'] = $this->rewriterSeo->getRobotsTag();
        $tags['canonical'] = $this->rewriterSeo->getCanonicalUrlTag();

        //$tags = $this->rewriterSeo->getTags();

        if (!$this->isUrl()) {
            return implode("\n", $tags);
        }

        if ($this->manager->getSeoTitle()) {
            $title = $this->normalize($this->manager->getSeoTitle());
            $tags['title'] = '<title>'.$title.'</title>';
            $tags['og:title'] = '<meta property="og:title" content="'.$title.'" />';
            $tags['twitter:title'] = '<meta name="twitter:title" content="'.$title.'" />';
        }

        if ($this->manager->getSeoDescription()) {
            $description = $this->normalize($this->manager->getSeoDescription());
            $tags['description'] = '<meta name="description" content="'.$description.'" />';
            $tags['og:description'] = '<meta property="og:description" content="'.$description.'" />';
            $tags['twitter:description'] = '<meta name="twitter:description" content="'.$description.'" />';
        }

        $fullUrl = $this->getFullUrl();
        $tags['canonical'] = '<link rel="canonical" href="'.$fullUrl.'" />';
        $tags['og:url'] = '<meta property="og:url" href="'.$fullUrl.'" />';
        $tags['twitter:url'] = '<meta name="twitter:url" content="'.$fullUrl.'" />';


        $items = $this->manager->getHreflang(\rex_clang::getAllIds(true));
        if ($items) {
            foreach ($items as $item) {
                $url = $item->getUrl();
                $url->withSolvedScheme();
                $code = \rex_clang::get($item->getClangId())->getCode();
                $tags['hreflang:'.$code] = '<link rel="alternate" hreflang="'.$code.'" href="'.$url.'" />';
            }
        }

        $tags['twitter:card'] = '<meta name="twitter:card" content="summary" />';

        if ($this->manager->getSeoImage()) {
            $image = array_shift(explode(',', $this->manager->getSeoImage()));

            $media = \rex_media::get($image);
            if ($media) {
                $url = $this->manager->getUrl();
                $url->withSolvedScheme();
                $mediaUrl = $url->getSchemeAndHttpHost().$media->getUrl();

                $tags['twitter:card'] = '<meta name="twitter:card" content="summary_large_image" />';

                $tags['image'] = '<meta name="image" content="'.$mediaUrl.'" />';
                $tags['og:image'] = '<meta property="og:image" content="'.$mediaUrl.'" />';
                $tags['twitter:image'] = '<meta name="twitter:image" content="'.$mediaUrl.'" />';

                if ($media->getWidth()) {
                    $tags['og:image:width'] = '<meta property="og:image:width" content="'.$media->getWidth().'" />';
                }
                if ($media->getHeight()) {
                    $tags['og:image:height'] = '<meta property="og:image:height" content="'.$media->getHeight().'" />';
                }
            }
        }

        $tags = \rex_extension::registerPoint(new \rex_extension_point('URL_SEO_TAGS', $tags));
        return implode("\n", $tags);

    }

    public function getFullUrl()
    {
        $url = $this->manager->getUrl();
        $url->withSolvedScheme();
        return $url->getSchemeAndHttpHost().$url->getPath();
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

            // $clang kann null sein, wenn "alle Sprachen" im Profil ausgewählt wurde
            $clang = \rex_clang::get($profile->getArticleClangId());
            if (null !== $clang) {
                if (!$clang->isOnline()) {
                    continue;
                }

                $article = \rex_article::get($profile->getArticleId(), $clang->getId());
                if (!$article->isOnline() || !$article->isPermitted()) {
                    continue;
                }
            }

            $profileUrls = $profile->getUrls();
            if (!$profileUrls) {
                continue;
            }

            $clangsAreOnline = array_flip(\rex_clang::getAllIds(true));
            foreach ($profileUrls as $profileUrl) {
                if (null === $clang) {
                    if (!isset($clangsAreOnline[$profileUrl->getClangId()])) {
                        continue;
                    }
                    $article = \rex_article::get($profile->getArticleId(), $profileUrl->getClangId());
                    if (!$article->isOnline() || !$article->isPermitted()) {
                        continue;
                    }
                }

                $url = $profileUrl->getUrl();
                $url->withSolvedScheme();
                $sitemapImage = '';
                if ($profileUrl->getSeoImage()) {
                    $image = array_shift(explode(',', $profileUrl->getSeoImage()));

                    $media = \rex_media::get($image);
                    if ($media) {
                        $imageTitle = '';
                        if ('' != $media->getTitle()) {
                            $imageTitle = "\n\t\t".'<image:title>'.rex_escape(strip_tags($media->getTitle())).'</image:title>';
                        }
                        $sitemapImage =
                            "\n\t".'<image:image>'.
                            "\n\t\t".'<image:loc>'.$url->getSchemeAndHttpHost().$media->getUrl().'</image:loc>'.
                            $imageTitle.
                            "\n\t".'</image:image>';


                    }
                }

                $sitemap[] =
                    "\n".'<url>'.
                    "\n\t".'<loc>'.$url->getSchemeAndHttpHost().urldecode($url->getPath()).'</loc>'.
                    "\n\t".'<lastmod>'.$profileUrl->getLastmod().'</lastmod>'.
                    $sitemapImage.
                    "\n\t".'<changefreq>'.$profile->getSitemapFrequency().'</changefreq>'.
                    "\n\t".'<priority>'.$profile->getSitemapPriority().'</priority>'.
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
        $string = rex_escape(strip_tags($string));
        return str_replace(["\n", "\r"], [' ', ''], $string);
    }
}
