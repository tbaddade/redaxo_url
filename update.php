<?php
$this->includeFile(__DIR__.'/install.php');

$sql = \rex_sql::factory();
$sql->setQuery('UPDATE '.\rex::getTable('url_generator_url').' SET url_hash = SHA1(url) WHERE url_hash = ""');

