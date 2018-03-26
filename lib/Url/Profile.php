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

class Profile
{
    const TABLE_NAME = 'url_generator_profile';

    /**
     * Number of possible relations
     */
    const ALIAS = 'data';

    /**
     * Separator
     */
    const COLUMN_PATH_SEPARATOR = '/';

    /**
     * Number of possible segments parts in the url
     * /part1-part2-part3/
     */
    const SEGMENT_PART_COUNT = 3;

    /**
     * Number of possible relations
     */
    const RELATION_COUNT = 3;

    /**
     * Prefix
     */
    const RELATION_PREFIX = 'relation_';

    /**
     * Number of possible restrictions
     */
    const RESTRICTION_COUNT = 3;

    /**
     * Prefix
     */
    const RESTRICTION_PREFIX = 'restriction_';


    /**
     * Original table values
     */
    protected $values;


    /** @var $relations ProfileRelation[] */
    protected $relations = [];

    /** @var $restrictions ProfileRestriction[] */
    protected $restrictions = [];

    protected $segmentParts;

    protected $appendStructureCategories = false;
    protected $appendUserPaths;
    protected $sitemap = false;
    protected $sitemapFrequency;
    protected $sitemapPriority;

    protected $dbId;
    protected $tableName;

    public function __construct($values)
    {
        $this->values = $values;

        if ($this->getValue('table_parameters')) {
            $this->setValue('table_parameters', json_decode($this->values['table_parameters'], true));
        }

        $group = Database::split($this->getValue('table_name'));
        $this->dbId = $group[0];
        $this->tableName = $group[1];

        for ($index = 1; $index <= self::RELATION_COUNT; $index++) {
            $relationColumnName = $this->getValue(self::RELATION_PREFIX . $index . '_column');
            $relationTableParams = $this->getValue(self::RELATION_PREFIX . $index . '_table_parameters');

            if ($relationColumnName && $relationColumnName != '' && $relationTableParams) {
                $relationTableParams = json_decode($relationTableParams, true);
                $relationTableName = $this->getValue(self::RELATION_PREFIX . $index . '_table_name');

                $segmentPosition = strtoupper($this->getValue(self::RELATION_PREFIX . $index . '_position'));
                $segmentPosition = $segmentPosition == 'BEFORE' ? $segmentPosition : 'AFTER';

                $relation = new ProfileRelation($index, $relationColumnName, $segmentPosition, $relationTableName, $relationTableParams);
                if ($relation->isValid()) {
                    $this->relations[] = $relation;
                }
            }
        }

        for ($index = 1; $index <= self::RESTRICTION_COUNT; $index++) {
            $restrictionColumnName = $this->getValue(self::RESTRICTION_PREFIX . $index . '_column');
            $restrictionComparisonOperator = $this->getValue(self::RESTRICTION_PREFIX . $index . '_comparison_operator');
            $restrictionValue = trim($this->getValue(self::RESTRICTION_PREFIX . $index . '_value'));
            $restrictionOperator = $this->getValue(self::RESTRICTION_PREFIX . $index . '_logical_operator');

            if ($restrictionColumnName && $restrictionColumnName != '' && $restrictionValue && $restrictionValue != '') {
                $restriction = new ProfileRestriction($index, $restrictionColumnName, $restrictionComparisonOperator, $restrictionValue, $restrictionOperator);
                if ($restriction->isValid()) {
                    $this->restrictions[] = $restriction;
                }
            }
        }

        for ($index = 1; $index <= self::SEGMENT_PART_COUNT; $index++) {
            $columnName = $this->getValue('column_segment_part_' . $index);
            if ($columnName && $columnName != '') {
                $separator = $this->getValue('column_segment_part_' . $index . '_separator');
                $separator = ($separator && $separator != '') ? $separator : null;
                $this->segmentParts[$index] = ['column_name' => $columnName, 'separator' => $separator];
            }
        }


        if (trim($this->getValue('append_user_paths')) != '') {
            $this->appendUserPaths = trim($this->getValue('append_user_paths'));
        }

        if ($this->getValue('append_structure_categories') === '1') {
            $this->appendStructureCategories = true;
        }

        if ($this->getValue('sitemap_add') === '1') {
            $this->sitemap = true;
        }

        $this->sitemapFrequency = $this->getValue('sitemap_frequency');
        $this->sitemapPriority = $this->getValue('sitemap_priority');
    }


    public function getArticleId()
    {
        return $this->getValue('article_id');
    }

    /*
     * @return null|int
     */
    public function getArticleClangId()
    {
        // Sprache vom Struktur Artikel zurückgeben
        // bei einer Sprache wird 1 zurückgegeben
        $clang_id = (int)$this->getValue('clang_id');
        if ($clang_id > 0) {
            return $clang_id;
        }
        // Ansonsten null und die Sprache muss über die Datentabelle ermittelt werden.
        return null;
    }

    public function getId()
    {
        return $this->getValue('id');
    }

    public function getNamespace()
    {
        return $this->getValue('namespace');
    }

    public function getValue($key)
    {
        $value = isset($this->values[$key]) ? $this->values[$key] : null;
        if ($value) {
            return $value;
        }
        return $this->getTableParam($key);
    }

    public function setValue($key, $value)
    {
        $this->values[$key] = $value;
    }

    public function getTableParams()
    {
        return $this->getValue('table_parameters');
    }

    public function getTableParam($key)
    {
        $params = $this->getTableParams();
        return isset($params[$key]) ? $params[$key] : null;
    }

    public function hasRelations()
    {
        return count($this->getRelations());
    }

    // public function getRelation($number)
    // {
    //     if (isset($this->relations[$number])) {
    //         return $this->relations[$number];
    //     }
    //     return null;
    // }

    public function getRelations()
    {
        return $this->relations;
    }

    public function hasRestrictions()
    {
        return count($this->getRestrictions());
    }

    public function getRestrictions()
    {
        return $this->restrictions;
    }

    public function getSegmentPart($index)
    {
        return isset($this->getSegmentParts()[$index]) ? $this->getSegmentParts()[$index] : null;
    }

    public function getSegmentParts()
    {
        return $this->segmentParts;
    }

    /**
     * @return bool
     */
    public function inSitemap()
    {
        return $this->sitemap;
    }

    public function getSitemapFrequency()
    {
        return $this->sitemapFrequency;
    }

    public function getSitemapPriority()
    {
        return $this->sitemapPriority;
    }

    public function getTableName()
    {
        return $this->tableName;
    }

    protected function getDataItem($primaryColumnName, $primaryId)
    {
        $query = $this->buildQuery();
        $query->where(self::ALIAS . '.' . $primaryColumnName, $primaryId);
        // $items = \rex_sql::factory()->setDebug()->getArray($query->getQuery(), $query->getParams());
        return $query->find();
    }

    protected function getDataItems()
    {
        $query = $this->buildQuery();
        // $items = \rex_sql::factory()->setDebug()->getArray($query->getQuery(), $query->getParams());
        return $query->find();
    }

    protected function buildQuery()
    {
        $query = \rex_yform_manager_query::get($this->getTableName());
        $query->alias(self::ALIAS);

        // Reset, "alias.*" löschen
        $query->resetSelect();

        // Order erzwingen für nicht YForm-Tabellen
        $query->orderBy(self::ALIAS . '_id');

        $query->select(self::ALIAS . '.' . $this->getValue('column_id'), 'id');
        $query->select(self::ALIAS . '.' . $this->getValue('column_id'), self::ALIAS . '_id');
        if (null === $this->getArticleClangId() && $this->getValue('column_clang_id') != '') {
            $query->select(self::ALIAS . '.' . $this->getValue('column_clang_id'), self::ALIAS . '_clang_id');
        }

        if (count($this->getSegmentParts())) {
            // $query->selectRaw('CONCAT(' .
            //     implode(', "' . self::COLUMN_PATH_SEPARATOR . '", ', array_map(
            //         function ($field) {
            //             return self::ALIAS . '.' . $field;
            //         }, $this->getSource()->getSegmentPartColumnNames())
            //     ) . ')',
            //     self::ALIAS . '_segment_part'
            // );

            foreach ($this->getSegmentParts() as $index => $segmentPart) {
                $query->select(self::ALIAS . '.' . $segmentPart['column_name'], self::ALIAS . '_segment_part_' . $index);
            }
        }

        if ($this->getValue('column_seo_title') != '') {
            $query->select(self::ALIAS . '.' . $this->getValue('column_seo_title'), self::ALIAS . '_seo_title');
        }
        if ($this->getValue('column_seo_description') != '') {
            $query->select(self::ALIAS . '.' . $this->getValue('column_seo_description'), self::ALIAS . '_seo_description');
        }
        if ($this->getValue('column_seo_image') != '') {
            $query->select(self::ALIAS . '.' . $this->getValue('column_seo_image'), self::ALIAS . '_seo_image');
        }
        if ($this->getValue('column_sitemap_lastmod') != '') {
            $query->select(self::ALIAS . '.' . $this->getValue('column_sitemap_lastmod'), self::ALIAS . '_sitemap_lastmod');
        }

        $firstSegmentPart = $this->getSegmentPart(1);
        $query->where(self::ALIAS . '.' . $firstSegmentPart['column_name'], '', '!=');
        $query->whereRaw(self::ALIAS . '.' . $firstSegmentPart['column_name'] . ' IS NOT NULL');


        if ($this->hasRestrictions()) {
            $where = [];
            foreach ($this->getRestrictions() as $index => $restriction) {
                if (!empty($where)) {
                    $where[] = $restriction->getWhereOperator();
                }
                $where[] = $restriction->getWhere();
            }
            $query->whereRaw(implode(' ', $where));
        }

        if ($this->hasRelations()) {
            foreach ($this->getRelations() as $relation) {
                $query = $relation->completeQuery($query, $this->getArticleClangId(), $this->getValue('column_clang_id'));
            }
        }

        return $query;
    }

    protected function concatColumns($columns)
    {
        return implode('', array_map(
            function ($column) {
                $separator = $column['separator'] === null ? '' : $column['separator'];
                return $separator . Url::getRewriter()->normalize($column['value']);
            }, $columns)
        );
    }

    /**
     * @param \rex_yform_manager_dataset $dataset
     *
     * @ return array with Url Objects, A data set can return more than one url object. (Dataset url, Append own paths, Append categories)
     */
    public function createAndSaveUrls(\rex_yform_manager_dataset $dataset)
    {
        $articleId = $this->getArticleId();
        $clangId = $this->getArticleClangId();
        if (null === $clangId && $this->getValue('column_clang_id') != '') {
            $clangId = $dataset->getValue(self::ALIAS . '_clang_id');
        }

        $url = new Url(Url::getRewriter()->getFullUrl($articleId, $clangId));
        $url->handleRewriterSuffix();
        $url->withScheme('');

        $dataPath = new Url('');

        $columnValues = [];
        foreach ($this->getSegmentParts() as $index => $segmentPart) {
            $columnValues[] = ['value' => $dataset->getValue(self::ALIAS . '_segment_part_' . $index), 'separator' => ($segmentPart['separator'])];
        }
        $dataPath->appendPathSegments(explode('/', $this->concatColumns($columnValues)));


        if ($this->hasRelations()) {
            $beforeColumnValues = [];
            $afterColumnValues = [];
            foreach ($this->getRelations() as $relation) {
                $columnValues = [];
                foreach ($relation->getSegmentParts() as $index => $segmentPart) {
                    $columnValues[] = ['value' => $dataset->getValue($relation->getAlias() . '_segment_part_' . $index), 'separator' => ($segmentPart['separator'])];
                }
                if ($relation->getSegmentPosition() === 'BEFORE') {
                    $beforeColumnValues = array_merge($beforeColumnValues, explode('/', $this->concatColumns($columnValues)));
                } else {
                    $afterColumnValues = array_merge($afterColumnValues, explode('/', $this->concatColumns($columnValues)));
                }
            }
            if (count($beforeColumnValues)) {
                $dataPath->prependPathSegments($beforeColumnValues);
            }
            if (count($afterColumnValues)) {
                $dataPath->appendPathSegments($afterColumnValues);
            }
        }
        $url->appendPathSegments($dataPath->getSegments());

        $urlObjects = [];
        $urlObjects[] = [
            'article_id' => $articleId,
            'object' => $url,
            'user_path' => false,
            'structure' => false,
            'seo' => [
                'title' => ($dataset->hasValue(self::ALIAS . '_seo_title') ? $dataset->getValue(self::ALIAS . '_seo_title') : false),
                'description' => ($dataset->hasValue(self::ALIAS . '_seo_description') ? $dataset->getValue(self::ALIAS . '_seo_description') : false),
                'image' => ($dataset->hasValue(self::ALIAS . '_seo_image') ? $dataset->getValue(self::ALIAS . '_seo_image') : false),
            ],
        ];

        if ($this->appendStructureCategories) {
            $articleCategory = \rex_category::get($articleId, $clangId);
            if ($articleCategory) {
                $categories = $articleCategory->getChildren();
                if (count($categories)) {
                    foreach ($categories as $category) {
                        $urlCategory = clone $url;
                        $urlCategory->appendPathSegments([$category->getName()]);
                        $urlObjects[] = [
                            'article_id' => $category->getId(),
                            'object' => $urlCategory,
                            'user_path' => false,
                            'structure' => true,
                            'seo' => [],
                        ];
                    }
                }
            }
        }

        if ($this->appendUserPaths) {
            $userPaths = explode("\n", $this->appendUserPaths);
            foreach ($userPaths as $userPathLine) {
                $userPathParts = explode('=', $userPathLine);

                $urlUserPath = clone $url;
                $urlUserPath->appendPathSegments(explode('/', trim($userPathParts[0])));
                $urlObjects[] = [
                    'article_id' => $articleId,
                    'object' => $urlUserPath,
                    'user_path' => true,
                    'structure' => false,
                    'seo' => [],
                ];
            }
        }


        foreach ($urlObjects as $urlObject) {
            /* @var $urlInstance \Url */
            $urlInstance = $urlObject['object'];
            $urlAsString = $urlInstance->__toString();

            $manager = UrlManagerSql::factory();
            $manager->setArticleId($urlObject['article_id']);
            $manager->setClangId($clangId);
            $manager->setDataId($dataset->getId());
            $manager->setProfileId($this->getId());
            $manager->setSeo($urlObject['seo']);
            $manager->setSitemap($this->sitemap);
            $manager->setStructure($urlObject['structure']);
            $manager->setUrl($urlAsString);
            $manager->setUserPath($urlObject['user_path']);

            $manager->setLastmod();
            if ($dataset->hasValue(self::ALIAS . '_sitemap_lastmod')) {
                $manager->setLastmod($dataset->getValue(self::ALIAS . '_sitemap_lastmod'));
            }

            if (!$manager->save()) {
                // $return[] = $urlAsString;
            }
        }
    }

    public function buildUrls()
    {
        $items = $this->getDataItems();
        foreach ($items as $item) {
            $this->createAndSaveUrls($item);
        }
    }

    public function buildDatasetUrls($datasetId, $datasetColumnName)
    {
        $items = $this->getDataItem($datasetColumnName, $datasetId);
        foreach ($items as $item) {
            $this->createAndSaveUrls($item);
        }
    }


    public function deleteUrls()
    {
        UrlManagerSql::deleteByProfile($this);
        return $this;
    }

    public function deleteDatasetUrls($datasetId)
    {
        UrlManagerSql::deleteByProfileWithDatasetId($this, $datasetId);
        return $this;
    }


    /**
     * @return null|UrlManager[]
     */
    public function getUrls()
    {
        return UrlManager::getUrlsByProfile($this);
    }

    /**
     * @param int $id
     *
     * @return null|static
     */
    public static function get($id)
    {
        $sql = \rex_sql::factory();
        // $sql->setDebug();
        $sql->setTable(\rex::getTable(self::TABLE_NAME));
        $sql->setWhere('id = ?', [$id]);
        $sql->select();
        if ($sql->getRows() == 1) {
            $instance = new self($sql->getArray()[0]);
            return $instance;
        }

        return null;
    }

    /**
     * @return null|Profile[]
     */
    public static function getAll()
    {
        $sql = \rex_sql::factory();
        // $sql->setDebug();
        $sql->setTable(\rex::getTable(self::TABLE_NAME));
        $sql->select();
        if ($sql->getRows() >= 1) {
            $instances = [];
            foreach ($sql->getArray() as $item) {
                $instances[] = new self($item);
            }
            return $instances;
        }

        return null;
    }

    /**
     * @param int $articleId
     * @param int $clangId
     *
     * @return null|static[]
     */
    public static function getByArticleId($articleId, $clangId)
    {
        $clangId = (int)$clangId;
        $sql = \rex_sql::factory();
        // $sql->setDebug();
        $sql->setTable(\rex::getTable(self::TABLE_NAME));
        $sql->setWhere('article_id = ? AND clang_id = ?', [$articleId, $clangId]);
        $sql->select();

        if ($sql->getRows() < 1) {
            // Profile können mit clang_id=0 gespeichert werden, falls der Artikel für alle Sprachen gelten soll
            // ExtensionPoints aus der Struktur übergeben immer eine clang_id >= 1 und so würden die Profile nicht gefunden
            $sql->setTable(\rex::getTable(self::TABLE_NAME));
            $sql->setWhere('article_id = ?', [$articleId]);
            $sql->select();
        }

        if ($sql->getRows() >= 1) {
            $instances = [];
            foreach ($sql->getArray() as $item) {
                $instances[] = new self($item);
            }
            return $instances;
        }

        return null;
    }

    /**
     * @param string $namespace
     *
     * @return null|static[]
     */
    public static function getByNamespace($namespace)
    {
        $sql = \rex_sql::factory();
        // $sql->setDebug();
        $sql->setTable(\rex::getTable(self::TABLE_NAME));
        $sql->setWhere('namespace = ?', [$sql->escape($namespace)]);
        $sql->select();

        if ($sql->getRows() >= 1) {
            $instances = [];
            foreach ($sql->getArray() as $item) {
                $instances[] = new self($item);
            }
            return $instances;
        }

        return null;
    }


    /**
     * @param string $tableName
     *
     * @return null|static[]
     */
    public static function getByTableName($tableName)
    {
        // $clangId = (int)$clangId;
        $sql = \rex_sql::factory();
        // $sql->setDebug();
        $sql->setTable(\rex::getTable(self::TABLE_NAME));

        $where = [];
        $whereParams = [];
        $dbConfigs = Database::getAll();
        foreach ($dbConfigs as $dbId => $dbConfig) {
            $where[] = 'table_name = ?';
            $whereParams[] = $sql->escape(Database::merge($dbId, $tableName));
        }
        $sql->setWhere(implode(' OR ', $where), $whereParams);
        $sql->select();

        if ($sql->getRows() >= 1) {
            $instances = [];
            foreach ($sql->getArray() as $item) {
                $instances[] = new self($item);
            }
            return $instances;
        }

        return null;
    }


    public static function getSegmentPartSeparators()
    {
        return [
            '/' => '/',
            '-' => '-',
        ];
    }
}
