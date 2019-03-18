<?php

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_value_url extends rex_yform_value_abstract
{
    public function enterObject()
    {

    }

    public function getDescription()
    {
        return 'url|name|label|title|notice';
    }

    public function getDefinitions()
    {
        return [
            'type' => 'value',
            'name' => 'url',
            'values' => [
                'name' => ['type' => 'name', 'label' => rex_i18n::msg('url_yform_value_name')],
                'label' => ['type' => 'text', 'label' => rex_i18n::msg('url_yform_value_label')],
                'profil' => ['type' => 'choice', 'label' => rex_i18n::msg('url_yform_value_profil'), 'choices' => "select id AS id, namespace AS name FROM rex_url_generator_profile"],
                'anchor' => ['type' => 'text', 'label' => rex_i18n::msg('url_yform_value_anchor')],
            ],
            'description' => rex_i18n::msg('url_yform_value_description'),
            'db_type' => ['none'],
            'is_searchable' => false,
            'famous' => false
        ];
    }

    public static function getListValue($params)
    {
        $url_profile = array_shift(rex_sql::factory()->getArray('select * FROM rex_url_generator_profile WHERE id = ?', [$params['params']['field']['profil']]));
        $table_parameters = json_decode($url_profile['table_parameters'], true);
        $id = $params['list']->getValue($table_parameters['column_id']);        
        $anchor = $params['params']['field']['anchor'];
        
        return '<a style="white-space: nowrap;" href="'.rex_getUrl(false, false, [$url_profile['namespace'] => $id]).'" title="'.rex_escape($anchor).'" target="_blank"><i class="rex-icon rex-icon-view"></i>&nbsp;'.rex_escape($anchor).'</a>';
    }
}
