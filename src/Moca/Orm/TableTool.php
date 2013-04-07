<?php
namespace Moca\Orm;

/**
 * Table tool
 * @author nikis
 *
 */
class TableTool {
	
	const DEFINE_START 	= '/*table*/';
	const DEFINE_END 	= '/*table-end*/';
	
	/**
	 * Generate attributes from sql table
	 */
	public function fromSQL(Table $table) {
		$attrs = array();
		$query = $table->connection()->query('DESCRIBE `'.$table->db().'`.`'.$table->name().'`');
		while($result = $table->connection()->fetch_arr($query)) {
			$attrs[$result['Field']] = $this->toAttr($result);	
		}
		$table->attrs($attrs);
	}
	
	/**
	 * Create table attrs from sql table
	 * @param unknown_type $field
	 * @return array
	 */
	public function toAttr($field) {
		$mainTypes = array(
			'integer' => 'int',
			'bigint' => 'bigint',
			'string' => array('varchar', 'text'),
			'float' => array('float', 'double'),
			'enum' => 'enum',
			'set' => 'set'
		);
		$type = 'string';
		$length = false;
		foreach($mainTypes as $mainType => $map) {
			$map = (array)$map;
			foreach($map as $val) {
				if(substr($field['Type'], 0, strlen($val)) == $val) {
					$type = $mainType;
					if(preg_match('/\(([0-9]+)\)/', $field['Type'], $m)) {
						$length = (int)$m[1];
					} else {
						$length = false;
					}
					break;
				}
			}
		}
		$attrs = array(
			'type' => $type
		);
		if($length) {
			$attrs['length'] = $length;
		}
		if($field['Default'] !== null) {
			$attrs['default'] = $field['Default'];
		}
		if($field['Key'] == 'UNI') {
			$attrs[] = 'unique';
		} else if($field['Key'] == 'PRI') {
			$attrs[] = 'primary';
		}
		if(strstr($field['Extra'], 'auto_increment')) {
			$attrs[] = 'auto_increment';
		}
		
		if($type == 'set' || $type == 'enum') {
			preg_match_all("('(.*?)')", $field['Type'], $m);
			$attrs['values'] = $m[1];
		}
		return $attrs;
	}
	
	/**
	 * Export table as php code
	 * @param unknown_type $ns
	 * @param unknown_type $extend
	 */
	public function toPHP(Table $table, $ns=null,$extend=null) {
		$name = $table->name();
		$use = null;
		if($ns) {
			$temp = explode('_', $name);
			if(count($temp) > 1) {
				$name = end($temp);
				unset($temp[count($temp)-1]);
				
				$use = $ns.'\\'.$extend;
				$ns .= '\\'.implode('\\', $temp);
			}	
		}
		$name = ucfirst($name);
		
		$str = '<?php'."\n";
		if($ns) {
			$str .= 'namespace '.$ns.';'."\n";
		}
		if($use) {
			$str .= 'use '.$use.';'."\n";
		}
		$str .= '
class '.ucfirst($name).' '.($extend ? 'extends '.$extend.' ' : '').'{
	'.($extend ? '' : "\n\t".'public $db = \''.$table->db().'\';').'
	'.self::DEFINE_START.'
	public $name = \''.$table->name().'\';
	public $attributes = array(';
		
	$fields = array();
	foreach($table->attrs() as $name=>$options) {
		$arr = var_export($options, true);
		$arr = str_replace("\n", " ", $arr);
		$arr = preg_replace('/[\s]{1,}/', ' ', $arr);
		$arr = str_replace('array ( ', 'array(', $arr);
		$arr = preg_replace('/[0-9]+ \=\> /', '', $arr);
		$arr = str_replace(', )', ')', $arr);
		$fields[] = "'".$name."' => ".$arr;
	}
	$str .= "\n\t\t".implode(",\n\t\t", $fields);
	$str .= '
	);'."\n\t".self::DEFINE_END."\n\n\t";

		$str .= "\n".'}';	
		return $str;
	}
	
	public function baseClassToPHP($className, $dbName, $ns=null, $providerClassName=null) {
		$str = '<?php
'.($ns ? 'namespace model;'."\n" : '').'
class '.$className.' extends \Table {
	
	public $db = \''.$dbName.'\';
	
	public function connection(IConnector $newConnector=null) {
		return '.$providerClassName.'
	}
}';
		return $str;
	}
	
	/**
	 * Update php content
	 * @param unknown_type $old
	 * @param unknown_type $new
	 */
	public function updateTableContent($old, $new) {
		$start = strpos($old, self::DEFINE_START);
		$end = strpos($old, self::DEFINE_END);
		$old_content = substr($old, $start, $end-$start);
	
		$start = strpos($new, self::DEFINE_START);
		$end = strpos($new, self::DEFINE_END);
		$new_content = substr($new, $start, $end - $start);
		
		return str_replace($old_content, $new_content, $old);
	}
	
	/**
	 * Extend two tables
	 * @param Table $old
	 * @param Table $new
	 */
	public function extendTables(Table $old, Table $new) {
		$oldAttrs = $old->attrs();
		$newAttrs = $new->attrs();

		$newAttrs = $this->attrs_merge($oldAttrs, $newAttrs);
		$new->attrs($newAttrs);
	}
	
	/**
	 * Merge tables attrs
	 * @param array $a
	 * @param array $b
	 */
	protected function attrs_merge(array $a, array $b) {
		foreach($a as $key=>$val) {
			if(!isset($b[$key])) {
				$b[$key] = $val;
			} else if(isset($b[$key]) && is_array($a[$key])) {
				if(!is_array($b[$key])) {
					$b[$key] = $a[$key];
				} else {
					$b[$key] = $this->attrs_merge($a[$key], $b[$key]);
				}
			}
		}
		return $b;
	}
}