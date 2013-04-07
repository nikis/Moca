<?php
namespace Moca\Orm;

/**
 * Query builder
 * @author nikis
 *
 */
class Builder {
	
	/**
	 * @var string
	 */
	protected $db;
	
	/**
	 * @var string
	 */
	protected $name;
	
	/**
	 * @var array
	 */
	protected $functions = array();
	
	/**
	 * @var array
	 */
	protected $fields = array();
	
	/**
	 * @var array
	 */
	protected $joins = array();
	
	/**
	 * @var array
	 */
	protected $where = array();
	
	/**
	 * @var array
	 */
	protected $order = array();
	
	/**
	 * @var array
	 */
	protected $group = array();
	
	/**
	 * @var array
	 */
	protected $having = array();
	
	/**
	 * @var array
	 */
	protected $set = array();
	
	/**
	 * @var array
	 */
	protected $limit = array();
	
	protected static $_instance = null;
	
	/**
	 * Get builder instance
	 * @param $db
	 * @param $name
	 * @return Builder
	 */
	public static function init($db, $name) {
		if(null === self::$_instance) {	
			self::$_instance = new Builder($db, $name);
		} else {
			self::$_instance->db($db)->name($name);
		}
		return self::$_instance;
	}
	
	public function __construct($db=null, $name=null) {
		$this->db($db);		
		$this->name($name);
	}
	
	/**
	 * Reset builder
	 * @return Builder
	 */
	public function reset($reset=array()) {
		if(!empty($reset)) {
			if(!is_array($reset)) {
				$reset = array($reset);
			}
			foreach($reset as $var) {
				$this->{$var} = array();
			}
		} else {
			$this->db = null;
			$this->name = null;
			$this->functions = array();
			$this->fields = array();
			$this->joins = array();
			$this->where = array();
			$this->order = array();
			$this->group = array();
			$this->having = array();
			$this->set = array();
			$this->limit = array();
		}
		return $this;
	}
	
	/**
	 * Database name
	 * @param $name
	 * @return Builder
	 */
	public function db($name) {
		$this->db = $name;
		return $this;
	}
	
	/**
	 * Table name
	 * @param $name
	 * @return Builder
	 */
	public function name($name) {
		$this->name = $name;
		return $this;
	}
	
	/**
	 * Prepend function
	 * @param $func
	 * @return Builder
	 */
	public function func($func) {
		if(is_array($func)) {
			foreach($func as $f) {
				$this->func($f);
			}
		} else {
			$this->functions[] = $func;
		}
		return $this;	
	}
	
	/**
	 * Fields
	 * @param $field
	 * @return Builder
	 */
	public function column($field) {
		if(is_array($field)) {
			foreach($field as $f) {
				$this->column($f);
			}
		} else {
			$this->fields[] = $field;
		}
		return $this;
	}
	
	/**
	 * Reset and set new columns
	 * @param array $fields
	 * @return Builder
	 */
	public function columns(array $fields) {
		$this->fields = array();
		foreach($fields as $field) {
			$this->column($field);
		}
		return $this;
	}
	
	/**
	 * Where
	 * @param $field
	 * @param $condition
	 * @param $value
	 * @param $next
	 * @return Builder
	 */
	public function where($field, $condition, $value, $next='AND', $castField=true, $castValue=true) {
		$this->where[] = array($field, $condition, $value, $next, $castField, $castValue);
		return $this;
	}
	
	/**
	 * Join table
	 * @param $db
	 * @param $table
	 * @param $column1
	 * @param $condition
	 * @param $column2
	 * @param $type
	 * @return Builder
	 */
	public function join($db=null, $table, $column1, $condition, $column2, $type='INNER') {
		if(!$db) $db = $this->db;
		$ukey = $table.$column1.$column2;
		$this->joins[$ukey] = array($db, $table, $column1, $condition, $column2, $type);
		return $this;
	}
	
	/**
	 * Order
	 * @param $field
	 * @param $mode
	 * @return Builder
	 */
	public function order($field, $mode) {
		$this->order[$field] = $mode;
		return $this;
	}
	
	/**
	 * Group
	 * @param $field
	 * @return Builder
	 */
	public function group($field) {
		$this->group[$field] = $field;
		return $this;
	}
	
	/**
	 * Having
	 * @param $field
	 * @param $condition
	 * @param $value
	 * @param $next
	 * @return Builder
	 */
	public function having($field, $condition='=', $value, $next='AND', $castField=true, $castValue=true) {
		$this->having[] = array($field, $condition, $value, $next, $castField, $castValue);
		return $this;
	}
	
	/**
	 * Set limit
	 * @param $from
	 * @param $to
	 * @return Builder
	 */
	public function limit($from, $to=null) {
		if(null != $to) {
			$this->limit = array($from, $to);
		} else {
			$this->limit = $from;
		}
		return $this;
	}
	
	/**
	 * SET data for update
	 * @param $field
	 * @param $value
	 * @return Builder
	 */
	public function set($field, $value=null) {
		if(is_array($field)) {
			foreach($field as $k=>$v) {
				$this->set($k, $v);
			}
		} else {
			$this->set[$field] = $value;
		}
		return $this;
	}
	
	/**
	 * Build select query
	 * @return string
	 */
	public function select() {
		$query = 'SELECT ';
		$query .= $this->getFunctions();
		$query .= $this->getColumns();
		$query .= 'FROM ';
		$query .= $this->getTable();
		$query .= $this->getJoins();
		$query .= $this->getWhere();
		$query .= $this->getGroup();
		$query .= $this->getHaving();
		$query .= $this->getOrder();
		$query .= $this->getLimit();
		return $query;
	}
	
	/**
	 * Build update query
	 * @return string
	 */
	public function update() {
		$query = 'UPDATE ';
		$query .= $this->getTable();
		$query .= $this->getSet();
		$query .= $this->getWhere();
		$query .= $this->getLimit();
		return $query;
	}
	
	/**
	 * Build delete query
	 * @return string
	 */
	public function delete() {
		$query = 'DELETE FROM ';
		$query .= $this->getTable();
		$query .= $this->getWhere();
		$query .= $this->getLimit();
		return $query;
	}
	
	/**
	 * Build insert query
	 * @param array $data
	 * @param $type
	 * @param array $onDuplicate
	 * @return string
	 */
	public function insert(array $data, $type=null, array $onDuplicate=array()) {
		$query = 'INSERT'.($type ? ' '.$type : '').' INTO ';
		$query .= $this->getTable();
		$query .= '('.$this->getInsertColumns($data).') ';
		$query .= 'VALUES '.$this->getInsertData($data).' ';
		$query .= $this->getOnDuplicateData($onDuplicate);
		return $query;
	}
	
	/**
	 * Create INSERT INTO ... SELECT query
	 * @param $db
	 * @param $name
	 * @return string
	 */
	public function insertSelect($db, $name) {
		$query = 'INSERT INTO '.$this->concatTable($db, $name).' '.$this->select();
		return $query;	
	}
	
	/**
	 * Create table syntax
	 * @param $temp
	 * @return string
	 */
	public function createTableLike($temp=false, $likeDb, $likeName) {
		$query = 'CREATE '.($temp ? 'TEMPORARY ' : '').' TABLE IF NOT EXISTS '.$this->getTable().' LIKE '.$this->concatTable($likeDb, $likeName);
		return $query;
	}
	
	/**
	 * Truncate query
	 * @return string
	 */
	public function truncate() {
		$query = 'TRUNCATE TABLE '.$this->getTable();
		return $query;
	}
	
	/**
	 * Concat field with table name
	 * @param $name
	 * @return string
	 */
	public function concatField($name, $table=null) {
		$table = $table ? $table : $this->name;
		if($name == '*') {
			return '`'.$table.'`.'.$name;
		} else if(strstr($name, '`') || strstr($name, '*')) {
			return $name;
		}
		return '`'.$table.'`.`'.$name.'`';	
	}
	
	/**
	 * Concat table with database
	 * @param $db
	 * @param $name
	 * @return string
	 */
	public function concatTable($db, $name) {
		return '`'.$db.'`.`'.$name.'`';
	}
	
	/**
	 * Concat table name with database
	 * @return string
	 */
	public function getTable() {
		return $this->concatTable($this->db, $this->name).' ';
	}
	
	/**
	 * Get functions
	 * @return string
	 */
	public function getFunctions() {
		if(empty($this->functions)) return '';
		$str = implode(' ', $this->functions).' ';
		return $str;
	}
	
	/**
	 * Get columns
	 * @return string
	 */
	public function getColumns() {
		$str = '';
		foreach($this->fields as $field) {
			$str .= $this->concatField($field).', ';
		}
		return $this->cutStr($str, -2);
	}
	
	/**
	 * Create joins
	 * @return string
	 */
	public function getJoins() {
		$str = '';
		foreach($this->joins as $join) {
			list($db, $table, $column1, $condition, $column2, $type) = $join;
			$str .= $type.' JOIN '.$this->concatTable($db, $table).' ON '.$this->concatField($column1, $table).' '.$condition.' '.$this->concatField($column2).' ';
		}
		return $str;
	}
	
	/**
	 * Create where
	 * @return string
	 */
	public function getWhere() {
		if(empty($this->where)) return ' ';
		$str = 'WHERE '.$this->createConditions($this->where);
		return $str;
	}
	
	/**
	 * Create group
	 * @return string
	 */
	public function getGroup() {
		if(empty($this->group)) return ' ';
		$str = 'GROUP BY ';
		foreach($this->group as $field) {
			$str .= $this->concatField($field).', ';
		}
		return $this->cutStr($str, -2);
	}
	
	/**
	 * Create having
	 * @return string
	 */
	public function getHaving() {
		if(empty($this->having)) return ' ';
		$str = 'HAVING '.$this->createConditions($this->having);
		return $str;
	}
	
	/**
	 * Get order by
	 * @return string
	 */
	public function getOrder() {
		if(empty($this->order)) return '';
		$str = 'ORDER BY ';
		foreach($this->order as $key=>$val) {
			$str .= $this->concatField($key).' '.$val.', ';
		}
		return $this->cutStr($str, -2);
	}
	
	/**
	 * Create limit
	 * @return string
	 */
	public function getLimit() {
		if(empty($this->limit)) return '';
		$str = 'LIMIT ';
		if(is_array($this->limit)) {
			$str .= $this->limit[0].', '.$this->limit[1];
		} else {
			$str .= $this->limit;
		}
		$str .= ' ';
		return $str;
	}
	
	/**
	 * Create set
	 * @return string
	 */
	public function getSet() {
		if(empty($this->set)) return ' ';
		$str = 'SET ';
		foreach($this->set as $key=>$val) {
			$str .= $this->createCondition($key, '=', $val, ',', true, true);
		}
		return $this->cutStr($str, -2);
	}
	
	/**
	 * Create insert columns
	 * @param array $data
	 * @return string
	 */
	public function getInsertColumns(array $data) {
		return '`'.implode('`, `', array_keys($data)).'`';
	}
	
	/**
	 * Create insert data
	 * @param array $data
	 * @return string
	 */
	public function getInsertData(array $data) {
		return $this->castValue($data);
	}
	
	/**
	 * Format duplicate data
	 * @param array $data
	 * @return string
	 */
	public function getOnDuplicateData(array $data) {
		if(empty($data)) return '';
		$str = 'ON DUPLICATE KEY UPDATE ';
		foreach($data as $key=>$val) {
			$str .= $this->createCondition($key, '=', $value, ',', true, true);
		}
		return $this->cutStr($str, -2);
	}
	
/**
	 * Marge builders data
	 * @return void
	 */
	public function merge($data) {
		if($data instanceof Builder) {
			$data = $data->toArray();
		}
		
		if(!is_array($data)) {
			return false;
		}
		
		if(isset($data['func'])) {
			if(!is_array($data['func'])) {
				$data['func'] = array($data['func']);
			}	
			$this->func($data['func']);
		}
		
		if(isset($data['column'])) {
			if(!is_array($data['column'])) {
				$data['column'] = array($data['column']);
			}
			$this->column($data['column']);
		}
		
		if(isset($data['join'])) {
			$this->__applyArrArgs('join', $data['join']);
		}
		if(isset($data['where'])) {
			$this->__applyArrArgs('where', $data['where']);
		}
		if(isset($data['order'])) {
			$this->__applyArrArgs('order', $data['order']);
		}
		if(isset($data['group'])) {
			$this->__applyArrArgs('group', $data['group']);
		}
		if(isset($data['having'])) {
			$this->__applyArrArgs('having', $data['having']);
		}
		if(isset($data['set'])) {
			$this->__applyArrArgs('set', $data['set']);
		}
		if(isset($data['limit'])) {
			if(!is_array($data['limit'])) {
				$data['limit'] = array($data['limit']);
			}
			
			if(!empty($data['limit'])) {
				if(!isset($data['limit'][1])) {
					$data['limit'][1] = null;
				}
				$this->limit($data['limit'][0], $data['limit'][1]);
			}
			
		}
	}
	
	/**
	 * Export builder to array
	 * @return array
	 */
	public function toArray() {
		$order = array();
		foreach($this->order as $k=>$v) {
			$order[$k] = array($k, $v);
		}
		return array(
			'db' => $this->db,
			'name' => $this->name,
			'column' => $this->fields,
			'join' => $this->joins,
			'where' => $this->where,
			'order' => $order,
			'group' => $this->group,
			'having' => $this->having,
			'set' => $this->set,
			'limit' => $this->limit
		);	
	}
	
	/**
	 * Cut and format string
	 * @param $str
	 * @param $len
	 * @return string
	 */
	protected function cutStr($str, $len=-1) {
		return substr($str, 0, $len).' ';
	}
	
	/**
	 * Cast value
	 * @param $value
	 * @return string
	 */
	protected function castValue($value) {
		if(is_array($value)) {
			foreach($value as $k=>$v) {
				$value[$k] = $this->castValue($v);
			}
			$value = '('.implode(', ', $value).')';
		} else {
			$value = '"'.addslashes($value).'"';
		}
		return $value;
	}
	
	/**
	 * Create conditions
	 * @param array $conditions
	 * @return string
	 */
	protected function createConditions(array $conditions) {
		$str = '';
		foreach($conditions as $singel) {
			list($field, $condition, $value, $next, $castField, $castValue) = $singel;
			$next .= ' ';
			$str .= $this->createCondition($field, $condition, $value, $next, $castField, $castValue);
		}
		return $this->cutStr($str, strlen($next)*-1);
	}
	
	/**
	 * create singel condition
	 * @param $field
	 * @param $condition
	 * @param $value
	 * @param $next
	 * @param $castField
	 * @param $castValue
	 * @return string
	 */
	protected function createCondition($field, $condition, $value, $next, $castField, $castValue) {
		if($castField) {
			$field = $this->concatField($field);
		}
		if($castValue) {
			$value = $this->castValue($value);
		}
		return $field.' '.$condition.' '.$value.' '.$next;
	}
	

	/**
	 * Cast value to array and apply for args
	 * @param $method
	 * @param $list
	 * @return void
	 */
	protected function __applyArrArgs($method, $list) {
		if(!is_array($list)) {
			$list = array($list);
		}	
		foreach($list as $args) {
			if(!is_array($args)) {
				$args = array($args);
			}
			call_user_func_array(array($this, $method), $args);
		}
	}
}