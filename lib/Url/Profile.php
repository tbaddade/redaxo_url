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
     * Number of possible relations.
     */
    const ALIAS = 'data';

    /**
     * Separator.
     */
    const COLUMN_PATH_SEPARATOR = '/';

    /**
     * Number of possible segments parts in the url
     * /part1-part2-part3/.
     */
    const SEGMENT_PART_COUNT = 3;

    /**
     * Number of possible relations.
     */
    const RELATION_COUNT = 3;

    /**
     * Prefix.
     */
    const RELATION_PREFIX = 'relation_';

    /**
     * Number of possible restrictions.
     */
    const RESTRICTION_COUNT = 3;

    /**
     * Prefix.
     */
    const RESTRICTION_PREFIX = 'restriction_';

    private static $cacheLoaded = false;
    private static $profiles = [];

    /**
     * Original table values.
     */
    // protected $values;

    // protected $segmentParts;
    //
    // protected $appendStructureCategories = false;
    // protected $appendUserPaths;

    // protected $sitemap = false;
    // protected $sitemapFrequency;
    // protected $sitemapPriority;
    //
    // protected $dbId;
    // protected $tableName;

    private $append_structure_categories;
    private $append_user_paths;
    private $article_id;
    private $clang_id;
    private $ep_pre_save_called;
    private $createdate;
    private $createuser;
    private $id;
    private $namespace;

    /** @var $relations ProfileRelation[] */
    private $relations = [];

    /** @var $restrictions ProfileRestriction[] */
    // private $restrictions = [];

    private $segment_part_separators;
    private $sitemap_add = false;
    private $sitemap_frequency;
    private $sitemap_priority;
    private $table;
    private $updatedate;
    private $updateuser;

    public function __construct()
    {
    }

    public function normalize()
    {
        for ($index = 1; $index <= self::RELATION_COUNT; ++$index) {
            if ($this->getColumnName(self::RELATION_PREFIX.$index) == '' || !isset($this->table['relations'][$index]['table']['name'])) {
                unset($this->table['relations'][$index]);
            }
        }
        foreach ($this->table['relations'] as $index => $values) {
            $this->relations[] = new ProfileRelation($index, $values);
        }

        for ($index = 1; $index <= self::RESTRICTION_COUNT; ++$index) {
            if ($this->getColumnName(self::RESTRICTION_PREFIX.$index) == '' || ($this->table['restrictions'][$index]['value'] == '' && !in_array($this->table['restrictions'][$index]['comparison_operator'], Database::getComparisonOperatorsForEmptyValue()))) {
                unset($this->table['restrictions'][$index]);
            }
        }

        // Ist keine Sprache des Strukturartikels ausgewählt (alle Sprachen) wird null gesetzt und die Sprache muss über die Datentabelle ermittelt werden.
        if ((int) $this->clang_id < 1) {
            $this->clang_id = null;
        }
    }

    public function getArticleId()
    {
        return $this->article_id;
    }

    /*
     * @return null|int
     */
    public function getArticleClangId()
    {
        return $this->clang_id;
    }

    public function getArticleUrl()
    {
        return new Url(Url::getRewriter()->getFullUrl($this->getArticleId(), $this->getArticleClangId()));
    }

    public function getId()
    {
        return (int) $this->id;
    }

    public function getNamespace()
    {
        return $this->namespace;
    }

    /**
     * @return bool
     */
    public function hasPreSaveCalled()
    {
        return (bool) $this->ep_pre_save_called;
    }

    public function hasRelations()
    {
        return count($this->getRelations());
    }

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
        return \rex_extension::registerPoint(new \rex_extension_point('URL_PROFILE_RESTRICTION', $this->table['restrictions'], [
             'profile' => $this
        ]));
    }

    /**
     * @return array
     */
    public function getSegmentPartSeparators()
    {
        return $this->segment_part_separators;
    }

    /**
     * @return bool
     */
    public function appendStructureCategories()
    {
        return (bool) $this->append_structure_categories;
    }

    /**
     * @return string
     */
    public function appendUserPaths()
    {
        return $this->append_user_paths;
    }

    /**
     * @return array
     */
    public function getUserPaths()
    {
        $array = [];
        $lines = explode("\n", $this->append_user_paths);
        foreach ($lines as $line) {
            $parts = explode('=', $line);
            if (!isset($parts[1])) {
                $parts[1] = $parts[0];
            }
            $array[trim($parts[0])] = trim($parts[1]);

        }
        return $array;
    }

    /**
     * @return bool
     */
    public function inSitemap()
    {
        return (bool) $this->sitemap_add;
    }

    public function getSitemapFrequency()
    {
        return $this->sitemap_frequency;
    }

    public function getSitemapPriority()
    {
        return $this->sitemap_priority;
    }

    public function getDatabaseId()
    {
        return (int) $this->table['dbid'];
    }

    public function getTableName()
    {
        return $this->table['name'];
    }

    public function addColumnName($column)
    {
        return $this->table['column_names'][$column] = $column;
    }

    public function getColumnName($column)
    {
        return $this->table['column_names'][$column];
    }

    public function getColumnNameWithAlias($column, $backtick = false)
    {
        $format = '%s.%s';
        if ($backtick) {
            $format = '`%s`.`%s`';
        }
        return sprintf($format, self::ALIAS, $this->getColumnName($column));
    }

    public function buildUrls()
    {
        $items = $this->getDatasets();
        foreach ($items as $item) {
            $this->createAndSaveUrls($item);
        }
    }

    public function buildUrlsByDatasetId($datasetId)
    {
        $items = $this->getDataset('id', $datasetId);
        foreach ($items as $item) {
            $this->createAndSaveUrls($item);
        }
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
        if (null === $clangId && $this->getColumnName('clang_id') != '') {
            $clangId = $dataset->getValue(self::ALIAS.'_clang_id');
        }

        $url = new Url(Url::getRewriter()->getFullUrl($articleId, $clangId));
        $url->withScheme('');

        $dataPath = new Url('');

        $concatSegmentParts = '';
        for ($index = 1; $index <= self::SEGMENT_PART_COUNT; ++$index) {
            if ($dataset->hasValue(self::ALIAS.'_segment_part_'.$index)) {
                $concatSegmentParts .= $this->getSegmentPartSeparators()[$index] ?? '';
                $concatSegmentParts .= Url::getRewriter()->normalize($dataset->getValue(self::ALIAS.'_segment_part_'.$index), $clangId);
            }
        }
        $dataPath->appendPathSegments(explode('/', $concatSegmentParts), $clangId);

        if ($this->hasRelations()) {
            $append = [];
            $prepend = [];
            foreach ($this->getRelations() as $relation) {
                $concatSegmentParts = '';
                for ($index = 1; $index <= self::SEGMENT_PART_COUNT; ++$index) {
                    if ($dataset->hasValue($relation->getAlias().'_segment_part_'.$index)) {
                        $concatSegmentParts .= $relation->getSegmentPartSeparators()[$index] ?? '';
                        $concatSegmentParts .= Url::getRewriter()->normalize($dataset->getValue($relation->getAlias().'_segment_part_'.$index), $clangId);
                    }
                }
                if ($relation->getSegmentPosition() === 'BEFORE') {
                    $prepend = array_merge($prepend, explode('/', $concatSegmentParts));
                } else {
                    $append = array_merge($append, explode('/', $concatSegmentParts));
                }
            }

            if ($prepend) {
                $dataPath->prependPathSegments($prepend, $clangId);
            }
            if ($append) {
                $dataPath->appendPathSegments($append, $clangId);
            }
        }
        $url->appendPathSegments($dataPath->getSegments(), $clangId);

        $urlObjects = [];
        $urlObjects[] = [
            'article_id' => $articleId,
            'object' => $url,
            'user_path' => false,
            'structure' => false,
            'seo' => [
                'title' => $dataset->hasValue(self::ALIAS.'_seo_title') ? $dataset->getValue(self::ALIAS.'_seo_title') : false,
                'description' => $dataset->hasValue(self::ALIAS.'_seo_description') ? $dataset->getValue(self::ALIAS.'_seo_description') : false,
                'image' => $dataset->hasValue(self::ALIAS.'_seo_image') ? $dataset->getValue(self::ALIAS.'_seo_image') : false,
            ],
        ];

        if ($this->appendStructureCategories()) {
            $articleCategory = \rex_category::get($articleId, $clangId);
            if ($articleCategory) {
                $categories = $articleCategory->getChildren();
                if (count($categories)) {
                    foreach ($categories as $category) {
                        $urlCategory = clone $url;
                        $urlCategory->appendPathSegments([$category->getName()], $clangId);
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

        if ($this->appendUserPaths()) {
            $userPaths = $this->getUserPaths();
            foreach ($userPaths as $userPath => $userPathLabel) {
                $urlUserPath = clone $url;
                $urlUserPath->appendPathSegments(explode('/', $userPath), $clangId);
                $urlObjects[] = [
                    'article_id' => $articleId,
                    'object' => $urlUserPath,
                    'user_path' => true,
                    'structure' => false,
                    'seo' => [],
                ];
            }
        }
        $preSaveCalled = false;
        foreach ($urlObjects as $urlObject) {
            /* @var $urlInstance Url */
            $urlInstance = $urlObject['object'];

            $urlObject['clang_id'] = $clangId;
            $urlObject['data_id'] = $dataset->getId();
            $urlObject['profile_id'] = $this->getId();
            $urlObject['sitemap'] = $this->inSitemap();
            $urlObject['profile'] = $this;

            $preUrlInstance = $urlInstance;
            $urlInstance = \rex_extension::registerPoint(new \rex_extension_point('URL_PRE_SAVE', $urlInstance, $urlObject));

            if (!$urlInstance instanceof Url) {
                throw new \rex_exception('Your returned Url is not an instance of Url\Url');
            }

            if (false === $preSaveCalled && $preUrlInstance->getPath() != $urlInstance->getPath()) {
                $preSaveCalled = true;
            }

            $urlAsString = $urlInstance->toString();
            $manager = UrlManagerSql::factory();
            $manager->setArticleId($urlObject['article_id']);
            $manager->setClangId($urlObject['clang_id']);
            $manager->setDataId($urlObject['data_id']);
            $manager->setProfileId($urlObject['profile_id']);
            $manager->setSeo($urlObject['seo']);
            $manager->setSitemap($urlObject['sitemap']);
            $manager->setStructure($urlObject['structure']);
            $manager->setUrl($urlAsString);
            $manager->setUserPath($urlObject['user_path']);

            $manager->setLastmod();
            if ($dataset->hasValue(self::ALIAS.'_sitemap_lastmod')) {
                $manager->setLastmod($dataset->getValue(self::ALIAS.'_sitemap_lastmod'));
            }

            if (!$manager->save()) {
                // $return[] = $urlAsString;
            }
        }

        if ($preSaveCalled) {
            $sql = \rex_sql::factory()
                ->setTable(\rex::getTable(Profile::TABLE_NAME))
                ->setWhere('id = :id', ['id' => $this->getId()])
                ->setValue('ep_pre_save_called', 1);

            if ($sql->update()) {
                Cache::deleteProfiles();
            }
        }
    }

    public function deleteUrls()
    {
        UrlManagerSql::deleteByProfileId($this->getId());
        return $this;
    }

    public function deleteUrlsByDatasetId($datasetId)
    {
        UrlManagerSql::deleteByProfileIdAndDatasetId($this->getId(), $datasetId);
        return $this;
    }

    /**
     * @return null|UrlManager[]
     */
    public function getUrls()
    {
        return UrlManager::getByProfileId($this->getId());
    }

    /**
     * @param string $key
     * @param string $value
     *
     * @return array
     */
    public function getUrlsAsKeyValuePair(string $key, string $value): array
    {
        $urls = $this->getUrls();
        if (!$urls) {
            return [];
        }

        $array = [];
        foreach ($urls as $url) {
            $array[$url->getValue($key)] = $url->getValue($value);
        }

        return $array;
    }

    /**
     * Returns the profile object for the given id.
     *
     * @param int $id Profile id
     *
     * @return self
     */
    public static function get($id)
    {
        if (self::exists($id)) {
            return self::$profiles[$id];
        }
        return null;
    }

    /**
     * Returns an array of all profiles.
     *
     * @return self[]
     */
    public static function getAll()
    {
        self::checkCache();
        return self::$profiles;
    }

    /**
     * Returns an array of all profile ids.
     *
     * @return array
     */
    public static function getAllArticleIds()
    {
        self::checkCache();

        return array_unique(array_map(function (self $profile) {
                return $profile->getArticleId();
            }, self::$profiles)
        );
    }

    /**
     * Returns an array of all profiles for the given articleId and clangId.
     *
     * @param int $articleId
     * @param int $clangId
     *
     * @return self[]
     */
    public static function getByArticleId($articleId, $clangId)
    {
        self::checkCache();

        return array_filter(self::$profiles, function (self $profile) use ($articleId, $clangId) {
            // Profile können mit clang_id=0 (via normalize null) gespeichert werden, falls der Artikel für alle Sprachen gelten soll
            // ExtensionPoints aus der Struktur übergeben aber immer eine clang_id >= 1 und so würden die Profile nicht gefunden
            return
                ($articleId == $profile->getArticleId() && $clangId == $profile->getArticleClangId())
                || ($articleId == $profile->getArticleId() && null === $profile->getArticleClangId())
            ;
        });
    }

    /**
     * Returns an array of all profiles for the given namespace.
     *
     * @param string $namespace
     *
     * @return self[]
     */
    public static function getByNamespace($namespace)
    {
        self::checkCache();

        return array_filter(self::$profiles, function (self $profile) use ($namespace) {
            return $namespace == $profile->getNamespace();
        });
    }

    /**
     * Returns an array of all profiles for the given tableName and databaseId.
     *
     * @param string $tableName
     * @param int    $dbId
     *
     * @return self[]
     */
    public static function getByTableName($tableName, $dbId = 1)
    {
        self::checkCache();

        return array_filter(self::$profiles, function (self $profile) use ($tableName, $dbId) {
            return $tableName == $profile->getTableName() && $dbId == $profile->getDatabaseId();
        });
    }

    /**
     * Checks if the given profile exists.
     *
     * @param int $id Profile id
     *
     * @return bool
     */
    public static function exists($id)
    {
        self::checkCache();
        return isset(self::$profiles[$id]);
    }

    protected function getDataset($primaryColumnName, $primaryId)
    {
        $query = $this->buildQuery();
        $query->where($this->getColumnNameWithAlias($primaryColumnName), $primaryId);
        // $items = \rex_sql::factory()->setDebug()->getArray($query->getQuery(), $query->getParams());
        return $query->find();
    }

    protected function getDatasets()
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
        $query->orderBy(self::ALIAS.'_id');

        $query->select($this->getColumnNameWithAlias('id'), 'id');
        $query->select($this->getColumnNameWithAlias('id'), self::ALIAS.'_id');
        if (null === $this->getArticleClangId() && $this->getColumnName('clang_id') != '') {
            $query->select($this->getColumnNameWithAlias('clang_id'), self::ALIAS.'_clang_id');
        }

        $columns = ['seo_description', 'seo_image', 'sitemap_lastmod', 'seo_title'];
        for ($index = 1; $index <= self::SEGMENT_PART_COUNT; ++$index) {
            $columns[] = 'segment_part_'.$index;
        }

        foreach ($columns as $column) {
            if ($this->getColumnName($column) != '') {
                $query->select($this->getColumnNameWithAlias($column), self::ALIAS.'_'.$column);
            }
        }

        // sicherstellen, dass der Datensatz auch Werte in den zu bildenen Spalten für die Url hat
        // $query->where($this->getColumnNameWithAlias('segment_part_1'), '', '!=');
        // $query->whereRaw($this->getColumnNameWithAlias('segment_part_1', true).' IS NOT NULL');
        $whereRawSegmentParts = [];
        for ($index = 1; $index <= self::SEGMENT_PART_COUNT; ++$index) {
            if ($this->getColumnName('segment_part_'.$index) != '') {
                $whereRawSegmentParts[] = $this->getColumnNameWithAlias('segment_part_'.$index).' != "" AND '.$this->getColumnNameWithAlias('segment_part_'.$index, true).' IS NOT NULL';
            }
        }
        $query->whereRaw('('.implode(' OR ', $whereRawSegmentParts).')');

        if ($this->hasRestrictions()) {
            $where = [];
            foreach ($this->getRestrictions() as $index => $values) {
                $restriction = new ProfileRestriction(
                    $index,
                    $this->getColumnNameWithAlias($values['column'] ?? 'restriction_'.$index),
                    $values['comparison_operator'],
                    $values['value'],
                    ($values['logical_operator'] ?? '')
                );

                if (!empty($where)) {
                    $where[] = $restriction->getWhereOperator();
                }
                $where[] = $restriction->getWhere();
            }
            $query->whereRaw(implode(' ', $where));
        }

        if ($this->hasRelations()) {
            foreach ($this->getRelations() as $relation) {
                $query = $relation->completeQuery(
                    $query,
                    $this->getColumnNameWithAlias('relation_'.$relation->getIndex()),
                    $this->getColumnNameWithAlias('clang_id'),
                    $this->getArticleClangId()
                );
            }
        }

        return \rex_extension::registerPoint(new \rex_extension_point('URL_PROFILE_QUERY', $query, [
            'profile' => $this
        ]));
    }

    /**
     * Loads the cache if not already loaded.
     *
     * @throws \rex_exception
     */
    private static function checkCache()
    {
        if (self::$cacheLoaded) {
            return;
        }

        $file = \rex_path::addonCache('url', 'profiles.cache');
        if (!file_exists($file)) {
            Cache::generateProfiles();
        }
        foreach (\rex_file::getCache($file) as $id => $data) {
            $profile = new self();

            foreach ($data as $key => $value) {
                $profile->$key = $value;
            }

            $profile->normalize();

            self::$profiles[$id] = $profile;
        }
        self::$cacheLoaded = true;
    }

    /**
     * Resets the intern cache of this class.
     */
    public static function reset()
    {
        self::$cacheLoaded = false;
        self::$profiles = [];
    }
}
