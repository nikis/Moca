<?php
namespace Moca\Cache;

class ApcCache extends Provider {
	
	protected function doGet($key) {
		return \apc_fetch($key);
	}
	
	protected function doSet($key, $val, $lifetime=0) {
		return \apc_add($key, $val, $lifetime);
	}
	
	protected function doDelete($key) {
		return \apc_delete($key);
	}
	
	protected function doHas($key) {
		return \apc_exists($key);
	}
}