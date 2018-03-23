<?php

/**
 * This file is part of the Url package.
 *
 * @author (c) Thomas Blum <thomas@addoff.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Url\Profile;
use Url\UrlManagerSql;

$id = rex_request('id', 'int');
$func = rex_request('func', 'string');

$a = [];

if ($func == '') {
    $query = '  SELECT      `p`.`namespace`,
                            `p`.`table_name`,
                            `u`.`profile_id`,
                            `u`.`article_id`,
                            `u`.`clang_id`,
                            `u`.`data_id`,
                            `u`.`url`
                FROM        ' . \rex::getTable(UrlManagerSql::TABLE_NAME) . ' AS u
                    LEFT JOIN ' . \rex::getTable(Profile::TABLE_NAME) . ' AS p
                        ON  `u`.`profile_id` = `p`.`id`
                        ';

    $list = rex_list::factory($query);
    $list->addTableAttribute('class', 'table-striped');

    $tdIcon = '<i class="rex-icon rex-icon-anchor"></i>';
    $thIcon = '<a href="' . $list->getUrl(['func' => 'add']) . '"' . rex::getAccesskey($this->i18n('add'), 'add') . '><i class="rex-icon rex-icon-add-article"></i></a>';
    $list->addColumn($thIcon, $tdIcon, 0, ['<th class="rex-table-icon">###VALUE###</th>', '<td class="rex-table-icon">###VALUE###</td>']);
    $list->setColumnParams($thIcon, ['func' => 'edit', 'id' => '###id###']);

    $list->removeColumn('id');

    $list->setColumnLabel('namespace', $this->i18n('url_generator_namespace'));
    $list->setColumnLabel('table_name', $this->i18n('url_generator_table'));
    $list->setColumnLabel('article_id', $this->i18n('url_generator_article'));
    $list->setColumnLabel('clang_id', $this->i18n('url_language'));
    $list->setColumnLabel('url', $this->i18n('url'));

    $content = $list->get();

    $fragment = new rex_fragment();
    $fragment->setVar('title', $this->i18n('url_generator_profiles'));
    $fragment->setVar('content', $content, false);
    $content = $fragment->parse('core/page/section.php');

    echo $content;
}


echo rex_getUrl('', '', ['movie-id' => 1, 'test' => 'jey', 'test2' => 'jey']);
echo '<br />';
echo rex_getUrl('', '', ['news-id' => 3]);
echo '<br />';
echo rex_getUrl('', '2', ['news-id' => 3]);
