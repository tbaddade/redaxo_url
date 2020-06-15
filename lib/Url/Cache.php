<?php

/**
 * This file is part of the Url package.
 *
 * @author (c) Thomas Blum <thomas@addoff.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Url;

class Cache
{
    /**
     * Schreibt Eigenschaften der Profile in die Datei profiles.cache.
     *
     * @throws \rex_exception
     */
    public static function generateProfiles()
    {
        $sql = \rex_sql::factory();
        $sql->setQuery('SELECT * FROM '.\rex::getTable(Profile::TABLE_NAME).' ORDER BY `table_name`');

        $profiles = [];
        /* @var $profile \rex_sql */
        foreach ($sql as $profile) {
            $id = $profile->getValue('id');
            foreach ($sql->getFieldnames() as $fieldName) {
                switch ($fieldName) {
                    case 'createdate':
                    case 'updatedate':
                        $profiles[$id][$fieldName] = $sql->getDateTimeValue($fieldName);
                        break;
                    case 'table_name':
                        $value = $profile->getValue($fieldName);
                        $group = Database::split($value);
                        $profiles[$id]['table']['dbid'] = $group[0];
                        $profiles[$id]['table']['name'] = $group[1];
                        break;
                    case 'table_parameters':
                        $params = json_decode($profile->getValue($fieldName), true);
                        foreach ($params as $key => $value) {
                            switch ($key) {
                                case 'column_id':
                                case 'column_clang_id':
                                case 'column_segment_part_1':
                                case 'column_segment_part_2':
                                case 'column_segment_part_3':
                                case 'column_seo_description':
                                case 'column_seo_image':
                                case 'column_seo_title':
                                case 'column_sitemap_lastmod':
                                    $profiles[$id]['table']['column_names'][substr($key, strlen('column_'))] = $value;
                                    break;
                                case 'relation_1_column':
                                case 'relation_2_column':
                                case 'relation_3_column':
                                case 'restriction_1_column':
                                case 'restriction_2_column':
                                case 'restriction_3_column':
                                    $profiles[$id]['table']['column_names'][substr($key, 0, -strlen('_column'))] = $value;
                                    break;
                                case 'column_segment_part_2_separator':
                                case 'column_segment_part_3_separator':
                                    $index = substr($key, strlen('column_segment_part_'), 1);
                                    $profiles[$id]['segment_part_separators'][$index] = $value;
                                    break;
                                case 'relation_1_position':
                                case 'relation_2_position':
                                case 'relation_3_position':
                                    $index = substr($key, strlen(Profile::RELATION_PREFIX), 1);
                                    $profiles[$id]['table']['relations'][$index][substr($key, strlen(Profile::RELATION_PREFIX.$index.'_'))] = $value;
                                    break;
                                case 'restriction_1_comparison_operator':
                                case 'restriction_1_value':
                                case 'restriction_2_comparison_operator':
                                case 'restriction_2_logical_operator':
                                case 'restriction_2_value':
                                case 'restriction_3_comparison_operator':
                                case 'restriction_3_logical_operator':
                                case 'restriction_3_value':
                                    $index = substr($key, strlen(Profile::RESTRICTION_PREFIX), 1);
                                    $profiles[$id]['table']['restrictions'][$index][substr($key, strlen(Profile::RESTRICTION_PREFIX.$index.'_'))] = $value;
                                    break;
                                case 'append_structure_categories':
                                case 'append_user_paths':
                                case 'sitemap_add':
                                case 'sitemap_frequency':
                                case 'sitemap_priority':
                                    $profiles[$id][$key] = $value;
                                    break;
                            }
                        }
                        break;
                    case 'relation_1_table_name':
                    case 'relation_2_table_name':
                    case 'relation_3_table_name':
                        $value = $profile->getValue($fieldName);
                        if ($value) {
                            $index = substr($fieldName, strlen(Profile::RELATION_PREFIX), 1);
                            $group = Database::split($value);
                            $profiles[$id]['table']['relations'][$index]['table']['dbid'] = $group[1];
                            $profiles[$id]['table']['relations'][$index]['table']['name'] = $group[2];
                        }
                        break;
                    case 'relation_1_table_parameters':
                    case 'relation_2_table_parameters':
                    case 'relation_3_table_parameters':
                        $value = $profile->getValue($fieldName);
                        if ($value && $value != '[]') {
                            $index = substr($fieldName, strlen(Profile::RELATION_PREFIX), 1);
                            $params = json_decode($value, true);
                            foreach ($params as $key => $paramValue) {
                                switch ($key) {
                                    case 'column_id':
                                    case 'column_clang_id':
                                    case 'column_segment_part_1':
                                    case 'column_segment_part_2':
                                    case 'column_segment_part_3':
                                        $profiles[$id]['table']['relations'][$index]['table']['column_names'][substr($key, strlen('column_'))] = $paramValue;
                                        break;
                                    case 'column_segment_part_2_separator':
                                    case 'column_segment_part_3_separator':
                                        $indexSeparator = substr($key, strlen('column_segment_part_'), 1);
                                        $profiles[$id]['table']['relations'][$index]['segment_part_separators'][$indexSeparator] = $paramValue;
                                        break;
                                }
                            }
                        }
                        break;
                    default:
                        $profiles[$id][$fieldName] = $profile->getValue($fieldName);
                        break;
                }
            }
        }

        $file = \rex_path::addonCache('url', 'profiles.cache');
        if (\rex_file::putCache($file, $profiles) === false) {
            throw new \rex_exception('Url Profile cache file could not be generated');
        }
    }

    public static function deleteProfiles()
    {
        Profile::reset();
        
        $file = \rex_path::addonCache('url', 'profiles.cache');
        \rex_file::delete($file);
    }
}
