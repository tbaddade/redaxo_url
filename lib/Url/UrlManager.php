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

    /**
     * @todo ambiguous fieldnames with relations
     *
     * @throws \rex_sql_exception
     */
    public function getDataset()
    {
        $profile = Profile::get($this->getProfileId());
        if (!$profile) {
            return null;
        }

        $query = \rex_yform_manager_query::get($profile->getTableName());

        $yFormTables = \rex_yform_manager_table::getAll();
        if (count($yFormTables) && isset($yFormTables[$profile->getTableName()])) {
            $modelClass = \rex_yform_manager_dataset::getModelClass($profile->getTableName());
            if ($modelClass) {
                /** @noinspection PhpUndefinedMethodInspection */
                $query = $modelClass::query();
            }
        }

        $query->resetOrderBy();
        $query->alias(Profile::ALIAS);
        $query->where(
            $profile->getColumnNameWithAlias(
                \rex_sql_table::get($profile->getTableName())->getPrimaryKey()[0]
            ),
            $this->getDatasetId()
        );

        if ($profile->hasRelations()) {
            foreach ($profile->getRelations() as $relation) {
                // $query->select($relation->getAlias().'.*');
                $joinCondition = $profile->getColumnNameWithAlias('relation_'.$relation->getIndex()).' = '.$relation->getColumnNameWithAlias('id');

                if (null === $profile->getColumnNameWithAlias('clang_id') && $profile->getArticleClangId() != '' && $relation->getColumnNameWithAlias('clang_id') != '') {
                    $joinCondition .= ' AND '.$profile->getArticleClangId().' = '.$relation->getColumnNameWithAlias('clang_id');
                }

                $query->joinRaw('LEFT', $relation->getTableName(), $relation->getAlias(), $joinCondition);
            }
        }

        return $query->findOne();
    }

    public function getDatasetId()
    {
        return $this->values['data_id'];
    }

    /**
     * @param $clangIds
     *
     * @throws \rex_sql_exception
     *
     * @return null|UrlManager[]
     */
    public function getHreflang($clangIds)
    {
        $items = UrlManagerSql::getHreflang($this, $clangIds);

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

    /**
     * @throws \rex_exception
     *
     * @return null|mixed
     */
    public function getSeoDescription()
    {
        return $this->getSeoValue('description');
    }

    /**
     * @throws \rex_exception
     *
     * @return null|mixed
     */
    public function getSeoImage()
    {
        return $this->getSeoValue('image');
    }

    /**
     * @throws \rex_exception
     *
     * @return null|mixed
     */
    public function getSeoTitle()
    {
        return $this->getSeoValue('title');
    }

    /**
     * @param $key
     *
     * @throws \rex_exception
     *
     * @return null|mixed
     */
    public function getSeoValue($key)
    {
        if (empty($key)) {
            throw new \rex_exception('Parameter key must not be empty!');
        }

        if (!isset($this->getSeo()[$key])) {
            return null;
        }

        return $this->getSeo()[$key];
    }

    /**
     * @return Url
     */
    public function getUrl()
    {
        return Url::get($this->values['url']);
    }

    public function getValue($key)
    {
        return $this->values[$key];
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
    public function isRoot()
    {
        return $this->isStructure() || $this->isUserPath() ? false : true;
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
     * @param int $profileId
     *
     * @throws \rex_sql_exception
     *
     * @return null|UrlManager[]
     */
    public static function getByProfileId($profileId)
    {
        $items = UrlManagerSql::getByProfileId($profileId);

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
     * @param Url $url
     *
     * @throws \rex_sql_exception
     *
     * @return null|UrlManager
     */
    public static function resolveUrl(Url $url)
    {
        // Url nur auflösen (DB-Abfrage), wenn der erste Teil des Url-Pfades auch in einem Profil zu finden ist
        // Prüft ob der erste Teil der übergebenen Url in einem Profil zu finden ist.
        $resolve = false;
        foreach (Profile::getAll() as $profile) {
            if ($profile->hasPreSaveCalled()) {
                $resolve = true;
                break;
            }

            if (null === $profile->getArticleClangId()) {
                $resolve = true;
                break;
            }

            $articlePath = $profile->getArticleUrl()->getPathWithoutSuffix();
            if ($articlePath == substr($url->getPath(), 0, strlen($articlePath))) {
                $resolve = true;
                break;
            }
        }
        if (!$resolve) {
            return null;
        }

        // Weiterleitung auf URL mit Suffix, wenn Suffix fehlt
        $rewriterSuffix = Url::getRewriter()->getSuffix();
        if (\rex::isFrontend() && $rewriterSuffix && substr($url->getRequestPath(), -strlen($rewriterSuffix)) !== $rewriterSuffix) {
            // URL Objekt nachfolgend neu erstellen um Parameter nicht zu verlieren
            if(count(UrlManagerSql::getByUrl($url)) == 1) {
                header('HTTP/1.1 301 Moved Permanently');
                header('Location: '. $url->toString());
                exit;
            }
        }

        $items = UrlManagerSql::getByUrl($url);
        if (count($items) != 1) {
            return null;
        }

        $instance = new self($items[0]);
        return $instance;
    }

    /**
     * @throws \rex_sql_exception
     *
     * @return null|array
     */
    public static function getArticleParams()
    {
        $current = Url::getCurrent();
        $instance = self::resolveUrl($current);
        if (!$instance) {
            return null;
        }
        return ['article_id' => $instance->getArticleId(), 'clang' => $instance->getClangId()];
    }

    /**
     * rex_getUrl oder ->getUrl wurde aufgerufen.
     *
     * @param \rex_extension_point $ep
     *
     * @throws \rex_sql_exception
     *
     * @return null|mixed|string
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

        $structureId = $ep->getParam('id');
        $clangId = $ep->getParam('clang');
        $urlParams = $ep->getParam('params');
        foreach ($urlParams as $urlParamKey => $urlParamValue) {
            if ((int) $urlParamValue < 1) {
                continue;
            }
            foreach ($profiles as $profile) {
                // Prüfen ob der Url-Param in einem Profil hinterlegt wurde
                if ($urlParamKey != $profile->getNamespace()) {
                    continue;
                }

                $urlRecord = self::getForRewriteUrl($profile, (int) $urlParamValue, $clangId);
                if (!$urlRecord) {
                    // Urls erstellen
                    $profile->buildUrlsByDatasetId($urlParamValue);
                    $urlRecord = self::getForRewriteUrl($profile, (int) $urlParamValue, $clangId);
                }

                if (!$urlRecord) {
                    // Keine Origin Url gefunden
                    continue;
                }

                // Prüfen ob für Unterkategorien eine Url gesetzt werden muss
                // (Unterkategorien anhängen)
                if ($profile->appendStructureCategories() && $profile->getArticleId() != $structureId) {
                    $category = \rex_category::get($structureId);
                    if ($category) {
                        $article = \rex_article::get($profile->getArticleId());
                        $restStructurePath = str_replace($article->getUrl(), '', $category->getUrl());

                        $restStructurePathUrl = new Url($restStructurePath);

                        $expandedOriginUrl = $urlRecord->getUrl();
                        $expandedOriginUrl->appendPathSegments($restStructurePathUrl->getSegments(), $article->getClangId());

                        $urlRecord = self::resolveUrl($expandedOriginUrl);
                    }
                }

                if (!$urlRecord) {
                    // Keine Origin oder appendStructureCategory Url gefunden
                    continue;
                }

                $url = $urlRecord->getUrl();

                unset($urlParams[$urlParamKey]);
                if (count($urlParams)) {
                    $url->withQuery('?'.\rex_string::buildQuery($urlParams, $ep->getParam('separator')));
                }

                if ($url->getDomain() == Url::getCurrent()->getDomain()) {
                    return $url->getPath().$url->getQuery();
                }

                $scheme = Url::getRewriter()->getSchemeByDomain($url->getDomain()) ?: (Url::getRewriter()->isHttps() ? 'https' : 'http');
                $url->withScheme($scheme);
                return $url->getSchemeAndHttpHost().$url->getPath().$url->getQuery();
            }
        }
        return null;
    }

    public static function getSegmentPartSeparators()
    {
        return [
            '/' => '/',
            '-' => '-',
            '_' => '_',
        ];
    }

    /**
     * @param Profile $profile
     * @param int     $datasetId
     * @param int     $clangId
     *
     * @throws \rex_sql_exception
     *
     * @return null|UrlManager
     */
    private static function getForRewriteUrl(Profile $profile, $datasetId, $clangId)
    {
        $items = UrlManagerSql::getOrigin($profile, $datasetId, $clangId);

        if (count($items) != 1) {
            return null;
        }

        $instance = new self($items[0]);
        return $instance;
    }
}
