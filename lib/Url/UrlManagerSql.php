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

    private \rex_sql $sql;
    private array $where = [];

    private function __construct()
    {
        $this->sql = \rex_sql::factory();
        $this->sql->setTable(\rex::getTable(self::TABLE_NAME));
    }

    public static function factory(): self
    {
        return new self();
    }

    /**
     * @param int $id
     */
    public function setArticleId(int $id): void
    {
        $this->sql->setValue('article_id', $id);
        $this->where['article_id'] = $id;
    }

    /**
     * @param int $id
     */
    public function setClangId(int $id): void
    {
        $this->sql->setValue('clang_id', $id);
        $this->where['clang_id'] = $id;
    }

    /**
     * @param int $id
     */
    public function setDataId(int $id): void
    {
        $this->sql->setValue('data_id', $id);
        $this->where['data_id'] = $id;
    }

    /**
     * @param int $id
     */
    public function setProfileId(int $id): void
    {
        $this->sql->setValue('profile_id', $id);
        $this->where['profile_id'] = $id;
    }

    /**
     * @param array $value
     */
    public function setSeo(array $value): void
    {
        $value = json_encode($value);
        $this->sql->setValue('seo', $value);
        $this->where['seo'] = $value;
    }

    /**
     * @param bool $value
     */
    public function setSitemap(bool $value): void
    {
        $value = ($value === true) ? $value : false;
        $this->sql->setValue('sitemap', $value);
        $this->where['sitemap'] = $value;
    }

    /**
     * @param bool $value
     */
    public function setStructure(bool $value): void
    {
        $this->sql->setValue('is_structure', $value);
        $this->where['is_structure'] = ($value ? '1' : '0');
        ;
    }

    /**
     * @param string $url
     */
    public function setUrl(string $url): void
    {
        $this->sql->setValue('url', $url);
        $this->sql->setValue('url_hash', sha1($url));
        $this->where['url'] = $url;
    }

    /**
     * @param bool $value
     */
    public function setUserPath(bool $value): void
    {
        $this->sql->setValue('is_user_path', $value);
        $this->where['is_user_path'] = ($value ? '1' : '0');
    }

    /**
     * @param string|null $value
     *
     * @throws \Exception
     */
    public function setLastmod(?string $value = null): void
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
    public function fetch(): array
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
    public function save(): bool
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
    public static function deleteAll(): void
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
    public static function deleteByProfileId(int $profileId): void
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
    public static function deleteByProfileIdAndDatasetId(int $profileId, int $datasetId): void
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
    public static function getByProfileId(int $profileId): array
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
    public static function getHreflang(UrlManager $manager, array $clangIds): array
    {
        $where = implode(
            ' OR ',
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
    public static function getOrigin(Profile $profile, int $datasetId, int $clangId): array
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
    public static function getOriginAndExpanded(Profile $profile, int $datasetId, int $clangId): array
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
    public static function getByUrl(Url $url): array
    {
        $this_url = clone $url;
        $this_url->withScheme('');
        $this_url->withQuery('');
        $urlAsString = $this_url->toString();

        $sql = self::factory();
        return $sql->sql->getArray('SELECT * FROM '.\rex::getTable(self::TABLE_NAME).' WHERE `url` = ?', [$urlAsString]);
    }

    public static function triggerTableUpdated(): void
    {
        \rex_extension::registerPoint(new \rex_extension_point('URL_TABLE_UPDATED'));
    }
}
