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

    protected $columnName;

    protected $comparisonOperator;
    protected $logicalOperator = 'AND';
    protected $value;

    public function __construct($index, $columnName, $comparisonOperator, $value, $logicalOperator)
    {
        $this->index = $index;
        $this->alias = Profile::RESTRICTION_PREFIX.$this->index;
        $this->prefix = $this->alias.'_';

        $this->columnName = $columnName;
        $this->comparisonOperator = $comparisonOperator;
        $this->value = trim($value);

        if ($logicalOperator && $logicalOperator != '') {
            $this->logicalOperator = $logicalOperator;
        }
    }

    public function isValid()
    {
        return $this->columnName != ''
            && $this->value != ''
            && isset(Database::getComparisonOperators()[$this->comparisonOperator])
            && isset(Database::getLogicalOperators()[$this->logicalOperator]);
    }

    public function getWhere()
    {
        $value = $this->value;
        switch ($this->comparisonOperator) {
            case 'FIND_IN_SET':
                break;
            case 'IN':
            case 'NOT IN':
                $values = explode(',', $value);
                foreach ($values as $key => $value) {
                    $value = str_replace(['"', "'"], ['', ''], $value);
                    if ('' === trim($value)) {
                        unset($values[$key]);
                    } else {
                        $values[$key] = \rex_sql::factory()->escape($value);
                    }
                }
                $value = ' ('.implode(',', $values).') ';
                break;

            case 'BETWEEN':
            case 'NOT BETWEEN':
                $values = explode(',', $value);
                if (count($values) == 2) {
                    $value = $values[0].' AND '.$values[1];
                }
                break;

            default:
                $value = \rex_sql::factory()->escape($value);
                break;
        }

        switch ($this->comparisonOperator) {
            case 'FIND_IN_SET':
                $where = sprintf('%s (%s, %s)', $this->comparisonOperator, $value, $this->columnName);
                break;

            default:
                $where = sprintf('%s %s %s', $this->columnName, $this->comparisonOperator, $value);
                break;
        }

        return $where;
    }

    public function getWhereOperator()
    {
        return $this->logicalOperator;
    }
}
