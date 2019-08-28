<?php
$this->includeFile(__DIR__.'/install.php');

$sql = \rex_sql::factory();
$sql->setQuery('UPDATE '.\rex::getTable('url_generator_url').' SET url_hash = SHA1(url) WHERE url_hash = ""');

// Index erst erstellen, wenn Werte in der Spalte vorhanden sind.
\rex_sql_table::get(
    \rex::getTable('url_generator_profile'))
    ->ensureIndex(new \rex_sql_index('url_hash', ['url_hash'], \rex_sql_index::UNIQUE));
