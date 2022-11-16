<?php

$this->includeFile(__DIR__.'/install.php');

if (rex_string::versionCompare(\rex_addon::get('url')->getVersion(), '1.0.1', '<=')) {
    // Upgrade tables form version 1.x to 2.x
    $result = \rex_sql::factory();
	// In case update to 2.0.1 failed due to duplicate namespace, article and clang combination: truncate tables
    $result->setQuery('TRUNCATE '.\rex::getTable('url_generator_profile'));
    $result->setQuery('TRUNCATE '.\rex::getTable('url_generator_url'));

	// Read old profiles
    $result->setQuery('SELECT * FROM '.\rex::getTable('url_generate'));

	$namespace_keys = [];
    for ($i = 0; $i < $result->getRows(); $i++) {
        $table_parameters = json_decode($result->getValue('table_parameters'), true);
        $table_first_key = key($table_parameters);
        $table_db_id = substr($table_first_key, 0, strpos($table_first_key, '_xxx_'));
        $table_key = substr($table_first_key, strlen($table_db_id) + 5, -8);
        $relation_table_parameters = json_decode($result->getValue('relation_table_parameters'), true);
        $relation_first_key = key($relation_table_parameters);
        $relation_db_id = substr($relation_first_key, 0, strpos($relation_first_key, '_xxx_'));
        $relation_key = substr($relation_first_key, strlen($relation_db_id) + 14, -8);
        $namespace = $table_parameters[$table_db_id.'_xxx_'.$table_key.'_url_param_key'] ?: $table_parameters[$table_db_id.'_xxx_'.$table_key.'_id'];
        $namespace_key = $namespace .'-'. $result->getValue('article_id') .'-'. $result->getValue('clang_id');

		if(!in_array($namespace_key, $namespace_keys)) {
		    $sql = \rex_sql::factory();
		    $sql->setTable(\rex::getTable('url_generator_profile'));
		    $sql->setValue('namespace', $namespace);
		    $sql->setValue('article_id', $result->getValue('article_id'));
		    $sql->setValue('clang_id', $result->getValue('clang_id'));
		    $sql->setValue('table_name', $result->getValue('table'));
		    $sql->setValue('table_parameters', '{"column_id":"'.$table_parameters[$table_db_id.'_xxx_'.$table_key.'_id'].'","column_clang_id":"'.$table_parameters[$table_db_id.'_xxx_'.$table_key.'_clang_id'].'","restriction_1_column":"'.$table_parameters[$table_db_id.'_xxx_'.$table_key.'_restriction_field'].'","restriction_1_comparison_operator":"'.$table_parameters[$table_db_id.'_xxx_'.$table_key.'_restriction_operator'].'","restriction_1_value":"'.$table_parameters[$table_db_id.'_xxx_'.$table_key.'_restriction_value'].'","restriction_2_logical_operator":"","restriction_2_column":"","restriction_2_comparison_operator":"=","restriction_2_value":"","restriction_3_logical_operator":"","restriction_3_column":"","restriction_3_comparison_operator":"=","restriction_3_value":"","column_segment_part_1":"'.$table_parameters[$table_db_id.'_xxx_'.$table_key.'_field_1'].'","column_segment_part_2_separator":"-","column_segment_part_2":"'.$table_parameters[$table_db_id.'_xxx_'.$table_key.'_field_2'].'","column_segment_part_3_separator":"-","column_segment_part_3":"'.$table_parameters[$table_db_id.'_xxx_'.$table_key.'_field_3'].'","relation_1_column":"'.$table_parameters[$table_db_id.'_xxx_'.$table_key.'_relation_field'].'","relation_1_position":"'.strtoupper($result->getValue('relation_insert')).'","relation_2_column":"","relation_2_position":"BEFORE","relation_3_column":"\",\"relation_3_position\":\"BEFORE\",\"append_user_paths\":\"'.$table_parameters[$table_db_id.'_xxx_'.$table_key.'_path_names']."\",\"append_structure_categories\":\"".$table_parameters[$table_db_id.'_xxx_'.$table_key.'_path_categories']."\",\"column_seo_title\":\"".$table_parameters[$table_db_id.'_xxx_'.$table_key.'_seo_title']."\",\"column_seo_description\":\"".$table_parameters[$table_db_id.'_xxx_'.$table_key.'_seo_description']."\",\"column_seo_image\":\"".$table_parameters[$table_db_id.'_xxx_'.$table_key.'_seo_image']."\",\"sitemap_add\":\"".$table_parameters[$table_db_id.'_xxx_'.$table_key.'_sitemap_add']."\",\"sitemap_frequency\":\"".$table_parameters[$table_db_id.'_xxx_'.$table_key.'_sitemap_frequency'].'","sitemap_priority":"'.$table_parameters[$table_db_id.'_xxx_'.$table_key.'_sitemap_priority'].'","column_sitemap_lastmod":"'.$table_parameters[$table_db_id.'_xxx_'.$table_key.'_sitemap_lastmod'].'"}');
		    $sql->setValue('relation_1_table_name', ($result->getValue('relation_table') == '' ? '' : 'relation_1_xxx_'.$relation_db_id.'_xxx_'.substr($result->getValue('relation_table'), 15)));
		    $sql->setValue('relation_1_table_parameters', $result->getValue('relation_table') == '' ? '[]' : '{"column_id":"'.$relation_table_parameters[$relation_db_id.'_xxx_relation_'.$relation_key.'_id'].'","column_clang_id":"'.$relation_table_parameters[$relation_db_id.'_xxx_relation_'.$relation_key.'_clang_id'].'","column_segment_part_1":"'.$relation_table_parameters[$relation_db_id.'_xxx_relation_'.$relation_key.'_field_1'].'","column_segment_part_2_separator":"-","column_segment_part_2":"'.$relation_table_parameters[$relation_db_id.'_xxx_relation_'.$relation_key.'_field_2'].'","column_segment_part_3_separator":"-","column_segment_part_3":"'.$relation_table_parameters[$relation_db_id.'_xxx_relation_'.$relation_key.'_field_3'].'"}');
		    $sql->setValue('relation_2_table_name', '');
		    $sql->setValue('relation_2_table_parameters', '[]');
		    $sql->setValue('relation_3_table_name', '');
		    $sql->setValue('relation_3_table_parameters', '[]');
		    $sql->setValue('createdate', date('Y-m-d H:i:s', $result->getValue('createdate')));
		    $sql->setValue('createuser', $result->getValue('createuser'));
		    $sql->setValue('updatedate', date('Y-m-d H:i:s', $result->getValue('updatedate')));
		    $sql->setValue('updateuser', $result->getValue('updateuser'));
		    $sql->insert();

			$namespace_keys[] = $namespace_key;
		}
		else {
			// In case update to 2.0.1 failed due to duplicate namespace, article and clang combination: inform user
			rex_view::warning(rex_i18n::hasMsg('url_update_duplicate_namespace') ? rex_i18n::msg('url_update_duplicate_namespace', $namespace, $result->getValue('article_id'), $result->getValue('clang_id')) :
					'Profile with combination of namespace "'. $namespace .'", article id "'. $result->getValue('article_id')
					.'" and language id "'. $result->getValue('clang_id') .'" existed twice, but only one was imported. '
					.'Please check your <a href="index.php?page=url/generator/profiles">profiles</a>');
		}
        $result->next();
    }

    $result->setQuery('DROP TABLE '.\rex::getTable('url_generate'));

    rex_delete_cache();
}
