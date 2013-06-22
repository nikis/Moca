<?php
namespace Moca\Cache;
use Memcache;

class MemcacheCache extends Provider {
	
	public function setMemcache(Memcache $mem) {
		$this->memcache = $mem;		
	}
	
	protected function doGet($key) {
		return $this->memcache->get($key);
	}
	
	protected function doSet($key, $val, $lifetime=0) {
		return $this->memcache->set($key, $val, false, $lifetime);
	}
	
	protected function doDelete($key) {
		return $this->memcache->delete($key);
	}
	
	protected function doHas($key) {
		return false !== $this->doGet($key) ? true : false;
	}
}