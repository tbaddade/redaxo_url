<?php
\rex_sql_table::get(
    \rex::getTable('url_generator_url'))
    ->ensureColumn(new \rex_sql_column('url_hash', 'VARCHAR(191)'))
    ->ensure();
$sql = \rex_sql::factory();
$sql->setQuery("UPDATE ". \rex::getTablePrefix() ."url_generator_url SET url_hash = SHA1(url) WHERE url_hash = '';");

$this->includeFile(__DIR__.'/install.php');
