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
use Url\Url;
use Url\UrlManagerSql;

$id = rex_request('id', 'int');
$func = rex_request('func', 'string');

$a = [];

if ($func == '') {
    $orderBy = rex_request('sort', 'string', '') == '' ? 'ORDER BY `p`.`namespace`, `u`.`article_id`, `u`.`data_id`, `u`.`clang_id`' : '';
    $query = '  SELECT      `u`.`id`,
                            `u`.`profile_id`,
                            `p`.`table_name`,
                            `p`.`namespace`,
                            `u`.`article_id`,
                            `u`.`data_id`,
                            `u`.`clang_id`,
                            `u`.`url`
                FROM        '.\rex::getTable(UrlManagerSql::TABLE_NAME).' AS u
                    LEFT JOIN '.\rex::getTable(Profile::TABLE_NAME).' AS p
                        ON  `u`.`profile_id` = `p`.`id`
                '.$orderBy;

    $list = rex_list::factory($query);
    $list->addTableAttribute('class', 'table-striped table-hover');
    $list->setColumnSortable('id');
    $list->setColumnSortable('profile_id');
    $list->setColumnSortable('namespace');
    $list->setColumnSortable('table_name');
    $list->setColumnSortable('article_id');
    $list->setColumnSortable('clang_id');
    $list->setColumnSortable('data_id');
    $list->setColumnSortable('url');

    $tdIcon = '<i class="rex-icon fa fa-anchor"></i>';
    $thIcon = '<a href="'.$list->getUrl(['func' => 'add']).'"'.rex::getAccesskey($this->i18n('add'), 'add').'><i class="rex-icon rex-icon-add-article"></i></a>';
    $list->addColumn($thIcon, $tdIcon, 0, ['<th class="rex-table-icon">###VALUE###</th>', '<td class="rex-table-icon">###VALUE###</td>']);
    $list->setColumnParams($thIcon, ['func' => 'edit', 'id' => '###id###']);

    $list->setColumnLabel('id', rex_i18n::msg('id'));
    $list->setColumnLayout('id', ['<th class="rex-table-id">###VALUE###</th>', '<td class="rex-table-id" data-title="'.rex_i18n::msg('id').'">###VALUE###</td>']);

    $list->setColumnLabel('namespace', $this->i18n('url_generator_namespace'));
    $list->setColumnLabel('table_name', $this->i18n('url_generator_table'));
    $list->setColumnLabel('data_id', $this->i18n('url_data_id'));
    $list->setColumnLabel('profile_id', $this->i18n('url_generator_profile'));
    $list->setColumnLabel('article_id', $this->i18n('url_generator_article'));
    $list->setColumnLabel('clang_id', $this->i18n('url_language'));
    $list->setColumnLabel('url', $this->i18n('url'));

    $list->setColumnFormat('url', 'custom', function ($params) {
        $value = $params['list']->getValue('url');
        $url = Url::get($value);
        $url->withSolvedScheme();

        return sprintf('<a href="%s" target="_blank">%s</a>', urldecode($url), urldecode($value));
    });

    $content = $list->get();

    $fragment = new rex_fragment();
    $fragment->setVar('title', $this->i18n('url_generator_profiles'));
    $fragment->setVar('content', $content, false);
    $content = $fragment->parse('core/page/section.php');

    echo $content;
}

// echo rex_getUrl('', '', ['movie-id' => 1, 'test' => 'jey', 'test2' => 'jey']);
// echo '<br />';
// echo rex_getUrl('', '', ['news-id' => 3]);
// echo '<br />';
// echo rex_getUrl('', '2', ['news-id' => 3]);

//dump(Profile::getAll());
