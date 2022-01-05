<?php

/**
 * This file is part of the Url package.
 *
 * @author (c) Thomas Blum <thomas@addoff.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

\rex_sql_table::get(\rex::getTable('url_generator_profile'))
    ->drop();
\rex_sql_table::get(\rex::getTable('url_generator_url'))
    ->drop();
