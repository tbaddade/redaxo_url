<?php

rex_sql_table::get(rex::getTable('url_generate'))
->ensureColumn(new rex_sql_column('relation_table', 'varchar(255)'))
->ensureColumn(new rex_sql_column('relation_table_parameters', 'text'))
->ensureColumn(new rex_sql_column('relation_insert', 'varchar(255)'))
->alter();
