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

class ExtensionPointManager
{
    const MODE_UPDATE_URL_ALL = 1;

    const MODE_UPDATE_URL_DATASET = 2;

    const MODE_UPDATE_URL_COLLECTION = 3;

    /**
     * The rex_extension_point instance.
     *
     * @var \rex_extension_point
     */
    protected $extensionPoint;

    protected $mode;

    protected $dataEditMode;
    protected $dataPrimaryId;
    protected $dataPrimaryColumnName;
    protected $dataTableName;

    protected $structureArticleId;
    protected $structureClangId;

    /**
     * Create a new manager instance.
     *
     * @param \rex_extension_point $extensionPoint
     */
    public function __construct($extensionPoint)
    {
        $this->extensionPoint = $extensionPoint;
        $this->normalize();
        return $this;
    }

    public function __get($name)
    {
        if (method_exists($this, $name)) {
            return $this->$name();
        }
        if (property_exists($this, $name)) {
            return $this->$name;
        }
        return null;
    }

    public function getMode()
    {
        return $this->mode;
    }

    public function getDatasetPrimaryId()
    {
        return $this->dataPrimaryId;
    }

    public function getDatasetPrimaryColumnName()
    {
        return $this->dataPrimaryColumnName;
    }

    public function getDatasetTableName()
    {
        return $this->dataTableName;
    }

    public function getStructureArticleId()
    {
        return $this->structureArticleId;
    }

    public function getStructureClangId()
    {
        return $this->structureClangId;
    }

    protected function normalize()
    {
        switch ($this->extensionPoint->getName()) {
            case 'ART_ADDED':
            case 'ART_MOVED':
            case 'ART_STATUS':
            case 'ART_UPDATED':
            case 'CAT_ADDED':
            case 'CAT_MOVED':
            case 'CAT_STATUS':
            case 'CAT_UPDATED':
                $clangId = $this->extensionPoint->getParam('clang');
                if (!$this->extensionPoint->hasParam('clang') && $this->extensionPoint->hasParam('clang_id')) {
                    $clangId = $this->extensionPoint->getParam('clang_id');
                }
                $this->setMode(self::MODE_UPDATE_URL_COLLECTION);
                $this->setStructureArticleId($this->extensionPoint->getParam('id'));
                $this->setStructureClangId($clangId);
                break;

            case 'CACHE_DELETED':
                Cache::deleteProfiles();
                $this->setMode(self::MODE_UPDATE_URL_ALL);
                break;

            case 'CLANG_ADDED':
            case 'CLANG_STATUS':
            case 'CLANG_UPDATED':
            // case 'CAT_UPDATED':
                $this->setMode(self::MODE_UPDATE_URL_ALL);
                break;

            case 'REX_FORM_SAVED':
                /* @var $object \rex_form */
                $object = $this->extensionPoint->getParam('form');
                $tableName = $object->getTableName();

                if ($tableName == \rex::getTable(Profile::TABLE_NAME)) {
                    // Profil wurde angelegt/aktualisiert
                    // Nur Urls dieses Profiles bearbeiten
                    Profile::reset();
                    $this->setMode(self::MODE_UPDATE_URL_COLLECTION);
                    $this->setStructureArticleId($object->elementPostValue(\rex_i18n::msg('url_generator_article_legend'), 'article_id'));
                    $this->setStructureClangId($object->elementPostValue(\rex_i18n::msg('url_generator_article_legend'), 'clang_id', '0'));
                } else {
                    // Datensatz wurde aktualisiert
                    // Urls neu schreiben
                    $profiles = Profile::getByTableName($tableName);
                    if (empty($profiles)) {
                        break;
                    }
                    $primaryKey = \rex_sql_table::get($tableName)->getPrimaryKey()[0];
                    if (null === $primaryKey) {
                        break;
                    }

                    $primaryId = $object->isEditMode() ? $object->getSql()->getValue($primaryKey) : $object->getSql()->getLastId();
                    $this->setMode(self::MODE_UPDATE_URL_DATASET);
                    $this->setDatasetEditMode($object->isEditMode());
                    $this->setDatasetPrimaryId($primaryId);
                    $this->setDatasetPrimaryColumnName($primaryKey);
                    $this->setDatasetTableName($tableName);
                }

                break;

            case 'REX_YFORM_SAVED':
                // dump($this->extensionPoint->getParams());
                // Domain wurde angelegt/aktualisiert
                if ($this->extensionPoint->getParam('table') == 'rex_yrewrite_domain') {
                    $this->setMode(self::MODE_UPDATE_URL_ALL);
                }
                break;

            case 'YFORM_DATA_ADDED':
            case 'YFORM_DATA_DELETED':
            case 'YFORM_DATA_UPDATED':
                // dump($this->extensionPoint->getParams());
                /* @var $object \rex_yform_manager_dataset */
                $object = $this->extensionPoint->getParam('data');
                $tableName = $object->getTableName();

                $profiles = Profile::getByTableName($tableName);
                if (empty($profiles)) {
                    break;
                }
                $primaryKey = \rex_sql_table::get($tableName)->getPrimaryKey()[0];
                if (null === $primaryKey) {
                    break;
                }

                $this->setMode(self::MODE_UPDATE_URL_DATASET);
                $this->setDatasetEditMode(($this->extensionPoint->getParam('old_data') ? true : false));
                $this->setDatasetPrimaryId($object->getId());
                $this->setDatasetPrimaryColumnName($primaryKey);
                $this->setDatasetTableName($object->getTableName());
                break;
        }
    }

    protected function setMode($mode)
    {
        $this->mode = $mode;
    }

    protected function setDatasetEditMode($mode)
    {
        $this->dataEditMode = $mode;
    }

    protected function setDatasetPrimaryId($id)
    {
        $this->dataPrimaryId = $id;
    }

    protected function setDatasetPrimaryColumnName($key)
    {
        $this->dataPrimaryColumnName = $key;
    }

    protected function setDatasetTableName($tableName)
    {
        $this->dataTableName = $tableName;
    }

    protected function setStructureArticleId($id)
    {
        $this->structureArticleId = $id;
    }

    protected function setStructureClangId($clang_id)
    {
        if (count(\rex_clang::getAll()) == 1) {
            $clang_id = 1;
        }
        $this->structureClangId = $clang_id;
    }
}
