<?php

/**
 * This file is part of the Url package.
 *
 * @author (c) Thomas Blum <thomas@addoff.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Url\Cache;
use Url\Database;
use Url\Generator;
use Url\Profile;
use Url\Url;

$id = rex_request('id', 'int');
$func = rex_request('func', 'string');
$action = rex_request('action', 'string');

if ($action == 'cache') {
    Cache::deleteProfiles();
}

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
            $return .= '<a href="'.rex_url::backendPage('/content/edit', ['category_id' => $a->getCategoryId(), 'article_id' => $a->getId(), 'clang' => $a->getClangId(), 'mode' => 'edit']).'">Backend</a>';
            $return .= ' | ';
            $return .= '<a href="'.rex_getUrl($list->getValue('article_id'), $list->getValue('clang_id')).'">Frontend</a>';
            $return .= ']';

            $return .= '<div><small><b>Domain: </b>'.\rex_yrewrite::getDomainByArticleId($a->getId(), $a->getClangId()).'</small></div>';

            $tree = $a->getParentTree();

            $levels = [];
            if (count(rex_clang::getAll()) >= 2 && in_array($list->getValue('clang_id'), rex_clang::getAllIds())) {
                $levels[] = rex_clang::get($list->getValue('clang_id'))->getName();
            }

            foreach ($tree as $object) {
                $levels[] = $object->getName();
            }
            $return .= '<div><small><b>Pfad: </b>'.implode(' : ', $levels).'</small></div>';
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
            $replace[] = $dbconfig['name'].'.';
        }
        $table_out = str_replace($search, $replace, $table);

        $url = Generator::stripRewriterSuffix(rex_getUrl($list->getValue('article_id'), $list->getValue('clang_id'))).'/';
        $url .= ($table_parameters[$table.'_field_1'] != '') ? '<code>'.$table_parameters[$table.'_field_1'].'</code>-' : '';
        $url .= ($table_parameters[$table.'_field_2'] != '') ? '<code>'.$table_parameters[$table.'_field_2'].'</code>-' : '';
        $url .= ($table_parameters[$table.'_field_3'] != '') ? '<code>'.$table_parameters[$table.'_field_3'].'</code>' : '';
        $url = rtrim($url, '-');
        $url = Generator::appendRewriterSuffix($url);

        $url_paths = '';
        if ($table_parameters[$table.'_path_names'] != '') {
            $paths = explode("\n", trim($table_parameters[$table.'_path_names']));
            if (count($paths)) {
                $url_paths .= '<b><small>'.rex_i18n::msg('url_generate_path_own').'</small></b><br />';
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
                    $url_paths .= Generator::buildUrl($url, [$pathSegment]).'<br />';
                }
            }
        }
        if ($table_parameters[$table.'_path_categories'] == '1') {
            $articleCategory = \rex_category::get($list->getValue('article_id'), $list->getValue('clang_id'));
            if ($articleCategory instanceof \rex_category) {
                $categories = $articleCategory->getChildren();
                if (count($categories)) {
                    $url_paths .= '<b><small>'.rex_i18n::msg('url_generate_path_categories').'</small></b><br />';
                    foreach ($categories as $category) {
                        $url_paths .= Generator::buildUrl($url, [trim($category->getName())]).'<br />';
                    }
                }
            }
        }
        if ($table_parameters[$table.'_relation_field'] != '') {
            $url_paths .= '<b><small>'.rex_i18n::msg('url_generator_path_relation').'</small></b><br />';
            $url_paths .= '<b><small>'.rex_i18n::msg('url_generator_path_relation_table').'</small>:</b> '.str_replace($search, $replace, str_replace('relation_', '', $list->getValue('relation_table'))).'<br />';
            //$url_paths .= '<b><small>' . rex_i18n::msg('url_generate_path_relation_url_field') . '</small>:</b> ' . str_replace($search, $replace, str_replace('relation_', '', $list->getValue('relation_table'))) . '<br />';
        }

        $return .= '<dl class="url-dl">';
        $return .= '<dt>'.rex_i18n::msg('url_table').': </dt><dd><code>'.$table_out.'</code></dd>';
        $return .= '<dt>'.rex_i18n::msg('url').': </dt><dd>'.$url.'</dd>';
        $return .= '<dt>'.rex_i18n::msg('url_id').': </dt><dd><code>'.$table_parameters[$table.'_id'].'</code></dd>';

        if ($table_parameters[$table.'_url_param_key'] != '') {
            $return .= '<dt>'.rex_i18n::msg('url_generate_url_param_key_short').': </dt><dd><code>rex_getUrl(\'\', \'\', [\'<b>'.$table_parameters[$table.'_url_param_key'].'</b>\' => {n}])</code></dd>';
        } else {
            $return .= '<dt>'.rex_i18n::msg('url_generate_url_param_key_short').': </dt><dd><code>rex_getUrl('.$list->getValue('article_id').', '.$list->getValue('clang_id').', [\'id\' => {n}])</code></dd>';
        }

        $field = $table_parameters[$table.'_restriction_field'];
        $operator = $table_parameters[$table.'_restriction_operator'];
        $value = $table_parameters[$table.'_restriction_value'];
        if ($field != '') {
            $return .= '<dt>'.rex_i18n::msg('url_generate_restriction').': </dt><dd><code>'.$field.$operator.$value.'</code></dd>';
        }

        $sitemapAdd = $table_parameters[$table.'_sitemap_add'];
        if ($sitemapAdd == '1') {
            $sitemapFrequency = $table_parameters[$table.'_sitemap_frequency'];
            $sitemapPriority = $table_parameters[$table.'_sitemap_priority'];
            $return .= '
                <dt>'.rex_i18n::msg('url_generate_sitemap').': </dt>
                <dd>
                    '.rex_i18n::msg('yes').'<br />
                    <small>'.rex_i18n::msg('url_generate_notice_sitemap_frequency').':</small> <code>'.$sitemapFrequency.'</code><br />
                    <small>'.rex_i18n::msg('url_generate_notice_sitemap_priority').':</small> <code>'.$sitemapPriority.'</code>
                </dd>';
        }

        if ($url_paths != '') {
            $return .= '<dt>'.rex_i18n::msg('url_generate_path_names_short').': </dt><dd>'.$url_paths.'</dd>';
        }

        $return .= '</dl>';
        return $return;
    }
}
if ($func == '') {
    $query = '  SELECT      `id`,
                            `article_id`,
                            `clang_id`,
                            `namespace`
                FROM        '.rex::getTable('url_generator_profile');

    $list = rex_list::factory($query);
    $list->addTableAttribute('class', 'table-striped');

    $tdIcon = '<i class="rex-icon fa fa-gears"></i>';
    $thIcon = '<a href="'.$list->getUrl(['func' => 'add']).'"'.rex::getAccesskey($this->i18n('add'), 'add').'><i class="rex-icon rex-icon-add-article"></i></a>';
    $list->addColumn($thIcon, $tdIcon, 0, ['<th class="rex-table-icon">###VALUE###</th>', '<td class="rex-table-icon">###VALUE###</td>']);
    $list->setColumnParams($thIcon, ['func' => 'edit', 'id' => '###id###']);

    $list->setColumnLabel('id', rex_i18n::msg('id'));
    $list->setColumnLayout('id', ['<th class="rex-table-id">###VALUE###</th>', '<td class="rex-table-id" data-title="'.rex_i18n::msg('id').'">###VALUE###</td>']);
    //$list->removeColumn('clang_id');

    $list->setColumnLabel('article_id', $this->i18n('url_generator_article'));
    $list->setColumnFormat('article_id', 'custom', 'url_generate_column_article');

    //$list->addColumn('data', '');
    //$list->setColumnLabel('data', $this->i18n('url_generator_data'));
    //$list->setColumnFormat('data', 'custom', 'url_generate_column_data');

    $list->addColumn($this->i18n('function'), '<i class="rex-icon rex-icon-edit"></i> '.$this->i18n('edit'));
    $list->setColumnLayout($this->i18n('function'), ['<th class="rex-table-action" colspan="2">###VALUE###</th>', '<td class="rex-table-action">###VALUE###</td>']);
    $list->setColumnParams($this->i18n('function'), ['func' => 'edit', 'id' => '###id###']);

    $content = $list->get();

    $fragment = new rex_fragment();
    $fragment->setVar('title', $this->i18n('url_generator_profiles'));
    $fragment->setVar('content', $content, false);
    $content = $fragment->parse('core/page/section.php');

    echo $content;
} elseif ($func == 'add' || $func == 'edit') {
    $title = $func == 'edit' ? $this->i18n('edit') : $this->i18n('add');

    $form = rex_form::factory(rex::getTable('url_generator_profile'), '', 'id = '.$id, 'post', false);
    $form->addParam('id', $id);
    $form->addParam('action', 'cache');
    $form->setApplyUrl(rex_url::currentBackendPage());
    $form->setEditMode($func == 'edit');

    $form->addFieldset($this->i18n('url_generator_article_legend'));
    $form->addErrorMessage(REX_FORM_ERROR_VIOLATE_UNIQUE_KEY, $this->i18n('url_generator_namespace_error'));

    $fieldNamespace = $form->addTextField('namespace');
    $fieldNamespace->setHeader('
        <div class="addoff-grid">
            <div class="addoff-grid-item" data-addoff-size="2">
                <label>'.$this->i18n('url_generator_namespace').'</label>
            </div>
            <div class="addoff-grid-item" data-addoff-size="3">');
    $fieldNamespace->setFooter('
            </div>
        </div>');
    $fieldNamespace->setNotice($this->i18n('url_generator_namespace_notice'));
    $fieldNamespace->getValidator()
        ->add('notEmpty', $this->i18n('url_generator_namespace_error'))
        ->add('match', $this->i18n('url_generator_namespace_error'), '/^[a-z0-9_-]*$/');

    $fieldArticleId = $form->addLinkmapField('article_id');
    $fieldArticleId->setHeader('
        <div class="addoff-grid">
            <div class="addoff-grid-item" data-addoff-size="2">
                <label>'.$this->i18n('url_generator_structure_article').'</label>
            </div>
            <div class="addoff-grid-item" data-addoff-size="3">');
    $fieldArticleId->setNotice($this->i18n('url_generator_article'));
    $fieldArticleId->getValidator()
        ->add('notEmpty', $this->i18n('url_generator_article_error'))
        ->add('match', $this->i18n('url_generator_article_error'), '/^[1-9][0-9]*$/');

    if (count(\rex_clang::getAll()) >= 2) {
        $fieldArticleId->setFooter('
                </div>');

        $fieldArticleClangId = $form->addSelectField('clang_id');
        $fieldArticleClangId->setHeader('
                <div class="addoff-grid-item" data-addoff-size="3">');
        $fieldArticleClangId->setFooter('
                </div>
            </div>');
        $fieldArticleClangId->setPrefix('<div class="rex-select-style">');
        $fieldArticleClangId->setSuffix('</div>');
        $fieldArticleClangId->setNotice($this->i18n('url_generator_article_clang').'; '.$this->i18n('url_generator_article_clang_notice', $this->i18n('url_generator_identify_record')));
        $select = $fieldArticleClangId->getSelect();
        $select->addOption($this->i18n('url_generator_article_clang_option_all'), '0');
        foreach (\rex_clang::getAll() as $clang) {
            $select->addOption($clang->getName(), $clang->getId());
        }
    } else {
        $fieldArticleId->setFooter('
                </div>
            </div>');
    }

    $form->addFieldset($this->i18n('url_generator_table_legend'));

    $fieldTable = $form->addSelectField('table_name');
    $fieldTable->setHeader('
        <div class="addoff-grid">
            <div class="addoff-grid-item" data-addoff-size="2">
                <label>'.$this->i18n('url_generator_table').'</label>
            </div>
            <div class="addoff-grid-item" data-addoff-size="3">');
    $fieldTable->setFooter('
            </div>
        </div>');
    $fieldTable->setPrefix('<div class="rex-select-style">');
    $fieldTable->setSuffix('</div>');
    $fieldTable->getValidator()
        ->add('notEmpty', $this->i18n('url_generator_table_error'));
    $fieldTableSelect = $fieldTable->getSelect();
    $fieldTableSelect->addOption($this->i18n('url_generator_table_not_selected'), '');

    $script = '
    <script type="text/javascript">
    <!--
    (function($) {
        var currentShown = null;
        $("#'.$fieldTable->getAttribute('id').'").change(function(){
            if(currentShown) currentShown.hide().find(":input").prop("disabled", true);
            var tableParamsId = "#rex-"+ jQuery(this).val();
            currentShown = $(tableParamsId);
            currentShown.show().find(":input").prop("disabled", false);
        }).change();
    })(jQuery);
    //-->
    </script>';

    $fieldContainer = $form->addContainerField('table_parameters');
    $fieldContainer->setAttribute('style', 'display: none');
    $fieldContainer->setSuffix($script);
    $fieldContainer->setMultiple(false);
    $fieldContainer->setActive($fieldTable->getValue());

    $supportedTables = Database::getSupportedTables();

    $fields = [];
    foreach ($supportedTables as $DBID => $databases) {
        $fieldTableSelect->addOptGroup($databases['name']);
        foreach ($databases['tables'] as $table) {
            $fieldTableSelect->addOption($table['name'], $table['name_unique']);
            foreach ($table['columns'] as $column) {
                $fields[$table['name_unique']][] = $column['name'];
            }
        }
    }

    if (count($fields) > 0) {
        foreach ($fields as $table => $columns) {
            $group = $table;
            $options = $columns;

            $type = 'select';
            $name = 'column_id';
            /* @var $f rex_form_select_element */
            $f = $fieldContainer->addGroupedField($group, $type, $name);
            $f->setHeader('
                <hr class="addoff-hr">
                <div class="addoff-grid">
                    <div class="addoff-grid-item" data-addoff-size="2">
                        <label>'.$this->i18n('url_generator_identify_record').'</label>
                    </div>
                    <div class="addoff-grid-item" data-addoff-size="3">');
            $f->setFooter('
                    </div>');
            $f->setPrefix('<div class="rex-select-style">');
            $f->setSuffix('</div>');
            $f->setAttribute('disabled', 'true');
            $f->setNotice($this->i18n('url_generator_id_notice'));
            $select = $f->getSelect();
            $select->addOptions($options, true);

            if (count(rex_clang::getAll()) >= 2) {
                $type = 'select';
                $name = 'column_clang_id';
                /* @var $f rex_form_select_element */
                $f = $fieldContainer->addGroupedField($group, $type, $name);
                $f->setHeader('
                        <div class="addoff-grid-item" data-addoff-size="3">');
                $f->setFooter('
                        </div>
                    </div>');
                $f->setPrefix('<div class="rex-select-style">');
                $f->setSuffix('</div>');
                $f->setAttribute('disabled', 'true');
                $f->setNotice($this->i18n('url_language').' '.$this->i18n('url_generator_clang_id_notice'));
                $select = $f->getSelect();
                $select->addOption($this->i18n('url_generator_no_clang_id'), '');
                $select->addOptions($options, true);
            } else {

                $type = 'hidden';
                $name = 'column_clang_id';
                $f = $fieldContainer->addGroupedField($group, $type, $name, '');
                $f->setFooter('</div>');
            }

            for ($i = 1; $i <= Profile::RESTRICTION_COUNT; ++$i) {
                if ($i > 1) {
                    $type = 'select';
                    $name = 'restriction_'.$i.'_logical_operator';
                    /* @var $f rex_form_select_element */
                    $f = $fieldContainer->addGroupedField($group, $type, $name);
                    $f->setHeader('
                                <div class="addoff-grid">
                                    <div class="addoff-grid-item" data-addoff-size="3of10" data-addoff-shift="2of10">');
                    $f->setFooter('
                                    </div>
                                </div>');
                    $f->setPrefix('<div class="rex-select-style">');
                    $f->setSuffix('</div>');
                    $f->setAttribute('disabled', 'true');
                    $select = $f->getSelect();
                    $select->addOption('', '');
                    $select->addOptions(Database::getLogicalOperators());
                }

                $type = 'select';
                $name = 'restriction_'.$i.'_column';
                /* @var $f rex_form_select_element */
                $f = $fieldContainer->addGroupedField($group, $type, $name);

                $prependHeader = '';
                if ($i == 1) {
                    $prependHeader = '
                    <hr class="addoff-hr" />
                    <div class="addoff-grid">
                        <div class="addoff-grid-item" data-addoff-size="2">
                            <label>'.$this->i18n('url_generator_restriction').'</label>
                            <p class="help-block">'.$this->i18n('url_generator_restriction_notice').'</p>
                        </div>
                        <div class="addoff-grid-item" data-addoff-size="10">';
                }
                $f->setHeader(
                        $prependHeader.'
                            <div class="addoff-grid">
                                <div class="addoff-grid-item" data-addoff-size="3of10">');
                $f->setFooter('
                                </div>');
                $f->setPrefix('<div class="rex-select-style">');
                $f->setSuffix('</div>');
                $f->setAttribute('disabled', 'true');
                $select = $f->getSelect();
                $select->addOption($this->i18n('url_generator_no_restriction'), '');
                $select->addOptions($options, true);

                $type = 'select';
                $name = 'restriction_'.$i.'_comparison_operator';
                /* @var $f rex_form_select_element */
                $f = $fieldContainer->addGroupedField($group, $type, $name);
                $f->setHeader('<div class="addoff-grid-item" data-addoff-size="1of10">');
                $f->setFooter('</div>');
                $f->setPrefix('<div class="rex-select-style">');
                $f->setSuffix('</div>');
                $f->setAttribute('disabled', 'true');
                $select = $f->getSelect();
                $select->addOptions(Database::getComparisonOperators());

                $type = 'text';
                $name = 'restriction_'.$i.'_value';
                $value = '';
                /* @var $f rex_form_element */
                $f = $fieldContainer->addGroupedField($group, $type, $name);
                $f->setHeader('
                                <div class="addoff-grid-item" data-addoff-size="3of10">');

                $appendFooter = ($i == Profile::RESTRICTION_COUNT) ? '</div></div>' : '';
                $f->setFooter('
                                </div>
                            </div>'.$appendFooter);
                $f->setAttribute('disabled', 'true');
            }

            for ($i = 1; $i <= Profile::SEGMENT_PART_COUNT; ++$i) {
                if ($i > 1) {
                    $type = 'select';
                    $name = 'column_segment_part_'.$i.'_separator';
                    /* @var $f rex_form_select_element */
                    $f = $fieldContainer->addGroupedField($group, $type, $name);
                    $f->setHeader('<div class="addoff-grid-item text-center text-large" data-addoff-size="1">');
                    $f->setFooter('</div>');
                    $f->setPrefix('<div class="rex-select-style">');
                    $f->setSuffix('</div>');
                    $f->setAttribute('disabled', 'true');
                    $select = $f->getSelect();
                    $select->addOptions(UrlManager::getSegmentPartSeparators());
                }

                $type = 'select';
                $name = 'column_segment_part_'.$i;
                /* @var $f rex_form_select_element */
                $f = $fieldContainer->addGroupedField($group, $type, $name);

                // $prependHeader = '<div class="addoff-grid-item text-center text-large" data-addoff-size="1"><b>/</b></div>';
                $prependHeader = '';
                if ($i == 1) {
                    $prependHeader = '
                    <hr class="addoff-hr" />
                    <div class="addoff-grid">
                        <div class="addoff-grid-item" data-addoff-size="2">
                            <label>'.$this->i18n('url').'</label>
                            <p class="help-block">'.$this->i18n('url_generator_url_notice').'</p>
                        </div>
                    ';
                }
                $f->setHeader($prependHeader.'
                        <div class="addoff-grid-item" data-addoff-size="2">');

                $appendFooter = ($i == Profile::SEGMENT_PART_COUNT) ? '</div>' : '';
                $f->setFooter('
                        </div>'.$appendFooter);
                $f->setPrefix('<div class="rex-select-style">');
                $f->setSuffix('</div>');
                $f->setAttribute('disabled', 'true');
                $select = $f->getSelect();
                if ($i > 1) {
                    $select->addOption($this->i18n('url_generator_no_additive'), '');
                }
                $select->addOptions($options, true);
            }

            for ($i = 1; $i <= Profile::RELATION_COUNT; ++$i) {
                $prependHeader = '';
                if ($i == 1) {
                    $prependHeader = '
                    <hr class="addoff-hr">
                    <div class="addoff-grid">
                        <div class="addoff-grid-item" data-addoff-size="2">
                            <label>'.$this->i18n('url_generator_relation_paths').'</label>
                            <p class="help-block">'.$this->i18n('url_generator_relation_column_notice').'</p>
                            <p class="help-block">'.$this->i18n('url_generator_relation_position_in_url').'<br />'.$this->i18n('url_generator_relation_position_notice').' '.$this->i18n('url_generator_relation_position_notice__2').'</p>
                        </div>
                        <div class="addoff-grid-item" data-addoff-size="10">
                            <div class="addoff-grid">
                                <div class="addoff-grid-item" data-addoff-size="2of10" data-addoff-shift="1of10">
                                    <p class="help-block">'.$this->i18n('url_generator_relation_column', '').'</p>
                                </div>
                                <div class="addoff-grid-item" data-addoff-size="2of10">
                                    <p class="help-block">'.$this->i18n('url_generator_relation_position_in_url').'</p>
                                </div>
                            </div>';
                }

                $type = 'select';
                $name = 'relation_'.$i.'_column';
                /* @var $f rex_form_select_element */
                $f = $fieldContainer->addGroupedField($group, $type, $name);
                $f->setHeader(
                        $prependHeader.'
                            <div class="addoff-grid">
                                <div class="addoff-grid-item" data-addoff-size="1of10"><label>'.$this->i18n('url_generator_relation', $i).'</label></div>
                                <div class="addoff-grid-item" data-addoff-size="2of10">');
                $f->setFooter('
                                </div>');
                $f->setPrefix('<div class="rex-select-style js-change-relation-'.$i.'-select">');
                $f->setSuffix('</div>');
                $f->setAttribute('disabled', 'true');
                // $f->setNotice($this->i18n('url_generator_relation_column_notice'));
                $select = $f->getSelect();
                $select->addOption($this->i18n('url_generator_no_relation_column'), '');
                $select->addOptions($options, true);

                $type = 'select';
                $name = 'relation_'.$i.'_position';
                /* @var $f rex_form_select_element */
                $f = $fieldContainer->addGroupedField($group, $type, $name);
                $f->setHeader('
                                <div class="addoff-grid-item" data-addoff-size="2of10">');
                $appendFooter = '';
                if ($i == Profile::RELATION_COUNT) {
                    $appendFooter = '
                            <div class="addoff-grid">
                                <div class="addoff-grid-item" data-addoff-size="1of10">
                                    <p class="help-block">'.$this->i18n('url_generator_relation_position_eg_label', '').'</p>
                                </div>
                                <div class="addoff-grid-item" data-addoff-size="4of10">
                                    <p class="help-block">'.$this->i18n('url_generator_relation_position_eg_code').'</p>
                                </div>
                            </div>
                        </div>
                    </div>';
                }
                $f->setFooter('
                                </div>
                            </div>'.$appendFooter);
                $f->setPrefix('<div class="rex-select-style">');
                $f->setSuffix('</div>');
                $f->setAttribute('disabled', 'true');
                // $f->setNotice($this->i18n('url_generator_relation_position_notice'));
                $select = $f->getSelect();
                $select->addOptions(['BEFORE' => $this->i18n('before'), 'AFTER' => $this->i18n('after')]);
            }

            $type = 'textarea';
            $name = 'append_user_paths';
            /* @var $f rex_form_element */
            $f = $fieldContainer->addGroupedField($group, $type, $name);
            $f->setHeader('
                <hr class="addoff-hr">
                <div class="addoff-grid">
                    <div class="addoff-grid-item" data-addoff-size="2">
                        <label>'.$this->i18n('url_generator_paths').'</label>
                    </div>
                    <div class="addoff-grid-item" data-addoff-size="4">');
            $f->setFooter('
                    </div>');
            $f->setPrefix('<label>'.$this->i18n('url_generator_append_user_path').'</label>');
            $f->setAttribute('disabled', 'true');
            $f->setNotice($this->i18n('url_generator_append_user_path_notice'));

            $type = 'select';
            $name = 'append_structure_categories';
            /* @var $f rex_form_select_element */
            $f = $fieldContainer->addGroupedField($group, $type, $name);
            $f->setHeader('
                    <div class="addoff-grid-item" data-addoff-size="3">');
            $f->setFooter('
                    </div>
                </div>');
            $f->setPrefix('<label>'.$this->i18n('url_generator_append_structure_categories_append').'</label><div class="rex-select-style">');
            $f->setSuffix('</div>');
            $f->setNotice($this->i18n('url_generator_append_structure_categories_notice'));
            $select = $f->getSelect();
            $select->addOptions(['0' => $this->i18n('no'), '1' => $this->i18n('yes')]);

            $type = 'select';
            $name = 'column_seo_title';
            /* @var $f rex_form_select_element */
            $f = $fieldContainer->addGroupedField($group, $type, $name);
            $f->setHeader('
                <hr class="addoff-hr">
                <div class="addoff-grid">
                    <div class="addoff-grid-item" data-addoff-size="2">
                        <label>'.$this->i18n('url_generator_seo').'</label>
                    </div>
                    <div class="addoff-grid-item" data-addoff-size="3">');
            $f->setFooter('
                    </div>');
            $f->setPrefix('<div class="rex-select-style">');
            $f->setSuffix('</div>');
            $f->setAttribute('disabled', 'true');
            $f->setNotice($this->i18n('url_generator_seo_title_notice'));
            $select = $f->getSelect();
            $select->addOption($this->i18n('url_generator_no_selection'), '');
            $select->addOptions($options, true);

            $type = 'select';
            $name = 'column_seo_description';
            /* @var $f rex_form_select_element */
            $f = $fieldContainer->addGroupedField($group, $type, $name);
            $f->setHeader('
                    <div class="addoff-grid-item" data-addoff-size="3">');
            $f->setFooter('
                    </div>');
            $f->setPrefix('<div class="rex-select-style">');
            $f->setSuffix('</div>');
            $f->setAttribute('disabled', 'true');
            $f->setNotice($this->i18n('url_generator_seo_description_notice'));
            $select = $f->getSelect();
            $select->addOption($this->i18n('url_generator_no_selection'), '');
            $select->addOptions($options, true);

            $type = 'select';
            $name = 'column_seo_image';
            /* @var $f rex_form_select_element */
            $f = $fieldContainer->addGroupedField($group, $type, $name);
            $f->setHeader('
                    <div class="addoff-grid-item" data-addoff-size="3">');
            $f->setFooter('
                    </div>
                </div>');
            $f->setPrefix('<div class="rex-select-style">');
            $f->setSuffix('</div>');
            $f->setAttribute('disabled', 'true');
            $f->setNotice($this->i18n('url_generator_seo_image_notice'));
            $select = $f->getSelect();
            $select->addOption($this->i18n('url_generator_no_selection'), '');
            $select->addOptions($options, true);

            $type = 'select';
            $name = 'sitemap_add';
            /* @var $f rex_form_select_element */
            $f = $fieldContainer->addGroupedField($group, $type, $name);
            $f->setHeader('
                <hr class="addoff-hr">
                <div class="addoff-grid">
                    <div class="addoff-grid-item" data-addoff-size="2">
                        <label>'.$this->i18n('url_generator_sitemap').'</label>
                    </div>
                    <div class="addoff-grid-item" data-addoff-size="2">');
            $f->setFooter('
                    </div>');
            $f->setPrefix('<div class="rex-select-style">');
            $f->setSuffix('</div>');
            $f->setAttribute('disabled', 'true');
            $f->setNotice($this->i18n('url_generator_sitemap_add_notice'));
            $select = $f->getSelect();
            $select->addOptions(['0' => $this->i18n('no'), '1' => $this->i18n('yes')]);

            $type = 'select';
            $name = 'sitemap_frequency';
            /* @var $f rex_form_select_element */
            $f = $fieldContainer->addGroupedField($group, $type, $name);
            $f->setHeader('
                    <div class="addoff-grid-item" data-addoff-size="2">');
            $f->setFooter('
                    </div>');
            $f->setPrefix('<div class="rex-select-style">');
            $f->setSuffix('</div>');
            $f->setAttribute('disabled', 'true');
            $f->setNotice($this->i18n('url_generator_sitemap_frequency_notice'));
            $select = $f->getSelect();
            $select->addOptions(Url::getRewriter()->getSitemapFrequency(), true);

            $type = 'select';
            $name = 'sitemap_priority';
            /* @var $f rex_form_select_element */
            $f = $fieldContainer->addGroupedField($group, $type, $name);
            $f->setHeader('
                    <div class="addoff-grid-item" data-addoff-size="2">');
            $f->setFooter('
                    </div>');
            $f->setPrefix('<div class="rex-select-style">');
            $f->setSuffix('</div>');
            $f->setAttribute('disabled', 'true');
            $f->setNotice($this->i18n('url_generator_sitemap_priority_notice'));
            $select = $f->getSelect();
            $select->addOptions(Url::getRewriter()->getSitemapPriority(), true);

            $type = 'select';
            $name = 'column_sitemap_lastmod';
            /* @var $f rex_form_select_element */
            $f = $fieldContainer->addGroupedField($group, $type, $name);
            $f->setHeader('
                    <div class="addoff-grid-item" data-addoff-size="3">');
            $f->setFooter('
                    </div>
                </div>');
            $f->setPrefix('<div class="rex-select-style">');
            $f->setSuffix('</div>');
            $f->setAttribute('disabled', 'true');
            $f->setNotice($this->i18n('url_generator_sitemap_lastmod_notice'));
            $select = $f->getSelect();
            $select->addOption($this->i18n('url_generator_no_selection'), '');
            $select->addOptions($options, true);
        }
    }

    for ($i = 1; $i <= Profile::RELATION_COUNT; ++$i) {
        $form->addRawField('<div class="js-change-relation-'.$i.'-container" style="display: none;"><fieldset><legend>'.$this->i18n('url_generator_table_relation_legend', $i).'</legend>');

        $f = $form->addSelectField('relation_'.$i.'_table_name');
        $f->setHeader('
            <div class="addoff-grid">
                <div class="addoff-grid-item" data-addoff-size="2">
                    <label>'.$this->i18n('url_generator_table').'</label>
                </div>
                <div class="addoff-grid-item" data-addoff-size="2">');
        $f->setFooter('
                </div>
            </div>');
        $f->setPrefix('<div class="rex-select-style">');
        $f->setSuffix('</div>');
        $fieldRelationTableSelect = $f->getSelect();
        $fieldRelationTableSelect->addOption($this->i18n('url_no_table_selected'), '');

        $activeRelationTable = $f->getValue();

        $script = '
        <script type="text/javascript">
        <!--
        (function($) {
            var currentShown = null;
            $("#'.$f->getAttribute('id').'").change(function(){
                if(currentShown) currentShown.hide().find(":input").prop("disabled", true);
                var tableParamsId = "#rex-"+ jQuery(this).val();
                currentShown = $(tableParamsId);
                currentShown.show().find(":input").prop("disabled", false);
            }).change();
        })(jQuery);
        //-->
        </script>';

        $fields = [];
        foreach ($supportedTables as $DBID => $databases) {
            $fieldRelationTableSelect->addOptGroup($databases['name']);
            foreach ($databases['tables'] as $table) {
                $mergedTableName = Database::merge('relation_'.$i, $table['name_unique']);
                $fieldRelationTableSelect->addOption($table['name'], $mergedTableName);
                foreach ($table['columns'] as $column) {
                    $fields[$mergedTableName][] = $column['name'];
                }
            }
        }

        $fieldContainer = $form->addContainerField('relation_'.$i.'_table_parameters');
        $fieldContainer->setAttribute('style', 'display: none');
        $fieldContainer->setSuffix($script);
        $fieldContainer->setMultiple(false);
        $fieldContainer->setActive($activeRelationTable);

        if (count($fields) > 0) {
            foreach ($fields as $table => $columns) {
                $group = $table;
                $options = $columns;

                $type = 'select';
                $name = 'column_id';
                /* @var $f rex_form_select_element */
                $f = $fieldContainer->addGroupedField($group, $type, $name);
                $f->setHeader('
                    <hr class="addoff-hr" />
                    <div class="addoff-grid">
                        <div class="addoff-grid-item" data-addoff-size="2">
                            <label>'.$this->i18n('url_generator_identify_record').'</label>
                        </div>
                        <div class="addoff-grid-item" data-addoff-size="3">');
                $f->setFooter('
                        </div>');
                $f->setPrefix('<div class="rex-select-style">');
                $f->setSuffix('</div>');
                $f->setAttribute('disabled', 'true');
                $f->setNotice($this->i18n('url_generator_id_notice'));
                $select = $f->getSelect();
                $select->addOptions($options, true);

                if (count(rex_clang::getAll()) >= 2) {
                    $type = 'select';
                    $name = 'column_clang_id';
                    /* @var $f rex_form_select_element */
                    $f = $fieldContainer->addGroupedField($group, $type, $name);
                    $f->setHeader('
                            <div class="addoff-grid-item" data-addoff-size="3">');
                    $f->setFooter('
                            </div>
                        </div>');
                    $f->setPrefix('<div class="rex-select-style">');
                    $f->setSuffix('</div>');
                    $f->setAttribute('disabled', 'true');
                    $f->setNotice($this->i18n('url_generator_clang_id_notice'));
                    $select = $f->getSelect();
                    $select->addOption($this->i18n('url_generator_no_clang_id'), '');
                    $select->addOptions($options, true);
                } else {
                    $f->setFooter('
                        </div>');

                    $type = 'hidden';
                    $name = 'column_clang_id';
                    $f = $fieldContainer->addGroupedField($group, $type, $name, '');
                }

                for ($j = 1; $j <= Profile::SEGMENT_PART_COUNT; ++$j) {
                    if ($j > 1) {
                        $type = 'select';
                        $name = 'column_segment_part_'.$j.'_separator';
                        /* @var $f rex_form_select_element */
                        $f = $fieldContainer->addGroupedField($group, $type, $name);
                        $f->setHeader('<div class="addoff-grid-item text-center text-large" data-addoff-size="1">');
                        $f->setFooter('</div>');
                        $f->setPrefix('<div class="rex-select-style">');
                        $f->setSuffix('</div>');
                        $f->setAttribute('disabled', 'true');
                        $select = $f->getSelect();
                        $select->addOptions(UrlManager::getSegmentPartSeparators());
                    }

                    $type = 'select';
                    $name = 'column_segment_part_'.$j;
                    /* @var $f rex_form_select_element */
                    $f = $fieldContainer->addGroupedField($group, $type, $name);

                    // $prependHeader = '<div class="addoff-grid-item text-center text-large" data-addoff-size="1"><b>/</b></div>';
                    $prependHeader = '';
                    if ($j == 1) {
                        $prependHeader = '
                        <hr class="addoff-hr" />
                        <div class="addoff-grid">
                            <div class="addoff-grid-item" data-addoff-size="2">
                                <label>'.$this->i18n('url').'</label>
                                <p class="help-block">'.$this->i18n('url_generator_url_notice').'</p>
                            </div>
                        ';
                    }
                    $f->setHeader($prependHeader.'
                            <div class="addoff-grid-item" data-addoff-size="2">');

                    $appendFooter = ($j == Profile::SEGMENT_PART_COUNT) ? '</div>' : '';
                    $f->setFooter('
                            </div>'.$appendFooter);
                    $f->setPrefix('<div class="rex-select-style">');
                    $f->setSuffix('</div>');
                    $f->setAttribute('disabled', 'true');
                    $select = $f->getSelect();
                    if ($j > 1) {
                        $select->addOption($this->i18n('url_generator_no_additive'), '');
                    }
                    $select->addOptions($options, true);
                }
            }
        }

        $form->addRawField('</fieldset></div>');
    }

    $content = $form->get();

    $fragment = new rex_fragment();
    $fragment->setVar('class', 'edit url-container', false);
    $fragment->setVar('title', $title);
    $fragment->setVar('body', $content, false);
    $content = $fragment->parse('core/page/section.php');

    echo $content;
}

if ($func == 'add' || $func == 'edit') {
    for ($i = 1; $i <= Profile::RELATION_COUNT; ++$i) {
        ?>
    <script type="text/javascript">
        (function($) {
            var $currentShownRelationSection = $(".js-change-relation-<?= $i ?>-container");
            $currentShownRelationSection.hide();
            $(".js-change-relation-<?= $i ?>-select select").change(function(){
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
}
?>

<style>
    @media (min-width: 75em) {
        .addoff-grid {
            display: -webkit-box;
            display: -ms-flexbox;
            display: flex;
            flex-wrap: wrap;
            flex-direction: row;
            margin-left: -15px;
            margin-right: -15px;
        }
        .addoff-grid-item {
            padding: 0 15px;
        }
        [data-addoff-size="1"] {
            width: 8.33333%;
        }
        [data-addoff-size="2"] {
            width: 16.66667%;
        }
        [data-addoff-size="3"] {
            width: 25%;
        }
        [data-addoff-size="4"] {
            width: 33.33333%;
        }
        [data-addoff-size="5"] {
            width: 41.66667%;
        }
        [data-addoff-size="6"] {
            width: 50%;
        }
        [data-addoff-size="7"] {
            width: 58.33333%;
        }
        [data-addoff-size="8"] {
            width: 66.66667%;
        }
        [data-addoff-size="9"] {
            width: 75%;
        }
        [data-addoff-size="10"] {
            width: 83.33333%;
        }
        [data-addoff-size="11"] {
            width: 91.66667%;
        }
        [data-addoff-size="12"] {
            width: 100%;
        }
        [data-addoff-size="1of10"] {
            width: 10%;
        }
        [data-addoff-size="2of10"] {
            width: 20%;
        }
        [data-addoff-size="3of10"] {
            width: 30%;
        }
        [data-addoff-shift="1"] {
            margin-left: 8.33333%;
        }
        [data-addoff-shift="2"] {
            margin-left: 16.66667%;
        }
        [data-addoff-shift="3"] {
            margin-left: 25%;
        }
        [data-addoff-shift="4"] {
            margin-left: 33.33333%;
        }
        [data-addoff-shift="5"] {
            margin-left: 41.66667%;
        }
        [data-addoff-shift="6"] {
            margin-left: 50%;
        }
        [data-addoff-shift="7"] {
            margin-left: 58.33333%;
        }
        [data-addoff-shift="8"] {
            margin-left: 66.66667%;
        }
        [data-addoff-shift="9"] {
            margin-left: 75%;
        }
        [data-addoff-shift="10"] {
            margin-left: 83.33333%;
        }
        [data-addoff-shift="11"] {
            margin-left: 91.66667%;
        }
        [data-addoff-shift="12"] {
            margin-left: 100%;
        }
        [data-addoff-shift="-1"] {
            margin-left: -8.33333%;
        }
        [data-addoff-shift="-2"] {
            margin-left: -16.66667%;
        }
        [data-addoff-shift="-3"] {
            margin-left: -25%;
        }
        [data-addoff-shift="-4"] {
            margin-left: -33.33333%;
        }
        [data-addoff-shift="-5"] {
            margin-left: -41.66667%;
        }
        [data-addoff-shift="-6"] {
            margin-left: -50%;
        }
        [data-addoff-shift="-7"] {
            margin-left: -58.33333%;
        }
        [data-addoff-shift="-8"] {
            margin-left: -66.66667%;
        }
        [data-addoff-shift="-9"] {
            margin-left: -75%;
        }
        [data-addoff-shift="-10"] {
            margin-left: -83.33333%;
        }
        [data-addoff-shift="-11"] {
            margin-left: -91.66667%;
        }
        [data-addoff-shift="-12"] {
            margin-left: -100%;
        }
        [data-addoff-shift="1of10"] {
            margin-left: 10%;
        }
        [data-addoff-shift="2of10"] {
            margin-left: 20%;
        }

        [data-addoff-grid="8-4"] > .addoff-grid-item:nth-child(1) {
            width: 66.66667%;
        }

        [data-addoff-grid="8-4"] > .addoff-grid-item:nth-child(2) {
            width: 33.33333%;
        }

        [data-addoff-grid="6-6"] > .addoff-grid-item {
            width: 50%;
        }

        [data-addoff-grid="6-3-3"] > .addoff-grid-item:nth-child(1) {
            width: 50%;
        }

        [data-addoff-grid="6-3-3"] > .addoff-grid-item:nth-child(2),
        [data-addoff-grid="6-3-3"] > .addoff-grid-item:nth-child(3) {
            width: 25%;
        }

        [data-addoff-grid="4-8"] > .addoff-grid-item:nth-child(1) {
            width: 33.33333%;
        }

        [data-addoff-grid="4-8"] > .addoff-grid-item:nth-child(2) {
            width: 66.66667%;
        }

        [data-addoff-grid="4-4-4"] > .addoff-grid-item {
            width: 33.33333%;
        }

        [data-addoff-grid="3-6-3"] > .addoff-grid-item:nth-child(2) {
            width: 50%;
        }

        [data-addoff-grid="3-6-3"] > .addoff-grid-item:nth-child(1),
        [data-addoff-grid="3-6-3"] > .addoff-grid-item:nth-child(3) {
            width: 25%;
        }

        [data-addoff-grid="3-3-6"] > .addoff-grid-item:nth-child(3) {
            width: 50%;
        }

        [data-addoff-grid="3-3-6"] > .addoff-grid-item:nth-child(1),
        [data-addoff-grid="3-3-6"] > .addoff-grid-item:nth-child(2) {
            width: 25%;
        }

        [data-addoff-grid="3-3-3-3"] > .addoff-grid-item {
            width: 25%;
        }
    }

    .addoff-grid-item .rex-form-group:not(.rex-form-group-vertical) > dd:first-child {
        padding-left: 0;
    }

    .addoff-form-vertical .rex-form-group dt,
    .addoff-form-vertical .rex-form-group dd {
        display: block;
        width: 100%;
    }

    .addoff-hr {
        margin-top: 10px;
        border-color: #3bb594;
        opacity: .5;
    }

    .text-large {
        font-size: 150%;
    }
</style>

<!--<style>-->
<!--    .form-group > dd .rex-select-style select.form-control {-->
<!--        margin-top: 0;-->
<!--        margin-bottom: 0;-->
<!--        padding-top: 7px;-->
<!--        padding-bottom: 7px;-->
<!--    }-->
<!--    .url-container .input-group,-->
<!--    .url-container .rex-select-style,-->
<!--    .url-container .url-grid-item .form-control {-->
<!--        width: 300px;-->
<!--    }-->
<!--    .url-container .input-group .form-control {-->
<!--        width: 100%;-->
<!--    }-->
<!--    .url-container .help-block {-->
<!--        color: #324050;-->
<!--        font-size: 90%;-->
<!--    }-->
<!--    .url-container .url-grid-item-small .rex-select-style,-->
<!--    .url-container .url-grid-item-small .rex-form-group,-->
<!--    .url-container .url-grid-item-small .form-control {-->
<!--        width: 135px;-->
<!--    }-->
<!--    .url-grid {-->
<!--        margin-left: -15px;-->
<!--        margin-right: -15px;-->
<!--    }-->
<!--    .url-grid:before,-->
<!--    .url-grid:after {-->
<!--        content: '';-->
<!--        display: table;-->
<!--    }-->
<!--    .url-grid:after {-->
<!--        clear: both;-->
<!--    }-->
<!--    .url-grid > .help-block {-->
<!--        clear: both;-->
<!--        position: relative;-->
<!--        top: -10px;-->
<!--    }-->
<!--    .url-grid-item {-->
<!--        position: relative;-->
<!--        float: left;-->
<!--        min-height: 1px;-->
<!--        padding-left: 15px;-->
<!--        padding-right: 15px;-->
<!--    }-->
<!--    .url-grid-item > .rex-form-group {-->
<!--        display: block;-->
<!--        width: auto;-->
<!--    }-->
<!--    .url-grid-item > .rex-form-group > * {-->
<!--        display: block;-->
<!--    }-->
<!--    .url-grid-item > .rex-form-group > dd:first-child {-->
<!--        padding-left: 0;-->
<!--    }-->
<!--    .url-grid-item + .url-grid-item > .rex-form-group > dt {-->
<!--        width: 330px;-->
<!--        text-align: right;-->
<!--    }-->
<!--    @media (min-width: 992px) {-->
<!--        .url-grid > .help-block {-->
<!--            padding-left: 195px-->
<!--        }-->
<!--    }-->
<!--    @media (min-width: 1200px) {-->
<!--        .url-grid > .help-block {-->
<!--            padding-left: 225px-->
<!--        }-->
<!--    }-->
<!--    @media (min-width: 1400px) {-->
<!--        .url-grid > .help-block {-->
<!--            padding-left: 315px-->
<!--        }-->
<!--    }-->
<!---->
<!--    .url-dl > dt {-->
<!--        clear: left;-->
<!--        float: left;-->
<!--        margin-bottom: 4px;-->
<!--        font-size: 90%;-->
<!--        font-weight: 700;-->
<!--    }-->
<!--    .url-dl > dd {-->
<!--        margin-left: 110px;-->
<!--        margin-bottom: 4px;-->
<!--    }-->
<!--    .url-hr {-->
<!--        border-top-color: #c1c9d4;-->
<!--    }-->
<!--</style>-->
