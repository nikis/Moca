<?php
namespace Moca\Orm;

use Exception;

/**
 * Table row
 * @author nikis
 *
 */
class Row {
	
	/**
	 * @var Table
	 */
	protected $table;
	
	/**
	 * @var array
	 */
	protected $data = array();
	
	/**
	 * @var array
	 */
	protected $modifiedData = array();
	
	public function __construct(Table $table, array $data=array()) {
		$this->table = $table;	
		$this->data = $data;
	}
	
	/**
	 * Initialize row
	 * @param array $data
	 * @return Row
	 */
	public function init(array $data) {
		$this->data = $this->typeData($data);
		$this->modifiedData = array();
		return $this;		
	}
	
	/**
	 * Insert new data
	 * @param array $data
	 * @return Row
	 */
	public function create(array $data=null) {
		$assign = true;
		if(null === $data) {
			$data = $this->data;
			$assign = false;
		}
		
		$data = $this->intersect($data);

		if($this->table->autoIncrement() && $pk = $this->table->pk()) {
			if(isset($data[$pk])) {
				unset($data[$pk]);
			}
		}
		
		$this->data = $data;

		if($duplicate = $this->isDuplicate(true)) {
			throw new onDuplicateRow($duplicate);
		}
		
		$this->table->triggerEvent('before.create', $this);
		
		$builder = $this->table->builder();
		$query = $builder->insert($this->data);

		$this->table->connection()->exec_query($query);
		
		if($this->table->autoIncrement() && $pk = $this->table->pk()) {
			$this->data[$pk] = $this->table->connection()->last_insert_id();
		}
		
		$this->table->triggerEvent('after.create', $this);
		
		if($assign) {
			$data = &$this;
		}
		return $this;
	}
	
	/**
	 * Save changes
	 * @return Row
	 */
	public function save() {
		if(empty($this->modifiedData)) {
			return null;
		}
	
		if($duplicate = $this->isDuplicate()) {
			throw new onDuplicateRow($duplicate);
		}
		
		$this->table->triggerEvent('before.save', $this, $this->modifiedData);
		
		$builder = $this->table->builder();
		$this->defaultBuilderWhere($builder);
		
		$fields = $this->table->attrs();
		foreach($this->modifiedData as $key=>$val) {
			$builder->set($key, $this->valueType($key, $fields[$key], $this->data[$key], false));
		}
		
		$query = $builder->update();

		$this->table->connection()->exec_query($query);
		
		$this->modifiedData = array();
		
		$this->table->triggerEvent('after.save', $this);
		
		return true;
	}
	
	/**
	 * Delete current row
	 * @return Row
	 */
	public function delete() {
		
		$this->table->triggerEvent('before.delete', $this);
		
		$builder = $this->table->builder();
		$this->defaultBuilderWhere($builder);
		
		$query = $builder->delete();
		$this->table->connection()->exec_query($query);
		
		$this->reset();
		
		$this->table->triggerEvent('after.delete', $this);
	}
	
	/**
	 * Check if data is duplicated
	 * @return Row
	 */
	public function isDuplicate($create=false) {
		$fields = $this->table->unique();

		$dat = array();
		
		if($create) { // if we add new row
			foreach($fields as $field) {
				if(isset($this->data[$field])) {
					$dat[$field] = $this->data[$field];
				}
			}
		} else {
			foreach($fields as $field) {
				if(isset($this->data[$field]) && isset($this->modifiedData[$field])) {
					$dat[$field] = $this->data[$field];		
				}
			}
		}

		if(empty($dat)) {
			return false;
		}

		$builder = $this->table->builder();
		foreach($dat as $key=>$val) {
			$builder->where($key, '=', $val);
		}

		$row = $this->table->first($builder);
		
		return $row->isEmpty() ? false : $row;
	}
	
	/**
	 * Check if row is empty
	 * @return boolean
	 */
	public function isEmpty() {
		return empty($this->data) ? true : false;
	}
	
	/**
	 * Set value
	 * @param $key
	 * @param $val
	 * @return Row
	 */
	public function set($key, $val) {
		if($this->has($key) && $this->data[$key] != $val) {
			$this->modifiedData[$key] = $this->get($key);
		}
		$this->data[$key] = $val;
		return $this;
	}
	
	/**
	 * Get value or given default if not exist
	 * @param $key
	 * @param $default
	 * @return mixed
	 */
	public function get($key, $default=null) {
		return $this->has($key) ? $this->data[$key] : $default;
	}
	
	/**
	 * Check if key exist
	 * @param $key
	 * @return boolean
	 */
	public function has($key) {
		return array_key_exists($key, $this->data);
	}
	
	/**
	 * Fill current row
	 * @param array $data
	 * @return Row
	 */
	public function fill(array $data) {
		foreach($data as $key=>$val) {
			$this->set($key, $val);
		}	
		return $this;
	}
	
	/**
	 * Reset row
	 * @return Row
	 */
	public function reset() {
		$this->data = array();
		$this->modifiedData = array();
		return $this;
	}
	
	/**
	 * Copy current row
	 * @return Row
	 */
	public function copy() {
		$class = get_class($this);
		return new $class($this->table, $this->toArray(false));
	}
	
	/**
	 * Export to array
	 * @return array
	 */
	public function toArray($full=true) {
		$dat = array();
		foreach($this->data as $key=>$val) {
			if($full && ($val instanceof Row || $val instanceof Table)) {
				$val = $val->toArray();
			}
			$dat[$key] = $val;
		}
		return $dat;
	}
	
	/**
	 * Export to json
	 * @return string
	 */
	public function toJson() {
		return json_encode($this->toArray());
	}
	
	/**
	 * Values
	 * @param $values
	 * @return array
	 */
	public function values($values) {
		if(!is_array($values)) {
			$values = func_get_args();
		}
		$data = $this->toArray();
		
		$buffer = array();
		foreach($values as $key) {
			$buffer[$key] = $this->_array_get($data, $key, null);
		}
		return $buffer;
	}
	
	/**
	 * Intersect data
	 * @param array $data
	 * @param $intersect
	 * @return array
	 */
	public function intersect(array $data) {
		$dat = array();

		$fields = $this->table->attrs();
		foreach($fields as $field=>$attrs) {
			if(isset($data[$field])) {
				$value = $data[$field];
			} else {
				$value = $this->valueDefault($field, $attrs, $data);
			}
			$value = $this->valueType($field, $attrs, $value, false);
			$dat[$field] = $value;
		}
		return $dat;
	}

	/**
	 * Type cast loaded data
	 * @param array $data
	 * @return array
	 */
	public function typeData(array $data) {
		$fields = $this->table->attrs();
		foreach($data as $key=>$value) {	
			if(array_key_exists($key, $fields)) {
				$data[$key] = $this->valueType($key, $fields[$key], $value, true);
			}
		}
		return $data;
	}
	
	/**
	 * Cast value
	 * @param $field
	 * @param array $attrs
	 * @param $value
	 * @param $reverse
	 * @return mixed
	 */
	public function valueType($field, array $attrs, $value, $reverse) {
		if(is_array($value) && isset($attrs['length'])) {
			$skipLen = true;
			$value = $this->valueLength($value, $attrs['length']);
		} else {
			$skipLen = false;
		}
		$method = 'type_'.$attrs['type'];
		if(method_exists($this, $method)) {
			$value = $this->$method($value, $reverse, $field, $attrs);
		} else if(method_exists($this->table, $method)) {
			$value = $this->table->$method($value, $reverse, $field, $attrs);
		}
		if(!$skipLen && isset($attrs['length'])) {
			$value = $this->valueLength($value, $attrs['length']);
		}
		return $value;
	}
	
	/**
	 * Cust value if need
	 * @param $value
	 * @param $length
	 * @return mixed
	 */
	public function valueLength($value, $length) {
		if(is_array($value)) {
			if(count($value) > $length) {
				$value = array_slice($value, 0, $length, true);
			}
		} else if(strlen($value) > $length) {
			$value = substr($value, 0, $length);
		}
		return $value;
	}
	
	/**
	 * Get default value
	 * @param $field
	 * @param array $attrs
	 * @param array $data
	 * @return mixed
	 */
	public function valueDefault($field, array $attrs, array $data) {
		if(isset($attrs['default'])) {
			$method = 'default_'.$attrs['default'];
			if(method_exists($this, $method)) {
				$value = $this->$method();
			} else if(method_exists($this->table, $method)) {
				$value = $this->table->$method();
			} else {
				$value = $attrs['default'];
			}
		} else {
			$value = null;
		}
		return $value;
	}
	
	/**
	 * Create default where for builder
	 * @return void
	 */
	protected function defaultBuilderWhere(Builder $builder) {
		$pk = $this->table->pk();
		if($pk && isset($this->data[$pk])) {
			$builder->where($pk, '=', $this->data[$pk]);
		} else {
			$unique = $this->table->unique();
			foreach($unique as $i=>$key) {
				if(array_key_exists($key, $this->modifiedData)) {
					unset($unique[$i]);
				} else {
					$unique[$i] = $this->data[$key];
				}
			}
			
			if(empty($unique)) {
				$unique = $this->data;
			}
			
			foreach($unique as $key=>$val) {
				if(array_key_exists($key, $this->modifiedData)) {
					continue;
				}
				if($this->table->attr($key)) {
					$builder->where($key, '=', $val);
				}
			}
		}
		$builder->limit(1);
	}
	
	/**
	 * MAGIC METHODS
	 */
	
	public function __set($key, $val) {
		$this->set($key, $val);
	}
	
	public function __get($key) {
		return $this->get($key);
	}
	
	public function __isset($key) {
		return $this->has($key);
	}
	
	public function __call($method, $args) {
		array_unshift($args, $method);
		if(substr($method, 0, 4) == 'set_') {
			array_unshift($args, 'set');
		} else {
			array_unshift($args, 'get');
		}
		return call_user_func_array(array($this, '_triggerGetterSetter'), $args);
	}
	
	/**
	 * Trigger getter or setter dynamic methods
	 * @param $type
	 * @param $key
	 * @param $value
	 * @return mixed
	 */
	protected function _triggerGetterSetter($type, $key) {
		$args = array_slice(func_get_args(), 2);
		array_unshift($args, $this);
		
		$method = $type.'_'.$key;
		
		if('get' == $type) {
			if($this->has($key)) {
				$value = $this->data[$key];
			}
		}
		
		if(method_exists($this, $method)) {
			return call_user_func_array(array($this, $method), $args);
		} else if(method_exists($this->table, $method)) {
			return call_user_func_array(array($this->table, $method), $args);
		} else if(method_exists($this->table, $key)) {
			return call_user_func_array(array($this->table, $key), $args);
		}
		
		if(isset($value)) {
			return $value;
		}
	}
	
	/**
	 * Split key and get value
	 * @param $array
	 * @param $key
	 * @param $default
	 * @return mixed
	 */
	protected function _array_get($array, $key, $default = null) {
		if (is_null($key)) return $array;
		foreach (explode('.', $key) as $segment) {

			if ( ! is_array($array) or ! array_key_exists($segment, $array)) {
				return $default;
			}
			
			$array = $array[$segment];	
		}
		return $array;
	}
	
	/**
	 * ROW ATTRIBUTE TYPES
	 */
	
	public function type_integer($value, $reverse) {
		return (int)$value;
	}
	
	public function type_int($value, $reverse) {
		return (int)$value;
	}
	
	public function type_bigint($value, $reverse) {
		return $value;
	}
	
	public function type_string($value, $reverse) {
		if($reverse) {
			return stripslashes((string)$value);
		}
		return (string)$value;
	}
	
	public function type_float($value, $reverse) {
		return str_replace(',', '.', $value);
	}
	
	public function type_date($value, $reverse) {
		if($reverse) return $value;
		return date("Y-m-d H:i:s", $value);	
	}
	
	public function type_enum($value, $reverse, $field, array $attrs) {
		if($reverse) {
			return $value;
		} else {
			$list = isset($attrs['values']) && is_array($attrs['values']) ? $attrs['values'] : array();
			if(!in_array((string)$value, $list, true)) {
				return null;
			}
			return $value;
		}
	}
	
	public function type_set($value, $reverse, $field, $attrs) {
		if($reverse) {
			$items = explode(',', $value);
			foreach($items as $key=>$item) {
				if('' == $item) {
					unset($items[$key]);
				}
			}
			return $items;
		}
		$value = !is_array($value) ? array($value) : $value;
		$list = isset($attrs['values']) && is_array($attrs['values']) ? $attrs['values'] : array();
		$inter = array_intersect($list, $value);
		return implode(',', $inter);
	}
	
	public function type_array($value, $reverse, $field, $attrs) {
		$delimiter = isset($attrs['delimiter']) ? $attrs['delimiter'] : ',';
		if($reverse) {
			return explode($delimiter, $value);
		} else {
			if(!is_array($value)) $value = array();
			$value = implode($delimiter, $value);
			return $value;
		}
	}
	
	public function type_serialize($value, $reverse) {
		if($reverse) {
			$value = @unserialize($value);
		} else {
			$value = serialize($value);
		}
		return $value;
	}
	
	public function type_json($value, $reverse) {
		if($reverse) {
			$value = json_decode($value, true);
			$value = !is_array($value) ? array() : $value;
		} else {
			$value = json_encode($value);
		}
		return $value;
	}
	
	public function type_md5($value, $reverse) {
		if($reverse) return $value;
		return md5($value);
	}
	
	public function type_crypt($value, $reverse, $field, $attrs) {
		if(!isset($attrs['crypt'])) {
			throw new Exception('Type crypt options is not defined for field '.$field);
		}
		
		$options = $attrs['crypt'];
		
		if(!isset($options['key'])) {
			throw new Exception('Type crypt for '.$field.' has no KEY');
		} else if(!isset($options['type'])) {
			throw new Exception('Type crypt for '.$field.' has no defined CRYPT TYPE');
		}
		
		$method = 'crypt_'.$options['type'];
		if(method_exists($this, $method)) {
			return $this->$method($value, $options, $reverse);
		} else {
			throw new Exception('Type crypt has no encryption method with name > '.$method);
		}
 	}
	
	/**
	 * ROW DEFAULT DATA
	 */
	
	public function default_unixtime() {
		return time();
	}
	
	/**
	 * CRYP METHODS
	 */
	
	public function crypt_openssl($value, $options, $reverse) {
		$method = 'AES256';
		if(isset($options['method'])) {
			$method = $options['method'];
		}
		
		if($reverse) {
			$value = openssl_decrypt($value, $method, $options['key']);
		} else {
			$value = openssl_encrypt($value, $method, $options['key']);
		}
		
		return $value;
	}
	
	public function crypt_mcrypt($value, $options, $reverse) {
		$iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
    	$iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
    	
    	if($reverse) {
    		$value = mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $options['key'], $value, MCRYPT_MODE_ECB, $iv);
    	} else {
			$value = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $options['key'], $value, MCRYPT_MODE_ECB, $iv);
    	}
    	
    	return $value;
	}
}

/**
 * Duplicate row exception
 * @author nikis
 *
 */
class onDuplicateRow extends Exception {
	
	/**
	 * @var Row
	 */
	protected $row;
	
	public function __construct(Row $row) {
		$this->row = $row;
	}
	
	/**
	 * Get duplicate row
	 * @return Row
	 */
	public function getRow() {
		return $this->row;
	}
}