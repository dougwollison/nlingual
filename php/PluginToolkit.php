<?php
/**
 * PluginToolkit class
 *
 * Base class for use by plugin API classes.
 */
abstract class PluginToolkit{
	/**
	 * Create a table if it doesn't already exist, and additionally
	 * add any columns that don't yet exist.
	 *
	 * Note: Planning to add checking of actual column configuration,
	 * in order to update it to match any changes.
	 *
	 * @global wpdb $wpdb The database abstraction class instance.
	 *
	 * @param string $name The name of the table
	 * @param array $data {
	 *		The configuration data for the table
	 *
	 *		@type array  $columns       The columns to add to the table (name=>config format).
	 *		@type string $primary_key   The name of the column for the PRIMARY key (optional).
	 *		@type array  $unique_keys   The UNIQUE keys to add (optional; name=>config format).
	 *		@type array  $index_keys    The INDEX keys to add (optional; name=>config format).
	 *		@type array  $spatial_keys  The SPATIAL keys to add (optional; name=>config format).
	 *		@type array  $fulltext_keys The FULLTEXT keys to add (optional; name=>config format).
	 * }
	 */
	static function makeTable($name, $data){
		global $wpdb;
		
		extract($data, EXTR_SKIP);
		
		// Check if table exists, create if not.
		if(!$wpdb->get_var("SHOW TABLES LIKE '$name'")){
			$cols = array();
			foreach($columns as $col => $config){
				$cols[] = "`$col` $config";
			}
			
			$cols = implode(',', $cols);
			
			$keys = array();
			if(isset($primary_key)){
				$keys[] = "PRIMARY KEY (`$primary_key`)";
			}
			
			foreach(array('UNIQUE', 'INDEX', 'SPATIAL', 'FULLTEXT') as $keytype){
				$keyset = strtolower($keytype)."_keys";
				if(isset($data[$keyset])){
					foreach($$keyset as $key => $columns){
						$columns = '`'.implode('`,`', (array) $columns).'`';
						$keys[] = "$keytype KEY `$key` ($columns)";
					}
				}
			}
			
			$keys = implode(',', $keys);
			
			$wpdb->query("CREATE TABLE `$name` ($cols, $keys)");
			
			return;
		}
		
		// Check that all the columns exist, add them if not
		$_columns = array_keys($columns); // The column names
		$i = 0;
		foreach($columns as $col => $config){
			if(!$wpdb->query($wpdb->prepare("
				SELECT TABLE_CATALOG
				FROM information_schema.COLUMNS
				WHERE TABLE_SCHEMA = %s
				AND TABLE_NAME = %s
				AND COLUMN_NAME = %s
			", DB_NAME, $name, $col))){
				$pos = $i == 0 ? 'FIRST' : 'AFTER '.$_columns[$i-1];
				$wpdb->query("ALTER TABLE `$name` ADD COLUMN `$col` $config $pos");
			}
			$i++;
		}
	}
}