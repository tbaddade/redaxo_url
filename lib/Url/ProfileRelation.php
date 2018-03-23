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

class ProfileRelation
{
    protected $index;

    protected $alias;
    protected $prefix;

    /** @var $profile Profile */
    protected $profile;

    protected $sourceColumnName;
    protected $dbId;
    protected $tableName;
    protected $tableParameters;
    protected $segmentPosition;

    protected $clangIdColumnName;
    protected $idColumnName;
    protected $segmentParts = [];

    public function __construct($index, $columnName, $segmentPosition, $mergedTableName, $tableParams)
    {
        $this->index = $index;
        $this->alias = Profile::RELATION_PREFIX . $this->index;
        $this->prefix = $this->alias . '_';

        $this->segmentPosition = $segmentPosition;
        $this->sourceColumnName = $columnName;

        $group = Database::split($mergedTableName);
        $this->dbId = $group[1];
        $this->tableName = $group[2];

        $this->tableParameters = $tableParams;

        $this->idColumnName = $this->getValue('column_id');
        $this->clangIdColumnName = $this->getValue('column_clang_id');

        for ($index = 1; $index <= Profile::SEGMENT_PART_COUNT; $index++) {
            $columnName = $this->getValue('column_segment_part_' . $index);
            if ($columnName && $columnName != '') {
                $separator = $this->getValue('column_segment_part_' . $index . '_separator');
                $separator = ($separator && $separator != '') ? $separator : null;
                $this->segmentParts[$index] = ['column_name' => $columnName, 'separator' => $separator];
            }
        }
    }

    public function isValid()
    {
        return $this->getTableName() != '' && $this->getSourceColumnName() != '' && $this->getIdColumnName() != '';
    }

    public function getAlias()
    {
        return $this->alias;
    }

    public function getClangIdColumnName()
    {
        return $this->clangIdColumnName;
    }

    public function getDBId()
    {
        return $this->dbId;
    }

    public function getIdColumnName()
    {
        return $this->idColumnName;
    }

    public function getSegmentParts()
    {
        return $this->segmentParts;
    }

    public function getSegmentPosition()
    {
        return $this->segmentPosition;
    }

    public function getSourceColumnName()
    {
        return $this->sourceColumnName;
    }

    public function getTableName()
    {
        return $this->tableName;
    }

    public function getTableParams()
    {
        return $this->tableParameters;
    }

    public function getTableParam($key)
    {
        $params = $this->getTableParams();
        return isset($params[$key]) ? $params[$key] : null;
    }

    public function getValue($key)
    {
        return $this->getTableParam($key);
    }


    public function completeQuery(\rex_yform_manager_query $query, $articleClangId, $clangIdColumnName)
    {
        $joinTyp = 'LEFT';
        $joinTable = $this->getTableName();
        $joinAlias = $this->alias;
        $joinCondition = Profile::ALIAS . '.' . $this->getSourceColumnName() . ' = ' . $joinAlias . '.' . $this->getIdColumnName();

        if (null === $articleClangId && $clangIdColumnName != '' && $this->getClangIdColumnName() != '') {
            $joinCondition .= ' AND ' . Profile::ALIAS . '.' . $clangIdColumnName . ' = ' .  $joinAlias . '.' . $this->getClangIdColumnName();
        }

        $query->joinRaw($joinTyp, $joinTable, $joinAlias, $joinCondition);
        $query->select($joinAlias . '.' . $this->getIdColumnName(), $this->prefix . 'id');

        if (null === $articleClangId && $this->getClangIdColumnName() != '') {
            $query->select($joinAlias . '.' . $this->getClangIdColumnName(), $this->prefix . 'clang_id');
        }

        if (count($this->getSegmentParts())) {
            foreach ($this->getSegmentParts() as $index => $columnName) {
                $query->select($joinAlias . '.' . $columnName['column_name'], $this->prefix . 'segment_part_' . $index);
            }
        }

        return $query;
    }

}
