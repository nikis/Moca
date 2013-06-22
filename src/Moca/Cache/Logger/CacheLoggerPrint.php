<?php
namespace Moca\Cache\Logger;

class CacheLoggerPrint implements CacheLogger {
	
	public function start($method, $key) {
		echo $method.'->'.$key.PHP_EOL;
	}
	
	public function stop() {}
}