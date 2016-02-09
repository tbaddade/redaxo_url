<?php

/**
 * This file is part of the Url package.
 *
 * @author (c) Thomas Blum <thomas@addoff.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Url\Rewriter;

class Yrewrite extends Rewriter
{
    /**
     * @return string
     */
    public function extensionPointArticleIdNotFound()
    {
        return 'YREWRITE_PREPARE';
    }

    /**
     * @return string
     */
    public function extensionPointAddSitemap()
    {

    }

    /**
     * @return string
     */
    public function callFullUrl()
    {

    }

    /**
     * @return string
     */
    public function getSuffix()
    {
        return '.html';
    }

    /**
     * @param string $string
     * @param int    $clang
     *
     * @return string
     */
    public function normalize($string)
    {
        $string = str_replace(
            ['Ä', 'Ö', 'Ü', 'ä', 'ö', 'ü', 'ß', '/'],
            ['Ae', 'Oe', 'Ue', 'ae', 'oe', 'ue', 'ss', '-'],
            $string
        );
        $string = iconv('UTF-8', 'ASCII//TRANSLIT', $string);
        $string = preg_replace('/[^\w -]+/', '', $string);
        $string = strtolower(trim($string));
        $string = urlencode($string);
        $string = preg_replace('/[+-]+/', '-', $string);
        return $string;
    }
}
