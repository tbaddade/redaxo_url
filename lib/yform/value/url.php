<?php

use Url\Profile;
use Url\UrlManagerSql;

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_value_url extends rex_yform_value_abstract
{
    public function enterObject(): void
    {
    }

    public function getDescription(): string
    {
        return 'url|name|label|title|notice';
    }

    /**
     * @return array<string, mixed>
     */
    public function getDefinitions(): array
    {
        return [
            'type' => 'value',
            'name' => 'url',
            'values' => [
                'name' => ['type' => 'name', 'label' => rex_i18n::msg('url_yform_value_name')],
                'label' => ['type' => 'text', 'label' => rex_i18n::msg('url_yform_value_label')],
            ],
            'description' => rex_i18n::msg('url_yform_value_description'),
            'db_type' => ['varchar(191)'],
            'is_searchable' => false,
            'famous' => false,
        ];
    }

    /**
     * @api
     * @param array $params
     * @return string|null
     */
    public static function getListValue(array $params): ?string
    {
        if (!isset($params['params']['field']['table_name'])) {
            return null;
        }

        $table = $params['params']['field']['table_name'];
        $profiles = Profile::getByTableName($table);
        if (count($profiles) === 0) {
            return null;
        }

        $return = [];
        /** @var rex_list $list */
        $list = $params['list'];
        foreach ($profiles as $profile) {
            $manager = UrlManagerSql::factory();
            $manager->setDataId($list->getValue('id'));
            $manager->setProfileId($profile->getId());
            $manager->setStructure(false);
            $manager->setUserPath(false);
            $urls = $manager->fetch();
            if (count($urls) === 1) {
                $return[] = sprintf('<a style="white-space: nowrap;" href="%s" target="_blank"><i class="rex-icon rex-icon-view"></i> %s</a>', $urls[0]['url'], rex_i18n::msg('url_generator_show_dataset', rex_clang::get($urls[0]['clang_id'])->getCode()));
            }
        }

        return implode('<br />', $return);
    }
}
