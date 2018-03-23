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

class ProfileRestriction
{
    protected $index;

    protected $alias;
    protected $prefix;

    protected $sourceColumnName;

    protected $comparisonOperator;
    protected $logicalOperator = 'AND';
    protected $value;

    public function __construct($index, $columnName, $comparisonOperator, $value, $operator)
    {
        $this->index = $index;
        $this->alias = Profile::RESTRICTION_PREFIX . $this->index;
        $this->prefix = $this->alias . '_';

        $this->sourceColumnName = $columnName;
        $this->comparisonOperator = $comparisonOperator;
        $this->value = $value;

        if ($operator && $operator != '') {
            $this->logicalOperator = $operator;
        }
    }

    public function isValid()
    {
        return $this->sourceColumnName != ''
            && $this->value != ''
            && isset(Database::getComparisonOperators()[$this->comparisonOperator])
            && isset(Database::getLogicalOperators()[$this->logicalOperator]);
    }

    public function getWhere()
    {
        $column = $this->sourceColumnName;
        $operator = $this->comparisonOperator;
        $value = trim($this->value);
        switch ($operator) {
            case 'FIND_IN_SET':
                break;
            case 'IN':
            case 'NOT IN':
                $values = explode(',', $value);
                foreach ($values as $key => $value) {
                    if (!(int) $value > 0) {
                        unset($values[$key]);
                    }
                }
                $value = ' (' . implode(',', $values) . ') ';
                break;

            case 'BETWEEN':
            case 'NOT BETWEEN':
                $values = explode(',', $value);
                if (count($values) == 2) {
                    $value = $values[0] . ' AND ' . $values[1];
                }
                break;

            default:
                $value = \rex_sql::factory()->escape($value);
                break;
        }

        switch ($operator) {
            case 'FIND_IN_SET':
                $where = sprintf('%s (%s, %s.%s)', $operator, $value, Profile::ALIAS, $column);
                break;

            default:
                $where = sprintf('%s.%s %s %s', Profile::ALIAS, $column, $operator, $value);
                break;
        }

        return $where;
    }

    public function getWhereOperator()
    {
        return $this->logicalOperator;
    }
}
