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

class Generator
{
    private static $databaseTableSeparator = '_xxx_';
    private static $pathfile = '';
    public static $paths = [];

    public static function boot()
    {
        self::$pathfile = \rex_path::addonCache('url', 'pathlist.php');
        //self::readPathFile();
    }

    public static function getRestrictionOperators()
    {
        return [ '='            => '=',
                 '>'            => '>',
                 '>='           => '>=',
                 '<'            => '<',
                 '<='           => '<=',
                 '!='           => '!=',
                 'LIKE'         => 'LIKE',
                 'NOT LIKE'     => 'NOT LIKE',
                 'IN (...)'     => 'IN (...)',
                 'NOT IN (...)' => 'NOT IN (...)',
                 'BETWEEN'      => 'BETWEEN',
                 'NOT BETWEEN'  => 'NOT BETWEEN',
                 'FIND_IN_SET'  => 'FIND_IN_SET',
                ];
    }

    public static function mergeDatabaseAndTable($database, $table)
    {
        return implode(self::$databaseTableSeparator, [$database, $table]);
    }
    public static function separateDatabaseAndTable($value)
    {
        return explode(self::$databaseTableSeparator, $value);
    }

    protected static function getTableObject($databaseAndTable, $parameters)
    {
        $parameters = json_decode($parameters, true);
        $databaseAndTableInformation = self::separateDatabaseAndTable($databaseAndTable);

        $table = new \stdClass();
        $table->dbid = $databaseAndTableInformation[0];
        $table->name = $databaseAndTableInformation[1];
        $table->field_1 = $parameters[$databaseAndTable][$databaseAndTable . '_field_1'];
        $table->field_2 = $parameters[$databaseAndTable][$databaseAndTable . '_field_2'];
        $table->field_3 = $parameters[$databaseAndTable][$databaseAndTable . '_field_3'];
        $table->id = $parameters[$databaseAndTable][$databaseAndTable . '_id'];
        $table->clang_id = $parameters[$databaseAndTable][$databaseAndTable . '_clang_id'];
        $table->restrictionField = $parameters[$databaseAndTable][$databaseAndTable . '_restriction_field'];
        $table->restrictionOperator = $parameters[$databaseAndTable][$databaseAndTable . '_restriction_operator'];
        $table->restrictionValue = $parameters[$databaseAndTable][$databaseAndTable . '_restriction_value'];
        $table->pathNames = $parameters[$databaseAndTable][$databaseAndTable . '_path_names'];
        $table->seoTitle = $parameters[$databaseAndTable][$databaseAndTable . '_seo_title'];
        $table->seoDescription = $parameters[$databaseAndTable][$databaseAndTable . '_seo_description'];
        $table->sitemapAdd = $parameters[$databaseAndTable][$databaseAndTable . '_sitemap_add'];
        $table->sitemapFrequency = $parameters[$databaseAndTable][$databaseAndTable . '_sitemap_frequency'];
        $table->sitemapPriority = $parameters[$databaseAndTable][$databaseAndTable . '_sitemap_priority'];
        $table->urlParamKey = $parameters[$databaseAndTable][$databaseAndTable . '_url_param_key'];
        return $table;

    }

    public static function appendRewriterSuffix($url)
    {
        $rewriterSuffix = Url::getRewriter()->getSuffix();
        if ($rewriterSuffix !== null) {
            $url .= $rewriterSuffix;
        }
        return $url;
    }

    public static function stripRewriterSuffix($url)
    {
        $rewriterSuffix = Url::getRewriter()->getSuffix();
        if ($rewriterSuffix !== null) {
            return substr($url, 0, (strlen($rewriterSuffix) * -1));
        }
        return $url;
    }

    public static function buildUrl($url, $fields = [])
    {
        $url = self::stripRewriterSuffix($url);
        $url .= '/';
        $url .= implode('-', array_map(
            function ($field) {
                return Url::getRewriter()->normalize($field);
            }, $fields)
        );
        $url = self::appendRewriterSuffix($url);
        return $url;
    }

    public static function generatePathFile($params)
    {
        $query = '  SELECT      `id`,
                                `article_id`,
                                `clang_id`,
                                `url`,
                                `table`,
                                `table_parameters`
                    FROM        ' . \rex::getTable('url_generate');

        $results = \rex_sql::factory()->setQuery($query)->getArray();
        if (count($results)) {
            foreach ($results as $result) {
                $articleId = $result['article_id'];
                $clangId = $result['clang_id'];

                $article = \rex_article::get($articleId, $clangId);
                if ($article instanceof \rex_article) {
                    $table = self::getTableObject($result['table'], $result['table_parameters']);

                    $queryWhere = '';
                    if ($table->restrictionField != '' && $table->restrictionValue != '' && in_array($table->restrictionOperator, self::getRestrictionOperators())) {
                        switch ($table->restrictionOperator) {
                            case 'FIND_IN_SET':
                                break;
                            case 'IN (...)':
                            case 'NOT IN (...)':
                                $table->restrictionOperator = str_replace(' (...)', '', $table->restrictionOperator);
                                $values = explode(',', $table->restrictionValue);
                                foreach ($values as $key => $value) {
                                    if (! (int)$value > 0) {
                                        unset($values[$key]);
                                    }
                                }
                                $table->restrictionValue = ' (' . implode(',', $values) . ') ';
                                break;

                            case 'BETWEEN':
                            case 'NOT BETWEEN':
                                $values = explode(',', $table->restrictionValue);
                                if (count($values) == 2) {
                                    $table->restrictionValue = $values[0] . ' AND ' . $values[1];
                                }
                                break;

                            default:
                                $table->restrictionValue = \rex_sql::factory()->escape($table->restrictionValue);
                                break;
                        }

                        switch ($table->restrictionOperator) {
                            case 'FIND_IN_SET':
                                $queryWhere = ' WHERE ' . $table->restrictionOperator . ' (' . $table->restrictionValue . ', ' . $table->restrictionField . ')';
                                break;

                            default:
                                $queryWhere = ' WHERE ' . $table->restrictionField . ' ' . $table->restrictionOperator . ' ' . $table->restrictionValue;
                                break;
                        }
                    }

                    $querySelect = [];
                    $querySelect[] = '`' . $table->id . '` AS id';
                    if (count(\rex_clang::getAll()) >= 2 && $clangId == '0' && $table->clang_id != '') {
                        $querySelect[] = '`' . $table->clang_id . '` AS clang_id';
                    }
                    if ($table->field_1 != '') {
                        $querySelect[] = '`' . $table->field_1 . '` AS field_1';
                    }
                    if ($table->field_2 != '') {
                        $querySelect[] = '`' . $table->field_2 . '` AS field_2';
                    }
                    if ($table->field_3 != '') {
                        $querySelect[] = '`' . $table->field_3 . '` AS field_3';
                    }
                    if (isset($table->seoTitle) && $table->seoTitle != '') {
                        $querySelect[] = '`' . $table->seoTitle . '` AS seo_title';
                    }
                    if (isset($table->seoDescription) && $table->seoDescription != '') {
                        $querySelect[] = '`' . $table->seoDescription . '` AS seo_description';
                    }

                    $querySelect = implode(',', $querySelect);

                    $query = 'SELECT  ' . $querySelect . ' FROM    ' . $table->name . ' ' . $queryWhere . '';
                    $entries = \rex_sql::factory($table->dbid)->setQuery($query)->getArray();
                    if (count($entries)) {
                        $savePaths = [];
                        foreach ($entries as $entry) {
                            $path = [];
                            if (isset($entry['field_1']) && $entry['field_1'] != '') {
                                $path[] = $entry['field_1'];
                            }
                            if (isset($entry['field_2']) && $entry['field_2'] != '') {
                                $path[] = $entry['field_2'];
                            }
                            if (isset($entry['field_3']) && $entry['field_3'] != '') {
                                $path[] = $entry['field_3'];
                            }

                            $articleClangId = $clangId;
                            if (count(\rex_clang::getAll()) >= 2 && $clangId == '0' && $table->clang_id != '') {
                                $articleClangId = $entry['clang_id'];
                            }
                            $articleUrl = Url::getRewriter()->getFullUrl($articleId, $articleClangId);
                            $articleUrl = self::stripRewriterSuffix($articleUrl) . '/';
                            $url = Url::parse($articleUrl);

                            $path = implode('-', $path);
                            $path .= (isset($savePaths[$path])) ? '-' . $entry['id'] : '';
                            $path = Url::getRewriter()->normalize($path);
                            $path .= Url::getRewriter()->getSuffix();

                            $object = new \stdClass();
                            $object->url = $url->appendPathSegment($path)->getUrl();
                            $object->fullUrl = $url->getFullUrl();
                            $object->pathNames = [];
                            $object->fullPathNames = [];
                            $object->urlParamKey = $table->urlParamKey;

                            //self::$paths[$url->getDomain()][$articleId][$entry['id']][$articleClangId]['root'] = $url->appendPathSegment($path)->getUrl();
                            //self::$paths[$url->getDomain()][$articleId][$entry['id']][$articleClangId]['path_names'] = [];
                            if (isset($table->pathNames) && $table->pathNames != '') {
                                $pathNames = explode("\n", trim($table->pathNames));
                                foreach ($pathNames as $pathName) {
                                    $urlForPathName = clone($url);
                                    $pathName = trim($pathName) . Url::getRewriter()->getSuffix();
                                    //self::$paths[$url->getDomain()][$articleId][$entry['id']][$articleClangId]['path_names'][] = $urlForPathName->appendPathSegment($pathName)->getUrl();
                                    $object->pathNames[] = $urlForPathName->appendPathSegment($pathName)->getUrl();
                                    $object->fullPathNames[] = $urlForPathName->getFullUrl();
                                }
                            }

                            if (isset($table->sitemapAdd) && $table->sitemapAdd == '1') {
                                $object->sitemap = true;
                                $object->sitemapFrequency = $table->sitemapFrequency;
                                $object->sitemapPriority = $table->sitemapPriority;
                            } else {
                                $object->sitemap = false;
                            }

                            if (isset($entry['seo_title'])) {
                                $object->seoTitle = $entry['seo_title'];
                            } else {
                                $object->seoTitle = '';
                            }

                            if (isset($entry['seo_description'])) {
                                $object->seoDescription = $entry['seo_description'];
                            } else {
                                $object->seoDescription = '';
                            }

                            self::$paths[$url->getDomain()][$articleId][$entry['id']][$articleClangId] = $object;

                            $savePaths[$path] = '';
                        }
                    }
                }
            }
           \rex_file::putCache(self::$pathfile, self::$paths);
        }
    }

    public static function getArticleParams()
    {
        self::ensurePaths();
        $currentUrl = Url::current();

        foreach (self::$paths as $domain => $articleIds) {
            if ($currentUrl->getDomain() == $domain) {
                foreach ($articleIds as $articleId => $ids) {
                    foreach ($ids as $id => $clangIds) {
                        foreach ($clangIds as $clangId => $object) {
                            if ($currentUrl->getPath() == $object->url || in_array($currentUrl->getPath(), $object->pathNames)) {
                                return ['article_id' => $articleId, 'clang' => $clangId];
                            }
                        }
                    }
                }
            }
        }
    }

    public static function getAll()
    {
        self::ensurePaths();
        $currentUrl = Url::current();

        foreach (self::$paths as $domain => $articleIds) {
            if ($currentUrl->getDomain() == $domain) {
                $all = [];
                foreach ($articleIds as $articleId => $ids) {
                    foreach ($ids as $id => $clangIds) {
                        foreach ($clangIds as $clangId => $object) {
                            $all[] = $object;
                        }
                    }
                }
                return $all;
            }
        }
        return false;
    }

    public static function getId()
    {
        self::ensurePaths();
        $currentUrl = Url::current();

        foreach (self::$paths as $domain => $articleIds) {
            if ($currentUrl->getDomain() == $domain) {
                foreach ($articleIds as $articleId => $ids) {
                    foreach ($ids as $id => $clangIds) {
                        foreach ($clangIds as $clangId => $object) {
                            if ($currentUrl->getPath() == $object->url || in_array($currentUrl->getPath(), $object->pathNames)) {
                                return $id;
                            }
                        }
                    }
                }
            }
        }
        return false;
    }

    public static function getData()
    {
        self::ensurePaths();
        $currentUrl = Url::current();

        foreach (self::$paths as $domain => $articleIds) {
            if ($currentUrl->getDomain() == $domain) {
                foreach ($articleIds as $articleId => $ids) {
                    foreach ($ids as $id => $clangIds) {
                        foreach ($clangIds as $clangId => $object) {
                            if ($currentUrl->getPath() == $object->url || in_array($currentUrl->getPath(), $object->pathNames)) {
                                return $object;
                            }
                        }
                    }
                }
            }
        }
        return false;
    }

    public static function getPath()
    {
        self::ensurePaths();
        $currentUrl = Url::current();

        foreach (self::$paths as $domain => $articleIds) {
            if ($currentUrl->getDomain() == $domain) {
                foreach ($articleIds as $articleId => $ids) {
                    foreach ($ids as $id => $clangIds) {
                        foreach ($clangIds as $clangId => $object) {
                            foreach ($object->pathNames as $pathName) {
                                if ($currentUrl->getPath() == $pathName) {
                                    return self::stripRewriterSuffix(str_replace($object->url, '', $pathName));
                                }
                            }
                        }
                    }
                }
            }
        }
        return false;
    }

    public static function getUrlById($primaryId, $articleId = null, $clangId = null)
    {
        if ((int) $primaryId < 1) {
            return;
        }
        if (null === $articleId || $articleId == '') {
            $articleId = \rex_article::getCurrentId();
        }
        if (null === $clangId) {
            $clangId = \rex_clang::getCurrentId();
        }

        self::ensurePaths();
        $currentUrl = Url::current();
        foreach (self::$paths as $domain => $articleIds) {
            if (isset($articleIds[$articleId][$primaryId][$clangId])) {
                if ($currentUrl->getDomain() == $domain) {
                    return $articleIds[$articleId][$primaryId][$clangId]->url;
                } else {
                    return $currentUrl->setHost($domain)->getSchemeAndHttpHost() . $articleIds[$articleId][$primaryId][$clangId]->url;
                }
            }
        }
    }

    public static function getArticleIdByUrlParamKey($paramKey)
    {
        self::ensurePaths();
        $currentUrl = Url::current();

        foreach (self::$paths as $domain => $articleIds) {
            if ($currentUrl->getDomain() == $domain) {
                foreach ($articleIds as $articleId => $ids) {
                    foreach ($ids as $id => $clangIds) {
                        foreach ($clangIds as $clangId => $object) {
                            if ($object->urlParamKey == $paramKey) {
                                return $articleId;
                            }
                        }
                    }
                }
            }
        }
        return false;
    }


    /**
     * gibt die Url eines Datensatzes zurück
     * wurde über rex_getUrl() aufgerufen
     */
    public static function rewrite($params = [])
    {
        // Url wurde von einer anderen Extension bereits gesetzt
        if (isset($params['subject']) && $params['subject'] != '') {
            return $params['subject'];
        }

        $articleId = $params['id'];
        $clangId = $params['clang'];
        $primaryId = 0;
        if (isset($params['params']['id'])) {
            $primaryId = $params['params']['id'];
            unset($params['params']['id']);
        } elseif (count($params['params']) > 0) {
            foreach ($params['params'] as $key => $value) {
                if ((int)$value > 0) {
                    $articleIdFound = self::getArticleIdByUrlParamKey($key);
                    if ($articleIdFound) {
                        $articleId = $articleIdFound;
                        $primaryId = (int)$value;
                        unset($params['params'][$key]);
                        break;
                    }
                }
            }
        }

        if ($primaryId > 0) {
            $url = self::getUrlById($primaryId, $articleId, $clangId);
            $urlParams = '';
            if (count($params['params'])) {
                $urlParams = \rex_string::buildQuery($params['params'], $params['separator']);
            }
            return $url . ($urlParams ? '?' . $urlParams : '');
        }
    }

    public static function ensurePaths()
    {
        if (empty(self::$paths)) {
            self::generatePathFile([]);
        }
    }

    public static function readPathFile()
    {
        if (!file_exists(self::$pathfile)) {
            self::generatePathFile([]);
        }
        self::$paths = \rex_file::getCache(self::$pathfile);
    }
}

