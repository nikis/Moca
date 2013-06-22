<?php
namespace Moca\Cache\Logger;

interface CacheLogger {
	
	public function start($method, $key);
	
	public function stop();
}