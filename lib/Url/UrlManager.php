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

class UrlManager
{
    protected $values;

    /**
     * UrlManager constructor.
     *
     * @param array $values
     */
    private function __construct($values)
    {
        $this->values = $values;
        if (isset($this->values['seo'])) {
            $this->values['seo'] = json_decode($this->values['seo'], true);
        }
    }

    public function getArticleId()
    {
        return $this->values['article_id'];
    }

    public function getClangId()
    {
        return $this->values['clang_id'];
    }

    // public function getDataset()
    // {
    //     $profile = Profile::get($this->getProfileId());
    //     if (!$profile) {
    //         return null;
    //     }
    //
    //     $yFormTables = \rex_yform_manager_table::getAll();
    //     if (count($yFormTables) && isset($yFormTables[$profile->getTableName()])) {
    //
    //     }
    // }

    public function getDatasetId()
    {
        return $this->values['data_id'];
    }

    /**
     * @param $clangIds
     *
     * @return null|UrlManager[]
     */
    public function getHreflangUrls($clangIds)
    {
        $items = UrlManagerSql::getHreflangUrlsForDataset($this->getDatasetId(), $this->getArticleId(), $clangIds);

        if (!$items) {
            return null;
        }

        $instances = [];
        foreach ($items as $item) {
            $instances[] = new self($item);
        }
        return $instances;
    }

    public function getLastmod()
    {
        return $this->values['lastmod'];
    }

    /**
     * @return null|Profile
     */
    public function getProfile()
    {
        return Profile::get($this->getProfileId());
    }

    public function getProfileId()
    {
        return $this->values['profile_id'];
    }

    /**
     * @return array
     */
    public function getSeo()
    {
        return $this->values['seo'];
    }

    public function getSeoDescription()
    {
        return $this->getSeo()['description'];
    }

    public function getSeoImage()
    {
        return $this->getSeo()['image'];
    }

    public function getSeoTitle()
    {
        return $this->getSeo()['title'];
    }

    /**
     * @return Url
     */
    public function getUrl()
    {
        return Url::get($this->values['url']);
    }

    /**
     * @return bool
     */
    public function inSitemap()
    {
        return $this->values['sitemap'] === 1 ? true : false;
    }

    /**
     * @return bool
     */
    public function isStructure()
    {
        return $this->values['is_structure'] === 1 ? true : false;
    }

    /**
     * @return bool
     */
    public function isUserPath()
    {
        return $this->values['is_user_path'] === 1 ? true : false;
    }

    /**
     * @param Profile $profile
     *
     * @return null|UrlManager[]
     */
    public static function getUrlsByProfile(Profile $profile)
    {
        $items = UrlManagerSql::getByProfileId($profile->getId());

        if (!$items) {
            return null;
        }

        $instances = [];
        foreach ($items as $item) {
            $instances[] = new self($item);
        }
        return $instances;
    }

    /**
     * @param Profile $profile
     * @param int     $datasetId
     * @param int     $clangId
     *
     * @return null|UrlManager
     */
    public static function getOriginalUrlForDataset(Profile $profile, $datasetId, $clangId)
    {
        $items = UrlManagerSql::getOriginalUrlForDataset($profile, $datasetId, $clangId);

        if (count($items) != 1) {
            return null;
        }

        $instance = new self($items[0]);
        return $instance;
    }

    /**
     * @return null|array
     */
    public static function getArticleParams()
    {
        $items = UrlManagerSql::getByUrl();

        if (count($items) != 1) {
            return null;
        }

        $instance = new self($items[0]);
        return ['article_id' => $instance->getArticleId(), 'clang' => $instance->getClangId()];
    }

    /**
     * @return null|UrlManager
     */
    public static function getData()
    {
        $items = UrlManagerSql::getByUrl();

        if (count($items) != 1) {
            return null;
        }

        $instance = new self($items[0]);
        return $instance;
    }

    /**
     * @param \rex_extension_point $ep
     *
     * @return mixed|null|string
     */
    public static function getRewriteUrl(\rex_extension_point $ep)
    {
        // Url wurde von einer anderen Extension bereits gesetzt
        if ($ep->getSubject() != '') {
            return $ep->getSubject();
        }

        $profiles = Profile::getAll();

        if (!$profiles) {
            return null;
        }

        $clangId = $ep->getParam('clang');
        $urlParams = $ep->getParam('params');
        foreach ($urlParams as $urlParamKey => $urlParamValue) {
            if ((int) $urlParamValue < 1) {
                continue;
            }
            foreach ($profiles as $profile) {
                if ($urlParamKey != $profile->getNamespace()) {
                    continue;
                }

                $url = self::getOriginalUrlForDataset($profile, (int) $urlParamValue, $clangId);
                if (!$url) {
                    continue;
                }

                $dataset = $url->getUrl();
                $current = Url::getCurrent();

                unset($urlParams[$urlParamKey]);
                if (count($urlParams)) {
                    $dataset->withQuery('?'.\rex_string::buildQuery($urlParams, $ep->getParam('separator')));
                }

                if ($dataset->getDomain() == $current->getDomain()) {
                    return $dataset->getPath().$dataset->getQuery();
                }

                $scheme = Url::getRewriter()->getSchemeByDomain($dataset->getDomain()) ?: (Url::getRewriter()->isHttps() ? 'https' : 'http');
                $dataset->withScheme($scheme);
                return $dataset->getSchemeAndHttpHost().$dataset->getPath();
            }
        }
        return null;
    }
}
