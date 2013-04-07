<?php
namespace Moca\Orm;

use Iterator,
	Exception;

/**
 * Table
 * @author nikis
 *
 */
class Table implements Iterator {
	
	/**
	 * Database name
	 * @var string
	 */
	public $db;
	
	/**
	 * Table name
	 * @var string
	 */
	public $name;
	
	/**
	 * @var array
	 */
	public $attributes = array();
	
	/**
	 * @var string
	 */
	public $rowClass = 'Moca\Orm\Row';
	
	/**
	 * @var Result
	 */
	public $result = array();
	
	/**
	 * @var string
	 */
	public $relationName = null;
	
	/**
	 * Instances holder
	 * @var array
	 */
	protected static $_instance = array();
       
	/**
	 * @var string
	 */
	protected $pk = false;
	
	/**
	 * @var boolean
	 */
	protected $autoIncrement = false;
	
	/**
	 * @var array
	 */
	protected $unique = array();

	/**
	 * @var boolean
	 */
	protected $hasMany = false;
	
	/**
	 * @var boolean
	 */
	protected $calculateRows = false;
	
	/**
	 * @var Row
	 */
	protected $row;
	
	/**
	 * @var IConnector
	 */
	protected $connector;

	/**
	 * @var int
	 */
	protected $foundRows = 0;
	
	/**
	 * @var array
	 */
	protected $events = array();
	
	/**
	 * @var Row
	 */
	protected $__row = null;
	
	/**
	 * @var int
	 */
	protected $__iteratorCounter = 0;
	
	/**
	 * @var int
	 */
	protected $__iteratorLimit = false;
	
	/**
	 * Get table instance
	 * @return Table
	 */
	public static function init($singelton=false) {
	 	$className = get_called_class();
	 	if($singelton) {
	 		if(!isset(self::$_instance[$className])) {
	 			self::$_instance[$className] = new $className();
	 		}
	 		return self::$_instance[$className];
	 	}
	 	return new $className();
	}
	
	/**
	 * Initialize table
	 * @return void
	 */
	protected function __construct() {
		if(!empty($this->attributes)) {
			$this->attrs($this->attributes);
		}		
	}
	
	/**
	 * Get or set database name
	 * @param $name
	 * @return string
	 */
	public function db($name=null) {
		if(null != $name) {
			$this->db = $name;
		}
		return $this->db;
	}
	
	/**
	 * Get or set table name
	 * @param $name
	 * @return string
	 */
	public function name($name=null) {
		if(null !== $name) {
			$this->name = $name;
		}
		return $this->name;
	}
	
	/**
	 * Get relation name
	 * @param $name
	 * @return string
	 */
	public function relationName($name=null) {
		if(null === $name && null === $this->relationName) {
			$class = get_class($this);
			$class = explode('\\', $class);
			$class = end($class);
			$this->relationName = strtolower($class);	
		}
		if(null !== $name) {
			$this->relationName = $name;
		}	
		return $this->relationName;
	}
	
	/**
	 * Set new attribute
	 * @param $key
	 * @param array $val
	 * @return array
	 */
	public function attr($key, array $val=null) {
		if($val != null) {
			if(!isset($val['type'])) {
				throw new Exception(get_class($this).' attribute with name '.$key.' has no type');
			}
			$this->attributes[$key] = $val;
			if(in_array('primary', $val, true) || isset($val['primary'])) {
				$this->pk = $key;
				if(in_array('auto_increment', $val, true) || isset($val['auto_increment'])) {
					$this->autoIncrement = true;
				}
				$this->unique[$key] = $key;
			} else if(in_array('unique', $val, true) || isset($val['unique'])) {
				$this->unique[$key] = $key;
			}
		}
		if(isset($this->attributes[$key])) {
			return $this->attributes[$key];
		}
		return false;
	}
	
	/**
	 * Check if table has attribute
	 * @param $key
	 * @return boolean
	 */
	public function hasAttr($key) {
		return isset($this->attributes[$key]) ? true : false;	
	}
	
	/**
	 * Set multi attributes
	 * @param array $attrs
	 * @return array
	 */
	public function attrs(array $attrs=null) {
		if(null !== $attrs) {
			foreach($attrs as $key=>$val) {
				$this->attr($key, $val);
			}
		}
		return $this->attributes;
	}
	
	/**
	 * Get primary key
	 * @return string
	 */
	public function pk() {
		return $this->pk;
	}
	
	/**
	 * Check if table has auto increment field
	 * @return boolean
	 */
	public function autoIncrement() {
		return $this->autoIncrement;
	}
	
	/**
	 * Get unique fields
	 * @return array
	 */
	public function unique() {
		return $this->unique;
	}
	
	/**
	 * One or many rows
	 * @param $bool
	 * @return boolean
	 */
	public function hasMany($bool=null) {
		if(null !== $bool) {
			$this->hasMany = $bool;
		}	
		return $this->hasMany;
	}
	
	/**
	 * Enable / Disable calculate rows
	 * @param $bool
	 * @return boolean
	 */
	public function calculateRows($bool=null) {
		if(null !== $bool) {
			$this->calculateRows = $bool;
		}
		return $this->calculateRows;
	}
	
	/**
	 * Get finded rows
	 * @return int
	 */
	public function totalRows() {
		return $this->foundRows;
	}
	
	/**
	 * Get row instance
	 * @return Row
	 */
	public function row() {
		$class = new $this->rowClass($this);
		return $class;	
	}
	
	/**
	 * Get last data list
	 * @return array
	 */
	public function result() {
		return $this->result;
	}
	
	/**
	 * Check if result is Row
	 * @return boolean
	 */
	public function is_row() {
		return $this->result instanceof Row;
	}
	
	/**
	 * Clear table
	 */
	public function clear() {
		$this->result = null;
	}
	
	/**
	 * Get builder instance
	 * @return Moca\Orm\Builder
	 */
	public function builder() {
		$b = new Builder($this->db(), $this->name());
		$b->column(array_keys($this->attributes));
		return $b;
	}
	
	/**
	 * Get database connector
	 * @return Moca\Orm\DatabaseProviderInterface
	 */
	public function connection(DatabaseProviderInterface $newConnector=null) {
		if(null !== $newConnector) {
			$this->connector = $newConnector;
		}
		return $this->connector;
	}
	
	/**
	 * Add event
	 * @param $name
	 * @param $func
	 * @return Table
	 */
	public function event($name, $func) {
		$this->events[$name][] = $func;
		return $this;
	}
	
	/**
	 * Trigger new event
	 * @param $event
	 * @return array
	 */
	public function triggerEvent($event) {
		if(!isset($this->events[$event])) {
			return false;
		}	
		$args = array_slice(func_get_args(), 1);
		foreach($this->events[$event] as $func) {
			call_user_func_array($func, $args);
		}
		return $this;
	}
	
	/**
	 * Get first record
	 * @param $value
	 * @return Row
	 */
	public function first($value=null) {
		$this->applyFirstLast($value, 'ASC');
		return $this->result;
	}
	
	/**
	 * Get last record
	 * @param $value
	 * @return Row
	 */
	public function last($value=null) {
		$this->applyFirstLast($value, 'DESC');
		return $this->result;
	}
	
	/**
	 * Get all records
	 * @return Result
	 */
	public function all($query=null) {
		$builder = $this->builder();
		$builder->merge($query);
		
		$this->hasMany(true);
		$this->fetchQuery($builder);
		return $this;
	}
	
	/**
	 * Execute query
	 * @param $query
	 * @return Result|Row
	 */
	public function query($query, $many=true) {
		if(!is_string($query)) {
			$builder = $this->builder();
			$builder->merge($query);
		} else {
			$builder = $query;
		}
		$this->hasMany($many);
		$this->fetchQuery($builder);
		return $this;	
	}
	
	/**
	 * Min
	 * @param $field
	 * @return Row
	 */
	public function min($field=null, $query=null) {
		return $this->applyColumnFunction($field, 'MIN', $query);
	}
	
	/**
	 * Max
	 * @param $field
	 * @return Row
	 */
	public function max($field=null, $query=null) {
		return $this->applyColumnFunction($field, 'MAX', $query);
	}
	
	/**
	 * Avg
	 * @param $field
	 * @return Row
	 */
	public function avg($field=null, $query=null) {
		return $this->applyColumnFunction($field, 'AVG', $query);
	}
	
	/**
	 * Sum rows
	 * @param $field
	 * @param $query
	 * @return int
	 */
	public function sum($field=null, $query=null) {
		return $this->applyColumnFunction($field, 'SUM', $query);
	}
	
	/**
	 * Count rows
	 * @param $field
	 * @return int
	 */
	public function count($field='*', $query=null) {
		return $this->applyColumnFunction($field, 'COUNT', $query);
	}
	
	/**
	 * Get distinct values for given field
	 * @param $field
	 * @param $query
	 * @return Result
	 */
	public function distinct($field, $query=null) {
		$builder = $this->builder();
		$builder->merge($query);
		$builder->reset('fields');
		$builder->columns(array(
			'DISTINCT `'.$field.'`'
		));
		$this->hasMany(true);
		$this->fetchQuery($builder);
		return $this;
	}
	
	/**
	 * Found calc rows
	 * @return int
	 */
	public function foundRows() {
		$result = $this->connection()->exec_query('SELECT FOUND_ROWS() AS `count`');
		$row = $this->connection()->fetch_arr($result);
		return (int)$row['count'];
	}
	
	/**
	 * Fetch query
	 * @param $query
	 * @return Table
	 */
	public function fetchQuery($query, $asResult=true) {
		
		$this->foundRows = 0;
		
		if($query instanceof Builder) {
			
			if($this->calculateRows()) {
				$query->func('SQL_CALC_FOUND_ROWS');	
			}
			
			$query = $query->select();
			
		}
		
		$queryResult = $this->connection()->exec_query($query);
		
		if($this->calculateRows()) {
			$this->calculateRows(false);
			$this->foundRows = $this->foundRows();	
		}
		
		if($this->hasMany) {
			
			$data = array();
			
			while($row = $this->connection()->fetch_arr($queryResult)) {
				
				$row = $this->row()->init($row);
				
				if($this->pk && $row->has($this->pk)) {
					$data[$row->get($this->pk)] = $row;
				} else {
					$data[] = $row;
				}
			}
			
		} else {
			
			$data = $this->connection()->fetch_arr($queryResult);
			if(!is_array($data)) {
				$data = array();
			}
			$row = $this->row();
			$row->init($data);
			
			$data = $row;
			
		}

		if($asResult) {
			$this->result = $data;
			return $this;
		} else {
			return $data;	
		}
	}
	
	/**
	 * Execute function on field and return result
	 * @param $field
	 * @param $func
	 * @return integer
	 */
	public function applyColumnFunction($field, $func, $query) {
		$builder = $this->builder();
		$builder->merge($query);
		
		if(null === $field) {
			if($this->pk) {
				$field = $this->pk;
			} else {
				$field = '*';
			}
		}
		if($field != '*') {
			$field = '`'.$field.'`';
		}
		$builder->columns(array(
			$func.'('.$field.') AS `result`'
		));
		
		$this->hasMany(false);
		
		$this->fetchQuery($builder);
		
		return (int)$this->result->result;
	}
	
	/**
	 * Truncate table
	 * @return unknown_type
	 */
	public function truncateTable() {
		$query = $this->builder()->truncate();
		$this->connection()->exec_query($query);
	}
	
	/**
	 * Copy table
	 * @param $db
	 * @param $name
	 * @return Table
	 */
	public function copyTable($name, $db=null, $temp=false) {
		if(!$temp && !$name) {
			throw new Exception('New table has no name');
		}
		
		$db = $db ? $db : $this->db();
		$name = $name ? $name : 'temp_'.substr(md5(microtime(true).rand(1,9999)), 0, 10);

		$table = new Table();
		$table->connection($this->connection());
		$table->db($db);
		$table->name($name);
		$table->attrs($this->attrs());
		$table->rowClass = $this->rowClass;
		
		$builder = $table->builder();
		$query = $builder->createTableLike($temp, $this->db(), $this->name());
		$table->connection()->exec_query($query);
		
		$builder = $this->builder();
		$builder->columns(array('*'));
		$query = $builder->insertSelect($table->db(), $table->name());
		$this->connection()->exec_query($query);
		
		return $table;
	}
	
	/**
	 * First / Last default query
	 * @param $value
	 * @return Row
	 */
	protected function applyFirstLast($value, $order) {
		$builder = $this->builder();
		if(is_array($value) || ($value instanceof Builder)) {
			$builder->merge($value);
		} else if($this->pk) {
			if(null !== $value) {
				$builder->where($this->pk, '=', $value);
			}
			$builder->order($this->pk, $order);
		}
		$builder->limit(1);
		$this->hasMany(false);
		return $this->fetchQuery($builder);
	}
	
	/**
	 * Load relations
	 * @param Table $table
	 * @param $myKey
	 * @param $otherKey
	 * @param $hasMany
	 * @return Result or Row
	 */
	public function load($table, $myKey, $otherKey, $hasMany=false, $loadEmpty=false) {
		
		if($this->hasMany()) {
			if(!is_array($this->result)) {
				$this->result = array();
			}
		} else if(!$this->is_row()) {
			$this->result = $this->row();
		}
		
		$mainResult = $this->result;
		
		$table = self::load_table($table, true);
		
		if(!$this->hasAttr($myKey)) {
			throw new Exception($this->name().' has no field '.$myKey);
		}
		if(!$table->hasAttr($otherKey)) {
			throw new Exception($table->name().' has no field '.$otherKey);
		}
		
		$relName = $table->relationName();
		
		$values = array();
		
		if(false === $loadEmpty) {
			if($mainResult instanceof Row && !$mainResult->isEmpty()) {
				$values = array($mainResult->get($myKey));
			} else if(!empty($mainResult)) {
				foreach($mainResult as $row) {
					$values[] = $row->get($myKey);
				}
			}		
		}
		
		$builder = $table->builder();
		if(!empty($values)) {
			$values = array_unique($values);
			$builder->where($otherKey, 'IN', $values);
		}
		
		$this->triggerEvent('relation', $relName, $builder);
		$this->triggerEvent('relation_'.$relName, $relName, $builder);
		
		if(!empty($values)) {
			$table->all($builder);
		} else {
			$table->hasMany($hasMany);
		}
		
		$result = $table;

		if($mainResult instanceof Row) {
			if($hasMany) {
				$mainResult->set($relName, $result);
			} else {
				$mainResult->set($relName, $result->first_row());
			}
		} else {

			foreach($mainResult as $mainKey=>$mainRow) {
				
				$value = $mainRow->get($myKey);
				$value = !is_array($value) ? array($value) : $value;
				
				$buffer = array();
				
				if(false === $loadEmpty) {
					foreach($result as $relKey=>$relRow) {
						$relValue = $relRow->get($otherKey);
						
						if(is_array($relValue)) {
							$inter = array_intersect($value, $relValue);
							if(empty($inter)) {
								continue;
							}
						} else if(!in_array($relValue, $value)) {
							continue;
						}
	
						$buffer[] = $relRow;
					}
				}

				if(!$hasMany) {
					$buffer = end($buffer);
					if(false === $buffer) {
						$buffer = $result->row();
					}
				}
				
				$mainRow->set($relName, $buffer);
			}
		}
		
		return $result;
	}
	
	/**
	 * Has one row
	 * @param $table
	 * @param $otherKey
	 * @param $empty
	 * @return mixed
	 */
	public function has_one($table, $otherKey, $empty=false) {
		return $this->load($table, $this->pk, $otherKey, false, $empty);	
	}
	
	/**
	 * Has many relations rows
	 * @param $table
	 * @param $otherKey
	 * @param $empty
	 * @return mixed
	 */
	public function has_many($table, $otherKey, $empty=false) {
		return $this->load($table, $this->pk, $otherKey, true, $empty);
	}
	
	/**
	 * Belongs to singel row
	 * @param $table
	 * @param $mainKey
	 * @param $empty
	 * @return mixed
	 */
	public function belongs_to($table, $mainKey, $empty=false) {
		$table = self::load_table($table, true);
		return $this->load($table, $mainKey, $table->pk(), false, $empty);
	}
	
	/**
	 * Belongs to many rows
	 * @param $table
	 * @param $mainKey
	 * @param $empty
	 * @return mixed
	 */
	public function belongs_to_many($table, $mainKey, $empty=false) {
		$table = self::load_table($table, true);
		return $this->load($table, $mainKey, $table->pk(), true, $empty);
	}
	
	/**
	 * Load table
	 * @param $table
	 * @param $exception
	 * @throws Exception
	 * @return Table
	 */
	protected static function load_table($table, $exception=false) {
		if(is_string($table)) {
			$table = @forward_static_call(array($table, 'init'));
		}	
		if($table instanceof Table) {
			return $table;
		}
		if($exception) {
			throw new Exception('Table must be instanceof Table');	
		}
		return false;
	}
	
	/**
	 * Check if result is empty
	 * @return boolean
	 */
	public function isEmpty() {
		if($this->result instanceof Row) {
			return $this->result->isEmpty();
		}
		return empty($this->result);
	}
	
	/**
	 * Set interator limit
	 * @param $limit
	 * @return Datalist
	 */
	public function limit($limit) {
		$this->__iteratorLimit = $limit;
		return $this;
	}
	
	/**
	 * Get first element
	 * @return Row
	 */
	public function first_row() {
		$result = reset($this->result);
		if(false === $result) {
			return $this->row();
		}
		return $result;
	}
	
	/**
	 * Get last element
	 * @return Row
	 */
	public function last_row() {
		$result = end($this->result);
		if(false === $result) {
			$result = $this->row();
		}
		return $result;
	}
	
	/**
	 * Dump table last result to array
	 * @return array
	 */
	public function toArray() {
		$buffer = array();
		if(is_array($this->result)) {	
			foreach($this->result as $i=>$row) {
				$buffer[$i] = $row->toArray();
			}
		} else if($this->result instanceof Row) {
			$buffer = $this->result->toArray();
		}
		return $buffer;
	}
	
	/**
	 * MAGIC METHODS
	 */
	
	public function __set($key, $val) {
		if(false === $this->result instanceof Row) {
			throw new \Exception('Result is not instance of Row');
		}
		return $this->result->set($key, $val);
	}
	
	public function __get($key) {
		if(false === $this->result instanceof Row) {
			throw new \Exception('Result is not instance of Row');
		}
		return $this->result->get($key);
	}
	
	public function __isset($key) {
		if(false === $this->result instanceof Row) {
			throw new \Exception('Result is not instance of Row');
		}
		return $this->result->has($key);
	}
	
	/**
	 * Iterator interface
	 */

	public function rewind() {
        reset($this->result);
    }

    public function current() {
        return current($this->result);
    }

    public function key() {
        return key($this->result);
    }

    public function next() {
    	if($this->__iteratorLimit) {
    		$this->__iteratorCounter++;
    	}
        return next($this->result);
    }

    public function valid() {
    	if($this->__iteratorLimit) {
    		if($this->__iteratorCounter >= $this->__iteratorLimit) {
    			$this->__iteratorCounter = 0;
    			$this->__iteratorLimit = false;
    			return false;
    		}
    	}
        return $this->current() !== false;
    }
}