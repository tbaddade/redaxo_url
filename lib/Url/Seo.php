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

    private $dataId;

    public function __construct()
    {
        $this->rewriter = Url::getRewriter();
        $this->rewriterSeo = $this->rewriter->getSeoInstance();
        $this->data = Generator::getData();
        $this->dataId = Generator::getId();
    }

    public function getTitleTag()
    {
        return $this->isUrl() ? '<title>' . htmlspecialchars($this->normalize($this->data['seoTitle'])) . '</title>' : $this->rewriterSeo->{$this->rewriter->getSeoTitleTagMethod()}();
    }

    public function getDescriptionTag()
    {
        return $this->isUrl() ? '<meta name="description" content="'.htmlspecialchars($this->normalize($this->data['seoDescription'])).'" />' : $this->rewriterSeo->{$this->rewriter->getSeoDescriptionTagMethod()}();
    }

    public function getCanonicalTag()
    {
        return $this->isUrl() ? '<link rel="canonical" href="' . $this->data['url'] . '" />' : $this->rewriterSeo->{$this->rewriter->getSeoCanonicalTagMethod()}();
    }

    public function getHreflangTags()
    {
        if ($this->isUrl()) {
            $return = '';
            $hrefs = [];
            foreach (\rex_clang::getAll() as $clang) {
                if ($clang->getId() == \rex_clang::getCurrentId()) {
                    continue;
                }

                $hrefs[$clang->getCode()] = Generator::getUrlById($this->dataId, '', $clang->getId());
            }

            if (count($hrefs)) {
                foreach ($hrefs as $code => $url) {
                    $return .= '<link rel="alternate" hreflang="' . $code . '" href="' . $url . '" />';
                }
            }
            return $return;
        }

        return $this->rewriterSeo->{$this->rewriter->getSeoHreflangTagsMethod()}();
    }

    public function getRobotsTag()
    {
        return $this->rewriterSeo->{$this->rewriter->getSeoRobotsTagMethod()}();
    }

    protected function isUrl()
    {
        return ($this->dataId > 0);
    }

    protected function normalize($string)
    {
        return str_replace(["\n", "\r"], [' ',''], $string);
    }

    public static function getSitemap()
    {
        $sitemap = [];
        $all = Generator::getAll();
        if ($all) {
            foreach ($all as $item) {
                if ($item['sitemap']) {
                    $sitemap[] =
                        "\n" . '<url>' .
                        "\n" . '<loc>' . $item['fullUrl'] . '</loc>' .
                        "\n" . '<lastmod>' . date(DATE_W3C, time()) . '</lastmod>' .
                        "\n" . '<changefreq>' . $item['sitemapFrequency'] . '</changefreq>' .
                        "\n" . '<priority>' . $item['sitemapPriority'] . '</priority>' .
                        "\n" . '</url>';
                    if (count($item['fullPathNames'])) {
                        foreach ($item['fullPathNames'] as $path) {
                            $sitemap[] =
                                "\n" . '<url>' .
                                "\n" . '<loc>' . $path . '</loc>' .
                                "\n" . '<lastmod>' . date(DATE_W3C, time()) . '</lastmod>' .
                                "\n" . '<changefreq>' . $item['sitemapFrequency'] . '</changefreq>' .
                                "\n" . '<priority>' . $item['sitemapPriority'] . '</priority>' .
                                "\n" . '</url>';
                        }
                    }
                    if (count($item['fullPathCategories'])) {
                        foreach ($item['fullPathCategories'] as $path) {
                            $sitemap[] =
                                "\n" . '<url>' .
                                "\n" . '<loc>' . $path . '</loc>' .
                                "\n" . '<lastmod>' . date(DATE_W3C, time()) . '</lastmod>' .
                                "\n" . '<changefreq>' . $item['sitemapFrequency'] . '</changefreq>' .
                                "\n" . '<priority>' . $item['sitemapPriority'] . '</priority>' .
                                "\n" . '</url>';
                        }
                    }
                }
            }
        }

        return $sitemap;
    }
}
