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

abstract class Rewriter
{

    /**
     * @return string
     */
    abstract function extensionPointArticleIdNotFound();

    /**
     * @return string
     */
    abstract function extensionPointAddSitemap();

    /**
     * @return string
     */
    abstract function callFullUrl();

    /**
     * @return string
     */
    abstract function getSuffix();

    /**
     * @return string
     */
    abstract function normalize($string);


}
