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
    public static $pathSlashPlaceholder = 'xbpxqdx';
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

    protected static function getTableObject($databaseAndTable, $parameters, $relationTable = false)
    {
        $parameters = json_decode($parameters, true);
        $databaseAndTableInformation = self::separateDatabaseAndTable($databaseAndTable);

        $table = new \stdClass();
        $table->dbid = $databaseAndTableInformation[0];
        if ($relationTable) {
            $table->name = substr($databaseAndTableInformation[1], 9);
        } else {
            $table->name = $databaseAndTableInformation[1];
        }
        $table->field_1 = $parameters[$databaseAndTable . '_field_1'];
        $table->field_2 = $parameters[$databaseAndTable . '_field_2'];
        $table->field_3 = $parameters[$databaseAndTable . '_field_3'];
        $table->id = $parameters[$databaseAndTable . '_id'];
        $table->clang_id = $parameters[$databaseAndTable . '_clang_id'];

        if (! $relationTable) {
            $table->relationField = $parameters[$databaseAndTable . '_relation_field'];
            $table->restrictionField = $parameters[$databaseAndTable . '_restriction_field'];
            $table->restrictionOperator = $parameters[$databaseAndTable . '_restriction_operator'];
            $table->restrictionValue = $parameters[$databaseAndTable . '_restriction_value'];
            $table->pathNames = $parameters[$databaseAndTable . '_path_names'];
            $table->pathCategories = $parameters[$databaseAndTable . '_path_categories'];
            $table->seoTitle = $parameters[$databaseAndTable . '_seo_title'];
            $table->seoDescription = $parameters[$databaseAndTable . '_seo_description'];
            $table->sitemapAdd = $parameters[$databaseAndTable . '_sitemap_add'];
            $table->sitemapFrequency = $parameters[$databaseAndTable . '_sitemap_frequency'];
            $table->sitemapPriority = $parameters[$databaseAndTable . '_sitemap_priority'];
            $table->sitemapLastmod = $parameters[$databaseAndTable . '_sitemap_lastmod'];
            $table->urlParamKey = $parameters[$databaseAndTable . '_url_param_key'];
        }
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
                                `table_parameters`,
                                `relation_table`,
                                `relation_table_parameters`,
                                `relation_insert`
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
                                $queryWhere = ' WHERE ' . $table->restrictionOperator . ' (' . $table->restrictionValue . ', ' . $table->name . '.' . $table->restrictionField . ')';
                                break;

                            default:
                                $queryWhere = ' WHERE ' . $table->name . '.' . $table->restrictionField . ' ' . $table->restrictionOperator . ' ' . $table->restrictionValue;
                                break;
                        }
                    }

                    $querySelect = [];
                    $querySelect[] = $table->name . '.' . $table->id . ' AS id';
                    if (count(\rex_clang::getAll()) >= 2 && $clangId == '0' && $table->clang_id != '') {
                        $querySelect[] = $table->name . '.' . $table->clang_id . ' AS clang_id';
                    }
                    if ($table->field_1 != '') {
                        $querySelect[] = $table->name . '.' . $table->field_1 . ' AS field_1';
                    }
                    if ($table->field_2 != '') {
                        $querySelect[] = $table->name . '.' . $table->field_2 . ' AS field_2';
                    }
                    if ($table->field_3 != '') {
                        $querySelect[] = $table->name . '.' . $table->field_3 . ' AS field_3';
                    }
                    if (isset($table->seoTitle) && $table->seoTitle != '') {
                        $querySelect[] = $table->name . '.' . $table->seoTitle . ' AS seo_title';
                    }
                    if (isset($table->seoDescription) && $table->seoDescription != '') {
                        $querySelect[] = $table->name . '.' . $table->seoDescription . ' AS seo_description';
                    }

                    $queryFrom = '';
                    $relationFlag = false;
                    $relationTable = '';
                    if (isset($table->relationField) && $table->relationField != '' &&
                        isset($result['relation_table']) && $result['relation_table'] != '') {
                        $relationFlag = true;
                        $relationTable = self::getTableObject($result['relation_table'], $result['relation_table_parameters'], true);

                        $queryFrom = 'LEFT JOIN ' . $relationTable->name . ' ON ' . $table->name . '.' . $table->relationField . ' = ' . $relationTable->name . '.' . $relationTable->id;

                        $querySelect[] = $relationTable->name . '.' . $relationTable->id . ' AS relation_id';
                        if (count(\rex_clang::getAll()) >= 2 && $clangId == '0' && $relationTable->clang_id != '') {
                            $querySelect[] = $relationTable->name . '.' . $relationTable->clang_id . ' AS relation_clang_id';
                        }
                        if ($relationTable->field_1 != '') {
                            $querySelect[] = $relationTable->name . '.' . $relationTable->field_1 . ' AS relation_field_1';
                        }
                        if ($relationTable->field_2 != '') {
                            $querySelect[] = $relationTable->name . '.' . $relationTable->field_2 . ' AS relation_field_2';
                        }
                        if ($relationTable->field_3 != '') {
                            $querySelect[] = $relationTable->name . '.' . $relationTable->field_3 . ' AS relation_field_3';
                        }
                    }

                    $querySelect = implode(',', $querySelect);

                    $query = 'SELECT ' . $querySelect . ' FROM ' . $table->name . ' ' . $queryFrom . ' ' . $queryWhere . '';
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
                            $path = Url::getRewriter()->normalize(implode('-', $path));

                            if ($relationFlag) {
                                $relationPath = [];
                                if (isset($entry['relation_field_1']) && $entry['relation_field_1'] != '') {
                                    $relationPath[] = $entry['relation_field_1'];
                                }
                                if (isset($entry['relation_field_2']) && $entry['relation_field_2'] != '') {
                                    $relationPath[] = $entry['relation_field_2'];
                                }
                                if (isset($entry['relation_field_3']) && $entry['relation_field_3'] != '') {
                                    $relationPath[] = $entry['relation_field_3'];
                                }
                                $relationPath = Url::getRewriter()->normalize(implode('-', $relationPath));

                                switch($result['relation_insert']) {
                                    case 'before':
                                        $path = $relationPath . '/' . $path;
                                        break;
                                    case 'after':
                                        $path = $path . '/' . $relationPath;
                                        break;
                                }
                            }

                            $articleClangId = $clangId;
                            if (count(\rex_clang::getAll()) >= 2 && $clangId == '0' && $table->clang_id != '') {
                                $articleClangId = $entry['clang_id'];
                            }
                            $articleUrl = Url::getRewriter()->getFullUrl($articleId, $articleClangId);
                            $articleUrl = self::stripRewriterSuffix($articleUrl) . '/';
                            $url = Url::parse($articleUrl);

                            $path .= (isset($savePaths[$path])) ? '-' . $entry['id'] : '';
                            $path .= Url::getRewriter()->getSuffix();

                            $url = \rex_extension::registerPoint(new \rex_extension_point('URL_GENERATOR_URL_CREATED', $url, [
                                'article_id' => $articleId,
                                'clang_id'   => $clangId,
                                'table'      => $table,
                                'data'       => $entry,
                            ]));
                            $path = \rex_extension::registerPoint(new \rex_extension_point('URL_GENERATOR_PATH_CREATED', $path, [
                                'article_id' => $articleId,
                                'clang_id'   => $clangId,
                                'table'      => $table,
                                'data'       => $entry,
                            ]));

                            $object = new \stdClass();
                            $object->articleId = $articleId;
                            $object->clangId = $articleClangId;
                            $object->table = $table;
                            $object->relationTable = $relationTable;
                            $object->url = $url->appendPathSegment($path)->getUrl();
                            $object->fullUrl = $url->getFullUrl();
                            $object->pathNames = [];
                            $object->fullPathNames = [];
                            $object->pathCategories = [];
                            $object->fullPathCategories = [];
                            $object->urlParamKey = $table->urlParamKey;

                            if (isset($table->pathNames) && $table->pathNames != '') {
                                $pathNames = explode("\n", trim($table->pathNames));
                                foreach ($pathNames as $pathName) {
                                    $urlForPathName = clone($url);
                                    $pathNameParts = explode('|', $pathName);
                                    $pathNameForUrl = trim($pathNameParts[0]);
                                    $pathNameForNav = trim($pathNameParts[0]);
                                    if (count($pathNameParts) == 2) {
                                        $pathNameForNav = trim($pathNameParts[1]);
                                    }
                                    // sicherstellen, dass selbst gesetzte "/" im Pfad erhalten bleiben. (ueber-uns/impressum)
                                    // normalize macht aus einem "/" > "-"
                                    $pathSegment =
                                        str_replace(
                                            self::$pathSlashPlaceholder, '/',
                                            Url::getRewriter()->normalize(
                                                str_replace(
                                                    '/', self::$pathSlashPlaceholder,
                                                    $pathNameForUrl))
                                            ) . Url::getRewriter()->getSuffix();
                                    $object->pathNames[$pathNameForNav] = $urlForPathName->appendPathSegment($pathSegment)->getUrl();
                                    $object->fullPathNames[$pathNameForNav] = $urlForPathName->getFullUrl();
                                }
                            }

                            if (isset($table->pathCategories) && $table->pathCategories == '1') {
                                $articleCategory = \rex_category::get($articleId, $articleClangId);
                                if ($articleCategory instanceof \rex_category) {
                                    $categories = $articleCategory->getChildren();
                                    if (count($categories)) {
                                        foreach ($categories as $category) {
                                            $urlForPathCategory = clone($url);
                                            $pathSegment = Url::getRewriter()->normalize(trim($category->getName())) . Url::getRewriter()->getSuffix();
                                            $object->pathCategories[$category->getId()] = $urlForPathCategory->appendPathSegment($pathSegment)->getUrl();
                                            $object->fullPathCategories[$category->getId()] = $urlForPathCategory->getFullUrl();
                                        }
                                    }
                                }
                            }

                            if (isset($table->sitemapAdd) && $table->sitemapAdd == '1') {
                                $object->sitemap = true;
                                $object->sitemapFrequency = $table->sitemapFrequency;
                                $object->sitemapPriority = $table->sitemapPriority;
                                $object->sitemapLastmod = $table->sitemapLastmod;
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

                            self::$paths[$url->getDomain()][$articleId][$entry['id']][$articleClangId] = (array) $object;

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
                            if ($currentUrl->getPath() == $object['url'] || in_array($currentUrl->getPath(), $object['pathNames'])) {
                                return ['article_id' => $articleId, 'clang' => $clangId];
                            }
                            if (false !== $categoryId = array_search($currentUrl->getPath(), $object['pathCategories'])) {
                                return ['article_id' => $categoryId, 'clang' => $clangId];
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
                            $all[] = (object)$object;
                        }
                    }
                }
                return $all;
            }
        }
        return false;
    }

    public static function getId($url = null)
    {
        self::ensurePaths();
        $currentUrl = Url::current();
        if ($url) {
            $currentUrl = Url::parse($url);
        }

        foreach (self::$paths as $domain => $articleIds) {
            if ($currentUrl->getDomain() == $domain) {
                foreach ($articleIds as $articleId => $ids) {
                    foreach ($ids as $id => $clangIds) {
                        foreach ($clangIds as $clangId => $object) {
                            if ($currentUrl->getPath() == $object['url'] || in_array($currentUrl->getPath(), $object['pathNames']) || in_array($currentUrl->getPath(), $object['pathCategories'])) {
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
                            if ($currentUrl->getPath() == $object['url'] || in_array($currentUrl->getPath(), $object['pathNames']) || in_array($currentUrl->getPath(), $object['pathCategories'])) {
                                return (object)$object;
                            }
                        }
                    }
                }
            }
        }
        return false;
    }

    public static function getCurrentOwnPath()
    {
        self::ensurePaths();
        $currentUrl = Url::current();

        foreach (self::$paths as $domain => $articleIds) {
            if ($currentUrl->getDomain() == $domain) {
                foreach ($articleIds as $articleId => $ids) {
                    foreach ($ids as $id => $clangIds) {
                        foreach ($clangIds as $clangId => $object) {
                            foreach ($object['pathNames'] as $pathName) {
                                if ($currentUrl->getPath() == $pathName) {
                                    return self::stripRewriterSuffix(str_replace($object['url'], '', $pathName));
                                }
                            }
                        }
                    }
                }
            }
        }
        return false;
    }

    public static function getOwnPaths()
    {
        self::ensurePaths();
        $currentUrl = Url::current();

        foreach (self::$paths as $domain => $articleIds) {
            if ($currentUrl->getDomain() == $domain) {
                foreach ($articleIds as $articleId => $ids) {
                    foreach ($ids as $id => $clangIds) {
                        foreach ($clangIds as $clangId => $object) {
                            return $object['pathNames'];
                        }
                    }
                }
            }
        }
        return false;
    }

    public static function getUrlById($primaryId, $articleId = null, $clangId = null, $returnFullUrl = false)
    {
        if ((int) $primaryId < 1) {
            return null;
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
                    if ($returnFullUrl) {
                        return $articleIds[$articleId][$primaryId][$clangId]['fullUrl'];
                    }
                    return $articleIds[$articleId][$primaryId][$clangId]['url'];
                } else {
                    return $currentUrl->setHost($domain)->getSchemeAndHttpHost() . $articleIds[$articleId][$primaryId][$clangId]['url'];
                }
            } else {
                foreach ($articleIds as $article_Id => $ids) {
                    foreach ($ids as $id => $clangIds) {
                        if ($id == $primaryId) {
                            foreach ($clangIds as $clang_Id => $object) {
                                if ($clang_Id == $clangId) {
                                    if (isset($object['pathCategories'][$articleId])) {
                                        if ($currentUrl->getDomain() == $domain) {
                                            return $object['pathCategories'][$articleId];
                                        } else {
                                            return $currentUrl->setHost($domain)->getSchemeAndHttpHost() . $object['pathCategories'][$articleId];
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    public static function getFullUrlById($primaryId, $articleId = null, $clangId = null)
    {
        return self::getUrlById($primaryId, $articleId, $clangId, true);
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
                            if ($object['urlParamKey'] == $paramKey) {
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
            self::readPathFile();
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

