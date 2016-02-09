<?php

/**
 * This file is part of the Url package.
 *
 * @author (c) Thomas Blum <thomas@addoff.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use \Url\Url;

rex_extension::register('PACKAGES_INCLUDED', function ($params) {
    Url::boot();
}, rex_extension::EARLY);
