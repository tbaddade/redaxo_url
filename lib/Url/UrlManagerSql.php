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
    }

    /**
     * @param int $id
     */
    public function setClangId($id)
    {
        $this->sql->setValue('clang_id', $id);
    }

    /**
     * @param int $id
     */
    public function setDataId($id)
    {
        $this->sql->setValue('data_id', $id);
    }

    /**
     * @param int $id
     */
    public function setProfileId($id)
    {
        $this->sql->setValue('profile_id', $id);
    }

    /**
     * @param array $value
     */
    public function setSeo(array $value)
    {
        $this->sql->setValue('seo', json_encode($value));
    }

    /**
     * @param bool $value
     */
    public function setSitemap($value)
    {
        $value = ($value === true) ? $value : false;
        $this->sql->setValue('sitemap', $value);
    }

    /**
     * @param bool $value
     */
    public function setStructure($value)
    {
        $this->sql->setValue('is_structure', $value);
    }

    /**
     * @param string $url
     */
    public function setUrl($url)
    {
        $this->sql->setValue('url', $url);
    }

    /**
     * @param bool $value
     */
    public function setUserPath($value)
    {
        $this->sql->setValue('is_user_path', $value);
    }

    /**
     * @param string $value
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
        } catch (\rex_sql_exception $e) {
            $success = false;
        }
        return $success;
    }

    public static function deleteAll()
    {
        $sql = self::factory();
        $sql->sql->setQuery('TRUNCATE TABLE '.\rex::getTable(self::TABLE_NAME));
    }

    /**
     * @param Profile $profile
     */
    public static function deleteByProfile(Profile $profile)
    {
        $sql = self::factory();
        $sql->sql->setWhere('profile_id = ?', [$profile->getId()]);
        $sql->sql->delete();
    }

    /**
     * @param Profile $profile
     * @param int     $datasetId
     */
    public static function deleteByProfileWithDatasetId(Profile $profile, $datasetId)
    {
        $sql = self::factory();
        $sql->sql->setWhere('profile_id = ? AND data_id = ?', [$profile->getId(), $datasetId]);
        $sql->sql->delete();
    }

    /**
     * @param int $profileId
     *
     * @return array
     */
    public static function getByProfileId($profileId)
    {
        $sql = self::factory();
        return $sql->sql->getArray('SELECT * FROM '.\rex::getTable(self::TABLE_NAME).' WHERE `profile_id` = ?', [$profileId]);
    }

    /**
     * @param Profile $profile
     * @param int     $datasetId
     * @param int     $clangId
     *
     * @return array
     */
    public static function getUrlsForDataset(Profile $profile, $datasetId, $clangId)
    {
        $sql = self::factory();
        return $sql->sql->getArray('SELECT * FROM '.\rex::getTable(self::TABLE_NAME).' WHERE `profile_id` = ? AND `data_id` = ? AND `clang_id` = ?', [$profile->getId(), $datasetId, $clangId]);
    }

    /**
     * @param int   $datasetId
     * @param int   $articleId
     * @param array $clangIds
     *
     * @return array
     */
    public static function getHreflangUrlsForDataset($datasetId, $articleId, $clangIds)
    {
        $where = implode(' OR ',
            array_map(function() {
                return '`clang_id` = ?';
            }, $clangIds)
        );
        $params = array_merge([$datasetId, $articleId, 0, 0], $clangIds);

        $sql = self::factory();
        return $sql->sql->getArray('SELECT * FROM '.\rex::getTable(self::TABLE_NAME).' WHERE `data_id` = ? AND `article_id` = ? AND is_user_path = ? AND is_structure = ? AND ('.$where.')', $params);
    }

    /**
     * @param Profile $profile
     * @param int     $datasetId
     * @param int     $clangId
     *
     * @return array
     */
    public static function getOriginalUrlForDataset(Profile $profile, $datasetId, $clangId)
    {
        $sql = self::factory();
        return $sql->sql->getArray('SELECT * FROM '.\rex::getTable(self::TABLE_NAME).' WHERE `profile_id` = ? AND `data_id` = ? AND `clang_id` = ? AND is_user_path = ? AND is_structure = ?', [$profile->getId(), $datasetId, $clangId, 0, 0]);
    }

    /**
     * @return array
     */
    public static function getByUrl()
    {
        $currentUrl = Url::getCurrent();
        $currentUrl->withScheme('');
        $currentUrl->withQuery('');
        $urlAsString = $currentUrl->__toString();

        $sql = self::factory();
        return $sql->sql->getArray('SELECT * FROM '.\rex::getTable(self::TABLE_NAME).' WHERE `url` = ?', [$urlAsString]);
    }
}
