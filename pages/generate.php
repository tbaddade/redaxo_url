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
use \Url\Generator;

$id = rex_request('id', 'int');
$func = rex_request('func', 'string');

$a = [];

if (!function_exists('url_generate_column_article')) {
    function url_generate_column_article($params)
    {
        $list = $params['list'];
        $return = '';

        $a = rex_article::get($list->getValue('article_id'), $list->getValue('clang_id'));
        if ($a instanceof rex_article) {
            $return = $a->getName();
            $return .= ' [';
            $return .= '<a href="' . rex_url::backendPage('/content/edit', ['category_id' => $a->getCategoryId(), 'article_id' => $a->getId(), 'clang' => $a->getClang(), 'mode' => 'edit']) . '">Backend</a>';
            $return .= ' | ';
            $return .= '<a href="' . rex_getUrl($list->getValue('article_id'), $list->getValue('clang_id')) . '">Frontend</a>';
            $return .= ']';

            $tree = $a->getParentTree();

            $levels = [];
            if (count(rex_clang::getAll()) >= 2 && in_array($list->getValue('clang_id'), rex_clang::getAllIds())) {
                $levels[] = rex_clang::get($list->getValue('clang_id'))->getName();
            }

            foreach ($tree as $object) {
                $levels[] = $object->getName();
            }
            $return .= '<div class="url-path"><small><b>Pfad: </b>' . implode(' : ', $levels) . '</small></div>';
        }
        return $return;
    }
}

if (!function_exists('url_generate_column_data')) {
    function url_generate_column_data($params)
    {
        $list = $params['list'];
        $return = '';
        $table = $list->getValue('table');
        $table_parameters = json_decode($list->getValue('table_parameters'), true);

        $search = [];
        $replace = [];
        $dbconfigs = rex::getProperty('db');
        foreach ($dbconfigs as $DBID => $dbconfig) {
            $search[] = Generator::mergeDatabaseAndTable($DBID, '');
            $replace[] = $dbconfig['name'] . '.';
        }
        $table_out = str_replace($search, $replace, $table);

        $url = Generator::stripRewriterSuffix(rex_getUrl($list->getValue('article_id'), $list->getValue('clang_id'))) . '/';
        $url .= ($table_parameters[$table . '_field_1'] != '') ? '<code>' . $table_parameters[$table . '_field_1'] . '</code>-' : '';
        $url .= ($table_parameters[$table . '_field_2'] != '') ? '<code>' . $table_parameters[$table . '_field_2'] . '</code>-' : '';
        $url .= ($table_parameters[$table . '_field_3'] != '') ? '<code>' . $table_parameters[$table . '_field_3'] . '</code>' : '';
        $url = rtrim($url, '-');
        $url = Generator::appendRewriterSuffix($url);

        $url_paths = '';
        if ($table_parameters[$table . '_path_names'] != '') {
            $paths = explode("\n", trim($table_parameters[$table . '_path_names']));
            if (count($paths)) {
                $url_paths .= '<b><small>' . rex_i18n::msg('url_generate_path_own') . '</small></b><br />';
                foreach ($paths as $path) {
                    $pathNameParts = explode('|', $path);
                    $pathNameForUrl = trim($pathNameParts[0]);
                    $pathSegment = str_replace(
                                        Generator::$pathSlashPlaceholder, '/',
                                        Url::getRewriter()->normalize(
                                            str_replace(
                                                '/', Generator::$pathSlashPlaceholder,
                                                $pathNameForUrl))
                                        );
                    $url_paths .= Generator::appendRewriterSuffix($url . $pathSegment) . '<br />';
                }
            }
        }
        if ($table_parameters[$table . '_path_categories'] == '1') {
            $articleCategory = \rex_category::get($list->getValue('article_id'), $list->getValue('clang_id'));
            if ($articleCategory instanceof \rex_category) {
                $categories = $articleCategory->getChildren();
                if (count($categories)) {
                    $url_paths .= '<b><small>' . rex_i18n::msg('url_generate_path_categories') . '</small></b><br />';
                    foreach ($categories as $category) {
                        $url_paths .= Generator::appendRewriterSuffix($url . Url::getRewriter()->normalize(trim($category->getName()))) . '<br />';
                    }
                }
            }
        }
        if ($table_parameters[$table . '_relation_field'] != '') {
            $url_paths .= '<b><small>' . rex_i18n::msg('url_generate_path_relation') . '</small></b><br />';
            $url_paths .= '<b><small>' . rex_i18n::msg('url_generate_path_relation_table') . '</small>:</b> ' . str_replace($search, $replace, str_replace('relation_', '', $list->getValue('relation_table'))) . '<br />';
            //$url_paths .= '<b><small>' . rex_i18n::msg('url_generate_path_relation_url_field') . '</small>:</b> ' . str_replace($search, $replace, str_replace('relation_', '', $list->getValue('relation_table'))) . '<br />';
        }

        $return .= '<dl class="url-dl">';
        $return .= '<dt>' . rex_i18n::msg('url_table') . ': </dt><dd><code>' . $table_out . '</code></dd>';
        $return .= '<dt>' . rex_i18n::msg('url') . ': </dt><dd>' . $url . '</dd>';
        $return .= '<dt>' . rex_i18n::msg('url_id') . ': </dt><dd><code>' . $table_parameters[$table . '_id'] . '</code></dd>';

        if ($table_parameters[$table . '_url_param_key'] != '') {
            $return .= '<dt>' . rex_i18n::msg('url_generate_url_param_key_short') . ': </dt><dd><code>rex_getUrl(\'\', \'\', [\'<b>' . $table_parameters[$table . '_url_param_key'] . '</b>\' => {n}])</code></dd>';
        } else {
            $return .= '<dt>' . rex_i18n::msg('url_generate_url_param_key_short') . ': </dt><dd><code>rex_getUrl(' . $list->getValue('article_id') . ', ' . $list->getValue('clang_id') . ', [\'id\' => {n}])</code></dd>';
        }

        $field = $table_parameters[$table . '_restriction_field'];
        $operator = $table_parameters[$table . '_restriction_operator'];
        $value = $table_parameters[$table . '_restriction_value'];
        if ($field != '') {
            $return .= '<dt>' . rex_i18n::msg('url_generate_restriction') . ': </dt><dd><code>' . $field . $operator . $value . '</code></dd>';
        }

        $sitemapAdd = $table_parameters[$table . '_sitemap_add'];
        if ($sitemapAdd == '1') {
            $sitemapFrequency = $table_parameters[$table . '_sitemap_frequency'];
            $sitemapPriority = $table_parameters[$table . '_sitemap_priority'];
            $return .= '
                <dt>' . rex_i18n::msg('url_generate_sitemap') . ': </dt>
                <dd>
                    ' . rex_i18n::msg('yes') . '<br />
                    <small>' . rex_i18n::msg('url_generate_notice_sitemap_frequency') . ':</small> <code>' . $sitemapFrequency . '</code><br />
                    <small>' . rex_i18n::msg('url_generate_notice_sitemap_priority') . ':</small> <code>' . $sitemapPriority . '</code>
                </dd>';
        }

        if ($url_paths != '') {
            $return .= '<dt>' . rex_i18n::msg('url_generate_path_names_short') . ': </dt><dd>' . $url_paths . '</dd>';
        }

        $return .= '</dl>';
        return $return;
    }
}
if ($func == '') {
    $query = '  SELECT      `id`,
                            `article_id`,
                            `clang_id`,
                            `url`,
                            `table`,
                            `table_parameters`, 
                            `relation_table`, 
                            `relation_table_parameters`
                FROM        ' . rex::getTable('url_generate');

    $list = rex_list::factory($query);
    $list->addTableAttribute('class', 'table-striped');

    $tdIcon = '<i class="rex-icon rex-icon-anchor"></i>';
    $thIcon = '<a href="' . $list->getUrl(['func' => 'add']) . '"' . rex::getAccesskey($this->i18n('add'), 'add') . '><i class="rex-icon rex-icon-add-article"></i></a>';
    $list->addColumn($thIcon, $tdIcon, 0, ['<th class="rex-table-icon">###VALUE###</th>', '<td class="rex-table-icon">###VALUE###</td>']);
    $list->setColumnParams($thIcon, ['func' => 'edit', 'id' => '###id###']);

    $list->removeColumn('id');
    $list->removeColumn('clang_id');
    $list->removeColumn('url');
    $list->removeColumn('table');
    $list->removeColumn('table_parameters');
    $list->removeColumn('relation_table');
    $list->removeColumn('relation_table_parameters');

    $list->setColumnLabel('article_id', $this->i18n('url_article'));
    $list->setColumnFormat('article_id', 'custom', 'url_generate_column_article');

    $list->addColumn('data', '');
    $list->setColumnLabel('data', $this->i18n('url_data'));
    $list->setColumnFormat('data', 'custom', 'url_generate_column_data');
    $list->addColumn($this->i18n('function'), $this->i18n('edit'));
    $list->setColumnLayout($this->i18n('function'), ['<th class="rex-table-action" colspan="2">###VALUE###</th>', '<td class="rex-table-action">###VALUE###</td>']);
    $list->setColumnParams($this->i18n('function'), ['func' => 'edit', 'id' => '###id###']);

    $content = $list->get();

    $fragment = new rex_fragment();
    $fragment->setVar('title', $this->i18n('url_generate'));
    $fragment->setVar('content', $content, false);
    $content = $fragment->parse('core/page/section.php');

    echo $content;
} elseif ($func == 'add' || $func == 'edit') {
    $title = $func == 'edit' ? $this->i18n('edit') : $this->i18n('add');

    $form = rex_form::factory(rex::getTable('url_generate'), '', 'id = ' . $id, 'post', false);

    $form->addParam('id', $id);
    $form->setApplyUrl(rex_url::currentBackendPage());
    $form->setEditMode($func == 'edit');

    $form->addFieldset($this->i18n('url_generate_legend_article'));

    $field = $form->addLinkmapField('article_id');
    $field->getValidator()->add('notEmpty', $this->i18n('url_generate_error_article'));
    $field->getValidator()->add('match', $this->i18n('url_generate_error_article'), '/^[1-9][0-9]*$/');
    $field->setLabel($this->i18n('url_article'));

    if (count(rex_clang::getAll()) >= 2) {
        $field->setHeader('<div class="url-grid"><div class="url-grid-item">');
        $field->setFooter('</div>');

        $field = $form->addSelectField('clang_id');
        $field->setHeader('<div class="url-grid-item">');
        $field->setFooter('</div></div>');
        $field->setPrefix('<div class="rex-select-style">');
        $field->setSuffix('</div>');
        $field->setLabel($this->i18n('url_article_language'));
        $field->setNotice($this->i18n('url_generate_notice_article_clang_ids'));
        $select = $field->getSelect();
        $select->addOption($this->i18n('url_generate_article_clang_ids'), '0');
        foreach (rex_clang::getAll() as $clang) {
            $select->addOption($clang->getName(), $clang->getId());
        }
    }

    $form->addFieldset($this->i18n('url_generate_legend_table'));

    $field = $form->addSelectField('table');
    $field->getValidator()->add('notEmpty', $this->i18n('url_generate_error_table'));
    $field->setPrefix('<div class="rex-select-style">');
    $field->setSuffix('</div>');
    $field->setLabel($this->i18n('url_table'));
    $select = $field->getSelect();
    $select->addOption($this->i18n('url_no_table_selected'), '');

    $script = '
    <script type="text/javascript">
    <!--
    (function($) {
        var currentShown = null;
        $("#' . $field->getAttribute('id') . '").change(function(){
            if(currentShown) currentShown.hide().find(":input").prop("disabled", true);
            var tableParamsId = "#rex-"+ jQuery(this).val();
            currentShown = $(tableParamsId);
            currentShown.show().find(":input").prop("disabled", false);
        }).change();
    })(jQuery);
    //-->
    </script>';

    $activeTable = $field->getValue();

    $fieldContainer = $form->addContainerField('table_parameters');
    $fieldContainer->setAttribute('style', 'display: none');
    $fieldContainer->setSuffix($script);
    $fieldContainer->setMultiple(false);
    $fieldContainer->setActive($activeTable);

    $dbconfigs = rex::getProperty('db');

    $fields = [];
    $tables = [];
    foreach ($dbconfigs as $DBID => $dbconfig) {
        if ($dbconfig['host'] . $dbconfig['login'] . $dbconfig['password'] . $dbconfig['name'] != '') {
            $connection = rex_sql::checkDbConnection(
                $dbconfig['host'],
                $dbconfig['login'],
                $dbconfig['password'],
                $dbconfig['name']
            );
            if ($connection === true) {
                $tables[$DBID] = rex_sql::showTables($DBID);
            }
        }
    }

    foreach ($tables as $DBID => $dbtables) {
        $dbname = $dbconfigs[$DBID]['name'];
        $select->addOptGroup($dbname);
        foreach ($dbtables as $dbtable) {
            $select->addOption($dbtable, Generator::mergeDatabaseAndTable($DBID, $dbtable));
            $columns = rex_sql::showColumns($dbtable, $DBID);
            foreach ($columns as $column) {
                $fields[Generator::mergeDatabaseAndTable($DBID, $dbtable)][] = $column['name'];
            }
        }
    }

    if (count($fields) > 0) {
        foreach ($fields as $table => $columns) {
            $group = $table;
            $options = $columns;
            $type = 'select';
            $name = $table . '_field_1';
            $f = $fieldContainer->addGroupedField($group, $type, $name);
            $f->setHeader('<div class="url-grid"><div class="url-grid-item">');
            $f->setFooter('</div>');
            $f->setPrefix('<div class="rex-select-style">');
            $f->setSuffix('</div>');
            $f->setAttribute('disabled', 'true');
            $f->setLabel($this->i18n('url'));
            $f->setNotice($this->i18n('url_generate_notice_name'));
            $select = $f->getSelect();
            $select->addOptions($options, true);

            $type = 'select';
            $name = $table . '_field_2';
            $f = $fieldContainer->addGroupedField($group, $type, $name);
            $f->setHeader('<div class="url-grid-item">');
            $f->setFooter('</div>');
            $f->setPrefix('<div class="rex-select-style">');
            $f->setSuffix('</div>');
            $f->setAttribute('disabled', 'true');
            $select = $f->getSelect();
            $select->addOption($this->i18n('url_generate_no_additive'), '');
            $select->addOptions($options, true);

            $type = 'select';
            $name = $table . '_field_3';
            $f = $fieldContainer->addGroupedField($group, $type, $name);
            $f->setHeader('<div class="url-grid-item">');
            $f->setFooter('</div></div>');
            $f->setPrefix('<div class="rex-select-style">');
            $f->setSuffix('</div>');
            $f->setAttribute('disabled', 'true');
            $select = $f->getSelect();
            $select->addOption($this->i18n('url_generate_no_additive'), '');
            $select->addOptions($options, true);

            $type = 'select';
            $name = $table . '_id';
            $f = $fieldContainer->addGroupedField($group, $type, $name);
            $f->setPrefix('<div class="rex-select-style">');
            $f->setSuffix('</div>');
            $f->setAttribute('disabled', 'true');
            $f->setLabel($this->i18n('url_id'));
            $f->setNotice($this->i18n('url_generate_notice_id'));
            $select = $f->getSelect();
            $select->addOptions($options, true);

            if (count(rex_clang::getAll()) >= 2) {
                $f->setHeader('<div class="url-grid"><div class="url-grid-item">');
                $f->setFooter('</div>');

                $type = 'select';
                $name = $table . '_clang_id';
                $f = $fieldContainer->addGroupedField($group, $type, $name);
                $f->setHeader('<div class="url-grid-item">');
                $f->setFooter('</div></div>');
                $f->setPrefix('<div class="rex-select-style">');
                $f->setSuffix('</div>');
                $f->setAttribute('disabled', 'true');
                $f->setLabel($this->i18n('url_language'));
                $f->setNotice($this->i18n('url_generate_notice_clang_id'));
                $select = $f->getSelect();
                $select->addOption($this->i18n('url_generate_no_clang_id'), '');
                $select->addOptions($options, true);
            } else {
                $type = 'hidden';
                $name = $table . '_clang_id';
                $f = $fieldContainer->addGroupedField($group, $type, $name, '');
            }

            $type = 'select';
            $name = $table . '_restriction_field';
            $f = $fieldContainer->addGroupedField($group, $type, $name);
            $f->setHeader('<div class="url-grid"><div class="url-grid-item">');
            $f->setFooter('</div>');
            $f->setPrefix('<div class="rex-select-style">');
            $f->setSuffix('</div>');
            $f->setAttribute('disabled', 'true');
            $f->setLabel($this->i18n('url_generate_restriction'));
            $select = $f->getSelect();
            $select->addOption($this->i18n('url_generate_no_restriction'), '');
            $select->addOptions($options, true);

            $type = 'select';
            $name = $table . '_restriction_operator';
            $f = $fieldContainer->addGroupedField($group, $type, $name);
            $f->setHeader('<div class="url-grid-item url-grid-item-small">');
            $f->setFooter('</div>');
            $f->setPrefix('<div class="rex-select-style">');
            $f->setSuffix('</div>');
            $f->setAttribute('disabled', 'true');
            $select = $f->getSelect();
            $select->addOptions(Generator::getRestrictionOperators());

            $type = 'text';
            $name = $table . '_restriction_value';
            $value = '';
            $f = $fieldContainer->addGroupedField($group, $type, $name);
            $f->setHeader('<div class="url-grid-item url-grid-item-small">');
            $f->setFooter('</div><p class="help-block">' . $this->i18n('url_generate_notice_restriction') . '</p></div>');
            $f->setAttribute('disabled', 'true');

            $type = 'text';
            $name = $table . '_url_param_key';
            $value = '';
            $f = $fieldContainer->addGroupedField($group, $type, $name);
            $f->setHeader('<div class="url-grid"><div class="url-grid-item">');
            $f->setFooter('</div><p class="help-block">' . $this->i18n('url_generate_notice_url_param_key') . '</p></div>');
            $f->setAttribute('disabled', 'true');
            $f->setLabel($this->i18n('url_generate_url_param_key'));

            $type = 'select';
            $name = $table . '_seo_title';
            $f = $fieldContainer->addGroupedField($group, $type, $name);
            $f->setHeader('<hr class="url-hr" /><div class="url-grid"><div class="url-grid-item">');
            $f->setFooter('</div>');
            $f->setPrefix('<div class="rex-select-style">');
            $f->setSuffix('</div>');
            $f->setAttribute('disabled', 'true');
            $f->setLabel($this->i18n('url_generate_seo'));
            $f->setNotice($this->i18n('url_generate_notice_seo_title'));
            $select = $f->getSelect();
            $select->addOption($this->i18n('url_generate_no_selection'), '');
            $select->addOptions($options, true);

            $type = 'select';
            $name = $table . '_seo_description';
            $f = $fieldContainer->addGroupedField($group, $type, $name);
            $f->setHeader('<div class="url-grid-item">');
            $f->setFooter('</div>');
            $f->setPrefix('<div class="rex-select-style">');
            $f->setSuffix('</div>');
            $f->setAttribute('disabled', 'true');
            $f->setNotice($this->i18n('url_generate_notice_seo_description'));
            $select = $f->getSelect();
            $select->addOption($this->i18n('url_generate_no_selection'), '');
            $select->addOptions($options, true);

            $type = 'select';
            $name = $table . '_seo_img';
            $f = $fieldContainer->addGroupedField($group, $type, $name);
            $f->setHeader('<div class="url-grid-item">');
            $f->setFooter('</div></div>');
            $f->setPrefix('<div class="rex-select-style">');
            $f->setSuffix('</div>');
            $f->setAttribute('disabled', 'true');
            $f->setNotice($this->i18n('url_generate_notice_seo_img'));
            $select = $f->getSelect();
            $select->addOption($this->i18n('url_generate_no_selection'), '');
            $select->addOptions($options, true);

            $type = 'select';
            $name = $table . '_sitemap_add';
            $f = $fieldContainer->addGroupedField($group, $type, $name);
            $f->setHeader('<hr class="url-hr" /><div class="url-grid"><div class="url-grid-item">');
            $f->setFooter('</div>');
            $f->setLabel($this->i18n('url_generate_sitemap'));
            $f->setPrefix('<div class="rex-select-style">');
            $f->setSuffix('</div>');
            $f->setAttribute('disabled', 'true');
            $f->setNotice($this->i18n('url_generate_notice_sitemap_add'));
            $select = $f->getSelect();
            $select->addOptions(['0' => $this->i18n('no'), '1' => $this->i18n('yes')]);

            $type = 'select';
            $name = $table . '_sitemap_frequency';
            $f = $fieldContainer->addGroupedField($group, $type, $name);
            $f->setHeader('<div class="url-grid-item">');
            $f->setFooter('</div>');
            $f->setPrefix('<div class="rex-select-style">');
            $f->setSuffix('</div>');
            $f->setAttribute('disabled', 'true');
            $f->setNotice($this->i18n('url_generate_notice_sitemap_frequency'));
            $select = $f->getSelect();
            $select->addOptions(Url::getRewriter()->getSitemapFrequency(), true);

            $type = 'select';
            $name = $table . '_sitemap_priority';
            $f = $fieldContainer->addGroupedField($group, $type, $name);
            $f->setHeader('<div class="url-grid-item">');
            $f->setFooter('</div>');
            $f->setPrefix('<div class="rex-select-style">');
            $f->setSuffix('</div>');
            $f->setAttribute('disabled', 'true');
            $f->setNotice($this->i18n('url_generate_notice_sitemap_priority'));
            $select = $f->getSelect();
            $select->addOptions(Url::getRewriter()->getSitemapPriority(), true);

            $type = 'select';
            $name = $table . '_sitemap_lastmod';
            $f = $fieldContainer->addGroupedField($group, $type, $name);
            $f->setHeader('<div class="url-grid-item">');
            $f->setFooter('</div></div>');
            $f->setPrefix('<div class="rex-select-style">');
            $f->setSuffix('</div>');
            $f->setAttribute('disabled', 'true');
            $f->setNotice($this->i18n('url_generate_notice_sitemap_lastmod'));
            $select = $f->getSelect();
            $select->addOption($this->i18n('url_generate_no_selection'), '');
            $select->addOptions($options, true);

            $type = 'textarea';
            $name = $table . '_path_names';
            $f = $fieldContainer->addGroupedField($group, $type, $name);
            $f->setHeader('<hr class="url-hr" /><h3>' . $this->i18n('url_generate_paths') . '</h3>');
            $f->setAttribute('disabled', 'true');
            $f->setLabel($this->i18n('url_generate_path_names'));
            $f->setNotice($this->i18n('url_generate_notice_path_names'));

            $type = 'select';
            $name = $table . '_path_categories';
            $f = $fieldContainer->addGroupedField($group, $type, $name);
            $f->setPrefix('<div class="rex-select-style">');
            $f->setSuffix('</div>');
            $f->setLabel($this->i18n('url_generate_path_categories_append'));
            $f->setNotice($this->i18n('url_generate_path_categories_notice'));
            $select = $f->getSelect();
            $select->addOptions(['0' => $this->i18n('no'), '1' => $this->i18n('yes')]);

            $type = 'select';
            $name = $table . '_relation_field';
            $f = $fieldContainer->addGroupedField($group, $type, $name);
            $f->setHeader('<div class="url-grid"><div class="url-grid-item">');
            $f->setFooter('</div></div>');
            $f->setPrefix('<div class="rex-select-style js-change-relation-field-select">');
            $f->setSuffix('</div>');
            $f->setAttribute('disabled', 'true');
            $f->setLabel($this->i18n('url_generate_relation_field'));
            $f->setNotice($this->i18n('url_generate_notice_relation_field'));
            $select = $f->getSelect();
            $select->addOption($this->i18n('url_generate_no_relation_field'), '');
            $select->addOptions($options, true);
        }
    }

    $form->addRawField('<div class="js-change-relation-field-container" style="display: none;"><fieldset><legend>' . $this->i18n('url_generate_legend_table_relation') . '</legend>');
    //$form->addFieldset($this->i18n('url_generate_legend_table_relation'));

    $field = $form->addSelectField('relation_insert');
    $field->setPrefix('<div class="rex-select-style">');
    $field->setSuffix('</div>');
    $field->setLabel($this->i18n('url_generate_relation_insert'));
    $field->setNotice($this->i18n('url_generate_relation_insert_notice'));
    $select = $field->getSelect();
    $select->addOptions(['before' => $this->i18n('before'), 'after' => $this->i18n('after')]);

    $field = $form->addSelectField('relation_table');
    $field->setPrefix('<div class="rex-select-style">');
    $field->setSuffix('</div>');
    $field->setLabel($this->i18n('url_table'));
    $select = $field->getSelect();
    $select->addOption($this->i18n('url_no_table_selected'), '');

    $activeRelationTable = $field->getValue();

    $script = '
    <script type="text/javascript">
    <!--
    (function($) {
        var currentShown = null;
        $("#' . $field->getAttribute('id') . '").change(function(){
            if(currentShown) currentShown.hide().find(":input").prop("disabled", true);
            var tableParamsId = "#rex-"+ jQuery(this).val();
            currentShown = $(tableParamsId);
            currentShown.show().find(":input").prop("disabled", false);
        }).change();
    })(jQuery);
    //-->
    </script>';

    $fields = [];
    foreach ($tables as $DBID => $dbtables) {
        $dbname = $dbconfigs[$DBID]['name'];
        $select->addOptGroup($dbname);
        foreach ($dbtables as $dbtable) {
            $select->addOption($dbtable, Generator::mergeDatabaseAndTable($DBID, 'relation_' . $dbtable));
            $columns = rex_sql::showColumns($dbtable, $DBID);
            foreach ($columns as $column) {
                $fields[Generator::mergeDatabaseAndTable($DBID, 'relation_' . $dbtable)][] = $column['name'];
            }
        }
    }

    $fieldContainer = $form->addContainerField('relation_table_parameters');
    $fieldContainer->setAttribute('style', 'display: none');
    $fieldContainer->setSuffix($script);
    $fieldContainer->setMultiple(false);
    $fieldContainer->setActive($activeRelationTable);

    if (count($fields) > 0) {
        foreach ($fields as $table => $columns) {
            $group = $table;
            $options = $columns;
            $type = 'select';
            $name = $table . '_field_1';
            $f = $fieldContainer->addGroupedField($group, $type, $name);
            $f->setHeader('<div class="url-grid"><div class="url-grid-item">');
            $f->setFooter('</div>');
            $f->setPrefix('<div class="rex-select-style">');
            $f->setSuffix('</div>');
            $f->setAttribute('disabled', 'true');
            $f->setLabel($this->i18n('url'));
            $f->setNotice($this->i18n('url_generate_notice_name'));
            $select = $f->getSelect();
            $select->addOptions($options, true);

            $type = 'select';
            $name = $table . '_field_2';
            $f = $fieldContainer->addGroupedField($group, $type, $name);
            $f->setHeader('<div class="url-grid-item">');
            $f->setFooter('</div>');
            $f->setPrefix('<div class="rex-select-style">');
            $f->setSuffix('</div>');
            $f->setAttribute('disabled', 'true');
            $select = $f->getSelect();
            $select->addOption($this->i18n('url_generate_no_additive'), '');
            $select->addOptions($options, true);

            $type = 'select';
            $name = $table . '_field_3';
            $f = $fieldContainer->addGroupedField($group, $type, $name);
            $f->setHeader('<div class="url-grid-item">');
            $f->setFooter('</div></div>');
            $f->setPrefix('<div class="rex-select-style">');
            $f->setSuffix('</div>');
            $f->setAttribute('disabled', 'true');
            $select = $f->getSelect();
            $select->addOption($this->i18n('url_generate_no_additive'), '');
            $select->addOptions($options, true);

            $type = 'select';
            $name = $table . '_id';
            $f = $fieldContainer->addGroupedField($group, $type, $name);
            $f->setPrefix('<div class="rex-select-style">');
            $f->setSuffix('</div>');
            $f->setAttribute('disabled', 'true');
            $f->setLabel($this->i18n('url_id'));
            $f->setNotice($this->i18n('url_generate_notice_id'));
            $select = $f->getSelect();
            $select->addOptions($options, true);

            if (count(rex_clang::getAll()) >= 2) {
                $f->setHeader('<div class="url-grid"><div class="url-grid-item">');
                $f->setFooter('</div>');

                $type = 'select';
                $name = $table . '_clang_id';
                $f = $fieldContainer->addGroupedField($group, $type, $name);
                $f->setHeader('<div class="url-grid-item">');
                $f->setFooter('</div></div>');
                $f->setPrefix('<div class="rex-select-style">');
                $f->setSuffix('</div>');
                $f->setAttribute('disabled', 'true');
                $f->setLabel($this->i18n('url_language'));
                $f->setNotice($this->i18n('url_generate_notice_clang_id'));
                $select = $f->getSelect();
                $select->addOption($this->i18n('url_generate_no_clang_id'), '');
                $select->addOptions($options, true);
            } else {
                $type = 'hidden';
                $name = $table . '_clang_id';
                $f = $fieldContainer->addGroupedField($group, $type, $name, '');
            }
        }
    }

    $form->addRawField('</fieldset></div>');

    $content = $form->get();

    $fragment = new rex_fragment();
    $fragment->setVar('class', 'edit url-container', false);
    $fragment->setVar('title', $title);
    $fragment->setVar('body', $content, false);
    $content = $fragment->parse('core/page/section.php');

    echo $content;
}

if ($func == 'add' || $func == 'edit') {
    ?>
    <script type="text/javascript">
        (function($) {
            var counter = 0;
            var $currentShownRelationSection = $(".js-change-relation-field-container");
            $currentShownRelationSection.hide();
            $(".js-change-relation-field-select select").change(function(){
                if ($(this).closest(".rex-form-container").is(":visible")) {
                    if ($(this).val().length > 0) {
                        $currentShownRelationSection.show();
                    } else {
                        $currentShownRelationSection.hide();
                    }
                }
            }).change();
        })(jQuery);

    </script>
<?php

}
?>
<style>
    .form-group > dd .rex-select-style select.form-control {
        margin-top: 0;
        margin-bottom: 0;
        padding-top: 7px;
        padding-bottom: 7px;
    }
    .url-container .input-group,
    .url-container .rex-select-style,
    .url-container .url-grid-item .form-control {
        width: 300px;
    }
    .url-container .input-group .form-control {
        width: 100%;
    }
    .url-container .help-block {
        color: #324050;
        font-size: 90%;
    }
    .url-container .url-grid-item-small .rex-select-style,
    .url-container .url-grid-item-small .rex-form-group,
    .url-container .url-grid-item-small .form-control {
        width: 135px;
    }
    .url-grid {
        margin-left: -15px;
        margin-right: -15px;
    }
    .url-grid:before,
    .url-grid:after {
        content: '';
        display: table;
    }
    .url-grid:after {
        clear: both;
    }
    .url-grid > .help-block {
        clear: both;
        position: relative;
        top: -10px;
    }
    .url-grid-item {
        position: relative;
        float: left;
        min-height: 1px;
        padding-left: 15px;
        padding-right: 15px;
    }
    .url-grid-item > .rex-form-group {
        display: block;
        width: auto;
    }
    .url-grid-item > .rex-form-group > * {
        display: block;
    }
    .url-grid-item > .rex-form-group > dd:first-child {
        padding-left: 0;
    }
    .url-grid-item + .url-grid-item > .rex-form-group > dt {
        width: 330px;
        text-align: right;
    }
    @media (min-width: 992px) {
        .url-grid > .help-block {
            padding-left: 195px
        }
    }
    @media (min-width: 1200px) {
        .url-grid > .help-block {
            padding-left: 225px
        }
    }
    @media (min-width: 1400px) {
        .url-grid > .help-block {
            padding-left: 315px
        }
    }

    .url-dl > dt {
        clear: left;
        float: left;
        margin-bottom: 4px;
        font-size: 90%;
        font-weight: 700;
    }
    .url-dl > dd {
        margin-left: 110px;
        margin-bottom: 4px;
    }
    .url-hr {
        border-top-color: #c1c9d4;
    }
</style>
