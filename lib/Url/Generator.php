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

use \Url\Url;

class Generator
{

    public static function getRestrictionOperators()
    {
        return [ '='            => '=',
                 '>'            => '>',
                 '>='           => '>=',
                 '<'            => '<',
                 '<='           => '<=',
                 '!='           => '!=',
                 'LIKE'         => 'LIKE',
                 'NOT LIKE'     => 'NOT LIKE',
                 'IN (...)'     => 'IN (...)',
                 'NOT IN (...)' => 'NOT IN (...)',
                 'BETWEEN'      => 'BETWEEN',
                 'NOT BETWEEN'  => 'NOT BETWEEN',
                 'FIND_IN_SET'  => 'FIND_IN_SET',
                ];
    }

    public static function getDatabaseTableSeparator()
    {
        return '_xxx_';
    }

    public static function appendRewriterSuffix($url)
    {
        $rewriterSuffix = Url::getRewriter()->getSuffix();
        return $url . $rewriterSuffix;
    }

    public static function stripRewriterSuffix($url)
    {
        $rewriterSuffix = Url::getRewriter()->getSuffix();
        return substr($url, 0, (strlen($rewriterSuffix) * -1));
    }

    public static function buildUrl($url, $fields = [])
    {
        $url = self::stripRewriterSuffix($url);
        $url .= '/';
        $url .= implode('-', array_map(
            function ($field) {
                return Url::getRewriter()->normalize($field);
            }, $fields)
        );
        $url = self::appendRewriterSuffix($url);
        return $url;
    }
}
