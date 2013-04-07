<?php
namespace Moca\Database;

use Serializable;

class Entity implements Serializable {
	
	/**
	 * @var array
	 */
	protected $_data = array();
	
	/**
	 * @var array
	 */
	protected $_modifiedData = array();
	
	public function __construct(array $data=array()) {
		$this->_data = $data;
	}
	
	/**
	 * Store key with value
	 * @param $key
	 * @param $val
	 */
	public function set($key, $val) {
		if($this->has($key) && false === $this->_data[$key] instanceof Entity) {
			if($this->_data[$key] != $val) {
				$this->_modifiedData[$key] = $this->_data[$key];
			}
		}
		return $this->_triggerSetter($key, $val);
	}
	
	/**
	 * Check if key exist
	 * @param $key
	 * @return boolean
	 */
	public function has($key) {
		return array_key_exists($key, $this->_data);
	}
	
	/**
	 * Get value for given key or return given default value
	 * @param $key
	 * @param $default
	 */
	public function get($key, $default=null) {
		return $this->_triggerGetter($key, $default);
	}
	
	/**
	 * Remove given key if exist
	 * @param $key
	 * @return boolean
	 */
	public function remove($key) {
		if($this->has($key)) {
			unset($this->_data[$key]);
			if(array_key_exists($key, $this->_modifiedData)) {
				unset($this->_modifiedData[$key]);
			}
			return true;
		}
		return  false;
	}
	
	/**
	 * Replace current data with new one
	 * @param array $data
	 */
	public function replace(array $data) {
		foreach($data as $key=>$val) {
			$this->set($key, $val);
		}
	}
	
	public function getModifiedData() {
		return $this->_modifiedData;
	}
	
	/**
	 * Get data as array
	 * @return array
	 */
	public function toArray() {
		$result = array();
		foreach($this->_data as $key=>$val) {
			if($val instanceof Entity) {
				$val = $val->toArray();
			}
			$result[$key] = $val;
		}	
		return $result;
	}
	
	/**
	 * Get data as json
	 * @return array
	 */
	public function toJson() {
		$data = $this->toArray();
		return json_encode($data);
	}
	
	/**
	 * Trigger setter for given key
	 * If method with name set_$key exist call and then set returned value
	 * @param $key
	 * @param $val
	 */
	protected function _triggerSetter($key, $val) {
		$method = 'set_'.$key;
		if(method_exists($this, $method)) {
			$val = call_user_func(array($this, $method), $val);
		} 
		$this->_data[$key] = $val;
		return $val;
	}
	
	/**
	 * Trigger getter for given key
	 * If method exist get_$key call and then return value
	 * @param $key
	 * @param $default
	 */
	protected function _triggerGetter($key, $default) {
		if($this->has($key)) {
			$val = $this->_data[$key];
			$method = 'get_'.$key;
			if(method_exists($this, $method)) {
				$val = call_user_func(array($this, $method), $val);
			}
		} else {
			$val = $default;
		}
		return $val;
	}
	
	public function serialize() {
		return serialize($this->_data);
	}
	
	public function unserialize($serialized) {
		$data = unserialize($serialized);
		$this->replace($data);
	}
	
	/**
	 * Magic methods
	 */
	
	public function __set($key, $val) {
		return $this->set($key, $val);
	}
	
	public function __isset($key) {
		return $this->has($key);
	}
	
	public function __get($key) {
		return $this->get($key);
	}
	
	public function __unset($key) {
		return $this->remove($key);
	}
}