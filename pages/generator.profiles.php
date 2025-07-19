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
use Url\Profile;
use Url\Url;
use Url\UrlManager;
use Url\UrlManagerSql;

/** @var rex_addon $this */

$id = rex_request('id', 'int');
$func = rex_request('func', 'string');
$action = rex_request('action', 'string');
$message = '';

if ($action === 'cache') {
    Cache::deleteProfiles();
}

$a = [];

if ($func === 'delete' && $id > 0) {
    if (!rex_csrf_token::factory('url_profile_delete')->isValid()) {
        $message = rex_view::error(rex_i18n::msg('csrf_token_invalid'));
    } else {
        $profile = Profile::get($id);
        if ($profile !== null) {
            $profile->deleteUrls();

            $sql = rex_sql::factory()
                ->setTable(rex::getTable(Profile::TABLE_NAME))
                ->setWhere('id = :id', ['id' => $id]);
            $sql->delete();
            $message .= rex_view::success(rex_i18n::msg('url_generator_profile_removed'));
            Cache::deleteProfiles();
        }
    }
    $func = '';
}

if (($func === 'refresh' && $id > 0) || $func === 'refresh_all') {
    if (!rex_csrf_token::factory('url_profile_refresh')->isValid()) {
        $message = rex_view::error(rex_i18n::msg('csrf_token_invalid'));
    } else {
        switch ($func) {
            case 'refresh':
                $profile = Profile::get($id);
                if ($profile !== null) {
                    $profile->deleteUrls();
                    $profile->buildUrls();
                    $message .= rex_view::success(rex_i18n::msg('url_generator_url_refreshed', $id));
                }
                break;
            case 'refresh_all':
                UrlManagerSql::deleteAll();
                $profiles = Profile::getAll();
                if (count($profiles) > 0) {
                    foreach ($profiles as $profile) {
                        $profile->buildUrls();
                        $message .= rex_view::success(rex_i18n::msg('url_generator_url_refreshed', $profile->getId()));
                    }
                }
                break;
        }
    }
    $func = '';
}

if ($message !== '') {
    echo $message;
}

if (!function_exists('url_generate_column_data')) {
    /**
     * @param array $params
     * @return string
     */
    function url_generate_column_data(array $params): string
    {
        /** @var rex_list $list */
        $list = $params['list'];

        $profile = Profile::get($list->getValue('id'));

        if ($profile === null) {
            return '<div class="alert alert-warning">Profil nicht gefunden.</div>';
        }

        $articleList = [];
        $dataList = [];
        $infoList = [
            rex_i18n::msg('url_generator_restrictions') => $profile->hasRestrictions(),
            rex_i18n::msg('url_generator_relations') => $profile->hasRelations(),
            rex_i18n::msg('url_generator_append_user_path_short') => $profile->appendUserPaths(),
            rex_i18n::msg('url_generator_append_structure_categories_append') => $profile->appendStructureCategories(),
            rex_i18n::msg('url_generator_sitemap') => $profile->inSitemap(),
            rex_i18n::msg('url_generator_pre_save_called') => $profile->hasPreSaveCalled(),
        ];

        $articleList[] = [
            rex_i18n::msg('url_generator_namespace'),
            sprintf('<code>%s</code>', $profile->getNamespace())
        ];

        $article = rex_article::get($profile->getArticleId(), $profile->getArticleClangId());
        if ($article !== null) {
            $articleList[] = [
                rex_i18n::msg('url_generator_domain'),
                \rex_yrewrite::getDomainByArticleId($article->getId(), $article->getClangId()),
            ];
            $articleList[] = [
                rex_i18n::msg('url_generator_article'),
                sprintf('%s<br /><a href="%s">[Backend]</a> | <a href="%s">[Frontend]</a>', $article->getName(), rex_url::backendPage('/content/edit', ['category_id' => $article->getCategoryId(), 'article_id' => $article->getId(), 'clang' => $article->getClangId(), 'mode' => 'edit']), $article->getUrl())
            ];
            $articleList[] = [
                rex_i18n::msg('url_generator_clang'),
                (null === $profile->getArticleClangId() ? '' : rex_clang::get($profile->getArticleClangId())->getName())
            ];

            $levels = [];
            if (null !== $profile->getArticleClangId() && count(rex_clang::getAll()) >= 2) {
                $levels[] = rex_clang::get($article->getClangId())->getName();
            }

            $tree = $article->getParentTree();
            foreach ($tree as $object) {
                $levels[] = sprintf('<a href="%s">%s</a>', $object->getUrl(), $object->getName());
            }

            $articleList[] = [
                rex_i18n::msg('url_generator_path'),
                implode(' : ', $levels),
            ];
        }

        $dataList[] = [
            rex_i18n::msg('url_generator_table'),
            sprintf('<code>%s</code>', $profile->getTableName())
        ];

        $dataList[] = [
            rex_i18n::msg('url_generator_identify_record'),
            '<code>'.$profile->getColumnName('id').'</code>' . ($profile->getColumnName('clang_id') === '' ? '' : ' - <code>'.$profile->getColumnName('clang_id').'</code>')
        ];

        $concatSegmentParts = '';
        for ($index = 1; $index <= Profile::SEGMENT_PART_COUNT; ++$index) {
            if ($profile->getColumnName('segment_part_'.$index) !== '') {
                $concatSegmentParts .= $profile->getSegmentPartSeparators()[$index] ?? '';
                $concatSegmentParts .= '<code>'.$profile->getColumnName('segment_part_'.$index).'</code>';
            }
        }

        $append = '';
        $prepend = '';
        if ($profile->hasRelations() > 0) {
            foreach ($profile->getRelations() as $relation) {
                $concatSegmentPartsRelation = '';
                for ($index = 1; $index <= Profile::SEGMENT_PART_COUNT; ++$index) {
                    if ($relation->getColumnName('segment_part_'.$index) !== '') {
                        $concatSegmentPartsRelation .= $relation->getSegmentPartSeparators()[$index] ?? '';
                        $concatSegmentPartsRelation .= '<code>'.$relation->getColumnNameWithAlias('segment_part_'.$index).'</code>';
                    }
                }
                if ($relation->getSegmentPosition() === 'BEFORE') {
                    $prepend .= $concatSegmentPartsRelation;
                } else {
                    $append .= $concatSegmentPartsRelation;
                }
            }
        }
        $concatSegmentParts = $prepend.$concatSegmentParts.$append;

        $url = new Url(Url::getRewriter()->getFullUrl($list->getValue('article_id'), $list->getValue('clang_id')));
        $url->withScheme('');

        $segments = $url->getSegments();
        $lastKey = array_key_last($segments);
        if (isset($segments[$lastKey]) && $segments[$lastKey] === Url::getRewriter()->getSuffix()) {
            unset($segments[$lastKey]);
        }

        $dataList[] = [
            rex_i18n::msg('url_generator_url'),
            implode('/', $segments).'/'.$concatSegmentParts.Url::getRewriter()->getSuffix()
        ];

        $dataList[] = [
            implode('<br />'.str_repeat('&nbsp;', 8), [rex_i18n::msg('url_generator_namespace_short'), rex_i18n::msg('url_function'), rex_i18n::msg('url_method')]),
            '<br /><code>rex_getUrl(\'\', \'\', [\''.$profile->getNamespace().'\' => {id}])</code><br /><code>->getUrl([\''.$profile->getNamespace().'\' => {id}])</code>'
        ];

        $dataList[] = [
            '',
            implode('', array_map(function($label, $value) {
                return sprintf('<span class="label %s">%s</span> ', ($value ? 'label-success' : 'label-default'), $label);
            }, array_keys($infoList), array_values($infoList)))
        ];

        $articleOut = '<table class="addoff-data-table table table-condensed small"><tbody>';
        foreach ($articleList as $data) {
            $articleOut .= sprintf('<tr><th>%s</th><td>%s</td></tr>', $data[0], $data[1]);
        }
        $articleOut .= '</tbody></table>';

        $dataOut = '<table class="addoff-data-table table table-condensed small"><tbody>';
        foreach ($dataList as $data) {
            $dataOut .= sprintf('<tr><th>%s</th><td class="rex-word-break">%s</td></tr>', $data[0], $data[1]);
        }
        $dataOut .= '</tbody></table>';

        $return = sprintf(
            '<div class="row">
                <div class="col-lg-6">%s</div>
                <div class="col-lg-6">%s</div>
            </div>',
            $articleOut,
            $dataOut
        );

        return $return;
    }
}
if ($func === '') {
    $query = '  SELECT      `id`,
                            `article_id`,
                            `clang_id`
                FROM        '.rex::getTable('url_generator_profile').'
                ORDER BY    `namespace`';

    $list = rex_list::factory($query);
    $list->addTableAttribute('class', 'addoff-table table-striped');

    $tdIcon = '<i class="rex-icon fa fa-gears"></i>';
    $thIcon = '<a href="'.$list->getUrl(['func' => 'add']).'"'.rex::getAccesskey($this->i18n('add'), 'add').'><i class="rex-icon rex-icon-add-article"></i></a>';
    $list->addColumn($thIcon, $tdIcon, 0, ['<th class="rex-table-icon">###VALUE###</th>', '<td class="rex-table-icon">###VALUE###</td>']);
    $list->setColumnParams($thIcon, ['func' => 'edit', 'id' => '###id###']);

    $list->setColumnLabel('id', rex_i18n::msg('id'));
    $list->setColumnLayout('id', ['<th class="rex-table-id">###VALUE###</th>', '<td class="rex-table-id" data-title="'.rex_i18n::msg('id').'">###VALUE###</td>']);

    $list->removeColumn('id');
    $list->removeColumn('clang_id');
    $list->removeColumn('article_id');

    $list->addColumn('data', '');
    $list->setColumnLabel('data', $this->i18n('url_generator_data'));
    $list->setColumnFormat('data', 'custom', 'url_generate_column_data');

    $list->addColumn('refresh', '<i class="rex-icon rex-icon-refresh"></i> '.$this->i18n('url_generator_url_refresh'));
    $list->setColumnLabel('refresh', $this->i18n('function'));
    $list->setColumnLayout('refresh', ['<th class="rex-table-action" colspan="3">###VALUE###</th>', '<td class="rex-table-action">###VALUE###</td>']);
    $list->setColumnParams('refresh', ['func' => 'refresh', 'id' => '###id###'] + rex_csrf_token::factory('url_profile_refresh')->getUrlParams());
    $list->addLinkAttribute('refresh', 'data-confirm', rex_i18n::msg('url_generator_url_refresh') . ' ?');
    $list->addLinkAttribute('refresh', 'class', 'label label-primary');

    $list->addColumn('edit', '<i class="rex-icon rex-icon-edit"></i> '.$this->i18n('edit'));
    $list->setColumnLayout('edit', ['', '<td class="rex-table-action">###VALUE###</td>']);
    $list->setColumnParams('edit', ['func' => 'edit', 'id' => '###id###']);

    $list->addColumn('delete', '<i class="rex-icon rex-icon-delete"></i> '.$this->i18n('delete'));
    $list->setColumnLayout('delete', ['', '<td class="rex-table-action">###VALUE###</td>']);
    $list->setColumnParams('delete', ['func' => 'delete', 'id' => '###id###'] + rex_csrf_token::factory('url_profile_delete')->getUrlParams());
    $list->addLinkAttribute('delete', 'data-confirm', rex_i18n::msg('delete') . ' ?');

    $content = $list->get();

    $fragment = new rex_fragment();
    $fragment->setVar('title', $this->i18n('url_generator_profiles'));
    $fragment->setVar('options', sprintf('<a class="btn btn-xs btn-delete" href="%s">%s</a>', rex_url::currentBackendPage(['func' => 'refresh_all'] + rex_csrf_token::factory('url_profile_refresh')->getUrlParams()), $this->i18n('url_generator_url_refresh_all')), false);
    $fragment->setVar('content', $content, false);
    $content = $fragment->parse('core/page/section.php');

    echo $content;
} elseif ($func === 'add' || $func === 'edit') {
        $title = $func === 'edit' ? $this->i18n('edit') : $this->i18n('add');

    rex_extension::register('REX_FORM_CONTROL_FIELDS', function (rex_extension_point $ep) {
        $controlFields = $ep->getSubject();
        $controlFields['delete'] = '';
        return $controlFields;
    });

    $form = rex_form::factory(rex::getTable('url_generator_profile'), '', 'id = '.$id, 'post', false);
    $form->addParam('id', $id);
    $form->addParam('action', 'cache');
    $form->setApplyUrl(rex_url::currentBackendPage());
    $form->setEditMode($func === 'edit');

    $form->addFieldset($this->i18n('url_generator_article_legend'));
    $form->addErrorMessage(REX_FORM_ERROR_VIOLATE_UNIQUE_KEY, $this->i18n('url_generator_namespace_error'));

    $form->addHiddenField('ep_pre_save_called', '0');

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

    if (count(rex_clang::getAll()) >= 2) {
        $fieldArticleId->setFooter('
                </div>');

        $fieldArticleClangId = $form->addSelectField('clang_id');
        $fieldArticleClangId->setHeader('
                <div class="addoff-grid-item" data-addoff-size="3">');
        $fieldArticleClangId->setFooter('
                </div>
            </div>');
        $fieldArticleClangId->setNotice($this->i18n('url_generator_article_clang').'; '.$this->i18n('url_generator_article_clang_notice', $this->i18n('url_generator_identify_record')));
        $select = $fieldArticleClangId->getSelect();
        $select->addOption($this->i18n('url_generator_article_clang_option_all'), '0');
        foreach (rex_clang::getAll() as $clang) {
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
        $fieldTableSelect->addOptgroup($databases['name']);
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
            /** @var rex_form_select_element $f */

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
            $f->setAttribute('disabled', 'true');
            $f->setNotice($this->i18n('url_generator_id_notice'));
            $select = $f->getSelect();
            $select->addOptions($options, true);

            if (count(rex_clang::getAll()) >= 2) {
                $type = 'select';
                $name = 'column_clang_id';
                /** @var rex_form_select_element $f */

                $f = $fieldContainer->addGroupedField($group, $type, $name);
                $f->setHeader('
                        <div class="addoff-grid-item" data-addoff-size="3">');
                $f->setFooter('
                        </div>
                    </div>');
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
                    /** @var rex_form_select_element $f */

                    $f = $fieldContainer->addGroupedField($group, $type, $name);
                    $f->setHeader('
                                <div class="addoff-grid">
                                    <div class="addoff-grid-item" data-addoff-size="3of10" data-addoff-shift="2of10">');
                    $f->setFooter('
                                    </div>
                                </div>');
                    $f->setAttribute('disabled', 'true');
                    $select = $f->getSelect();
                    $select->addOption('', '');
                    $select->addOptions(Database::getLogicalOperators());
                }

                $type = 'select';
                $name = 'restriction_'.$i.'_column';
                /** @var rex_form_select_element $f */
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
                $f->setAttribute('disabled', 'true');
                $select = $f->getSelect();
                $select->addOption($this->i18n('url_generator_no_restriction'), '');
                $select->addOptions($options, true);

                $type = 'select';
                $name = 'restriction_'.$i.'_comparison_operator';
                /** @var rex_form_select_element $f */

                $f = $fieldContainer->addGroupedField($group, $type, $name);
                $f->setHeader('<div class="addoff-grid-item" data-addoff-size="1of10">');
                $f->setFooter('</div>');
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
                    /** @var rex_form_select_element $f */

                    $f = $fieldContainer->addGroupedField($group, $type, $name);
                    $f->setHeader('<div class="addoff-grid-item text-center addoff-text-large" data-addoff-size="1">');
                    $f->setFooter('</div>');
                    $f->setAttribute('disabled', 'true');
                    $select = $f->getSelect();
                    $select->addOptions(UrlManager::getSegmentPartSeparators());
                }

                $type = 'select';
                $name = 'column_segment_part_'.$i;
                /** @var rex_form_select_element $f */

                $f = $fieldContainer->addGroupedField($group, $type, $name);

                // $prependHeader = '<div class="addoff-grid-item text-center addoff-text-large" data-addoff-size="1"><b>/</b></div>';
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
                /** @var rex_form_select_element $f */

                $f = $fieldContainer->addGroupedField($group, $type, $name);
                $f->setHeader(
                        $prependHeader.'
                            <div class="addoff-grid">
                                <div class="addoff-grid-item" data-addoff-size="1of10"><label>'.$this->i18n('url_generator_relation', $i).'</label></div>
                                <div class="addoff-grid-item" data-addoff-size="2of10">');
                $f->setFooter('
                                </div>');
                $f->setPrefix('<div class="js-change-relation-'.$i.'-select">');
                $f->setSuffix('</div>');
                $f->setAttribute('disabled', 'true');
                // $f->setNotice($this->i18n('url_generator_relation_column_notice'));
                $select = $f->getSelect();
                $select->addOption($this->i18n('url_generator_no_relation_column'), '');
                $select->addOptions($options, true);

                $type = 'select';
                $name = 'relation_'.$i.'_position';
                /** @var rex_form_select_element $f */

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
            /** @var rex_form_select_element $f */

            $f = $fieldContainer->addGroupedField($group, $type, $name);
            $f->setHeader('
                    <div class="addoff-grid-item" data-addoff-size="3">');
            $f->setFooter('
                    </div>
                </div>');
            $f->setPrefix('<label>'.$this->i18n('url_generator_append_structure_categories_append').'</label>');
            $f->setNotice($this->i18n('url_generator_append_structure_categories_notice'));
            $select = $f->getSelect();
            $select->addOptions(['0' => $this->i18n('no'), '1' => $this->i18n('yes')]);

            $type = 'select';
            $name = 'column_seo_title';
            /** @var rex_form_select_element $f */

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
            $f->setAttribute('disabled', 'true');
            $f->setNotice($this->i18n('url_generator_seo_title_notice'));
            $select = $f->getSelect();
            $select->addOption($this->i18n('url_generator_no_selection'), '');
            $select->addOptions($options, true);

            $type = 'select';
            $name = 'column_seo_description';
            /** @var rex_form_select_element $f */

            $f = $fieldContainer->addGroupedField($group, $type, $name);
            $f->setHeader('
                    <div class="addoff-grid-item" data-addoff-size="3">');
            $f->setFooter('
                    </div>');
            $f->setAttribute('disabled', 'true');
            $f->setNotice($this->i18n('url_generator_seo_description_notice'));
            $select = $f->getSelect();
            $select->addOption($this->i18n('url_generator_no_selection'), '');
            $select->addOptions($options, true);

            $type = 'select';
            $name = 'column_seo_image';
            /** @var rex_form_select_element $f */

            $f = $fieldContainer->addGroupedField($group, $type, $name);
            $f->setHeader('
                    <div class="addoff-grid-item" data-addoff-size="3">');
            $f->setFooter('
                    </div>
                </div>');
            $f->setAttribute('disabled', 'true');
            $f->setNotice($this->i18n('url_generator_seo_image_notice'));
            $select = $f->getSelect();
            $select->addOption($this->i18n('url_generator_no_selection'), '');
            $select->addOptions($options, true);

            $type = 'select';
            $name = 'sitemap_add';
            /** @var rex_form_select_element $f */

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
            $f->setAttribute('disabled', 'true');
            $f->setNotice($this->i18n('url_generator_sitemap_add_notice'));
            $select = $f->getSelect();
            $select->addOptions(['0' => $this->i18n('no'), '1' => $this->i18n('yes')]);

            $type = 'select';
            $name = 'sitemap_frequency';
            /** @var rex_form_select_element $f */

            $f = $fieldContainer->addGroupedField($group, $type, $name);
            $f->setHeader('
                    <div class="addoff-grid-item" data-addoff-size="2">');
            $f->setFooter('
                    </div>');
            $f->setAttribute('disabled', 'true');
            $f->setNotice($this->i18n('url_generator_sitemap_frequency_notice'));
            $select = $f->getSelect();
            $select->addOptions(Url::getRewriter()->getSitemapFrequency(), true);

            $type = 'select';
            $name = 'sitemap_priority';
            /** @var rex_form_select_element $f */

            $f = $fieldContainer->addGroupedField($group, $type, $name);
            $f->setHeader('
                    <div class="addoff-grid-item" data-addoff-size="2">');
            $f->setFooter('
                    </div>');
            $f->setAttribute('disabled', 'true');
            $f->setNotice($this->i18n('url_generator_sitemap_priority_notice'));
            $select = $f->getSelect();
            $select->addOptions(Url::getRewriter()->getSitemapPriority(), true);

            $type = 'select';
            $name = 'column_sitemap_lastmod';
            /** @var rex_form_select_element $f */

            $f = $fieldContainer->addGroupedField($group, $type, $name);
            $f->setHeader('
                    <div class="addoff-grid-item" data-addoff-size="3">');
            $f->setFooter('
                    </div>
                </div>');
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
        $fieldRelationTableSelect = $f->getSelect();
        $fieldRelationTableSelect->addOption($this->i18n('url_generator_table_not_selected'), '');

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
            $fieldRelationTableSelect->addOptgroup($databases['name']);
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
                /** @var rex_form_select_element $f */

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
                $f->setAttribute('disabled', 'true');
                $f->setNotice($this->i18n('url_generator_id_notice'));
                $select = $f->getSelect();
                $select->addOptions($options, true);

                if (count(rex_clang::getAll()) >= 2) {
                    $type = 'select';
                    $name = 'column_clang_id';
                    /** @var rex_form_select_element $f */

                    $f = $fieldContainer->addGroupedField($group, $type, $name);
                    $f->setHeader('
                            <div class="addoff-grid-item" data-addoff-size="3">');
                    $f->setFooter('
                            </div>
                        </div>');
                    $f->setAttribute('disabled', 'true');
                    $f->setNotice($this->i18n('url_generator_clang_id_notice'));
                    $select = $f->getSelect();
                    $select->addOption($this->i18n('url_generator_no_clang_id'), '');
                    $select->addOptions($options, true);
                } else {
                    $f->setFooter('
                        </div>
                    </div>');

                    $type = 'hidden';
                    $name = 'column_clang_id';
                    $f = $fieldContainer->addGroupedField($group, $type, $name, '');
                }

                for ($j = 1; $j <= Profile::SEGMENT_PART_COUNT; ++$j) {
                    if ($j > 1) {
                        $type = 'select';
                        $name = 'column_segment_part_'.$j.'_separator';
                        /** @var rex_form_select_element $f */

                        $f = $fieldContainer->addGroupedField($group, $type, $name);
                        $f->setHeader('<div class="addoff-grid-item text-center addoff-text-large" data-addoff-size="1">');
                        $f->setFooter('</div>');
                        $f->setAttribute('disabled', 'true');
                        $select = $f->getSelect();
                        $select->addOptions(UrlManager::getSegmentPartSeparators());
                    }

                    $type = 'select';
                    $name = 'column_segment_part_'.$j;
                    /** @var rex_form_select_element $f */

                    $f = $fieldContainer->addGroupedField($group, $type, $name);

                    // $prependHeader = '<div class="addoff-grid-item text-center addoff-text-large" data-addoff-size="1"><b>/</b></div>';
                    $prependHeader = '';
                    if ($j === 1) {
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

if ($func === 'add' || $func === 'edit') {
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
