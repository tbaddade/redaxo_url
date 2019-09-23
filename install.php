<?php

/**
 * This file is part of the Url package.
 *
 * @author (c) Thomas Blum <thomas@addoff.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

\rex_sql_table::get(
    \rex::getTable('url_generator_profile'))
    ->ensurePrimaryIdColumn()
    ->ensureColumn(new \rex_sql_column('namespace', 'VARCHAR(255)'))
    ->ensureColumn(new \rex_sql_column('article_id', 'INT(11)'))
    ->ensureColumn(new \rex_sql_column('clang_id', 'INT(11)', false, 1))
    ->ensureColumn(new \rex_sql_column('ep_pre_save_called', 'TINYINT(1)', false, 0))
    ->ensureColumn(new \rex_sql_column('table_name', 'VARCHAR(255)'))
    ->ensureColumn(new \rex_sql_column('table_parameters', 'TEXT', true))
    ->ensureColumn(new \rex_sql_column('relation_1_table_name', 'VARCHAR(255)'))
    ->ensureColumn(new \rex_sql_column('relation_1_table_parameters', 'TEXT', true))
    ->ensureColumn(new \rex_sql_column('relation_2_table_name', 'VARCHAR(255)'))
    ->ensureColumn(new \rex_sql_column('relation_2_table_parameters', 'TEXT', true))
    ->ensureColumn(new \rex_sql_column('relation_3_table_name', 'VARCHAR(255)'))
    ->ensureColumn(new \rex_sql_column('relation_3_table_parameters', 'TEXT', true))
    ->ensureColumn(new \rex_sql_column('createdate', 'DATETIME'))
    ->ensureColumn(new \rex_sql_column('createuser', 'VARCHAR(255)'))
    ->ensureColumn(new \rex_sql_column('updatedate', 'DATETIME'))
    ->ensureColumn(new \rex_sql_column('updateuser', 'VARCHAR(255)'))
    ->ensureIndex(new \rex_sql_index('namespace', ['namespace', 'article_id', 'clang_id'], \rex_sql_index::UNIQUE))
    ->ensure();

\rex_sql_table::get(
    \rex::getTable('url_generator_url'))
    ->ensurePrimaryIdColumn()
    ->ensureColumn(new \rex_sql_column('profile_id', 'INT(11)'))
    ->ensureColumn(new \rex_sql_column('article_id', 'INT(11)'))
    ->ensureColumn(new \rex_sql_column('clang_id', 'INT(11)'))
    ->ensureColumn(new \rex_sql_column('data_id', 'INT(11)'))
    ->ensureColumn(new \rex_sql_column('is_user_path', 'TINYINT(1)', false, '0'))
    ->ensureColumn(new \rex_sql_column('is_structure', 'TINYINT(1)', false, '0'))
    ->ensureColumn(new \rex_sql_column('url', 'TEXT'))
    ->ensureColumn(new \rex_sql_column('url_hash', 'VARCHAR(191)'))
    ->ensureColumn(new \rex_sql_column('sitemap', 'TINYINT(1)', false, '0'))
    ->ensureColumn(new \rex_sql_column('lastmod', 'VARCHAR(255)'))
    ->ensureColumn(new \rex_sql_column('seo', 'TEXT'))
    ->ensureColumn(new \rex_sql_column('createdate', 'DATETIME'))
    ->ensureColumn(new \rex_sql_column('createuser', 'VARCHAR(255)'))
    ->ensureColumn(new \rex_sql_column('updatedate', 'DATETIME'))
    ->ensureColumn(new \rex_sql_column('updateuser', 'VARCHAR(255)'))
    ->removeIndex('url')
    ->ensure();

$sql = \rex_sql::factory();
$sql->setQuery('UPDATE '.\rex::getTable('url_generator_url').' SET url_hash = SHA1(url) WHERE url_hash = ""');

\rex_sql_table::get(
    \rex::getTable('url_generator_url'))
    ->ensureIndex(new \rex_sql_index('url_hash', ['url_hash'], \rex_sql_index::UNIQUE))
    ->ensure();
