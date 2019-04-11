<?php

$this->includeFile(__DIR__.'/install.php');

if(rex_string::versionCompare(\rex_addon::get('url')->getVersion(), '1.5', '>=')) {
	// Upgrade tables form version 1.x to 2.x
    $result = \rex_sql::factory();
	$result->setQuery("SELECT * FROM ". \rex::getTablePrefix() ."url_generate");
	for($i = 0; $i < $result->getRows(); $i++) {
		$table_parameters = json_decode($result->getValue('table_parameters'), TRUE);
		$table_key = substr(key($table_parameters), 6, -8);
		$table_db_id = substr(key($table_parameters), 0, 1);

		$relation_table_parameters = json_decode($result->getValue('relation_table_parameters'), TRUE);
		$relation_key = substr(key($relation_table_parameters), 15, -8);
		$relation_db_id = substr(key($relation_table_parameters), 0, 1);

		$query = "INSERT INTO ". \rex::getTablePrefix() ."url_generator_profile (`namespace`, `article_id`, `clang_id`, `table_name`, `table_parameters`, `relation_1_table_name`, `relation_1_table_parameters`, `relation_2_table_name`, `relation_2_table_parameters`, `relation_3_table_name`, `relation_3_table_parameters`, `createdate`, `createuser`, `updatedate`, `updateuser`) VALUES
			('". ($table_parameters[$table_db_id .'_xxx_'. $table_key .'_url_param_key'] != "" ? $table_parameters[$table_db_id .'_xxx_'. $table_key .'_url_param_key'] : $table_parameters[$table_db_id .'_xxx_'. $table_key .'_id']) ."', "
			. $result->getValue('article_id') .", "
			. $result->getValue('clang_id') .", "
			. "'". $result->getValue('table') ."', "
			. "'{\"column_id\":\"". $table_parameters[$table_db_id .'_xxx_'. $table_key .'_id'] ."\",\"column_clang_id\":\"". $table_parameters[$table_db_id .'_xxx_'. $table_key .'_clang_id'] ."\",\"restriction_1_column\":\"". $table_parameters[$table_db_id .'_xxx_'. $table_key .'_restriction_field'] ."\",\"restriction_1_comparison_operator\":\"". $table_parameters[$table_db_id .'_xxx_'. $table_key .'_restriction_operator'] ."\",\"restriction_1_value\":\"". $table_parameters[$table_db_id .'_xxx_'. $table_key .'_restriction_value'] ."\",\"restriction_2_logical_operator\":\"\",\"restriction_2_column\":\"\",\"restriction_2_comparison_operator\":\"=\",\"restriction_2_value\":\"\",\"restriction_3_logical_operator\":\"\",\"restriction_3_column\":\"\",\"restriction_3_comparison_operator\":\"=\",\"restriction_3_value\":\"\",\"column_segment_part_1\":\"". $table_parameters[$table_db_id .'_xxx_'. $table_key .'_field_1'] ."\",\"column_segment_part_2_separator\":\"\-\",\"column_segment_part_2\":\"". $table_parameters[$table_db_id .'_xxx_'. $table_key .'_field_2'] ."\",\"column_segment_part_3_separator\":\"\-\",\"column_segment_part_3\":\"". $table_parameters[$table_db_id .'_xxx_'. $table_key .'_field_3'] ."\",\"relation_1_column\":\"". $table_parameters[$table_db_id .'_xxx_'. $table_key .'_relation_field'] ."\",\"relation_1_position\":\"". strtoupper($result->getValue('relation_insert')) ."\",\"relation_2_column\":\"\",\"relation_2_position\":\"BEFORE\",\"relation_3_column\":\"\",\"relation_3_position\":\"BEFORE\",\"append_user_paths\":\"". $table_parameters[$table_db_id .'_xxx_'. $table_key .'_path_names'] ."\",\"append_structure_categories\":\"". $table_parameters[$table_db_id .'_xxx_'. $table_key .'_path_categories'] ."\",\"column_seo_title\":\"". $table_parameters[$table_db_id .'_xxx_'. $table_key .'_seo_title'] ."\",\"column_seo_description\":\"". $table_parameters[$table_db_id .'_xxx_'. $table_key .'_seo_description'] ."\",\"column_seo_image\":\"". $table_parameters[$table_db_id .'_xxx_'. $table_key .'_seo_image'] ."\",\"sitemap_add\":\"". $table_parameters[$table_db_id .'_xxx_'. $table_key .'_sitemap_add'] ."\",\"sitemap_frequency\":\"". $table_parameters[$table_db_id .'_xxx_'. $table_key .'_sitemap_frequency'] ."\",\"sitemap_priority\":\"". $table_parameters[$table_db_id .'_xxx_'. $table_key .'_sitemap_priority'] ."\",\"column_sitemap_lastmod\":\"". $table_parameters[$table_db_id .'_xxx_'. $table_key .'_sitemap_lastmod'] ."\"}', "
			. "'". ($result->getValue('relation_table') == "" ? "" : "relation_1_xxx_". $relation_db_id  ."_xxx_". substr($result->getValue('relation_table'), 15)) ."', "
			.( $result->getValue('relation_table') == "" ?
				"'[]'," :
				 "'{\"column_id\":\"". $relation_table_parameters[$relation_db_id .'_xxx_relation_'. $relation_key .'_id'] ."\",\"column_clang_id\":\"". $relation_table_parameters[$relation_db_id .'_xxx_relation_'. $relation_key .'_clang_id'] ."\",\"column_segment_part_1\":\"". $relation_table_parameters[$relation_db_id .'_xxx_relation_'. $relation_key .'_field_1'] ."\",\"column_segment_part_2_separator\":\"\-\",\"column_segment_part_2\":\"". $relation_table_parameters[$relation_db_id .'_xxx_relation_'. $relation_key .'_field_2'] ."\",\"column_segment_part_3_separator\":\"\-\",\"column_segment_part_3\":\"". $relation_table_parameters[$relation_db_id .'_xxx_relation_'. $relation_key .'_field_3'] ."\"}', "
			)
			. "'', '[]', '', '[]', '". date('Y-m-d H:i:s', $result->getValue('createdate')) ."', '". $result->getValue('createuser') ."', '". date('Y-m-d H:i:s', $result->getValue('updatedate')) ."', '". $result->getValue('updateuser') ."');";
		$sql = \rex_sql::factory();
		$sql->setQuery($query);

		$result->next();
	}
	
    $result->setQuery("DROP TABLE ". \rex::getTablePrefix() ."url_generate;");
    
	rex_delete_cache();
}
