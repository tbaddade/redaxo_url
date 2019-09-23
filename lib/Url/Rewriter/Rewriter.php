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
    abstract public function articleIdNotFound();

    /**
     * @return string
     */
    abstract public function getSitemapExtensionPoint();

    /**
     * @return array
     */
    abstract public function getSitemapFrequency();

    /**
     * @return array
     */
    abstract public function getSitemapPriority();

    /**
     * @return object
     */
    abstract public function getSeoInstance();

    /**
     * @return string
     */
    abstract public function getSeoTags();

    /**
     * @param int $article_id
     * @param int $clang_id
     *
     * @return string
     */
    abstract public function getFullUrl($article_id, $clang_id);

    /**
     * @param string $path
     *
     * @return string
     */
    abstract public function getFullPath($path);

    /**
     * @return string
     */
    abstract public function getSuffix();

    /**
     * @param string $domain
     *
     * @return string
     */
    abstract public function getSchemeByDomain($domain);

    /**
     * @return bool
     */
    abstract public function isHttps();

    /**
     * @param string  $string
     * @param integer $clangId
     *
     * @return string
     */
    abstract public function normalize($string, $clangId);
}
