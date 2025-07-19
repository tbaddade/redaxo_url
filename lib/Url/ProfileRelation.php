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
    private int|string $index;
    private int|string $position;
    private array $segment_part_separators;
    private array $table;

    public function __construct(int|string $index, array $values)
    {
        $this->index = $index;
        $this->position = $values['position'];
        $this->segment_part_separators = $values['segment_part_separators'];
        $this->table = $values['table'];
    }

    public function getDatabaseId(): int|string|null
    {
        return $this->table['dbid'];
    }

    public function getTableName(): string
    {
        return $this->table['name'];
    }

    public function getColumnName(string $column): string
    {
        return $this->table['column_names'][$column];
    }

    public function getColumnNameWithAlias(string $column): string
    {
        return $this->getAlias().'.'.$this->getColumnName($column);
    }

    public function getAlias(): string
    {
        return Profile::RELATION_PREFIX.$this->index;
    }

    public function getIndex(): int|string
    {
        return $this->index;
    }

    /**
     * Get segment part seperators as array
     * @return array
     */
    public function getSegmentPartSeparators(): array
    {
        return $this->segment_part_separators;
    }

    public function getSegmentPosition(): int|string|null
    {
        return $this->position;
    }

    public function completeQuery(\rex_yform_manager_query $query, string $sourceRelationColumnName, string $sourceClangColumnName, int|string|null $structureArticleClangId): \rex_yform_manager_query
    {
        $joinCondition = $sourceRelationColumnName.' = '.$this->getColumnNameWithAlias('id');

        if (null === $structureArticleClangId && $sourceClangColumnName != '' && $this->getColumnName('clang_id') != '') {
            $joinCondition .= ' AND '.$sourceClangColumnName.' = '.$this->getColumnNameWithAlias('clang_id');
        } elseif ($sourceClangColumnName != '' && $this->getColumnName('clang_id') != '') {
            $query->where($this->getColumnNameWithAlias('clang_id'), $structureArticleClangId);
        }

        $query->joinRaw('LEFT', $this->getTableName(), $this->getAlias(), $joinCondition);
        $query->select($this->getColumnNameWithAlias('id'), $this->getAlias().'_id');

        if (null === $structureArticleClangId && $this->getColumnName('clang_id') != '') {
            $query->select($this->getColumnNameWithAlias('clang_id'), $this->getAlias().'_clang_id');
        }

        $columns = [];
        for ($index = 1; $index <= Profile::SEGMENT_PART_COUNT; ++$index) {
            $columns[] = 'segment_part_'.$index;
        }

        foreach ($columns as $column) {
            if ($this->getColumnName($column) != '') {
                $query->select($this->getColumnNameWithAlias($column), $this->getAlias().'_'.$column);
            }
        }

        return $query;
    }
}
