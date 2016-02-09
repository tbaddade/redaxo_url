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

use \Url\Rewriter\Rewriter;
use \Url\Rewriter\YRewrite;

class Url
{
    /**
     * @var \Url\Rewriter\Rewriter
     */
    private static $rewriter;


    public static function getRewriter()
    {
        return self::$rewriter;
    }

    public static function setRewriter(Rewriter $rewriter)
    {
        self::$rewriter = $rewriter;
    }

    public static function boot()
    {
        if (null === self::$rewriter) {
            if (\rex_addon::get('yrewrite')->isAvailable()) {
                self::setRewriter(new YRewrite());
            } else {
                throw new \rex_sql_exception('Please install a rewriter addon and call Url::setRewriter(Rewriter $rewriter).');
            }
        }
    }
}
