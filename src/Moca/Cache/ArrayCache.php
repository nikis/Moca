<?php
namespace Moca\Cache;
use Exception;

class ArrayCache extends Provider {
	
	private $data = array();
	
	public function enableBuffering($bool) {
		throw new Exception('ArrayCache cannot use buffer');
	}
	
	protected function doGet($key) {
		return $this->doHas($key) ? $this->data[$key] : false;
	}
	
	protected function doSet($key, $val, $lifetime=0) {
		return $this->data[$key] = $val;
	}
	
	protected function doDelete($key) {
		if($this->doHas($key)) {
			unset($this->data[$key]);
		}
	}
	
	protected function doHas($key) {
		return array_key_exists($key, $this->data);
	}
}