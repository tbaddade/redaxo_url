<?php

/**
 * This file is part of the Url package.
 *
 * @author (c) Thomas Blum <thomas@addoff.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


$sql = rex_sql::factory();
$sql->setQuery('CREATE TABLE IF NOT EXISTS `' . rex::getTable('url_generate') . '` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `article_id` INT(11) NOT NULL,
    `clang_id` INT(11) NOT NULL DEFAULT 1,
    `url` TEXT NOT NULL,
    `table` VARCHAR(255) NOT NULL,
    `table_parameters` TEXT NOT NULL,
    `relation_table` VARCHAR(255) NOT NULL,
    `relation_table_parameters` TEXT NOT NULL,
    `relation_insert` VARCHAR(255) NOT NULL,
    `createdate` INT(11) NOT NULL,
    `createuser` VARCHAR(255) NOT NULL,
    `updatedate` INT(11) NOT NULL,
    `updateuser` VARCHAR(255) NOT NULL,
    PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');
