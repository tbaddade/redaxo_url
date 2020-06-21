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

class UrlManagerSql
{
    const TABLE_NAME = 'url_generator_url';

    private $sql;
    private $where = [];

    private function __construct()
    {
        $this->sql = \rex_sql::factory();
        $this->sql->setTable(\rex::getTable(self::TABLE_NAME));
    }

    public static function factory()
    {
        return new self();
    }

    /**
     * @param int $id
     */
    public function setArticleId($id)
    {
        $this->sql->setValue('article_id', $id);
        $this->where['article_id'] = $id;
    }

    /**
     * @param int $id
     */
    public function setClangId($id)
    {
        $this->sql->setValue('clang_id', $id);
        $this->where['clang_id'] = $id;
    }

    /**
     * @param int $id
     */
    public function setDataId($id)
    {
        $this->sql->setValue('data_id', $id);
        $this->where['data_id'] = $id;
    }

    /**
     * @param int $id
     */
    public function setProfileId($id)
    {
        $this->sql->setValue('profile_id', $id);
        $this->where['profile_id'] = $id;
    }

    /**
     * @param array $value
     */
    public function setSeo(array $value)
    {
        $value = json_encode($value);
        $this->sql->setValue('seo', $value);
        $this->where['seo'] = $value;
    }

    /**
     * @param bool $value
     */
    public function setSitemap($value)
    {
        $value = ($value === true) ? $value : false;
        $this->sql->setValue('sitemap', $value);
        $this->where['sitemap'] = $value;
    }

    /**
     * @param bool $value
     */
    public function setStructure($value)
    {
        $this->sql->setValue('is_structure', $value);
        $this->where['is_structure'] = ($value ? '1' : '0');;
    }

    /**
     * @param string $url
     */
    public function setUrl($url)
    {
        $this->sql->setValue('url', $url);
        $this->sql->setValue('url_hash', sha1($url));
        $this->where['url'] = $url;
    }

    /**
     * @param bool $value
     */
    public function setUserPath($value)
    {
        $this->sql->setValue('is_user_path', $value);
        $this->where['is_user_path'] = ($value ? '1' : '0');
    }

    /**
     * @param string $value
     *
     * @throws \Exception
     */
    public function setLastmod($value = null)
    {
        if (!$value) {
            $value = time();
        }

        if (strpos($value, '-')) {
            // mysql date
            $datetime = new \DateTime($value);
            $value = $datetime->getTimestamp();
        }
        $this->sql->setValue('lastmod', date(DATE_W3C, $value));
        $this->where['lastmod'] = date(DATE_W3C, $value);
    }

    /**
     * @throws \rex_sql_exception
     *
     * @return array
     */
    public function fetch()
    {
        $query = '';
        foreach ($this->where as $fieldName => $value) {
            if ($query != '') {
                $query .= ' AND ';
            }
            $query .= $this->sql->escapeIdentifier($fieldName) . ' = :' . $fieldName;
        }

        $sql = self::factory();
        return $sql->sql->getArray('SELECT * FROM '.\rex::getTable(self::TABLE_NAME).' WHERE '.$query, $this->where);
    }

    /**
     * @return bool
     */
    public function save()
    {
        try {
            $this->sql->addGlobalCreateFields();
            $this->sql->addGlobalUpdateFields();
            $this->sql->insert();
            $success = true;

            self::triggerTableUpdated();
        } catch (\rex_sql_exception $e) {
            $success = false;
        }
        return $success;
    }

    /**
     * @throws \rex_sql_exception
     */
    public static function deleteAll()
    {
        $sql = self::factory();
        $sql->sql->setQuery('TRUNCATE TABLE '.\rex::getTable(self::TABLE_NAME));

        self::triggerTableUpdated();
    }

    /**
     * @param int $profileId
     *
     * @throws \rex_sql_exception
     */
    public static function deleteByProfileId($profileId)
    {
        $sql = self::factory();
        $sql->sql->setWhere('profile_id = ?', [$profileId]);
        $sql->sql->delete();

        self::triggerTableUpdated();
    }

    /**
     * @param int $profileId
     * @param int $datasetId
     *
     * @throws \rex_sql_exception
     */
    public static function deleteByProfileIdAndDatasetId($profileId, $datasetId)
    {
        $sql = self::factory();
        $sql->sql->setWhere('profile_id = ? AND data_id = ?', [$profileId, $datasetId]);
        $sql->sql->delete();

        self::triggerTableUpdated();
    }

    /**
     * @param int $profileId
     *
     * @throws \rex_sql_exception
     *
     * @return array
     */
    public static function getByProfileId($profileId)
    {
        $sql = self::factory();
        return $sql->sql->getArray('SELECT * FROM '.\rex::getTable(self::TABLE_NAME).' WHERE `profile_id` = ?', [$profileId]);
    }

    /**
     * @param UrlManager $manager
     * @param array      $clangIds
     *
     * @throws \rex_sql_exception
     *
     * @return array
     */
    public static function getHreflang(UrlManager $manager, $clangIds)
    {
        $where = implode(' OR ',
            array_map(function () {
                return '`clang_id` = ?';
            }, $clangIds)
        );
        $params = array_merge([
            $manager->getDatasetId(),
            $manager->getArticleId(),
            $manager->isUserPath() ? 1 : 0,
            $manager->isStructure() ? 1 : 0,
        ], $clangIds);

        $sql = self::factory();
        return $sql->sql->getArray('SELECT * FROM '.\rex::getTable(self::TABLE_NAME).' WHERE `data_id` = ? AND `article_id` = ? AND is_user_path = ? AND is_structure = ? AND ('.$where.')', $params);
    }

    /**
     * @param Profile $profile
     * @param int     $datasetId
     * @param int     $clangId
     *
     * @throws \rex_sql_exception
     *
     * @return array
     */
    public static function getOrigin(Profile $profile, $datasetId, $clangId)
    {
        $sql = self::factory();
        return $sql->sql->getArray('SELECT * FROM '.\rex::getTable(self::TABLE_NAME).' WHERE `profile_id` = ? AND `data_id` = ? AND `clang_id` = ? AND is_user_path = ? AND is_structure = ?', [$profile->getId(), $datasetId, $clangId, 0, 0]);
    }

    /**
     * @param Profile $profile
     * @param int     $datasetId
     * @param int     $clangId
     *
     * @throws \rex_sql_exception
     *
     * @return array
     */
    public static function getOriginAndExpanded(Profile $profile, $datasetId, $clangId)
    {
        $sql = self::factory();
        return $sql->sql->getArray('SELECT * FROM '.\rex::getTable(self::TABLE_NAME).' WHERE `profile_id` = ? AND `data_id` = ? AND `clang_id` = ?', [$profile->getId(), $datasetId, $clangId]);
    }

    /**
     * @param Url $url
     *
     * @throws \rex_sql_exception
     *
     * @return array
     */
    public static function getByUrl(Url $url)
    {
        $this_url = clone $url;
        $this_url->withScheme('');
        $this_url->withQuery('');
        $urlAsString = $this_url->toString();

        $sql = self::factory();
        return $sql->sql->getArray('SELECT * FROM '.\rex::getTable(self::TABLE_NAME).' WHERE `url` = ?', [$urlAsString]);
    }

    public static function triggerTableUpdated()
    {
        \rex_extension::registerPoint(new \rex_extension_point('URL_TABLE_UPDATED'));
    }
}
